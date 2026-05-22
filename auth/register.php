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

$errors = []; $name = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = client_ip();
    if (is_rate_limited($ip, 'register')) {
        $mins = ceil(lockout_remaining($ip, 'register') / 60);
        $errors[] = "Too many registration attempts. Please try again in {$mins} minute(s).";
    } elseif (!csrf_validate()) { $errors[] = 'Invalid request.'; }
    else {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        if (!$name) $errors[] = 'Name is required.';
        if (!valid_email($email)) $errors[] = 'Valid email is required.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        $pwdErrors = validate_password($password);
        if ($pwdErrors) $errors = array_merge($errors, $pwdErrors);
        // Check registration open
        if (!$errors) {
            $regOpen = get_setting('registration_open', '1');
            if ($regOpen !== '1') $errors[] = 'Registration is currently closed.';
        }
        // Check duplicate email
        if (!$errors) {
            $db = getDB();
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) $errors[] = 'Unable to create an account with this email address.';
        }
        if (!$errors) {
            record_attempt($ip, valid_email($email) ? $email : null, 'register');
            $db = getDB();
            $hash = password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT, defined('PASSWORD_ARGON2ID') ? [] : ['cost' => BCRYPT_COST]);
            $status = REQUIRE_ADMIN_APPROVAL ? 'pending' : 'active';
            $requiresVerification = email_verification_required();
            $emailVerified = $requiresVerification ? 0 : 1;
            $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role, status, email_verified) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$name, $email, $hash, 'user', $status, $emailVerified]);
            $userId = (int)$db->lastInsertId();

            $mailSent = true;
            if ($requiresVerification) {
                $mailSent = send_verification_email($userId, $email, $name);
            }

            log_activity('register', "New registration: {$email} (status: {$status}, email verification: " . ($requiresVerification ? 'required' : 'off') . ")", $userId);
            $registeredState = $requiresVerification ? ($mailSent ? 'verify' : 'verify_failed') : '1';
            redirect(app_url('/auth/login?registered=' . $registeredState));
        }
    }
}
$dark = isset($_COOKIE['ventguide_dark']) && $_COOKIE['ventguide_dark']==='1';
?>
<!DOCTYPE html>
<html lang="en" class="<?= $dark?'dark':'' ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
<title>Register — <?= e(APP_NAME) ?></title>
<?= pwa_zoom_lock_script() ?>
<?= pwa_head_tags('Request access to ED VentGuide Pro.') . "\n" ?>
<link rel="stylesheet" href="<?= asset_url('/assets/css/auth.css?v=6') ?>">
</head>
<body>
<button class="dark-toggle" onclick="toggleDark()" title="Toggle dark mode"><span id="darkIcon"><?= $dark?'☀️':'🌙' ?></span></button>
<div class="auth-wrapper"><div class="auth-card">
<div class="auth-brand"><div class="auth-logo">🫁</div><div class="auth-app-name"><?= e(APP_NAME) ?></div><div class="auth-app-sub">Evidence-Based Emergency Ventilation</div></div>
<h1 class="auth-title">Create your account</h1>
<p class="auth-subtitle">Register to request access to VentGuide Pro.</p>
<?php foreach($errors as $err): ?><div class="flash flash-danger">❌ <?= e($err) ?></div><?php endforeach; ?>
<form method="POST" autocomplete="on"><?= csrf_field() ?>
<div class="form-group"><label class="form-label" for="name">👤 Full Name</label>
<input type="text" id="name" name="name" class="form-input" placeholder="Dr. EM Physician" value="<?= e($name) ?>" required autofocus></div>
<div class="form-group"><label class="form-label" for="email">📧 Email</label>
<input type="email" id="email" name="email" class="form-input" placeholder="you@hospital.com" value="<?= e($email) ?>" required></div>
<div class="form-group"><label class="form-label" for="password">🔒 Password</label>
<div class="input-password-wrap"><input type="password" id="password" name="password" class="form-input" placeholder="Min 8 chars, 1 upper, 1 number, 1 special" required minlength="8" oninput="checkStrength(this.value)">
<button type="button" class="password-toggle" onclick="togglePwd('password')">👁️</button></div>
<div class="pwd-strength"><div class="pwd-bar" id="b1"></div><div class="pwd-bar" id="b2"></div><div class="pwd-bar" id="b3"></div></div>
<div class="pwd-label" id="pwdLabel" style="color:var(--text-3)"></div></div>
<div class="form-group"><label class="form-label" for="confirm">🔒 Confirm Password</label>
<div class="input-password-wrap"><input type="password" id="confirm" name="confirm" class="form-input" placeholder="Repeat your password" required>
<button type="button" class="password-toggle" onclick="togglePwd('confirm')">👁️</button></div></div>
<button type="submit" class="btn btn-primary">📝 Create Account</button>
</form>
<div class="auth-footer">Already have an account? <a href="<?= app_url('/auth/login') ?>">Sign in</a></div>
</div></div>
<script>
function togglePwd(id){const i=document.getElementById(id);i.type=i.type==='password'?'text':'password';}
function toggleDark(){document.documentElement.classList.toggle('dark');const d=document.documentElement.classList.contains('dark');document.getElementById('darkIcon').textContent=d?'☀️':'🌙';document.cookie='ventguide_dark='+(d?'1':'0')+';path=/;max-age=31536000';}
function checkStrength(p){const b=[document.getElementById('b1'),document.getElementById('b2'),document.getElementById('b3')];const l=document.getElementById('pwdLabel');let s=0;if(p.length>=8)s++;if(/[A-Z]/.test(p)&&/[0-9]/.test(p)&&/[^A-Za-z0-9]/.test(p))s++;if(p.length>=12)s++;b.forEach((x,i)=>{x.className='pwd-bar';if(i<s)x.classList.add(s===1?'active-weak':s===2?'active-medium':'active-strong');});l.textContent=s===0?'':s===1?'Weak':s===2?'Medium':'Strong';l.style.color=s===1?'var(--danger)':s===2?'var(--warning)':'var(--success)';}
</script>
<?= pwa_script_tag() . "\n" ?>
</body></html>
