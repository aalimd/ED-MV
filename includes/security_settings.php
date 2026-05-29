<?php
declare(strict_types=1);

/**
 * Centralized runtime security settings.
 *
 * Values are read from app_settings when available and fall back to config.php
 * constants during early installation, CLI repair, or database outages.
 */

require_once __DIR__ . '/db.php';

function security_setting(string $key, string $default = ''): string {
    static $cache = [];
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    try {
        $stmt = getDB()->prepare('SELECT setting_value FROM app_settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        $cache[$key] = $value === false || $value === null ? $default : (string)$value;
    } catch (Throwable) {
        $cache[$key] = $default;
    }

    return $cache[$key];
}

function security_setting_int(string $key, int $default, int $min, int $max): int {
    $raw = security_setting($key, (string)$default);
    $value = filter_var($raw, FILTER_VALIDATE_INT);
    if ($value === false) {
        $value = $default;
    }
    return max($min, min($max, (int)$value));
}

function effective_session_lifetime(): int {
    $defaultMinutes = max(1, (int)ceil(SESSION_LIFETIME / 60));
    return security_setting_int('session_timeout_minutes', $defaultMinutes, 5, 1440) * 60;
}

function effective_max_attempts(string $action = 'login'): int {
    return match ($action) {
        'register' => 3,
        default => security_setting_int('max_login_attempts', MAX_LOGIN_ATTEMPTS, 3, 20),
    };
}

function effective_lockout_minutes(string $action = 'login'): int {
    return match ($action) {
        'register' => 120,
        default => security_setting_int('lockout_minutes', LOCKOUT_MINUTES, 5, 60),
    };
}
