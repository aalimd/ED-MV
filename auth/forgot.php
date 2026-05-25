<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../includes/pwa.php';
init_session();

$msg = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = client_ip();
    if (is_rate_limited($ip, 'password_reset')) {
        $mins = ceil(lockout_remaining($ip, 'password_reset') / 60);
        $error = "Too many reset attempts. Please try again in {$mins} minute(s).";
    } elseif (!csrf_validate()) { $error = 'Invalid request.'; }
    else {
        $email = strtolower(trim($_POST['email'] ?? ''));
        if (!valid_email($email)) { $error = 'Enter a valid email.'; }
        else {
            try {
                record_attempt($ip, $email, 'password_reset');
                $db = getDB();
                $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND status = "active"');
                $stmt->execute([$email]); $user = $stmt->fetch();
                if ($user) {
                    // Delete old tokens for this email
                    $db->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);
                    $token = bin2hex(random_bytes(32));
                    $hash = hash('sha256', $token);
                    $expires = date('Y-m-d H:i:s', time() + 3600);
                    $db->prepare('INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?,?,?)')->execute([$email, $hash, $expires]);
                    $resetUrl = app_url("/auth/reset?token={$token}&email=" . urlencode($email));
                    $sent = send_password_reset_email($email, $resetUrl);
                    log_activity($sent ? 'password_reset_email_sent' : 'password_reset_email_failed', "Reset requested for: {$email}");
                }
                $msg = 'If an account exists with that email, a reset link has been sent.';
            } catch (Throwable $e) {
                error_log('Password reset request failed: ' . $e->getMessage());
                $msg = 'If an account exists with that email, a reset link has been sent.';
            }
        }
    }
}
$dark = isset($_COOKIE['ventguide_dark']) && $_COOKIE['ventguide_dark']==='1';
?>
<!DOCTYPE html>
<html lang="en" class="<?= $dark?'dark':'' ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
<title>Forgot Password — <?= e(APP_NAME) ?></title>
<?= pwa_zoom_lock_script() ?>
<?= pwa_head_tags('Reset your ED VentGuide Pro password.') . "\n" ?>
<link rel="stylesheet" href="<?= asset_url('/assets/css/auth.css?v=6') ?>">
<?= toast_head_tag() ?>
</head>
<body>
<button class="dark-toggle" onclick="toggleDark()" title="Toggle dark mode"><span id="darkIcon"><?= $dark?'☀️':'🌙' ?></span></button>
<div class="auth-wrapper"><div class="auth-card">
<div class="auth-brand"><div class="auth-logo">🔐</div><div class="auth-app-name"><?= e(APP_NAME) ?></div></div>
<h1 class="auth-title">Reset your password</h1>
<p class="auth-subtitle">Enter your email and we'll send you a reset link.</p>
<?php if($error): ?><div class="flash flash-danger">❌ <?= e($error) ?></div><?php endif; ?>
<?php if($msg): ?><div class="flash flash-success">✅ <?= e($msg) ?></div><?php endif; ?>
<form method="POST"><?= csrf_field() ?>
<div class="form-group"><label class="form-label" for="email">📧 Email</label>
<input type="email" id="email" name="email" class="form-input" placeholder="you@example.com" required autofocus></div>
<button type="submit" class="btn btn-primary">📩 Send Reset Link</button>
</form>
<div class="auth-footer"><a href="<?= app_url('/auth/login') ?>">← Back to login</a></div>
</div></div>
<script>
function toggleDark(){document.documentElement.classList.toggle('dark');const d=document.documentElement.classList.contains('dark');document.getElementById('darkIcon').textContent=d?'☀️':'🌙';document.cookie='ventguide_dark='+(d?'1':'0')+';path=/;max-age=31536000';}
</script>
<?= pwa_script_tag() . "\n" ?>
<?= toast_script_tag() . "\n" ?>
</body></html>
