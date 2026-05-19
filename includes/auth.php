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

// Add HTTP security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; worker-src 'self'; manifest-src 'self'; connect-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;");
}

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
        header('Location: ' . app_url('/auth/login'));
        exit;
    }
    // Check if account is still active in DB
    $db = getDB();
    $stmt = $db->prepare('SELECT status, role, auth_version FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $row = $stmt->fetch();
    if (!$row || $row['status'] !== 'active') {
        session_destroy_full();
        header('Location: ' . app_url('/auth/login?error=suspended'));
        exit;
    }
    if ((int)$row['auth_version'] !== (int)($user['auth_version'] ?? 1)) {
        session_destroy_full();
        header('Location: ' . app_url('/auth/login?reauth=1'));
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
        header('Location: ' . app_url('/'));
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
        header('Location: ' . app_url('/subscribe'));
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
