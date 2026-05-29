<?php
/**
 * Secure Session Management + CSRF
 * ──────────────────────────────────
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/security_settings.php';

function session_request_is_secure(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (($_SERVER['SERVER_PORT'] ?? null) === '443' || ($_SERVER['SERVER_PORT'] ?? null) === 443) {
        return true;
    }
    if (defined('TRUST_PROXIES') && TRUST_PROXIES) {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        return is_string($forwarded) && strtolower(trim(strtok($forwarded, ','))) === 'https';
    }
    return false;
}

function session_cookie_options(int $lifetime): array {
    return [
        'lifetime' => $lifetime,
        'path'     => '/',
        'domain'   => '',
        'secure'   => session_request_is_secure(),
        'httponly'  => true,
        'samesite'  => 'Strict',
    ];
}

function session_refresh_cookie(int $lifetime): void {
    if (session_status() !== PHP_SESSION_ACTIVE || headers_sent()) {
        return;
    }
    $options = session_cookie_options($lifetime);
    setcookie(session_name(), session_id(), [
        'expires' => $lifetime > 0 ? time() + $lifetime : 0,
        'path' => $options['path'],
        'domain' => $options['domain'],
        'secure' => $options['secure'],
        'httponly' => $options['httponly'],
        'samesite' => $options['samesite'],
    ]);
}

function init_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $sessionLifetime = effective_session_lifetime();
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', (string)max($sessionLifetime, REMEMBER_ME_LIFETIME));
    
    session_name(SESSION_NAME);
    session_set_cookie_params(session_cookie_options($sessionLifetime));

    session_start();

    // Session timeout check
    if (isset($_SESSION['last_activity'])) {
        $timeout = ($_SESSION['remember_me'] ?? false) ? REMEMBER_ME_LIFETIME : $sessionLifetime;
        if (time() - $_SESSION['last_activity'] > $timeout) {
            session_unset();
            session_destroy();
            session_start();
            session_regenerate_id(true);
        }
    }
    $_SESSION['last_activity'] = time();

    // Generate CSRF token if not exists
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
}

/**
 * Get the current CSRF token
 */
function csrf_token(): string {
    init_session();
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Output a hidden CSRF input field
 */
function csrf_field(): string {
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * Validate CSRF token from POST
 */
function csrf_validate(): bool {
    $token = $_POST[CSRF_TOKEN_NAME] ?? '';
    return hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', $token);
}

/**
 * Regenerate session ID (call on login)
 */
function session_secure_regenerate(): void {
    init_session();
    session_regenerate_id(true);
}

/**
 * Store user in session
 */
function session_set_user(array $user, bool $rememberMe = false): void {
    session_secure_regenerate();
    $cookieLifetime = $rememberMe ? REMEMBER_ME_LIFETIME : effective_session_lifetime();
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_name']   = $user['name'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_role']   = $user['role'];
    $_SESSION['user_status'] = $user['status'];
    $_SESSION['auth_version'] = (int)($user['auth_version'] ?? 1);
    $_SESSION['logged_in']   = true;
    $_SESSION['login_time']  = time();
    $_SESSION['remember_me'] = $rememberMe;
    session_refresh_cookie($cookieLifetime);
}

/**
 * Get current logged-in user info
 */
function session_user(): ?array {
    if (empty($_SESSION['logged_in'])) return null;
    if (empty($_SESSION['user_id']) || empty($_SESSION['user_role'])) return null;
    return [
        'id'     => $_SESSION['user_id'],
        'name'   => $_SESSION['user_name'],
        'email'  => $_SESSION['user_email'],
        'role'   => $_SESSION['user_role'],
        'status' => $_SESSION['user_status'],
        'auth_version' => (int)($_SESSION['auth_version'] ?? 1),
    ];
}

/**
 * Destroy session completely
 */
function session_destroy_full(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        init_session();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $p['path'],
            'domain' => $p['domain'],
            'secure' => (bool)$p['secure'],
            'httponly' => (bool)$p['httponly'],
            'samesite' => $p['samesite'] ?? 'Strict',
        ]);
    }
    session_destroy();
}
