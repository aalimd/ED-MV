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
 * Require the current user to have a specific paid feature.
 */
function require_feature(string $key, string $featureName = 'This feature'): void {
    require_login();
    require_once __DIR__ . '/features.php';

    if (!has_feature($key)) {
        flash('warning', $featureName . ' is not included in your current plan.');
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
        client_ip(),
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
    ]);
}

function maintenance_mode_enabled(): bool {
    return get_setting('maintenance_mode', '0') === '1';
}

function current_request_path(): string {
    $path = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    return is_string($path) ? $path : '';
}

function maintenance_route_allowed(): bool {
    $path = rtrim(current_request_path(), '/');
    return str_ends_with($path, '/auth/login')
        || str_ends_with($path, '/auth/login.php')
        || str_ends_with($path, '/auth/logout')
        || str_ends_with($path, '/auth/logout.php');
}

function render_maintenance_page(): never {
    http_response_code(503);
    if (!headers_sent()) {
        header('Retry-After: 600');
    }
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover"><title>Maintenance</title>';
    echo '<link rel="stylesheet" href="' . e(asset_url('/assets/css/auth.css?v=6')) . '"></head><body>';
    echo '<div class="auth-wrapper"><div class="auth-card" style="text-align:center"><div style="font-size:3rem;margin-bottom:12px">🔧</div>';
    echo '<h1 class="auth-title">Under Maintenance</h1>';
    echo '<p class="auth-subtitle">We\'re updating VentGuide Pro. Please check back shortly.</p></div></div></body></html>';
    exit;
}

function enforce_maintenance_mode(): void {
    if (!maintenance_mode_enabled() || maintenance_route_allowed()) {
        return;
    }

    $user = session_user();
    if ($user && ($user['role'] ?? '') === 'admin') {
        return;
    }

    render_maintenance_page();
}

enforce_maintenance_mode();
