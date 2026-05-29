<?php
/**
 * Helper Utilities
 * ─────────────────
 */

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/db.php';

/**
 * Sanitize output for HTML
 */
function e(?string $str): string {
    if ($str === null) return '';
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

/**
 * Get the app base path from APP_URL, e.g. "/ED-MV".
 */
function app_base_path(): string {
    if (request_is_local()) {
        $requestBase = request_base_path();
        if ($requestBase !== '') {
            return $requestBase;
        }
    }

    $path = parse_url(APP_URL, PHP_URL_PATH);
    if (!is_string($path) || $path === '' || $path === '/') {
        return '';
    }
    return '/' . trim($path, '/');
}

function host_without_port(string $host): string {
    $host = trim($host);
    if ($host === '') {
        return '';
    }
    if ($host[0] === '[') {
        $end = strpos($host, ']');
        return $end === false ? $host : substr($host, 1, $end - 1);
    }
    $colon = strpos($host, ':');
    return $colon === false ? $host : substr($host, 0, $colon);
}

function is_local_host(string $host): bool {
    return in_array(strtolower(host_without_port($host)), ['localhost', '127.0.0.1', '::1'], true);
}

function request_host(): string {
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    return is_string($host) ? trim($host) : '';
}

function request_is_local(): bool {
    $host = request_host();
    return $host !== '' && is_local_host($host);
}

function request_base_path(): string {
    if (empty($_SERVER['SCRIPT_NAME']) && empty($_SERVER['REQUEST_URI'])) {
        return '';
    }

    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptFile = realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
    $appRoot = realpath(APP_ROOT);
    if ($scriptName !== '' && is_string($scriptFile) && is_string($appRoot)) {
        $scriptFile = str_replace('\\', '/', $scriptFile);
        $appRoot = rtrim(str_replace('\\', '/', $appRoot), '/');
        if (str_starts_with($scriptFile, $appRoot . '/')) {
            $relativeScript = substr($scriptFile, strlen($appRoot));
            if ($relativeScript !== '' && str_ends_with($scriptName, $relativeScript)) {
                $base = substr($scriptName, 0, -strlen($relativeScript));
                $base = '/' . trim($base, '/');
                return $base === '/' ? '' : $base;
            }
        }
    }

    $requestUri = (string)($_SERVER['REQUEST_URI'] ?? '');
    if (str_starts_with($requestUri, '/ED-MV/')) {
        return '/ED-MV';
    }

    return '';
}

/**
 * Detect the scheme (http/https) for the current request.
 * Falls back to APP_URL's scheme on the CLI or when nothing is detectable.
 */
function current_scheme(): string {
    $configuredScheme = parse_url(APP_URL, PHP_URL_SCHEME);
    $configuredHost = parse_url(APP_URL, PHP_URL_HOST);
    if (request_host() !== '') {
        if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
            return 'https';
        }
        if (defined('TRUST_PROXIES') && TRUST_PROXIES) {
            $forwarded = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
            if (is_string($forwarded) && strtolower(strtok($forwarded, ',')) === 'https') {
                return 'https';
            }
        }
        if (($_SERVER['SERVER_PORT'] ?? '') === '443' || ($_SERVER['SERVER_PORT'] ?? '') === 443) {
            return 'https';
        }
        if (!request_is_local() && $configuredScheme === 'https' && is_string($configuredHost) && !is_local_host($configuredHost)) {
            return 'https';
        }
        if (request_is_local()) {
            return 'http';
        }
    }
    return is_string($configuredScheme) && $configuredScheme !== '' ? $configuredScheme : 'http';
}

/**
 * Build an in-app URL that always uses the scheme of the current request.
 * This prevents mixed-content errors when the page is loaded over HTTPS but
 * APP_URL is configured with HTTP (or vice versa).
 *
 * Example:
 *   app_url('/admin/users') → https://localhost/ED-MV/admin/users (on HTTPS)
 *   app_url('/admin/users') → http://localhost/ED-MV/admin/users  (on HTTP)
 */
function app_url(string $path = ''): string {
    $parts = parse_url(APP_URL);
    if (!is_array($parts) || empty($parts['host'])) {
        return rtrim(APP_URL, '/') . ($path === '' ? '' : '/' . ltrim($path, '/'));
    }

    $scheme = current_scheme();
    $host   = $parts['host'];
    $port   = isset($parts['port']) ? ':' . $parts['port'] : '';
    $base   = isset($parts['path']) ? '/' . trim($parts['path'], '/') : '';
    if (request_is_local()) {
        $host = request_host();
        $port = '';
        $base = request_base_path();
    }
    if ($base === '/') {
        $base = '';
    }

    if ($path === '') {
        return $scheme . '://' . $host . $port . $base;
    }

    return $scheme . '://' . $host . $port . $base . '/' . ltrim($path, '/');
}

/**
 * Convenience alias for static asset URLs (CSS, JS, images).
 */
function asset_url(string $path): string {
    return app_url($path);
}

/**
 * Normalize a local path so redirects stay inside the app, even when deployed in a subdirectory.
 */
