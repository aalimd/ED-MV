<?php
/** Admin — Subscription Management */
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../includes/features.php';
require_once __DIR__ . '/../includes/subscription_service.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate()) {
    $action = $_POST['action'] ?? '';
    $subId = (int)($_POST['sub_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0);
    $adminId = session_user()['id'];

    try {
        if ($action === 'activate' && $subId > 0) {
            $db->beginTransaction();
            $subUserId = subscription_user_id($db, $subId);
            if ($subUserId === null) {
                throw new RuntimeException('Subscription not found.');
            }
            subscription_lock_user($db, $subUserId);
            $subRow = subscription_row_with_plan($db, $subId);
            if (!$subRow) {
                throw new RuntimeException('Subscription not found.');
            }
            if ((int)$subRow['is_active'] !== 1) {
                throw new RuntimeException('The selected subscription uses an inactive plan.');
            }

            $userSubscriptions = subscription_lock_user_subscriptions($db, $subUserId);
            $rolloverRows = array_filter(
                $userSubscriptions,
                static fn (array $row): bool => (int)$row['id'] !== $subId
            );
            $rolloverSeconds = subscription_rollover_seconds($rolloverRows);
            $expires = subscription_expires_at(subscription_duration_days($subRow), $rolloverSeconds);

            subscription_cancel_user_statuses($db, $subUserId, ['active', 'pending'], $subId);
            $stmt = $db->prepare("UPDATE subscriptions SET status='active', starts_at=NOW(), expires_at=?, activated_by=? WHERE id=?");
            $stmt->execute([$expires, $adminId, $subId]);
            subscription_sync_user_role($db, $subUserId);
            log_activity('admin_activate_sub', "Activated sub ID: {$subId} with rollover of " . floor($rolloverSeconds / 86400) . " days");
            $db->commit();
            invalidate_feature_cache();
            flash('success', "Subscription activated. Added " . floor($rolloverSeconds / 86400) . " rollover days from previous active time.");

        } elseif ($action === 'cancel' && $subId > 0) {
            $db->beginTransaction();
            $subUserId = subscription_user_id($db, $subId);
            if ($subUserId === null) {
                throw new RuntimeException('Subscription not found.');
            }
            subscription_lock_user($db, $subUserId);
            $subRow = subscription_row_with_plan($db, $subId);
            if (!$subRow) {
                throw new RuntimeException('Subscription not found.');
            }
            $db->prepare("UPDATE subscriptions SET status='cancelled' WHERE id=?")->execute([$subId]);
            subscription_sync_user_role($db, $subUserId);
            log_activity('admin_cancel_sub', "Cancelled subscription ID: {$subId}");
            $db->commit();
            invalidate_feature_cache();
            flash('warning', 'Subscription cancelled.');

        } elseif ($action === 'modify_days' && $subId > 0) {
            $days = (int)($_POST['extra_days'] ?? 0);
            if ($days !== 0) {
                if ($days < -3650 || $days > 3650) {
                    throw new RuntimeException('Day adjustment is outside the allowed range.');
                }

                $db->beginTransaction();
                $subUserId = subscription_user_id($db, $subId);
                if ($subUserId === null) {
                    throw new RuntimeException('Subscription not found.');
                }
                subscription_lock_user($db, $subUserId);
                $subRow = subscription_row_with_plan($db, $subId);
                if (!$subRow) {
                    throw new RuntimeException('Subscription not found.');
                }
                $db->prepare("UPDATE subscriptions SET expires_at = DATE_ADD(IFNULL(expires_at, NOW()), INTERVAL ? DAY) WHERE id=?")->execute([$days, $subId]);
                subscription_sync_user_role($db, $subUserId);
                $actionWord = $days > 0 ? 'Extended' : 'Reduced';
                log_activity('admin_modify_sub_days', "{$actionWord} sub ID {$subId} by " . abs($days) . " days");
                $db->commit();
                invalidate_feature_cache();
                flash('success', "Subscription " . strtolower($actionWord) . " by " . abs($days) . " days.");
            }

        } elseif ($action === 'change_plan' && $subId > 0) {
            $newPlanId = (int)($_POST['new_plan_id'] ?? 0);
            if ($newPlanId <= 0) {
                throw new RuntimeException('Invalid plan selected.');
            }

            $db->beginTransaction();
            $subUserId = subscription_user_id($db, $subId);
            if ($subUserId === null) {
                throw new RuntimeException('Subscription not found.');
            }
            subscription_lock_user($db, $subUserId);
            $subRow = subscription_row_with_plan($db, $subId);
            if (!$subRow) {
                throw new RuntimeException('Subscription not found.');
            }
            $newPlan = subscription_active_plan($db, $newPlanId, true);
            if (!$newPlan) {
                throw new RuntimeException('Invalid or inactive plan selected.');
            }

            $oldPlanName = $subRow['plan_name'] ?: 'Unknown';
            $oldPlanDays = subscription_duration_days($subRow);
            $newPlanDays = subscription_duration_days($newPlan);
            $dayDifference = 0;

            $db->prepare('UPDATE subscriptions SET plan_id = ? WHERE id = ?')->execute([$newPlanId, $subId]);
            if ($subRow['status'] === 'active' && !empty($subRow['starts_at'])) {
                $dayDifference = $newPlanDays - $oldPlanDays;
                if ($dayDifference !== 0) {
                    $db->prepare("UPDATE subscriptions SET expires_at = DATE_ADD(IFNULL(expires_at, NOW()), INTERVAL ? DAY) WHERE id=?")->execute([$dayDifference, $subId]);
                }
            }

            subscription_sync_user_role($db, $subUserId);
            log_activity('admin_change_plan', "Changed sub ID {$subId} from \"{$oldPlanName}\" to \"{$newPlan['name']}\" (adjusted expiry by {$dayDifference} days)");
            $db->commit();
            invalidate_feature_cache();
            flash('success', "Plan changed from {$oldPlanName} to {$newPlan['name']}. Expiry date automatically adjusted.");

        } elseif ($action === 'grant' && $userId > 0) {
            $planId = (int)($_POST['plan_id'] ?? 0);
            $db->beginTransaction();
            $userRow = subscription_lock_user($db, $userId);
            if (!$userRow || $userRow['status'] !== 'active' || $userRow['role'] === 'admin') {
                throw new RuntimeException('Subscription can only be granted to active non-admin users.');
            }

            $planRow = subscription_active_plan($db, $planId, true);
            if (!$planRow) {
                throw new RuntimeException('Invalid or inactive plan selected.');
            }

            subscription_lock_user_subscriptions($db, $userId);
            subscription_cancel_user_statuses($db, $userId, ['active', 'pending']);
            $expires = subscription_expires_at(subscription_duration_days($planRow));
            $stmt = $db->prepare("INSERT INTO subscriptions (user_id, plan_id, status, starts_at, expires_at, activated_by) VALUES (?, ?, 'active', NOW(), ?, ?)");
            $stmt->execute([$userId, $planId, $expires, $adminId]);
            subscription_sync_user_role($db, $userId);
            log_activity('admin_grant_sub', "Granted subscription to user ID: {$userId}");
            $db->commit();
            invalidate_feature_cache();
            flash('success', 'Subscription granted.');
        }
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log('Admin subscription mutation failed: ' . $e->getMessage());
        flash('danger', $e instanceof RuntimeException ? $e->getMessage() : 'Unable to update the subscription. Please try again.');
    }
    redirect(app_url('/admin/subscriptions' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : '')));
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
