<?php
/** Admin — User Management */
require_once __DIR__ . '/layout.php';
$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate()) {
    $action = $_POST['action'] ?? '';
    $uid = (int)($_POST['user_id'] ?? 0);
    $adminUser = session_user();
    $allowedRoles = ['user', 'subscriber', 'admin'];
    $allowedStatuses = ['pending', 'active', 'suspended'];

    if ($action === 'create_user') {
        $name = trim($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $role = $_POST['role'] ?? 'user';
        $status = $_POST['status'] ?? 'active';
        $emailVerified = isset($_POST['email_verified']) ? 1 : 0;
        $password = $_POST['password'] ?? '';
        $errors = [];

        if ($name === '' || strlen($name) > 100) $errors[] = 'Name must be 1-100 characters.';
        if (!valid_email($email) || strlen($email) > 255) $errors[] = 'Enter a valid email address.';
        if (!in_array($role, $allowedRoles, true)) $errors[] = 'Invalid role.';
        if (!in_array($status, $allowedStatuses, true)) $errors[] = 'Invalid status.';
        $pwdErrors = validate_password($password);
        if ($pwdErrors) $errors[] = 'Password: ' . implode(', ', $pwdErrors);

        if ($email !== '') {
            $dupeStmt = $db->prepare('SELECT id, status FROM users WHERE email=? LIMIT 1');
            $dupeStmt->execute([$email]);
            $existing = $dupeStmt->fetch();
            if ($existing) {
                $errors[] = $existing['status'] === 'deleted'
                    ? 'A deleted account already uses this email. Restore or permanently purge it before reusing the address.'
                    : 'An account with this email already exists.';
            }
        }

        if ($errors) {
            flash('danger', implode(' ', $errors));
            redirect(app_url('/admin/users?create=1' . (isset($_GET['filter']) ? '&filter=' . urlencode($_GET['filter']) : '')));
        }

        $hash = password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT, defined('PASSWORD_ARGON2ID') ? [] : ['cost' => BCRYPT_COST]);
        $stmt = $db->prepare('INSERT INTO users (name, email, password_hash, role, status, email_verified) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$name, $email, $hash, $role, $status, $emailVerified]);
        $newUserId = (int)$db->lastInsertId();

        log_activity('admin_create_user', "Created user ID: {$newUserId} ({$email})", $newUserId);
        flash('success', 'User created successfully.');
        redirect(app_url('/admin/users?edit=' . $newUserId));
    }

    // Prevent self-modification for destructive actions
    if ($uid > 0 && $uid === (int)$adminUser['id'] && in_array($action, ['suspend', 'delete', 'make_user', 'make_subscriber'], true)) {
        flash('danger', 'You cannot modify your own account this way.');
        redirect(app_url('/admin/users' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : '')));
    }

    if ($uid > 0) {
        switch ($action) {
            case 'update_user':
                $name = trim($_POST['name'] ?? '');
                $email = strtolower(trim($_POST['email'] ?? ''));
                $role = $_POST['role'] ?? 'user';
                $status = $_POST['status'] ?? 'pending';
                $emailVerified = isset($_POST['email_verified']) ? 1 : 0;
                $newPassword = $_POST['new_password'] ?? '';
                $errors = [];

                $targetStmt = $db->prepare("SELECT id, name, email, role, status, email_verified FROM users WHERE id=? AND status != 'deleted' LIMIT 1");
                $targetStmt->execute([$uid]);
                $target = $targetStmt->fetch();

                if (!$target) $errors[] = 'User not found.';
                if ($name === '' || strlen($name) > 100) $errors[] = 'Name must be 1–100 characters.';
                if (!valid_email($email) || strlen($email) > 255) $errors[] = 'Enter a valid email address.';
                if (!in_array($role, $allowedRoles, true)) $errors[] = 'Invalid role.';
                if (!in_array($status, $allowedStatuses, true)) $errors[] = 'Invalid status.';

                if ($uid === (int)$adminUser['id']) {
                    $role = $target['role'] ?? $adminUser['role'];
                    $status = $target['status'] ?? $adminUser['status'];
                }

                if ($email !== '') {
                    $dupeStmt = $db->prepare('SELECT id FROM users WHERE email=? AND id<>? LIMIT 1');
                    $dupeStmt->execute([$email, $uid]);
                    if ($dupeStmt->fetch()) $errors[] = 'Another user already uses this email.';
                }

                if ($newPassword !== '') {
                    $pwdErrors = validate_password($newPassword);
                    if ($pwdErrors) $errors[] = 'Password: ' . implode(', ', $pwdErrors);
                }

                if ($errors) {
                    flash('danger', implode(' ', $errors));
                    redirect(app_url('/admin/users?edit=' . $uid . (isset($_GET['filter']) ? '&filter=' . urlencode($_GET['filter']) : '')));
                }

                $db->beginTransaction();
                try {
                    $authBump = '';
                    if ($role !== $target['role'] || $status !== $target['status']) {
                        $authBump = ', auth_version = auth_version + 1';
                    }
                    $params = [$name, $email, $role, $status, $emailVerified, $uid];
                    $sql = "UPDATE users SET name=?, email=?, role=?, status=?, email_verified=?{$authBump} WHERE id=?";
                    if ($newPassword !== '') {
                        $hash = password_hash($newPassword, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT, defined('PASSWORD_ARGON2ID') ? [] : ['cost' => BCRYPT_COST]);
                        $sql = 'UPDATE users SET name=?, email=?, role=?, status=?, email_verified=?, password_hash=?, auth_version = auth_version + 1 WHERE id=?';
                        $params = [$name, $email, $role, $status, $emailVerified, $hash, $uid];
                    }
                    $db->prepare($sql)->execute($params);
                    if ($newPassword !== '' || $email !== $target['email']) {
                        $db->prepare('DELETE FROM password_resets WHERE email IN (?, ?)')->execute([$target['email'], $email]);
                    }
                    if ($email !== $target['email'] || $emailVerified === 1 || (int)$target['email_verified'] !== $emailVerified) {
                        $db->prepare('DELETE FROM email_verifications WHERE user_id = ? OR email IN (?, ?)')->execute([$uid, $target['email'], $email]);
                    }
                    if ($status !== 'active') {
                        $db->prepare("UPDATE subscriptions SET status='cancelled' WHERE user_id=? AND status='active'")->execute([$uid]);
                    }
                    $db->commit();
                } catch (Throwable $e) {
                    $db->rollBack();
                    throw $e;
                }

                if ($uid === (int)$adminUser['id']) {
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = $role;
                    $_SESSION['user_status'] = $status;
                }

                log_activity('admin_update_user', "Updated user ID: {$uid}");
                flash('success', 'User details updated.');
                break;
            case 'activate':
                $db->prepare("UPDATE users SET status='active', auth_version = auth_version + 1 WHERE id=?")->execute([$uid]);
                log_activity('admin_activate_user', "Activated user ID: {$uid}");
                flash('success', 'User activated.');
                break;
            case 'suspend':
                $db->prepare("UPDATE users SET status='suspended', auth_version = auth_version + 1 WHERE id=?")->execute([$uid]);
                // Also cancel any active subscriptions
                $db->prepare("UPDATE subscriptions SET status='cancelled' WHERE user_id=? AND status='active'")->execute([$uid]);
                log_activity('admin_suspend_user', "Suspended user ID: {$uid} (subscriptions cancelled)");
                flash('warning', 'User suspended. Active subscriptions have been cancelled.');
                break;
            case 'deactivate':
                // Soft hold — sets pending, preserves subscription for later
                $db->prepare("UPDATE users SET status='pending', auth_version = auth_version + 1 WHERE id=?")->execute([$uid]);
                log_activity('admin_deactivate_user', "Deactivated (held) user ID: {$uid}");
                flash('warning', 'User account placed on hold (pending). They cannot log in until reactivated.');
                break;
            case 'delete':
                $db->prepare("UPDATE users SET status='deleted', auth_version = auth_version + 1 WHERE id=?")->execute([$uid]);
                $db->prepare("UPDATE subscriptions SET status='cancelled' WHERE user_id=? AND status IN ('active','pending')")->execute([$uid]);
                log_activity('admin_delete_user', "Soft-deleted user ID: {$uid}");
                flash('danger', 'User deleted and subscriptions cancelled.');
                break;
            case 'make_admin':
                $db->prepare("UPDATE users SET role='admin', auth_version = auth_version + 1 WHERE id=?")->execute([$uid]);
                log_activity('admin_promote', "Promoted user ID {$uid} to admin");
                flash('success', 'User promoted to admin.');
                break;
            case 'make_subscriber':
                $db->prepare("UPDATE users SET role='subscriber', auth_version = auth_version + 1 WHERE id=?")->execute([$uid]);
                log_activity('admin_set_role', "Set user ID {$uid} to subscriber");
                flash('success', 'Role updated to subscriber.');
                break;
            case 'make_user':
                $db->prepare("UPDATE users SET role='user', auth_version = auth_version + 1 WHERE id=?")->execute([$uid]);
                log_activity('admin_set_role', "Set user ID {$uid} to user");
                flash('success', 'Role updated to user.');
                break;
            case 'reset_password':
                $newPwd = generate_temporary_password();
                $hash = password_hash($newPwd, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT, defined('PASSWORD_ARGON2ID') ? [] : ['cost' => BCRYPT_COST]);
                $db->prepare("UPDATE users SET password_hash=?, auth_version = auth_version + 1 WHERE id=?")->execute([$hash, $uid]);
                $db->prepare('DELETE FROM password_resets WHERE email = (SELECT email FROM users WHERE id = ? LIMIT 1)')->execute([$uid]);
                log_activity('admin_reset_password', "Reset password for user ID: {$uid}");
                flash('success', "Password reset. Temporary password: {$newPwd}. Share it securely.");
                break;
        }
        redirect(app_url('/admin/users' . (isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : '')));
    }
}

