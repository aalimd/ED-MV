<?php
/** Admin — User Management */
require_once __DIR__ . '/layout.php';
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate()) {
    $action = $_POST['action'] ?? '';
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid > 0) {
        switch ($action) {
            case 'activate':
                $db->prepare("UPDATE users SET status='active' WHERE id=?")->execute([$uid]);
                log_activity('admin_activate_user', "Activated user ID: {$uid}");
                flash('success', 'User activated.');
                break;
            case 'suspend':
                $db->prepare("UPDATE users SET status='suspended' WHERE id=?")->execute([$uid]);
                log_activity('admin_suspend_user', "Suspended user ID: {$uid}");
                flash('warning', 'User suspended.');
                break;
            case 'delete':
                $db->prepare("UPDATE users SET status='deleted' WHERE id=?")->execute([$uid]);
                log_activity('admin_delete_user', "Soft-deleted user ID: {$uid}");
                flash('danger', 'User deleted.');
                break;
            case 'make_admin':
                $db->prepare("UPDATE users SET role='admin' WHERE id=?")->execute([$uid]);
                log_activity('admin_promote', "Promoted user ID {$uid} to admin");
                flash('success', 'User promoted to admin.');
                break;
            case 'make_subscriber':
                $db->prepare("UPDATE users SET role='subscriber' WHERE id=?")->execute([$uid]);
                log_activity('admin_set_role', "Set user ID {$uid} to subscriber");
                flash('success', 'Role updated to subscriber.');
                break;
            case 'make_user':
                $db->prepare("UPDATE users SET role='user' WHERE id=?")->execute([$uid]);
                log_activity('admin_set_role', "Set user ID {$uid} to user");
                flash('success', 'Role updated to user.');
                break;
            case 'reset_password':
                $newPwd = bin2hex(random_bytes(4)); // 8-char temp password
                $hash = password_hash($newPwd, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                $db->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $uid]);
                log_activity('admin_reset_password', "Reset password for user ID: {$uid}");
                flash('success', "Password reset. Temporary password: <strong>{$newPwd}</strong> — share securely.");
                break;
        }
        redirect(APP_URL . '/admin/users.php' . (isset($_GET['filter']) ? '?filter='.$_GET['filter'] : ''));
    }
}

// Fetch users
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$where = "WHERE status != 'deleted'";
if ($filter === 'pending') $where .= " AND status = 'pending'";
elseif ($filter === 'active') $where .= " AND status = 'active'";
elseif ($filter === 'suspended') $where .= " AND status = 'suspended'";
if ($search) $where .= " AND (name LIKE ? OR email LIKE ?)";

$sql = "SELECT * FROM users {$where} ORDER BY created_at DESC";
if ($search) {
    $stmt = $db->prepare($sql);
    $stmt->execute(["%{$search}%", "%{$search}%"]);
} else {
    $stmt = $db->query($sql);
}
$users = $stmt->fetchAll();

admin_header('User Management', '👥', 'users');
?>

<div class="search-bar">
<form method="GET" style="display:flex;gap:8px;flex:1">
<input type="text" name="q" placeholder="🔍 Search users..." value="<?= e($search) ?>">
<input type="hidden" name="filter" value="<?= e($filter) ?>">
<button type="submit" class="topbar-btn">Search</button>
</form>
</div>

<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
<?php foreach(['all'=>'All','pending'=>'⏳ Pending','active'=>'✅ Active','suspended'=>'🚫 Suspended'] as $k=>$v): ?>
<a href="?filter=<?=$k?><?=$search?"&q=".urlencode($search):''?>" class="topbar-btn" style="<?=$filter===$k?'background:var(--theme);color:#fff;border-color:var(--theme)':''?>"><?=$v?></a>
<?php endforeach; ?>
</div>

<div class="data-card">
<div class="dc-header"><div class="dc-title">👥 Users (<?= count($users) ?>)</div></div>
<div style="overflow-x:auto">
<table class="data-table">
<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Last Login</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($users as $u): ?>
<tr>
<td style="font-family:'Space Mono',monospace;font-size:.78rem;color:var(--text-3)">#<?= $u['id'] ?></td>
<td><strong><?= e($u['name']) ?></strong></td>
<td style="font-size:.8rem;color:var(--text-2)"><?= e($u['email']) ?></td>
<td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
<td><span class="badge badge-<?= $u['status'] ?>"><?= $u['status'] ?></span></td>
<td style="font-size:.78rem;color:var(--text-3)"><?= fmt_date($u['created_at']) ?></td>
<td style="font-size:.78rem;color:var(--text-3)"><?= $u['last_login'] ? time_ago($u['last_login']) : '—' ?></td>
<td style="white-space:nowrap">
<form method="POST" style="display:inline"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
<?php if($u['status']==='pending'): ?>
<button name="action" value="activate" class="act-btn success">✅ Approve</button>
<?php elseif($u['status']==='active'): ?>
<button name="action" value="suspend" class="act-btn danger" onclick="return confirm('Suspend this user?')">🚫</button>
<?php elseif($u['status']==='suspended'): ?>
<button name="action" value="activate" class="act-btn success">✅ Reactivate</button>
<?php endif; ?>
<button name="action" value="reset_password" class="act-btn" onclick="return confirm('Reset password?')">🔑</button>
<button name="action" value="delete" class="act-btn danger" onclick="return confirm('Delete this user?')">🗑️</button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php if(empty($users)): ?><tr><td colspan="8" class="empty-state"><div class="es-icon">🔍</div><p>No users found</p></td></tr><?php endif; ?>
</tbody></table>
</div>
</div>

<?php admin_footer(); ?>
