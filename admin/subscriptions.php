<?php
/** Admin — Subscription Management */
require_once __DIR__ . '/layout.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate()) {
    $action = $_POST['action'] ?? '';
    $subId = (int)($_POST['sub_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($action === 'activate' && $subId > 0) {
        $planStmt = $db->prepare("SELECT p.duration_days FROM subscriptions s JOIN plans p ON s.plan_id=p.id WHERE s.id=?");
        $planStmt->execute([$subId]); $planRow = $planStmt->fetch();
        $days = $planRow ? $planRow['duration_days'] : 30;
        $expires = date('Y-m-d H:i:s', time() + ($days * 86400));
        $adminId = session_user()['id'];
        $db->prepare("UPDATE subscriptions SET status='active', starts_at=NOW(), expires_at=?, activated_by=? WHERE id=?")->execute([$expires, $adminId, $subId]);
        // Also set user role to subscriber
        $db->prepare("UPDATE users u JOIN subscriptions s ON u.id=s.user_id SET u.role='subscriber' WHERE s.id=?")->execute([$subId]);
        log_activity('admin_activate_sub', "Activated subscription ID: {$subId}");
        flash('success', 'Subscription activated!');
    } elseif ($action === 'cancel' && $subId > 0) {
        $db->prepare("UPDATE subscriptions SET status='cancelled' WHERE id=?")->execute([$subId]);
        log_activity('admin_cancel_sub', "Cancelled subscription ID: {$subId}");
        flash('warning', 'Subscription cancelled.');
    } elseif ($action === 'extend' && $subId > 0) {
        $extraDays = (int)($_POST['extra_days'] ?? 30);
        $db->prepare("UPDATE subscriptions SET expires_at = DATE_ADD(IFNULL(expires_at, NOW()), INTERVAL ? DAY) WHERE id=?")->execute([$extraDays, $subId]);
        log_activity('admin_extend_sub', "Extended sub ID {$subId} by {$extraDays} days");
        flash('success', "Subscription extended by {$extraDays} days.");
    } elseif ($action === 'grant' && $userId > 0) {
        $planId = (int)($_POST['plan_id'] ?? 1);
        $planStmt = $db->prepare("SELECT duration_days FROM plans WHERE id=?");
        $planStmt->execute([$planId]); $planRow = $planStmt->fetch();
        $days = $planRow ? $planRow['duration_days'] : 30;
        $expires = date('Y-m-d H:i:s', time() + ($days * 86400));
        $adminId = session_user()['id'];
        $db->prepare("INSERT INTO subscriptions (user_id,plan_id,status,starts_at,expires_at,activated_by) VALUES (?,?,'active',NOW(),?,?)")
           ->execute([$userId, $planId, $expires, $adminId]);
        $db->prepare("UPDATE users SET role='subscriber' WHERE id=?")->execute([$userId]);
        log_activity('admin_grant_sub', "Granted subscription to user ID: {$userId}");
        flash('success', 'Subscription granted!');
    }
    redirect(APP_URL . '/admin/subscriptions.php');
}

$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter === 'pending') $where = "WHERE s.status = 'pending'";
elseif ($filter === 'active') $where = "WHERE s.status = 'active'";
elseif ($filter === 'expired') $where = "WHERE s.status = 'expired' OR (s.status='active' AND s.expires_at < NOW())";

$subs = $db->query("SELECT s.*, u.name, u.email, p.name as plan_name, p.duration_days, a.name as activated_by_name FROM subscriptions s JOIN users u ON s.user_id=u.id JOIN plans p ON s.plan_id=p.id LEFT JOIN users a ON s.activated_by=a.id {$where} ORDER BY s.created_at DESC")->fetchAll();
$plans = $db->query("SELECT * FROM plans ORDER BY sort_order")->fetchAll();
$usersWithoutSub = $db->query("SELECT u.* FROM users u LEFT JOIN subscriptions s ON u.id=s.user_id AND s.status='active' WHERE s.id IS NULL AND u.status='active' AND u.role != 'admin' ORDER BY u.name")->fetchAll();

admin_header('Subscriptions', '💳', 'subscriptions');
?>