// Fetch users
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['q'] ?? '');
$editId = (int)($_GET['edit'] ?? 0);
$showCreate = isset($_GET['create']);
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

$editUser = null;
if ($editId > 0) {
    $editStmt = $db->prepare("SELECT id, name, email, role, status, email_verified, created_at, last_login FROM users WHERE id=? AND status != 'deleted' LIMIT 1");
    $editStmt->execute([$editId]);
    $editUser = $editStmt->fetch() ?: null;
    if (!$editUser) {
        flash('danger', 'User not found.');
        redirect(app_url('/admin/users'));
    }
}

// Get subscription info for each user
$subLookup = [];
$subRows = $db->query("SELECT s.user_id, s.status, s.expires_at, p.name as plan_name FROM subscriptions s JOIN plans p ON s.plan_id=p.id WHERE s.status IN ('active','pending') ORDER BY s.created_at DESC")->fetchAll();
foreach ($subRows as $sr) {
    if (!isset($subLookup[$sr['user_id']])) {
        $subLookup[$sr['user_id']] = $sr;
    }
}

admin_header('User Management', '👥', 'users');
?>

<div class="search-bar">
<form method="GET" style="display:flex;gap:8px;flex:1">
<input type="text" name="q" placeholder="🔍 Search users..." value="<?= e($search) ?>">
<input type="hidden" name="filter" value="<?= e($filter) ?>">
<button type="submit" class="topbar-btn">Search</button>
</form>
<a href="<?= app_url('/admin/users?create=1' . ($filter !== 'all' ? '&filter=' . urlencode($filter) : '') . ($search ? '&q=' . urlencode($search) : '')) ?>" class="topbar-btn" style="background:var(--theme);color:#fff;border-color:var(--theme);text-decoration:none">➕ Add User</a>
</div>

