<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email_verification.php';
require_once __DIR__ . '/../includes/pwa.php';
init_session();

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (valid_email((string)$email)) {
    $db = getDB();
    $stmt = $db->prepare('SELECT email_verified FROM users WHERE email = ? AND status != "deleted" LIMIT 1');
    $stmt->execute([(string)$email]);
    $user = $stmt->fetch();
} else {
    $user = null;
}

if ($user && (int)$user['email_verified'] === 1) {
    flash('success', 'Email is already verified. You can sign in after admin approval is complete.');
} elseif (verify_email_token((string)$token, (string)$email)) {
    flash('success', 'Email verified successfully. Please sign in after admin approval is complete.');
} else {
    flash('danger', 'Invalid or expired verification link. Please request a new verification email.');
}

redirect(APP_URL . '/auth/login');