function normalize_local_path(string $path, string $fallback = '/'): string {
    $path = trim($path);
    if ($path === '' || preg_match('/[\r\n]/', $path)) {
        return $fallback;
    }
    if (!str_starts_with($path, '/') || str_starts_with($path, '//')) {
        return $fallback;
    }

    $parts = parse_url($path);
    if ($parts === false) {
        return $fallback;
    }

    $cleanPath = $parts['path'] ?? '/';
    $queryPart = $parts['query'] ?? null;
    $query = is_string($queryPart) && $queryPart !== '' ? '?' . $queryPart : '';
    $appBase = app_base_path();

    if ($appBase !== '' && ($cleanPath === $appBase || str_starts_with($cleanPath, $appBase . '/'))) {
        $cleanPath = substr($cleanPath, strlen($appBase));
        if ($cleanPath === '') {
            $cleanPath = '/';
        }
    }

    if (!str_starts_with($cleanPath, '/') || str_starts_with($cleanPath, '//')) {
        return $fallback;
    }

    return $cleanPath . $query;
}

/**
 * Redirect only to an in-app path to avoid sending users to external URLs.
 */
function redirect_local(string $path, string $fallback = '/'): void {
    redirect(app_url(normalize_local_path($path, $fallback)));
}

/**
 * Set a flash message (shown once on next page load)
 */
function flash(string $type, string $message): void {
    init_session();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash messages
 */
function get_flashes(): array {
    init_session();
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/**
 * Render flash messages as HTML.
 *
 * Outputs:
 *   1. A hidden JSON script tag consumed by assets/js/toast.js to show
 *      beautiful slide-in toast notifications.
 *   2. Traditional inline `.flash` divs as a no-JS fallback (hidden via
 *      the `noscript-flash` class when JS is available).
 */
function render_flashes(): string {
    $flashes = get_flashes();
    if (empty($flashes)) return '';

    // 1. JSON data for the toast system (consumed by toast.js on DOMContentLoaded)
    $jsonData = json_encode(
        array_map(fn($f) => ['type' => $f['type'], 'message' => $f['message']], $flashes),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
    );
    $html = '<script type="application/json" id="toast-flash-data">' . $jsonData . '</script>';

    // 2. Inline fallback (hidden by JS, visible without JS)
    $html .= '<noscript>';
    foreach ($flashes as $f) {
        $type = e($f['type']);
        $msg = e($f['message']);
        $icon = match($type) {
            'success' => '✅',
            'danger'  => '❌',
            'warning' => '⚠️',
            default   => 'ℹ️',
        };
        $html .= "<div class=\"flash flash-{$type}\">{$icon} {$msg}</div>";
    }
    $html .= '</noscript>';

    return $html;
}

/**
 * Return the CSS + JS tags for the toast notification system.
 * Include this in the <head> (CSS) or before </body> (JS) of every page.
 */
function toast_head_tag(): string {
    return '<link rel="stylesheet" href="' . asset_url('/assets/css/toast.css?v=2') . '">';
}

function toast_script_tag(): string {
    return '<script src="' . asset_url('/assets/js/toast.js?v=2') . '"></script>';
}

/**
 * Get client IP (handles proxies)
 */
function client_ip(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (defined('TRUST_PROXIES') && TRUST_PROXIES) {
        $trusted = defined('TRUSTED_PROXY_IPS') ? TRUSTED_PROXY_IPS : [];
        if (empty($trusted) || in_array($ip, $trusted)) {
            $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
            if (is_string($forwarded) && $forwarded !== '') {
                $ips = explode(',', $forwarded);
                $ip = trim($ips[0]);
            }
        }
    }
    return $ip;
}

/**
 * Format date for display
 */
function fmt_date(?string $date): string {
    if (!$date) return '—';
    return date('M j, Y', strtotime($date));
}

/**
 * Format date with time
 */
function fmt_datetime(?string $date): string {
    if (!$date) return '—';
    return date('M j, Y g:i A', strtotime($date));
}

/**
 * Time ago format
 */
function time_ago(string $datetime): string {
    $now = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);
    if ($diff->y > 0) return $diff->y . 'y ago';
    if ($diff->m > 0) return $diff->m . 'mo ago';
    if ($diff->d > 0) return $diff->d . 'd ago';
    if ($diff->h > 0) return $diff->h . 'h ago';
    if ($diff->i > 0) return $diff->i . 'min ago';
    return 'Just now';
}

/**
 * Validate email format
 */
function valid_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check password strength: min 8 chars, 1 uppercase, 1 digit, 1 special char
 */
function validate_password(string $password): array {
    $errors = [];
    if (mb_strlen($password, 'UTF-8') < 8) $errors[] = 'Minimum 8 characters';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'At least one uppercase letter';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'At least one number';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'At least one special character';
    return $errors;
}

/**
 * Generate a temporary password that always meets the app password policy.
 */
function generate_temporary_password(int $randomLength = 8): string {
    $alphabet = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $specials = '!@#$%^&*';
    $random = '';

    for ($i = 0; $i < $randomLength; $i++) {
        $random .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    $characters = str_split('A' . 'b' . '7' . $specials[random_int(0, strlen($specials) - 1)] . $random);
    for ($i = count($characters) - 1; $i > 0; $i--) {
        $j = random_int(0, $i);
        [$characters[$i], $characters[$j]] = [$characters[$j], $characters[$i]];
    }

    return implode('', $characters);
}

/**
 * Get app setting from database
 */
function get_setting(string $key, string $default = ''): string {
    static $cache = [];
    if (isset($cache[$key])) return $cache[$key];
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ?');
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        $cache[$key] = $row ? ($row['setting_value'] ?? $default) : $default;
    } catch (Exception) {
        $cache[$key] = $default;
    }
    return $cache[$key];
}

/**
 * Set app setting
 */
function set_setting(string $key, string $value): void {
    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?) 
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

/**
 * JSON response helper (for API endpoints)
 */
function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