<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
<?php foreach(['all'=>'All','pending'=>'⏳ Pending','active'=>'✅ Active','expired'=>'⌛ Expired'] as $k=>$v): ?>
<a href="?filter=<?=$k?>" class="topbar-btn" style="<?=$filter===$k?'background:var(--theme);color:#fff;border-color:var(--theme)':''?>"><?=$v?></a>
<?php endforeach; ?>
</div>

<?php if(!empty($usersWithoutSub)): ?>
<div class="data-card" style="margin-bottom:20px">
<div class="dc-header"><div class="dc-title">🎁 Grant Subscription</div></div>
<div style="padding:16px">
<form method="POST" style="display:flex;gap:8px;flex-wrap:wrap;align-items:end"><?= csrf_field() ?>
<input type="hidden" name="action" value="grant">
<div style="flex:1;min-width:150px"><label style="font-size:.72rem;font-weight:800;color:var(--text-3);text-transform:uppercase;display:block;margin-bottom:4px">User</label>
<select name="user_id" style="width:100%;padding:9px;border:2px solid var(--border);border-radius:8px;font-family:inherit;font-size:.85rem;font-weight:600;background:var(--surface-2);color:var(--text)">
<?php foreach($usersWithoutSub as $u): ?><option value="<?= $u['id'] ?>"><?= e($u['name']) ?> (<?= e($u['email']) ?>)</option><?php endforeach; ?>
</select></div>
<div style="min-width:120px"><label style="font-size:.72rem;font-weight:800;color:var(--text-3);text-transform:uppercase;display:block;margin-bottom:4px">Plan</label>
<select name="plan_id" style="width:100%;padding:9px;border:2px solid var(--border);border-radius:8px;font-family:inherit;font-size:.85rem;font-weight:600;background:var(--surface-2);color:var(--text)">
<?php foreach($plans as $p): ?><option value="<?= $p['id'] ?>"><?= e($p['name']) ?> (<?= $p['duration_days'] ?>d)</option><?php endforeach; ?>
</select></div>
<button type="submit" class="act-btn success" style="padding:9px 16px">✅ Grant</button>
</form>
</div></div>
<?php endif; ?>

<div class="data-card">
<div class="dc-header"><div class="dc-title">💳 Subscriptions (<?= count($subs) ?>)</div></div>
<div style="overflow-x:auto">
<table class="data-table">
<thead><tr><th>ID</th><th>User</th><th>Plan</th><th>Status</th><th>Starts</th><th>Expires</th><th>Activated By</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($subs as $s):
  $isExpired = $s['status']==='active' && $s['expires_at'] && strtotime($s['expires_at']) < time();
?>
<tr>
<td style="font-family:'Space Mono',monospace;font-size:.78rem;color:var(--text-3)">#<?= $s['id'] ?></td>
<td><strong><?= e($s['name']) ?></strong><br><span style="font-size:.75rem;color:var(--text-3)"><?= e($s['email']) ?></span></td>
<td><?= e($s['plan_name']) ?></td>
<td><span class="badge badge-<?= $isExpired?'expired':$s['status'] ?>"><?= $isExpired?'expired':$s['status'] ?></span></td>
<td style="font-size:.78rem;color:var(--text-3)"><?= fmt_date($s['starts_at']) ?></td>
<td style="font-size:.78rem;color:var(--text-3)"><?= fmt_date($s['expires_at']) ?></td>
<td style="font-size:.78rem;color:var(--text-3)"><?= e($s['activated_by_name'] ?? '—') ?></td>
<td style="white-space:nowrap">
<form method="POST" style="display:inline"><?= csrf_field() ?><input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
<?php if($s['status']==='pending'): ?>
<button name="action" value="activate" class="act-btn success">✅ Activate</button>
<?php endif; ?>
<?php if($s['status']==='active'): ?>
<button name="action" value="cancel" class="act-btn danger" onclick="return confirm('Cancel?')">❌</button>
<input type="number" name="extra_days" value="30" style="width:55px;padding:4px;border:1px solid var(--border);border-radius:4px;font-size:.75rem;text-align:center">
<button name="action" value="extend" class="act-btn">📅 Extend</button>
<?php endif; ?>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php if(empty($subs)): ?><tr><td colspan="8" class="empty-state"><div class="es-icon">💳</div><p>No subscriptions yet</p></td></tr><?php endif; ?>
</tbody></table>
</div></div>

<?php admin_footer(); ?>
