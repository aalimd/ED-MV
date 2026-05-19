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
    font-family: \'DM Sans\', sans-serif;
    position: relative;
    z-index: 9999;
    max-width: 100vw;
  }
  #vg-user-summary {
    background: var(--surface, #fff);
    border-bottom: 1px solid var(--border, #e2e8f0);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px max(12px, env(safe-area-inset-right)) 6px max(12px, env(safe-area-inset-left));
    cursor: pointer;
    list-style: none;
    user-select: none;
  }
  #vg-user-summary::-webkit-details-marker { display: none; }
  #vg-user-summary .left { display: flex; align-items: center; gap: 8px; }
  #vg-user-summary .right { display: flex; align-items: center; gap: 8px; color: var(--text-3, #64748b); font-size: .75rem; font-weight: 700; }
  #vg-user-summary .vg-user-name { font-weight: 800; color: var(--theme, #2563eb); max-width: 50vw; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .8rem; }
  #vg-user-details[open] #vg-user-summary { background: var(--surface-2, #f8fafc); }
  
  #vg-user-details .vg-pill { font-size: .65rem; padding: 2px 6px; border-radius: 9999px; background: rgba(37,99,235,0.1); color: var(--theme,#2563eb); font-weight: 800; text-transform: uppercase; white-space: nowrap; line-height: 1; }
  #vg-user-details .vg-pill.time { background: rgba(16,185,129,0.1); color: #059669; }
  
  #vg-user-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: var(--surface, #fff);
    border-bottom: 1px solid var(--border, #e2e8f0);
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
    display: flex;
    flex-direction: column;
    padding: 8px max(12px, env(safe-area-inset-right)) 8px max(12px, env(safe-area-inset-left));
    gap: 4px;
    animation: fadeIn 0.15s ease;
  }
  #vg-user-dropdown .meta { display: flex; align-items: center; justify-content: space-between; padding: 4px 8px 8px 8px; border-bottom: 1px solid var(--border, #e2e8f0); margin-bottom: 4px; font-size: .75rem; font-weight: 600; color: var(--text-2); }
  #vg-user-dropdown a { font-size: .85rem; font-weight: 700; text-decoration: none; padding: 10px 12px; border-radius: 8px; display: flex; align-items: center; gap: 10px; color: var(--text); }
  #vg-user-dropdown a:hover { background: var(--surface-2, #f1f5f9); }
  #vg-user-dropdown a.danger { color: var(--danger, #dc2626); }
  #vg-user-dropdown a.danger:hover { background: rgba(220,38,38,0.05); }
  
  #vg-chevron { transition: transform 0.2s ease; }
  #vg-user-details[open] #vg-chevron { transform: rotate(180deg); }
  
  @keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }
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
      <span>Account Role</span>
      <span class="vg-pill">' . htmlspecialchars($user['role']) . '</span>
    </div>';

if ($isAdmin) {
    $userMenu .= '<a href="' . APP_URL . '/admin/">⚙️ <span>Admin Dashboard</span></a>';
}

$userMenu .= '
    <form method="POST" action="' . APP_URL . '/auth/logout" style="margin:0;">
      ' . csrf_field() . '
      <button type="submit" class="danger" style="width:100%;text-align:left;border:none;background:none;cursor:pointer;">
        🚪 <span>Sign Out</span>
      </button>
    </form>
  </div>
</details>';

// Inject after <body> tag
$html = preg_replace('/<body>/', '<body>' . render_feature_script() . $userMenu, $html, 1);

echo $html;
