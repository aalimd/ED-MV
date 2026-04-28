<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/rate_limit.php';
require_once __DIR__ . '/../includes/auth.php';
init_session();
if (is_logged_in()) redirect(APP_URL . '/index.php');

$error = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) { $error = 'Invalid request. Please try again.'; }
    else {
        $ip = client_ip();
        if (is_rate_limited($ip)) {
            $mins = ceil(lockout_remaining($ip) / 60);
            $error = "Too many attempts. Try again in {$mins} minute(s).";
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $remember = isset($_POST['remember']);
            if (!$email || !$password) { $error = 'Please fill in all fields.'; }
            else {
                $db = getDB();
                $stmt = $db->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
                $stmt->execute([$email]); $user = $stmt->fetch();
                if ($user && password_verify($password, $user['password_hash'])) {
                    if ($user['status'] === 'pending') $error = 'Your account is pending admin approval.';
                    elseif ($user['status'] === 'suspended') $error = 'Your account has been suspended.';
                    elseif ($user['status'] === 'deleted') $error = 'Invalid credentials.';
                    else {
                        clear_login_attempts($ip);
                        session_set_user($user, $remember);
                        $db->prepare('UPDATE users SET last_login=NOW(),last_ip=? WHERE id=?')->execute([$ip,$user['id']]);
                        log_activity('login','Successful login',$user['id']);
                        $redir = $_SESSION['redirect_after_login'] ?? '/index.php';
                        unset($_SESSION['redirect_after_login']);
                        redirect_local($redir);
                    }
                } else {
                    record_failed_attempt($ip, $email);
                    log_activity('login_failed',"Failed login for: {$email}");
                    $error = 'Invalid email or password.';
                }
            }
        }
    }
}
if (isset($_GET['error']) && $_GET['error']==='suspended') $error='Your account has been suspended.';
if (isset($_GET['registered'])) flash('success','Account created! Please wait for admin approval.');
$dark = isset($_COOKIE['ventguide_dark']) && $_COOKIE['ventguide_dark']==='1';
?>
<!DOCTYPE html>
<html lang="en" class="<?= $dark?'dark':'' ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Login — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css">
</head>
<body>
<button class="dark-toggle" onclick="toggleDark()" title="Toggle dark mode"><span id="darkIcon"><?= $dark?'☀️':'🌙' ?></span></button>
<div class="auth-wrapper"><div class="auth-card">
<div class="auth-brand"><div class="auth-logo">🫁</div><div class="auth-app-name"><?= e(APP_NAME) ?></div><div class="auth-app-sub">Evidence-Based Emergency Ventilation</div></div>
<h1 class="auth-title">Welcome back</h1>
<p class="auth-subtitle">Sign in to access your ventilation tools.</p>
<?= render_flashes() ?>
<?php if($error): ?><div class="flash flash-danger">❌ <?= e($error) ?></div><?php endif; ?>
<form method="POST" autocomplete="on"><?= csrf_field() ?>
<div class="form-group"><label class="form-label" for="email">📧 Email</label>
<input type="email" id="email" name="email" class="form-input" placeholder="you@example.com" value="<?= e($email) ?>" required autofocus></div>
<div class="form-group"><label class="form-label" for="password">🔒 Password</label>
<div class="input-password-wrap"><input type="password" id="password" name="password" class="form-input" placeholder="••••••••" required>
<button type="button" class="password-toggle" onclick="togglePwd('password')">👁️</button></div></div>
<div class="auth-links"><label class="form-check" style="margin-bottom:0"><input type="checkbox" name="remember"><span style="font-size:.82rem;font-weight:600;color:var(--text-2)">Remember me</span></label>
<a href="<?= APP_URL ?>/auth/forgot.php" class="auth-link">Forgot password?</a></div>
<button type="submit" class="btn btn-primary">🔑 Sign In</button>
</form>
<div class="auth-footer">Don't have an account? <a href="<?= APP_URL ?>/auth/register.php">Create one</a></div>
</div></div>
<script>
function togglePwd(id){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';}
function toggleDark(){document.documentElement.classList.toggle('dark');const d=document.documentElement.classList.contains('dark');document.getElementById('darkIcon').textContent=d?'☀️':'🌙';document.cookie='ventguide_dark='+(d?'1':'0')+';path=/;max-age=31536000';}
</script>
</body></html>