<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
<?php foreach(['all'=>'All','pending'=>'⏳ Pending','active'=>'✅ Active','suspended'=>'🚫 Suspended'] as $k=>$v): ?>
<a href="?filter=<?=$k?><?=$search?'&q='.urlencode($search):''?>" class="topbar-btn" style="<?=$filter===$k?'background:var(--theme);color:#fff;border-color:var(--theme)':''?>"><?=$v?></a>
<?php endforeach; ?>
</div>

<?php if ($showCreate): ?>
<div class="data-card">
<div class="dc-header">
  <div class="dc-title">➕ Add User</div>
  <a href="<?= app_url('/admin/users' . ($filter !== 'all' ? '?filter=' . urlencode($filter) : '')) ?>" class="topbar-btn">Close</a>
</div>
<form method="POST" class="admin-form" style="max-width:none;padding:18px;">
<?= csrf_field() ?>
<input type="hidden" name="action" value="create_user">
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">
  <div class="form-group">
    <label for="create_name">Name</label>
    <input type="text" id="create_name" name="name" maxlength="100" required autofocus>
  </div>
  <div class="form-group">
    <label for="create_email">Email</label>
    <input type="email" id="create_email" name="email" maxlength="255" required>
  </div>
  <div class="form-group">
    <label for="create_password">Password</label>
    <input type="password" id="create_password" name="password" minlength="8" autocomplete="new-password" required>
  </div>
  <div class="form-group">
    <label for="create_role">Role</label>
    <select id="create_role" name="role">
      <option value="user">User</option>
      <option value="subscriber">Subscriber</option>
      <option value="admin">Admin</option>
    </select>
  </div>
  <div class="form-group">
    <label for="create_status">Status</label>
    <select id="create_status" name="status">
      <option value="active">Active</option>
      <option value="pending">Pending</option>
      <option value="suspended">Suspended</option>
    </select>
  </div>
