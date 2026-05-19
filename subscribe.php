<?php
/**
 * Subscription Page — Premium pricing page
 * All text, plans, prices, and features are admin-controlled via the database.
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/features.php';
require_once __DIR__ . '/includes/pwa.php';
require_login();

$user = session_user();
$db = getDB();
$plans = $db->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll();

// Build plan-to-features lookup from the database
$planFeaturesMap = [];
$pfq = $db->query("SELECT pf.plan_id, f.icon, f.name FROM plan_features pf JOIN features f ON pf.feature_id = f.id WHERE f.is_active = 1 ORDER BY f.sort_order");
foreach ($pfq->fetchAll() as $pf) {
    $planFeaturesMap[$pf['plan_id']][] = $pf['icon'] . ' ' . $pf['name'];
}

// Page content from admin settings
$pageTitle = get_setting('sub_page_title', 'Choose Your Plan');
$pageSubtitle = get_setting('sub_page_subtitle', 'Get full access to evidence-based emergency ventilation tools.');
$pageFooter = get_setting('sub_page_footer', '');
$currencyOverride = get_setting('sub_currency_symbol', '');

$requested = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate()) {
    $planId = (int)($_POST['plan_id'] ?? 0);
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

// Check for current active plan to prevent re-requesting it
$stmt = $db->prepare("SELECT plan_id FROM subscriptions WHERE user_id=? AND status='active' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC LIMIT 1");
$stmt->execute([$user['id']]);
$activePlanId = $stmt->fetchColumn() ?: 0;

$hasSub = has_subscription();
$dark = isset($_COOKIE['ventguide_dark']) && $_COOKIE['ventguide_dark']==='1';
?>
<!DOCTYPE html>
<html lang="en" class="<?= $dark?'dark':'' ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
<title><?= e($pageTitle) ?> — <?= e(APP_NAME) ?></title>
<?= pwa_zoom_lock_script() ?>
<?= pwa_head_tags('Choose your ED VentGuide Pro access plan.') . "\n" ?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/auth.css?v=3">
<style>
/* ── Pricing Page Styles ──────────────────────── */
.pricing-wrapper {
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:40px 16px;position:absolute;inset:0;
  overflow-y:auto;overflow-x:hidden;overscroll-behavior-y:none;touch-action:pan-y;
}
.pricing-wrapper::before,.pricing-wrapper::after {
  content:'';position:absolute;border-radius:50%;filter:blur(100px);opacity:0.3;pointer-events:none;
  animation:blobFloat 10s ease-in-out infinite alternate;
}
.pricing-wrapper::before {width:500px;height:500px;background:rgba(var(--theme-rgb),0.25);top:-150px;right:-100px;}
.pricing-wrapper::after {width:350px;height:350px;background:rgba(var(--theme-rgb),0.15);bottom:-80px;left:-80px;animation-delay:-5s;}

.pricing-header {text-align:center;margin-bottom:40px;position:relative;z-index:1;animation:cardSlideUp 0.5s cubic-bezier(0.16,1,0.3,1);}
.pricing-header h1 {font-size:2rem;font-weight:900;color:var(--text);margin-bottom:10px;letter-spacing:-0.03em;}
.pricing-header p {font-size:1rem;color:var(--text-2);font-weight:500;max-width:520px;margin:0 auto;line-height:1.6;}

.pricing-grid {
  display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:20px;
  max-width:900px;width:100%;position:relative;z-index:1;
}

.price-card {
  background:var(--surface);border:2px solid var(--border);border-radius:20px;
  padding:28px 24px;position:relative;transition:all 0.3s ease;
  animation:cardSlideUp 0.5s cubic-bezier(0.16,1,0.3,1);
  display:flex;flex-direction:column;
}
.price-card:hover {transform:translateY(-4px);box-shadow:var(--shadow-lg);}
.price-card.featured {border-color:var(--card-accent, var(--theme));box-shadow:0 8px 30px rgba(var(--theme-rgb),0.2);}
.price-card.featured::before {
  content:'';position:absolute;top:0;left:0;right:0;height:4px;
  background:var(--card-accent, var(--theme));border-radius:20px 20px 0 0;
}

.card-badge {
  position:absolute;top:-12px;right:20px;
  padding:4px 14px;border-radius:20px;font-size:.72rem;font-weight:900;
  text-transform:uppercase;letter-spacing:.04em;color:#fff;
}

