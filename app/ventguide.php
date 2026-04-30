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

// Fetch remaining days
$daysLeftText = '';
$db = getDB();
$stmt = $db->prepare("SELECT expires_at FROM subscriptions WHERE user_id = ? AND status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) ORDER BY id DESC LIMIT 1");
$stmt->execute([$user['id']]);
$expiresAt = $stmt->fetchColumn();

if ($user['role'] !== 'admin') {
    if (!$expiresAt) {
        $daysLeftText = 'Lifetime';
    } else {
        $daysLeft = max(0, floor((strtotime($expiresAt) - time()) / 86400));
        $daysLeftText = $daysLeft . ' Days';
    }
}

// Inject a user menu bar right after <body>
$userMenu = '
<style>
  #vg-user-details {
    background: var(--surface, #fff);
    border-bottom: 1px solid var(--border, #e2e8f0);
    font-family: \'DM Sans\', sans-serif;
    font-size: .8rem;
    position: relative;
    z-index: 999;
    max-width: 100vw;
  }
  #vg-user-summary {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px max(12px, env(safe-area-inset-right)) 8px max(12px, env(safe-area-inset-left));
    cursor: pointer;
    list-style: none;
    user-select: none;
  }
  #vg-user-summary::-webkit-details-marker { display: none; }
  #vg-user-summary .left { display: flex; align-items: center; gap: 8px; }
  #vg-user-summary .right { display: flex; align-items: center; gap: 8px; color: var(--text-3, #64748b); font-size: .75rem; font-weight: 700; }
  #vg-user-summary .vg-user-name { font-weight: 800; color: var(--theme, #2563eb); max-width: 50vw; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  #vg-user-details[open] #vg-user-summary { border-bottom: 1px dashed var(--border, #e2e8f0); background: var(--surface-2, #f8fafc); }
  
  #vg-user-details .vg-pill { font-size: .65rem; padding: 3px 8px; border-radius: 9999px; background: rgba(37,99,235,0.1); color: var(--theme,#2563eb); font-weight: 800; text-transform: uppercase; white-space: nowrap; line-height: 1; }
  #vg-user-details .vg-pill.time { background: rgba(16,185,129,0.1); color: #059669; }
  
  #vg-user-dropdown {
    padding: 10px max(12px, env(safe-area-inset-right)) 10px max(12px, env(safe-area-inset-left));
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    animation: fadeIn 0.2s ease;
  }
  #vg-user-dropdown .meta { display: flex; align-items: center; gap: 6px; }
  #vg-user-dropdown .actions { display: flex; align-items: center; gap: 8px; }
  #vg-user-dropdown a { font-size: .75rem; font-weight: 700; text-decoration: none; padding: 6px 10px; border-radius: 8px; white-space: nowrap; display: inline-flex; align-items: center; line-height: 1; }
  
  #vg-chevron { transition: transform 0.2s ease; }
  #vg-user-details[open] #vg-chevron { transform: rotate(180deg); }
  
  @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
  
  @media (max-width:480px){
    #vg-user-summary { padding: 6px 10px; }
    #vg-user-dropdown { flex-direction: column; align-items: flex-start; gap: 10px; padding: 12px 10px; }
    #vg-user-dropdown .actions { width: 100%; justify-content: flex-end; }
  }
</style>
<details id="vg-user-details">
  <summary id="vg-user-summary">
    <div class="left">
      <span class="vg-user-name">👤 ' . htmlspecialchars($user['name']) . '</span>
    </div>
    <div class="right">
      ' . ($daysLeftText ? '<span class="vg-pill time">⏳ ' . $daysLeftText . '</span>' : '') . '
      <svg id="vg-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
    </div>
  </summary>
  <div id="vg-user-dropdown">
    <div class="meta">
      <span class="vg-pill">' . htmlspecialchars($user['role']) . '</span>
    </div>
    <div class="actions">';

if ($isAdmin) {
    $userMenu .= '<a href="' . APP_URL . '/admin/" style="color:var(--theme,#2563eb);background:rgba(37,99,235,0.08);">⚙️ Admin Dashboard</a>';
}

$userMenu .= '
      <a href="' . APP_URL . '/auth/logout.php" style="color:var(--danger,#dc2626);background:rgba(220,38,38,0.08);">🚪 Logout</a>
    </div>
  </div>
</details>';

// Inject after <body> tag
$html = preg_replace('/<body>/', '<body>' . render_feature_script() . $userMenu, $html, 1);

echo $html;
