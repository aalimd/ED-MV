<?php
/**
 * ED VentGuide Pro — Vent Coach
 * ──────────────────────────────
 * Real-time clinical safety coaching from current vent settings + ABG.
 * Feature-gated by the "vent_coach" feature key.
 *
 * All clinical guidance comes from includes/vent_coach.php (pure logic).
 * This file handles HTTP I/O, persistence, and rendering only.
 *
 * Visual design intentionally mirrors app/ventguide_raw.php so users
 * experience a single, coherent app:
 *   • Same design tokens, fonts, components and dark-mode toggle.
 *   • Same scroll model (fixed body + scrollable main) so the PWA
 *     zoom-lock script lets the page scroll on touch devices.
 *   • Same bottom navigation, with Coach as the 6th tab; the other
 *     five tabs link back to /app/ventguide#hash for instant return.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/features.php';
require_once __DIR__ . '/../includes/vent_coach.php';
require_once __DIR__ . '/../includes/pwa.php';

require_login();
require_subscription();

// ─── Feature gate ─────────────────────────────────────────────────────
if (!has_feature('vent_coach')) {
    flash('warning', 'Vent Coach is not included in your current plan.');
    redirect(app_url('/subscribe'));
}

$user   = session_user();
$db     = getDB();
$userId = (int)$user['id'];

// ─── POST actions ─────────────────────────────────────────────────────
$activeCaseId    = null;
$activeInput     = null;
$activeReference = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate()) {
        flash('danger', 'Security token expired. Please try again.');
        redirect(app_url('/app/coach'));
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $caseId = (int)($_POST['case_id'] ?? 0);
        if ($caseId > 0) {
            $stmt = $db->prepare('DELETE FROM patient_cases WHERE id = ? AND user_id = ?');
            $stmt->execute([$caseId, $userId]);
            log_activity('coach_delete_case', "Deleted case #{$caseId}");
            flash('success', 'Case deleted.');
        }
        redirect(app_url('/app/coach'));
    }

    if ($action === 'save' || $action === 'analyze') {
        $rawInput = [
            'scenario'     => $_POST['scenario']     ?? 'healthy',
            'pbw_kg'       => $_POST['pbw_kg']       ?? null,
            'target_paco2' => $_POST['target_paco2'] ?? null,
            'vent' => [
                'vt_ml'    => $_POST['vt_ml']    ?? null,
                'rr'       => $_POST['rr']       ?? null,
                'peep'     => $_POST['peep']     ?? null,
                'pplat'    => $_POST['pplat']    ?? null,
                'fio2_pct' => $_POST['fio2_pct'] ?? null,
            ],
            'abg' => [
                'ph'    => $_POST['ph']    ?? null,
                'paco2' => $_POST['paco2'] ?? null,
                'pao2'  => $_POST['pao2']  ?? null,
                'hco3'  => $_POST['hco3']  ?? null,
                'spo2'  => $_POST['spo2']  ?? null,
            ],
        ];
        $activeInput     = vc_sanitize_input($rawInput);
        $activeReference = trim((string)($_POST['reference'] ?? ''));

        if ($action === 'save') {
            $ref      = $activeReference;
            if (mb_strlen($ref) > 80) $ref = mb_substr($ref, 0, 80);
            $caseId   = (int)($_POST['case_id'] ?? 0);

            $result   = vc_analyze($activeInput);
            $ventJson = json_encode($activeInput['vent'], JSON_UNESCAPED_UNICODE);
            $abgJson  = json_encode($activeInput['abg'],  JSON_UNESCAPED_UNICODE);
            $resJson  = json_encode($result,              JSON_UNESCAPED_UNICODE);

            if ($caseId > 0) {
                $owns = $db->prepare('SELECT id FROM patient_cases WHERE id = ? AND user_id = ?');
                $owns->execute([$caseId, $userId]);
                if ($owns->fetchColumn()) {
                    $stmt = $db->prepare(
                        'UPDATE patient_cases
                            SET reference = ?, scenario = ?, pbw_kg = ?,
                                vent_data_json = ?, abg_data_json = ?, result_json = ?, safety_level = ?
                          WHERE id = ? AND user_id = ?'
                    );
                    $stmt->execute([
                        $ref !== '' ? $ref : null,
                        $activeInput['scenario'],
                        $activeInput['pbw_kg'],
                        $ventJson, $abgJson, $resJson, $result['safety_level'],
                        $caseId, $userId,
                    ]);
                    $activeCaseId = $caseId;
                    log_activity('coach_update_case', "Updated case #{$caseId} ({$result['safety_level']})");
                    flash('success', 'Case updated.');
                } else {
                    flash('danger', 'Case not found.');
                }
            } else {
                $stmt = $db->prepare(
                    'INSERT INTO patient_cases
                       (user_id, reference, scenario, pbw_kg, vent_data_json, abg_data_json, result_json, safety_level)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $userId,
                    $ref !== '' ? $ref : null,
                    $activeInput['scenario'],
                    $activeInput['pbw_kg'],
                    $ventJson, $abgJson, $resJson, $result['safety_level'],
                ]);
                $activeCaseId = (int)$db->lastInsertId();
                log_activity('coach_save_case', "Saved case #{$activeCaseId} ({$result['safety_level']})");
                flash('success', 'Case saved.');
            }
            redirect(app_url('/app/coach?case=' . $activeCaseId));
        }
    }
}

// ─── GET: optionally load a saved case ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $loadId = (int)($_GET['case'] ?? 0);
    if ($loadId > 0) {
        $stmt = $db->prepare('SELECT * FROM patient_cases WHERE id = ? AND user_id = ?');
        $stmt->execute([$loadId, $userId]);
        $row = $stmt->fetch();
        if ($row) {
            $activeCaseId    = (int)$row['id'];
            $vent            = json_decode($row['vent_data_json'] ?? 'null', true) ?: [];
            $abg             = json_decode($row['abg_data_json']  ?? 'null', true) ?: [];
            $activeInput     = vc_sanitize_input([
                'scenario' => $row['scenario'],
                'pbw_kg'   => $row['pbw_kg'],
                'vent'     => $vent,
                'abg'      => $abg,
            ]);
            $activeReference = (string)($row['reference'] ?? '');
        }
    }
}

// ─── Compute analysis for rendering ───────────────────────────────────
$result    = $activeInput ? vc_analyze($activeInput) : null;
$scenarios = vc_scenarios();

// ─── Load user's saved cases for the sidebar ──────────────────────────
$casesStmt = $db->prepare(
    'SELECT id, reference, scenario, safety_level, updated_at
       FROM patient_cases
      WHERE user_id = ?
   ORDER BY updated_at DESC
      LIMIT 20'
);
$casesStmt->execute([$userId]);
$savedCases = $casesStmt->fetchAll();

// ─── View helpers ─────────────────────────────────────────────────────
function vc_field(?float $v): string {
    return $v === null ? '' : (string)(rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.'));
}
function vc_level_label(string $level): string {
    return match($level) {
        'red'    => 'Action required',
        'yellow' => 'Borderline — watch closely',
        'green'  => 'Within safety targets',
        default  => '—',
    };
}
function vc_level_emoji(string $level): string {
    return match($level) {
        'red'    => '🚨',
        'yellow' => '⚠️',
        'green'  => '✅',
        default  => 'ℹ️',
    };
}
function vc_box_class(string $level): string {
    return match($level) {
        'red'    => 'alert-box',
        'yellow' => 'clin-warn',
        'green'  => 'ok-box',
        'info'   => 'abg-interp',
        default  => 'abg-interp',
    };
}
?><!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
  <?= pwa_head_tags('Real-time ventilation safety coach — bedside titration support.') . "\n" ?>
  <?= pwa_zoom_lock_script() . "\n" ?>
  <title>🧠 Vent Coach · ED VentGuide Pro</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
  <?= toast_head_tag() ?>

  <style>
    /* ─── DESIGN TOKENS — kept in sync with app/ventguide_raw.php ───── */
    :root {
      --bg:#f1f5f9; --surface:#ffffff; --surface-2:#f8fafc;
      --text:#0f172a; --text-2:#475569; --text-3:#64748b;
      --border:#e2e8f0; --danger:#dc2626; --danger-bg:#fef2f2;
      --danger-border:#fecaca; --success:#16a34a; --warning:#d97706;
      --shadow-xs:0 1px 3px rgba(0,0,0,0.06);
      --shadow-sm:0 2px 8px rgba(0,0,0,0.07);
      --shadow-md:0 4px 16px rgba(0,0,0,0.09);
      --shadow-lg:0 8px 32px rgba(0,0,0,0.12);
      --shadow-xl:0 20px 60px rgba(0,0,0,0.16);
      --r-sm:10px; --r-md:16px; --r-lg:22px; --r-xl:28px; --r-full:9999px;
      --theme:#2563eb; --theme-rgb:37,99,235;
      --theme-light:rgba(37,99,235,0.09); --theme-mid:rgba(37,99,235,0.18);
      --focus-ring:0 0 0 3px rgba(37,99,235,0.3);
    }
    .dark {
      --bg:#0b0f1a; --surface:#141929; --surface-2:#1a2236;
      --text:#f0f4ff; --text-2:#8899bb; --text-3:#6f84a8;
      --border:#1e2d47; --danger:#f87171; --danger-bg:rgba(220,38,38,0.16);
      --danger-border:rgba(248,113,113,0.32); --success:#4ade80; --warning:#fbbf24;
      --shadow-xs:0 1px 3px rgba(0,0,0,0.3);
      --shadow-sm:0 2px 8px rgba(0,0,0,0.38);
      --shadow-md:0 4px 16px rgba(0,0,0,0.45);
      --shadow-lg:0 8px 32px rgba(0,0,0,0.5);
    }
    @media (prefers-reduced-motion:reduce) { *,*::before,*::after { animation-duration:.01ms!important; transition-duration:.01ms!important; } }

    /* ─── RESET & SCROLL MODEL (mirrors main app) ──────────────────── */
    *,*::before,*::after { box-sizing:border-box; margin:0; padding:0; -webkit-tap-highlight-color:transparent; }
    html,body {
      height:100%; width:100%;
      max-width:100vw;
      overflow:hidden;
      overscroll-behavior:none;
      -webkit-text-size-adjust:100%; text-size-adjust:100%;
    }
    body {
      font-family:'DM Sans',system-ui,-apple-system,sans-serif;
      background:var(--bg); color:var(--text);
      display:flex; flex-direction:column;
      transition:background .3s,color .3s;
      position:fixed; inset:0;
      max-width:100vw;
      overflow:hidden;
      touch-action:pan-y;
      -webkit-user-select:none; user-select:none;
      -webkit-font-smoothing:antialiased;
    }
    input, textarea, select, [contenteditable] { -webkit-user-select:auto; user-select:auto; }
    button { font-family:inherit; cursor:pointer; }
    a { color:var(--theme); text-decoration:none; }

    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:var(--border); border-radius:99px; }

    button:focus-visible,a:focus-visible,input:focus-visible,select:focus-visible {
      outline:none; box-shadow:var(--focus-ring); border-radius:var(--r-sm);
    }

    /* ─── HEADER (mirrors .header in main app) ──────────────────────── */
    .header {
      background:var(--theme); color:white;
      padding:max(env(safe-area-inset-top),14px) 18px 22px;
      border-bottom-left-radius:var(--r-xl); border-bottom-right-radius:var(--r-xl);
      box-shadow:var(--shadow-md); transition:background .4s ease;
      position:relative; z-index:10; overflow:hidden; flex-shrink:0;
    }
    .header::before { content:''; position:absolute; top:-55%; right:-8%; width:210px; height:210px; border-radius:50%; background:rgba(255,255,255,.08); pointer-events:none; }
    .header::after  { content:''; position:absolute; bottom:-30%; left:-4%; width:130px; height:130px; border-radius:50%; background:rgba(255,255,255,.05); pointer-events:none; }
    .header-inner { display:flex; justify-content:space-between; align-items:flex-start; position:relative; z-index:1; gap:12px; }
    .header-title { font-size:1.35rem; font-weight:800; color:white; display:flex; align-items:center; gap:9px; letter-spacing:-.02em; }
    .header-sub { font-size:.84rem; color:rgba(255,255,255,.84); font-weight:500; margin-top:3px; }
    .header-badge { background:rgba(255,255,255,.2); color:white; font-size:.7rem; font-weight:800; padding:3px 10px; border-radius:var(--r-full); letter-spacing:.05em; margin-top:8px; display:inline-block; border:1px solid rgba(255,255,255,.3); }
    .header-actions { display:flex; gap:7px; flex-wrap:wrap; }
    .hbtn {
      background:rgba(255,255,255,.18); border:1.5px solid rgba(255,255,255,.3);
      width:38px; height:38px; border-radius:50%; color:white;
      display:flex; align-items:center; justify-content:center;
      cursor:pointer; font-size:1rem; transition:all .2s ease;
    }
    .hbtn:hover { background:rgba(255,255,255,.28); }
    .hbtn:active { transform:scale(.88); }
    .hbtn svg { width:17px; height:17px; pointer-events:none; }

    /* ─── CONTENT (scrollable, matches .content of main app) ────────── */
    main { flex:1; min-height:0; width:100%; max-width:100vw; overflow:hidden; }
    .content {
      flex:1; min-height:0; width:100%; max-width:100vw;
      height:100%;
      overflow-y:auto; overflow-x:hidden;
      padding:16px max(14px,env(safe-area-inset-right)) calc(96px + env(safe-area-inset-bottom)) max(14px,env(safe-area-inset-left));
      scroll-behavior:smooth;
      overscroll-behavior-y:contain;
      overscroll-behavior-x:none;
      -webkit-overflow-scrolling:touch;
      touch-action:pan-y;
    }
    .content-inner { max-width:1180px; margin:0 auto; }

    /* ─── FLASH MESSAGES ────────────────────────────────────────────── */
    .flash {
      padding:12px 14px; border-radius:var(--r-md); margin-bottom:14px;
      font-weight:700; font-size:.86rem; border:1px solid;
    }
    .flash-success { background:rgba(22,163,74,0.08); border-color:rgba(22,163,74,0.3); color:var(--success); }
    .flash-danger  { background:var(--danger-bg); border-color:var(--danger-border); color:var(--danger); }
    .flash-warning { background:rgba(217,119,6,0.10); border-color:rgba(217,119,6,0.30); color:var(--warning); }

    /* ─── DISCLAIMER (matches clin-warn) ────────────────────────────── */
    .vc-disclaimer {
      background:rgba(217,119,6,.09); border:1.5px solid rgba(217,119,6,.25);
      border-left:4px solid var(--warning); border-radius:var(--r-sm);
      padding:13px 15px; margin:0 0 16px; font-size:.85rem;
      font-weight:600; color:#7c2d12; line-height:1.5;
    }
    .dark .vc-disclaimer { color:#fed7aa; background:rgba(217,119,6,.12); }
    .vc-disclaimer strong { color:var(--warning); }

    /* ─── GRID LAYOUT ───────────────────────────────────────────────── */
    .vc-grid { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1.05fr); gap:16px; }
    @media (max-width:920px) { .vc-grid { grid-template-columns:1fr; } }

    /* ─── SECTION HEADER (matches .sec-hdr) ─────────────────────────── */
    .sec-hdr {
      background:var(--surface); border-radius:var(--r-md); padding:15px 17px;
      margin-bottom:15px; box-shadow:var(--shadow-xs); border:1px solid var(--border);
    }
    .sec-hdr-title { font-size:.98rem; font-weight:800; color:var(--theme); display:flex; align-items:center; gap:8px; margin-bottom:4px; }
    .sec-hdr-sub { font-size:.84rem; color:var(--text-2); font-weight:500; line-height:1.5; }

    /* ─── INFO CARD (matches .info-card) ────────────────────────────── */
    .info-card {
      background:var(--surface); border-radius:var(--r-md); padding:18px;
      margin-bottom:13px; border:1px solid var(--border); box-shadow:var(--shadow-xs);
    }
    .info-card h3 { font-size:.95rem; font-weight:800; color:var(--theme); display:flex; align-items:center; gap:8px; margin-bottom:12px; }
    .info-card .sub { font-size:.78rem; color:var(--text-3); font-weight:600; margin-bottom:10px; line-height:1.5; }

    /* ─── FORM FIELDS (matches .calc-field / .calc-input) ───────────── */
    .calc-field { margin-bottom:12px; }
    .calc-label {
      display:block; font-size:.72rem; font-weight:800; letter-spacing:.06em;
      text-transform:uppercase; color:var(--text-2); margin-bottom:6px;
    }
    .calc-input {
      width:100%; padding:11px 13px; border:2px solid var(--border);
      border-radius:var(--r-sm); font-family:'Space Mono',monospace;
      font-size:.95rem; font-weight:700; color:var(--text);
      background:var(--surface-2); transition:all .2s ease;
    }
    .calc-input:focus { outline:none; border-color:var(--theme); background:var(--surface); box-shadow:var(--focus-ring); }
    select.calc-input { font-family:'DM Sans',sans-serif; -webkit-appearance:none; appearance:none; cursor:pointer; padding-right:32px; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2364748b' d='M1 1l5 5 5-5z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; }
    .input-hint { font-size:.74rem; color:var(--text-3); margin-top:4px; font-weight:600; }

    .calc-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(120px,1fr)); gap:10px; }

    /* ─── BUTTONS (matches .ehr-btn) ────────────────────────────────── */
    .vc-btn-row { display:flex; gap:10px; margin-top:18px; flex-wrap:wrap; }
    .ehr-btn {
      width:100%; background:var(--theme); color:white; border:none;
      padding:13px; border-radius:var(--r-md); font-weight:800; font-size:.9rem;
      display:flex; align-items:center; justify-content:center; gap:9px;
      cursor:pointer; transition:all .2s ease; font-family:inherit;
      box-shadow:var(--shadow-sm); flex:1; min-width:140px;
    }
    .ehr-btn:hover { filter:brightness(1.08); transform:translateY(-1px); }
    .ehr-btn:active { transform:scale(.98); }
    .ehr-btn.secondary { background:var(--theme-light); color:var(--theme); border:2px solid var(--theme); box-shadow:none; }
    .dark .ehr-btn.secondary { background:rgba(37,99,235,0.15); }

    /* ─── SAFETY OVERVIEW CARD ─────────────────────────────────────── */
    .vc-safety {
      border-radius:var(--r-md); padding:18px;
      border:1.5px solid; border-left:5px solid;
      display:flex; align-items:flex-start; gap:14px;
      margin-bottom:16px; box-shadow:var(--shadow-sm);
    }
    .vc-safety.red    { background:var(--danger-bg); border-color:var(--danger-border); border-left-color:var(--danger); }
    .vc-safety.yellow { background:rgba(217,119,6,.10); border-color:rgba(217,119,6,.28); border-left-color:var(--warning); }
    .vc-safety.green  { background:rgba(22,163,74,.10); border-color:rgba(22,163,74,.28); border-left-color:var(--success); }
    .vc-safety-icon {
      width:48px; height:48px; border-radius:12px; flex-shrink:0;
      display:flex; align-items:center; justify-content:center; font-size:1.4rem; color:white;
    }
    .vc-safety.red    .vc-safety-icon { background:var(--danger); }
    .vc-safety.yellow .vc-safety-icon { background:var(--warning); }
    .vc-safety.green  .vc-safety-icon { background:var(--success); }
    .vc-safety-label  { font-size:.7rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; }
    .vc-safety.red    .vc-safety-label { color:var(--danger); }
    .vc-safety.yellow .vc-safety-label { color:var(--warning); }
    .vc-safety.green  .vc-safety-label { color:var(--success); }
    .vc-safety-title  { font-size:1.05rem; font-weight:800; color:var(--text); margin:2px 0 4px; }
    .vc-safety-sub    { font-size:.83rem; color:var(--text-2); font-weight:600; line-height:1.45; }

    /* ─── DERIVED TILES ─────────────────────────────────────────────── */
    .derived-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:10px; margin-bottom:4px; }
    .derived-tile {
      background:var(--surface-2); border:1px solid var(--border);
      border-radius:var(--r-sm); padding:11px 13px;
    }
    .derived-tile .k { font-size:.68rem; font-weight:800; color:var(--text-3); text-transform:uppercase; letter-spacing:.05em; }
    .derived-tile .v { font-family:'Space Mono',monospace; font-size:1.1rem; font-weight:800; margin-top:4px; color:var(--text); }
    .derived-tile .u { font-size:.7rem; color:var(--text-3); font-weight:600; margin-left:4px; }

    /* ─── ABG INTERP (matches .abg-interp) ──────────────────────────── */
    .abg-interp {
      background:var(--surface-2); border-radius:var(--r-md);
      padding:13px 15px; margin:6px 0 4px; border-left:4px solid var(--theme);
    }
    .abg-interp h4 { font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--theme); margin-bottom:5px; }
    .abg-interp p { font-size:.92rem; font-weight:700; color:var(--text); }
    .abg-interp .meta { font-size:.78rem; font-weight:600; color:var(--text-2); margin-top:4px; }

    /* ─── ALERT BOXES (matches .alert-box / .clin-warn / .ok-box) ───── */
    .alert-list { display:flex; flex-direction:column; gap:9px; }
    .alert-box, .clin-warn, .ok-box {
      padding:12px 14px; border-radius:var(--r-sm);
      font-size:.86rem; font-weight:700; line-height:1.5;
    }
    .alert-box {
      background:rgba(220,38,38,.08); border:1px solid rgba(220,38,38,.22);
      border-left:4px solid var(--danger); color:#991b1b;
    }
    .dark .alert-box { color:#fecaca; background:rgba(220,38,38,.16); }
    .clin-warn {
      background:rgba(217,119,6,.09); border:1.5px solid rgba(217,119,6,.25);
      border-left:4px solid var(--warning); color:#7c2d12;
    }
    .dark .clin-warn { color:#fed7aa; background:rgba(217,119,6,.12); }
    .clin-warn strong { color:var(--warning); }
    .ok-box {
      background:rgba(22,163,74,.08); border:1px solid rgba(22,163,74,.22);
      border-left:4px solid var(--success); color:#166534;
    }
    .dark .ok-box { color:#bbf7d0; background:rgba(22,163,74,.14); }

    .alert-title { font-weight:800; font-size:.92rem; display:flex; align-items:center; gap:8px; margin-bottom:3px; }
    .alert-detail { font-weight:600; font-size:.84rem; line-height:1.55; }
    .alert-src { font-size:.7rem; opacity:.75; margin-top:6px; font-style:italic; font-weight:600; }

    /* ─── RECOMMENDATIONS (matches .stack-item) ─────────────────────── */
    .stack-list { display:flex; flex-direction:column; gap:9px; margin-top:4px; }
    .stack-item {
      background:var(--surface-2); border:1px solid var(--border);
      border-left:4px solid var(--theme); border-radius:var(--r-sm);
      padding:12px 14px;
    }
    .stack-item .ttl { font-weight:800; font-size:.88rem; color:var(--text); display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .stack-item .pri {
      background:var(--theme); color:white; font-size:.62rem;
      padding:3px 7px; border-radius:99px; font-weight:800;
      letter-spacing:.05em;
    }
    .stack-item .why { font-size:.8rem; color:var(--text-2); font-weight:600; margin-top:5px; line-height:1.55; }
    .stack-item .src { font-size:.7rem; color:var(--text-3); margin-top:6px; font-style:italic; font-weight:600; }

    /* ─── COMPLETENESS BAR ──────────────────────────────────────────── */
    .completeness { display:flex; align-items:center; gap:9px; font-size:.74rem; font-weight:700; color:var(--text-3); margin-bottom:10px; }
    .cp-bar { flex:1; height:5px; background:var(--border); border-radius:99px; overflow:hidden; }
    .cp-fill { height:100%; background:var(--theme); border-radius:99px; transition:width .3s; }

    /* ─── EMPTY STATE ───────────────────────────────────────────────── */
    .empty-state {
      padding:32px 18px; text-align:center;
      background:var(--surface-2); border:1.5px dashed var(--border);
      border-radius:var(--r-md);
    }
    .es-icon { font-size:2.4rem; margin-bottom:8px; opacity:.6; }
    .es-text { font-size:.86rem; color:var(--text-3); font-weight:600; line-height:1.55; }

    /* ─── SAVED CASES LIST ──────────────────────────────────────────── */
    .case-list { display:flex; flex-direction:column; gap:8px; }
    .case-row {
      display:flex; align-items:center; gap:11px;
      padding:11px 13px; border-radius:var(--r-sm);
      background:var(--surface-2); border:1px solid var(--border);
      transition:all .15s ease;
    }
    .case-row:hover { background:var(--surface); box-shadow:var(--shadow-xs); }
    .case-row.active { border-color:var(--theme); background:var(--theme-light); }
    .case-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
    .case-dot.red    { background:var(--danger); }
    .case-dot.yellow { background:var(--warning); }
    .case-dot.green  { background:var(--success); }
    .case-info { flex:1; min-width:0; }
    .case-ref  { font-weight:800; font-size:.86rem; color:var(--text); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .case-meta { font-size:.72rem; color:var(--text-3); font-weight:600; margin-top:2px; }
    .case-act {
      background:transparent; border:none; padding:6px 8px;
      font-size:.95rem; cursor:pointer; border-radius:6px;
      color:var(--text-3); transition:all .15s;
    }
    .case-act:hover { background:var(--surface); color:var(--theme); }
    .case-act.danger:hover { background:var(--danger-bg); color:var(--danger); }

    /* ─── BOTTOM NAV (matches main app exactly) ─────────────────────── */
    .bottom-nav {
      position:fixed; bottom:0; left:0; width:100%; max-width:100vw;
      background:var(--surface); box-shadow:0 -2px 20px rgba(0,0,0,.09);
      display:flex; justify-content:space-around;
      padding:10px 4px calc(10px + env(safe-area-inset-bottom));
      z-index:100; border-top:1px solid var(--border);
      border-top-left-radius:var(--r-xl); border-top-right-radius:var(--r-xl);
      gap:2px;
    }
    .nav-it {
      display:flex; flex-direction:column; align-items:center; gap:4px;
      color:var(--text-3); font-size:.58rem; font-weight:800;
      letter-spacing:.03em; text-transform:uppercase;
      padding:7px 3px; border-radius:var(--r-sm);
      cursor:pointer; transition:all .2s ease;
      flex:1 1 0; min-width:0;
      border:none; background:transparent; font-family:inherit; text-decoration:none;
    }
    .nav-it .nav-emoji { font-size:1.25rem; line-height:1; transition:transform .25s cubic-bezier(.34,1.56,.64,1); }
    .nav-it.active { color:var(--theme); background:var(--theme-light); }
    .nav-it.active .nav-emoji { transform:scale(1.22); }
    .nav-it > span:last-child { width:100%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; text-align:center; }

    /* ─── PRINT (lean output) ──────────────────────────────────────── */
    @media print {
      html,body { position:static; height:auto; overflow:visible; max-width:none; }
      .header, .vc-btn-row, .case-list, form.vc-form, .vc-no-print, .bottom-nav { display:none !important; }
      body { background:white; color:black; }
      main, .content { position:static; height:auto; overflow:visible; padding:0; }
      .info-card, .sec-hdr { box-shadow:none; border:1px solid #ccc; break-inside:avoid; }
      .vc-disclaimer { display:none; }
      .vc-grid { grid-template-columns:1fr !important; }
    }
  </style>
</head>
<body>

<!-- ── HEADER ────────────────────────────────────────────────────────── -->
<header class="header">
  <div class="header-inner">
    <div>
      <div class="header-title">🧠 <span>Vent Coach</span></div>
      <div class="header-sub">Real-time bedside titration support</div>
      <?php if ($result): ?>
        <div class="header-badge"><?= e($result['scenario']['emoji']) ?> <?= e($result['scenario']['name']) ?></div>
      <?php else: ?>
        <div class="header-badge">📋 Fill in the form to start</div>
      <?php endif; ?>
    </div>
    <div class="header-actions">
      <button class="hbtn" id="printBtn" title="Print" aria-label="Print">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      </button>
      <button class="hbtn" id="darkToggle" title="Toggle dark mode" aria-label="Toggle dark mode">
        <svg id="moonIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg id="sunIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:none;"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/></svg>
      </button>
    </div>
  </div>
</header>

<!-- ── MAIN SCROLLABLE CONTENT ──────────────────────────────────────── -->
<main>
  <div class="content">
    <div class="content-inner">

      <?= render_flashes() ?>

      <div class="vc-disclaimer">
        ⚠️ <strong>Educational use only.</strong>
        Vent Coach provides evidence-based suggestions, not orders. Always verify with your patient, your team, and local protocols.
      </div>

      <div class="vc-grid">

        <!-- ── LEFT: INPUT FORM ──────────────────────────────────── -->
        <form method="POST" action="<?= app_url('/app/coach') ?>" class="vc-form vc-no-print">
          <?= csrf_field() ?>
          <input type="hidden" name="case_id" value="<?= e((string)($activeCaseId ?? '')) ?>">

          <div class="sec-hdr">
            <div class="sec-hdr-title">📋 Patient snapshot</div>
            <div class="sec-hdr-sub">Anonymous bedside data only — never enter the patient's name or MRN.</div>
          </div>

          <div class="info-card">
            <h3>🩺 Reference &amp; scenario</h3>

            <div class="calc-field">
              <label class="calc-label">Bedside reference (optional)</label>
              <input type="text" name="reference" maxlength="80" class="calc-input"
                     placeholder="e.g. Bed 12 — 06:30"
                     value="<?= e($activeReference) ?>"
                     style="font-family:'DM Sans',sans-serif">
              <div class="input-hint">Free-text label only — no patient identifiers.</div>
            </div>

            <div class="calc-grid">
              <div class="calc-field">
                <label class="calc-label">Scenario</label>
                <select name="scenario" class="calc-input">
                  <?php foreach($scenarios as $k => $s):
                    $sel = ($activeInput['scenario'] ?? 'healthy') === $k ? 'selected' : ''; ?>
                    <option value="<?= e($k) ?>" <?= $sel ?>><?= e($s['emoji']) ?> <?= e($s['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="calc-field">
                <label class="calc-label">PBW (kg)</label>
                <input type="number" name="pbw_kg" step="0.1" min="25" max="150" inputmode="decimal" class="calc-input"
                       value="<?= e(vc_field($activeInput['pbw_kg'] ?? null)) ?>" placeholder="62">
                <div class="input-hint">From height/sex · <a href="<?= app_url('/app/ventguide') ?>">PBW calculator →</a></div>
              </div>
            </div>
          </div>

          <div class="info-card">
            <h3>🎛️ Ventilator settings</h3>
            <div class="calc-grid">
              <div class="calc-field">
                <label class="calc-label">VT (mL)</label>
                <input type="number" name="vt_ml" step="1" min="100" max="1500" inputmode="numeric" class="calc-input"
                       value="<?= e(vc_field($activeInput['vent']['vt_ml'] ?? null)) ?>" placeholder="450">
              </div>
              <div class="calc-field">
                <label class="calc-label">RR (/min)</label>
                <input type="number" name="rr" step="1" min="4" max="60" inputmode="numeric" class="calc-input"
                       value="<?= e(vc_field($activeInput['vent']['rr'] ?? null)) ?>" placeholder="14">
              </div>
              <div class="calc-field">
                <label class="calc-label">PEEP (cmH₂O)</label>
                <input type="number" name="peep" step="1" min="0" max="25" inputmode="numeric" class="calc-input"
                       value="<?= e(vc_field($activeInput['vent']['peep'] ?? null)) ?>" placeholder="5">
              </div>
              <div class="calc-field">
                <label class="calc-label">Pplat (cmH₂O)</label>
                <input type="number" name="pplat" step="1" min="0" max="60" inputmode="numeric" class="calc-input"
                       value="<?= e(vc_field($activeInput['vent']['pplat'] ?? null)) ?>" placeholder="22">
              </div>
              <div class="calc-field">
                <label class="calc-label">FiO₂ (%)</label>
                <input type="number" name="fio2_pct" step="1" min="21" max="100" inputmode="numeric" class="calc-input"
                       value="<?= e(vc_field($activeInput['vent']['fio2_pct'] ?? null)) ?>" placeholder="40">
              </div>
            </div>
          </div>

          <div class="info-card">
            <h3>🧪 Latest ABG <span style="font-size:.7rem;font-weight:600;color:var(--text-3);margin-left:6px;">(optional)</span></h3>
            <div class="calc-grid">
              <div class="calc-field">
                <label class="calc-label">pH</label>
                <input type="number" name="ph" step="0.01" min="6.8" max="8.0" inputmode="decimal" class="calc-input"
                       value="<?= e(vc_field($activeInput['abg']['ph'] ?? null)) ?>" placeholder="7.38">
              </div>
              <div class="calc-field">
                <label class="calc-label">PaCO₂</label>
                <input type="number" name="paco2" step="1" min="10" max="150" inputmode="numeric" class="calc-input"
                       value="<?= e(vc_field($activeInput['abg']['paco2'] ?? null)) ?>" placeholder="40">
              </div>
              <div class="calc-field">
                <label class="calc-label">PaO₂</label>
                <input type="number" name="pao2" step="1" min="20" max="700" inputmode="numeric" class="calc-input"
                       value="<?= e(vc_field($activeInput['abg']['pao2'] ?? null)) ?>" placeholder="80">
              </div>
              <div class="calc-field">
                <label class="calc-label">HCO₃</label>
                <input type="number" name="hco3" step="0.1" min="3" max="60" inputmode="decimal" class="calc-input"
                       value="<?= e(vc_field($activeInput['abg']['hco3'] ?? null)) ?>" placeholder="24">
              </div>
              <div class="calc-field">
                <label class="calc-label">SpO₂ (%)</label>
                <input type="number" name="spo2" step="1" min="30" max="100" inputmode="numeric" class="calc-input"
                       value="<?= e(vc_field($activeInput['abg']['spo2'] ?? null)) ?>" placeholder="96">
              </div>
              <div class="calc-field">
                <label class="calc-label">Target PaCO₂</label>
                <input type="number" name="target_paco2" step="1" min="25" max="80" inputmode="numeric" class="calc-input"
                       value="<?= e(vc_field($activeInput['target_paco2'] ?? null)) ?>" placeholder="40">
              </div>
            </div>
          </div>

          <div class="vc-btn-row">
            <button type="submit" name="action" value="analyze" class="ehr-btn">🔎 Analyze</button>
            <button type="submit" name="action" value="save" class="ehr-btn secondary">💾 <?= $activeCaseId ? 'Update case' : 'Save case' ?></button>
          </div>
        </form>

        <!-- ── RIGHT: RESULTS ──────────────────────────────────────── -->
        <div>
          <div class="sec-hdr">
            <div class="sec-hdr-title">🩺 Live analysis</div>
            <div class="sec-hdr-sub">All thresholds reference published evidence (ARDSNet, Amato 2015, SCCM, ATS).</div>
          </div>

          <?php if ($result === null): ?>
            <div class="info-card">
              <div class="empty-state">
                <div class="es-icon">🧠</div>
                <div class="es-text">Fill in the ventilator settings (and ABG, if available) on the left, then press <strong>🔎 Analyze</strong> to see a personalized safety report.</div>
              </div>
            </div>
          <?php else: ?>

            <?php $lvl = $result['safety_level']; ?>
            <div class="vc-safety <?= e($lvl) ?>">
              <div class="vc-safety-icon"><?= vc_level_emoji($lvl) ?></div>
              <div>
                <div class="vc-safety-label"><?= strtoupper($lvl) ?> zone</div>
                <div class="vc-safety-title"><?= e(vc_level_label($lvl)) ?></div>
                <div class="vc-safety-sub">
                  <?= e($result['scenario']['emoji'] . ' ' . $result['scenario']['name']) ?>
                  <?php if ($activeInput['pbw_kg'] !== null): ?> · PBW <?= e(vc_field($activeInput['pbw_kg'])) ?> kg<?php endif; ?>
                </div>
              </div>
            </div>

            <div class="info-card">
              <div class="completeness">
                <span>Data completeness</span>
                <div class="cp-bar"><div class="cp-fill" style="width:<?= (int)$result['completeness'] ?>%"></div></div>
                <span><?= (int)$result['completeness'] ?>%</span>
              </div>

              <h3>📊 Derived metrics</h3>
              <div class="derived-grid">
                <div class="derived-tile">
                  <div class="k">ΔP (driving)</div>
                  <div class="v"><?= $result['derived']['driving_pressure'] !== null ? e(vc_field($result['derived']['driving_pressure'])) . '<span class="u">cmH₂O</span>' : '—' ?></div>
                </div>
                <div class="derived-tile">
                  <div class="k">VT / PBW</div>
                  <div class="v"><?= $result['derived']['vt_per_kg'] !== null ? e(vc_field($result['derived']['vt_per_kg'])) . '<span class="u">mL/kg</span>' : '—' ?></div>
                </div>
                <div class="derived-tile">
                  <div class="k">Minute vent</div>
                  <div class="v"><?= $result['derived']['minute_ventilation'] !== null ? e(vc_field($result['derived']['minute_ventilation'])) . '<span class="u">L/min</span>' : '—' ?></div>
                </div>
                <div class="derived-tile">
                  <div class="k">P/F ratio</div>
                  <div class="v"><?= $result['derived']['pf_ratio'] !== null ? (int)$result['derived']['pf_ratio'] : '—' ?></div>
                </div>
                <div class="derived-tile">
                  <div class="k">Compliance</div>
                  <div class="v"><?= $result['derived']['static_compliance'] !== null ? e(vc_field($result['derived']['static_compliance'])) . '<span class="u">mL/cm</span>' : '—' ?></div>
                </div>
                <?php if ($result['target_vt']): ?>
                  <div class="derived-tile">
                    <div class="k">Target VT</div>
                    <div class="v"><?= (int)$result['target_vt']['low'] ?>–<?= (int)$result['target_vt']['high'] ?><span class="u">mL</span></div>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <?php if (!empty($result['abg']['summary'])): ?>
              <div class="info-card">
                <h3>🧪 ABG interpretation</h3>
                <div class="abg-interp">
                  <h4>Acid–base assessment</h4>
                  <p><?= e($result['abg']['summary']) ?></p>
                  <?php if (!empty($result['abg']['severity']) && $result['abg']['severity'] !== 'normal'): ?>
                    <div class="meta">Severity: <strong><?= e(ucfirst($result['abg']['severity'])) ?></strong></div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($result['alerts'])): ?>
              <div class="info-card">
                <h3>🔔 Safety alerts</h3>
                <div class="alert-list">
                  <?php foreach ($result['alerts'] as $a):
                    $boxClass = vc_box_class($a['level']); ?>
                    <div class="<?= e($boxClass) ?>">
                      <div class="alert-title"><?= e($a['icon']) ?> <?= e($a['title']) ?></div>
                      <div class="alert-detail"><?= e($a['detail']) ?></div>
                      <div class="alert-src">Source · <?= e($a['source']) ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <?php if (!empty($result['recommendations'])): ?>
              <div class="info-card">
                <h3>✅ Recommended actions</h3>
                <div class="stack-list">
                  <?php foreach ($result['recommendations'] as $rc): ?>
                    <div class="stack-item">
                      <div class="ttl">
                        <span class="pri">P<?= (int)$rc['priority'] ?></span>
                        <?= e($rc['icon']) ?>
                        <span><?= e($rc['action']) ?></span>
                      </div>
                      <div class="why"><?= e($rc['rationale']) ?></div>
                      <div class="src">Source · <?= e($rc['source']) ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

          <?php endif; ?>
        </div>

      </div>

      <!-- ── SAVED CASES ─────────────────────────────────────────── -->
      <div class="sec-hdr vc-no-print" style="margin-top:18px;">
        <div class="sec-hdr-title">📂 Saved cases <span style="font-size:.78rem;font-weight:700;color:var(--text-3);margin-left:6px;">(<?= count($savedCases) ?>)</span></div>
        <div class="sec-hdr-sub">All cases are anonymous and stored only inside your own account. Delete any time.</div>
      </div>

      <div class="info-card vc-no-print">
        <?php if (empty($savedCases)): ?>
          <div class="empty-state">
            <div class="es-icon">📂</div>
            <div class="es-text">No saved cases yet. Press <strong>💾 Save case</strong> after analyzing to create your first snapshot.</div>
          </div>
        <?php else: ?>
          <div class="case-list">
            <?php foreach ($savedCases as $c):
              $isActive = $activeCaseId === (int)$c['id'];
              $sc       = $scenarios[$c['scenario']] ?? ['emoji'=>'🩺','name'=>$c['scenario']];
              $lvl      = $c['safety_level'] ?? '';
            ?>
              <div class="case-row <?= $isActive ? 'active' : '' ?>">
                <div class="case-dot <?= e($lvl) ?>"></div>
                <div class="case-info">
                  <div class="case-ref"><?= e($c['reference'] ?: ('Case #' . $c['id'])) ?></div>
                  <div class="case-meta"><?= e($sc['emoji'] . ' ' . $sc['name']) ?> · <?= e(time_ago($c['updated_at'])) ?></div>
                </div>
                <a class="case-act" href="?case=<?= (int)$c['id'] ?>" title="Open">📂</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this case?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="case_id" value="<?= (int)$c['id'] ?>">
                  <button type="submit" class="case-act danger" title="Delete">🗑️</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</main>

<!-- ── BOTTOM NAV — matches the main app exactly so the tab strip ─── -->
<!--    stays visible when the user opens Coach, giving a native feel.  -->
<nav class="bottom-nav" role="tablist" aria-label="Main navigation">
  <a class="nav-it" href="<?= app_url('/app/ventguide') ?>#scenarios" data-feature="scenarios" data-feature-name="Ventilation Scenarios">
    <span class="nav-emoji">🏥</span><span>Scenarios</span>
  </a>
  <a class="nav-it" href="<?= app_url('/app/ventguide') ?>#abg" data-feature="abg_calc" data-feature-name="ABG Calculator">
    <span class="nav-emoji">🧪</span><span>ABG Calc</span>
  </a>
  <a class="nav-it" href="<?= app_url('/app/ventguide') ?>#compare" data-feature="compare" data-feature-name="Scenario Comparison">
    <span class="nav-emoji">📊</span><span>Compare</span>
  </a>
  <a class="nav-it" href="<?= app_url('/app/ventguide') ?>#guide" data-feature="guide" data-feature-name="Clinical Guidelines">
    <span class="nav-emoji">📖</span><span>Guide</span>
  </a>
  <a class="nav-it" href="<?= app_url('/app/ventguide') ?>#tools" data-feature="tools" data-feature-name="Clinical Tools">
    <span class="nav-emoji">🔧</span><span>Tools</span>
  </a>
  <a class="nav-it active" href="<?= app_url('/app/coach') ?>" aria-current="page" aria-selected="true">
    <span class="nav-emoji">🧠</span><span>Coach</span>
  </a>
</nav>

<script>
(function(){
  'use strict';
  // ── Shared dark-mode persistence (same key as main app) ──────────
  const STATE_KEY = 'edvpro_state';
  const moon = document.getElementById('moonIcon');
  const sun  = document.getElementById('sunIcon');

  function readState() {
    try { return JSON.parse(localStorage.getItem(STATE_KEY) || '{}') || {}; }
    catch (e) { return {}; }
  }
  function writeState(patch) {
    try {
      const cur = readState();
      Object.assign(cur, patch);
      localStorage.setItem(STATE_KEY, JSON.stringify(cur));
    } catch (e) { /* private mode / quota — ignore */ }
  }
  function applyDark(dark) {
    document.body.classList.toggle('dark', !!dark);
    if (moon && sun) {
      moon.style.display = dark ? 'none' : '';
      sun.style.display  = dark ? ''     : 'none';
    }
  }

  // Initial dark-mode state: respect saved preference, else system pref.
  const saved = readState();
  const initialDark = (typeof saved.dark === 'boolean')
    ? saved.dark
    : (window.matchMedia && window.matchMedia('(prefers-color-scheme:dark)').matches);
  applyDark(initialDark);

  const toggle = document.getElementById('darkToggle');
  if (toggle) {
    toggle.addEventListener('click', () => {
      const next = !document.body.classList.contains('dark');
      applyDark(next);
      writeState({ dark: next });
    });
  }

  const printBtn = document.getElementById('printBtn');
  if (printBtn) printBtn.addEventListener('click', () => window.print());
})();
</script>

<?= toast_script_tag() . "\n" ?>

</body>
</html>
