<?php
/** Admin — Subscription Management */
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../includes/features.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate()) {
    $action = $_POST['action'] ?? '';
    $subId = (int)($_POST['sub_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0);
    $adminId = session_user()['id'];

    if ($action === 'activate' && $subId > 0) {
        // 1. Get new plan details
        $planStmt = $db->prepare("SELECT s.user_id, p.duration_days FROM subscriptions s JOIN plans p ON s.plan_id=p.id WHERE s.id=?");
        $planStmt->execute([$subId]); 
        $subRow = $planStmt->fetch();
        
        if ($subRow) {
            $subUserId = $subRow['user_id'];
            $newPlanDays = $subRow['duration_days'] ?: 30;
            
            // 2. Find any currently active subscription for this user to calculate rollover days
            $oldSubStmt = $db->prepare("SELECT id, expires_at FROM subscriptions WHERE user_id = ? AND status = 'active' AND id != ?");
            $oldSubStmt->execute([$subUserId, $subId]);
            $oldSubs = $oldSubStmt->fetchAll();
            
            $rolloverSeconds = 0;
            foreach ($oldSubs as $oldSub) {
                if (strtotime($oldSub['expires_at']) > time()) {
                    $rolloverSeconds += (strtotime($oldSub['expires_at']) - time());
                }
                // Cancel old active subscriptions to enforce the "1 active subscription only" rule
                $db->prepare("UPDATE subscriptions SET status='cancelled' WHERE id=?")->execute([$oldSub['id']]);
            }
            
            // 3. Calculate new exact expiry: New Plan Days + Rollover Days from old plans
            $totalSeconds = ($newPlanDays * 86400) + $rolloverSeconds;
            $expires = date('Y-m-d H:i:s', time() + $totalSeconds);
            
            // 4. Activate the new subscription
            $db->prepare("UPDATE subscriptions SET status='active', starts_at=NOW(), expires_at=?, activated_by=? WHERE id=?")->execute([$expires, $adminId, $subId]);
            $db->prepare("UPDATE users u JOIN subscriptions s ON u.id=s.user_id SET u.role='subscriber' WHERE s.id=?")->execute([$subId]);
            
            log_activity('admin_activate_sub', "Activated sub ID: {$subId} with rollover of " . floor($rolloverSeconds/86400) . " days");
            flash('success', "Subscription activated! Added " . floor($rolloverSeconds/86400) . " rollover days from previous plan.");
        }


    } elseif ($action === 'cancel' && $subId > 0) {
        $db->prepare("UPDATE subscriptions SET status='cancelled' WHERE id=?")->execute([$subId]);
        log_activity('admin_cancel_sub', "Cancelled subscription ID: {$subId}");
        flash('warning', 'Subscription cancelled.');

    } elseif ($action === 'modify_days' && $subId > 0) {
        $days = (int)($_POST['extra_days'] ?? 0);
        if ($days !== 0) {
            $db->prepare("UPDATE subscriptions SET expires_at = DATE_ADD(IFNULL(expires_at, NOW()), INTERVAL ? DAY) WHERE id=?")->execute([$days, $subId]);
            $actionWord = $days > 0 ? "Extended" : "Reduced";
            log_activity('admin_modify_sub_days', "{$actionWord} sub ID {$subId} by " . abs($days) . " days");
            flash('success', "Subscription " . strtolower($actionWord) . " by " . abs($days) . " days.");
        }

    } elseif ($action === 'change_plan' && $subId > 0) {
        $newPlanId = (int)($_POST['new_plan_id'] ?? 0);
        if ($newPlanId > 0) {
            // Validate plan exists
            $planCheck = $db->prepare("SELECT id, name, duration_days FROM plans WHERE id = ?");
            $planCheck->execute([$newPlanId]);
            $newPlan = $planCheck->fetch();
            if ($newPlan) {
                // Get the old plan name for logging
                $oldStmt = $db->prepare("SELECT p.name FROM subscriptions s JOIN plans p ON s.plan_id=p.id WHERE s.id=?");
                $oldStmt->execute([$subId]);
                $oldPlanName = $oldStmt->fetchColumn() ?: 'Unknown';

                // Update the subscription's plan
                $db->prepare("UPDATE subscriptions SET plan_id = ? WHERE id = ?")->execute([$newPlanId, $subId]);

                // Recalculate expires_at based on the new plan's duration
                $subStmt = $db->prepare("SELECT status, starts_at, expires_at FROM subscriptions WHERE id = ?");
                $subStmt->execute([$subId]);
                $sub = $subStmt->fetch();
                
                $dayDifference = 0;
                if ($sub && $sub['status'] === 'active' && !empty($sub['starts_at'])) {
                    $newDuration = $newPlan['duration_days'] ?: 30; // default 30 if 0
                    
                    // Fetch old plan duration
                    $oldPlanStmt = $db->prepare("SELECT duration_days FROM plans WHERE name = ? LIMIT 1");
                    $oldPlanStmt->execute([$oldPlanName]);
                    $oldPlanDays = $oldPlanStmt->fetchColumn() ?: 30;
                    
                    $dayDifference = $newDuration - $oldPlanDays;
                    
                    if ($dayDifference !== 0) {
                        $db->prepare("UPDATE subscriptions SET expires_at = DATE_ADD(IFNULL(expires_at, NOW()), INTERVAL ? DAY) WHERE id=?")->execute([$dayDifference, $subId]);
                    }
                }

                log_activity('admin_change_plan', "Changed sub ID {$subId} from \"{$oldPlanName}\" to \"{$newPlan['name']}\" (adjusted expiry by {$dayDifference} days)");
                flash('success', "Plan changed from <strong>{$oldPlanName}</strong> to <strong>" . e($newPlan['name']) . "</strong>. Expiry date automatically adjusted.");
            } else {
                flash('danger', 'Invalid plan selected.');
            }
        }

    } elseif ($action === 'grant' && $userId > 0) {
        $planId = (int)($_POST['plan_id'] ?? 1);
        $planStmt = $db->prepare("SELECT duration_days FROM plans WHERE id=?");
        $planStmt->execute([$planId]); $planRow = $planStmt->fetch();
        $days = $planRow ? $planRow['duration_days'] : 30;
        $expires = date('Y-m-d H:i:s', time() + ($days * 86400));
        $db->prepare("INSERT INTO subscriptions (user_id,plan_id,status,starts_at,expires_at,activated_by) VALUES (?,?,'active',NOW(),?,?)")
           ->execute([$userId, $planId, $expires, $adminId]);
        $db->prepare("UPDATE users SET role='subscriber' WHERE id=?")->execute([$userId]);
        log_activity('admin_grant_sub', "Granted subscription to user ID: {$userId}");
        flash('success', 'Subscription granted!');
    }
    redirect(APP_URL . '/admin/subscriptions.php' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : ''));
}

$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter === 'pending') $where = "WHERE s.status = 'pending'";
elseif ($filter === 'active') $where = "WHERE s.status = 'active'";
elseif ($filter === 'expired') $where = "WHERE s.status = 'expired' OR (s.status='active' AND s.expires_at < NOW())";
elseif ($filter === 'cancelled') $where = "WHERE s.status = 'cancelled'";

