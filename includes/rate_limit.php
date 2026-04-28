<?php
/**
 * Rate Limiting — Brute-Force Protection
 * ────────────────────────────────────────
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../config.php';

/**
 * Check if IP is currently locked out
 */
function is_rate_limited(string $ip): bool {
    $db = getDB();
    // Clean expired lockouts
    $db->prepare('DELETE FROM login_attempts WHERE locked_until IS NOT NULL AND locked_until < NOW()')->execute();

    $stmt = $db->prepare(
        'SELECT attempts, locked_until FROM login_attempts WHERE ip = ? ORDER BY first_attempt DESC LIMIT 1'
    );
    $stmt->execute([$ip]);
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
function lockout_remaining(string $ip): int {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT locked_until FROM login_attempts WHERE ip = ? AND locked_until > NOW() LIMIT 1'
    );
    $stmt->execute([$ip]);
    $row = $stmt->fetch();
    if (!$row) return 0;
    return max(0, strtotime($row['locked_until']) - time());
}

/**
 * Record a failed login attempt
 */
function record_failed_attempt(string $ip, string $email): void {
    $db = getDB();
    $windowStart = date('Y-m-d H:i:s', time() - (LOCKOUT_MINUTES * 60));

    // Count recent attempts
    $stmt = $db->prepare(
        'SELECT COUNT(*) as cnt FROM login_attempts WHERE ip = ? AND first_attempt > ?'
    );
    $stmt->execute([$ip, $windowStart]);
    $count = (int) $stmt->fetch()['cnt'];

    $locked = null;
    if ($count + 1 >= MAX_LOGIN_ATTEMPTS) {
        $locked = date('Y-m-d H:i:s', time() + (LOCKOUT_MINUTES * 60));
    }

    $stmt = $db->prepare(
        'INSERT INTO login_attempts (ip, email, attempts, locked_until) VALUES (?, ?, 1, ?)'
    );
    $stmt->execute([$ip, $email, $locked]);
}

/**
 * Clear attempts for an IP (call on successful login)
 */
function clear_login_attempts(string $ip): void {
    $db = getDB();
    $db->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
}