.card-name {font-size:1.1rem;font-weight:800;color:var(--text);margin-bottom:4px;}
.card-price {font-size:2.2rem;font-weight:900;font-family:'Space Mono',monospace;margin:12px 0 4px;letter-spacing:-0.02em;}
.card-currency {font-size:.9rem;font-weight:700;opacity:.7;}
.card-period {font-size:.82rem;color:var(--text-3);font-weight:600;margin-bottom:16px;}

.card-features {list-style:none;padding:0;margin:0 0 20px;flex:1;}
.card-features li {
  font-size:.84rem;color:var(--text-2);font-weight:600;padding:6px 0;
  display:flex;align-items:center;gap:8px;line-height:1.4;
}
.card-features li::before {content:'✓';font-size:.75rem;font-weight:900;color:var(--card-accent, var(--theme));
  width:20px;height:20px;border-radius:50%;display:flex;align-items:center;justify-content:center;
  background:rgba(var(--theme-rgb),0.08);flex-shrink:0;}

.card-btn {
  width:100%;padding:13px;border:2px solid var(--border);border-radius:12px;
  background:var(--surface-2);color:var(--text);font-family:inherit;
  font-size:.9rem;font-weight:800;cursor:pointer;transition:all 0.2s;
}
.card-btn:hover {border-color:var(--card-accent, var(--theme));color:var(--card-accent, var(--theme));}
.price-card.featured .card-btn {
  background:var(--card-accent, var(--theme));color:#fff;border-color:transparent;
  box-shadow:0 4px 14px rgba(var(--theme-rgb),0.3);
}
.price-card.featured .card-btn:hover {transform:translateY(-1px);box-shadow:0 6px 20px rgba(var(--theme-rgb),0.4);}

.pricing-footer {
  text-align:center;margin-top:32px;position:relative;z-index:1;
  max-width:500px;
}
.pricing-footer p {font-size:.84rem;color:var(--text-3);font-weight:500;line-height:1.6;}
.pricing-footer a:not(.btn) {color:var(--theme);font-weight:700;text-decoration:none;}
.pricing-footer a:not(.btn):hover {text-decoration:underline;}

.pending-card {
  background:var(--surface);border:2px solid var(--border);border-radius:20px;
  padding:40px 32px;text-align:center;max-width:480px;width:100%;
  position:relative;z-index:1;animation:cardSlideUp 0.5s cubic-bezier(0.16,1,0.3,1);
}
.pending-icon {font-size:3rem;margin-bottom:16px;}
.pending-title {font-size:1.2rem;font-weight:800;color:var(--text);margin-bottom:8px;}
.pending-sub {font-size:.88rem;color:var(--text-2);font-weight:500;line-height:1.6;margin-bottom:20px;}

@media(max-width:680px) {
  .pricing-grid {grid-template-columns:1fr;max-width:360px;}
  .pricing-header h1 {font-size:1.5rem;}
}
</style>
</head>
<body>
<button class="dark-toggle" onclick="toggleDark()" title="Toggle dark mode"><span id="darkIcon"><?= $dark?'☀️':'🌙' ?></span></button>

<?php if($pending || $requested): ?>
<!-- ── Pending State ──────────────────────── -->
<div class="pricing-wrapper">
<div class="pending-card">
  <div class="pending-icon">⏳</div>
  <div class="pending-title">Request Pending</div>
  <div class="pending-sub">Your subscription request for <strong><?= e($pending['plan_name'] ?? 'a plan') ?></strong> is awaiting admin approval. You'll get full access as soon as it's approved.</div>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:10px;">
    <?php if($hasSub): ?>
      <a href="<?= APP_URL ?>/app/ventguide" class="btn btn-primary" style="flex:1;min-width:140px;">📱 Open App</a>
    <?php endif; ?>
    <form method="POST" action="<?= APP_URL ?>/auth/logout" style="flex:1;min-width:140px;">
      <?= csrf_field() ?>
      <button type="submit" class="btn btn-secondary" style="width:100%;">🚪 Logout</button>
    </form>
  </div>
</div>
</div>

