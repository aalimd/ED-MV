<?php
/**
 * Auth Middleware
 * ───────────────
 * Call these at the top of any page that needs protection.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

init_session();

/**
 * Require user to be logged in. Redirects to login if not.
 */
function require_login(): void {
    $user = session_user();
    if (!$user) {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/index.php';
        $_SESSION['redirect_after_login'] = (is_string($requestUri) && str_starts_with($requestUri, '/') && !str_starts_with($requestUri, '//'))
            ? $requestUri
            : '/index.php';
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
    // Check if account is still active in DB
    $db = getDB();
    $stmt = $db->prepare('SELECT status, role FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if (!$row || $row['status'] !== 'active') {
        session_destroy_full();
        header('Location: ' . APP_URL . '/auth/login.php?error=suspended');
        exit;
    }
    // Sync role if changed by admin
    if ($row['role'] !== $_SESSION['user_role']) {
        $_SESSION['user_role'] = $row['role'];
    }
    $_SESSION['user_status'] = $row['status'];
}

/**
 * Require admin role. Redirects if not admin.
 */
function require_admin(): void {
    require_login();
    $user = session_user();
    if ($user['role'] !== 'admin') {
        header('Location: ' . APP_URL . '/index.php');
        exit;
    }
}

/**
 * Require active subscription. Redirects to subscription page if expired.
 */
function require_subscription(): void {
    require_login();
    $user = session_user();
    // Admins bypass subscription check
    if ($user['role'] === 'admin') return;

    $db = getDB();
    $stmt = $db->prepare(
        "SELECT id FROM subscriptions 
         WHERE user_id = ? 
           AND status = 'active' 
           AND (expires_at IS NULL OR expires_at > NOW())
         LIMIT 1"
    );
    $stmt->execute([$user['id']]);
    if (!$stmt->fetch()) {
        header('Location: ' . APP_URL . '/subscribe.php');
        exit;
    }
}

/**
 * Check if current user is admin (non-redirecting)
 */
function is_admin(): bool {
    $user = session_user();
    return $user && $user['role'] === 'admin';
}

/**
 * Check if current user is logged in (non-redirecting)
 */
function is_logged_in(): bool {
    return session_user() !== null;
}

/**
 * Check if user has active subscription (non-redirecting)
 */
function has_subscription(): bool {
    $user = session_user();
    if (!$user) return false;
    if ($user['role'] === 'admin') return true;

    $db = getDB();
    $stmt = $db->prepare(
        "SELECT id FROM subscriptions 
         WHERE user_id = ? 
           AND status = 'active' 
           AND (expires_at IS NULL OR expires_at > NOW())
         LIMIT 1"
    );
    $stmt->execute([$user['id']]);
    return (bool) $stmt->fetch();
}

/**
 * Log an activity
 */
function log_activity(string $action, ?string $details = null, ?int $userId = null): void {
    $db = getDB();
    $user = session_user();
    $stmt = $db->prepare(
        'INSERT INTO activity_log (user_id, action, details, ip, user_agent) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $userId ?? ($user['id'] ?? null),
        $action,
        $details,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
    ]);
}
