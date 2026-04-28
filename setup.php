<?php
/**
 * First-Run Setup — Creates the admin account
 * DELETE THIS FILE after setup is complete!
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/helpers.php';
init_session();

// Check if admin already exists
$db = getDB();
$stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
$stmt->execute();
$adminExists = $stmt->fetch();
if ($adminExists && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(404);
    exit('Not found');
}

$done = false; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate()) {
    if ($adminExists) { $error = 'Admin already exists. Delete this file.'; }
    else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$name || !valid_email($email) || strlen($password) < 8) {
            $error = 'All fields required. Password min 8 chars.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            $db->prepare('INSERT INTO users (name,email,password_hash,role,status,email_verified) VALUES (?,?,?,?,?,?)')
               ->execute([$name, $email, $hash, 'admin', 'active', 1]);
            $done = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Setup — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css">
</head>
<body>
<div class="auth-wrapper"><div class="auth-card">
<div class="auth-brand"><div class="auth-logo">⚙️</div><div class="auth-app-name">First-Time Setup</div><div class="auth-app-sub">Create your admin account</div></div>

<?php if($adminExists && !$done): ?>
<div class="flash flash-warning">⚠️ An admin account already exists. <strong>Delete this setup.php file</strong> for security.</div>
<div class="auth-footer"><a href="<?= APP_URL ?>/auth/login.php">Go to Login</a></div>

<?php elseif($done): ?>
<div class="flash flash-success">✅ Admin account created successfully!</div>
<div class="sub-info"><h3>⚠️ Important</h3><p>Delete <code>setup.php</code> from your server immediately for security.</p></div>
<a href="<?= APP_URL ?>/auth/login.php" class="btn btn-primary">🔑 Go to Login</a>

<?php else: ?>
<?php if($error): ?><div class="flash flash-danger">❌ <?= e($error) ?></div><?php endif; ?>
<form method="POST"><?= csrf_field() ?>
<div class="form-group"><label class="form-label" for="name">👤 Admin Name</label>
<input type="text" id="name" name="name" class="form-input" placeholder="Dr. Admin" required></div>
<div class="form-group"><label class="form-label" for="email">📧 Admin Email</label>
<input type="email" id="email" name="email" class="form-input" placeholder="admin@hospital.com" required></div>
<div class="form-group"><label class="form-label" for="password">🔒 Password</label>
<input type="password" id="password" name="password" class="form-input" placeholder="Min 8 characters" required minlength="8"></div>
<button type="submit" class="btn btn-primary">🚀 Create Admin Account</button>
</form>
<?php endif; ?>
</div></div>
</body></html>