</div>
<label class="toggle-wrap" style="margin:0 0 16px;">
  <span class="toggle"><input type="checkbox" name="email_verified" value="1" checked><span class="toggle-slider"></span></span>
  <span class="toggle-label">Email verified</span>
</label>
<p style="font-size:.78rem;color:var(--text-3);font-weight:700;margin:-2px 0 14px;">
  Password must be at least 8 characters and include one uppercase letter, one number, and one special character.
</p>
<div style="display:flex;gap:10px;flex-wrap:wrap;">
  <button type="submit" class="btn btn-primary" style="max-width:220px;">➕ Create User</button>
  <a href="<?= app_url('/admin/users' . ($filter !== 'all' ? '?filter=' . urlencode($filter) : '')) ?>" class="btn btn-secondary" style="max-width:160px;">Cancel</a>
</div>
</form>
</div>
<?php endif; ?>

<?php if ($editUser):
    $editingSelf = (int)$editUser['id'] === (int)session_user()['id'];
?>
<div class="data-card">
<div class="dc-header">
  <div class="dc-title">✏️ Edit User #<?= (int)$editUser['id'] ?></div>
  <a href="<?= app_url('/admin/users' . ($filter !== 'all' ? '?filter=' . urlencode($filter) : '')) ?>" class="topbar-btn">Close</a>
</div>
<form method="POST" class="admin-form" style="max-width:none;padding:18px;">
<?= csrf_field() ?>
<input type="hidden" name="action" value="update_user">
<input type="hidden" name="user_id" value="<?= (int)$editUser['id'] ?>">
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;">
  <div class="form-group">
    <label for="edit_name">Name</label>
    <input type="text" id="edit_name" name="name" value="<?= e($editUser['name']) ?>" maxlength="100" required>
  </div>
  <div class="form-group">
    <label for="edit_email">Email</label>
    <input type="email" id="edit_email" name="email" value="<?= e($editUser['email']) ?>" maxlength="255" required>
  </div>
  <div class="form-group">
    <label for="edit_role">Role</label>
    <select id="edit_role" name="role" <?= $editingSelf ? 'disabled' : '' ?>>
      <?php foreach(['user'=>'User','subscriber'=>'Subscriber','admin'=>'Admin'] as $rv=>$rl): ?>
      <option value="<?= e($rv) ?>" <?= $editUser['role']===$rv?'selected':'' ?>><?= e($rl) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($editingSelf): ?><input type="hidden" name="role" value="<?= e($editUser['role']) ?>"><?php endif; ?>
  </div>
  <div class="form-group">
    <label for="edit_status">Status</label>
    <select id="edit_status" name="status" <?= $editingSelf ? 'disabled' : '' ?>>
      <?php foreach(['pending'=>'Pending','active'=>'Active','suspended'=>'Suspended'] as $sv=>$sl): ?>
      <option value="<?= e($sv) ?>" <?= $editUser['status']===$sv?'selected':'' ?>><?= e($sl) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if ($editingSelf): ?><input type="hidden" name="status" value="<?= e($editUser['status']) ?>"><?php endif; ?>
  </div>
</div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;align-items:end;">
  <label class="toggle-wrap" style="margin:0 0 16px;">
    <span class="toggle"><input type="checkbox" name="email_verified" value="1" <?= (int)$editUser['email_verified'] === 1 ? 'checked' : '' ?>><span class="toggle-slider"></span></span>
    <span class="toggle-label">Email verified</span>
  </label>
  <div class="form-group">
    <label for="edit_password">New password (optional)</label>
    <input type="password" id="edit_password" name="new_password" placeholder="Leave blank to keep current password" minlength="8" autocomplete="new-password">
  </div>
