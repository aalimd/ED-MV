<?php
/**
 * Admin Layout Helper
 * Usage: include this, call admin_header('Page Title','icon'), then content, then admin_footer()
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/pwa.php';
require_admin();

function admin_header(string $title, string $icon = '📊', string $activePage = ''): void {
    $user = session_user();
    $dark = isset($_COOKIE['ventguide_dark']) && $_COOKIE['ventguide_dark']==='1';
    $pages = [
        ['url'=>'index.php','icon'=>'📊','label'=>'Dashboard','key'=>'dashboard'],
        ['url'=>'users.php','icon'=>'👥','label'=>'Users','key'=>'users'],
        ['url'=>'subscriptions.php','icon'=>'💳','label'=>'Subscriptions','key'=>'subscriptions'],
        ['url'=>'plans.php','icon'=>'🏷️','label'=>'Plans & Pricing','key'=>'plans'],
        ['url'=>'settings.php','icon'=>'⚙️','label'=>'Settings','key'=>'settings'],
        ['url'=>'logs.php','icon'=>'📋','label'=>'Activity Logs','key'=>'logs'],
    ];
?>
<!DOCTYPE html>
<html lang="en" class="<?= $dark?'dark':'' ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> — Admin</title>
<?= pwa_head_tags('ED VentGuide Pro admin control panel.') . "\n" ?>
<link rel="stylesheet" href="<?= APP_URL ?>/assets/css/admin.css">
</head>
<body>
<button class="sb-toggle" onclick="document.querySelector('.admin-sidebar').classList.toggle('open')">☰</button>
<div class="admin-layout">
<aside class="admin-sidebar">
  <div class="sb-brand"><span class="sb-logo">🫁</span><div><div class="sb-name">VentGuide Admin</div><div class="sb-sub">Control Panel</div></div></div>
  <nav class="sb-nav">
    <?php foreach($pages as $p): ?>
    <a href="<?= APP_URL ?>/admin/<?= $p['url'] ?>" class="sb-link <?= $activePage===$p['key']?'active':'' ?>">
      <span class="sb-emoji"><?= $p['icon'] ?></span><?= $p['label'] ?>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="sb-footer">
    <a href="<?= APP_URL ?>/app/ventguide.php">← Back to App</a><br>
    Logged in as <strong><?= e($user['name']) ?></strong>
  </div>
</aside>
<main class="admin-main">
<div class="admin-topbar">
  <h1 class="admin-page-title"><?= $icon ?> <?= e($title) ?></h1>
  <div class="topbar-actions">
    <button class="topbar-btn" onclick="toggleDark()"><span id="darkIcon"><?= $dark?'☀️':'🌙' ?></span></button>
    <a href="<?= APP_URL ?>/auth/logout.php" class="topbar-btn" style="color:var(--danger)">🚪 Logout</a>
  </div>
</div>
<?= render_flashes() ?>
<?php } // end admin_header

function admin_footer(): void { ?>
</main></div>
<script>
function toggleDark(){document.documentElement.classList.toggle('dark');const d=document.documentElement.classList.contains('dark');document.getElementById('darkIcon').textContent=d?'☀️':'🌙';document.cookie='ventguide_dark='+(d?'1':'0')+';path=/;max-age=31536000';}
</script>
<?= pwa_script_tag() . "\n" ?>
</body></html>
<?php } // end admin_footer
