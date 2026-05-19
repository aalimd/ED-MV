<?php
/**
 * Rate Limiting — Brute-Force Protection
 * ────────────────────────────────────────
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config.php';

function rate_limit_config(string $action): array {
    return match ($action) {
        'register' => ['window' => 60, 'attempts' => 3, 'lockout' => 120],
        'password_reset' => ['window' => 15, 'attempts' => 5, 'lockout' => 15],
        'email_verification' => ['window' => 15, 'attempts' => 5, 'lockout' => 15],
        default => ['window' => LOCKOUT_MINUTES, 'attempts' => MAX_LOGIN_ATTEMPTS, 'lockout' => LOCKOUT_MINUTES],
    };
}

/**
 * Check if IP is currently locked out for a specific action
 */
function is_rate_limited(string $ip, string $action = 'login'): bool {
    $db = getDB();
    // Clean expired lockouts
    $db->prepare('DELETE FROM login_attempts WHERE locked_until IS NOT NULL AND locked_until < NOW()')->execute();

    $stmt = $db->prepare(
        'SELECT attempts, locked_until FROM login_attempts WHERE ip = ? AND action = ? ORDER BY first_attempt DESC LIMIT 1'
    );
    $stmt->execute([$ip, $action]);
    $row = $stmt->fetch();

    if (!$row) return false;

    if ($row['locked_until'] && strtotime($row['locked_until']) > time()) {
        return true;
    }

    return false;
}

/**
 * Get remaining lockout seconds
 */
function lockout_remaining(string $ip, string $action = 'login'): int {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT locked_until FROM login_attempts WHERE ip = ? AND action = ? AND locked_until > NOW() LIMIT 1'
    );
    $stmt->execute([$ip, $action]);
    $row = $stmt->fetch();
    if (!$row) return 0;
    return max(0, strtotime($row['locked_until']) - time());
}

/**
 * Record a failed attempt for an action
 */
function record_attempt(string $ip, ?string $email = null, string $action = 'login'): void {
    $db = getDB();
    $config = rate_limit_config($action);
    $windowMinutes = $config['window'];
    $maxAttempts = $config['attempts'];
    $lockoutMinutes = $config['lockout'];

    $windowStart = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));

    // Count recent attempts
    $stmt = $db->prepare(
        'SELECT COUNT(*) as cnt FROM login_attempts WHERE ip = ? AND action = ? AND first_attempt > ?'
    );
    $stmt->execute([$ip, $action, $windowStart]);
    $count = (int) $stmt->fetch()['cnt'];

    $locked = null;
    if ($count + 1 >= $maxAttempts) {
        $locked = date('Y-m-d H:i:s', time() + ($lockoutMinutes * 60));
    }

    $stmt = $db->prepare(
        'INSERT INTO login_attempts (ip, email, action, attempts, locked_until) VALUES (?, ?, ?, 1, ?)'
    );
    $stmt->execute([$ip, $email, $action, $locked]);
}

/**
 * Legacy wrapper for record_failed_attempt
 */
function record_failed_attempt(string $ip, string $email): void {
    record_attempt($ip, $email, 'login');
}

/**
 * Clear attempts for an IP and action
 */
function clear_login_attempts(string $ip, string $action = 'login'): void {
    $db = getDB();
    $db->prepare('DELETE FROM login_attempts WHERE ip = ? AND action = ?')->execute([$ip, $action]);
}
