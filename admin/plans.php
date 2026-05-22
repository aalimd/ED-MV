<?php
/** Admin — Plan Management (CRUD) */
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../includes/features.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_validate()) {
    $action = $_POST['action'] ?? '';
    $planId = (int)($_POST['plan_id'] ?? 0);

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $slug = preg_replace('/[^a-z0-9-]/', '', strtolower(str_replace(' ', '-', $name)));
        $desc = trim($_POST['description'] ?? '');
        $features = trim($_POST['features'] ?? '');
        $days = max(1, (int)($_POST['duration_days'] ?? 30));
        $price = max(0, floatval($_POST['price'] ?? 0));
        $currency = strtoupper(trim($_POST['currency'] ?? 'SAR'));
        $badge = trim($_POST['badge'] ?? '') ?: null;
        $color = trim($_POST['color'] ?? '#2563eb');
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;
        $sort = (int)($_POST['sort_order'] ?? 0);

        if ($name) {
            $originalSlug = $slug;
            $counter = 1;
            while (true) {
                $stmt = $db->prepare("SELECT COUNT(*) FROM plans WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetchColumn() == 0) break;
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            try {
                $db->beginTransaction();
                $db->prepare("INSERT INTO plans (name,slug,description,features,duration_days,price,currency,badge,is_featured,color,is_active,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                   ->execute([$name, $slug, $desc, $features, $days, $price, $currency, $badge, $featured, $color, $active, $sort]);
                // Save feature assignments
                $newPlanId = (int)$db->lastInsertId();
                $featureIds = array_map('intval', $_POST['feature_ids'] ?? []);
                foreach ($featureIds as $fid) {
                    $db->prepare("INSERT IGNORE INTO plan_features (plan_id, feature_id) VALUES (?, ?)")->execute([$newPlanId, $fid]);
                }
                $db->commit();
                invalidate_feature_cache();
                log_activity('admin_create_plan', "Created plan: {$name}");
                flash('success', "Plan \"{$name}\" created!");
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                flash('danger', "Failed to create plan.");
            }
        }
    } elseif ($action === 'update' && $planId > 0) {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $features = trim($_POST['features'] ?? '');
        $days = max(1, (int)($_POST['duration_days'] ?? 30));
        $price = max(0, floatval($_POST['price'] ?? 0));
        $currency = strtoupper(trim($_POST['currency'] ?? 'SAR'));
        $badge = trim($_POST['badge'] ?? '') ?: null;
        $color = trim($_POST['color'] ?? '#2563eb');
        $featured = isset($_POST['is_featured']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;
        $sort = (int)($_POST['sort_order'] ?? 0);

        if ($name) {
            try {
                $db->beginTransaction();
                $db->prepare("UPDATE plans SET name=?,description=?,features=?,duration_days=?,price=?,currency=?,badge=?,is_featured=?,color=?,is_active=?,sort_order=? WHERE id=?")
                   ->execute([$name, $desc, $features, $days, $price, $currency, $badge, $featured, $color, $active, $sort, $planId]);
                // Update feature assignments
                $featureIds = array_map('intval', $_POST['feature_ids'] ?? []);
                $db->prepare("DELETE FROM plan_features WHERE plan_id = ?")->execute([$planId]);
                foreach ($featureIds as $fid) {
                    $db->prepare("INSERT INTO plan_features (plan_id, feature_id) VALUES (?, ?)")->execute([$planId, $fid]);
                }
                $db->commit();
                invalidate_feature_cache();
                log_activity('admin_update_plan', "Updated plan ID: {$planId}");
                flash('success', "Plan updated!");
            } catch (Exception $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                flash('danger', "Failed to update plan.");
            }
        }
    } elseif ($action === 'delete' && $planId > 0) {
        // Check if any subscriptions use this plan
        $check = $db->prepare("SELECT COUNT(*) FROM subscriptions WHERE plan_id = ?");
        $check->execute([$planId]);
        if ((int)$check->fetchColumn() > 0) {
            flash('danger', 'Cannot delete a plan that has subscriptions. Deactivate it instead.');
        } else {
            $db->prepare("DELETE FROM plans WHERE id = ?")->execute([$planId]);
            log_activity('admin_delete_plan', "Deleted plan ID: {$planId}");
            flash('warning', 'Plan deleted.');
        }
    } elseif ($action === 'save_page_settings') {
        set_setting('sub_page_title', trim($_POST['sub_page_title'] ?? 'Choose Your Plan'));
        set_setting('sub_page_subtitle', trim($_POST['sub_page_subtitle'] ?? ''));
        set_setting('sub_page_footer', trim($_POST['sub_page_footer'] ?? ''));
        set_setting('sub_currency_symbol', strtoupper(trim($_POST['sub_currency_symbol'] ?? 'SAR')));
        log_activity('admin_update_sub_page', 'Updated subscription page settings');
        flash('success', 'Page settings saved!');
    }
    redirect(app_url('/admin/plans'));
}

$plans = $db->query("SELECT * FROM plans ORDER BY sort_order, id")->fetchAll();
$editing = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($plans as $p) { if ((int)$p['id'] === $editId) { $editing = $p; break; } }
}

// Load all active features for the checkbox grid
$allFeatures = $db->query("SELECT * FROM features WHERE is_active = 1 ORDER BY sort_order")->fetchAll();
$editingFeatureIds = [];
if ($editing) {
    $stmt = $db->prepare("SELECT feature_id FROM plan_features WHERE plan_id = ?");
    $stmt->execute([$editing['id']]);
    $editingFeatureIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Build plan-to-features icon lookup for the table
$planFeatureLookup = [];
$pfRows = $db->query("SELECT pf.plan_id, f.icon FROM plan_features pf JOIN features f ON pf.feature_id = f.id WHERE f.is_active = 1 ORDER BY f.sort_order")->fetchAll();
foreach ($pfRows as $pf) {
    $planFeatureLookup[$pf['plan_id']][] = $pf['icon'];
}

admin_header('Plans & Pricing', '🏷️', 'plans');
?>

<!-- ── Page Settings Card ─────────────────────── -->
<div class="data-card" style="padding:20px;margin-bottom:20px">
<h3 style="font-size:.88rem;font-weight:800;color:var(--theme);margin-bottom:14px">📝 Subscription Page Content</h3>
<p style="font-size:.78rem;color:var(--text-3);margin-bottom:14px">These texts appear on the page your users see when they need to subscribe.</p>
<form method="POST" class="admin-form" style="max-width:none"><?= csrf_field() ?>
<input type="hidden" name="action" value="save_page_settings">
<div class="form-group">
  <label>Page Title</label>
  <input type="text" name="sub_page_title" value="<?= e(get_setting('sub_page_title','Choose Your Plan')) ?>">
</div>
<div class="form-group">
  <label>Subtitle / Description</label>
  <textarea name="sub_page_subtitle" rows="2"><?= e(get_setting('sub_page_subtitle','')) ?></textarea>
</div>
<div class="form-group">
  <label>Footer Note</label>
  <textarea name="sub_page_footer" rows="2"><?= e(get_setting('sub_page_footer','')) ?></textarea>
</div>
<div class="form-group">
  <label>Currency Code (shown on plans)</label>
  <input type="text" name="sub_currency_symbol" value="<?= e(get_setting('sub_currency_symbol','SAR')) ?>" maxlength="5" style="width:100px">
</div>
<button type="submit" class="btn btn-primary" style="max-width:240px">💾 Save Page Settings</button>
</form>
</div>

<!-- ── Create / Edit Plan Card ────────────────── -->
<div class="data-card" style="padding:20px;margin-bottom:20px">
<h3 style="font-size:.88rem;font-weight:800;color:var(--theme);margin-bottom:14px"><?= $editing ? '✏️ Edit Plan' : '➕ Create New Plan' ?></h3>
<form method="POST" class="admin-form" style="max-width:none"><?= csrf_field() ?>
<input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
<?php if($editing): ?><input type="hidden" name="plan_id" value="<?= $editing['id'] ?>"><?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px">
  <div class="form-group">
    <label>Plan Name *</label>
    <input type="text" name="name" value="<?= e($editing['name'] ?? '') ?>" required placeholder="e.g. Monthly">
  </div>
  <div class="form-group">
    <label>Price</label>
    <input type="number" name="price" value="<?= e($editing['price'] ?? '0') ?>" step="0.01" min="0">
  </div>
  <div class="form-group">
    <label>Currency</label>
    <input type="text" name="currency" value="<?= e($editing['currency'] ?? 'SAR') ?>" maxlength="5">
  </div>
  <div class="form-group">
    <label>Duration (days)</label>
    <input type="number" name="duration_days" value="<?= e($editing['duration_days'] ?? '30') ?>" min="1">
  </div>
  <div class="form-group">
    <label>Badge Text <span style="font-size:.72rem;color:var(--text-3)">(optional)</span></label>
    <input type="text" name="badge" value="<?= e($editing['badge'] ?? '') ?>" placeholder="Leave blank for none">
  </div>
  <div class="form-group">
    <label>Card Color</label>
    <input type="color" name="color" value="<?= e($editing['color'] ?? '#2563eb') ?>" style="height:44px;padding:4px;cursor:pointer">
  </div>
  <div class="form-group">
    <label>Sort Order</label>
    <input type="number" name="sort_order" value="<?= e($editing['sort_order'] ?? '0') ?>">
  </div>
</div>

<div class="form-group">
  <label>Short Description</label>
  <input type="text" name="description" value="<?= e($editing['description'] ?? '') ?>" placeholder="e.g. Full access for 30 days">
</div>

<div class="form-group">
  <label>Features <span style="font-size:.72rem;color:var(--text-3)">(one per line)</span></label>
  <textarea name="features" rows="4" placeholder="Full ventilation reference&#10;All clinical scenarios&#10;PBW calculator"><?= e(str_replace('|', "\n", $editing['features'] ?? '')) ?></textarea>
</div>

<div style="display:flex;gap:20px;margin-bottom:16px;flex-wrap:wrap">
<label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="is_active" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--theme)"><span style="font-size:.82rem;font-weight:700">Active (visible to users)</span></label>
<label style="display:flex;align-items:center;gap:8px;cursor:pointer"><input type="checkbox" name="is_featured" <?= ($editing['is_featured'] ?? 0) ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--theme)"><span style="font-size:.82rem;font-weight:700">⭐ Featured (highlighted card)</span></label>
</div>

<?php if(!empty($allFeatures)): ?>
<div style="margin-bottom:16px">
<label style="font-size:.82rem;font-weight:800;margin-bottom:8px;display:block">🔓 Feature Access</label>
<p style="font-size:.75rem;color:var(--text-3);margin-bottom:10px">Check which features this plan grants access to.</p>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:6px">
<?php foreach($allFeatures as $f):
    $checked = (!$editing || in_array($f['id'], $editingFeatureIds)) ? 'checked' : '';
?>
<label style="display:flex;align-items:center;gap:8px;cursor:pointer;padding:6px 8px;border-radius:8px;border:1px solid var(--border);background:var(--surface-2)">
  <input type="checkbox" name="feature_ids[]" value="<?= $f['id'] ?>" <?= $checked ?> style="width:16px;height:16px;accent-color:var(--theme)">
  <span style="font-size:.8rem;font-weight:700"><?= e($f['icon']) ?> <?= e($f['name']) ?></span>
</label>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<div style="display:flex;gap:10px;flex-wrap:wrap">
  <button type="submit" class="btn btn-primary" style="max-width:240px"><?= $editing ? '💾 Update Plan' : '➕ Create Plan' ?></button>
  <?php if($editing): ?><a href="<?= app_url('/admin/plans') ?>" class="btn btn-secondary" style="max-width:140px">Cancel</a><?php endif; ?>
</div>
</form>
</div>

<!-- ── Existing Plans Table ───────────────────── -->
<div class="data-card">
<div class="dc-header"><div class="dc-title">🏷️ Plans (<?= count($plans) ?>)</div></div>
<div style="overflow-x:auto">
<table class="data-table">
<thead><tr><th>Order</th><th>Name</th><th>Price</th><th>Duration</th><th>Features</th><th>Badge</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($plans as $p): ?>
<tr>
<td style="font-family:'Space Mono',monospace;font-size:.78rem;color:var(--text-3)"><?= $p['sort_order'] ?></td>
<td><strong style="color:<?= e($p['color']) ?>"><?= e($p['name']) ?></strong><br><span style="font-size:.75rem;color:var(--text-3)"><?= e($p['description'] ?? '') ?></span></td>
<td style="font-family:'Space Mono',monospace;font-weight:800"><?= e($p['currency']) ?> <?= number_format($p['price'],2) ?></td>
<td><?= $p['duration_days'] ?> days</td>
<td style="font-size:1rem;letter-spacing:2px"><?= implode('', $planFeatureLookup[$p['id']] ?? []) ?: '—' ?></td>
<td><?= $p['badge'] ? '<span class="badge badge-subscriber">' . e($p['badge']) . '</span>' : '—' ?></td>
<td><span class="badge badge-<?= $p['is_active']?'active':'suspended' ?>"><?= $p['is_active']?'Active':'Hidden' ?></span></td>
<td style="white-space:nowrap">
<a href="?edit=<?= $p['id'] ?>" class="act-btn">✏️ Edit</a>
<form method="POST" style="display:inline"><?= csrf_field() ?>
<input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
<button name="action" value="delete" class="act-btn danger" onclick="return confirm('Delete this plan?')">🗑️</button>
</form>
</td>
</tr>
<?php endforeach; ?>
<?php if(empty($plans)): ?><tr><td colspan="8" class="empty-state"><div class="es-icon">🏷️</div><p>No plans yet</p></td></tr><?php endif; ?>
</tbody></table>
</div>
</div>

<?php admin_footer(); ?>
