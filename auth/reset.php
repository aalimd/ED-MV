<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/pwa.php';
init_session();

$error = ''; $success = false;
$token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
$email = strtolower(trim((string)($_GET['email'] ?? $_POST['email'] ?? '')));

// Validate token
function validateToken(string $token, string $email): array|false {
    if ($token === '' || !valid_email($email)) return false;
    $db = getDB();
    $hash = hash('sha256', $token);
    $stmt = $db->prepare('SELECT * FROM password_resets WHERE email=? AND token_hash=? AND used=0 AND expires_at > NOW() LIMIT 1');
    $stmt->execute([$email, $hash]);
    return $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) { $error = 'Invalid request.'; }
    else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if ($password !== $confirm) { $error = 'Passwords do not match.'; }
        else {
            $pwdErrors = validate_password($password);
            if ($pwdErrors) { $error = implode(', ', $pwdErrors); }
            else {
                $resetRow = validateToken($token, $email);
                if (!$resetRow) { $error = 'Invalid or expired reset link.'; }
                else {
                    $db = getDB();
                    try {
                        $db->beginTransaction();
                        $hash = password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT, defined('PASSWORD_ARGON2ID') ? [] : ['cost' => BCRYPT_COST]);
                        $stmt = $db->prepare('UPDATE users SET password_hash=?, auth_version = auth_version + 1 WHERE email=? AND status = "active"');
                        $stmt->execute([$hash, $email]);
                        $db->prepare('UPDATE password_resets SET used=1 WHERE email=? AND token_hash=?')->execute([$email, hash('sha256', $token)]);
                        if ($stmt->rowCount() !== 1) {
                            throw new RuntimeException('Password update affected an unexpected number of users.');
                        }
                        $db->commit();
                        try {
                            log_activity('password_reset', "Password reset for: {$email}");
                        } catch (Throwable $e) {
                            error_log('Password reset logging failed: ' . $e->getMessage());
                        }
                        flash('success', 'Password reset successfully. Please login.');
                        redirect(APP_URL . '/auth/login');
                    } catch (Throwable $e) {
                        if ($db->inTransaction()) $db->rollBack();
                        error_log('Password reset failed: ' . $e->getMessage());
                        $error = 'Unable to reset password right now. Please request a new reset link and try again.';
                    }
                }
            }
        }
    }
} else {
    if (!validateToken($token, $email)) { $error = 'Invalid or expired reset link. Please request a new one.'; }
}
$dark = isset($_COOKIE['ventguide_dark']) && $_COOKIE['ventguide_dark']==='1';
?>
<!DOCTYPE html>
<html lang="en" class="<?= $dark?'dark':'' ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
<title>Reset Password — <?= e(APP_NAME) ?></title>
<?= pwa_zoom_lock_script() ?>
<?= pwa_head_tags('Set a new ED VentGuide Pro password.') . "\n" ?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css?v=2">
</head>
<body>
<button class="dark-toggle" onclick="toggleDark()" title="Toggle dark mode"><span id="darkIcon"><?= $dark?'☀️':'🌙' ?></span></button>
<div class="auth-wrapper"><div class="auth-card">
<div class="auth-brand"><div class="auth-logo">🔐</div><div class="auth-app-name"><?= e(APP_NAME) ?></div></div>
<h1 class="auth-title">Set new password</h1>
<?php if($error): ?><div class="flash flash-danger">❌ <?= e($error) ?></div>
<div class="auth-footer"><a href="<?= APP_URL ?>/auth/forgot">Request a new reset link</a></div>
<?php else: ?>
<form method="POST"><?= csrf_field() ?>
<input type="hidden" name="token" value="<?= e($token) ?>">
<input type="hidden" name="email" value="<?= e($email) ?>">
<div class="form-group"><label class="form-label" for="password">🔒 New Password</label>
<div class="input-password-wrap"><input type="password" id="password" name="password" class="form-input" placeholder="Min 8 chars" required minlength="8">
<button type="button" class="password-toggle" onclick="togglePwd('password')">👁️</button></div></div>
<div class="form-group"><label class="form-label" for="confirm">🔒 Confirm</label>
<div class="input-password-wrap"><input type="password" id="confirm" name="confirm" class="form-input" placeholder="Repeat password" required>
<button type="button" class="password-toggle" onclick="togglePwd('confirm')">👁️</button></div></div>
<button type="submit" class="btn btn-primary">✅ Reset Password</button>
</form>
<?php endif; ?>
</div></div>
<script>
function togglePwd(id){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';}
function toggleDark(){document.documentElement.classList.toggle('dark');const d=document.documentElement.classList.contains('dark');document.getElementById('darkIcon').textContent=d?'☀️':'🌙';document.cookie='ventguide_dark='+(d?'1':'0')+';path=/;max-age=31536000';}
</script>
<?= pwa_script_tag() . "\n" ?>
</body></html>
