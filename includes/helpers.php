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
function e(string $str): string {
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
    $path = parse_url(APP_URL, PHP_URL_PATH);
    if (!is_string($path) || $path === '' || $path === '/') {
        return '';
    }
    return '/' . trim($path, '/');
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
    $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
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
    redirect(APP_URL . normalize_local_path($path, $fallback));
}

/**
 * Set a flash message (shown once on next page load)
 */
function flash(string $type, string $message): void {
    init_session();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message, 'html' => false];
}

/**
 * Flash message that may contain intentional HTML (admin-only trusted content).
 */
function flash_html(string $type, string $message): void {
    init_session();
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message, 'html' => true];
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
 * Render flash messages as HTML
 */
function render_flashes(): string {
    $flashes = get_flashes();
    if (empty($flashes)) return '';
    $html = '';
    foreach ($flashes as $f) {
        $type = e($f['type']);
        $msg = !empty($f['html']) ? $f['message'] : e($f['message']);
        $icon = match($type) {
            'success' => '✅',
            'danger'  => '❌',
            'warning' => '⚠️',
            default   => 'ℹ️',
        };
        $html .= "<div class=\"flash flash-{$type}\">{$icon} {$msg}</div>";
    }
    return $html;
}

/**
 * Get client IP (handles proxies)
 */
function client_ip(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
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
    if (strlen($password) < 8) $errors[] = 'Minimum 8 characters';
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