$subs = $db->query("SELECT s.*, u.name, u.email, u.status as user_status, p.name as plan_name, p.duration_days, a.name as activated_by_name FROM subscriptions s JOIN users u ON s.user_id=u.id JOIN plans p ON s.plan_id=p.id LEFT JOIN users a ON s.activated_by=a.id {$where} ORDER BY s.created_at DESC")->fetchAll();
$plans = $db->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order")->fetchAll();
$usersWithoutSub = $db->query("SELECT u.* FROM users u LEFT JOIN subscriptions s ON u.id=s.user_id AND s.status='active' WHERE s.id IS NULL AND u.status='active' AND u.role != 'admin' ORDER BY u.name")->fetchAll();

admin_header('Subscriptions', '💳', 'subscriptions');
?>

<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
<?php foreach(['all'=>'All','pending'=>'⏳ Pending','active'=>'✅ Active','expired'=>'⌛ Expired','cancelled'=>'❌ Cancelled'] as $k=>$v): ?>
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
  $displayStatus = $isExpired ? 'expired' : $s['status'];
?>
<tr>
<td style="font-family:'Space Mono',monospace;font-size:.78rem;color:var(--text-3)">#<?= $s['id'] ?></td>
<td>
  <strong><?= e($s['name']) ?></strong>
  <?php if($s['user_status'] === 'suspended'): ?><span class="badge badge-suspended" style="font-size:.6rem;margin-left:4px">SUSPENDED</span><?php endif; ?>
  <br><span style="font-size:.75rem;color:var(--text-3)"><?= e($s['email']) ?></span>
</td>
<td><strong><?= e($s['plan_name']) ?></strong></td>
<td><span class="badge badge-<?= $displayStatus ?>"><?= $displayStatus ?></span></td>
<td style="font-size:.78rem;color:var(--text-3)"><?= fmt_date($s['starts_at']) ?></td>
<td style="font-size:.78rem;color:var(--text-3)"><?= fmt_date($s['expires_at']) ?></td>
<td style="font-size:.78rem;color:var(--text-3)"><?= e($s['activated_by_name'] ?? '—') ?></td>
<td style="white-space:nowrap">
<form method="POST" style="display:inline"><?= csrf_field() ?><input type="hidden" name="sub_id" value="<?= $s['id'] ?>">

<?php if($s['status']==='pending'): ?>
<button name="action" value="activate" class="act-btn success">✅ Activate</button>
<?php endif; ?>

<?php if($s['status']==='active' || $isExpired): ?>
<!-- Change Plan -->
<select name="new_plan_id" style="width:90px;padding:4px;border:1px solid var(--border);border-radius:4px;font-size:.75rem;font-weight:600;background:var(--surface-2);color:var(--text)">
<?php foreach($plans as $p): ?>
<option value="<?= $p['id'] ?>" <?= $p['id'] == $s['plan_id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
<?php endforeach; ?>
</select>
<button name="action" value="change_plan" class="act-btn" title="Change plan">🔄</button>

<!-- Modify Days -->
<input type="number" name="extra_days" value="30" min="-3650" max="3650" placeholder="+/-" style="width:60px;padding:4px;border:1px solid var(--border);border-radius:4px;font-size:.75rem;text-align:center">
<button name="action" value="modify_days" class="act-btn" title="Add or remove days (+/-)">⏳</button>

<!-- Cancel -->
<button name="action" value="cancel" class="act-btn danger" onclick="return confirm('Cancel this subscription?')" title="Cancel subscription">❌</button>
<?php endif; ?>

</form>
</td>
</tr>
<?php endforeach; ?>
<?php if(empty($subs)): ?><tr><td colspan="8" class="empty-state"><div class="es-icon">💳</div><p>No subscriptions yet</p></td></tr><?php endif; ?>
</tbody></table>
</div></div>

<?php admin_footer(); ?>
