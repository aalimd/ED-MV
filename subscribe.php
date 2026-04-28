<?php
/**
 * Subscription Page — shown when user is logged in but not subscribed
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_login();
if (has_subscription()) redirect(APP_URL . '/app/ventguide.php');

$user = session_user();
$db = getDB();
$plans = $db->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order")->fetchAll();

$requested = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate()) {
    $planId = (int)($_POST['plan_id'] ?? 0);
    // Check if already has a pending subscription
    $stmt = $db->prepare("SELECT id FROM subscriptions WHERE user_id = ? AND status = 'pending' LIMIT 1");
    $stmt->execute([$user['id']]);
    if (!$stmt->fetch() && $planId > 0) {
        $db->prepare("INSERT INTO subscriptions (user_id, plan_id, status) VALUES (?, ?, 'pending')")
           ->execute([$user['id'], $planId]);
        log_activity('subscription_request', "Requested plan ID: {$planId}", $user['id']);
    }
    $requested = true;
}

// Check for existing pending request
$stmt = $db->prepare("SELECT s.*, p.name as plan_name FROM subscriptions s JOIN plans p ON s.plan_id=p.id WHERE s.user_id=? AND s.status='pending' LIMIT 1");
$stmt->execute([$user['id']]);
$pending = $stmt->fetch();

$dark = isset($_COOKIE['ventguide_dark']) && $_COOKIE['ventguide_dark']==='1';
?>
<!DOCTYPE html>
<html lang="en" class="<?= $dark?'dark':'' ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Subscribe — <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css">
</head>
<body>
<button class="dark-toggle" onclick="toggleDark()" title="Toggle dark mode"><span id="darkIcon"><?= $dark?'☀️':'🌙' ?></span></button>
<div class="auth-wrapper"><div class="auth-card">
<div class="auth-brand"><div class="auth-logo">🫁</div><div class="auth-app-name"><?= e(APP_NAME) ?></div></div>
<h1 class="auth-title">Subscription Required</h1>
<p class="auth-subtitle">Hello <strong><?= e($user['name']) ?></strong>! Select a plan to access VentGuide Pro.</p>

<?php if($pending || $requested): ?>
<div class="flash flash-warning">⏳ Your subscription request for <strong><?= e($pending['plan_name'] ?? 'a plan') ?></strong> is pending admin approval.</div>
<div class="sub-info"><h3>What happens next?</h3><p>The admin will review and activate your subscription. You'll get full access as soon as it's approved.</p></div>
<?php else: ?>
<form method="POST"><?= csrf_field() ?>
<div class="plan-cards">
<?php foreach($plans as $i => $plan): ?>
<label class="plan-card <?= $i===0?'selected':'' ?>" onclick="selectPlan(this,<?= $plan['id'] ?>)">
<input type="radio" name="plan_id" value="<?= $plan['id'] ?>" <?= $i===0?'checked':'' ?> style="display:none">
<div class="plan-name"><?= e($plan['name']) ?></div>
<div class="plan-price"><?= e($plan['currency']) ?> <?= number_format($plan['price'],2) ?></div>
<div class="plan-duration"><?= e($plan['description']) ?></div>
</label>
<?php endforeach; ?>
</div>
<button type="submit" class="btn btn-primary">📩 Request Subscription</button>
</form>
<?php endif; ?>

<div class="auth-divider">or</div>
<a href="<?= APP_URL ?>/auth/logout.php" class="btn btn-secondary">🚪 Logout</a>
</div></div>
<script>
function selectPlan(el,id){document.querySelectorAll('.plan-card').forEach(c=>c.classList.remove('selected'));el.classList.add('selected');el.querySelector('input').checked=true;}
function toggleDark(){document.documentElement.classList.toggle('dark');const d=document.documentElement.classList.contains('dark');document.getElementById('darkIcon').textContent=d?'☀️':'🌙';document.cookie='ventguide_dark='+(d?'1':'0')+';path=/;max-age=31536000';}
</script>
</body></html>
