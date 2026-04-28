<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
init_session();

$msg = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) { $error = 'Invalid request.'; }
    else {
        $email = trim($_POST['email'] ?? '');
        if (!valid_email($email)) { $error = 'Enter a valid email.'; }
        else {
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
                log_activity('password_reset_request', "Reset requested for: {$email}");
                // In production, send email. For now, show token for testing.
                $resetUrl = APP_URL . "/auth/reset.php?token={$token}&email=" . urlencode($email);
                $msg = "Reset link generated. In production this would be emailed. For testing, use this link:";
                $_SESSION['reset_link'] = $resetUrl;
            }
            // Always show success (prevents email enumeration)
            if (!$msg) $msg = 'If an account exists with that email, a reset link has been sent.';
        }
    }
}
$dark = isset($_COOKIE['ventguide_dark']) && $_COOKIE['ventguide_dark']==='1';
?>
<!DOCTYPE html>
<html lang="en" class="<?= $dark?'dark':'' ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css">
</head>
<body>
<button class="dark-toggle" onclick="toggleDark()" title="Toggle dark mode"><span id="darkIcon"><?= $dark?'☀️':'🌙' ?></span></button>
<div class="auth-wrapper"><div class="auth-card">
<div class="auth-brand"><div class="auth-logo">🔐</div><div class="auth-app-name"><?= e(APP_NAME) ?></div></div>
<h1 class="auth-title">Reset your password</h1>
<p class="auth-subtitle">Enter your email and we'll send you a reset link.</p>
<?php if($error): ?><div class="flash flash-danger">❌ <?= e($error) ?></div><?php endif; ?>
<?php if($msg): ?><div class="flash flash-success">✅ <?= e($msg) ?></div>
<?php if(isset($_SESSION['reset_link'])): ?>
<div style="background:var(--surface-2);border:1px solid var(--border);border-radius:var(--r-sm);padding:12px;margin-bottom:16px;word-break:break-all;">
<div style="font-size:.72rem;font-weight:800;text-transform:uppercase;color:var(--text-3);margin-bottom:6px;">🔗 Test Reset Link</div>
<a href="<?= e($_SESSION['reset_link']) ?>" style="font-size:.82rem;color:var(--theme);font-weight:700;"><?= e($_SESSION['reset_link']) ?></a>
</div>
<?php unset($_SESSION['reset_link']); endif; ?>
<?php endif; ?>
<form method="POST"><?= csrf_field() ?>
<div class="form-group"><label class="form-label" for="email">📧 Email</label>
<input type="email" id="email" name="email" class="form-input" placeholder="you@example.com" required autofocus></div>
<button type="submit" class="btn btn-primary">📩 Send Reset Link</button>
</form>
<div class="auth-footer"><a href="<?= APP_URL ?>/auth/login.php">← Back to login</a></div>
</div></div>
<script>
function toggleDark(){document.documentElement.classList.toggle('dark');const d=document.documentElement.classList.contains('dark');document.getElementById('darkIcon').textContent=d?'☀️':'🌙';document.cookie='ventguide_dark='+(d?'1':'0')+';path=/;max-age=31536000';}
</script>
</body></html>
