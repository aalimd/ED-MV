<?php
/**
 * Rate Limiting — Brute-Force Protection
 * ────────────────────────────────────────
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/security_settings.php';

function rate_limit_config(string $action): array {
    $window = match ($action) {
        'register' => 60,
        'password_reset', 'email_verification' => 15,
        default => effective_lockout_minutes($action),
    };

    return match ($action) {
        'register' => ['window' => $window, 'attempts' => effective_max_attempts($action), 'lockout' => effective_lockout_minutes($action)],
        'password_reset', 'email_verification' => ['window' => $window, 'attempts' => effective_max_attempts($action), 'lockout' => effective_lockout_minutes($action)],
        default => ['window' => $window, 'attempts' => effective_max_attempts($action), 'lockout' => effective_lockout_minutes($action)],
    };
}

function rate_limit_email(?string $email): ?string {
    $email = strtolower(trim((string)$email));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
}

/**
 * Check if IP is currently locked out for a specific action
 */
function is_rate_limited(string $ip, string $action = 'login', ?string $email = null): bool {
    $db = getDB();
    $email = rate_limit_email($email);
    // Clean expired lockouts
    $db->prepare('DELETE FROM login_attempts WHERE locked_until IS NOT NULL AND locked_until < NOW()')->execute();

    if ($email !== null) {
        $stmt = $db->prepare(
            'SELECT locked_until FROM login_attempts
             WHERE action = ?
               AND locked_until > NOW()
               AND (ip = ? OR email = ?)
             ORDER BY locked_until DESC
             LIMIT 1'
        );
        $stmt->execute([$action, $ip, $email]);
    } else {
        $stmt = $db->prepare(
            'SELECT locked_until FROM login_attempts
             WHERE ip = ? AND action = ? AND locked_until > NOW()
             ORDER BY locked_until DESC
             LIMIT 1'
        );
        $stmt->execute([$ip, $action]);
    }
    $row = $stmt->fetch();

    if (!$row) return false;

    return $row['locked_until'] && strtotime($row['locked_until']) > time();
}

/**
 * Get remaining lockout seconds
 */
function lockout_remaining(string $ip, string $action = 'login', ?string $email = null): int {
    $db = getDB();
    $email = rate_limit_email($email);
    if ($email !== null) {
        $stmt = $db->prepare(
            'SELECT locked_until FROM login_attempts
             WHERE action = ?
               AND locked_until > NOW()
               AND (ip = ? OR email = ?)
             ORDER BY locked_until DESC
             LIMIT 1'
        );
        $stmt->execute([$action, $ip, $email]);
    } else {
        $stmt = $db->prepare(
            'SELECT locked_until FROM login_attempts WHERE ip = ? AND action = ? AND locked_until > NOW() ORDER BY locked_until DESC LIMIT 1'
        );
        $stmt->execute([$ip, $action]);
    }
    $row = $stmt->fetch();
    if (!$row) return 0;
    return max(0, strtotime($row['locked_until']) - time());
}

/**
 * Record a failed attempt for an action
 */
function record_attempt(string $ip, ?string $email = null, string $action = 'login'): void {
    $db = getDB();
    $email = rate_limit_email($email);
    $config = rate_limit_config($action);
    $windowMinutes = $config['window'];
    $maxAttempts = $config['attempts'];
    $lockoutMinutes = $config['lockout'];

    $windowStart = date('Y-m-d H:i:s', time() - ($windowMinutes * 60));

    // Cleanup old non-locked attempts
    $db->prepare('DELETE FROM login_attempts WHERE locked_until IS NULL AND action = ? AND first_attempt < ?')->execute([$action, $windowStart]);

    // Count recent attempts by IP and by account/email.
    $stmt = $db->prepare(
        'SELECT COUNT(*) as cnt FROM login_attempts WHERE ip = ? AND action = ? AND first_attempt > ?'
    );
    $stmt->execute([$ip, $action, $windowStart]);
    $ipCount = (int) $stmt->fetch()['cnt'];

    $emailCount = 0;
    if ($email !== null) {
        $stmt = $db->prepare(
            'SELECT COUNT(*) as cnt FROM login_attempts WHERE email = ? AND action = ? AND first_attempt > ?'
        );
        $stmt->execute([$email, $action, $windowStart]);
        $emailCount = (int) $stmt->fetch()['cnt'];
    }

    $locked = null;
    if (($ipCount + 1) >= $maxAttempts || ($email !== null && ($emailCount + 1) >= $maxAttempts)) {
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
function clear_login_attempts(string $ip, string $action = 'login', ?string $email = null): void {
    $db = getDB();
    $email = rate_limit_email($email);
    if ($email !== null) {
        $db->prepare('DELETE FROM login_attempts WHERE action = ? AND (ip = ? OR email = ?)')->execute([$action, $ip, $email]);
        return;
    }
    $db->prepare('DELETE FROM login_attempts WHERE ip = ? AND action = ?')->execute([$ip, $action]);
}