<?php else: ?>
<!-- ── Pricing Page ───────────────────────── -->
<div class="pricing-wrapper">
<div class="pricing-header">
  <div style="font-size:2.5rem;margin-bottom:12px">🫁</div>
  <h1><?= e($pageTitle) ?></h1>
  <p><?= e($pageSubtitle) ?></p>
  <?php if($hasSub): ?>
  <div style="background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.2);color:#059669;padding:12px;border-radius:12px;margin:20px auto 0;max-width:520px;font-size:0.85rem;font-weight:600;text-align:left;">
    💡 <strong>Smart Upgrade:</strong> If you upgrade today, the remaining days on your current plan will be automatically added to your new plan! You will not lose any paid time.
  </div>
  <?php endif; ?>
</div>

<div class="pricing-grid">
<?php foreach($plans as $i => $plan):
  $features = $planFeaturesMap[$plan['id']] ?? [];
  $isFeatured = $plan['is_featured'];
  $cardColor = $plan['color'] ?? '#2563eb';
  $currency = $currencyOverride ?: $plan['currency'];
  $priceInt = floor($plan['price']);
  $priceDec = round(($plan['price'] - $priceInt) * 100);
?>
<form method="POST" class="price-card <?= $isFeatured ? 'featured' : '' ?>" style="--card-accent:<?= e($cardColor) ?>;animation-delay:<?= $i * 0.1 ?>s" onsubmit="return confirm('Just a gentle reminder:\n\nTo help us maintain a high-quality service, please note that all subscription purchases and upgrades are final and non-refundable once approved.\n\nWould you like to proceed with requesting this plan?');">
  <?= csrf_field() ?>
  <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
  <?php if($plan['badge']): ?>
  <div class="card-badge" style="background:<?= e($cardColor) ?>"><?= e($plan['badge']) ?></div>
  <?php endif; ?>
  <div class="card-name"><?= e($plan['name']) ?></div>
  <div class="card-price" style="color:<?= e($cardColor) ?>">
    <span class="card-currency"><?= e($currency) ?></span> <?= number_format($priceInt) ?><?php if($priceDec): ?><span style="font-size:1rem">.<?= str_pad($priceDec, 2, '0') ?></span><?php endif; ?>
  </div>
  <div class="card-period"><?= e($plan['description'] ?? '') ?></div>
  <?php if(!empty($features)): ?>
  <ul class="card-features">
    <?php foreach($features as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
  </ul>
  <?php endif; ?>
  <?php if ($plan['id'] == $activePlanId): ?>
    <button type="button" class="card-btn" style="background:var(--border);color:var(--text-3);cursor:not-allowed;box-shadow:none;" disabled>✅ Current Plan</button>
  <?php else: ?>
    <button type="submit" class="card-btn">📩 Request This Plan</button>
  <?php endif; ?>
</form>
<?php endforeach; ?>
</div>

<?php if($pageFooter): ?>
<div class="pricing-footer"><p><?= e($pageFooter) ?></p></div>
<?php endif; ?>

<div class="pricing-footer" style="margin-top:16px; margin-bottom:16px; opacity: 0.8;">
  <p style="font-size: 0.8rem; color: var(--text-2);">⚠️ <strong>No Refunds:</strong> Please note that all subscription purchases and upgrades are final and non-refundable.</p>
</div>

<div class="pricing-footer" style="margin-top:0;display:flex;flex-direction:column;gap:12px;align-items:center;">
  <?php if($hasSub): ?>
    <a href="<?= APP_URL ?>/app/ventguide" class="btn btn-primary" style="max-width:260px;width:100%;font-size:1rem;padding:12px;border-radius:12px;">📱 Return to App</a>
  <?php endif; ?>
  <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;justify-content:center;">
    <span>Logged in as <strong><?= e($user['name']) ?></strong></span>
    <form method="POST" action="<?= APP_URL ?>/auth/logout" style="display:inline">
      <?= csrf_field() ?>
      <button type="submit" style="background:none;border:none;color:var(--theme);padding:0;cursor:pointer;text-decoration:underline;font:inherit;">Logout</button>
    </form>
  </div>
</div>
</div>
<?php endif; ?>

<script>
function toggleDark(){document.documentElement.classList.toggle('dark');const d=document.documentElement.classList.contains('dark');document.getElementById('darkIcon').textContent=d?'☀️':'🌙';document.cookie='ventguide_dark='+(d?'1':'0')+';path=/;max-age=31536000';}
</script>
<?= pwa_script_tag() . "\n" ?>
</body></html>
