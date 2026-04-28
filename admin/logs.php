<?php
/** Admin — Activity Logs */
require_once __DIR__ . '/layout.php';
$db = getDB();

$filter = $_GET['action'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$where = '';
$params = [];
if ($filter) { $where = 'WHERE l.action = ?'; $params[] = $filter; }

$total = $db->prepare("SELECT COUNT(*) FROM activity_log l {$where}");
$total->execute($params);
$totalCount = (int)$total->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

$stmt = $db->prepare("SELECT l.*, u.name as user_name, u.email as user_email FROM activity_log l LEFT JOIN users u ON l.user_id=u.id {$where} ORDER BY l.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$actions = $db->query("SELECT DISTINCT action FROM activity_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

admin_header('Activity Logs', '📋', 'logs');
?>

<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
<a href="?action=" class="topbar-btn" style="<?=!$filter?'background:var(--theme);color:#fff;border-color:var(--theme)':''?>">All</a>
<?php foreach($actions as $a): ?>
<a href="?action=<?= urlencode($a) ?>" class="topbar-btn" style="<?=$filter===$a?'background:var(--theme);color:#fff;border-color:var(--theme)':''?>"><?= e($a) ?></a>
<?php endforeach; ?>
</div>

<div class="data-card">
<div class="dc-header"><div class="dc-title">📋 Logs (<?= $totalCount ?>)</div></div>
<div style="overflow-x:auto">
<table class="data-table">
<thead><tr><th>Time</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
<tbody>
<?php foreach($logs as $log): ?>
<tr>
<td style="font-size:.78rem;color:var(--text-3);white-space:nowrap"><?= fmt_datetime($log['created_at']) ?></td>
<td><?= e($log['user_name'] ?? 'System') ?></td>
<td><span class="badge badge-subscriber"><?= e($log['action']) ?></span></td>
<td style="font-size:.78rem;color:var(--text-2);max-width:250px;overflow:hidden;text-overflow:ellipsis"><?= e($log['details'] ?? '') ?></td>
<td style="font-size:.75rem;font-family:'Space Mono',monospace;color:var(--text-3)"><?= e($log['ip'] ?? '') ?></td>
</tr>
<?php endforeach; ?>
<?php if(empty($logs)): ?><tr><td colspan="5" class="empty-state"><div class="es-icon">📋</div><p>No logs yet</p></td></tr><?php endif; ?>
</tbody></table>
</div>
<?php if($totalPages > 1): ?>
<div class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<a href="?page=<?=$i?><?=$filter?"&action=".urlencode($filter):''?>" class="page-btn <?=$i===$page?'active':''?>"><?=$i?></a>
<?php endfor; ?>
</div>
<?php endif; ?>
</div>

<?php admin_footer(); ?>
