<?php
/**
 * Auth Middleware
 * ───────────────
 * Call these at the top of any page that needs protection.
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

init_session();

/**
 * Require user to be logged in. Redirects to login if not.
 */
function require_login(): void {
    $user = session_user();
    if (!$user) {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $_SESSION['redirect_after_login'] = is_string($requestUri)
            ? normalize_local_path($requestUri)
            : '/';
        header('Location: ' . APP_URL . '/auth/login');
        exit;
    }
    // Check if account is still active in DB
    $db = getDB();
    $stmt = $db->prepare('SELECT status, role, auth_version FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if (!$row || $row['status'] !== 'active') {
        session_destroy_full();
        header('Location: ' . APP_URL . '/auth/login?reauth=1');
        exit;
    }
    if ((int)$row['auth_version'] !== (int)($user['auth_version'] ?? 1)) {
        session_destroy_full();
        header('Location: ' . APP_URL . '/auth/login?reauth=1');
        exit;
    }
    // Sync role if changed by admin
    if ($row['role'] !== $_SESSION['user_role']) {
        $_SESSION['user_role'] = $row['role'];
    }
    $_SESSION['user_status'] = $row['status'];
    $_SESSION['auth_version'] = (int)$row['auth_version'];
}

/**
 * Require admin role. Redirects if not admin.
 */
function require_admin(): void {
    require_login();
    $user = session_user();
    if ($user['role'] !== 'admin') {
        header('Location: ' . APP_URL . '/');
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
        header('Location: ' . APP_URL . '/subscribe');
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
 * Increment auth_version so stale sessions lose privileges.
 */
function bump_auth_version(int $userId): void {
    if ($userId <= 0) {
        return;
    }
    $db = getDB();
    $db->prepare('UPDATE users SET auth_version = auth_version + 1 WHERE id = ?')->execute([$userId]);
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
