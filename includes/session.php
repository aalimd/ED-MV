<?php
/**
 * Secure Session Management + CSRF
 * ──────────────────────────────────
 */

require_once __DIR__ . '/../config.php';

function init_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly'  => true,
        'samesite'  => 'Strict',
    ]);

    session_start();

    // Session timeout check
    if (isset($_SESSION['last_activity'])) {
        $timeout = $_SESSION['remember_me'] ?? false ? REMEMBER_ME_LIFETIME : SESSION_LIFETIME;
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
    $_SESSION['user_id']     = $user['id'];
    $_SESSION['user_name']   = $user['name'];
    $_SESSION['user_email']  = $user['email'];
    $_SESSION['user_role']   = $user['role'];
    $_SESSION['user_status'] = $user['status'];
    $_SESSION['auth_version'] = (int)($user['auth_version'] ?? 1);
    $_SESSION['logged_in']   = true;
    $_SESSION['login_time']  = time();
    $_SESSION['remember_me'] = $rememberMe;
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
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