</div>
<p style="font-size:.78rem;color:var(--text-3);font-weight:700;margin:-2px 0 14px;">
  <?= $editingSelf ? 'For safety, you cannot change your own role or status here.' : 'Changing status away from active cancels active subscriptions.' ?>
</p>
<div style="display:flex;gap:10px;flex-wrap:wrap;">
  <button type="submit" class="btn btn-primary" style="max-width:220px;">💾 Save Changes</button>
  <a href="<?= app_url('/admin/users' . ($filter !== 'all' ? '?filter=' . urlencode($filter) : '')) ?>" class="btn btn-secondary" style="max-width:160px;">Cancel</a>
</div>
</form>
</div>
<?php endif; ?>

<div class="data-card">
<div class="dc-header"><div class="dc-title">👥 Users (<?= count($users) ?>)</div></div>
<div style="overflow-x:auto">
<table class="data-table">
<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Subscription</th><th>Joined</th><th>Last Login</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($users as $u):
    $isSelf = (int)$u['id'] === (int)session_user()['id'];
    $sub = $subLookup[$u['id']] ?? null;
?>
<tr>
<td style="font-family:'Space Mono',monospace;font-size:.78rem;color:var(--text-3)">#<?= $u['id'] ?></td>
<td><strong><?= e($u['name']) ?></strong><?= $isSelf ? ' <span style="font-size:.7rem;color:var(--theme);font-weight:800">(You)</span>' : '' ?></td>
<td style="font-size:.8rem;color:var(--text-2)">
  <?= e($u['email']) ?>
  <br><span style="font-size:.7rem;color:<?= (int)$u['email_verified'] === 1 ? 'var(--success)' : 'var(--warning)' ?>;font-weight:800"><?= (int)$u['email_verified'] === 1 ? 'Email verified' : 'Email unverified' ?></span>
</td>
<td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
<td><span class="badge badge-<?= $u['status'] ?>"><?= $u['status'] ?></span></td>
<td style="font-size:.78rem">
<?php if ($sub): ?>
  <span style="font-weight:700;color:var(--text)"><?= e($sub['plan_name']) ?></span>
  <br><span style="font-size:.7rem;color:var(--text-3)">Exp: <?= fmt_date($sub['expires_at']) ?></span>
<?php else: ?>
  <span style="color:var(--text-3)">—</span>
<?php endif; ?>
</td>
<td style="font-size:.78rem;color:var(--text-3)"><?= fmt_date($u['created_at']) ?></td>
<td style="font-size:.78rem;color:var(--text-3)"><?= $u['last_login'] ? time_ago($u['last_login']) : '—' ?></td>
<td style="white-space:nowrap">
<form method="POST" style="display:inline"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= $u['id'] ?>">
<a href="?edit=<?= $u['id'] ?>&filter=<?= urlencode($filter) ?><?= $search?'&q='.urlencode($search):''?>" class="act-btn" style="text-decoration:none;display:inline-block" title="Edit user">✏️ Edit</a>

<?php if(!$isSelf): ?>
<?php // ── Status Actions ──
if ($u['status'] === 'pending'): ?>
<button name="action" value="activate" class="act-btn success" title="Approve user">✅ Approve</button>
<?php elseif ($u['status'] === 'active'): ?>
<button name="action" value="deactivate" class="act-btn" onclick="return confirm('Place this user on hold? They will not be able to log in.')" title="Hold account (set to pending)">⏸️</button>
<button name="action" value="suspend" class="act-btn danger" onclick="return confirm('Suspend this user? Their subscriptions will be cancelled.')" title="Suspend user & cancel subscriptions">🚫</button>
<?php elseif ($u['status'] === 'suspended'): ?>
<button name="action" value="activate" class="act-btn success" title="Reactivate user">✅ Reactivate</button>
<?php endif; ?>

<?php // ── Utility Actions ── ?>
<button name="action" value="reset_password" class="act-btn" onclick="return confirm('Reset this user\'s password?')" title="Reset password">🔑</button>
<button name="action" value="delete" class="act-btn danger" onclick="return confirm('Delete this user? This action soft-deletes the account and cancels subscriptions.')" title="Delete user">🗑️</button>

</form>
<?php else: ?>
</form>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
<?php if(empty($users)): ?><tr><td colspan="9" class="empty-state"><div class="es-icon">🔍</div><p>No users found</p></td></tr><?php endif; ?>
</tbody></table>
</div>
</div>

<?php admin_footer(); ?>
