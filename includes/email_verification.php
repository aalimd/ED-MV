<?php
/**
 * Email verification helpers.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mailer.php';

function email_verification_required(): bool
{
    return get_setting('require_email_verification', '1') === '1';
}

function create_email_verification_token(int $userId, string $email): string
{
    $db = getDB();
    $db->prepare('DELETE FROM email_verifications WHERE user_id = ? OR email = ?')->execute([$userId, $email]);

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 86400);

    $stmt = $db->prepare('INSERT INTO email_verifications (user_id, email, token_hash, expires_at) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $email, $hash, $expires]);

    return $token;
}

function send_verification_email(int $userId, string $email, string $name): bool
{
    $token = create_email_verification_token($userId, $email);
    $verifyUrl = app_url('/auth/verify-email?token=' . $token . '&email=' . urlencode($email));

    return send_email_verification_email($email, $name, $verifyUrl);
}

function resend_verification_email_for_address(string $email): bool
{
    if (!valid_email($email) || !email_verification_required()) {
        return false;
    }

    $db = getDB();
    $stmt = $db->prepare(
        'SELECT id, name, email, email_verified, status
         FROM users
         WHERE email = ? AND status != "deleted"
         LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || (int)$user['email_verified'] === 1) {
        return false;
    }

    $sent = send_verification_email((int)$user['id'], $user['email'], $user['name']);
    if (function_exists('log_activity')) {
        log_activity(
            $sent ? 'email_verification_resent' : 'email_verification_resend_failed',
            "Verification resend requested for: {$email}",
            (int)$user['id']
        );
    }
    return $sent;
}

function verify_email_token(string $token, string $email): bool
{
    if ($token === '' || !valid_email($email)) {
        return false;
    }

    $db = getDB();
    $hash = hash('sha256', $token);
    $stmt = $db->prepare(
        'SELECT id, user_id FROM email_verifications
         WHERE email = ? AND token_hash = ? AND used = 0 AND expires_at > NOW()
         LIMIT 1'
    );
    $stmt->execute([$email, $hash]);
    $row = $stmt->fetch();
    if (!$row) {
        return false;
    }

    try {
        $db->beginTransaction();
        $db->prepare('UPDATE users SET email_verified = 1 WHERE id = ? AND email = ? AND status != "deleted"')
            ->execute([(int)$row['user_id'], $email]);
        $db->prepare('UPDATE email_verifications SET used = 1 WHERE id = ?')->execute([(int)$row['id']]);
        $db->commit();
        if (function_exists('log_activity')) {
            try {
                log_activity('email_verified', "Verified email: {$email}", (int)$row['user_id']);
            } catch (Throwable $e) {
                error_log('Email verification logging failed: ' . $e->getMessage());
            }
        }
        return true;
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Email verification failed: ' . $e->getMessage());
        return false;
    }
}
