<?php
/** Admin Dashboard — Home */
require_once __DIR__ . '/layout.php';
$db = getDB();

$totalUsers = $db->query("SELECT COUNT(*) FROM users WHERE status != 'deleted'")->fetchColumn();
$pendingUsers = $db->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
$activeSubs = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active' AND (expires_at IS NULL OR expires_at > NOW())")->fetchColumn();
$pendingSubs = $db->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'pending'")->fetchColumn();

$recentUsers = $db->query("SELECT * FROM users WHERE status != 'deleted' ORDER BY created_at DESC LIMIT 8")->fetchAll();
$recentLogs = $db->query("SELECT l.*, u.name as user_name FROM activity_log l LEFT JOIN users u ON l.user_id=u.id ORDER BY l.created_at DESC LIMIT 10")->fetchAll();

admin_header('Dashboard', '📊', 'dashboard');
?>

<div class="stats-grid">
  <div class="stat-card"><div class="sc-label">👥 Total Users</div><div class="sc-value"><?= $totalUsers ?></div></div>
  <div class="stat-card"><div class="sc-label">⏳ Pending Approval</div><div class="sc-value"><?= $pendingUsers ?></div>
    <?php if($pendingUsers > 0): ?><div class="sc-change sc-down">Needs attention</div><?php endif; ?></div>
  <div class="stat-card"><div class="sc-label">💳 Active Subscriptions</div><div class="sc-value"><?= $activeSubs ?></div></div>
  <div class="stat-card"><div class="sc-label">📩 Pending Subscriptions</div><div class="sc-value"><?= $pendingSubs ?></div></div>
</div>

<?php if($pendingUsers > 0): ?>
<div class="flash flash-warning">⚠️ You have <strong><?= $pendingUsers ?></strong> user(s) awaiting approval. <a href="<?= APP_URL ?>/admin/users.php?filter=pending" style="color:var(--warning);font-weight:800">Review now →</a></div>
<?php endif; ?>
<?php if($pendingSubs > 0): ?>
<div class="flash flash-info">📩 You have <strong><?= $pendingSubs ?></strong> subscription request(s). <a href="<?= APP_URL ?>/admin/subscriptions.php?filter=pending" style="color:var(--theme);font-weight:800">Review now →</a></div>
<?php endif; ?>

<div class="data-card">
<div class="dc-header"><div class="dc-title">👥 Recent Users</div><a href="<?= APP_URL ?>/admin/users.php" class="topbar-btn">View All →</a></div>
<table class="data-table">
<thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
<tbody>
<?php foreach($recentUsers as $u): ?>
<tr>
<td><strong><?= e($u['name']) ?></strong></td>
<td style="font-size:.8rem;color:var(--text-2)"><?= e($u['email']) ?></td>
<td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
<td><span class="badge badge-<?= $u['status'] ?>"><?= $u['status'] ?></span></td>
<td style="font-size:.78rem;color:var(--text-3)"><?= time_ago($u['created_at']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>

<div class="data-card">
<div class="dc-header"><div class="dc-title">📋 Recent Activity</div><a href="<?= APP_URL ?>/admin/logs.php" class="topbar-btn">View All →</a></div>
<table class="data-table">
<thead><tr><th>User</th><th>Action</th><th>Details</th><th>Time</th></tr></thead>
<tbody>
<?php foreach($recentLogs as $log): ?>
<tr>
<td><?= e($log['user_name'] ?? 'System') ?></td>
<td><span class="badge badge-subscriber"><?= e($log['action']) ?></span></td>
<td style="font-size:.78rem;color:var(--text-2);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($log['details'] ?? '') ?></td>
<td style="font-size:.78rem;color:var(--text-3)"><?= time_ago($log['created_at']) ?></td>
</tr>
<?php endforeach; ?>
<?php if(empty($recentLogs)): ?><tr><td colspan="4" class="empty-state">No activity yet</td></tr><?php endif; ?>
</tbody></table>
</div>

<?php admin_footer(); ?>
