<?php
/** Admin — App Settings */
require_once __DIR__ . '/layout.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate()) {
    $settings = [
        'app_name' => trim($_POST['app_name'] ?? ''),
        'app_tagline' => trim($_POST['app_tagline'] ?? ''),
        'theme_color' => trim($_POST['theme_color'] ?? '#2563eb'),
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'registration_open' => isset($_POST['registration_open']) ? '1' : '0',
        'require_approval' => '1',
        'session_timeout_minutes' => max(5, (int)($_POST['session_timeout_minutes'] ?? 120)),
        'max_login_attempts' => max(3, (int)($_POST['max_login_attempts'] ?? 5)),
        'lockout_minutes' => max(5, (int)($_POST['lockout_minutes'] ?? 15)),
    ];
    foreach ($settings as $k => $v) set_setting($k, (string)$v);
    log_activity('admin_settings_update', 'Updated app settings');
    flash('success', 'Settings saved!');
    redirect(APP_URL . '/admin/settings.php');
}

admin_header('Settings', '⚙️', 'settings');
?>

<form method="POST" class="admin-form" style="max-width:600px"><?= csrf_field() ?>

<div class="data-card" style="padding:20px;margin-bottom:20px">
<h3 style="font-size:.88rem;font-weight:800;color:var(--theme);margin-bottom:14px">🏷️ Branding</h3>
<div class="form-group"><label>App Name</label>
<input type="text" name="app_name" value="<?= e(get_setting('app_name','ED VentGuide Pro')) ?>"></div>
<div class="form-group"><label>Tagline</label>
<input type="text" name="app_tagline" value="<?= e(get_setting('app_tagline','Evidence-Based Emergency Ventilation Reference')) ?>"></div>
<div class="form-group"><label>Theme Color</label>
<input type="color" name="theme_color" value="<?= e(get_setting('theme_color','#2563eb')) ?>" style="height:42px;padding:4px"></div>
</div>

<div class="data-card" style="padding:20px;margin-bottom:20px">
<h3 style="font-size:.88rem;font-weight:800;color:var(--theme);margin-bottom:14px">🔐 Access Control</h3>
<div class="toggle-wrap" style="margin-bottom:14px">
<label class="toggle"><input type="checkbox" name="maintenance_mode" <?= get_setting('maintenance_mode','0')==='1'?'checked':'' ?>><span class="toggle-slider"></span></label>
<span class="toggle-label">🔧 Maintenance Mode <span style="font-size:.75rem;color:var(--text-3)">(blocks all non-admin access)</span></span>
</div>
<div class="toggle-wrap" style="margin-bottom:14px">
<label class="toggle"><input type="checkbox" name="registration_open" <?= get_setting('registration_open','1')==='1'?'checked':'' ?>><span class="toggle-slider"></span></label>
<span class="toggle-label">📝 Registration Open</span>
</div>
<div class="toggle-wrap" style="margin-bottom:14px">
<label class="toggle"><input type="checkbox" name="require_approval" checked disabled><span class="toggle-slider"></span></label>
<span class="toggle-label">✅ Require Admin Approval <span style="font-size:.75rem;color:var(--text-3)">(always on: new users stay pending)</span></span>
</div>
</div>

<div class="data-card" style="padding:20px;margin-bottom:20px">
<h3 style="font-size:.88rem;font-weight:800;color:var(--theme);margin-bottom:14px">🛡️ Security</h3>
<div class="form-group"><label>Session Timeout (minutes)</label>
<input type="number" name="session_timeout_minutes" value="<?= e(get_setting('session_timeout_minutes','120')) ?>" min="5" max="1440"></div>
<div class="form-group"><label>Max Login Attempts</label>
<input type="number" name="max_login_attempts" value="<?= e(get_setting('max_login_attempts','5')) ?>" min="3" max="20"></div>
<div class="form-group"><label>Lockout Duration (minutes)</label>
<input type="number" name="lockout_minutes" value="<?= e(get_setting('lockout_minutes','15')) ?>" min="5" max="60"></div>
</div>

<button type="submit" class="btn btn-primary" style="max-width:240px">💾 Save Settings</button>
</form>

<?php admin_footer(); ?>
