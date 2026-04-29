<?php
/**
 * ED VentGuide Pro — Protected App
 * Auth gate + user menu injection around the original HTML
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/features.php';
require_login();
require_subscription();

$user = session_user();
$isAdmin = $user['role'] === 'admin';

// Render the original HTML from a PHP include guarded against direct access.
define('VENTGUIDE_INTERNAL', true);
ob_start();
include __DIR__ . '/ventguide_raw.php';
$html = ob_get_clean();

// Inject a user menu bar right after <body>
$userMenu = '
<style>
  #vg-user-bar{background:var(--surface,#fff);border-bottom:1px solid var(--border,#e2e8f0);padding:7px max(12px,env(safe-area-inset-right)) 7px max(12px,env(safe-area-inset-left));display:flex;align-items:center;justify-content:space-between;gap:10px;font-family:\'DM Sans\',sans-serif;font-size:.8rem;z-index:999;position:relative;max-width:100vw}
  #vg-user-bar .vg-user-meta,#vg-user-bar .vg-user-actions{display:flex;align-items:center;gap:8px;min-width:0}
  #vg-user-bar .vg-user-name{font-weight:800;color:var(--theme,#2563eb);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:52vw}
  #vg-user-bar .vg-pill{font-size:.7rem;padding:2px 8px;border-radius:9999px;background:rgba(37,99,235,0.1);color:var(--theme,#2563eb);font-weight:700;text-transform:uppercase;white-space:nowrap}
  #vg-user-bar a{font-size:.78rem;font-weight:700;text-decoration:none;padding:7px 10px;border-radius:8px;white-space:nowrap;min-height:34px;display:inline-flex;align-items:center}
  @media (max-width:480px){#vg-user-bar{font-size:.76rem;align-items:flex-start}#vg-user-bar .vg-user-meta{flex-direction:column;align-items:flex-start;gap:3px}#vg-user-bar .vg-user-name{max-width:48vw}#vg-user-bar a{padding:6px 8px;font-size:.74rem}}
</style>
<div id="vg-user-bar">
  <div class="vg-user-meta">
    <span class="vg-user-name">👤 ' . htmlspecialchars($user['name']) . '</span>
    <span class="vg-pill">' . htmlspecialchars($user['role']) . '</span>
  </div>
  <div class="vg-user-actions">';

if ($isAdmin) {
    $userMenu .= '<a href="' . APP_URL . '/admin/" style="color:var(--theme,#2563eb);background:rgba(37,99,235,0.08);">⚙️ Admin</a>';
}

$userMenu .= '
    <a href="' . APP_URL . '/auth/logout.php" style="color:var(--danger,#dc2626);background:rgba(220,38,38,0.08);">🚪 Logout</a>
  </div>
</div>';

// Inject after <body> tag
$html = preg_replace('/<body>/', '<body>' . render_feature_script() . $userMenu, $html, 1);

echo $html;
