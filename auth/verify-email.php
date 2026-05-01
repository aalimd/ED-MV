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
$verified = verify_email_token((string)$token, (string)$email);

if ($verified) {
    flash('success', 'Email verified successfully. Please sign in after admin approval is complete.');
} else {
    flash('danger', 'Invalid or expired verification link. Please contact an admin for help.');
}

redirect(APP_URL . '/auth/login.php');
