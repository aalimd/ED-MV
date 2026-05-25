<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../includes/email_verification.php';
require_once __DIR__ . '/../includes/pwa.php';
init_session();
if (is_logged_in()) redirect(app_url('/'));

$email = trim($_GET['email'] ?? '');
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = client_ip();
    if (is_rate_limited($ip, 'email_verification')) {
        $mins = ceil(lockout_remaining($ip, 'email_verification') / 60);
        $error = "Too many verification requests. Please try again in {$mins} minute(s).";
    } elseif (!csrf_validate()) {
        $error = 'Invalid request.';
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!valid_email($email)) {
            $error = 'Enter a valid email.';
        } else {
            record_attempt($ip, $email, 'email_verification');
            try {
                resend_verification_email_for_address($email);
            } catch (Throwable $e) {
                error_log('Email verification resend failed: ' . $e->getMessage());
            }
            $message = 'If this email needs verification, a new link has been sent.';
        }
    }
}

$dark = isset($_COOKIE['ventguide_dark']) && $_COOKIE['ventguide_dark']==='1';
?>
<!DOCTYPE html>
<html lang="en" class="<?= $dark?'dark':'' ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
<title>Resend Verification — <?= e(APP_NAME) ?></title>
<?= pwa_zoom_lock_script() ?>
<?= pwa_head_tags('Request a new email verification link.') . "\n" ?>
<link rel="stylesheet" href="<?= asset_url('/assets/css/auth.css?v=6') ?>">
<?= toast_head_tag() ?>
</head>
<body>
<button class="dark-toggle" onclick="toggleDark()" title="Toggle dark mode"><span id="darkIcon"><?= $dark?'☀️':'🌙' ?></span></button>
<div class="auth-wrapper"><div class="auth-card">
<div class="auth-brand"><div class="auth-logo">📧</div><div class="auth-app-name"><?= e(APP_NAME) ?></div></div>
<h1 class="auth-title">Resend verification</h1>
<p class="auth-subtitle">Enter your email and we'll send a fresh verification link.</p>
<?php if($error): ?><div class="flash flash-danger">❌ <?= e($error) ?></div><?php endif; ?>
<?php if($message): ?><div class="flash flash-success">✅ <?= e($message) ?></div><?php endif; ?>
<form method="POST" autocomplete="on"><?= csrf_field() ?>
<div class="form-group"><label class="form-label" for="email">📧 Email</label>
<input type="email" id="email" name="email" class="form-input" placeholder="you@example.com" value="<?= e($email) ?>" required autofocus></div>
<button type="submit" class="btn btn-primary">📩 Send Verification Link</button>
</form>
<div class="auth-footer"><a href="<?= app_url('/auth/login') ?>">← Back to login</a></div>
</div></div>
<script>
function toggleDark(){document.documentElement.classList.toggle('dark');const d=document.documentElement.classList.contains('dark');document.getElementById('darkIcon').textContent=d?'☀️':'🌙';document.cookie='ventguide_dark='+(d?'1':'0')+';path=/;max-age=31536000';}
</script>
<?= pwa_script_tag() . "\n" ?>
<?= toast_script_tag() . "\n" ?>
</body></html>
