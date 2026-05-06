<?php
if (!defined('VENTGUIDE_INTERNAL')) { http_response_code(404); exit('Not found'); }
require_once __DIR__ . '/../includes/pwa.php';
?>
<!DOCTYPE html>
<html lang="en" class="no-js">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0, viewport-fit=cover">
  <?= pwa_head_tags('Evidence-based emergency department ventilation reference.') . "\n" ?>
  <?= pwa_zoom_lock_script() . "\n" ?>
  <title>🫁 ED VentGuide Pro</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">

  <style>
    /* ── PRE-RENDER FLASH PREVENTION ── */
    :root {
      --bg:#f1f5f9; --surface:#ffffff; --surface-2:#f8fafc;
      --text:#0f172a; --text-2:#475569; --text-3:#94a3b8;
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

    /* ── SCENARIO THEME VARS ── */
    :root {
      --c-healthy:#2563eb; --rgb-healthy:37,99,235;
      --c-asthma:#d97706;  --rgb-asthma:217,119,6;
      --c-ards:#7c3aed;    --rgb-ards:124,58,237;
      --c-hypo:#be123c;    --rgb-hypo:190,18,60;
      --c-preg:#db2777;    --rgb-preg:219,39,119;
      --c-niv:#06b6d4;     --rgb-niv:6,182,212;
      --c-sepsis:#15803d;  --rgb-sepsis:21,128,61;
      --c-neuro:#4f46e5;   --rgb-neuro:79,70,229;
      --c-pe:#7c2d12;      --rgb-pe:124,45,18;
      --c-dka:#c026d3;     --rgb-dka:192,38,211;
      --c-cpe:#dc2626;     --rgb-cpe:220,38,38;
      --c-ana:#b45309;     --rgb-ana:180,83,9;
      --c-obesity:#475569; --rgb-obesity:71,85,105;
      --c-rosc:#0f766e;    --rgb-rosc:15,118,110;
    }

    /* ── DARK MODE ── */
    .dark {
      --bg:#0b0f1a; --surface:#141929; --surface-2:#1a2236;
      --text:#f0f4ff; --text-2:#8899bb; --text-3:#4a5a7a;
      --border:#1e2d47; --danger-bg:rgba(220,38,38,0.12);
      --danger-border:rgba(220,38,38,0.28);
      --shadow-xs:0 1px 3px rgba(0,0,0,0.3);
      --shadow-sm:0 2px 8px rgba(0,0,0,0.38);
      --shadow-md:0 4px 16px rgba(0,0,0,0.45);
      --shadow-lg:0 8px 32px rgba(0,0,0,0.5);
    }

    /* ── REDUCED MOTION ── */
    @media (prefers-reduced-motion:reduce) {
      *,*::before,*::after { animation-duration:.01ms!important; transition-duration:.01ms!important; }
    }

    /* ── RESET ── */
    *,*::before,*::after { box-sizing:border-box; -webkit-tap-highlight-color:transparent; margin:0; padding:0; }
    html,body {
      height:100%;
      width:100%;
      max-width:100vw;
      overflow:hidden;
      overscroll-behavior:none;
      -webkit-text-size-adjust:100%;
      text-size-adjust:100%;
    }
    body {
      font-family:'DM Sans',system-ui,-apple-system,sans-serif;
      background:var(--bg); color:var(--text);
      display:flex; flex-direction:column;
      transition:background .3s,color .3s;
      position:fixed;
      inset:0;
      max-width:100vw;
      overflow:hidden;
      touch-action:pan-y;
      -webkit-user-select:none; user-select:none;
    }
    input, textarea, [contenteditable] {
      -webkit-user-select:auto; user-select:auto;
    }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width:4px; height:4px; }
    ::-webkit-scrollbar-track { background:transparent; }
    ::-webkit-scrollbar-thumb { background:var(--border); border-radius:99px; }

    /* ── ACCESSIBILITY ── */
    .sr-only { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border-width:0; }
    button,a { outline:none; text-decoration:none; -webkit-tap-highlight-color:transparent; touch-action:manipulation; }
    button:focus-visible,a:focus-visible,input:focus-visible,[tabindex]:focus-visible { outline:none; box-shadow:var(--focus-ring); border-radius:var(--r-sm); }

    /* ── TERM TOOLTIPS ── */
    .term { border-bottom:2px dotted var(--theme); cursor:help; font-weight:700; transition:border-color .3s; }

    /* ── TOAST ── */
    .toast-wrap { position:fixed; top:16px; left:50%; transform:translateX(-50%); z-index:9999; display:flex; flex-direction:column; gap:8px; width:92%; max-width:400px; pointer-events:none; }
    .toast { background:var(--surface); color:var(--text); padding:13px 16px; border-radius:var(--r-md); box-shadow:var(--shadow-lg); font-size:.88rem; font-weight:700; line-height:1.45; display:flex; align-items:flex-start; gap:10px; opacity:0; pointer-events:auto; border:1px solid var(--border); border-left:4px solid var(--theme); animation:toastIn .32s cubic-bezier(.16,1,.3,1) forwards; }
    .toast.danger { border-left-color:var(--danger); }
    .toast.success { border-left-color:var(--success); }
    @keyframes toastIn { from{opacity:0;transform:translateY(-14px) scale(.96)} to{opacity:1;transform:translateY(0) scale(1)} }
    @keyframes toastOut { to{opacity:0;transform:translateY(-10px) scale(.96)} }
    .toast.removing { animation:toastOut .28s forwards; }

    /* ── HEADER ── */
    .header {
      background:var(--theme); color:white;
      padding:max(env(safe-area-inset-top),14px) 18px 22px;
      border-bottom-left-radius:var(--r-xl); border-bottom-right-radius:var(--r-xl);
      box-shadow:var(--shadow-md); transition:background .4s ease;
      position:relative; z-index:10; overflow:hidden;
    }
    .header::before { content:''; position:absolute; top:-55%; right:-8%; width:210px; height:210px; border-radius:50%; background:rgba(255,255,255,.08); pointer-events:none; }
    .header::after  { content:''; position:absolute; bottom:-30%; left:-4%; width:130px; height:130px; border-radius:50%; background:rgba(255,255,255,.05); pointer-events:none; }
    .header-inner { display:flex; justify-content:space-between; align-items:flex-start; position:relative; z-index:1; }
    .header-title { font-size:1.35rem; font-weight:800; color:white; display:flex; align-items:center; gap:9px; letter-spacing:-.02em; }
    .header-sub { font-size:.84rem; color:rgba(255,255,255,.84); font-weight:500; margin-top:3px; }
    .header-badge { background:rgba(255,255,255,.2); color:white; font-size:.7rem; font-weight:800; padding:3px 10px; border-radius:var(--r-full); letter-spacing:.05em; margin-top:8px; display:inline-block; border:1px solid rgba(255,255,255,.3); }
    .header-actions { display:flex; gap:7px; }
    .hbtn { background:rgba(255,255,255,.18); border:1.5px solid rgba(255,255,255,.3); width:38px; height:38px; border-radius:50%; color:white; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:1rem; transition:all .2s ease; }
    .hbtn:hover { background:rgba(255,255,255,.28); }
    .hbtn:active { transform:scale(.88); }
    .hbtn svg { width:17px; height:17px; pointer-events:none; }

    /* ── CONTENT ── */
    main { width:100%; max-width:100vw; min-height:0; overflow:hidden; }
    .content {
      flex:1; min-height:0; width:100%; max-width:100vw;
      overflow-y:auto; overflow-x:hidden;
      padding:16px 16px calc(96px + env(safe-area-inset-bottom));
      scroll-behavior:smooth;
      overscroll-behavior-y:contain;
      overscroll-behavior-x:none;
      -webkit-overflow-scrolling:touch;
      touch-action:pan-y;
    }
    .view { display:none; animation:slideUp .35s cubic-bezier(.16,1,.3,1); padding-bottom:20px; width:100%; max-width:100%; overflow-x:hidden; }
    .view.active { display:block; }
    @keyframes slideUp { from{opacity:0;transform:translateY(13px)} to{opacity:1;transform:translateY(0)} }

    /* ── CHIPS ── */
    .chips-outer { position:relative; margin:0 -16px; }
    .chips-outer::after { content:''; position:absolute; right:0; top:0; bottom:16px; width:38px; background:linear-gradient(to right,transparent,var(--bg)); pointer-events:none; z-index:2; }
    .chips-wrap { display:flex; overflow-x:auto; gap:10px; padding:4px 16px 16px; scroll-snap-type:x mandatory; scroll-padding-inline:16px; scrollbar-width:none; overscroll-behavior-x:contain; -webkit-overflow-scrolling:touch; }
    .chips-wrap::-webkit-scrollbar { display:none; }
    .chip { flex:0 0 auto; background:var(--surface); border:2px solid var(--border); padding:10px 16px; border-radius:var(--r-full); box-shadow:var(--shadow-xs); font-weight:700; font-size:.88rem; color:var(--text-2); display:flex; align-items:center; gap:8px; transition:all .22s ease; cursor:pointer; white-space:nowrap; scroll-snap-align:start; scroll-snap-stop:always; scroll-margin-inline:16px; min-height:44px; }
    .chip:hover { transform:translateY(-1px); box-shadow:var(--shadow-sm); }
    .chip:active { transform:scale(.95); }
    .chip.active {
      background:var(--theme);
      color:white;
      border-color:transparent;
      box-shadow:0 4px 14px rgba(var(--theme-rgb),.38);
      transform:none;
    }
    .chip-emoji { font-size:1.1rem; line-height:1; }
    .chip-label { min-width:0; overflow:hidden; text-overflow:ellipsis; }
    .chip-label-short { display:none; }
    .scenario-select-wrap { display:none; margin-bottom:16px; }
    .scenario-select-label { display:block; font-size:.7rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--text-3); margin-bottom:7px; }
    .scenario-select-shell { position:relative; }
    .scenario-select-shell::after {
      content:'▾';
      position:absolute;
      right:14px;
      top:50%;
      transform:translateY(-50%);
      font-size:1rem;
      color:var(--text-3);
      pointer-events:none;
    }
    .scenario-select {
      width:100%;
      -webkit-appearance:none;
      appearance:none;
      border:2px solid var(--border);
      border-radius:var(--r-md);
      background:var(--surface);
      color:var(--text);
      box-shadow:var(--shadow-xs);
      padding:13px 42px 13px 14px;
      font-family:inherit;
      font-size:.95rem;
      font-weight:800;
      line-height:1.3;
    }
    .scenario-select:focus {
      outline:none;
      border-color:var(--theme);
      box-shadow:var(--focus-ring);
    }

    /* ── SAFETY ALERT ── */
    .safety-alert { background:var(--danger-bg); border:1.5px solid var(--danger-border); border-left:5px solid var(--danger); border-radius:var(--r-md); padding:14px 16px; margin-bottom:16px; display:flex; align-items:flex-start; gap:13px; }
    .safety-icon { width:40px; height:40px; border-radius:10px; background:rgba(220,38,38,.12); display:flex; align-items:center; justify-content:center; font-size:1.2rem; color:var(--danger); flex-shrink:0; }
    .safety-label { font-size:.7rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; color:var(--danger); }
    .safety-title { font-size:1.05rem; font-weight:800; color:#991b1b; margin:2px 0 4px; }
    .dark .safety-title { color:#fca5a5; }
    .safety-note { font-size:.84rem; color:var(--danger); font-weight:600; line-height:1.45; }

    /* ── NIV CONTRAINDICATION BANNER ── */
    .contra-banner { background:var(--danger-bg); border:1.5px solid var(--danger-border); border-left:5px solid var(--danger); border-radius:var(--r-md); padding:14px 16px; margin-bottom:16px; }
    .contra-banner h3 { color:var(--danger); font-size:.95rem; font-weight:800; display:flex; align-items:center; gap:8px; margin-bottom:6px; }
    .contra-banner p { font-size:.85rem; color:var(--text-2); font-weight:600; }
    .hidden { display:none!important; }

    /* ── STATS BAR ── */
    .stats-bar { display:flex; gap:9px; overflow-x:auto; padding:4px 0 14px; overscroll-behavior-x:contain; }
    .stats-bar::-webkit-scrollbar { display:none; }
    .stat-pill { flex:0 0 auto; background:var(--surface); border:1px solid var(--border); border-radius:var(--r-full); padding:7px 14px; font-size:.77rem; font-weight:700; color:var(--text-2); box-shadow:var(--shadow-xs); display:flex; align-items:center; gap:6px; white-space:nowrap; }
    .stat-pill .sp-val { font-family:'Space Mono',monospace; color:var(--theme); font-weight:700; transition:color .3s; }

    /* ── PARAM CARDS ── */
    .card-grid { display:grid; gap:12px; grid-template-columns:1fr; }
    @media(min-width:580px){ .card-grid { grid-template-columns:1fr 1fr; } }
    .pcard { background:var(--surface); border-radius:var(--r-md); padding:16px; box-shadow:var(--shadow-sm); display:flex; align-items:flex-start; gap:14px; border-left:4px solid var(--theme); transition:transform .2s ease,box-shadow .2s ease; position:relative; overflow:hidden; }
    .pcard::after { content:''; position:absolute; top:0; right:0; width:56px; height:100%; background:linear-gradient(90deg,transparent,var(--theme-light)); pointer-events:none; }
    .pcard:hover { transform:translateY(-2px); box-shadow:var(--shadow-md); }
    .pcard-icon { width:44px; height:44px; border-radius:var(--r-sm); background:var(--theme-light); display:flex; align-items:center; justify-content:center; flex-shrink:0; transition:background .3s; }
    .pcard-icon svg { color:var(--theme); }
    .pcard-icon .emoji { font-size:1.3rem; }
    .pcard-label { font-size:.72rem; text-transform:uppercase; letter-spacing:.06em; font-weight:800; color:var(--text-3); margin-bottom:3px; }
    .pcard-val { font-size:1.15rem; font-weight:800; color:var(--text); line-height:1.25; margin-bottom:4px; font-family:'Space Mono',monospace; }
    .pcard-note { font-size:.83rem; color:var(--text-2); font-weight:500; line-height:1.5; }
    .pcard-range { display:block; font-size:.78rem; font-weight:700; color:var(--text-3); margin-top:4px; }

    /* ── EVIDENCE TAGS ── */
    .evidence-bar { display:flex; gap:8px; flex-wrap:wrap; margin-top:14px; }
    .ev-tag { font-size:.7rem; font-weight:700; text-transform:uppercase; letter-spacing:.04em; padding:4px 10px; border-radius:var(--r-full); background:var(--theme-light); color:var(--theme); border:1px solid rgba(var(--theme-rgb),.2); transition:background .3s,color .3s; }

    /* ── WAVEFORM ── */
    .waveform-card { background:var(--surface); border-radius:var(--r-md); padding:16px; box-shadow:var(--shadow-sm); margin-top:16px; border:1px solid var(--border); }
    .waveform-label { font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--text-3); margin-bottom:10px; display:flex; align-items:center; gap:7px; }
    .waveform-svg { width:100%; height:110px; background:var(--surface-2); border-radius:var(--r-sm); border:1px solid var(--border); display:block; }
    .waveform-caption { margin:8px 0 0; font-size:.8rem; color:var(--text-2); font-weight:600; }

    /* ── EHR BUTTON ── */
    .ehr-btn { width:100%; background:var(--theme); color:white; border:none; padding:14px; border-radius:var(--r-md); font-weight:800; font-size:.93rem; display:flex; align-items:center; justify-content:center; gap:9px; margin-top:16px; cursor:pointer; transition:all .2s ease; font-family:inherit; box-shadow:var(--shadow-sm); }
    .ehr-btn svg { width:18px; height:18px; pointer-events:none; }
    .ehr-btn:hover { filter:brightness(1.08); transform:translateY(-1px); }
    .ehr-btn:active { transform:scale(.98); }
    .ehr-btn.secondary { background:var(--theme-light); color:var(--theme); border:2px solid var(--theme); }
    .ehr-sub { text-align:center; font-size:.77rem; color:var(--text-3); margin-top:7px; font-weight:500; }

    /* ── SECTION HEADER ── */
    .sec-hdr { background:var(--surface); border-radius:var(--r-md); padding:15px 17px; margin-bottom:15px; box-shadow:var(--shadow-xs); border:1px solid var(--border); }
    .sec-hdr-title { font-size:.98rem; font-weight:800; color:var(--theme); display:flex; align-items:center; gap:8px; margin-bottom:4px; transition:color .3s; }
    .sec-hdr-sub { font-size:.84rem; color:var(--text-2); font-weight:500; line-height:1.5; }

    /* ── INFO CARDS ── */
    .info-card { background:var(--surface); border-radius:var(--r-md); padding:18px; margin-bottom:13px; border:1px solid var(--border); box-shadow:var(--shadow-xs); }
    .info-card h3 { font-size:.95rem; font-weight:800; color:var(--theme); display:flex; align-items:center; gap:8px; margin-bottom:12px; transition:color .3s; }
    .info-card h3 svg { width:17px; height:17px; flex-shrink:0; }
    .info-card ul { padding-left:18px; }
    .info-card li { font-size:.88rem; color:var(--text); font-weight:500; line-height:1.6; margin-bottom:7px; }
    .info-card li strong { font-weight:800; }
    .info-card p { font-size:.87rem; color:var(--text-2); font-weight:500; line-height:1.6; }

    /* ── CLINICAL WARNING ── */
    .clin-warn { background:rgba(217,119,6,.09); border:1.5px solid rgba(217,119,6,.25); border-left:4px solid var(--warning); border-radius:var(--r-sm); padding:13px 15px; margin:14px 0; font-size:.85rem; font-weight:600; color:#7c2d12; line-height:1.5; }
    .dark .clin-warn { color:#fed7aa; background:rgba(217,119,6,.12); }
    .clin-warn strong { color:var(--warning); }

    /* ── ABG RESULT BOX ── */
    .result-box { background:var(--theme-light); border:2px solid rgba(var(--theme-rgb),.2); border-radius:var(--r-md); padding:18px; text-align:center; margin-top:20px; }
    .result-lbl { font-size:.75rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; color:var(--theme); }
    .result-val { font-size:2.2rem; font-weight:900; color:var(--text); margin:5px 0; font-family:'Space Mono',monospace; }
    .result-unit { font-size:.84rem; font-weight:600; color:var(--text-2); }

    /* ── ABG INTERPRETATION BOX ── */
    .abg-interp { background:var(--surface-2); border-radius:var(--r-md); padding:13px 15px; margin:14px 0; border-left:4px solid var(--theme); }
    .abg-interp h4 { font-size:.75rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--theme); margin-bottom:5px; }
    .abg-interp p { font-size:.95rem; font-weight:700; color:var(--text); }

    /* ── COMPARE TABLE ── */
    .table-wrap { background:var(--surface); border-radius:var(--r-md); box-shadow:var(--shadow-sm); overflow-x:auto; border:1px solid var(--border); max-width:100%; overscroll-behavior-x:contain; }
    table { width:100%; border-collapse:collapse; min-width:620px; font-size:.85rem; }
    th,td { padding:12px 14px; text-align:left; border-bottom:1px solid var(--border); }
    th { background:var(--surface-2); font-weight:800; color:var(--text-3); text-transform:uppercase; font-size:.72rem; letter-spacing:.06em; position:sticky; top:0; z-index:2; }
    th:first-child,td:first-child { position:sticky; left:0; background:var(--surface-2); z-index:3; font-weight:800; border-right:1px solid var(--border); min-width:130px; }
    td { font-weight:600; color:var(--text); vertical-align:top; }
    td:first-child { background:var(--surface); font-family:'Space Mono',monospace; font-size:.8rem; color:var(--text-2); }
    tr:last-child td { border-bottom:none; }
    .td-sub { display:block; font-size:.75rem; color:var(--text-3); margin-top:3px; font-weight:500; }
    .col-hi { background:rgba(var(--theme-rgb),.05)!important; }

    /* ── PEEP TABLE ── */
    .peep-table table { min-width:100%; text-align:center; }
    .peep-table th,.peep-table td { padding:8px; border:1px solid var(--border); position:static; background:transparent; color:var(--text); }
    .peep-table th { background:var(--surface-2); font-size:.78rem; text-align:center; }
    .peep-table td:first-child { position:static; font-size:.82rem; }

    /* ── DOPE GRID ── */
    .dope-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-top:12px; }
    .dope-item { background:var(--surface-2); border:1px solid var(--border); border-radius:var(--r-sm); padding:14px; text-align:center; }
    .dope-letter { font-size:1.8rem; font-weight:900; color:var(--theme); font-family:'Space Mono',monospace; }
    .dope-word { font-size:.8rem; font-weight:800; color:var(--text); margin-top:2px; }
    .dope-desc { font-size:.75rem; color:var(--text-2); margin-top:4px; line-height:1.4; }

    /* ── GATE LIST (NIV contraindications) ── */
    .gate-list { list-style:none; padding:0; margin:14px 0; }
    .gate-list li { display:flex; align-items:flex-start; gap:10px; padding:10px; border-radius:var(--r-sm); margin-bottom:8px; background:var(--surface-2); border:1px solid var(--border); }
    .gate-list input[type="checkbox"] { width:20px; height:20px; margin-top:2px; accent-color:var(--danger); cursor:pointer; flex-shrink:0; }
    .gate-list label { font-weight:700; font-size:.88rem; cursor:pointer; flex:1; line-height:1.4; color:var(--text); }
    .gate-warn { background:var(--danger-bg); color:var(--danger); padding:12px; border-radius:var(--r-sm); font-weight:700; font-size:.88rem; border-left:3px solid var(--danger); margin-bottom:12px; }

    /* ── BOTTOM NAV ── */
    .bottom-nav { position:fixed; bottom:0; left:0; width:100%; max-width:100vw; background:var(--surface); box-shadow:0 -2px 20px rgba(0,0,0,.09); display:flex; justify-content:space-around; padding:10px 8px calc(10px + env(safe-area-inset-bottom)); z-index:100; border-top:1px solid var(--border); border-top-left-radius:var(--r-xl); border-top-right-radius:var(--r-xl); }
    .nav-it { display:flex; flex-direction:column; align-items:center; gap:4px; color:var(--text-3); font-size:.58rem; font-weight:800; letter-spacing:.03em; text-transform:uppercase; padding:7px 5px; border-radius:var(--r-sm); cursor:pointer; transition:all .2s ease; width:20%; border:none; background:transparent; font-family:inherit; }
    .nav-it .nav-emoji { font-size:1.25rem; line-height:1; transition:transform .25s cubic-bezier(.34,1.56,.64,1); }
    .nav-it.active { color:var(--theme); background:var(--theme-light); }
    .nav-it.active .nav-emoji { transform:scale(1.22); }

    /* ── FAB ── */
    .fab { position:fixed; bottom:calc(80px + env(safe-area-inset-bottom)); right:18px; background:var(--theme); color:white; width:54px; height:54px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.35rem; box-shadow:0 6px 20px rgba(var(--theme-rgb),.45); cursor:pointer; z-index:90; transition:all .3s cubic-bezier(.34,1.56,.64,1); border:none; }
    .fab:hover { transform:scale(1.08); }
    .fab:active { transform:scale(.88); }

    /* ── MODALS ── */
    .modal-bg { position:fixed; inset:0; background:rgba(0,0,0,.58); -webkit-backdrop-filter:blur(6px); backdrop-filter:blur(6px); z-index:500; display:flex; align-items:center; justify-content:center; opacity:0; pointer-events:none; transition:opacity .25s ease; }
    .modal-bg.open { opacity:1; pointer-events:auto; }
    .modal { background:var(--surface); width:92%; max-width:420px; max-height:88vh; overflow-y:auto; border-radius:var(--r-xl); padding:24px; box-shadow:var(--shadow-xl); transform:translateY(22px) scale(.96); transition:transform .32s cubic-bezier(.16,1,.3,1); border:1px solid var(--border); }
    .modal-bg.open .modal { transform:translateY(0) scale(1); }
    .modal-hdr { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
    .modal-hdr h2 { font-size:1.1rem; font-weight:800; color:var(--theme); display:flex; align-items:center; gap:8px; }
    .modal-hdr h2 svg { width:20px; height:20px; }
    .close-x { background:var(--surface-2); border:1px solid var(--border); width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; color:var(--text-2); font-size:.9rem; transition:all .2s; }
    .close-x svg { width:16px; height:16px; pointer-events:none; }
    .close-x:hover { background:var(--danger-bg); color:var(--danger); }

    /* ── FORM ELEMENTS ── */
    .calc-field { margin-bottom:15px; }
    .calc-label { display:block; font-size:.77rem; font-weight:800; letter-spacing:.06em; text-transform:uppercase; color:var(--text-2); margin-bottom:7px; }
    .calc-input { width:100%; padding:13px 14px; border:2px solid var(--border); border-radius:var(--r-sm); font-family:'Space Mono',monospace; font-size:.97rem; font-weight:700; color:var(--text); background:var(--surface-2); transition:all .2s ease; }
    .calc-input:focus { outline:none; border-color:var(--theme); background:var(--surface); box-shadow:var(--focus-ring); }
    .calc-input:invalid:not(:placeholder-shown) { border-color:var(--danger); }
    .input-hint { font-size:.78rem; color:var(--text-3); margin-top:4px; font-weight:600; }
    .input-error { font-size:.78rem; color:var(--danger); margin-top:4px; font-weight:700; display:none; }
    .input-error.show { display:block; }

    .seg { display:flex; background:var(--surface-2); border-radius:var(--r-sm); padding:3px; gap:3px; border:1px solid var(--border); }
    .seg-btn { flex:1; padding:10px; border:none; background:transparent; font-family:inherit; font-weight:700; font-size:.88rem; color:var(--text-2); border-radius:8px; cursor:pointer; transition:all .2s ease; }
    .seg-btn.active { background:var(--surface); color:var(--theme); box-shadow:var(--shadow-xs); }
    .input-row { display:flex; gap:10px; align-items:center; }
    .input-row .seg { flex:0 0 112px; }

    /* ── PBW RESULT ── */
    .pbw-result { background:var(--theme-light); border:2px solid rgba(var(--theme-rgb),.2); border-radius:var(--r-md); padding:16px; text-align:center; margin-top:20px; }
    .pbw-lbl { font-size:.72rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; color:var(--theme); }
    .pbw-val { font-size:2rem; font-weight:900; color:var(--text); margin:4px 0; font-family:'Space Mono',monospace; }
    .pbw-sub { font-size:.82rem; color:var(--text-2); font-weight:600; line-height:1.5; }
    .calc-note { font-size:.8rem; color:var(--text-3); margin-bottom:18px; font-weight:500; line-height:1.5; }
    .modal-btn-row { display:flex; gap:10px; margin-top:14px; }
    .modal-btn-row .ehr-btn { margin-top:0; flex:1; }

    /* ── DISCLAIMER ── */
    #disclaimerModal .modal { max-width:410px; }
    .disc-icon { font-size:2.4rem; text-align:center; margin-bottom:10px; }
    .disc-title { font-size:1.2rem; font-weight:800; color:var(--danger); text-align:center; display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:10px; }
    .disc-title svg { width:22px; height:22px; }
    .disc-body { font-size:.87rem; color:var(--text-2); line-height:1.65; text-align:center; }
    .disc-body strong { color:var(--text); font-weight:800; }


    /* ── TOOLS VIEW ── */
    .tools-row { display:flex; gap:10px; align-items:flex-start; flex-wrap:wrap; }
    .tools-row .calc-field { min-width:70px; }

    .tool-result { background:var(--theme-light); border:2px solid rgba(var(--theme-rgb),.2); border-radius:var(--r-md); padding:14px 16px; text-align:center; margin-top:14px; transition:background .3s; }
    .tr-label { font-size:.72rem; font-weight:800; letter-spacing:.07em; text-transform:uppercase; color:var(--theme); margin-bottom:2px; }
    .tr-val { font-size:2rem; font-weight:900; color:var(--text); font-family:'Space Mono',monospace; line-height:1.1; }
    .tr-unit { font-size:.82rem; font-weight:600; color:var(--text-2); margin-top:2px; }
    .tr-badge { display:inline-block; margin-top:8px; padding:4px 12px; border-radius:var(--r-full); font-size:.75rem; font-weight:800; letter-spacing:.05em; text-transform:uppercase; }
    .tr-badge.ok     { background:rgba(22,163,74,.12); color:#16a34a; border:1px solid rgba(22,163,74,.25); }
    .tr-badge.warn   { background:rgba(217,119,6,.12);  color:#d97706; border:1px solid rgba(217,119,6,.25); }
    .tr-badge.danger { background:rgba(220,38,38,.12); color:#dc2626; border:1px solid rgba(220,38,38,.25); }
    select.calc-input { -webkit-appearance:none; appearance:none; cursor:pointer; }
    .micro-note { font-size:.78rem; color:var(--text-3); font-weight:600; line-height:1.5; margin-top:8px; }
    .pill-row { display:flex; gap:8px; flex-wrap:wrap; margin-top:12px; }
    .pill-btn {
      border:2px solid var(--border); background:var(--surface); color:var(--text-2);
      padding:9px 12px; border-radius:var(--r-full); font-size:.78rem; font-weight:800; cursor:pointer;
      transition:all .18s ease; font-family:inherit;
    }
    .pill-btn.active { background:var(--theme); color:white; border-color:var(--theme); box-shadow:0 4px 10px rgba(var(--theme-rgb),.24); }
    .simple-grid { display:grid; grid-template-columns:1fr; gap:10px; }
    @media(min-width:620px){ .simple-grid { grid-template-columns:1fr 1fr; } }
    .mini-card {
      background:var(--surface-2); border:1px solid var(--border); border-radius:var(--r-sm);
      padding:12px 13px;
    }
    .mini-card h4 { font-size:.77rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--theme); margin-bottom:7px; }
    .mini-card p, .mini-card li { font-size:.82rem; color:var(--text-2); font-weight:600; line-height:1.55; }
    .mini-card ul { padding-left:16px; }
    .mini-card li { margin-bottom:5px; }
    .stack-list { display:flex; flex-direction:column; gap:10px; margin-top:12px; }
    .stack-item {
      background:var(--surface-2); border:1px solid var(--border); border-left:4px solid var(--theme);
      border-radius:var(--r-sm); padding:12px 13px;
    }
    .stack-item strong { display:block; font-size:.8rem; color:var(--text); margin-bottom:4px; }
    .stack-item p { font-size:.82rem; color:var(--text-2); line-height:1.5; font-weight:600; }
    .alert-box {
      background:rgba(220,38,38,.08); border:1px solid rgba(220,38,38,.22); border-left:4px solid #dc2626;
      color:#991b1b; padding:12px 13px; border-radius:var(--r-sm); font-size:.83rem; font-weight:700; line-height:1.5;
    }
    .dark .alert-box { color:#fecaca; }
    .ok-box {
      background:rgba(22,163,74,.08); border:1px solid rgba(22,163,74,.22); border-left:4px solid #16a34a;
      color:#166534; padding:12px 13px; border-radius:var(--r-sm); font-size:.83rem; font-weight:700; line-height:1.5;
    }
    .dark .ok-box { color:#bbf7d0; }
    .ref-table table { min-width:100%; table-layout:fixed; }
    .ref-table th,.ref-table td { position:static; padding:9px 10px; }
    .ref-table th:first-child,.ref-table td:first-child { position:static; min-width:0; background:var(--surface-2); }
    .flow-svg { width:100%; height:86px; background:var(--surface-2); border-radius:var(--r-sm); border:1px solid var(--border); margin-top:10px; }
    details.mini-accordion {
      background:var(--surface-2); border:1px solid var(--border); border-radius:var(--r-sm); padding:12px 13px; margin-top:10px;
    }
    details.mini-accordion summary { cursor:pointer; font-weight:800; color:var(--text); font-size:.84rem; }
    details.mini-accordion[open] summary { margin-bottom:9px; }
    .drug-grid { display:grid; grid-template-columns:1fr; gap:10px; margin-top:10px; }
    @media(min-width:720px){ .drug-grid { grid-template-columns:1fr 1fr; } }
    .drug-card {
      background:var(--surface-2); border:1px solid var(--border); border-radius:var(--r-sm); padding:12px 13px;
    }
    .drug-card h4 { font-size:.82rem; font-weight:800; color:var(--text); margin-bottom:6px; }
    .drug-card p { font-size:.8rem; color:var(--text-2); font-weight:600; line-height:1.5; }
    .score-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    @media(max-width:520px){ .score-grid { grid-template-columns:1fr; } }

    /* Berlin bar */
    .berlin-track { display:flex; height:14px; border-radius:var(--r-full); overflow:hidden; gap:2px; }
    .berlin-seg { height:100%; border-radius:3px; }
    .berlin-labels { display:flex; justify-content:space-between; font-size:.68rem; font-weight:700; color:var(--text-3); margin-top:5px; }
    .berlin-arrow { font-size:1.1rem; text-align:center; margin-top:4px; transition:margin-left .4s cubic-bezier(.34,1.56,.64,1); }

    /* RASS grid */
    .rass-grid { display:flex; flex-direction:column; gap:5px; }
    .rass-item { display:flex; align-items:center; gap:10px; padding:9px 12px; border-radius:var(--r-sm); border:1.5px solid var(--border); cursor:pointer; transition:all .18s ease; background:var(--surface-2); }
    .rass-item:hover { transform:translateX(3px); }
    .rass-item.open { border-color:var(--rass-color); background:rgba(var(--rass-rgb),.08); }
    .rass-score { font-family:'Space Mono',monospace; font-weight:900; font-size:1rem; width:32px; text-align:center; flex-shrink:0; }
    .rass-name { font-size:.82rem; font-weight:800; color:var(--text); flex:1; }
    .rass-desc { font-size:.75rem; color:var(--text-2); font-weight:500; }
    .rass-detail { display:none; padding:8px 10px; background:var(--bg); border-radius:var(--r-sm); margin-top:6px; font-size:.8rem; color:var(--text-2); font-weight:600; line-height:1.5; }
    .rass-arrow { color:var(--text-3); font-size:.75rem; transition:transform .2s; flex-shrink:0; }
    .rass-item.open .rass-detail { display:block; }

    /* Post-intubation checklist */
    .checklist-item { display:flex; align-items:flex-start; gap:11px; padding:10px 12px; border-radius:var(--r-sm); margin-bottom:7px; border:1.5px solid var(--border); background:var(--surface-2); cursor:pointer; transition:all .18s ease; }
    .checklist-item.done { background:rgba(22,163,74,.07); border-color:rgba(22,163,74,.3); }
    .checklist-item.done .ci-check { background:#16a34a; border-color:#16a34a; color:white; }
    .checklist-item.done .ci-text { color:var(--text-3); text-decoration:line-through; }
    .ci-check { width:22px; height:22px; border-radius:6px; border:2px solid var(--border); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:.8rem; transition:all .18s; }
    .ci-text { font-size:.86rem; font-weight:700; color:var(--text); line-height:1.4; }
    .ci-sub { font-size:.75rem; color:var(--text-3); font-weight:500; margin-top:2px; }
    .checklist-progress { display:flex; align-items:center; gap:10px; margin-bottom:12px; }
    .cp-bar { flex:1; height:6px; background:var(--border); border-radius:99px; overflow:hidden; }
    .cp-fill { height:100%; background:var(--success); border-radius:99px; transition:width .3s ease; }
    .cp-text { font-size:.78rem; font-weight:800; color:var(--text-2); white-space:nowrap; }

    /* ── FEATURE GATING ── */
    .nav-it.feature-locked { opacity:0.4; position:relative; }
    .nav-it.feature-locked .lock-badge { position:absolute; top:0; right:2px; font-size:.55rem; line-height:1; }
    .feature-locked-el { opacity:0.35; filter:grayscale(0.3); pointer-events:none; position:relative; }

    /* ── PRINT ── */
    @media print {
      html,body { height:auto; max-width:none; overflow:visible; }
      body { position:static; inset:auto; overflow:visible; background:white; color:black; }
      .header,.bottom-nav,.fab,.chips-outer,.scenario-select-wrap,.ehr-btn,.modal-bg,.toast-wrap,.contra-banner { display:none!important; }
      .content { overflow:visible; padding:0; }
      .view { display:block!important; animation:none; }
      .view:not(.active) { display:none!important; }
      .pcard,.info-card,.waveform-card { break-inside:avoid; box-shadow:none; border:1px solid #ccc; }
      .feature-locked-el { display:none!important; }
    }

    @media(max-width:640px){
      .scenario-select-wrap { display:block; }
      .chips-outer { display:none; }
      .stats-bar {
        display:grid;
        grid-template-columns:repeat(2, minmax(0,1fr));
        overflow:visible;
        padding:4px 0 14px;
      }
      .stat-pill {
        min-width:0;
        white-space:normal;
        align-items:flex-start;
        line-height:1.35;
        padding:9px 12px;
      }
      .stat-pill:last-child { grid-column:1 / -1; }
      .table-wrap { overflow-x:auto; -webkit-overflow-scrolling:touch; }
      table { min-width:620px; table-layout:auto; }
      th,td { padding:10px 8px; word-break:break-word; }
      th:first-child,td:first-child { min-width:96px; }
      .ref-table table,.peep-table table { min-width:100%; table-layout:fixed; }
    }

    /* ── UTILITIES ── */
    .mt-4 { margin-top:14px; }
    .divider { border:none; border-top:1px solid var(--border); margin:18px 0; }
    .text-muted-center { text-align:center; font-size:.78rem; color:var(--text-3); margin-top:7px; font-weight:500; }

    /* ── COMPARE — INTERACTIVE SPOTLIGHT ── */
    .cmp-hero { background:var(--surface); border-radius:var(--r-md); padding:15px 17px; margin-bottom:14px; border:1px solid var(--border); box-shadow:var(--shadow-xs); }
    .cmp-hero-title { font-size:1rem; font-weight:800; color:var(--theme); margin-bottom:3px; transition:color .3s; }
    .cmp-hero-sub { font-size:.83rem; color:var(--text-2); font-weight:500; }

    /* Parameter pill selector */
    .param-pill-row { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
    .param-pill {
      padding:8px 15px; border-radius:var(--r-full); border:2px solid var(--border);
      background:var(--surface); color:var(--text-2); font-size:.8rem; font-weight:800;
      cursor:pointer; transition:all .2s ease; display:flex; align-items:center; gap:6px;
      font-family:inherit; letter-spacing:.01em;
    }
    .param-pill:hover { border-color:var(--theme); color:var(--theme); transform:translateY(-1px); }
    .param-pill.active {
      background:var(--theme); color:white; border-color:var(--theme);
      box-shadow:0 4px 12px rgba(var(--theme-rgb),.32);
    }

    /* Compare grid — 2 cols on mobile, 3 on wide */
    .cmp-grid { display:grid; grid-template-columns:1fr 1fr; gap:11px; }
    @media(min-width:600px) { .cmp-grid { grid-template-columns:1fr 1fr 1fr; } }

    .cmp-card {
      background:var(--surface); border-radius:var(--r-md); padding:14px 13px;
      border:2px solid var(--border); box-shadow:var(--shadow-xs);
      transition:all .25s ease; cursor:default; position:relative; overflow:hidden;
      animation:cmpFadeIn .3s ease forwards; opacity:0;
    }
    @keyframes cmpFadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:translateY(0)} }
    .cmp-card:nth-child(1) { animation-delay:.04s; }
    .cmp-card:nth-child(2) { animation-delay:.08s; }
    .cmp-card:nth-child(3) { animation-delay:.12s; }
    .cmp-card:nth-child(4) { animation-delay:.16s; }
    .cmp-card:nth-child(5) { animation-delay:.20s; }
    .cmp-card:nth-child(6) { animation-delay:.24s; }
    .cmp-card.is-active { border-color:var(--c-card); box-shadow:0 4px 16px rgba(var(--rgb-card),.25); }
    .cmp-card::before {
      content:''; position:absolute; top:0; left:0; right:0; height:3px;
      background:var(--c-card,var(--border)); transition:background .3s;
    }
    .cmp-scenario-head { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
    .cmp-scenario-emoji { font-size:1.2rem; line-height:1; }
    .cmp-scenario-name { font-size:.78rem; font-weight:800; color:var(--text-2); letter-spacing:.01em; flex:1; min-width:0; line-height:1.2; }
    .cmp-val { font-size:.98rem; font-weight:900; color:var(--text); font-family:'Space Mono',monospace; line-height:1.25; margin-bottom:5px; word-break:break-word; }
    .cmp-val .highlight { color:var(--c-card,var(--theme)); }
    .cmp-note { font-size:.73rem; color:var(--text-3); font-weight:500; line-height:1.4; }
    .cmp-badge {
      display:inline-block; font-size:.63rem; font-weight:800; letter-spacing:.05em;
      text-transform:uppercase; padding:2px 7px; border-radius:var(--r-full);
      background:rgba(var(--rgb-card,var(--theme-rgb)),.12);
      color:var(--c-card,var(--theme)); margin-bottom:6px;
    }

    /* Insight bar */
    .cmp-insight {
      background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--theme);
      border-radius:var(--r-md); padding:13px 15px; margin-top:14px;
      display:flex; align-items:flex-start; gap:10px; font-size:.85rem;
      font-weight:600; color:var(--text-2); line-height:1.5;
      animation:cmpFadeIn .35s ease forwards;
    }
    .cmp-insight-icon { font-size:1.1rem; flex-shrink:0; margin-top:1px; }

    /* ═══════════════════════════════════════════════════
       COMPARE — REDESIGNED (3 modes)
    ═══════════════════════════════════════════════════ */

    /* Mode bar */
    .cmp-mode-bar { display:flex; gap:6px; padding:4px; background:var(--surface); border-radius:var(--r-md); border:1px solid var(--border); box-shadow:var(--shadow-xs); margin-bottom:14px; }
    .cmp-mode-btn { flex:1; padding:9px 6px; border:none; background:transparent; font-family:inherit; font-size:.78rem; font-weight:800; color:var(--text-3); border-radius:var(--r-sm); cursor:pointer; transition:all .2s ease; letter-spacing:.01em; }
    .cmp-mode-btn.active { background:var(--theme); color:white; box-shadow:0 2px 8px rgba(var(--theme-rgb),.35); }
    .cmp-mode-btn:not(.active):hover { background:var(--surface-2); color:var(--text-2); }

    /* Panes */
    .cmp-pane { display:none; }
    .cmp-pane.active { display:block; animation:slideUp .3s ease; }
    .cmp-intro { font-size:.82rem; color:var(--text-3); font-weight:600; margin-bottom:14px; line-height:1.5; }

    /* ── MODE 1: OVERVIEW cards ── */
    .ov-card {
      background:var(--surface); border-radius:var(--r-md); margin-bottom:12px;
      border:1.5px solid var(--border); box-shadow:var(--shadow-xs);
      overflow:hidden; transition:box-shadow .2s ease;
    }
    .ov-card:hover { box-shadow:var(--shadow-md); }
    .ov-header {
      padding:14px 16px; display:flex; align-items:center; gap:12px;
      cursor:pointer; -webkit-user-select:none; user-select:none;
    }
    .ov-color-bar { width:5px; height:100%; border-radius:3px; position:absolute; left:0; top:0; bottom:0; }
    .ov-emoji { font-size:1.5rem; line-height:1; flex-shrink:0; }
    .ov-title-block { flex:1; min-width:0; }
    .ov-name { font-size:.95rem; font-weight:800; color:var(--text); }
    .ov-sub  { font-size:.75rem; color:var(--text-3); font-weight:600; margin-top:1px; }
    .ov-chevron { font-size:.8rem; color:var(--text-3); transition:transform .25s ease; flex-shrink:0; }
    .ov-card.open .ov-chevron { transform:rotate(180deg); }
    .ov-key-rule {
      font-size:.78rem; font-weight:800; padding:3px 9px; border-radius:var(--r-full);
      letter-spacing:.04em; text-transform:uppercase;
    }

    .ov-body { display:none; border-top:1px solid var(--border); }
    .ov-card.open .ov-body { display:block; }

    /* Param grid inside overview */
    .ov-params { display:grid; grid-template-columns:1fr 1fr; gap:0; }
    .ov-param { padding:10px 14px; border-bottom:1px solid var(--border); border-right:1px solid var(--border); }
    .ov-param:nth-child(even) { border-right:none; }
    .ov-param:nth-last-child(-n+2) { border-bottom:none; }
    .ov-param-key { font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; font-weight:800; color:var(--text-3); margin-bottom:3px; }
    .ov-param-val { font-size:.9rem; font-weight:800; color:var(--text); font-family:'Space Mono',monospace; line-height:1.3; }
    .ov-param-note { font-size:.72rem; color:var(--text-2); font-weight:500; margin-top:2px; line-height:1.4; }

    /* Danger / pearl zones */
    .ov-zones { display:grid; grid-template-columns:1fr 1fr; gap:0; border-top:1px solid var(--border); }
    .ov-zone { padding:11px 14px; }
    .ov-zone:first-child { border-right:1px solid var(--border); }
    .ov-zone-title { font-size:.68rem; text-transform:uppercase; letter-spacing:.06em; font-weight:800; margin-bottom:6px; display:flex; align-items:center; gap:5px; }
    .ov-zone-danger { color:#dc2626; }
    .ov-zone-pearl  { color:#059669; }
    .ov-zone ul { padding-left:12px; }
    .ov-zone li { font-size:.75rem; color:var(--text-2); font-weight:600; line-height:1.5; margin-bottom:3px; }

    /* Evidence strip */
    .ov-evidence { padding:10px 14px; background:var(--surface-2); display:flex; align-items:center; gap:8px; flex-wrap:wrap; border-top:1px solid var(--border); }
    .ov-ev-label { font-size:.68rem; font-weight:800; color:var(--text-3); text-transform:uppercase; letter-spacing:.05em; }
    .ov-ev-tag { font-size:.68rem; font-weight:700; padding:2px 8px; border-radius:var(--r-full); background:var(--theme-light); color:var(--theme); border:1px solid rgba(var(--theme-rgb),.2); transition:background .3s,color .3s; }
    .ov-grade { font-size:.68rem; font-weight:800; padding:2px 8px; border-radius:var(--r-full); margin-left:auto; }
    .ov-grade.A { background:rgba(22,163,74,.12); color:#16a34a; border:1px solid rgba(22,163,74,.25); }
    .ov-grade.B { background:rgba(217,119,6,.12);  color:#d97706; border:1px solid rgba(217,119,6,.25); }
    .ov-grade.C { background:rgba(100,116,139,.12);color:#64748b; border:1px solid rgba(100,116,139,.25);}

    /* Go to scenario button */
    .ov-goto { width:100%; background:none; border:none; border-top:1px solid var(--border); padding:10px 16px; font-family:inherit; font-size:.8rem; font-weight:800; color:var(--theme); cursor:pointer; text-align:center; transition:background .18s; }
    .ov-goto:hover { background:var(--theme-light); }

    /* ── MODE 2: DUEL ── */
    .duel-selector { display:grid; grid-template-columns:1fr auto 1fr; gap:10px; align-items:center; margin-bottom:16px; }
    .duel-pick { background:var(--surface); border:2px solid var(--border); border-radius:var(--r-md); padding:12px; }
    .dp-label { font-size:.68rem; font-weight:800; text-transform:uppercase; letter-spacing:.06em; color:var(--text-3); margin-bottom:4px; }
    .dp-value { font-size:.85rem; font-weight:800; color:var(--text-2); margin-bottom:8px; }
    .dp-chips { display:flex; flex-direction:column; gap:5px; }
    .dp-chip { padding:6px 10px; border-radius:var(--r-sm); border:1.5px solid var(--border); background:var(--surface-2); font-size:.75rem; font-weight:700; cursor:pointer; transition:all .18s; color:var(--text-2); display:flex; align-items:center; gap:6px; }
    .dp-chip:hover { border-color:var(--theme); color:var(--theme); }
    .dp-chip.selected { background:var(--theme); color:white; border-color:var(--theme); }
    .duel-vs { font-size:1rem; font-weight:900; color:var(--text-3); text-align:center; }

    /* Duel table */
    .duel-table { background:var(--surface); border-radius:var(--r-md); border:1px solid var(--border); overflow:hidden; box-shadow:var(--shadow-sm); }
    .duel-col-headers { display:grid; grid-template-columns:90px 1fr 1fr; background:var(--surface-2); border-bottom:2px solid var(--border); }
    .duel-col-hdr { padding:10px 12px; font-size:.72rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; color:var(--text-3); display:flex; align-items:center; gap:6px; }
    .duel-col-hdr:not(:first-child) { border-left:1px solid var(--border); }
    .duel-col-hdr .duel-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }

    .duel-row { display:grid; grid-template-columns:90px 1fr 1fr; border-bottom:1px solid var(--border); }
    .duel-row:last-child { border-bottom:none; }
    .duel-row.highlight-row { background:rgba(var(--theme-rgb),.04); }
    .duel-cell { padding:11px 12px; font-size:.82rem; font-weight:700; color:var(--text); vertical-align:top; }
    .duel-cell:not(:first-child) { border-left:1px solid var(--border); }
    .duel-cell-key { font-size:.72rem; font-weight:800; color:var(--text-3); text-transform:uppercase; letter-spacing:.04em; font-family:'Space Mono',monospace; }
    .duel-val { font-family:'Space Mono',monospace; font-size:.85rem; font-weight:800; color:var(--text); line-height:1.3; }
    .duel-rationale { font-size:.72rem; color:var(--text-2); font-weight:500; margin-top:3px; line-height:1.4; }
    .duel-winner { display:inline-block; font-size:.6rem; font-weight:800; text-transform:uppercase; padding:1px 6px; border-radius:var(--r-full); margin-top:4px; letter-spacing:.04em; }
    .duel-winner.key  { background:rgba(220,38,38,.12); color:#dc2626; border:1px solid rgba(220,38,38,.2); }
    .duel-winner.diff { background:rgba(217,119,6,.12);  color:#d97706; border:1px solid rgba(217,119,6,.2); }
    .duel-winner.same { background:var(--surface-2); color:var(--text-3); border:1px solid var(--border); }
    .duel-empty { padding:40px 20px; text-align:center; color:var(--text-3); font-size:.88rem; font-weight:600; }

    /* ── MODE 3: SPOTLIGHT ── */
    /* (reuses existing param-pill-row, cmp-grid, cmp-insight, cmp-card CSS) */
    /* Enhanced cmp-card */
    .cmp-card {
      background:var(--surface); border-radius:var(--r-md); padding:13px 12px;
      border:2px solid var(--border); box-shadow:var(--shadow-xs);
      transition:all .22s ease; position:relative; overflow:hidden;
      animation:cmpFadeIn .3s ease forwards; opacity:0;
      cursor:pointer;
    }
    .cmp-card::before { content:''; position:absolute; top:0; left:0; right:0; height:3px; background:var(--c-card,var(--border)); }
    .cmp-card:hover { transform:translateY(-2px); box-shadow:var(--shadow-md); }
    .cmp-card.is-active { border-color:var(--c-card); box-shadow:0 3px 12px rgba(var(--rgb-card),.22); }
    @keyframes cmpFadeIn { from{opacity:0;transform:translateY(7px)} to{opacity:1;transform:translateY(0)} }
    .cmp-card:nth-child(1){animation-delay:.04s} .cmp-card:nth-child(2){animation-delay:.08s}
    .cmp-card:nth-child(3){animation-delay:.12s} .cmp-card:nth-child(4){animation-delay:.16s}
    .cmp-card:nth-child(5){animation-delay:.2s}  .cmp-card:nth-child(6){animation-delay:.24s}
    .cmp-scenario-head { display:flex; align-items:center; gap:7px; margin-bottom:8px; }
    .cmp-scenario-emoji { font-size:1.1rem; line-height:1; }
    .cmp-scenario-name { font-size:.75rem; font-weight:800; color:var(--text-2); line-height:1.2; flex:1; }
    .cmp-val { font-size:.95rem; font-weight:900; color:var(--text); font-family:'Space Mono',monospace; line-height:1.25; margin-bottom:4px; word-break:break-word; }
    .cmp-note { font-size:.7rem; color:var(--text-3); font-weight:500; line-height:1.4; }
    .cmp-rationale { font-size:.7rem; color:var(--text-2); font-weight:600; line-height:1.4; margin-top:5px; padding-top:5px; border-top:1px solid var(--border); }
    .cmp-badge { display:inline-block; font-size:.6rem; font-weight:800; text-transform:uppercase; letter-spacing:.05em; padding:2px 7px; border-radius:var(--r-full); background:rgba(var(--rgb-card),.12); color:var(--c-card); margin-bottom:6px; }
    .cmp-pitfall { font-size:.68rem; color:#dc2626; font-weight:700; margin-top:5px; padding-top:5px; border-top:1px solid var(--danger-border); display:flex; gap:4px; }

    /* Insight bar */
    .cmp-insight { background:var(--surface); border:1px solid var(--border); border-left:4px solid var(--theme); border-radius:var(--r-md); padding:12px 15px; margin-top:13px; display:flex; align-items:flex-start; gap:9px; font-size:.83rem; font-weight:600; color:var(--text-2); line-height:1.5; animation:cmpFadeIn .35s ease; }
    .cmp-insight-icon { font-size:1rem; flex-shrink:0; margin-top:1px; }

    /* Param pills */
    .param-pill-row { display:flex; gap:7px; flex-wrap:wrap; margin-bottom:14px; }
    .param-pill { padding:7px 13px; border-radius:var(--r-full); border:2px solid var(--border); background:var(--surface); color:var(--text-2); font-size:.78rem; font-weight:800; cursor:pointer; transition:all .18s ease; display:flex; align-items:center; gap:5px; font-family:inherit; }
    .param-pill:hover { border-color:var(--theme); color:var(--theme); }
    .param-pill.active { background:var(--theme); color:white; border-color:var(--theme); box-shadow:0 3px 10px rgba(var(--theme-rgb),.3); }
    .cmp-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    @media(min-width:600px){ .cmp-grid { grid-template-columns:1fr 1fr 1fr; } }

  </style>

  <script>
    /* Prevent dark mode flash */
    (function(){
      try {
        const s = JSON.parse(localStorage.getItem('edvpro_state'));
        if(s&&s.dark || (!s && window.matchMedia('(prefers-color-scheme:dark)').matches)) {
          document.documentElement.classList.add('dark');
        }
      } catch(e){}
    })();
  </script>
</head>
<body>

<!-- ████ TOAST ████ -->
<div id="toastWrap" class="toast-wrap" role="region" aria-live="polite" aria-label="Notifications"></div>

<!-- ████ DISCLAIMER MODAL ████ -->
<div class="modal-bg open" id="disclaimerModal" role="dialog" aria-modal="true" aria-labelledby="discTitle">
  <div class="modal">
    <div class="disc-icon">⚕️</div>
    <div class="disc-title" id="discTitle">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      Medical Disclaimer
    </div>
    <p class="disc-body">
      This app is for <strong>educational &amp; quick-reference use only</strong>.<br><br>
      It is <strong>not</strong> a substitute for clinical judgment, institutional protocols, or licensed medical training. All calculations must be verified by a qualified clinician.
      <br><br>
      <strong>Not for pediatric patients (&lt;18 yrs). For trained clinicians only.</strong>
    </p>
    <button class="ehr-btn" id="acceptBtn" style="background:var(--danger);margin-top:20px;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:18px;height:18px;"><polyline points="20 6 9 17 4 12"/></svg>
      I Understand — Continue
    </button>
  </div>
</div>

<!-- ████ NIV GATE MODAL ████ -->
<div class="modal-bg" id="gateModal" role="dialog" aria-modal="true" aria-labelledby="gateTitle">
  <div class="modal">
    <div class="modal-hdr">
      <h2 id="gateTitle" style="color:var(--danger);">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/></svg>
        😷 NIV Safety Gate
      </h2>
      <button class="close-x" id="closeGate" aria-label="Close safety gate">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <p style="font-size:.88rem;color:var(--text-2);font-weight:600;margin-bottom:14px;">Confirm <strong>none</strong> of the following major contraindications are present before initiating NIV:</p>
    <ul class="gate-list" id="gateList">
      <li><input type="checkbox" id="g1"><label for="g1">🧠 Altered mental status / GCS &lt; 8</label></li>
      <li><input type="checkbox" id="g2"><label for="g2">💔 Hemodynamic instability (SBP &lt; 90, pressors needed)</label></li>
      <li><input type="checkbox" id="g3"><label for="g3">🤕 Facial trauma, burns, or upper airway obstruction</label></li>
      <li><input type="checkbox" id="g4"><label for="g4">⚠️ Severe acidosis (pH &lt; 7.10) or apnea</label></li>
      <li><input type="checkbox" id="g5"><label for="g5">🤮 Inability to protect airway / copious vomiting</label></li>
    </ul>
    <div class="gate-warn hidden" id="gateWarn">⛔ NIV may be unsafe here — escalate to the airway/critical care plan and consider intubation or alternate support.</div>
    <button class="ehr-btn" id="gateProceed" disabled>✅ Proceed to NIV Settings</button>
  </div>
</div>

<!-- ████ PBW CALCULATOR MODAL ████ -->
<div class="modal-bg" id="calcModal" role="dialog" aria-modal="true" aria-labelledby="calcTitle">
  <div class="modal">
    <div class="modal-hdr">
      <h2 id="calcTitle">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="8" y1="6" x2="16" y2="6"/><line x1="8" y1="10" x2="16" y2="10"/><line x1="8" y1="14" x2="16" y2="14"/></svg>
        ⚖️ PBW Calculator
      </h2>
      <button class="close-x" id="closeCalc" aria-label="Close calculator">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
    <p class="calc-note">ARDSNet formula — results <strong>auto-sync tidal volumes</strong> across all scenarios instantly.</p>

    <div class="calc-field">
      <label class="calc-label">🧬 Biological Sex</label>
      <div class="seg" id="sexToggle" role="radiogroup" aria-label="Biological sex">
        <button class="seg-btn active" data-val="male" role="radio" aria-checked="true">♂ Male</button>
        <button class="seg-btn" data-val="female" role="radio" aria-checked="false">♀ Female</button>
      </div>
    </div>

    <div class="calc-field">
      <label class="calc-label">📏 Height
        <span class="term" style="margin-left:8px;font-size:.75rem;" tabindex="0" onclick="App.toast('💡 Pregnancy: use pre-pregnancy height — not current weight — for PBW.')">ℹ️ Preg note</span>
      </label>
      <div class="input-row">
        <input type="number" id="heightInput" class="calc-input" placeholder="e.g. 175" inputmode="decimal" style="flex:1" aria-describedby="heightHint">
        <div class="seg" id="unitToggle" role="radiogroup" aria-label="Unit" style="flex:0 0 112px;">
          <button class="seg-btn active" data-val="cm" role="radio" aria-checked="true">cm</button>
          <button class="seg-btn" data-val="in" role="radio" aria-checked="false">in</button>
        </div>
      </div>
      <div class="input-hint" id="heightHint">Enter height to auto-calculate tidal volumes.</div>
      <div class="input-error" id="heightErr">Height must be 120–250 cm (47–98 in)</div>
    </div>

    <div class="pbw-result">
      <div class="pbw-lbl">🏋️ Predicted Body Weight</div>
      <div class="pbw-val" id="pbwVal">-- kg</div>
      <div class="pbw-sub" id="vtVal">Enter height above to calculate<br>Target VT (6–8 mL/kg)</div>
    </div>

    <div class="modal-btn-row">
      <button class="ehr-btn secondary" id="resetCalc">🔄 Reset</button>
      <button class="ehr-btn" id="applyCalc" style="flex:2;">✅ Apply & Close</button>
    </div>
  </div>
</div>

<!-- ████ UPGRADE MODAL ████ -->
<div class="modal-bg" id="upgradeModal" role="dialog" aria-modal="true">
  <div class="modal" style="text-align:center;">
    <div style="font-size:2.4rem;margin-bottom:10px;">🔒</div>
    <h2 style="font-size:1.2rem;font-weight:800;color:var(--theme);margin-bottom:10px;">Premium Feature</h2>
    <p style="font-size:.87rem;color:var(--text-2);line-height:1.65;margin-bottom:20px;"><strong id="upgradeName">This feature</strong> requires a higher subscription plan.</p>
    <a href="<?= APP_URL ?>/subscribe" class="ehr-btn" style="margin-bottom:8px;">⬆️ Upgrade Plan</a>
    <button class="ehr-btn secondary" id="closeUpgrade">✖️ Close</button>
  </div>
</div>

<!-- ████ HEADER ████ -->
<header class="header" id="appHeader">
  <div class="header-inner">
    <div>
      <div class="header-title">🫁 <span id="hTitle">ED VentGuide</span></div>
      <div class="header-sub" id="hSub">Emergency Ventilation Reference</div>
      <div class="header-badge" id="hBadge">🏥 STANDARD INITIATION</div>
    </div>
    <div class="header-actions">
      <button class="hbtn" id="printBtn" data-feature="print" data-feature-name="Print Pocket Card" aria-label="Print pocket card" title="Print">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      </button>
      <button class="hbtn" id="darkToggle" aria-label="Toggle dark mode" title="Toggle dark mode">
        <svg id="moonIcon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        <svg id="sunIcon"  viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:none;"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/></svg>
      </button>
    </div>
  </div>
</header>

<!-- ████ MAIN ████ -->
<main class="content">

  <!-- ── SCENARIOS ── -->
  <section id="view-scenarios" class="view active" aria-label="Ventilation scenarios">
    <div class="scenario-select-wrap">
      <label class="scenario-select-label" for="scenarioSelect">Scenario</label>
      <div class="scenario-select-shell">
        <select id="scenarioSelect" class="scenario-select" aria-label="Select patient scenario"></select>
      </div>
    </div>
    <div class="chips-outer">
      <div class="chips-wrap" id="chipsWrap" role="tablist" aria-label="Patient scenarios"></div>
    </div>

    <!-- NIV Contraindication Banner -->
    <div class="contra-banner hidden" id="contraBanner">
      <h3><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:17px;height:17px;"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/></svg> ⛔ Contraindication Check Required</h3>
      <p>Verify NIV safety criteria before applying these settings.</p>
      <button class="ehr-btn" id="openGateBtn" style="background:var(--danger);margin-top:12px;max-width:260px;">Open Safety Gate</button>
    </div>

    <!-- Safety Alert -->
    <div class="safety-alert" id="safetyAlert">
      <div class="safety-icon">🚨</div>
      <div>
        <div class="safety-label">⚠️ Critical Safety Target</div>
        <div class="safety-title" id="safetyTitle">Keep Pplat ≤ 30 cmH₂O</div>
        <div class="safety-note" id="safetyNote">Plateau pressure is the major safety target.</div>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="stats-bar" id="statsBar">
      <div class="stat-pill">⚖️ PBW: <span class="sp-val" id="pbwStatus">Not set</span></div>
      <div class="stat-pill">🎯 VT Range: <span class="sp-val" id="vtStatus">--</span></div>
      <div class="stat-pill">🫧 O₂ Goal: <span class="sp-val" id="o2Status">92–96%</span></div>
      <div class="stat-pill">🌡️ CO₂ / pH: <span class="sp-val" id="co2Status">PaCO₂ 35–45</span></div>
      <div class="stat-pill">📋 Protocol: <span class="sp-val" id="protocolStatus">ARDSNet</span></div>
    </div>

    <div class="card-grid" id="paramGrid"></div>

    <!-- Waveform visualization -->
    <div class="waveform-card">
      <div class="waveform-label">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:15px;height:15px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Expected Ventilator Waveform
      </div>
      <svg id="waveformSvg" class="waveform-svg" viewBox="0 0 400 110" preserveAspectRatio="none" aria-label="Ventilator waveform"></svg>
      <p class="waveform-caption" id="waveCaption">Loading…</p>
    </div>

    <!-- Evidence Tags -->
    <div class="evidence-bar" id="evidenceBar"></div>

    <!-- EHR -->
    <button class="ehr-btn" id="ehrBtn" data-feature="ehr_export" data-feature-name="EHR Export">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
      📋 Copy Structured Note to EHR
    </button>
    <p class="text-muted-center">Includes timestamp, evidence references &amp; PBW.</p>
  </section>

  <!-- ── ABG CALCULATOR ── -->
  <section id="view-abg" class="view" aria-label="ABG calculator">
    <div class="sec-hdr mt-4">
      <div class="sec-hdr-title">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:17px;height:17px;"><path d="M12 2a7 7 0 0 0-7 7c0 2.38 1.19 4.47 3 5.74V17a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2v-2.26c1.81-1.27 3-3.36 3-5.74a7 7 0 0 0-7-7Z"/></svg>
        🧪 ABG Interpreter &amp; RR Corrector
      </div>
      <div class="sec-hdr-sub">Enter a full ABG for screening interpretation, then use the bedside RR formula cautiously.<br><em>New RR = Current RR × (PaCO₂ ÷ Target PaCO₂)</em></div>
    </div>

    <div class="clin-warn">
      ⚠️ <strong>Clinical Warning:</strong> In Asthma, COPD, or severe ARDS,
      <span class="term" tabindex="0" onclick="App.toast('🫁 Permissive hypercapnia = accepting mild acidosis to avoid dynamic hyperinflation or excess lung stretch.')">permissive hypercapnia</span>
      is often preferred. <strong>Do NOT chase PaCO₂ = 40 if the patient is avoiding breath stacking and the pH remains clinically acceptable.</strong>
    </div>

    <div class="calc-field">
      <label class="calc-label">🩺 pH</label>
      <input type="number" id="abgPh" class="calc-input" placeholder="7.40" step="0.01" min="6.8" max="8.0" inputmode="decimal">
    </div>
    <div class="calc-field">
      <label class="calc-label">🫧 PaCO₂ (mmHg)</label>
      <input type="number" id="abgPco2" class="calc-input" placeholder="40" inputmode="decimal">
    </div>
    <div class="calc-field">
      <label class="calc-label">🧂 HCO₃⁻ (mEq/L)</label>
      <input type="number" id="abgHco3" class="calc-input" placeholder="24" inputmode="decimal">
    </div>
    <div class="calc-field">
      <label class="calc-label">💨 Current Respiratory Rate (breaths/min)</label>
      <input type="number" id="abgRr" class="calc-input" placeholder="e.g. 14" inputmode="decimal">
    </div>
    <div class="calc-field">
      <label class="calc-label">🎯 Target PaCO₂ (mmHg)</label>
      <input type="number" id="abgTarget" class="calc-input" value="40" inputmode="decimal">
    </div>

    <div class="abg-interp hidden" id="abgInterp">
      <h4>🔬 ABG Interpretation</h4>
      <p id="abgInterpText">--</p>
    </div>

    <div class="result-box">
      <div class="result-lbl">🔄 Recommended New RR</div>
      <div class="result-val" id="abgResult">--</div>
      <div class="result-unit">breaths / minute</div>
    </div>

    <div class="info-card mt-4">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        🧠 Clinical Pearls
      </h3>
      <ul>
        <li>In <strong>COPD</strong>, the baseline CO₂ is often chronically elevated (50–60 mmHg) — target <span class="term" tabindex="0" onclick="App.toast('Target the patient\'s baseline CO₂, not the normal value of 40 mmHg.')">their baseline</span>, not 40 mmHg.</li>
        <li>Recheck ABG or VBG <strong>30–60 min</strong> after any ventilator change.</li>
        <li><strong>VBG</strong> is often adequate for pH and CO₂ trend screening, but do not use venous CO₂ as an exact PaCO₂ substitute in shock or severe respiratory failure.</li>
        <li>Overcorrecting CO₂ too fast risks <strong>post-hypercapnic alkalosis</strong> and seizures.</li>
        <li>This RR tool is a <strong>screening aid</strong> only; in active asthma/COPD with air trapping, prioritize longer expiratory time over a “normal” PaCO₂.</li>
      </ul>
    </div>
  </section>

  <!-- ── COMPARE ── -->
  <section id="view-compare" class="view" aria-label="Comparison matrix">

    <!-- Mode switcher -->
    <div class="cmp-mode-bar mt-4">
      <button class="cmp-mode-btn active" data-mode="overview">🗺️ Overview</button>
      <button class="cmp-mode-btn" data-mode="duel">⚔️ Head-to-Head</button>
      <button class="cmp-mode-btn" data-mode="spotlight">🔬 Deep Dive</button>
    </div>

    <!-- MODE 1: OVERVIEW -->
    <div id="cmp-overview" class="cmp-pane active">
      <div class="cmp-intro">Tap any scenario card to explore it — or switch to Head-to-Head to compare two directly.</div>
      <div id="overviewCards"></div>
    </div>

    <!-- MODE 2: DUEL -->
    <div id="cmp-duel" class="cmp-pane">
      <div class="cmp-intro">Select two scenarios to compare every parameter head-to-head with clinical rationale.</div>
      <div class="duel-selector">
        <div class="duel-pick" id="duelPickA">
          <div class="dp-label">Patient A</div>
          <div class="dp-value" id="duelALabel">Choose →</div>
          <div class="dp-chips" id="duelAChips"></div>
        </div>
        <div class="duel-vs">VS</div>
        <div class="duel-pick" id="duelPickB">
          <div class="dp-label">Patient B</div>
          <div class="dp-value" id="duelBLabel">Choose →</div>
          <div class="dp-chips" id="duelBChips"></div>
        </div>
      </div>
      <div id="duelTable"></div>
    </div>

    <!-- MODE 3: DEEP DIVE / SPOTLIGHT -->
    <div id="cmp-spotlight" class="cmp-pane">
      <div class="cmp-intro">Select a parameter — see values, clinical rationale, and evidence for each scenario.</div>
      <div class="param-pill-row" id="paramPillRow"></div>
      <div class="cmp-grid" id="cmpGrid"></div>
      <div class="cmp-insight hidden" id="cmpInsight">
        <span class="cmp-insight-icon">💡</span>
        <span id="cmpInsightText"></span>
      </div>
    </div>

  </section>

  <!-- ── GUIDE ── -->
  <section id="view-guide" class="view" aria-label="Clinical guidelines">

    <div class="info-card mt-4">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
        🛡️ Core Lung-Protective Principles
      </h3>
      <ul>
        <li>🫁 <strong>Low Tidal Volume:</strong> Most adults start at 6–8 mL/kg <span class="term" tabindex="0" onclick="App.toast('⚖️ PBW = Predicted Body Weight. Always calculate from HEIGHT and SEX — never use actual body weight.')">PBW</span>; established ARDS uses 4–8 mL/kg PBW with plateau pressure control to prevent <span class="term" tabindex="0" onclick="App.toast('💥 VILI = Ventilator-Induced Lung Injury. Includes barotrauma, volutrauma, and atelectrauma.')">VILI</span>.</li>
        <li>📐 <strong>Driving Pressure:</strong> Target ΔP (Pplat − PEEP) &lt; <strong>15 cmH₂O</strong>. A key surrogate of lung stress.</li>
        <li>💊 <strong>Sedation &amp; Analgesia:</strong> Use analgesia-first, goal-directed sedation and target the lightest RASS compatible with comfort and synchrony.</li>
        <li>🔄 <strong>Reassess:</strong> ABG or VBG within <strong>30–60 min</strong> of initiation or any major change.</li>
        <li>📉 <strong>Plateau Pressure:</strong> Check Pplat every 4h or after changes. Target ≤ 30 cmH₂O.</li>
        <li>🩺 <strong>Prone Positioning:</strong> Consider early prone sessions longer than 12 h/day in severe ARDS, especially when P/F ≤ 150 mmHg despite lung-protective ventilation and adequate PEEP.</li>
      </ul>
    </div>

    <div class="info-card">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 12h16"/><path d="M4 7h10"/><path d="M4 17h13"/><path d="M18 8l2-2 2 2"/><path d="M20 6v12"/></svg>
        🌬️ High-Flow Nasal Cannula (HFNC)
      </h3>
      <ul>
        <li>HFNC is preferred over conventional oxygen in <strong>acute hypoxemic respiratory failure</strong> and is often favored over NIV in de novo hypoxemia when close reassessment is available.</li>
        <li>Typical ED start: <strong>40–60 L/min</strong>, FiO₂ titrated to saturation goal, heated humidification on, patient upright.</li>
        <li>In <strong>sepsis-induced hypoxemic failure</strong>, current sepsis guidance favors HFNC over NIV when noninvasive support is needed.</li>
        <li>In <strong>COPD with hypercapnia</strong>, NIV still has the stronger evidence base; HFNC is a bridge or fallback, not a replacement for NIV.</li>
        <li>Do not let HFNC delay escalation: rising FiO₂ needs, increasing work of breathing, altered mentation, or worsening acidosis should trigger a new airway decision.</li>
      </ul>
    </div>

    <div class="info-card" style="border-left:4px solid #dc2626;">
      <h3 style="color:#dc2626;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        🔥 Refractory Hypoxemia Rescue Ladder
      </h3>
      <p style="font-size:0.84rem;color:var(--text-2);margin-bottom:8px;">When SpO₂ remains &lt;88% despite 100% FiO₂ and optimal PEEP, escalate rapidly:</p>
      <ol style="font-size:0.85rem;color:var(--text-1);padding-left:20px;line-height:1.5;">
        <li style="margin-bottom:6px;"><strong>Rule out DOPE:</strong> Disconnect vent, bag manually. Is it hard to bag? Consider tension pneumo (decompress).</li>
        <li style="margin-bottom:6px;"><strong>Deep Sedation &amp; Paralysis:</strong> If clearly indicated, ensure analgesia/sedation first, then use NMBA to regain synchrony and lung-protective ventilation.</li>
        <li style="margin-bottom:6px;"><strong>Recruitment / PEEP Strategy:</strong> Avoid routine sustained inflation maneuvers. If expert teams use a recruitment approach, do it cautiously with continuous hemodynamic monitoring.</li>
        <li style="margin-bottom:6px;"><strong>Prone Positioning:</strong> Perform early in the ED if boarding. Highly effective for dependent atelectasis in ARDS.</li>
        <li><strong>ECMO / iNO:</strong> Consult for venovenous ECMO or start inhaled pulmonary vasodilators (Flolan/Nitric Oxide).</li>
      </ol>
    </div>

    <div class="info-card">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10"/><path d="M9 4v5a3 3 0 0 0 6 0V4"/><path d="M6 14a6 6 0 0 1 12 0"/></svg>
        🧪 pH / Gas Targets by Scenario
      </h3>
      <div class="table-wrap ref-table">
        <table>
          <thead>
            <tr><th>Scenario</th><th>Gas Goal</th><th>Bedside Target</th></tr>
          </thead>
          <tbody>
            <tr><td>Asthma/COPD</td><td>Permissive hypercapnia is acceptable</td><td>Usually tolerate pH ≥ 7.20 if mechanics are safe and exhalation is complete.</td></tr>
            <tr><td>ARDS / Sepsis</td><td>Protect the lung first</td><td>Commonly accept pH ≥ 7.20 while keeping Pplat ≤ 30 and ΔP &lt; 15.</td></tr>
            <tr><td>Neuro / TBI / Stroke</td><td>Normocapnia</td><td>Target premorbid PaCO₂ when known; otherwise PaCO₂ 35–45 mmHg and pH 7.35–7.45 unless acute herniation.</td></tr>
            <tr><td>Post-ROSC</td><td>Strict normocapnia</td><td>Target PaCO₂ 35–45 mmHg and avoid hypocapnia and hyperoxia.</td></tr>
            <tr><td>DKA / Severe Metabolic Acidosis</td><td>Preserve compensation</td><td>Match or exceed pre-intubation minute ventilation immediately; the short-term goal is preventing a sudden pH drop.</td></tr>
            <tr><td>Pregnancy</td><td>Pregnancy-normal gas exchange</td><td>Usually PaCO₂ about 28–32 mmHg with maternal oxygen saturation kept high.</td></tr>
            <tr><td>Massive PE / Shock</td><td>Avoid worsening acidaemia</td><td>Keep oxygenation adequate while avoiding high intrathoracic pressure and sudden CO₂ rise.</td></tr>
          </tbody>
        </table>
      </div>
      <p class="micro-note">These are bedside targets, not absolutes. Always recheck blood gas and mechanics after the first round of vent changes.</p>
    </div>

    <div class="info-card">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20"/><path d="M5 7h14"/><path d="M5 17h14"/><path d="M8 7c0 4 8 6 8 10"/></svg>
        🫀 Hemodynamic Effects of Ventilation
      </h3>
      <div class="simple-grid">
        <div class="mini-card">
          <h4>PEEP</h4>
          <p>PEEP raises intrathoracic pressure, which can reduce venous return and RV preload. This matters most in <strong>hypovolemia, septic shock before resuscitation, massive PE, and RV failure</strong>.</p>
        </div>
        <div class="mini-card">
          <h4>VT / Plateau</h4>
          <p>Higher VT and plateau pressure increase alveolar pressure and RV afterload, worsening lung injury and hemodynamic compromise. Keep <strong>Pplat ≤ 30</strong> and watch ΔP.</p>
        </div>
        <div class="mini-card">
          <h4>RR / Auto-PEEP</h4>
          <p>Fast RR can create incomplete exhalation and intrinsic PEEP. The result may be sudden hypotension, high-pressure alarms, and poor venous return even when set PEEP is low.</p>
        </div>
        <div class="mini-card">
          <h4>Clinical Rule</h4>
          <p>When the vent causes hypotension, think <strong>too much pressure, too little preload, or a missed obstructive problem</strong> before reflexively adding more sedation.</p>
        </div>
      </div>
    </div>

    <div class="info-card">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
        📋 ARDSNet Low PEEP / High FiO₂ Table
      </h3>
      <p style="margin-bottom:10px;">Match PEEP to FiO₂ to optimize oxygenation while limiting VILI. This is the classic ARDSNet low-PEEP/high-FiO₂ table; moderate-severe ARDS may justify a higher-PEEP strategy in experienced hands.</p>
      <div class="table-wrap peep-table">
        <table>
          <thead><tr><th>FiO₂</th><th>0.3</th><th>0.4</th><th>0.5</th><th>0.6</th><th>0.7</th><th>0.8</th><th>0.9</th><th>1.0</th></tr></thead>
          <tbody><tr><td><strong>PEEP</strong></td><td>5</td><td>5–8</td><td>8–10</td><td>10</td><td>10–14</td><td>14</td><td>14–18</td><td>18–24</td></tr></tbody>
        </table>
      </div>
      <p style="margin-top:10px;font-size:.8rem;color:var(--text-3);">🎯 Target: PaO₂ 55–80 mmHg or SpO₂ 88–95%</p>
    </div>

    <div class="info-card">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/></svg>
        🚨 Alarm Troubleshooting — DOPE Mnemonic
      </h3>
      <div class="dope-grid">
        <div class="dope-item"><div class="dope-letter">D</div><div class="dope-word">Displacement</div><div class="dope-desc">ETT dislodged or right mainstem intubation</div></div>
        <div class="dope-item"><div class="dope-letter">O</div><div class="dope-word">Obstruction</div><div class="dope-desc">Secretions, biting tube, or kinking</div></div>
        <div class="dope-item"><div class="dope-letter">P</div><div class="dope-word">Pneumothorax</div><div class="dope-desc">Tension — needle decompress immediately</div></div>
        <div class="dope-item"><div class="dope-letter">E</div><div class="dope-word">Equipment</div><div class="dope-desc"><span class="term" tabindex="0" onclick="App.toast('💨 Auto-PEEP = breath stacking from incomplete exhalation. Disconnect circuit and allow passive exhalation.')">Auto-PEEP</span> or machine failure</div></div>
      </div>
      <div class="mini-card" style="margin-top:12px;background:rgba(220,38,38,.05);border-left:3px solid #dc2626;">
        <h4 style="color:#dc2626;margin-bottom:4px;">High Pressure Alarm? Press "Inspiratory Hold"</h4>
        <p style="font-size:0.8rem;margin:4px 0;"><strong>Is Plateau High (&gt;30)?</strong> Problem is in the lungs (Pneumothorax, ARDS, Right Mainstem).</p>
        <p style="font-size:0.8rem;margin:0;"><strong>Is Plateau Normal?</strong> Problem is resistance (Biting tube, mucus plug, asthma, kinked circuit).</p>
      </div>
    </div>

    <div class="info-card">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16v4H4z"/><path d="M6 8v12"/><path d="M18 8v12"/><path d="M8 20h8"/></svg>
        🛌 Liberation from Mechanical Ventilation
      </h3>
      <ul>
        <li><strong>SAT first:</strong> lighten continuous sedation daily when hemodynamically safe and there is no compelling reason for deep sedation.</li>
        <li><strong>SBT next:</strong> once oxygenation, mentation, and hemodynamics are acceptable, perform an SBT and assess cough, secretions, and airway protection.</li>
        <li><strong>RSBI:</strong> f / VT(L). A value <strong>&lt; 105</strong> supports readiness but never replaces clinical judgment.</li>
        <li><strong>High-risk extubation:</strong> after a successful SBT, consider preventive NIV in selected high-risk patients; use a cuff leak test when upper-airway edema is a concern.</li>
        <li><strong>Neuro patients:</strong> extubation is not just gas exchange; airway reflexes, secretion burden, and intracranial stability matter.</li>
      </ul>
    </div>

    <div class="info-card">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 6h16"/><path d="M6 12h12"/><path d="M8 18h8"/></svg>
        🦠 VAP Prevention Bundle
      </h3>
      <ul>
        <li><strong>Head of bed 30–45°</strong> unless contraindicated.</li>
        <li><strong>Daily awakening / sedation minimization</strong> with SBT pairing when feasible.</li>
        <li><strong>Oral care and secretion management</strong> with meticulous suctioning and humidification.</li>
        <li><strong>Cuff pressure and tube position</strong> should be checked regularly; use subglottic drainage tubes when prolonged ventilation is expected.</li>
        <li><strong>Avoid unnecessary ventilator circuit breaks</strong> and keep strict infection-prevention basics in place.</li>
      </ul>
    </div>

    <div class="info-card">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 7h12"/><path d="M6 12h12"/><path d="M6 17h12"/><path d="M4 7h.01"/><path d="M4 12h.01"/><path d="M4 17h.01"/></svg>
        🔄 Prone Positioning Protocol
      </h3>
      <div class="stack-list">
        <div class="stack-item">
          <strong>When to prone</strong>
          <p>Moderate-severe ARDS, especially when <strong>P/F ≤ 150</strong> despite lung-protective ventilation and adequate PEEP.</p>
        </div>
        <div class="stack-item">
          <strong>How long</strong>
          <p>Experienced teams should aim for <strong>12–16 hours per session</strong>, commonly about 16 hours, with repeat sessions until oxygenation remains improved after returning supine.</p>
        </div>
        <div class="stack-item">
          <strong>Team roles</strong>
          <p>Airway lead at the head, two torso/arm staff, two hip/leg staff, one line-and-monitor lead, checklist read-back before each turn.</p>
        </div>
        <div class="stack-item">
          <strong>If deterioration occurs prone</strong>
          <p>Check tube depth, circuit, hemodynamics, chest movement, and pressure alarms first. Return supine urgently if the airway, oxygenation, or circulation cannot be stabilized.</p>
        </div>
      </div>
    </div>

    <div class="info-card">
      <h3>
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 12h6"/><path d="M12 9v6"/><path d="M5 6h14v12H5z"/></svg>
        💪 Neuromuscular Blockade (NMB) Guide
      </h3>
      <ul>
        <li>Consider continuous NMBA in <strong>ARDS with P/F &lt; 150</strong> when the patient remains persistently hypoxemic or cannot meet ventilation targets despite adequate analgesia/sedation.</li>
        <li><strong>Sedation and analgesia must be secure before paralysis</strong>; reassess that repeatedly.</li>
        <li>Current SCCM guidance allows either a <strong>fixed-dose</strong> strategy or a <strong>monitoring-based</strong> strategy for blockade depth.</li>
        <li>Train-of-four can be used when the team follows a titration approach, but guideline certainty for the best monitoring strategy remains low.</li>
        <li>Stop as soon as oxygenation/synchrony goals can be maintained without it; do not paralyze by inertia.</li>
      </ul>
    </div>
</section>

  <!-- ── TOOLS TAB ── -->
  <section id="view-tools" class="view" aria-label="Clinical tools">

    <!-- ── 1. ASSESSMENT: Difficult Airway Predictor (LEMON) ── -->
    <div class="info-card mt-4" style="border-left:4px solid #7c2d12;">
      <h3 style="color:#7c2d12;">🧱 Difficult Airway Predictor (LEMON)</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Each positive item adds one point. Obstruction overrides total and escalates plan.</p>
      <div class="simple-grid">
        <label class="checklist-item" style="margin-bottom:0;"><input type="checkbox" id="lemonLook" style="margin-top:3px;"> <div><div class="ci-text">Look externally difficult</div><div class="ci-sub">Trauma, beard, obesity, facial distortion.</div></div></label>
        <label class="checklist-item" style="margin-bottom:0;"><input type="checkbox" id="lemonEval" style="margin-top:3px;"> <div><div class="ci-text">Evaluate 3-3-2 abnormal</div><div class="ci-sub">Limited mandibular space or opening.</div></div></label>
        <label class="checklist-item" style="margin-bottom:0;"><input type="checkbox" id="lemonMallampati" style="margin-top:3px;"> <div><div class="ci-text">Mallampati III/IV or unable</div><div class="ci-sub">Treat “cannot assess” as potentially difficult.</div></div></label>
        <label class="checklist-item" style="margin-bottom:0;"><input type="checkbox" id="lemonObstruction" style="margin-top:3px;"> <div><div class="ci-text">Obstruction / edema / blood</div><div class="ci-sub">Anaphylaxis, burn, infection, tumor.</div></div></label>
        <label class="checklist-item" style="margin-bottom:0;"><input type="checkbox" id="lemonNeck" style="margin-top:3px;"> <div><div class="ci-text">Neck mobility limited</div><div class="ci-sub">C-collar, ankylosis, severe pain.</div></div></label>
      </div>
      <div class="simple-grid" style="margin-top:12px;">
        <div class="tool-result" style="margin-top:0;border-color:rgba(124,45,18,.2);background:rgba(124,45,18,.06);">
          <div class="tr-label" style="color:#7c2d12;">LEMON Score</div>
          <div class="tr-val" id="lemonScore">0</div>
          <div class="tr-unit">0–5</div>
          <div class="tr-badge hidden" id="lemonBadge"></div>
        </div>
        <div class="mini-card">
          <h4>Suggested Approach</h4>
          <p id="lemonPlan">Low score supports standard RSI pathway.</p>
        </div>
      </div>
    </div>

    <!-- ── 2. PREPARATION: Peri-Intubation Hemodynamic Risk Screen ── -->
    <div class="info-card" style="border-left:4px solid #dc2626;">
      <h3 style="color:#dc2626;">🫀 Peri-Intubation Hemodynamic Risk Screen</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Bedside clinical screen to force early resuscitation planning before induction.</p>
      <div class="tools-row">
        <div class="calc-field" style="flex:1"><label class="calc-label">HR (/min)</label><input type="number" id="riskHr" class="calc-input" placeholder="e.g. 128" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">SBP (mmHg)</label><input type="number" id="riskSbp" class="calc-input" placeholder="e.g. 86" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">DBP (mmHg)</label><input type="number" id="riskDbp" class="calc-input" placeholder="e.g. 48" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">Hb (g/dL)</label><input type="number" id="riskHb" class="calc-input" placeholder="e.g. 7.8" step="0.1" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1">
          <label class="calc-label">Likely Physiology</label>
          <select id="riskDx" class="calc-input">
            <option value="">Select</option>
            <option value="sepsis">Sepsis</option>
            <option value="pe">Massive PE</option>
            <option value="cardiogenic">Cardiogenic Shock</option>
            <option value="hypovolemia">Hypovolemia</option>
            <option value="dka">DKA/Acidosis</option>
            <option value="neuro">Neurocritical</option>
            <option value="other">Other</option>
          </select>
        </div>
      </div>
      <div class="simple-grid">
        <div class="tool-result" style="margin-top:0;border-color:rgba(220,38,38,.2);background:rgba(220,38,38,.06);">
          <div class="tr-label" style="color:#dc2626;">Risk Score</div>
          <div class="tr-val" id="riskVal">--</div>
          <div class="tr-badge hidden" id="riskBadge"></div>
        </div>
        <div class="mini-card">
          <h4>Pre-induction Plan</h4>
          <p id="riskPlan">Enter hemodynamics to generate plan.</p>
        </div>
      </div>
    </div>

    <!-- ── 3. INTUBATION: RSI & Push-Dose Pressor Calculator ── -->
    <div class="info-card" style="border-left:4px solid #dc2626;">
      <h3 style="color:#dc2626;">💉 RSI &amp; Push-Dose Pressor Calculator</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Calculates induction, paralysis, and rescue pressor doses based on body weight.</p>
      <div class="calc-field">
        <label class="calc-label">Total Body Weight (kg)</label>
        <input type="number" id="rsiTbw" class="calc-input" placeholder="e.g. 80" inputmode="decimal">
      </div>
      <div class="stack-list" id="rsiOutput">
        <div style="padding:10px;text-align:center;color:var(--text-3);font-size:.8rem;font-weight:600;">Enter weight to calculate doses</div>
      </div>
    </div>

    <!-- ── 4. IMMEDIATE POST-INTUBATION: Post-Intubation Checklist ── -->
    <div class="info-card" style="border-left:4px solid var(--warning);">
      <h3 style="color:var(--warning);">✅ Post-Intubation Checklist</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Confirm placement, settings, and sedation immediately after tube is secured.</p>
      <div id="checklistWrap"></div>
      <div style="display:flex;gap:10px;margin-top:12px;">
        <button class="ehr-btn secondary" id="resetChecklist" style="margin-top:0;flex:1;">🔄 Reset</button>
        <button class="ehr-btn" id="copyChecklist" style="margin-top:0;flex:2;">📋 Copy</button>
      </div>
    </div>

    <!-- ── 5. POST-INTUBATION: Drug Infusion Quick Reference ── -->
    <div class="info-card" style="border-left:4px solid #0f766e;">
      <h3 style="color:#0f766e;">💉 Drug Infusion Quick Reference</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Interactive weight-based titration for common ED/ICU drips.</p>

      <div class="calc-field" style="margin-bottom:15px;">
        <label class="calc-label">Patient Weight (kg)</label>
        <input type="number" id="infusionWeight" class="calc-input" placeholder="e.g. 80" inputmode="decimal">
        <p style="font-size:0.7rem;color:var(--text-3);margin-top:4px;">Used for weight-based dose calculations.</p>
      </div>

      <div class="stack-list" id="infusionList">
        <div style="padding:20px;text-align:center;color:var(--text-3);font-size:.85rem;">Enter weight to see start &amp; usual upper ranges</div>
      </div>

      <div class="alert-box" style="margin-top:12px;background:var(--danger-bg);border-color:var(--danger-border);color:var(--danger);">
        <strong>⚠️ Extravasation Warning:</strong> Vasopressors are vesicants. Peripheral use should be short-term through a well-functioning proximal line, with frequent site checks. Transition to <strong>central access</strong> if the infusion is ongoing or escalating.
      </div>
    </div>

    <!-- ── 5. EMERGENCY TROUBLESHOOTING: Rapid Deterioration Troubleshooter ── -->
    <div class="info-card" style="border-left:4px solid #1d4ed8;">
      <h3 style="color:#1d4ed8;">🚨 Rapid Deterioration Troubleshooter</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Acute management for bedside crises. Choose the primary problem.</p>
      <div class="pill-row" id="deteriorationButtons">
        <button class="pill-btn active" data-problem="desat">Desaturation</button>
        <button class="pill-btn" data-problem="high-pressure">High Pressure</button>
        <button class="pill-btn" data-problem="hypotension">Hypotension</button>
        <button class="pill-btn" data-problem="fighting">Fighting Vent</button>
      </div>
      <div class="stack-list" id="deteriorationOutput"></div>
    </div>

    <!-- ── 6. VENT MANAGEMENT: Ventilator Dyssynchrony Guide ── -->
    <div class="info-card" style="border-left:4px solid #475569;">
      <h3 style="color:#475569;">📈 Ventilator Dyssynchrony Guide</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Identify and fix ventilator/patient mismatch using waveforms.</p>
      <div class="pill-row" id="dyssyncButtons">
        <button class="pill-btn active" data-dyssync="flow">Flow Starvation</button>
        <button class="pill-btn" data-dyssync="double">Double Trigger</button>
        <button class="pill-btn" data-dyssync="reverse">Reverse Trigger</button>
        <button class="pill-btn" data-dyssync="auto">Auto-Trigger</button>
      </div>
      <svg id="dyssyncWave" class="flow-svg" viewBox="0 0 400 86" preserveAspectRatio="none"></svg>
      <div class="stack-list" id="dyssyncOutput"></div>
    </div>

    <!-- ── 7. METABOLIC: Minute Ventilation Matcher ── -->
    <div class="info-card" style="border-left:4px solid #9333ea;">
      <h3 style="color:#9333ea;">⚙️ Minute Ventilation Matcher</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">For DKA or severe metabolic acidosis. Match pre-intubation breathing.</p>
      <div class="tools-row">
        <div class="calc-field" style="flex:1"><label class="calc-label">Obs RR (/min)</label><input type="number" id="mvObsRr" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">Obs VT (mL)</label><input type="number" id="mvObsVt" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">Target VT (mL)</label><input type="number" id="mvTargetVt" class="calc-input" placeholder="Auto" inputmode="decimal"></div>
      </div>
      <div class="simple-grid">
        <div class="tool-result" style="margin-top:0;border-color:rgba(147,51,234,.2);background:rgba(147,51,234,.06);">
          <div class="tr-label" style="color:#9333ea;">Current VE</div><div class="tr-val" id="mvVal">--</div>
        </div>
        <div class="tool-result" style="margin-top:0;border-color:rgba(147,51,234,.2);background:rgba(147,51,234,.06);">
          <div class="tr-label" style="color:#9333ea;">Matched RR</div><div class="tr-val" id="mvNeedRr">--</div>
        </div>
      </div>
      <div class="mini-card" style="margin-top:12px;">
        <p id="mvAlt" style="font-size:0.8rem;"></p>
      </div>
    </div>

    <!-- ── 8. METABOLIC: Acidosis Rescue (Bicarb Deficit) ── -->
    <div class="info-card" style="border-left:4px solid #ec4899;">
      <h3 style="color:#ec4899;">🧪 Acidosis Rescue (Bicarb Deficit)</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Temporizing measure for profound acidosis (pH &lt; 6.9). Goal HCO₃ ≈ 15.</p>
      <div class="tools-row">
        <div class="calc-field" style="flex:1"><label class="calc-label">Weight (kg)</label><input type="number" id="bicarbTbw" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">Current HCO₃</label><input type="number" id="bicarbCurr" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">Target HCO₃</label><input type="number" id="bicarbTarget" class="calc-input" value="15" inputmode="decimal"></div>
      </div>
      <div class="simple-grid">
        <div class="tool-result" style="margin-top:0;border-color:rgba(236,72,153,.2);background:rgba(236,72,153,.06);">
          <div class="tr-label" style="color:#ec4899;">Deficit (mEq)</div><div class="tr-val" id="bicarbDeficitVal">--</div>
        </div>
        <div class="tool-result" style="margin-top:0;border-color:rgba(236,72,153,.2);background:rgba(236,72,153,.06);">
          <div class="tr-label" style="color:#ec4899;">Initial Amps</div><div class="tr-val" id="bicarbDoseVal">--</div>
        </div>
      </div>
    </div>

    <!-- ── 9. OBSTRUCTIVE: Auto-PEEP Detector ── -->
    <div class="info-card" style="border-left:4px solid #d97706;">
      <h3 style="color:#d97706;">💨 Auto-PEEP Detector / Calculator</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Intrinsic PEEP measurement via expiratory hold.</p>
      <div class="tools-row">
        <div class="calc-field" style="flex:1"><label class="calc-label">Total PEEP</label><input type="number" id="autoPeepTotal" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">Set PEEP</label><input type="number" id="autoPeepSet" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1">
          <label class="calc-label">Returns to Zero?</label>
          <select id="autoPeepFlow" class="calc-input"><option value="">Select</option><option value="yes">Yes</option><option value="no">No</option></select>
        </div>
        <div class="calc-field" style="flex:1">
          <label class="calc-label">Trigger Burden?</label>
          <select id="autoPeepTrigger" class="calc-input"><option value="">Select</option><option value="yes">Yes</option><option value="no">No</option></select>
        </div>
      </div>
      <div class="simple-grid">
        <div class="tool-result" style="margin-top:0;border-color:rgba(217,119,6,.2);background:rgba(217,119,6,.08);">
          <div class="tr-label" style="color:#d97706;">Intrinsic PEEP</div>
          <div class="tr-val" id="autoPeepVal">--</div>
          <div class="tr-badge hidden" id="autoPeepBadge"></div>
        </div>
        <div class="mini-card">
          <p id="autoPeepExt" style="font-size:0.8rem;">Confirm measurements.</p>
        </div>
      </div>
    </div>

    <!-- ── 10. OXYGENATION: S/F Ratio (SpO₂ / FiO₂) ── -->
    <div class="info-card" style="border-left:4px solid #0f766e;">
      <h3 style="color:#0f766e;">🩸 S/F Ratio (SpO₂ / FiO₂)</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Quick estimate of oxygenation before ABG results.</p>
      <div class="tools-row">
        <div class="calc-field" style="flex:1"><label class="calc-label">SpO₂ (%)</label><input type="number" id="sfSpo2" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">FiO₂ (0.21–1.0)</label><input type="number" id="sfFio2" class="calc-input" step="0.01" inputmode="decimal"></div>
      </div>
      <div class="tool-result" style="border-color:rgba(15,118,110,.2);background:rgba(15,118,110,.06);">
        <div class="tr-label" style="color:#0f766e;">S/F Ratio</div>
        <div class="tr-val" id="sfVal">--</div>
        <div class="tr-badge hidden" id="sfBadge"></div>
      </div>
    </div>

    <!-- ── 11. OXYGENATION: P/F Ratio & ARDS Severity ── -->
    <div class="info-card" style="border-left:4px solid #dc2626;">
      <h3 style="color:#dc2626;">💧 P/F Ratio & ARDS Severity</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Berlin criteria for ventilated ARDS patients.</p>
      <div class="tools-row">
        <div class="calc-field" style="flex:1"><label class="calc-label">PaO₂ (mmHg)</label><input type="number" id="pfPao2" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">FiO₂ (0.21–1.0)</label><input type="number" id="pfFio2" class="calc-input" step="0.01" inputmode="decimal"></div>
      </div>
      <div class="tool-result" style="border-color:rgba(220,38,38,.2);background:rgba(220,38,38,.06);">
        <div class="tr-label" style="color:#dc2626;">P/F Ratio</div>
        <div class="tr-val" id="pfVal">--</div>
        <div class="tr-badge hidden" id="pfBadge"></div>
      </div>
      <div class="berlin-bar" id="berlinBar" style="display:none;margin-top:14px;">
        <div class="berlin-track">
          <div class="berlin-seg" style="background:#dc2626;flex:1;"></div>
          <div class="berlin-seg" style="background:#d97706;flex:1;"></div>
          <div class="berlin-seg" style="background:#ca8a04;flex:1;"></div>
          <div class="berlin-seg" style="background:#16a34a;flex:1;"></div>
        </div>
        <div class="berlin-arrow" id="berlinArrow">▲</div>
      </div>
    </div>

    <!-- ── 12. LUNG PROTECTION: Driving Pressure Calculator ── -->
    <div class="info-card" style="border-left:4px solid var(--theme);">
      <h3>📐 Driving Pressure Calculator</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">ΔP = Pplat − PEEP. Target: &lt; 15 cmH₂O.</p>
      <div class="tools-row">
        <div class="calc-field" style="flex:1"><label class="calc-label">Pplat (cmH₂O)</label><input type="number" id="dpPplat" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">PEEP (cmH₂O)</label><input type="number" id="dpPeep" class="calc-input" inputmode="decimal"></div>
      </div>
      <div class="tool-result">
        <div class="tr-label">Driving Pressure</div>
        <div class="tr-val" id="dpVal">--</div>
        <div class="tr-badge hidden" id="dpBadge"></div>
      </div>
    </div>

    <!-- ── 13. LUNG PROTECTION: Static Compliance ── -->
    <div class="info-card" style="border-left:4px solid #7c3aed;">
      <h3 style="color:#7c3aed;">📊 Static Compliance Calculator</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Crs = VT ÷ (Pplat − PEEP). Normal ≥ 50 mL/cmH₂O.</p>
      <div class="tools-row">
        <div class="calc-field" style="flex:1"><label class="calc-label">VT (mL)</label><input type="number" id="crsVt" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">Pplat (cmH₂O)</label><input type="number" id="crsPplat" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">PEEP (cmH₂O)</label><input type="number" id="crsPeep" class="calc-input" inputmode="decimal"></div>
      </div>
      <div class="tool-result" style="border-color:rgba(124,58,237,.2);background:rgba(124,58,237,.07);">
        <div class="tr-label" style="color:#7c3aed;">Compliance</div>
        <div class="tr-val" id="crsVal">--</div>
        <div class="tr-badge hidden" id="crsBadge"></div>
      </div>
    </div>

    <!-- ── 14. ADVANCED: Mean Airway Pressure (Paw) ── -->
    <div class="info-card" style="border-left:4px solid #059669;">
      <h3 style="color:#059669;">🌡️ Mean Airway Pressure (Paw)</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Averaged pressure across the respiratory cycle.</p>
      <div class="tools-row">
        <div class="calc-field" style="flex:1"><label class="calc-label">PIP</label><input type="number" id="pawPip" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">PEEP</label><input type="number" id="pawPeep" class="calc-input" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">I-time</label><input type="number" id="pawItime" class="calc-input" step="0.1" inputmode="decimal"></div>
        <div class="calc-field" style="flex:1"><label class="calc-label">RR</label><input type="number" id="pawRr" class="calc-input" inputmode="decimal"></div>
      </div>
      <div class="tool-result" style="border-color:rgba(5,150,105,.2);background:rgba(5,150,105,.07);">
        <div class="tr-label" style="color:#059669;">Mean Paw</div><div class="tr-val" id="pawVal">--</div>
      </div>
    </div>



    <!-- ── 16. BOARDING: RASS Sedation Target ── -->
    <div class="info-card" style="border-left:4px solid #0284c7;">
      <h3 style="color:#0284c7;">😴 RASS Sedation Target</h3>
      <p style="margin-bottom:10px;font-size:.84rem;color:var(--text-2);">Richmond Agitation-Sedation Scale. Tap level for actions.</p>
      <div class="rass-grid" id="rassGrid"></div>
    </div>

    <!-- ── 17. BOARDING: SOFA Severity Context ── -->
    <div class="info-card" style="border-left:4px solid #2563eb;">
      <h3 style="color:#2563eb;">📊 SOFA Severity Context</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">Select organ dysfunction category. Quick severity context tool.</p>
      <div class="simple-grid">
        <div class="calc-field"><label class="calc-label">Resp</label><select id="sofaResp" class="calc-input"><option value="0">P/F >400</option><option value="1">P/F ≤400</option><option value="2">P/F ≤300</option><option value="3">P/F ≤200 on vent</option><option value="4">P/F ≤100 on vent</option></select></div>
        <div class="calc-field"><label class="calc-label">Coag</label><select id="sofaCoag" class="calc-input"><option value="0">Plt ≥150</option><option value="1">Plt &lt;150</option><option value="2">Plt &lt;100</option><option value="3">Plt &lt;50</option><option value="4">Plt &lt;20</option></select></div>
        <div class="calc-field"><label class="calc-label">Liver</label><select id="sofaLiver" class="calc-input"><option value="0">Bili &lt;1.2</option><option value="1">Bili 1.2–1.9</option><option value="2">Bili 2.0–5.9</option><option value="3">Bili 6.0–11.9</option><option value="4">Bili ≥12</option></select></div>
        <div class="calc-field"><label class="calc-label">CV</label><select id="sofaCv" class="calc-input"><option value="0">MAP ≥70</option><option value="1">MAP &lt;70</option><option value="2">Dop &lt;5 or Dob any</option><option value="3">Dop >5 / Epi or Norepi ≤0.1</option><option value="4">Dop >15 / Epi or Norepi >0.1</option></select></div>
        <div class="calc-field"><label class="calc-label">CNS</label><select id="sofaCns" class="calc-input"><option value="0">GCS 15</option><option value="1">GCS 13–14</option><option value="2">GCS 10–12</option><option value="3">GCS 6–9</option><option value="4">GCS &lt;6</option></select></div>
        <div class="calc-field"><label class="calc-label">Renal</label><select id="sofaRenal" class="calc-input"><option value="0">Cr &lt;1.2</option><option value="1">Cr 1.2–1.9</option><option value="2">Cr 2.0–3.4</option><option value="3">Cr 3.5–4.9</option><option value="4">Cr ≥5</option></select></div>
      </div>
      <div class="tool-result" style="border-color:rgba(37,99,235,.2);background:rgba(37,99,235,.07);">
        <div class="tr-label">SOFA Total</div><div class="tr-val" id="sofaTotal">0</div><div class="tr-badge hidden" id="sofaBadge"></div>
      </div>
    </div>

    <!-- ── 18. LIBERATION: Weaning & Extubation Readiness ── -->
    <div class="info-card" style="border-left:4px solid #16a34a;">
      <h3 style="color:#16a34a;">🫁 Weaning &amp; Extubation Readiness</h3>
      <p style="margin-bottom:12px;font-size:.84rem;color:var(--text-2);">RSBI = RR ÷ VT (in liters). Target &lt; 105 breaths/min/L.</p>
      <div class="tools-row">
        <div class="calc-field" style="flex:1">
          <label class="calc-label">Spontaneous RR (/min)</label>
          <input type="number" id="rsbiRr" class="calc-input" placeholder="e.g. 24" inputmode="decimal">
        </div>
        <div class="calc-field" style="flex:1">
          <label class="calc-label">Spontaneous VT (mL)</label>
          <input type="number" id="rsbiVt" class="calc-input" placeholder="e.g. 420" inputmode="decimal">
        </div>
      </div>
      <div class="tool-result" style="border-color:rgba(22,163,74,.2);background:rgba(22,163,74,.06);">
        <div class="tr-label" style="color:#16a34a;">RSBI</div>
        <div class="tr-val" id="rsbiVal">--</div>
        <div class="tr-unit">breaths / min / L</div>
        <div class="tr-badge hidden" id="rsbiBadge"></div>
      </div>
      <div class="stack-list">
        <div class="stack-item"><strong>SAT / analgesia review</strong><p>Continuous sedatives lightened, pain controlled, and no compelling reason for deep sedation.</p></div>
        <div class="stack-item"><strong>Respiratory readiness</strong><p>FiO₂ and PEEP modest, secretions manageable, cough present, and no uncontrolled acidosis.</p></div>
        <div class="stack-item"><strong>Airway / extubation risk</strong><p>Cuff leak considered when edema is a concern; high-risk patients may need NIV or HFNC immediately after extubation.</p></div>
      </div>
    </div>

  </section>
</main>

<!-- ████ BOTTOM NAV ████ -->
<nav class="bottom-nav" role="tablist" aria-label="Main navigation">
  <button class="nav-it active" data-target="view-scenarios" data-feature="scenarios" data-feature-name="Ventilation Scenarios" role="tab" aria-selected="true">
    <span class="nav-emoji">🏥</span><span>Scenarios</span>
  </button>
  <button class="nav-it" data-target="view-abg" data-feature="abg_calc" data-feature-name="ABG Calculator" role="tab" aria-selected="false">
    <span class="nav-emoji">🧪</span><span>ABG Calc</span>
  </button>
  <button class="nav-it" data-target="view-compare" data-feature="compare" data-feature-name="Scenario Comparison" role="tab" aria-selected="false">
    <span class="nav-emoji">📊</span><span>Compare</span>
  </button>
  <button class="nav-it" data-target="view-guide" data-feature="guide" data-feature-name="Clinical Guidelines" role="tab" aria-selected="false">
    <span class="nav-emoji">📖</span><span>Guide</span>
  </button>
  <button class="nav-it" data-target="view-tools" data-feature="tools" data-feature-name="Clinical Tools" role="tab" aria-selected="false">
    <span class="nav-emoji">🔧</span><span>Tools</span>
  </button>
</nav>

<!-- ████ FAB ████ -->
<button class="fab" id="fabCalc" data-feature="pbw_calc" data-feature-name="PBW Calculator" aria-label="Open PBW Calculator" title="PBW Calculator">⚖️</button>

<!-- ████ SCRIPT ████ -->
<script>
'use strict';

// ─── FEATURE GATE ENGINE ─────────────────────────────
const FG = (() => {
  const granted = new Set(window.__FEATURES || []);

  function has(key) {
    if (granted.has(key)) return true;
    let dot = key.lastIndexOf('.');
    while (dot > 0) {
      key = key.substring(0, dot);
      if (granted.has(key)) return true;
      dot = key.lastIndexOf('.');
    }
    return false;
  }

  function scan() {
    document.querySelectorAll('[data-feature]').forEach(el => {
      const key = el.dataset.feature;
      if (has(key)) return;
      if (el.classList.contains('nav-it')) {
        el.classList.add('feature-locked');
        const badge = document.createElement('span');
        badge.className = 'lock-badge';
        badge.textContent = '🔒';
        el.appendChild(badge);
      } else {
        el.classList.add('feature-locked-el');
      }
    });
  }

  function prompt(key) {
    const modal = document.getElementById('upgradeModal');
    if (!modal) return;
    const el = document.querySelector('[data-feature="' + key + '"]');
    const name = el ? (el.dataset.featureName || 'This feature') : 'This feature';
    document.getElementById('upgradeName').textContent = name;
    modal.classList.add('open');
  }

  return { has, scan, prompt };
})();

// ─── STORE (persistent state) ────────────────────────
const Store = {
  _d: { scenario:'healthy', pbw:null, sex:'male', unit:'cm', height:null, dark:false, disc:false, nivGate:false },
  _subs: [],

  init() {
    try {
      const saved = JSON.parse(localStorage.getItem('edvpro_state'));
      if (saved) Object.assign(this._d, saved);
    } catch(e) {}
    if (!localStorage.getItem('edvpro_state')) {
      this._d.dark = window.matchMedia('(prefers-color-scheme:dark)').matches;
    }
  },

  get(k) { return this._d[k]; },

  set(k, v) { this._d[k] = v; this._save(); this._notify(); },

  setBulk(o) { Object.assign(this._d, o); this._save(); this._notify(); },

  _save() {
    try { localStorage.setItem('edvpro_state', JSON.stringify(this._d)); } catch(e) {}
  },

  sub(fn) { this._subs.push(fn); },
  _notify() { this._subs.forEach(fn => fn(this._d)); }
};

// ─── SCENARIO DATA ───────────────────────────────────
const SCENARIOS = [
  {
    id:'healthy', name:'Healthy Lungs', emoji:'💙',
    theme:'#2563eb', rgb:'37,99,235',
    title:'Baseline Setup', sub:'Standard ED Initiation', badge:'🏥 STANDARD INITIATION',
    protocol:'Standard AC-VC',
    oxygenTarget:'SpO₂ 92–96%',
    co2Target:'PaCO₂ 35–45',
    safetyTitle:'Baseline ED Start-Up',
    safetyNote:'Balanced start. Reassess mechanics after intubation. Titrate FiO₂ toward SpO₂ 92–96% rapidly.',
    waveform:'normal',
    evidence:['ARDSNet NEJM 2000','SCCM/ATS Guidelines'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'The universally recommended ED default mode (Rosen’s / EMCrit). Reliable volume delivery.'},
      {key:'Tidal Volume', icon:'🫁', val:'8 mL/kg PBW', base:{min:6,max:8,target:8}, note:'Conventional default. Reduce if Pplat > 30 cmH₂O.'},
      {key:'Resp Rate',    icon:'⏱️', val:'10–12 breaths/min',     note:'Moderate starting rate. Adjust after ABG in 30–60 min.'},
      {key:'FiO₂',        icon:'💨', val:'1.0 → titrate',         note:'Start 100%. Titrate rapidly to SpO₂ 92–96%. Avoid O₂ toxicity.'},
      {key:'PEEP',         icon:'🔒', val:'5 cmH₂O',              note:'Physiologic baseline. Counteracts post-intubation atelectasis.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:2',                   note:'Standard timing — adequate expiration, no stacking.'}
    ]

  },
  {
    id:'sepsis', name:'Sepsis / Septic Shock', emoji:'🦠',
    theme:'#15803d', rgb:'21,128,61',
    title:'Sepsis / Septic Shock', sub:'Distributive Shock / ARDS Risk', badge:'⚠️ PERI-INTUBATION SHOCK WINDOW',
    protocol:'Resuscitate-Before-Tube',
    oxygenTarget:'SpO₂ 92–96%',
    co2Target:'Match acidosis initially',
    safetyTitle:'Resuscitate Before the Tube',
    safetyNote:'If shock is present, begin balanced crystalloid when fluid responsive and start vasopressors early rather than waiting for central access. Avoid prolonged apnea; push-dose vasopressors should follow local protocol.',
    waveform:'normal',
    evidence:['SSC 2026','INTUBE 2022','Peri-intubation Review 2025'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'Predictable minute ventilation and easy plateau checks while shock physiology is evolving.'},
      {key:'Tidal Volume', icon:'🫁', val:'6–8 mL/kg PBW',         base:{min:6,max:8,target:6}, note:'Bias low if infiltrates or hypoxemia suggest evolving ARDS; never use actual body weight.'},
      {key:'Resp Rate',    icon:'⏱️', val:'16–24 breaths/min',     note:'Sepsis often has lactic acidosis and high native drive; avoid dropping minute ventilation abruptly.'},
      {key:'FiO₂',         icon:'💨', val:'1.0 → titrate',         note:'Start high, then titrate rapidly once oxygenation stabilizes.'},
      {key:'PEEP',         icon:'🔒', val:'5–8 cmH₂O',            note:'Enough to prevent derecruitment, but reassess blood pressure after every increase.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:2',                   note:'Standard timing unless obstructive physiology coexists.'}
    ]

  },
  {
    id:'asthma-copd', name:'Asthma/COPD', emoji:'🌬️',
    theme:'#d97706', rgb:'217,119,6',
    title:'Obstructive Disease', sub:'High Resistance / Air Trapping', badge:'⚠️ AIR TRAPPING RISK',
    protocol:'Obstructive Strategy',
    oxygenTarget:'Asthma 93–95%; COPD 88–92%',
    co2Target:'Permissive hypercapnia',
    safetyTitle:'Watch for Auto-PEEP',
    safetyNote:'Prioritize long expiratory time and permissive hypercapnia; treat auto-PEEP and dynamic hyperinflation before chasing a normal PaCO₂.',
    waveform:'obstructive',
    evidence:['GINA 2025','GOLD 2026','ATS Acute Asthma / ERS-ATS NIV'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'Use a mode that allows close monitoring and avoids breath stacking.'},
      {key:'Tidal Volume', icon:'🫁', val:'6 mL/kg PBW', base:{min:6,max:8,target:6}, note:'Lung-protective. Expect high Peak Pressures (PIP) due to resistance; monitor Pplat instead.'},
      {key:'Resp Rate',    icon:'⏱️', val:'8–12 breaths/min',      note:'🔑 Start low and titrate to pH and auto-PEEP; permissive hypercapnia is often safer than dynamic hyperinflation.'},
      {key:'FiO₂',        icon:'💨', val:'1.0 → titrate',         note:'Start 100%, then titrate to the lowest FiO₂ that maintains adequate saturation; asthma often targets 93–95%, COPD 88–92%.'},
      {key:'PEEP',         icon:'🔒', val:'0–5 cmH₂O',            note:'Start low; if trigger effort is high, external PEEP can be titrated cautiously against measured auto-PEEP.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:4 to 1:5',           note:'🔑 Increase Inspiratory Flow (e.g., 60–80+ L/min) to deliver breath faster and maximize expiratory time.'}
    ]

  },
  {
    id:'ards', name:'ARDS', emoji:'🫁',
    theme:'#7c3aed', rgb:'124,58,237',
    title:'ARDS Protocol', sub:'Poor Compliance / Stiff Lungs', badge:'⚠️ ARDS — STRICT PROTOCOL',
    protocol:'ARDSNet / Prone Early',
    oxygenTarget:'SpO₂ 88–95%',
    co2Target:'pH ≥ 7.20 usually',
    safetyTitle:'Keep Pplat ≤ 30 cmH₂O',
    safetyNote:'Use lung-protective ventilation, keep Pplat ≤ 30 cmH₂O, follow driving pressure closely, and consider early proning when P/F ≤ 150.',
    waveform:'ards',
    evidence:['ATS/ESICM/SCCM ARDS','Global ARDS Definition 2024','ARDSNet / PROSEVA'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'Use a mode that reliably delivers lung-protective VT and permits plateau pressure checks.'},
      {key:'Tidal Volume', icon:'🫁', val:'6 mL/kg PBW', base:{min:4,max:8,target:6}, note:'🔑 Start at 6. Can reduce to 4 mL/kg if Pplat > 30 cmH₂O.'},
      {key:'Resp Rate',    icon:'⏱️', val:'18–30 breaths/min',     note:'Often needs a higher RR to support pH with low VT; max about 35 in ARDSNet while tolerating permissive hypercapnia if needed.'},
      {key:'FiO₂',        icon:'💨', val:'1.0 → per table',       note:'Use ARDSNet PEEP/FiO₂ table. Target SpO₂ 88–95%.'},
      {key:'PEEP',         icon:'🔒', val:'PEEP/FiO₂ table',      note:'Use an ARDSNet low-PEEP/high-FiO₂ or higher-PEEP strategy; avoid PEEP < 5 and reassess hemodynamics.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:2',                   note:'Standard. Monitor for auto-PEEP if RR is high.'}
    ]

  },
  {
    id:'neuro', name:'Neuro / TBI / Stroke', emoji:'🧠',
    theme:'#4f46e5', rgb:'79,70,229',
    title:'Neurocritical Ventilation', sub:'TBI / Stroke / Status Epilepticus', badge:'🧠 NORMOCAPNIA MATTERS',
    protocol:'ENLS / TBI Guidance',
    oxygenTarget:'SpO₂ ≥ 94%',
    co2Target:'pH 7.35–7.45',
    safetyTitle:'Avoid Hypoxia and CO₂ Swings',
    safetyNote:'Target normocapnia unless there is acute cerebral herniation. Keep the head elevated 30°, avoid hypotension, and be cautious with unnecessary high PEEP.',
    waveform:'normal',
    evidence:['ENLS 6.0','ACS TBI 2024','BTF 4th Edition'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'Allows tight control of PaCO₂ and rapid blood-gas driven adjustments.'},
      {key:'Tidal Volume', icon:'🫁', val:'6–8 mL/kg PBW',         base:{min:6,max:8,target:6}, note:'Use lung-protective VT rather than increasing VT to chase CO₂.'},
      {key:'Resp Rate',    icon:'⏱️', val:'14–18 breaths/min',     note:'Adjust RR to target premorbid PaCO₂ when known, otherwise 35–45 mmHg; brief hyperventilation is a rescue for herniation, not routine care.'},
      {key:'FiO₂',         icon:'💨', val:'1.0 → target ≥94%',    note:'Prevent hypoxia aggressively, then titrate to the lowest FiO₂ that safely maintains oxygenation.'},
      {key:'PEEP',         icon:'🔒', val:'5–8 cmH₂O',            note:'Moderate PEEP is often tolerated; if you need >10, reassess ICP/CPP or clinical brain perfusion.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:2',                   note:'Standard timing. Avoid unnecessary air trapping that may worsen venous return.'}
    ]

  },
  {
    id:'rosc', name:'Post-ROSC', emoji:'⚡',
    theme:'#0f766e', rgb:'15,118,110',
    title:'Post-Cardiac Arrest Care', sub:'Ventilator as Part of the Post-Resuscitation Bundle', badge:'⚡ AVOID HYPEROXIA / HYPOCAPNIA',
    protocol:'Post-Arrest Normocapnia',
    oxygenTarget:'SpO₂ 90–98%',
    co2Target:'PaCO₂ 35–45',
    safetyTitle:'Avoid Hyperoxia, Hypocapnia, Hypotension',
    safetyNote:'Once reliable monitoring is available, titrate oxygen away from 100%, keep PaCO₂ 35–45 mmHg, maintain MAP ≥65, and treat temperature and seizures as part of the same bundle.',
    waveform:'normal',
    evidence:['AHA PCAC 2025','ILCOR CoSTR','AHA/NCS Post-Arrest'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'Simple controlled ventilation supports tight gas control during the first post-arrest hours.'},
      {key:'Tidal Volume', icon:'🫁', val:'6–8 mL/kg PBW',         base:{min:6,max:8,target:6}, note:'Use lung-protective VT unless another physiology forces a different approach.'},
      {key:'Resp Rate',    icon:'⏱️', val:'10–16 breaths/min',     note:'Adjust to keep PaCO₂ in the normal range; ETCO₂ often underestimates PaCO₂ after ROSC.'},
      {key:'FiO₂',         icon:'💨', val:'1.0 → target 90–98%',  note:'Start with 100% until oxygenation is reliably measured, then titrate to avoid hyperoxia.'},
      {key:'PEEP',         icon:'🔒', val:'5–8 cmH₂O',            note:'Reasonable baseline unless pulmonary edema or aspiration require more recruitment.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:2',                   note:'Standard timing with frequent ABG confirmation.'}
    ]
  },
  {
    id:'niv', name:'BiPAP / NIV', emoji:'😷',
    theme:'#06b6d4', rgb:'6,182,212',
    title:'Non-Invasive Ventilation', sub:'Best supported for COPD or cardiogenic pulmonary edema', badge:'😷 NIV — SELECT THE RIGHT PATIENT',
    protocol:'ERS/ATS NIV',
    oxygenTarget:'COPD 88–92% or scenario target',
    co2Target:'Relieve work of breathing',
    safetyTitle:'Monitor Airway Protection',
    safetyNote:'Best-supported for hypercapnic COPD exacerbation or cardiogenic pulmonary edema. Do not use as default therapy for de novo hypoxemic ARF/ARDS without expert monitoring.',
    waveform:'niv',
    requiresGate:true,
    evidence:['ERS/ATS NIV Guideline','GOLD 2026','ERS/ATS acute respiratory failure'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'BiPAP (S/T)',           note:'Spontaneous/Timed mode — patient triggers with backup rate.'},
      {key:'IPAP',         icon:'⬆️', val:'10–15 cmH₂O',          note:'🔑 Drives ventilation and CO₂ clearance. Titrate +2 cmH₂O for CO₂ retention.'},
      {key:'EPAP',         icon:'⬇️', val:'5–8 cmH₂O',            note:'For CPE, do not hesitate to titrate EPAP up to 10–12 cmH₂O to aggressively reduce LV preload and afterload.'},
      {key:'FiO₂',        icon:'💨', val:'Titrate to target',      note:'COPD: often 88–92%. Cardiogenic edema or other causes usually target higher saturations if tolerated.'},
      {key:'Backup Rate',  icon:'⏱️', val:'12–16 breaths/min',     note:'Delivers breath if patient becomes apneic or rate drops below threshold.'},
      {key:'Max I-Time',   icon:'⌛', val:'1.0–1.2 sec',           note:'Prevents excessively long inspirations causing discomfort/dyssynchrony.'}
    ]

  },
  {
    id:'cpe', name:'Cardiogenic Edema', emoji:'🌊',
    theme:'#dc2626', rgb:'220,38,38',
    title:'Cardiogenic Pulmonary Edema', sub:'Failed NIV / Wet Restrictive Lungs', badge:'💧 POSITIVE PRESSURE IS THERAPY',
    protocol:'Recruit and Offload',
    oxygenTarget:'SpO₂ 92–96%',
    co2Target:'Treat work of breathing',
    safetyTitle:'NIV First, But Recruit When Tubed',
    safetyNote:'If NIV fails, the invasive vent should preserve the physiologic benefit of positive pressure: lung-protective VT, higher PEEP than a normal lung, and ongoing afterload/preload management.',
    waveform:'restrictive',
    evidence:['ESC HF 2021','ERS/ATS NIV','Acute Pulmonary Oedema Algorithms'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'A controlled mode provides reliable oxygenation while the heart failure therapy works.'},
      {key:'Tidal Volume', icon:'🫁', val:'6–8 mL/kg PBW',         base:{min:6,max:8,target:6}, note:'Treat as a stiff-lung physiology rather than a healthy-lung intubation.'},
      {key:'Resp Rate',    icon:'⏱️', val:'18–24 breaths/min',     note:'Helps unload work of breathing without driving excessive mean pressure.'},
      {key:'FiO₂',         icon:'💨', val:'1.0 → titrate',         note:'Start high, then step down once oxygenation and afterload improve.'},
      {key:'PEEP',         icon:'🔒', val:'8–12 cmH₂O',           note:'Recruit flooded alveoli and support preload/afterload reduction if blood pressure tolerates it.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:1.5 to 1:2',         note:'Short inspiratory times are reasonable; avoid stacking in mixed obstructive disease.'}
    ]

  },
  {
    id:'hypovolemia', name:'Hypovolemia', emoji:'🩸',
    theme:'#be123c', rgb:'190,18,60',
    title:'Hemodynamic Instability', sub:'Volume-Depleted Patient', badge:'🩸 LOW PRELOAD — CAUTION',
    protocol:'Preload-Protective',
    oxygenTarget:'SpO₂ 92–96%',
    co2Target:'Match pre-tube drive',
    safetyTitle:'Maintain Adequate Preload',
    safetyNote:'Positive pressure can reduce venous return and precipitate collapse. Resuscitate first with blood products or balanced crystalloid when indicated, and keep vasopressors ready.',
    waveform:'normal',
    evidence:['Peri-intubation Shock Literature','Hemodynamic Ventilation Physiology'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'Standard start. Have vasopressors and resuscitation products ready at bedside.'},
      {key:'Tidal Volume', icon:'🫁', val:'8 mL/kg PBW', base:{min:6,max:8,target:8}, note:'Standard volume. Reassess if Pplat elevated.'},
      {key:'Resp Rate',    icon:'⏱️', val:'10–12 breaths/min',     note:'Monitor acid-base status closely. Compensatory tachypnea common.'},
      {key:'FiO₂',        icon:'💨', val:'1.0 → titrate',         note:'Start high. Titrate as hemodynamics and oxygenation improve.'},
      {key:'PEEP',         icon:'🔒', val:'0–5 cmH₂O',            note:'🔑 Low PEEP protects venous return. Raise only after preload and blood pressure are supported.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:2',                   note:'Default. Reassess after fluid resuscitation.'}
    ]

  },
  {
    id:'dka', name:'DKA / Metabolic Acidosis', emoji:'🧪',
    theme:'#c026d3', rgb:'192,38,211',
    title:'Metabolic Acidosis Rescue', sub:'Kussmaul Compensation Must Be Preserved', badge:'⚠️ MATCH THE MINUTE VENTILATION',
    protocol:'Compensation-Preserving',
    oxygenTarget:'SpO₂ 92–96%',
    co2Target:'Preserve compensation',
    safetyTitle:'Match Minute Ventilation Immediately',
    safetyNote:'Do not intubate unless absolutely necessary. If you must intubate, preoxygenate without losing compensation when possible and set the vent to match or exceed the pre-intubation minute ventilation from the first breath.',
    waveform:'normal',
    evidence:['DKA Consensus 2024','NAEMSP Ventilation 2022','High-Risk Airway Review 2020'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'Controlled ventilation lets you deliberately preserve high minute ventilation.'},
      {key:'Tidal Volume', icon:'🫁', val:'6–8 mL/kg PBW',         base:{min:6,max:8,target:8}, note:'Use PBW-based VT; the usual compensatory need is met mostly by RR, not huge VT.'},
      {key:'Resp Rate',    icon:'⏱️', val:'24–35 breaths/min',     note:'Often much higher than “usual” ED starts. Match the patient, then down-titrate only as the acidosis corrects.'},
      {key:'FiO₂',         icon:'💨', val:'1.0 → titrate',         note:'Start high during the procedure, then titrate once the patient is stable.'},
      {key:'PEEP',         icon:'🔒', val:'5 cmH₂O',              note:'Low baseline PEEP is usually adequate unless another oxygenation problem coexists.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:1.5 to 1:2',         note:'No obstructive physiology here; the priority is raw minute ventilation.'}
    ]

  },
  {
    id:'pe', name:'Massive / Submassive PE', emoji:'🫀',
    theme:'#7c2d12', rgb:'124,45,18',
    title:'Pulmonary Embolism', sub:'RV Failure / Mean Airway Pressure Sensitive', badge:'🚨 HIGHEST-RISK INTUBATION',
    protocol:'Low-Pressure RV Strategy',
    oxygenTarget:'SpO₂ > 92%',
    co2Target:'Avoid severe acidaemia',
    safetyTitle:'Avoid the Intubation Spiral',
    safetyNote:'If the patient can tolerate it, prefer HFNC or NIV while reperfusion therapy is organized. If intubation is unavoidable, minimize PEEP and mean airway pressure, preload carefully, start vasopressors early, and keep thrombolysis/catheter/surgical reperfusion decisions moving.',
    waveform:'normal',
    evidence:['ESC/ERS PE 2019','INTUBE 2022','Peri-intubation Review 2025'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'Use a simple controlled mode and avoid prolonged inspiratory times.'},
      {key:'Tidal Volume', icon:'🫁', val:'6 mL/kg PBW',            base:{min:6,max:8,target:6}, note:'Limit plateau and mean airway pressure to protect the failing RV.'},
      {key:'Resp Rate',    icon:'⏱️', val:'10–14 breaths/min',     note:'Enough to prevent worsening acidosis, but not so high that expiratory time shortens or mean pressure rises.'},
      {key:'FiO₂',         icon:'💨', val:'1.0 → titrate',         note:'Correct hypoxemia quickly, but avoid long periods of unnecessary hyperoxia once stable.'},
      {key:'PEEP',         icon:'🔒', val:'0–5 cmH₂O',            note:'PEEP can collapse preload in high-risk PE; use the minimum needed for oxygenation.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:2',                   note:'Shorter inspiratory time helps keep mean airway pressure down.'}
    ]

  },
  {
    id:'anaphylaxis', name:'Anaphylaxis / Airway Edema', emoji:'🐝',
    theme:'#b45309', rgb:'180,83,9',
    title:'Anaphylaxis / Edematous Airway', sub:'Difficult Airway + Residual Bronchospasm', badge:'⚠️ AIRWAY CAN DISAPPEAR',
    protocol:'Early Epi + Obstructive Vent',
    oxygenTarget:'SpO₂ ≥ 94%',
    co2Target:'Avoid breath stacking',
    safetyTitle:'Airway Can Disappear',
    safetyNote:'Prioritize epinephrine and expert airway backup. After intubation, expect a smaller tube and manage residual lower-airway obstruction with an obstructive-style ventilator strategy.',
    waveform:'obstructive',
    evidence:['Anaphylaxis 2023 Practice Parameter','Obstructive Vent Principles','Difficult Airway Guidelines'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'Provides controlled ventilation while edema and bronchospasm are treated.'},
      {key:'Tidal Volume', icon:'🫁', val:'6–8 mL/kg PBW',         base:{min:6,max:8,target:6}, note:'Keep VT modest if bronchospasm is still active.'},
      {key:'Resp Rate',    icon:'⏱️', val:'10–14 breaths/min',     note:'Lower rates protect expiratory time if wheeze and dynamic obstruction persist.'},
      {key:'FiO₂',         icon:'💨', val:'1.0 → titrate',         note:'Start high during the airway phase, then titrate once edema and bronchospasm improve.'},
      {key:'PEEP',         icon:'🔒', val:'0–5 cmH₂O',            note:'Use low PEEP if severe obstruction dominates; if edema/recruitment dominates, modest PEEP may help.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:3 to 1:4',           note:'Bias toward longer exhalation until airway resistance normalizes.'}
    ]

  },
  {
    id:'obesity', name:'Obesity (BMI >35)', emoji:'⚖️',
    theme:'#475569', rgb:'71,85,105',
    title:'Obesity Ventilation', sub:'Low FRC / Fast Derecruitment', badge:'🪜 PBW + POSITION + PEEP',
    protocol:'Head-Up Recruitment',
    oxygenTarget:'SpO₂ 92–96%',
    co2Target:'Keep VE adequate',
    safetyTitle:'PBW, PEEP, Position',
    safetyNote:'Dose VT to PBW, not actual body weight. Expect rapid derecruitment and difficult mask ventilation; start head-up or reverse Trendelenburg and use more PEEP than a non-obese lung if tolerated.',
    waveform:'restrictive',
    evidence:['Obese ICU Ventilation Review 2020','How I Ventilate Obese Patient 2019','Protective Ventilation Reviews'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'Simple controlled ventilation is usually easiest while recruitment and positioning are optimized.'},
      {key:'Tidal Volume', icon:'🫁', val:'6–8 mL/kg PBW',         base:{min:6,max:8,target:6}, note:'PBW is critical here because actual body weight grossly overestimates safe VT.'},
      {key:'Resp Rate',    icon:'⏱️', val:'16–22 breaths/min',     note:'Higher RR may be needed because chest wall load increases work of breathing and CO₂ retention risk.'},
      {key:'FiO₂',         icon:'💨', val:'1.0 → titrate',         note:'Start high during the peri-intubation phase; derecruitment is common if you under-support early.'},
      {key:'PEEP',         icon:'🔒', val:'8–12 cmH₂O',           note:'Many obese patients need moderate PEEP to stay open, but aggressive PEEP can worsen hypotension. Titrate to oxygenation, driving pressure, and blood pressure.'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:2',                   note:'Standard timing unless obstructive physiology coexists.'}
    ]

  },
  {
    id:'pregnancy', name:'Pregnancy', emoji:'🤰',
    theme:'#db2777', rgb:'219,39,119',
    title:'Obstetric Patient', sub:'Altered Physiology — Dual Priority', badge:'🤰 OB — DUAL PRIORITIES',
    protocol:'Maternal-Fetal Oxygenation',
    oxygenTarget:'SpO₂ ≥ 95%',
    co2Target:'PaCO₂ about 28–32',
    safetyTitle:'Dual Priority: Mother & Fetus',
    safetyNote:'Maternal SpO₂ > 95% is critical for fetal oxygenation. Left lateral tilt. Use pre-pregnancy height for PBW.',
    waveform:'restrictive',
    evidence:['ACOG Critical Care in Pregnancy','Adult ICU principles','ARDSNet PBW'],
    params:[
      {key:'Mode',         icon:'🎛️', val:'Assist-Control (VC)',   note:'Reliable volume. Monitor for dyssynchrony.'},
      {key:'Tidal Volume', icon:'🫁', val:'6 mL/kg PBW', base:{min:6,max:8,target:6}, note:'🔑 Use PRE-PREGNANCY height. FRC is reduced by gravid uterus.'},
      {key:'Resp Rate',    icon:'⏱️', val:'14–18 breaths/min',     note:'Matches physiologic hyperventilation (baseline CO₂ ≈ 32 mmHg in pregnancy).'},
      {key:'FiO₂',        icon:'💨', val:'1.0 → target ≥95%',    note:'🔑 Target SpO₂ ≥ 95% — fetal O₂ delivery depends on maternal sat.'},
      {key:'PEEP',         icon:'🔒', val:'5–8 cmH₂O',            note:'Higher PEEP needed — gravid uterus compresses diaphragm (↓ FRC).'},
      {key:'I:E Ratio',    icon:'↔️', val:'1:2',                   note:'Standard. Involve OB team and consider fetal monitoring.'}
    ]

  }
];

// ─── WAVEFORM RENDERER ───────────────────────────────
function renderWaveform(type) {
  const svg = document.getElementById('waveformSvg');
  const cap = document.getElementById('waveCaption');
  const PATHS = {
    normal: {
      pressure: 'M0,70 L20,70 L60,20 L80,20 L90,70 L180,70 L200,70 L240,20 L260,20 L270,70 L360,70',
      flow:     'M0,120 L20,120 L25,85 L80,85 L85,120 C100,160 140,140 180,120 L200,120 L205,85 L260,85 L265,120 C280,160 320,140 360,120',
      txt: 'Normal Mechanics — Square flow, normal peak/plateau pressure, flow returns to baseline.'
    },
    obstructive: {
      pressure: 'M0,70 L20,70 L40,10 L50,40 L90,40 L100,70 L250,70 L270,70 L290,10 L300,40 L340,40 L350,70 L400,70',
      flow:     'M0,120 L20,120 L25,85 L90,85 L95,120 C110,160 180,150 250,130 L270,130 L275,85 L340,85 L345,120 C360,160 400,150 400,145',
      txt: 'Obstructive — High peak pressure, prolonged expiratory flow failing to reach zero (Auto-PEEP).'
    },
    ards: {
      pressure: 'M0,60 L20,60 L50,20 L80,20 L90,60 L160,60 L180,60 L210,20 L240,20 L250,60 L320,60 L340,60 L370,20 L400,20',
      flow:     'M0,120 L20,120 L25,85 L80,85 L85,120 C100,150 120,130 160,120 L180,120 L185,85 L240,85 L245,120 C260,150 280,130 320,120 L340,120 L345,85 L400,85',
      txt: 'ARDS (Stiff Lungs) — High PEEP (baseline), rapid pressure rise to high plateau, rapid exhalation.'
    },
    restrictive: {
      pressure: 'M0,60 L20,60 L25,25 L80,25 L90,60 L160,60 L180,60 L185,25 L240,25 L250,60 L320,60',
      flow:     'M0,120 L20,120 L25,80 L80,120 L85,120 C100,150 130,130 160,120 L180,120 L185,80 L240,120 L245,120 C260,150 290,130 320,120',
      txt: 'Restrictive (Pressure Control) — Decelerating flow, square pressure wave, elevated PEEP.'
    },
    niv: {
      pressure: 'M0,65 L20,65 L30,30 L60,30 L70,65 L120,65 L130,30 L160,30 L170,65 L220,65 L230,30 L260,30 L270,65 L320,65',
      flow:     'M0,120 L20,120 L25,85 Q40,100 60,120 L65,120 C75,140 90,130 120,120 L125,85 Q140,100 160,120 L165,120 C175,140 190,130 220,120 L225,85 Q240,100 260,120 L265,120 C275,140 290,130 320,120',
      txt: 'BiPAP (NIV) — Spontaneous flow patterns, cycling between EPAP and IPAP.'
    }
  };
  const d = PATHS[type] || PATHS.normal;
  
  svg.setAttribute('viewBox', '0 0 400 160');
  svg.style.minHeight = '140px';
  
  svg.innerHTML = `
    <!-- Grid -->
    <g stroke="var(--border)" stroke-width="1" stroke-dasharray="4 4">
      <line x1="0" y1="35" x2="400" y2="35"/>
      <line x1="0" y1="70" x2="400" y2="70"/>
      <line x1="0" y1="120" x2="400" y2="120"/>
    </g>
    
    <!-- Pressure Waveform (Yellow) -->
    <path d="${d.pressure}" fill="none" stroke="#f59e0b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    <text x="10" y="20" fill="#f59e0b" font-size="10" font-weight="700" font-family="DM Sans,sans-serif">Pressure (cmH₂O)</text>
    
    <!-- Flow Waveform (Green) -->
    <path d="${d.flow}" fill="none" stroke="#10b981" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
    <text x="10" y="105" fill="#10b981" font-size="10" font-weight="700" font-family="DM Sans,sans-serif">Flow (L/min)</text>
  `;
  cap.textContent = d.txt;
}

// ─── ABG INTERPRETER ────────────────────────────────
function interpretABG(ph, pco2, hco3) {
  if (!ph || !pco2 || !hco3) return null;
  const acid = ph < 7.35, alk = ph > 7.45;
  let primary = '', comp = '';
  if (acid) {
    if (pco2 > 45 && hco3 < 22) primary = 'Mixed Respiratory & Metabolic Acidosis';
    else if (pco2 > 45) {
      primary = 'Respiratory Acidosis';
      const exp = 24 + (pco2 - 40) * 0.1;
      if (hco3 > exp + 2) comp = 'with metabolic compensation';
      else if (hco3 < exp - 2) comp = '+ concurrent Metabolic Acidosis';
    } else if (hco3 < 22) {
      primary = 'Metabolic Acidosis';
      const exp = 1.5 * hco3 + 8;
      if (pco2 < exp - 2) comp = 'with respiratory compensation';
      else if (pco2 > exp + 2) comp = '+ concurrent Respiratory Acidosis';
    }
  } else if (alk) {
    if (pco2 < 35 && hco3 > 26) primary = 'Mixed Respiratory & Metabolic Alkalosis';
    else if (pco2 < 35) {
      primary = 'Respiratory Alkalosis';
      const exp = 24 - (40 - pco2) * 0.2;
      if (hco3 < exp - 2) comp = 'with metabolic compensation';
    } else if (hco3 > 26) {
      primary = 'Metabolic Alkalosis';
      const exp = 40 + (hco3 - 24) * 0.7;
      if (pco2 > exp + 2) comp = 'with respiratory compensation';
    }
  } else {
    primary = 'Normal ABG or fully compensated';
  }
  return primary + (comp ? ' ' + comp : '');
}

// ─── DYNAMIC TIDAL VOLUME ───────────────────────────
function getDynTV(param, pbw) {
  if (param.key === 'Tidal Volume' && pbw && param.base) {
    const tv  = Math.round(pbw * param.base.target);
    const min = Math.round(pbw * param.base.min);
    const max = Math.round(pbw * param.base.max);
    return {
      html: `<span style="color:var(--theme)">${tv} mL</span> <span style="font-size:.82rem;font-weight:600">(${param.base.target} mL/kg)</span>`,
      range: `Range: ${min}–${max} mL`,
      text: `${tv} mL (${param.base.target} mL/kg PBW)`
    };
  }
  return { html: param.val, range: null, text: param.val };
}

// ─── APP ─────────────────────────────────────────────
const App = {
  _toolsBound: false,
  _viewportLocked: false,
  _chipSnapTimer: null,
  _chipSnapAdjusting: false,

  init() {
    Store.init();
    this._lockViewport();
    this._applyDark();
    this._checkDisclaimer();
    this._renderChips();
    this._renderScenarioSelect();
    this._setupChipRail();
    this._setupNav();
    this._setupCalcModal();
    this._setupABG();
    this._setupGate();
    this._setupEHR();
    this._setupTooltips();
    this._setupKeyboard();
    this._setupPrint();
    this._setupTools();
    this._setupCompareModes();
    this._updateUI();

    // Feature gating — scan and lock UI elements
    FG.scan();
    document.getElementById('closeUpgrade')?.addEventListener('click', () => {
      document.getElementById('upgradeModal').classList.remove('open');
    });
    document.getElementById('upgradeModal')?.addEventListener('click', (e) => {
      if (e.target.id === 'upgradeModal') e.target.classList.remove('open');
    });

    Store.sub(() => this._updateUI());
  },

  // ── App-like viewport lock ────────────────────────
  _lockViewport() {
    if (this._viewportLocked) return;
    this._viewportLocked = true;

    let lastTouchEnd = 0;

    const stopGesture = e => e.preventDefault();
    const stopMultiTouch = e => {
      if (e.touches && e.touches.length > 1) e.preventDefault();
    };
    const stopDoubleTapZoom = e => {
      const now = Date.now();
      if (now - lastTouchEnd <= 300) e.preventDefault();
      lastTouchEnd = now;
    };

    document.addEventListener('gesturestart', stopGesture, { passive:false });
    document.addEventListener('gesturechange', stopGesture, { passive:false });
    document.addEventListener('touchmove', stopMultiTouch, { passive:false });
    document.addEventListener('touchend', stopDoubleTapZoom, { passive:false });
  },

  // ── Dark mode ──────────────────────────────────────
  _applyDark() {
    const d = Store.get('dark');
    document.documentElement.classList.toggle('dark', d);
    document.getElementById('moonIcon').style.display = d ? 'none' : 'block';
    document.getElementById('sunIcon').style.display  = d ? 'block' : 'none';
    document.getElementById('themeColorMeta').setAttribute('content', d ? '#0b0f1a' : Store.get('theme') || '#2563eb');
  },

  // ── Disclaimer ─────────────────────────────────────
  _checkDisclaimer() {
    const modal = document.getElementById('disclaimerModal');
    if (Store.get('disc')) { modal.classList.remove('open'); return; }
    document.getElementById('acceptBtn').addEventListener('click', () => {
      Store.set('disc', true);
      modal.classList.remove('open');
      this.toast('👋 Welcome to ED VentGuide Pro!', 'success');
    });
  },

  // ── Theme ──────────────────────────────────────────
  _setTheme(s) {
    document.documentElement.style.setProperty('--theme',  s.theme);
    document.documentElement.style.setProperty('--theme-rgb', s.rgb);
    document.documentElement.style.setProperty('--theme-light', `rgba(${s.rgb},.09)`);
    document.documentElement.style.setProperty('--theme-mid',   `rgba(${s.rgb},.18)`);
    document.getElementById('appHeader').style.background = s.theme;
    document.getElementById('themeColorMeta').setAttribute('content', s.theme);
    document.getElementById('hTitle').textContent = s.title;
    document.getElementById('hSub').textContent   = s.sub;
    document.getElementById('hBadge').textContent  = s.badge;
    document.getElementById('safetyTitle').textContent = s.safetyTitle;
    document.getElementById('safetyNote').textContent  = s.safetyNote;
  },

  // ── Chips ──────────────────────────────────────────
  _renderChips() {
    const wrap = document.getElementById('chipsWrap');
    const active = Store.get('scenario');
    wrap.innerHTML = '';
    SCENARIOS.forEach((s, i) => {
      const btn = document.createElement('button');
      btn.className = `chip${s.id === active ? ' active' : ''}`;
      btn.dataset.id = s.id;
      btn.setAttribute('role', 'tab');
      btn.setAttribute('aria-selected', s.id === active);
      btn.setAttribute('tabindex', '0');
      btn.innerHTML = `
        <span class="chip-emoji">${s.emoji}</span>
        <span class="chip-label chip-label-full">${s.name}</span>
        <span class="chip-label chip-label-short">${this._getChipShortLabel(s)}</span>
      `;

      btn.addEventListener('click', () => this._selectScenario(s.id));
      btn.addEventListener('keydown', e => {
        if (e.key === 'ArrowRight') { const n = wrap.children[i+1] || wrap.children[0]; n.focus(); n.click(); }
        if (e.key === 'ArrowLeft')  { const p = wrap.children[i-1] || wrap.children[wrap.children.length-1]; p.focus(); p.click(); }
      });
      wrap.appendChild(btn);
    });
    setTimeout(() => {
      const a = wrap.querySelector('.active');
      if (a) {
        const inlineMode = window.matchMedia('(max-width:640px)').matches ? 'nearest' : 'center';
        a.scrollIntoView({ behavior:'smooth', block:'nearest', inline:inlineMode });
      }
    }, 60);
  },

  _renderScenarioSelect() {
    const select = document.getElementById('scenarioSelect');
    if (!select) return;

    select.innerHTML = '';
    const groups = [
      {
        label: 'Common ED / high-risk',
        ids: ['sepsis','asthma-copd','cpe','dka','neuro','rosc','pe','hypovolemia']
      },
      {
        label: 'Ventilation patterns',
        ids: ['healthy','ards','niv']
      },
      {
        label: 'Special airway / population',
        ids: ['pregnancy','anaphylaxis','obesity']
      }
    ];
    const used = new Set();

    groups.forEach(group => {
      const optgroup = document.createElement('optgroup');
      optgroup.label = group.label;
      group.ids.forEach(id => {
        const s = SCENARIOS.find(x => x.id === id);
        if (!s) return;
        used.add(id);
        const opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = `${s.emoji} ${s.name}`;
        optgroup.appendChild(opt);
      });
      if (optgroup.children.length) select.appendChild(optgroup);
    });

    SCENARIOS.filter(s => !used.has(s.id)).forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = `${s.emoji} ${s.name}`;
      select.appendChild(opt);
    });
    select.value = Store.get('scenario');

    if (!select.dataset.bound) {
      select.dataset.bound = '1';
      select.addEventListener('change', e => this._selectScenario(e.target.value));
    }
  },

  _getChipShortLabel(s) {
    const labels = {
      healthy: 'Healthy',
      'asthma-copd': 'Asthma/COPD',
      ards: 'ARDS',
      hypovolemia: 'Hypovolemia',
      pregnancy: 'Pregnancy',
      niv: 'BiPAP/NIV',
      sepsis: 'Sepsis',
      neuro: 'Neuro/TBI',
      pe: 'PE',
      dka: 'DKA',
      cpe: 'CPE',
      anaphylaxis: 'Anaphylaxis',
      obesity: 'Obesity',
      rosc: 'Post-ROSC'
    };
    return labels[s.id] || s.name;
  },

  _setupChipRail() {
    const wrap = document.getElementById('chipsWrap');
    if (!wrap || wrap.dataset.snapBound) return;
    wrap.dataset.snapBound = '1';

    wrap.addEventListener('scroll', () => {
      if (!window.matchMedia('(max-width:640px)').matches || this._chipSnapAdjusting) return;
      clearTimeout(this._chipSnapTimer);
      this._chipSnapTimer = setTimeout(() => {
        const firstChip = wrap.querySelector('.chip');
        if (!firstChip) return;
        const gap = parseFloat(getComputedStyle(wrap).gap || '0');
        const step = firstChip.getBoundingClientRect().width + gap;
        if (!step) return;
        const target = Math.round(wrap.scrollLeft / step) * step;
        if (Math.abs(target - wrap.scrollLeft) < 4) return;
        this._chipSnapAdjusting = true;
        wrap.scrollTo({ left:target, behavior:'smooth' });
        setTimeout(() => { this._chipSnapAdjusting = false; }, 220);
      }, 90);
    }, { passive:true });
  },

  _selectScenario(id) {
    const s = SCENARIOS.find(x => x.id === id);
    if (s.requiresGate && !Store.get('nivGate')) {
      const select = document.getElementById('scenarioSelect');
      if (select) select.value = Store.get('scenario');
      document.getElementById('gateModal').classList.add('open');
      this._trapFocus(document.getElementById('gateModal'));
      return;
    }
    Store.set('scenario', id);
    document.querySelectorAll('.chip').forEach(c => {
      c.classList.toggle('active', c.dataset.id === id);
      c.setAttribute('aria-selected', c.dataset.id === id);
    });
    const select = document.getElementById('scenarioSelect');
    if (select) select.value = id;
    this._updateUI();
  },

  // ── Main UI update ─────────────────────────────────
  _updateUI() {
    const s   = SCENARIOS.find(x => x.id === Store.get('scenario'));
    const pbw = Store.get('pbw');
    this._setTheme(s);
    const scenarioSelect = document.getElementById('scenarioSelect');
    if (scenarioSelect && scenarioSelect.value !== s.id) scenarioSelect.value = s.id;

    // Contra banner
    const banner = document.getElementById('contraBanner');
    banner.classList.toggle('hidden', !(s.requiresGate && !Store.get('nivGate')));

    // Stats bar
    if (pbw) {
      document.getElementById('pbwStatus').textContent = `${pbw.toFixed(1)} kg`;
      const tvP = s.params.find(p => p.base);
      if (tvP) document.getElementById('vtStatus').textContent = `${Math.round(pbw*tvP.base.min)}–${Math.round(pbw*tvP.base.max)} mL`;
    } else {
      document.getElementById('pbwStatus').textContent = 'Not set';
      document.getElementById('vtStatus').textContent  = '--';
    }
    document.getElementById('o2Status').textContent = s.oxygenTarget || '92–96%';
    document.getElementById('co2Status').textContent = s.co2Target || 'PaCO₂ 35–45';
    document.getElementById('protocolStatus').textContent = s.protocol || 'ARDSNet';

    // Param cards
    const grid = document.getElementById('paramGrid');
    grid.innerHTML = '';
    s.params.forEach(p => {
      const dyn  = getDynTV(p, pbw);
      const card = document.createElement('div');
      card.className = 'pcard';
      card.innerHTML = `
        <div class="pcard-icon"><span class="emoji">${p.icon}</span></div>
        <div style="flex:1;min-width:0;">
          <div class="pcard-label">${p.key}</div>
          <div class="pcard-val">${dyn.html}</div>
          <div class="pcard-note">${p.note}</div>
          ${dyn.range ? `<span class="pcard-range">${dyn.range}</span>` : ''}
        </div>
      `;
      grid.appendChild(card);
    });

    // Waveform
    renderWaveform(s.waveform);

    // Evidence
    document.getElementById('evidenceBar').innerHTML =
      s.evidence.map(e => `<span class="ev-tag">${e}</span>`).join('');

    // Refresh compare if tab is active
    if (document.getElementById('view-compare')?.classList.contains('active')) this._renderCompare();
  },

  // ═══════════════════════════════════════════════════════
  // COMPARE — REDESIGNED (3 modes)
  // ═══════════════════════════════════════════════════════
  _cmpMode:  'overview',
  _cmpParam: 'Tidal Volume',
  _duelA:    null,
  _duelB:    null,

  // ── Rich clinical data per scenario ──────────────────
  _OV_DATA: {
    'healthy': {
      keyRule:    'Standard start',
      grade:      'B',
      dangers:    ['High FiO₂ causes absorption atelectasis — titrate rapidly','Oversedation leads to prolonged ventilation','Delayed Pplat check misses occult high pressures'],
      pearls:     ['Reassess FiO₂ every 15 min — target SpO₂ 92–96%','Check Pplat within 15 min of first breath','Use this as your default, then adapt to physiology'],
    },
    'asthma-copd': {
      keyRule:    'Slow down, accept CO₂',
      grade:      'A',
      dangers:    ['Fast RR causes dynamic hyperinflation and cardiac arrest','High PEEP worsens air trapping','Chasing normal PaCO₂ in COPD is harmful'],
      pearls:     ['Disconnect from vent if acute deterioration — allow passive exhalation','Increase inspiratory flow (>60 L/min) to achieve I:E 1:4–1:5','pH >7.20 is acceptable; do not increase RR to normalise CO₂'],
    },
    'ards': {
      keyRule:    'Low volume, low pressure',
      grade:      'A',
      dangers:    ['VT >8 mL/kg PBW independently increases mortality','Driving pressure >15 cmH₂O — reduce VT first','Ignoring plateau pressure until crisis is a common fatal error'],
      pearls:     ['Proning: ≥12–16 h/day if P/F ≤150 reduces mortality (PROSEVA)','Check Pplat AND driving pressure every 4 h','While PEEP/FiO₂ table is a safe start, consider Driving Pressure-guided PEEP titration'],
    },
    'hypovolemia': {
      keyRule:    'Protect the preload',
      grade:      'B',
      dangers:    ['PEEP >5 can reduce venous return and precipitate collapse in unsupported hypovolemia','PPV (positive-pressure ventilation) acutely drops preload','Intubating before adequate resuscitation causes peri-intubation arrest'],
      pearls:     ['Resuscitate before induction: blood products for hemorrhage, balanced crystalloid when appropriate','Have norepinephrine connected or immediately available for induction','Choose induction drugs deliberately; avoid large propofol boluses in marginal shock'],
    },
    'pregnancy': {
      keyRule:    'Two patients, same vent',
      grade:      'B',
      dangers:    ['SpO₂ <95% causes fetal hypoxia even briefly','Using actual body weight for PBW overestimates VT','Supine position compresses IVC — causes maternal and fetal compromise'],
      pearls:     ['Left lateral tilt 15–30° or manual uterine displacement always','Target SpO₂ ≥95% (higher than non-pregnant adults)','Use pre-pregnancy height for all PBW calculations'],
    },
    'niv': {
      keyRule:    'Right patient only',
      grade:      'A',
      dangers:    ['NIV in de novo hypoxaemic ARF delays intubation and worsens outcomes','Mask leak worsens CO₂ rebreathing','Any deterioration of GCS → intubate immediately, do not persist with NIV'],
      pearls:     ['Best evidence: hypercapnic COPD exacerbation and cardiogenic pulmonary oedema','IPAP–EPAP gap drives tidal volume — widen gap for CO₂ retention','Reassess response in 30–60 min; if no improvement, early intubation is safer'],
    },
    'sepsis': {
      keyRule:    'Resuscitate before tube',
      grade:      'B',
      dangers:    ['Inducing apnea before preload/pressor support is ready can precipitate arrest','Treating septic shock like simple hypovolemia underestimates vasoplegia','Ignoring evolving ARDS leads to over-large VT'],
      pearls:     ['Have norepinephrine connected before induction in the crashing patient','In profound shock, consider deep sedation and paralysis early to divert cardiac output back to vital organs','Preserve minute ventilation if severe lactic acidosis is driving tachypnea'],
    },
    'neuro': {
      keyRule:    'Normocapnia protects brain',
      grade:      'A',
      dangers:    ['Hypocapnia from overventilation can worsen cerebral ischemia','Hypoxia and hypotension are both secondary brain insults','Reflexly avoiding all PEEP may under-treat oxygenation'],
      pearls:     ['Target PaCO₂ with RR, not oversized VT','Use HOB 30° and align ventilation with brain goals','Brief hyperventilation is a rescue for herniation, not routine practice'],
    },
    'pe': {
      keyRule:    'Minimize intrathoracic pressure',
      grade:      'A',
      dangers:    ['Positive pressure can collapse RV preload and cardiac output','Sedative-induced vasodilation is especially dangerous in RV failure','High PEEP in massive PE can be catastrophic'],
      pearls:     ['If feasible, delay intubation until reperfusion and pressor plans are moving','Low PEEP and short inspiratory time reduce RV stress','Treat acidaemia and hypoxia, but do not over-pressurize the chest to do it'],
    },
    'dka': {
      keyRule:    'Match compensation exactly',
      grade:      'B',
      dangers:    ['A normal-looking RR after intubation may be a lethal under-ventilation','Paralysis without a rapid ventilation plan can cause abrupt pH collapse','If you do not measure pre-tube VE, you are guessing'],
      pearls:     ['Count the native RR and estimate VT before medications','Use the minute ventilation matcher instead of an arbitrary ED start','Down-titrate only as ketones and acidosis improve'],
    },
    'cpe': {
      keyRule:    'Positive pressure is therapy',
      grade:      'B',
      dangers:    ['Persisting with low PEEP after failed NIV throws away the main benefit of ventilation','Over-sedation worsens hypotension in an already unstable heart','Intubation without ongoing decongestion delays recovery'],
      pearls:     ['Treat this like wet restrictive lungs, not a healthy-lung intubation','PEEP can improve oxygenation and reduce preload/afterload when tolerated','Reassess immediately for cardiogenic shock if blood pressure falls'],
    },
    'anaphylaxis': {
      keyRule:    'Secure airway early',
      grade:      'B',
      dangers:    ['Delay can convert a difficult airway into an impossible airway','Assuming edema is gone after tube placement is a trap','Continuing obstructive mechanics can create auto-PEEP post-intubation'],
      pearls:     ['Keep epinephrine going while the ventilator strategy catches up','Expect a smaller tube and delayed cuff leak','If wheeze persists, ventilate like obstructive disease until proven otherwise'],
    },
    'obesity': {
      keyRule:    'PBW plus PEEP plus position',
      grade:      'B',
      dangers:    ['Actual body weight VT causes silent over-distension','Flat supine positioning accelerates derecruitment','PEEP that is too low is often the hidden reason for refractory hypoxemia'],
      pearls:     ['Reverse Trendelenburg buys oxygenation before you change a single vent number','Obesity often needs higher baseline PEEP than clinicians expect','Recruitment failure is common during transfers and disconnects'],
    },
    'rosc': {
      keyRule:    'Normoxia and normocapnia',
      grade:      'A',
      dangers:    ['Leaving FiO₂ at 100% after the first minutes risks hyperoxia','Hypocapnia is a common post-arrest ventilator error','ETCO₂ may underestimate arterial CO₂ after ROSC'],
      pearls:     ['ABG early, then titrate the vent with real PaCO₂ data','Bundle vent targets with MAP, temperature, and seizure control','The safest oxygen target is the minimum that avoids hypoxemia'],
    },
  },

  // ── Parameter deep-dive rationale per scenario ───────
  _PARAM_RATIONALE: {
    'Mode': {
      'healthy':      'AC-VC ensures reliable VT delivery and allows easy Pplat checks. All that is needed for a straightforward intubation.',
      'asthma-copd':  'AC-VC allows close monitoring of peak and plateau pressures. Breath stacking risk requires vigilant RR control.',
      'ards':         'AC-VC closely matches the ARDSNet protocol. Precise volume delivery and measurable plateau pressure are non-negotiable.',
      'hypovolemia':  'AC-VC provides predictable minute ventilation. Switch to spontaneous modes only after haemodynamic stabilisation.',
      'pregnancy':    'AC-VC guarantees volume delivery despite altered chest compliance from the gravid uterus.',
      'niv':          'BiPAP S/T allows patient-triggered breaths with a backup rate if apnoea occurs — critical for safety in awake patients.',
    },
    'Tidal Volume': {
      'healthy':      'Start at 8 mL/kg PBW — acceptable for normal lungs, but re-evaluate after first Pplat check.',
      'asthma-copd':  '6 mL/kg reduces peak pressures and limits dynamic hyperinflation. Lower VT = more time for exhalation per breath.',
      'ards':         'The most evidence-based intervention in ARDS. 6 mL/kg reduces mortality by ~22% vs 12 mL/kg (ARDSNet NEJM 2000).',
      'hypovolemia':  '8 mL/kg is standard when lungs are normal. Reassess if infiltrates develop (evolving ARDS from the underlying cause).',
      'pregnancy':    '6 mL/kg using PRE-PREGNANCY height. The gravid uterus reduces FRC — do not compensate by increasing VT.',
      'niv':          'VT is pressure-determined in BiPAP. Widen the IPAP–EPAP gap to increase delivered VT for CO₂ retention.',
    },
    'Resp Rate': {
      'healthy':      '10–12 is a safe starting range. Adjust after first ABG — most post-intubation patients need 14–18.',
      'asthma-copd':  'The most critical obstructive setting. Rate determines expiratory time. 8–12 breaths/min maximises the time available for complete exhalation.',
      'ards':         'Higher rates (18–30) compensate for low VT in maintaining pH. Watch for auto-PEEP if RR >28.',
      'hypovolemia':  'Keep at 10–12 initially. Lactic acidosis from haemorrhage drives a high spontaneous RR pre-intubation — the vent replaces this.',
      'pregnancy':    'Physiologic respiratory alkalosis in pregnancy (PaCO₂ ≈28–32 mmHg). Target a rate that maintains CO₂ in this range, not 40 mmHg.',
      'niv':          'Backup rate is a safety net only. The patient should be triggering every breath. If backup rate is frequently firing, re-evaluate or intubate.',
    },
    'FiO₂': {
      'healthy':      'Start 1.0 but titrate rapidly. Prolonged hyperoxia causes absorption atelectasis and oxygen toxicity.',
      'asthma-copd':  'Asthma: titrate to about 93–95%. COPD or suspected CO₂ retention: titrate to 88–92%; hyperoxia can worsen hypercapnia.',
      'ards':         'Follow the ARDSNet PEEP/FiO₂ table. Never raise FiO₂ in isolation — always pair with appropriate PEEP.',
      'hypovolemia':  'Start 1.0. Titrate once haemodynamics are controlled. Anaemia reduces O₂ delivery — address Hb alongside FiO₂.',
      'pregnancy':    'Target SpO₂ ≥95% to ensure adequate fetal oxygenation. The fetal haemoglobin curve shifts left — maternal PaO₂ must be higher.',
      'niv':          'COPD: 88–92% to avoid hyperoxia-associated CO₂ worsening. Cardiogenic oedema: titrate to comfort, usually 92–96%.',
    },
    'PEEP': {
      'healthy':      '5 cmH₂O is physiologic PEEP — prevents post-intubation atelectasis without haemodynamic compromise.',
      'asthma-copd':  'Start 0–5. External PEEP can be titrated against measured auto-PEEP only after it is confirmed >8–10 cmH₂O.',
      'ards':         'Titrate per ARDSNet PEEP/FiO₂ table. Higher PEEP improves oxygenation and reduces atelectrauma. Check haemodynamics after each increase.',
      'hypovolemia':  'Keep ≤5 initially. PEEP can reduce venous return and cardiac output, especially before preload and vascular tone are supported. Resuscitate first, PEEP second.',
      'pregnancy':    '5–8 cmH₂O counteracts the reduced FRC from diaphragmatic elevation. Avoids atelectasis in supine position.',
      'niv':          'EPAP = PEEP equivalent. Minimum 5 to prevent CO₂ rebreathing. In cardiogenic oedema, EPAP 8–10 splints open flooded alveoli.',
    },
    'I:E Ratio': {
      'healthy':      '1:2 provides adequate exhalation time for normal airways. Standard starting point.',
      'asthma-copd':  '1:4–1:5 is the defining obstructive setting. Achieve this by increasing Inspiratory Flow. Inadequate expiratory time leads to progressive air trapping, auto-PEEP, and haemodynamic collapse.',
      'ards':         '1:2 is standard. Inverse ratio ventilation (I:E >1) is occasionally used in refractory ARDS but requires expert supervision.',
      'hypovolemia':  '1:2 default. No specific I:E manipulation needed unless underlying obstructive disease is present.',
      'pregnancy':    '1:2 standard. The elevated diaphragm slightly shortens effective exhalation but 1:2 remains adequate.',
      'niv':          'Not directly set. Max I-time (1.0–1.2 s) prevents excessively long machine breaths. Set EPAP rise time for patient comfort.',
    },
  },

  // ── Pitfalls per param per scenario ──────────────────
  _PITFALLS: {
    'Tidal Volume': {
      'ards':        '⚠️ Using actual weight instead of PBW can substantially overshoot safe VT and increase volutrauma risk',
      'pregnancy':   '⚠️ Using current (pregnant) body weight overestimates PBW and causes volutrauma',
      'asthma-copd': '⚠️ High VT increases peak pressures and exacerbates air trapping',
    },
    'PEEP': {
      'hypovolemia': '⚠️ PEEP >5 in hypovolaemia can precipitate collapse — resuscitate first and titrate pressure cautiously',
      'asthma-copd': '⚠️ Injudicious PEEP addition worsens dynamic hyperinflation',
      'ards':        '⚠️ PEEP <5 is usually avoided in ARDS because derecruitment and atelectrauma worsen',
    },
    'Resp Rate': {
      'asthma-copd': '⚠️ High RR is the single most common mistake — causes breath stacking and haemodynamic collapse',
      'ards':        '⚠️ RR >35 causes excessive auto-PEEP and dyssynchrony',
    },
    'FiO₂': {
      'asthma-copd': '⚠️ Hyperoxia worsens hypercapnia in COPD — titrate to 88–92% early',
      'ards':        '⚠️ Raising FiO₂ without adjusting PEEP misses the main treatment lever',
    },
    'I:E Ratio': {
      'asthma-copd': '⚠️ 1:2 in an obstructive patient will cause progressive air trapping — must use 1:4 minimum',
    },
  },

  // ── Insight blurbs per parameter ─────────────────────
  _INSIGHTS: {
    'Mode':        'All invasive scenarios use AC-VC. This allows reliable volume delivery and easy plateau pressure measurement. NIV/BiPAP is the only pressure-support, non-invasive mode.',
    'Tidal Volume':'The single most evidence-based vent intervention. ARDS starts near 6 mL/kg PBW and may use 4–8 mL/kg to keep Pplat ≤30. Always use PBW — never actual body weight. Enter height in ⚖️ to get real mL.',
    'Resp Rate':   'Obstructive disease = low rate (8–12). ARDS = high rate (18–30) to compensate for low VT. Pregnancy targets 14–18 to match physiologic hyperventilation. RR is the main CO₂ lever.',
    'FiO₂':       'Start at 1.0 for the airway phase, then titrate down promptly. COPD: target 88–92%; asthma: about 93–95%; pregnancy: keep ≥95% for fetal protection. ARDS: use the PEEP/FiO₂ table.',
    'PEEP':        'ARDS often needs higher PEEP titrated to the PEEP/FiO₂ table and hemodynamics. Hypovolaemia usually needs the least (0–5 initially) because intrathoracic pressure can impair venous return. COPD: external PEEP only if auto-PEEP is confirmed.',
    'I:E Ratio':   'The signature obstructive setting is 1:4–1:5 — inadequate expiratory time causes progressive air trapping and cardiac arrest. All other scenarios use standard 1:2.',
    'IPAP':        'IPAP drives tidal volume and CO₂ clearance in NIV. Start 10–15, titrate +2 cmH₂O for persistent hypercapnia. IPAP–EPAP gap = ventilatory support level.',
    'EPAP':        'EPAP = functional PEEP in NIV. Minimum 5 to prevent CO₂ rebreathing. For CPE, do not hesitate to titrate EPAP up to 10–12 cmH₂O to aggressively reduce LV preload and afterload simultaneously.',
    'Backup Rate': 'Safety net for NIV — fires only if the patient becomes apnoeic. If backup is activating frequently, airway protection is failing → proceed to intubation.',
    'Max I-Time':  'Prevents machine breaths from being excessively prolonged in NIV, which causes discomfort and dyssynchrony. Typically 1.0–1.2 s.',
  },

  // ── Setup mode bar ───────────────────────────────────
  _setupCompareModes() {
    document.querySelectorAll('.cmp-mode-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.cmp-mode-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        this._cmpMode = btn.dataset.mode;
        document.querySelectorAll('.cmp-pane').forEach(p => p.classList.remove('active'));
        document.getElementById(`cmp-${this._cmpMode}`)?.classList.add('active');
        if (this._cmpMode === 'overview')   this._renderOverview();
        if (this._cmpMode === 'duel')       this._renderDuel();
        if (this._cmpMode === 'spotlight')  this._renderSpotlight();
      });
    });
  },

  // ── MODE 1: Overview ─────────────────────────────────
  _renderOverview() {
    const container = document.getElementById('overviewCards');
    if (!container || container.dataset.built) return;
    container.dataset.built = '1';
    container.innerHTML = '';

    SCENARIOS.forEach(s => {
      const ov = this._OV_DATA[s.id] || {};
      const card = document.createElement('div');
      card.className = 'ov-card';
      card.style.setProperty('--ov-theme', s.theme);
      card.style.setProperty('--ov-rgb', s.rgb);

      const gradeClass = ov.grade || 'C';
      const paramRows = s.params.map(p => `
        <div class="ov-param">
          <div class="ov-param-key">${p.key}</div>
          <div class="ov-param-val">${p.val}</div>
          <div class="ov-param-note">${p.note}</div>
        </div>
      `).join('');

      const dangers = (ov.dangers||[]).map(d=>`<li>${d}</li>`).join('');
      const pearls  = (ov.pearls||[]).map(p=>`<li>${p}</li>`).join('');

      card.innerHTML = `
        <div class="ov-header" style="position:relative;">
          <div style="position:absolute;left:0;top:0;bottom:0;width:5px;background:${s.theme};border-radius:3px 0 0 3px;"></div>
          <div style="padding-left:8px;display:flex;align-items:center;gap:12px;width:100%;">
            <span class="ov-emoji">${s.emoji}</span>
            <div class="ov-title-block">
              <div class="ov-name">${s.name}</div>
              <div class="ov-sub">${s.sub || ''}</div>
            </div>
            <span class="ov-key-rule" style="background:rgba(${s.rgb},.1);color:${s.theme};border:1px solid rgba(${s.rgb},.2);">${ov.keyRule||''}</span>
            <span class="ov-chevron">▾</span>
          </div>
        </div>
        <div class="ov-body">
          <div class="ov-params">${paramRows}</div>
          <div class="ov-zones">
            <div class="ov-zone">
              <div class="ov-zone-title ov-zone-danger">🚨 Avoid these mistakes</div>
              <ul>${dangers}</ul>
            </div>
            <div class="ov-zone">
              <div class="ov-zone-title ov-zone-pearl">💎 Clinical pearls</div>
              <ul>${pearls}</ul>
            </div>
          </div>
          <div class="ov-evidence">
            <span class="ov-ev-label">Evidence</span>
            ${s.evidence.map(e=>`<span class="ov-ev-tag">${e}</span>`).join('')}
            <span class="ov-grade ${gradeClass}">Grade ${gradeClass}</span>
          </div>
          <button class="ov-goto" data-id="${s.id}">→ Switch to this scenario</button>
        </div>
      `;

      // Toggle open/close
      card.querySelector('.ov-header').addEventListener('click', () => {
        const isOpen = card.classList.toggle('open');
        // Auto-close others
        if (isOpen) {
          container.querySelectorAll('.ov-card.open').forEach(other => {
            if (other !== card) other.classList.remove('open');
          });
        }
      });

      // Go to scenario
      card.querySelector('.ov-goto').addEventListener('click', (e) => {
        e.stopPropagation();
        document.querySelectorAll('.nav-it').forEach(b => {
          b.classList.toggle('active', b.dataset.target === 'view-scenarios');
          b.setAttribute('aria-selected', b.dataset.target === 'view-scenarios');
        });
        document.querySelectorAll('.view').forEach(v => v.classList.toggle('active', v.id === 'view-scenarios'));
        this._selectScenario(s.id);
        document.querySelector('.content').scrollTo({top:0,behavior:'smooth'});
        this.toast(`🔀 Switched to ${s.name}`);
      });

      container.appendChild(card);
    });
  },

  // ── MODE 2: Head-to-Head Duel ────────────────────────
  _renderDuel() {
    this._buildDuelChips('A');
    this._buildDuelChips('B');
    this._buildDuelTable();
  },

  _buildDuelChips(side) {
    const container = document.getElementById(`duel${side}Chips`);
    const label     = document.getElementById(`duel${side}Label`);
    if (!container) return;
    container.innerHTML = '';

    SCENARIOS.forEach(s => {
      const chip = document.createElement('div');
      chip.className = `dp-chip${this[`_duel${side}`] === s.id ? ' selected' : ''}`;
      chip.innerHTML  = `${s.emoji} ${s.name}`;
      chip.addEventListener('click', () => {
        this[`_duel${side}`] = s.id;
        label.textContent = `${s.emoji} ${s.name}`;
        this._buildDuelChips(side);
        this._buildDuelTable();
      });
      container.appendChild(chip);
    });
  },

  _buildDuelTable() {
    const container = document.getElementById('duelTable');
    if (!container) return;

    if (!this._duelA || !this._duelB) {
      container.innerHTML = `<div class="duel-empty">Select two scenarios above to begin the comparison.</div>`;
      return;
    }

    const sA = SCENARIOS.find(x => x.id === this._duelA);
    const sB = SCENARIOS.find(x => x.id === this._duelB);
    const pbw = Store.get('pbw');

    // All unique params across both scenarios
    const allKeys = [...new Set([...sA.params.map(p=>p.key), ...sB.params.map(p=>p.key)])];

    let html = `
      <div class="duel-table">
        <div class="duel-col-headers">
          <div class="duel-col-hdr" style="font-size:.65rem;">Parameter</div>
          <div class="duel-col-hdr"><span class="duel-dot" style="background:${sA.theme};"></span>${sA.emoji} ${sA.name}</div>
          <div class="duel-col-hdr"><span class="duel-dot" style="background:${sB.theme};"></span>${sB.emoji} ${sB.name}</div>
        </div>
    `;

    allKeys.forEach((key, idx) => {
      const pA = sA.params.find(x => x.key === key);
      const pB = sB.params.find(x => x.key === key);
      const ratA = (this._PARAM_RATIONALE[key] || {})[this._duelA] || (pA ? pA.note : '');
      const ratB = (this._PARAM_RATIONALE[key] || {})[this._duelB] || (pB ? pB.note : '');
      const pitA = (this._PITFALLS[key] || {})[this._duelA] || '';
      const pitB = (this._PITFALLS[key] || {})[this._duelB] || '';

      const valA = pA ? getDynTV(pA, pbw).text : '—';
      const valB = pB ? getDynTV(pB, pbw).text : '—';
      const isSame = valA === valB && valA !== '—';
      const hasPitfall = pitA || pitB;

      html += `
        <div class="duel-row${hasPitfall ? ' highlight-row' : ''}">
          <div class="duel-cell duel-cell-key">${key}</div>
          <div class="duel-cell">
            <div class="duel-val" style="color:${sA.theme}">${valA}</div>
            ${ratA ? `<div class="duel-rationale">${ratA}</div>` : ''}
            ${pitA ? `<div class="cmp-pitfall">${pitA}</div>` : ''}
          </div>
          <div class="duel-cell">
            <div class="duel-val" style="color:${sB.theme}">${valB}</div>
            ${ratB ? `<div class="duel-rationale">${ratB}</div>` : ''}
            ${pitB ? `<div class="cmp-pitfall">${pitB}</div>` : ''}
          </div>
        </div>
      `;
    });

    // Safety targets row
    html += `
      <div class="duel-row highlight-row">
        <div class="duel-cell duel-cell-key">Safety</div>
        <div class="duel-cell"><div class="duel-val" style="color:${sA.theme};font-size:.75rem;">${sA.safetyTitle}</div><div class="duel-rationale">${sA.safetyNote}</div></div>
        <div class="duel-cell"><div class="duel-val" style="color:${sB.theme};font-size:.75rem;">${sB.safetyTitle}</div><div class="duel-rationale">${sB.safetyNote}</div></div>
      </div>
    `;

    // Evidence row
    html += `
      <div class="duel-row">
        <div class="duel-cell duel-cell-key">Evidence</div>
        <div class="duel-cell"><div class="duel-rationale">${sA.evidence.join(' · ')}</div></div>
        <div class="duel-cell"><div class="duel-rationale">${sB.evidence.join(' · ')}</div></div>
      </div>
    `;

    html += `</div>`;
    container.innerHTML = html;
  },

  // ── MODE 3: Spotlight ────────────────────────────────
  _renderSpotlight() {
    const pbw = Store.get('pbw');
    const activeScenario = Store.get('scenario');

    const pillRow = document.getElementById('paramPillRow');
    if (!pillRow) return;
    pillRow.innerHTML = '';

    const paramSet = new Set();
    SCENARIOS.forEach(s => s.params.forEach(p => paramSet.add(p.key)));
    const params = ['Mode','Tidal Volume','Resp Rate','FiO₂','PEEP','I:E Ratio','IPAP','EPAP','Backup Rate','Max I-Time'].filter(k => paramSet.has(k));
    const emojis = {'Mode':'🎛️','Tidal Volume':'🫁','Resp Rate':'⏱️','FiO₂':'💨','PEEP':'🔒','I:E Ratio':'↔️','IPAP':'⬆️','EPAP':'⬇️','Backup Rate':'🔄','Max I-Time':'⌛'};

    params.forEach(key => {
      const pill = document.createElement('button');
      pill.className = `param-pill${key === this._cmpParam ? ' active' : ''}`;
      pill.innerHTML = `${emojis[key]||'•'} ${key}`;
      pill.addEventListener('click', () => { this._cmpParam = key; this._renderSpotlight(); });
      pillRow.appendChild(pill);
    });

    const grid = document.getElementById('cmpGrid');
    grid.innerHTML = '';

    SCENARIOS.forEach(s => {
      const p = s.params.find(x => x.key === this._cmpParam);
      const isActive = s.id === activeScenario;
      const rationale = (this._PARAM_RATIONALE[this._cmpParam] || {})[s.id] || (p ? p.note : '');
      const pitfall   = (this._PITFALLS[this._cmpParam] || {})[s.id] || '';

      const card = document.createElement('div');
      card.className = `cmp-card${isActive ? ' is-active' : ''}`;
      card.style.setProperty('--c-card',   s.theme);
      card.style.setProperty('--rgb-card', s.rgb);

      const dyn = p ? getDynTV(p, pbw) : { html:'—', range:null };

      card.innerHTML = `
        ${isActive ? `<div class="cmp-badge">● Active</div>` : ''}
        <div class="cmp-scenario-head">
          <span class="cmp-scenario-emoji">${s.emoji}</span>
          <span class="cmp-scenario-name">${s.name}</span>
        </div>
        <div class="cmp-val">${p ? dyn.html : '<span style="color:var(--text-3)">N/A</span>'}</div>
        ${dyn.range ? `<div class="cmp-note" style="margin-top:3px;">${dyn.range}</div>` : ''}
        ${rationale ? `<div class="cmp-rationale">${rationale}</div>` : ''}
        ${pitfall ? `<div class="cmp-pitfall">⚠️ ${pitfall.replace('⚠️','').trim()}</div>` : ''}
      `;

      card.addEventListener('click', () => {
        document.querySelectorAll('.nav-it').forEach(b => {
          b.classList.toggle('active', b.dataset.target === 'view-scenarios');
          b.setAttribute('aria-selected', b.dataset.target === 'view-scenarios');
        });
        document.querySelectorAll('.view').forEach(v => v.classList.toggle('active', v.id === 'view-scenarios'));
        this._selectScenario(s.id);
        document.querySelector('.content').scrollTo({top:0,behavior:'smooth'});
        this.toast(`🔀 Switched to ${s.name}`);
      });
      grid.appendChild(card);
    });

    // Insight
    const insight = document.getElementById('cmpInsight');
    const insightText = document.getElementById('cmpInsightText');
    const blurb = this._INSIGHTS[this._cmpParam];
    if (blurb && insight && insightText) {
      insight.classList.remove('hidden');
      insightText.textContent = blurb;
    } else if (insight) {
      insight.classList.add('hidden');
    }
  },

  // ── Master compare renderer (called from updateUI / nav) ──
  _renderCompare() {
    if (this._cmpMode === 'overview')  this._renderOverview();
    if (this._cmpMode === 'duel')      this._renderDuel();
    if (this._cmpMode === 'spotlight') this._renderSpotlight();
  },
  // ── Navigation ─────────────────────────────────────
  _setupNav() {
    document.querySelectorAll('.nav-it').forEach(btn => {
      btn.addEventListener('click', () => {
        // Feature gate check
        const fk = btn.dataset.feature;
        if (fk && !FG.has(fk)) { FG.prompt(fk); return; }
        // Original navigation logic
        document.querySelectorAll('.nav-it').forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected','false'); });
        btn.classList.add('active'); btn.setAttribute('aria-selected','true');
        const t = btn.dataset.target;
        document.querySelectorAll('.view').forEach(v => v.classList.toggle('active', v.id === t));
        document.querySelector('.content').scrollTo({ top:0, behavior:'smooth' });
        // Render compare spotlight when switching to that tab
        if (t === 'view-compare') { this._renderCompare(); }
        if (t === 'view-tools') this._setupTools();
      });
    });
  },

  // ── PBW Calculator ─────────────────────────────────
  _setupCalcModal() {
    const modal = document.getElementById('calcModal');
    const fab   = document.getElementById('fabCalc');
    const close = document.getElementById('closeCalc');
    const apply = document.getElementById('applyCalc');
    const reset = document.getElementById('resetCalc');
    const sexBtns  = document.querySelectorAll('#sexToggle .seg-btn');
    const unitBtns = document.querySelectorAll('#unitToggle .seg-btn');
    const hIn  = document.getElementById('heightInput');

    // Restore
    const sx = Store.get('sex'), un = Store.get('unit'), ht = Store.get('height');
    if (sx) sexBtns.forEach(b => b.classList.toggle('active', b.dataset.val === sx));
    if (un) { unitBtns.forEach(b => b.classList.toggle('active', b.dataset.val === un)); hIn.placeholder = un==='in' ? 'e.g. 68' : 'e.g. 175'; }
    if (ht) { hIn.value = ht; this._calcPBW(); }

    const open  = () => { modal.classList.add('open'); this._trapFocus(modal); setTimeout(()=>hIn.focus(),60); };
    const closeM = () => { modal.classList.remove('open'); this._releaseFocus(); };

    fab.addEventListener('click', () => {
      if (fab.dataset.feature && !FG.has(fab.dataset.feature)) { FG.prompt(fab.dataset.feature); return; }
      open();
    });
    close.addEventListener('click', closeM);
    apply.addEventListener('click', closeM);
    modal.addEventListener('click', e => { if (e.target === modal) closeM(); });

    reset.addEventListener('click', () => {
      hIn.value = '';
      Store.setBulk({ pbw:null, height:null });
      document.getElementById('pbwVal').textContent = '-- kg';
      document.getElementById('vtVal').innerHTML = 'Enter height above to calculate<br>Target VT (6–8 mL/kg)';
      document.getElementById('heightErr').classList.remove('show');
      this._renderCompare();
      if (this._refreshToolSummaries) this._refreshToolSummaries();
      this.toast('🔄 PBW reset — tidal volumes cleared.');
    });

    sexBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        sexBtns.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-checked','false'); });
        btn.classList.add('active'); btn.setAttribute('aria-checked','true');
        Store.set('sex', btn.dataset.val);
        this._calcPBW();
      });
    });

    unitBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        unitBtns.forEach(b => { b.classList.remove('active'); b.setAttribute('aria-checked','false'); });
        btn.classList.add('active'); btn.setAttribute('aria-checked','true');
        Store.set('unit', btn.dataset.val);
        hIn.placeholder = btn.dataset.val === 'in' ? 'e.g. 68' : 'e.g. 175';
        this._calcPBW();
      });
    });

    hIn.addEventListener('input', () => {
      const v = parseFloat(hIn.value);
      const u = Store.get('unit') || 'cm';
      const cm = u === 'in' ? v * 2.54 : v;
      const err = document.getElementById('heightErr');
      if (hIn.value && (cm < 120 || cm > 250)) {
        err.classList.add('show');
        hIn.setAttribute('aria-invalid','true');
      } else {
        err.classList.remove('show');
        hIn.removeAttribute('aria-invalid');
      }
      Store.set('height', hIn.value);
      this._calcPBW();
    });
  },

  _calcPBW() {
    const ht  = parseFloat(Store.get('height'));
    const sex = Store.get('sex') || 'male';
    const un  = Store.get('unit') || 'cm';

    if (!ht) {
      document.getElementById('pbwVal').textContent = '-- kg';
      document.getElementById('vtVal').innerHTML = 'Enter height above to calculate<br>Target VT (6–8 mL/kg)';
      Store.set('pbw', null);
      this._renderCompare();
      if (this._refreshToolSummaries) this._refreshToolSummaries();
      return;
    }

    const cm = un === 'in' ? ht * 2.54 : ht;
    if (cm < 120 || cm > 250) { Store.set('pbw', null); this._renderCompare(); return; }

    const pbw = Math.max(30, (sex === 'male' ? 50 : 45.5) + 0.91 * (cm - 152.4));
    Store.set('pbw', pbw);

    document.getElementById('pbwVal').textContent = `${pbw.toFixed(1)} kg`;
    const mn = Math.round(pbw*6), mx = Math.round(pbw*8);
    document.getElementById('vtVal').innerHTML = `<span style="color:var(--theme);font-weight:800">✓ Sync Active</span><br>Target VT (6–8 mL/kg): <strong>${mn}–${mx} mL</strong>`;
    this._renderCompare();
    if (this._refreshToolSummaries) this._refreshToolSummaries();
    this.toast(`⚖️ PBW = ${pbw.toFixed(1)} kg — tidal volumes synced across all scenarios!`, 'success');
  },

  // ── ABG Calc ───────────────────────────────────────
  _setupABG() {
    const ids = ['abgPh','abgPco2','abgHco3','abgRr','abgTarget'];
    const calc = () => {
      const ph  = parseFloat(document.getElementById('abgPh').value);
      const co2 = parseFloat(document.getElementById('abgPco2').value);
      const hco3= parseFloat(document.getElementById('abgHco3').value);
      const rr  = parseFloat(document.getElementById('abgRr').value);
      const tgt = parseFloat(document.getElementById('abgTarget').value);

      // Interpretation
      const interp = interpretABG(ph, co2, hco3);
      const box = document.getElementById('abgInterp');
      if (interp) {
        box.classList.remove('hidden');
        document.getElementById('abgInterpText').textContent = interp;
      } else {
        box.classList.add('hidden');
      }

      // RR Calc
      const res = document.getElementById('abgResult');
      if (rr && co2 && tgt && tgt !== 0) {
        const newRR = Math.round((rr * co2) / tgt);
        res.textContent = newRR;
        if (Store.get('scenario') === 'asthma-copd' && newRR > rr) {
          this.toast('⚠️ Obstructive physiology selected — raising RR can worsen air trapping. Reassess expiratory time and auto-PEEP first.', 'danger');
        }
        if (newRR > 30) this.toast('⚠️ New RR > 30 — consider permissive hypercapnia instead of aggressive correction!', 'danger');
        if (newRR < 6)  this.toast('⚠️ New RR < 6 — verify inputs and consider clinical context.', 'danger');
      } else {
        res.textContent = '--';
      }
    };
    ids.forEach(id => document.getElementById(id).addEventListener('input', calc));
  },

  // ── NIV Gate ───────────────────────────────────────
  _setupGate() {
    const modal   = document.getElementById('gateModal');
    const checks  = document.querySelectorAll('#gateList input[type="checkbox"]');
    const proceed = document.getElementById('gateProceed');
    const warn    = document.getElementById('gateWarn');
    const close   = document.getElementById('closeGate');
    const openBtn = document.getElementById('openGateBtn');

    // Enable proceed only when no major contraindication is checked.
    const validate = () => {
      const anyContra = Array.from(checks).some(c => c.checked);
      proceed.disabled = anyContra;           // blocked only if a CI is ticked
      warn.classList.toggle('hidden', !anyContra);
    };
    checks.forEach(c => c.addEventListener('change', validate));
    validate();

    proceed.addEventListener('click', () => {
      Store.set('nivGate', true);
      // Reset checkboxes for next time
      checks.forEach(c => c.checked = false);
      warn.classList.add('hidden');
      modal.classList.remove('open');
      this._releaseFocus();
      this.toast('✅ NIV safety gate passed — BiPAP settings unlocked.', 'success');
      this._selectScenario('niv');
    });

    const closeGate = () => {
      // Reset checks when dismissed without proceeding
      checks.forEach(c => c.checked = false);
      warn.classList.add('hidden');
      validate();
      modal.classList.remove('open');
      this._releaseFocus();
    };

    close.addEventListener('click', closeGate);
    if (openBtn) openBtn.addEventListener('click', () => { modal.classList.add('open'); this._trapFocus(modal); });
    modal.addEventListener('click', e => { if (e.target === modal) closeGate(); });
  },

  // ── EHR Copy ───────────────────────────────────────
  _setupEHR() {
    document.getElementById('ehrBtn').addEventListener('click', async () => {
      // Feature gate check
      const ehrEl = document.getElementById('ehrBtn');
      if (ehrEl.dataset.feature && !FG.has(ehrEl.dataset.feature)) { FG.prompt(ehrEl.dataset.feature); return; }
      const s   = SCENARIOS.find(x => x.id === Store.get('scenario'));
      const pbw = Store.get('pbw');
      const now = new Date().toISOString().slice(0,16).replace('T',' ');

      let text = `ED VENTILATION PLAN — ${s.name.toUpperCase()}\n`;
      text += `Generated: ${now} UTC\n`;
      text += `Evidence: ${s.evidence.join(' | ')}\n`;
      text += `${'─'.repeat(44)}\n`;
      s.params.forEach(p => {
        const dyn = getDynTV(p, pbw);
        text += `• ${p.key}: ${dyn.text}\n`;
      });
      text += `\n⚠️  Safety Target: ${s.safetyTitle}\n   ${s.safetyNote}\n`;
      if (pbw) text += `\n⚖️  PBW: ${pbw.toFixed(1)} kg (calculated)\n`;
      text += `\n[ED VentGuide Pro — Educational Use Only — Verify with clinical team]`;

      try {
        await navigator.clipboard.writeText(text);
        this.toast('📋 Structured note copied to clipboard!', 'success');
      } catch(e) {
        this.toast('❌ Clipboard access denied. Please copy manually.', 'danger');
      }
    });
  },

  // ── Tooltips ───────────────────────────────────────
  _setupTooltips() {
    document.querySelectorAll('.term').forEach(el => {
      const tip = () => { if (el.dataset.tip) this.toast(el.dataset.tip); };
      el.addEventListener('click', tip);
      el.addEventListener('keydown', e => { if (e.key==='Enter'||e.key===' ') { e.preventDefault(); tip(); } });
    });
  },

  // ── Keyboard ───────────────────────────────────────
  _setupKeyboard() {
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') {
        document.querySelectorAll('.modal-bg.open').forEach(m => {
          m.classList.remove('open');
          this._releaseFocus();
        });
      }
    });

    document.getElementById('darkToggle').addEventListener('click', () => {
      const d = !Store.get('dark');
      Store.set('dark', d);
      this._applyDark();
      this.toast(d ? '🌙 Dark mode on' : '☀️ Light mode on');
    });
  },

  // ── Print ──────────────────────────────────────────
  _setupPrint() {
    document.getElementById('printBtn').addEventListener('click', () => {
      if (!Features.has('print')) {
        this.toast('🖨️ Printing is disabled by your administrator.', 'danger');
        return;
      }
      window.print();
    });
  },

  // ── Toast ──────────────────────────────────────────
  toast(msg, type = 'info') {
    const wrap = document.getElementById('toastWrap');
    const el = document.createElement('div');
    el.className = `toast${type==='danger'?' danger':type==='success'?' success':''}`;
    el.setAttribute('role','status');
    el.innerHTML = `<span>${msg}</span>`;
    wrap.appendChild(el);
    setTimeout(() => {
      el.classList.add('removing');
      setTimeout(() => el.remove(), 300);
    }, 3600);
  },


  // ── Tools View Setup ───────────────────────────────
  _setupTools() {
    if (this._toolsBound) return;
    this._toolsBound = true;

    // Driving Pressure
    const dpCalc = () => {
      const pplat = parseFloat(document.getElementById('dpPplat')?.value);
      const peep  = parseFloat(document.getElementById('dpPeep')?.value);
      const valEl = document.getElementById('dpVal');
      const badge = document.getElementById('dpBadge');
      if (!valEl) return;
      if (!isNaN(pplat) && !isNaN(peep)) {
        const dp = pplat - peep;
        valEl.textContent = dp.toFixed(1);
        badge.classList.remove('hidden','ok','warn','danger');
        if (dp < 13)       { badge.textContent='✅ Excellent (<13)'; badge.classList.add('ok'); }
        else if (dp <= 15) { badge.textContent='⚠️ Borderline (13–15)'; badge.classList.add('warn'); }
        else               { badge.textContent='🚨 High Risk (>15) — reduce VT or PEEP'; badge.classList.add('danger'); }
      } else { valEl.textContent='--'; badge.classList.add('hidden'); }
    };
    ['dpPplat','dpPeep'].forEach(id => document.getElementById(id)?.addEventListener('input', dpCalc));

    // Compliance
    const crsCalc = () => {
      const vt = parseFloat(document.getElementById('crsVt')?.value);
      const pp = parseFloat(document.getElementById('crsPplat')?.value);
      const pe = parseFloat(document.getElementById('crsPeep')?.value);
      const valEl = document.getElementById('crsVal');
      const badge = document.getElementById('crsBadge');
      if (!valEl) return;
      if (!isNaN(vt) && !isNaN(pp) && !isNaN(pe) && (pp-pe) > 0) {
        const crs = vt / (pp - pe);
        valEl.textContent = crs.toFixed(1);
        badge.classList.remove('hidden','ok','warn','danger');
        if (crs >= 50)      { badge.textContent='✅ Normal (≥50)'; badge.classList.add('ok'); }
        else if (crs >= 30) { badge.textContent='⚠️ Reduced (30–49) — likely ARDS'; badge.classList.add('warn'); }
        else                { badge.textContent='🚨 Severely reduced (<30) — severe ARDS'; badge.classList.add('danger'); }
      } else { valEl.textContent='--'; badge.classList.add('hidden'); }
    };
    ['crsVt','crsPplat','crsPeep'].forEach(id => document.getElementById(id)?.addEventListener('input', crsCalc));

    // P/F Ratio
    const pfCalc = () => {
      const pao2 = parseFloat(document.getElementById('pfPao2')?.value);
      const fio2Raw = parseFloat(document.getElementById('pfFio2')?.value);
      const valEl = document.getElementById('pfVal');
      const badge = document.getElementById('pfBadge');
      const bar   = document.getElementById('berlinBar');
      const arrow = document.getElementById('berlinArrow');
      if (!valEl) return;
      const fio2 = !isNaN(fio2Raw) && fio2Raw > 1 && fio2Raw <= 100 ? fio2Raw / 100 : fio2Raw;
      if (!isNaN(pao2) && !isNaN(fio2) && fio2 > 0 && fio2 <= 1) {
        const pf = pao2 / fio2;
        valEl.textContent = Math.round(pf);
        badge.classList.remove('hidden','ok','warn','danger');
        bar.style.display = 'block';
        let pos='87%', label='✅ Normal (≥300)', cls='ok';
        if (pf < 100)      { pos='6%';  label='🚨 Severe ARDS (<100)';   cls='danger'; }
        else if (pf < 200) { pos='25%'; label='🔴 Moderate ARDS (<200)'; cls='danger'; }
        else if (pf < 300) { pos='50%'; label='⚠️ Mild ARDS (<300)';     cls='warn'; }
        badge.textContent=label; badge.classList.add(cls);
        arrow.style.marginLeft=pos; arrow.textContent='▲';
      } else { valEl.textContent='--'; badge.classList.add('hidden'); if(bar) bar.style.display='none'; }
    };
    ['pfPao2','pfFio2'].forEach(id => document.getElementById(id)?.addEventListener('input', pfCalc));

    // S/F Ratio
    const sfCalc = () => {
      const spo2 = parseFloat(document.getElementById('sfSpo2')?.value);
      const fio2Raw = parseFloat(document.getElementById('sfFio2')?.value);
      const valEl = document.getElementById('sfVal');
      const badge = document.getElementById('sfBadge');
      if (!valEl || !badge) return;
      const fio2 = !isNaN(fio2Raw) && fio2Raw > 1 && fio2Raw <= 100 ? fio2Raw / 100 : fio2Raw;
      if (!isNaN(spo2) && !isNaN(fio2) && fio2 > 0 && fio2 <= 1 && spo2 > 0) {
        const sf = spo2 / fio2;
        valEl.textContent = Math.round(sf);
        badge.classList.remove('hidden','ok','warn','danger');
        if (spo2 > 97) {
          badge.textContent = '⚠️ Plateau zone (>97%)';
          badge.classList.add('warn');
        } else if (sf < 148) {
          badge.textContent = '🚨 Severe hypoxemia range';
          badge.classList.add('danger');
        } else if (sf < 235) {
          badge.textContent = '🔴 About P/F <200';
          badge.classList.add('danger');
        } else if (sf < 315) {
          badge.textContent = '⚠️ About P/F <300';
          badge.classList.add('warn');
        } else {
          badge.textContent = '✅ Better oxygenation range';
          badge.classList.add('ok');
        }
      } else {
        valEl.textContent = '--';
        badge.classList.add('hidden');
      }
    };
    ['sfSpo2','sfFio2'].forEach(id => document.getElementById(id)?.addEventListener('input', sfCalc));

    // Minute ventilation matcher
    const mvCalc = () => {
      const rr = parseFloat(document.getElementById('mvObsRr')?.value);
      const vt = parseFloat(document.getElementById('mvObsVt')?.value);
      const targetInput = document.getElementById('mvTargetVt');
      const pbw = Store.get('pbw');
      const valEl = document.getElementById('mvVal');
      const needEl = document.getElementById('mvNeedRr');
      const altEl = document.getElementById('mvAlt');
      if (!valEl || !needEl || !altEl) return;

      let targetVt = parseFloat(targetInput?.value);
      if (isNaN(targetVt) && pbw) {
        targetVt = Math.round(pbw * 8);
      }

      if (!isNaN(rr) && !isNaN(vt) && rr > 0 && vt > 0) {
        const ve = (rr * vt) / 1000;
        valEl.textContent = ve.toFixed(1);
        if (!isNaN(targetVt) && targetVt > 0) {
          const need = Math.ceil((ve * 1000) / targetVt);
          needEl.textContent = `${need}`;
        } else {
          needEl.textContent = '--';
        }
      } else {
        valEl.textContent = '--';
        needEl.textContent = '--';
      }

      if (pbw) {
        const opts = [6,7,8].map(mlkg => {
          const vtForPbw = Math.round(pbw * mlkg);
          const need = (!isNaN(rr) && !isNaN(vt) && rr > 0 && vt > 0) ? Math.ceil((rr * vt) / vtForPbw) : null;
          return `${mlkg} mL/kg = ${vtForPbw} mL${need ? ` → RR ${need}/min` : ''}`;
        });
        altEl.textContent = `PBW ${pbw.toFixed(1)} kg: ${opts.join(' | ')}`;
      } else {
        altEl.textContent = 'Set PBW in the calculator or enter a target VT to generate PBW-based alternatives.';
      }
    };
    ['mvObsRr','mvObsVt','mvTargetVt'].forEach(id => document.getElementById(id)?.addEventListener('input', mvCalc));

    // Auto-PEEP
    const autoPeepCalc = () => {
      const total = parseFloat(document.getElementById('autoPeepTotal')?.value);
      const setPeep = parseFloat(document.getElementById('autoPeepSet')?.value);
      const flow = document.getElementById('autoPeepFlow')?.value;
      const trigger = document.getElementById('autoPeepTrigger')?.value;
      const valEl = document.getElementById('autoPeepVal');
      const badge = document.getElementById('autoPeepBadge');
      const extEl = document.getElementById('autoPeepExt');
      if (!valEl || !badge || !extEl) return;

      let intrinsic = null;
      if (!isNaN(total) && !isNaN(setPeep)) intrinsic = Math.max(0, total - setPeep);

      if (intrinsic !== null) {
        valEl.textContent = intrinsic.toFixed(1);
        badge.classList.remove('hidden','ok','warn','danger');
        if (intrinsic >= 8) {
          badge.textContent = '🚨 Marked auto-PEEP';
          badge.classList.add('danger');
        } else if (intrinsic >= 4) {
          badge.textContent = '⚠️ Moderate auto-PEEP';
          badge.classList.add('warn');
        } else {
          badge.textContent = '✅ Low / minimal';
          badge.classList.add('ok');
        }

        if (intrinsic > 0 && trigger === 'yes') {
          const low = Math.round(intrinsic * 0.7);
          const high = Math.round(intrinsic * 0.8);
          extEl.textContent = `Measured intrinsic PEEP is ${intrinsic.toFixed(1)} cmH₂O. If trigger work is high, cautiously trial external PEEP around ${low}–${high} cmH₂O while watching hemodynamics and expiratory flow.`;
        } else if (intrinsic > 0) {
          extEl.textContent = `Measured intrinsic PEEP is ${intrinsic.toFixed(1)} cmH₂O. First reduce RR, shorten inspiratory time, and let the patient fully exhale before adding external PEEP.`;
        } else {
          extEl.textContent = 'Measured intrinsic PEEP is minimal. If alarms persist, look for other causes such as secretions, tube obstruction, or pneumothorax.';
        }
      } else {
        valEl.textContent = '--';
        badge.classList.add('hidden');
        if (flow === 'no') {
          extEl.textContent = 'Expiratory flow not reaching zero strongly suggests incomplete exhalation even if you have not yet measured total PEEP. Slow the rate, shorten inspiratory time, and recheck with an expiratory hold.';
        } else {
          extEl.textContent = 'If intrinsic PEEP is confirmed and trigger work is high, consider carefully titrating external PEEP to about 70–80% of intrinsic PEEP while watching hemodynamics and exhalation.';
        }
      }
    };
    ['autoPeepTotal','autoPeepSet'].forEach(id => document.getElementById(id)?.addEventListener('input', autoPeepCalc));
    ['autoPeepFlow','autoPeepTrigger'].forEach(id => document.getElementById(id)?.addEventListener('change', autoPeepCalc));

    // Bicarb Calculator
    const bicarbCalc = () => {
      const tbw = parseFloat(document.getElementById('bicarbTbw')?.value);
      const curr = parseFloat(document.getElementById('bicarbCurr')?.value);
      const target = parseFloat(document.getElementById('bicarbTarget')?.value);
      const defEl = document.getElementById('bicarbDeficitVal');
      const doseEl = document.getElementById('bicarbDoseVal');
      
      if (!defEl || !doseEl) return;
      if (!isNaN(tbw) && !isNaN(curr) && !isNaN(target) && tbw > 0 && target > curr) {
        // Simple bicarb deficit formula: 0.5 * weight(kg) * (target_HCO3 - current_HCO3)
        const deficit = 0.5 * tbw * (target - curr);
        defEl.textContent = Math.round(deficit);
        
        // 1 amp = 50 mEq. Suggest giving 1/3 to 1/2 of deficit safely
        const safePush = Math.min(Math.round((deficit * 0.33) / 50), 3) || 1; 
        const safeDoseAmps = safePush === 1 ? '1 Amp' : `${safePush} Amps`;
        doseEl.textContent = safeDoseAmps;
      } else {
        defEl.textContent = '--';
        doseEl.textContent = '--';
      }
    };
    ['bicarbTbw','bicarbCurr','bicarbTarget'].forEach(id => document.getElementById(id)?.addEventListener('input', bicarbCalc));

    // Peri-intubation hemodynamic risk screen
    const riskCalc = () => {
      const hr = parseFloat(document.getElementById('riskHr')?.value);
      const sbp = parseFloat(document.getElementById('riskSbp')?.value);
      const dbp = parseFloat(document.getElementById('riskDbp')?.value);
      const hb = parseFloat(document.getElementById('riskHb')?.value);
      const dx = document.getElementById('riskDx')?.value || '';
      const valEl = document.getElementById('riskVal');
      const badge = document.getElementById('riskBadge');
      const plan = document.getElementById('riskPlan');
      if (!valEl || !badge || !plan) return;

      let score = 0;
      const map = (!isNaN(sbp) && !isNaN(dbp)) ? (sbp + (2 * dbp)) / 3 : null;
      const shockIndex = (!isNaN(hr) && !isNaN(sbp) && sbp > 0) ? hr / sbp : null;

      if (!isNaN(sbp)) {
        if (sbp < 90) score += 3;
        else if (sbp < 100) score += 2;
        else if (sbp < 110) score += 1;
      }
      if (map !== null) {
        if (map < 65) score += 2;
        else if (map < 75) score += 1;
      }
      if (shockIndex !== null) {
        if (shockIndex >= 1) score += 2;
        else if (shockIndex >= 0.9) score += 1;
      }
      if (!isNaN(hb)) {
        if (hb < 7) score += 2;
        else if (hb < 9) score += 1;
      }

      const dxPts = {sepsis:2, pe:3, cardiogenic:2, hypovolemia:2, dka:2, neuro:1, other:0};
      score += dxPts[dx] || 0;

      valEl.textContent = `${score}`;
      badge.classList.remove('hidden','ok','warn','danger');
      if (score >= 8) {
        badge.textContent = '🚨 Extreme risk';
        badge.classList.add('danger');
      } else if (score >= 5) {
        badge.textContent = '⚠️ High risk';
        badge.classList.add('danger');
      } else if (score >= 3) {
        badge.textContent = '🟠 Moderate risk';
        badge.classList.add('warn');
      } else {
        badge.textContent = '✅ Lower risk';
        badge.classList.add('ok');
      }

      const planBits = [];
      if (score >= 3) planBits.push('Minimize apnea and keep respiratory support on through preoxygenation when possible.');
      if (score >= 5) planBits.push('Have norepinephrine connected before induction; resuscitate to MAP about 65 or better first if feasible.');
      if (score >= 8) planBits.push('Escalate to the most experienced airway operator and consider an awake or delayed-sequence strategy rather than a rushed crash RSI.');

      if (dx === 'sepsis') planBits.push('Bias toward balanced crystalloids if fluid responsive, then norepinephrine early for vasoplegia.');
      if (dx === 'pe') planBits.push('Avoid high PEEP and long inspiratory times; treat this like RV failure and move reperfusion planning urgently.');
      if (dx === 'cardiogenic') planBits.push('Avoid propofol bolus in the marginal patient; keep positive-pressure benefits but anticipate post-induction hypotension.');
      if (dx === 'hypovolemia') planBits.push('Blood, fluid, and vasopressor planning should be in place before paralytic if time allows.');
      if (dx === 'dka') planBits.push('Measure and match pre-intubation minute ventilation immediately after the tube is placed.');
      if (dx === 'neuro') planBits.push('Prevent hypotension and hypoxia just as aggressively as you prevent hypercapnia.');

      if (planBits.length === 0) planBits.push('Use standard preoxygenation and a hemodynamically thoughtful induction plan.');
      if (shockIndex !== null) {
        const siText = shockIndex.toFixed(2);
        const mapText = map !== null ? ` MAP ${Math.round(map)}.` : '.';
        plan.textContent = `Shock index ${siText}.${mapText} ${planBits.join(' ')}`;
      } else {
        plan.textContent = planBits.join(' ');
      }
    };
    ['riskHr','riskSbp','riskDbp','riskHb'].forEach(id => document.getElementById(id)?.addEventListener('input', riskCalc));
    document.getElementById('riskDx')?.addEventListener('change', riskCalc);

    // SOFA quick score
    const sofaCalc = () => {
      const ids = ['sofaResp','sofaCoag','sofaLiver','sofaCv','sofaCns','sofaRenal'];
      const total = ids.reduce((sum, id) => sum + parseInt(document.getElementById(id)?.value || '0', 10), 0);
      const totalEl = document.getElementById('sofaTotal');
      const badge = document.getElementById('sofaBadge');
      if (!totalEl || !badge) return;
      totalEl.textContent = `${total}`;
      badge.classList.remove('hidden','ok','warn','danger');
      if (total >= 12) {
        badge.textContent = '🚨 Very high organ failure burden';
        badge.classList.add('danger');
      } else if (total >= 8) {
        badge.textContent = '⚠️ High burden';
        badge.classList.add('danger');
      } else if (total >= 4) {
        badge.textContent = '🟠 Moderate burden';
        badge.classList.add('warn');
      } else {
        badge.textContent = '✅ Lower burden';
        badge.classList.add('ok');
      }
    };
    ['sofaResp','sofaCoag','sofaLiver','sofaCv','sofaCns','sofaRenal'].forEach(id => document.getElementById(id)?.addEventListener('change', sofaCalc));
    sofaCalc();

    // Rapid deterioration guide
    const DETERIORATION = {
      desat: [
        { title:'Step 1: Oxygen now', text:'Turn FiO₂ to 100% and, if you are unsure of the vent, hand-ventilate while watching chest rise and capnography.' },
        { title:'Step 2: Run DOPE fast', text:'Check ETT depth, suction passability, circuit integrity, unilateral breath sounds, and pressure alarms.' },
        { title:'Step 3: Think physiology', text:'Obstructive disease may need disconnect-and-exhale; sudden unilateral findings or shock should trigger immediate decompression of suspected tension pneumothorax.' },
      ],
      'high-pressure': [
        { title:'Step 1: Separate peak from plateau', text:'If peak is high but plateau is not, think resistance: secretions, biting, kinking, bronchospasm, or auto-PEEP.' },
        { title:'Step 2: Suction and inspect', text:'Pass a suction catheter, look for water in the tubing, and confirm the tube has not migrated endobronchially.' },
        { title:'Step 3: Fix the vent', text:'In obstruction, lower RR, shorten inspiratory time, and increase expiratory time before escalating sedation.' },
      ],
      hypotension: [
        { title:'Step 1: Suspect pressure or preload first', text:'Briefly disconnect if severe auto-PEEP is plausible, then reassess blood pressure and pulse pressure immediately.' },
        { title:'Step 2: Lower chest pressure if safe', text:'Reduce PEEP or mean airway pressure only if oxygenation permits, and look for tension pneumothorax.' },
        { title:'Step 3: Match the physiology', text:'Give preload or blood if depleted, start vasopressors early in vasoplegia, and remember RV failure / PE can collapse after even modest positive pressure.' },
      ],
      fighting: [
        { title:'Step 1: Pain, fear, dyssynchrony', text:'Check analgesia, sedation target, trigger sensitivity, and waveform pattern before reflexively deepening sedation.' },
        { title:'Step 2: Correct the mismatch', text:'Flow starvation needs more inspiratory flow or a different mode; double triggering needs lower drive or longer inspiratory support.' },
        { title:'Step 3: Escalate carefully', text:'If the patient still cannot meet oxygenation or lung-protective goals, deepen sedation or consider NMBA for a clear indication.' },
      ]
    };
    const renderDeterioration = problem => {
      const wrap = document.getElementById('deteriorationOutput');
      if (!wrap) return;
      document.querySelectorAll('#deteriorationButtons .pill-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.problem === problem));
      wrap.innerHTML = (DETERIORATION[problem] || []).map(step => `
        <div class="stack-item">
          <strong>${step.title}</strong>
          <p>${step.text}</p>
        </div>
      `).join('');
    };
    document.querySelectorAll('#deteriorationButtons .pill-btn').forEach(btn => {
      btn.addEventListener('click', () => renderDeterioration(btn.dataset.problem));
    });
    renderDeterioration('desat');

    // LEMON score
    const lemonCalc = () => {
      const ids = ['lemonLook','lemonEval','lemonMallampati','lemonObstruction','lemonNeck'];
      const score = ids.reduce((sum, id) => sum + (document.getElementById(id)?.checked ? 1 : 0), 0);
      const obstruction = document.getElementById('lemonObstruction')?.checked;
      const scoreEl = document.getElementById('lemonScore');
      const badge = document.getElementById('lemonBadge');
      const plan = document.getElementById('lemonPlan');
      if (!scoreEl || !badge || !plan) return;
      scoreEl.textContent = `${score}`;
      badge.classList.remove('hidden','ok','warn','danger');
      if (obstruction || score >= 4) {
        badge.textContent = '🚨 High risk';
        badge.classList.add('danger');
        plan.textContent = 'Prepare a difficult-airway strategy: senior help, video laryngoscopy, bougie, suction, and front-of-neck backup. Awake or very controlled airway plans deserve serious consideration.';
      } else if (score >= 2) {
        badge.textContent = '⚠️ Intermediate risk';
        badge.classList.add('warn');
        plan.textContent = 'Optimize positioning and preoxygenation, use video laryngoscopy early, and have backup devices open before induction.';
      } else {
        badge.textContent = '✅ Lower risk';
        badge.classList.add('ok');
        plan.textContent = 'A standard RSI pathway may be reasonable if physiology allows, but keep backup tools ready because physiology can still make an easy anatomy airway dangerous.';
      }
    };
    ['lemonLook','lemonEval','lemonMallampati','lemonObstruction','lemonNeck'].forEach(id => document.getElementById(id)?.addEventListener('change', lemonCalc));
    lemonCalc();

    // Dyssynchrony guide
    const DYSSYNC = {
      flow: {
        pressure: 'M0,70 L20,70 L30,20 Q60,50 90,20 L100,70 L200,70 L220,70 L230,20 Q260,50 290,20 L300,70 L400,70',
        flow:     'M0,120 L20,120 L25,85 L40,105 Q60,80 90,115 L100,120 C120,150 150,140 200,120 L220,120 L225,85 L240,105 Q260,80 290,115 L300,120 C320,150 350,140 400,120',
        steps:[
          'Waveform clue: Scooped pressure curve, while the flow curve shows a bump or failure to decelerate smoothly because the patient is pulling for more air.',
          'First move: Increase inspiratory flow, choose a decelerating flow pattern, or switch to a pressure-targeted mode.',
          'Also treat pain, acidosis, anxiety, and fever because high respiratory drive can mimic a sedation problem.'
        ]
      },
      double: {
        pressure: 'M0,70 L20,70 L30,20 L70,20 L80,50 L90,10 L130,10 L140,70 L280,70',
        flow:     'M0,120 L20,120 L25,85 L70,115 L75,125 L85,80 L130,115 L135,120 C150,160 190,140 280,120',
        steps:[
          'Waveform clue: Two breaths stack with little or no exhalation between them. The second breath often hits higher peak pressures.',
          'First move: Match the ventilator to patient demand. Lengthen inspiratory time if the patient wants a longer breath, or increase sedation if drive is excessive.',
          'In ARDS, double triggering can deliver injurious combined tidal volumes despite a lung-protective set VT.'
        ]
      },
      reverse: {
        pressure: 'M0,70 L20,70 L30,20 L70,20 L80,70 C90,70 100,80 105,75 L115,20 L155,20 L165,70 L320,70',
        flow:     'M0,120 L20,120 L25,85 L70,115 L75,120 C85,150 95,140 100,125 L110,85 L155,115 L160,120 C190,160 250,140 320,120',
        steps:[
          'Waveform clue: A mandatory breath is delivered, then as it ends the diaphragm contracts and may trigger a second breath.',
          'First move: Reassess sedation depth, respiratory drive, and mode settings; consider expert ventilator review if breath stacking persists.',
          'Reverse triggering may look like synchrony but can still cause breath stacking, excess transpulmonary pressure, and diaphragm injury.'
        ]
      },
      auto: {
        pressure: 'M0,70 Q10,68 20,70 Q30,68 40,70 Q50,66 60,70 L70,20 L110,20 L120,70 Q130,68 140,70 Q150,68 160,70 L170,20 L210,20 L220,70',
        flow:     'M0,120 Q10,118 20,120 Q30,118 40,120 Q50,115 60,120 L65,85 L110,115 L115,120 C120,140 125,130 130,120 Q140,118 150,120 Q160,118 170,120 L175,85 L210,115 L215,120',
        steps:[
          'Waveform clue: Extra breaths fire without true patient effort. Baseline flow or pressure shows tiny rhythmic ripples or circuit noise.',
          'First move: Drain condensate, fix leaks, and reduce trigger sensitivity until false breaths stop.',
          'Cardiac oscillation can auto-trigger the ventilator in hyperdynamic states. Confirm the breaths are real before escalating sedation.'
        ]
      }
    };
    const renderDyssync = key => {
      const data = DYSSYNC[key];
      const wave = document.getElementById('dyssyncWave');
      const out = document.getElementById('dyssyncOutput');
      if (!wave || !out || !data) return;
      document.querySelectorAll('#dyssyncButtons .pill-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.dyssync === key));
      wave.setAttribute('viewBox', '0 0 400 160');
      wave.style.minHeight = '140px';
      wave.innerHTML = `
        <g stroke="var(--border)" stroke-width="1" stroke-dasharray="4 4">
          <line x1="0" y1="35" x2="400" y2="35"/>
          <line x1="0" y1="70" x2="400" y2="70"/>
          <line x1="0" y1="120" x2="400" y2="120"/>
        </g>
        <path d="${data.pressure}" fill="none" stroke="#f59e0b" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <text x="10" y="20" fill="#f59e0b" font-size="10" font-weight="700" font-family="DM Sans,sans-serif">Pressure (cmH2O)</text>
        <path d="${data.flow}" fill="none" stroke="#10b981" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
        <text x="10" y="105" fill="#10b981" font-size="10" font-weight="700" font-family="DM Sans,sans-serif">Flow (L/min)</text>
      `;
      out.innerHTML = data.steps.map((txt, idx) => `
        <div class="stack-item">
          <strong>${idx === 0 ? 'Recognition' : idx === 1 ? 'First Move' : 'Why it Matters'}</strong>
          <p>${txt}</p>
        </div>
      `).join('');
    };
    document.querySelectorAll('#dyssyncButtons .pill-btn').forEach(btn => {
      btn.addEventListener('click', () => renderDyssync(btn.dataset.dyssync));
    });
    renderDyssync('flow');

    // RSBI
    const rsbiCalc = () => {
      const rr = parseFloat(document.getElementById('rsbiRr')?.value);
      const vt = parseFloat(document.getElementById('rsbiVt')?.value);
      const valEl = document.getElementById('rsbiVal');
      const badge = document.getElementById('rsbiBadge');
      if (!valEl || !badge) return;
      if (!isNaN(rr) && !isNaN(vt) && rr > 0 && vt > 0) {
        const rsbi = rr / (vt / 1000);
        valEl.textContent = Math.round(rsbi);
        badge.classList.remove('hidden','ok','warn','danger');
        if (rsbi < 105) {
          badge.textContent = '✅ Favourable range';
          badge.classList.add('ok');
        } else if (rsbi < 130) {
          badge.textContent = '⚠️ Borderline';
          badge.classList.add('warn');
        } else {
          badge.textContent = '🚨 Poor readiness signal';
          badge.classList.add('danger');
        }
      } else {
        valEl.textContent = '--';
        badge.classList.add('hidden');
      }
    };
    ['rsbiRr','rsbiVt'].forEach(id => document.getElementById(id)?.addEventListener('input', rsbiCalc));

    // Mean Airway Pressure
    const pawCalc = () => {
      const pip=parseFloat(document.getElementById('pawPip')?.value);
      const peep=parseFloat(document.getElementById('pawPeep')?.value);
      const it=parseFloat(document.getElementById('pawItime')?.value);
      const rr=parseFloat(document.getElementById('pawRr')?.value);
      const el=document.getElementById('pawVal');
      if(!el) return;
      if(!isNaN(pip)&&!isNaN(peep)&&!isNaN(it)&&!isNaN(rr)&&rr>0){
        const te=(60/rr)-it;
        if (te <= 0 || it <= 0) {
          el.textContent='--';
          return;
        }
        el.textContent=(peep+(pip-peep)*(it/(it+te))).toFixed(1);
      } else { el.textContent='--'; }
    };
    ['pawPip','pawPeep','pawItime','pawRr'].forEach(id=>document.getElementById(id)?.addEventListener('input',pawCalc));

    // RSI Calculator
    const rsiCalc = () => {
      const tbwRaw = document.getElementById('rsiTbw')?.value;
      const tbw = parseFloat(tbwRaw);
      const out = document.getElementById('rsiOutput');
      const hint = document.getElementById('rsiPbwHint');
      if (!out) return;
      if (hint) {
        hint.textContent = 'RSI doses use total body weight here; adjust for severe obesity or organ failure per local protocol.';
      }
      if (isNaN(tbw) || tbw <= 0) {
        out.innerHTML = '<div style="padding:10px;text-align:center;color:var(--text-3);font-size:.8rem;font-weight:600;">Enter Total Body Weight to calculate doses</div>';
        return;
      }
      const drugs = [
        { class: 'Induction', name: 'Ketamine', dose: '1.5 – 2.0 mg/kg', calc: `${(tbw * 1.5).toFixed(0)} – ${(tbw * 2.0).toFixed(0)} mg`, note: 'Bronchodilator, preserves drive. Avoid if catecholamine depleted.' },
        { class: 'Induction', name: 'Etomidate', dose: '0.3 mg/kg', calc: `${(tbw * 0.3).toFixed(0)} mg`, note: 'Hemodynamically stable profile; adrenal suppression concern should be weighed against shock risk.' },
        { class: 'Paralytic', name: 'Rocuronium', dose: '1.0 – 1.2 mg/kg (TBW)', calc: `${(tbw * 1.0).toFixed(0)} – ${(tbw * 1.2).toFixed(0)} mg`, note: 'Safe in hyperkalemia. Long duration; sedation must be active after intubation.' },
        { class: 'Paralytic', name: 'Succinylcholine', dose: '1.5 mg/kg (TBW)', calc: `${(tbw * 1.5).toFixed(0)} mg`, note: 'Avoid in crush, burns >48h, denervation, severe hyperkalemia.' },
        { class: 'Maintenance Drip', name: 'Propofol', dose: '10 – 50 mcg/kg/min', calc: `${(tbw * 10).toFixed(0)} – ${(tbw * 50).toFixed(0)} mcg/min`, note: 'Fast on/off. Risk of hypotension. Avoid if hemodynamically unstable.' },
        { class: 'Maintenance Drip', name: 'Fentanyl', dose: '50 – 150 mcg/hr', calc: '50 – 150 mcg/hr', note: 'Start analgesia FIRST. Does not provide amnesia alone.' },
        { class: 'Rescue Pressor', name: 'Push-Dose Epinephrine', dose: '10 – 20 mcg', calc: '1 – 2 mL', note: 'Mix 1 mL of cardiac Epi (1:10,000) in 9 mL saline = 10 mcg/mL.' }
      ];
      out.innerHTML = drugs.map(d => `
        <div class="stack-item" style="display:flex;justify-content:space-between;align-items:center;">
          <div>
            <div style="font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--theme);font-weight:700;">${d.class}</div>
            <strong style="font-size:1.1rem;">${d.name}</strong>
            <p style="margin-top:2px;font-size:0.85rem;color:var(--text-2);">${d.note}</p>
          </div>
          <div style="text-align:right;min-width:90px;">
            <div style="font-weight:900;font-size:1.2rem;color:var(--text-1);">${d.calc}</div>
            <div style="font-size:0.75rem;color:var(--text-3);">${d.dose}</div>
          </div>
        </div>
      `).join('');
    };
    // Drug Infusion Calculator
    const INFUSIONS = [
      { name: 'Norepinephrine', sel: 0, modes: [{ unit: 'mcg/kg/min', start: 0.05, max: 1.0 }, { unit: 'mcg/min', start: 2, max: 30 }], access: 'Peripheral acceptable short-term through proximal, well-functioning line.', note: 'First-line vasopressor in septic shock. Initial MAP target: 65 mmHg.' },
      { name: 'Epinephrine', sel: 0, modes: [{ unit: 'mcg/kg/min', start: 0.05, max: 1.0 }, { unit: 'mcg/min', start: 2, max: 30 }], access: 'Central preferred; peripheral only as short bridge if needed.', note: 'Add-on or alternative vasopressor/inotrope. Monitor tachyarrhythmias and lactate.' },
      { name: 'Vasopressin', sel: 0, modes: [{ unit: 'Units/min', start: 0.03, max: 0.04 }, { unit: 'Units/hr', start: 1.8, max: 2.4 }], access: 'Central preferred.', note: 'Adjunct to norepinephrine; usually fixed dose rather than titrated high.' },
      { name: 'Propofol', sel: 0, modes: [{ unit: 'mcg/kg/min', start: 5, max: 50 }, { unit: 'mg/kg/hr', start: 0.3, max: 3.0 }], access: 'Peripheral acceptable.', note: 'Rapid onset/offset. Watch hypotension, triglycerides, and deep sedation.' },
      { name: 'Dexmedetomidine', sel: 0, modes: [{ unit: 'mcg/kg/hr', start: 0.2, max: 0.7 }], access: 'Peripheral acceptable.', note: 'Light sedation; monitor bradycardia and hypotension. Higher doses are protocol-specific.' },
      { name: 'Ketamine', sel: 0, modes: [{ unit: 'mg/kg/hr', start: 0.1, max: 2.0 }, { unit: 'mcg/kg/min', start: 1.6, max: 33 }], access: 'PIV safe.', note: 'Analgesia (0.1-0.3) vs. Sedation (0.5+).' },
      { name: 'Fentanyl', sel: 0, modes: [{ unit: 'mcg/hr', start: 25, max: 200 }, { unit: 'mcg/kg/hr', start: 0.5, max: 2.0 }], access: 'Peripheral acceptable.', note: 'Analgesia, not amnesia. Titrate with validated pain/sedation scales.' },
      { name: 'Midazolam', sel: 0, modes: [{ unit: 'mg/hr', start: 1, max: 10 }, { unit: 'mg/kg/hr', start: 0.02, max: 0.2 }], access: 'Peripheral acceptable.', note: 'Risk of accumulation and delirium; reserve when preferred agents are unsuitable.' }
    ];

    window.setInfusionMode = (drugIdx, modeIdx) => {
      if (INFUSIONS[drugIdx]) {
        INFUSIONS[drugIdx].sel = modeIdx;
        infusionCalc();
      }
    };

    const infusionCalc = () => {
      const weight = parseFloat(document.getElementById('infusionWeight')?.value);
      const list = document.getElementById('infusionList');
      if (!list) return;
      if (isNaN(weight) || weight <= 0) {
        list.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-3);font-size:.85rem;">Enter weight to see start &amp; usual upper ranges</div>';
        return;
      }
      list.innerHTML = INFUSIONS.map((d, drugIdx) => {
        const mode = d.modes[d.sel];
        const isWeightBased = mode.unit.includes('/kg/');
        const isMinute = mode.unit.includes('/min');
        const startVal = isWeightBased ? (mode.start * weight).toFixed(1) : mode.start;
        const maxVal = isWeightBased ? (mode.max * weight).toFixed(1) : mode.max;
        const displayUnit = mode.unit.split('/')[0];
        const timeSuffix = isMinute ? '/min' : '/hr';
        const toggles = d.modes.length > 1 ? `
          <div style="display:flex;background:var(--surface-3);padding:2px;border-radius:6px;margin-bottom:8px;">
            ${d.modes.map((m, modeIdx) => `
              <button onclick="setInfusionMode(${drugIdx}, ${modeIdx})" style="flex:1;border:0;padding:4px;font-size:0.65rem;font-weight:700;border-radius:4px;cursor:pointer;background:${d.sel === modeIdx ? 'var(--theme)' : 'transparent'};color:${d.sel === modeIdx ? '#fff' : 'var(--text-3)'};transition:all 0.2s;">
                ${m.unit}
              </button>
            `).join('')}
          </div>
        ` : '';
        return `
          <div class="stack-item">
            <div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:6px;">
              <strong style="font-size:1.05rem;color:var(--theme);">${d.name}</strong>
              <span style="font-size:0.7rem;font-weight:700;color:var(--text-3);text-transform:uppercase;">${mode.unit}</span>
            </div>
            ${toggles}
            <div class="simple-grid" style="gap:8px;margin-bottom:8px;">
              <div style="background:var(--surface-2);padding:8px;border-radius:8px;text-align:center;">
                <div style="font-size:0.6rem;font-weight:700;color:var(--text-2);text-transform:uppercase;">Start (${mode.start})</div>
                <div style="font-size:1rem;font-weight:800;color:var(--text);">${startVal} <span style="font-size:0.7rem;">${displayUnit}${timeSuffix}</span></div>
              </div>
              <div style="background:var(--surface-2);padding:8px;border-radius:8px;text-align:center;">
                <div style="font-size:0.6rem;font-weight:700;color:var(--text-2);text-transform:uppercase;">Usual upper (${mode.max})</div>
                <div style="font-size:1rem;font-weight:800;color:var(--text);">${maxVal} <span style="font-size:0.7rem;">${displayUnit}${timeSuffix}</span></div>
              </div>
            </div>
            <div style="background:rgba(220,38,38,0.05);border:1px solid rgba(220,38,38,0.1);padding:6px 10px;border-radius:8px;margin-bottom:4px;">
              <div style="font-size:0.6rem;font-weight:700;color:var(--danger);text-transform:uppercase;margin-bottom:2px;">Vascular Access Safety</div>
              <div style="font-size:0.75rem;font-weight:700;color:var(--text);">${d.access}</div>
              <p style="font-size:0.7rem;color:var(--text-3);margin-top:2px;line-height:1.4;">${d.note}</p>
            </div>
          </div>
        `;
      }).join('');
    };
    document.getElementById('rsiTbw')?.addEventListener('input', (e) => {
      rsiCalc();
      const val = e.target.value;
      const other = document.getElementById('infusionWeight');
      if (other && other.value !== val) {
        other.value = val;
        other.dispatchEvent(new Event('input'));
      }
    });

    document.getElementById('infusionWeight')?.addEventListener('input', (e) => {
      infusionCalc();
      const val = e.target.value;
      const other = document.getElementById('rsiTbw');
      if (other && other.value !== val) {
        other.value = val;
        other.dispatchEvent(new Event('input'));
      }
    });

    this._refreshToolSummaries = () => {
      mvCalc();
      sfCalc();
      rsbiCalc();
      rsiCalc();
      infusionCalc();
    };
    this._refreshToolSummaries();

    // RASS Scale — only build once
    const rassGrid = document.getElementById('rassGrid');
    if (rassGrid && !rassGrid.dataset.built) {
      rassGrid.dataset.built = '1';
      const RASS = [
        { score:'+4', name:'Combative',       color:'#dc2626', desc:'Overtly combative, violent, immediate danger to staff',      action:'Ensure staff safety, search for reversible causes, and use rapid analgesia/sedation per local protocol.' },
        { score:'+3', name:'Very Agitated',   color:'#dc2626', desc:'Pulls or removes tubes/lines; aggressive',                    action:'Check pain, hypoxemia, dyssynchrony, urinary retention, and withdrawal before escalating sedation.' },
        { score:'+2', name:'Agitated',        color:'#d97706', desc:'Frequent non-purposeful movement, fights ventilator',         action:'Optimize analgesia, ventilator synchrony, and treat triggers before deepening sedation.' },
        { score:'+1', name:'Restless',        color:'#d97706', desc:'Anxious but movements not aggressive or vigorous',            action:'Reassure, treat pain/anxiety, and minimize deliriogenic medications when possible.' },
        { score:'0',  name:'Alert & Calm',    color:'#16a34a', desc:'Spontaneous sustained attention to caregiver',                action:'✅ Common target for many ventilated patients when oxygenation and synchrony allow.' },
        { score:'−1', name:'Drowsy',          color:'#2563eb', desc:'Not fully alert but sustained awakening >10 sec to voice',    action:'Acceptable light sedation; reassess whether continuous sedatives can be reduced.' },
        { score:'−2', name:'Light Sedation',  color:'#2563eb', desc:'Briefly awakens with voice, eye contact <10 sec',             action:'Often acceptable when extra synchrony is needed. Consider daily awakening when safe.' },
        { score:'−3', name:'Moderate Sedation',color:'#7c3aed',desc:'Movement or eye opening to voice, no eye contact',           action:'Reserve for severe dyssynchrony, proning, or high oxygen demand, then lighten as soon as feasible.' },
        { score:'−4', name:'Deep Sedation',   color:'#94a3b8', desc:'No response to voice but movement to physical stimulation',   action:'Use only for a clear indication with frequent reassessment and documented goals.' },
        { score:'−5', name:'Unarousable',     color:'#475569', desc:'No response to voice or physical stimulation',                action:'Deepest sedation state; verify the indication daily and reassess the need to remain here.' },
      ];
      rassGrid.innerHTML = RASS.map(r => `
        <div class="rass-item" style="--rass-color:${r.color}">
          <div class="rass-score" style="color:${r.color}">${r.score}</div>
          <div style="flex:1">
            <div class="rass-name">${r.name}</div>
            <div class="rass-desc">${r.desc}</div>
            <div class="rass-detail">🩺 <strong>Action:</strong> ${r.action}</div>
          </div>
          <div class="rass-arrow">▾</div>
        </div>
      `).join('');
      rassGrid.querySelectorAll('.rass-item').forEach(item => {
        item.addEventListener('click', () => {
          item.classList.toggle('open');
          const arr = item.querySelector('.rass-arrow');
          if(arr) arr.style.transform = item.classList.contains('open') ? 'rotate(180deg)' : '';
        });
      });
    }

    // Post-intubation checklist — only build once
    const wrap = document.getElementById('checklistWrap');
    if (wrap && !wrap.dataset.built) {
      wrap.dataset.built = '1';
      const CHECKLIST = [
        { text:'🎯 Confirm ETT position',           sub:'Bilateral breath sounds + CXR ordered. Capnography waveform present.',            key:'pos' },
        { text:'📏 Note ETT depth at teeth',         sub:'Typically 21–23 cm women, 23–25 cm men. Document in notes.',                      key:'depth' },
        { text:'🔒 Secure ETT with tape/holder',     sub:'Prevent inadvertent extubation. Re-confirm depth after securing.',                key:'secure' },
        { text:'🫁 Set ventilator settings',         sub:'Mode, VT (6–8 mL/kg PBW), RR, FiO₂ 1.0, PEEP 5 per scenario.',                 key:'settings' },
        { text:'📊 Check first Pplat & PEEP',        sub:'Within 15 min. Target Pplat ≤ 30, ΔP < 15. Adjust VT if needed.',               key:'pplat' },
        { text:'💊 Post-intubation sedation',         sub:'Use analgesia-first, goal-directed sedation. Set a RASS target and document drips/boluses.', key:'sed' },
        { text:'🩸 Order ABG/VBG in 30 min',         sub:'ABG preferred for PaO₂ severity; VBG adequate for pH/pCO₂ clearance.',                 key:'abg' },
        { text:'📸 Review CXR when available',       sub:'ETT tip 2–4 cm above carina. Exclude pneumothorax. Note any new infiltrates.',   key:'cxr' },
        { text:'🧠 Reassess underlying cause',       sub:'Treat the indication: pneumonia, PE, sepsis, overdose, etc.',                    key:'cause' },
        { text:'📋 Document & handoff',              sub:'Time, drug doses, ETT size, depth, complications. SBAR handoff to ICU.',         key:'doc' },
      ];
      let checkState = {};
      const renderCL = () => {
        const done = Object.values(checkState).filter(Boolean).length;
        const total = CHECKLIST.length;
        wrap.innerHTML = `
          <div class="checklist-progress">
            <div class="cp-bar"><div class="cp-fill" style="width:${(done/total*100).toFixed(0)}%"></div></div>
            <div class="cp-text">${done}/${total} complete</div>
          </div>
        ` + CHECKLIST.map(item => `
          <div class="checklist-item ${checkState[item.key]?'done':''}" data-key="${item.key}">
            <div class="ci-check">${checkState[item.key]?'✓':''}</div>
            <div><div class="ci-text">${item.text}</div><div class="ci-sub">${item.sub}</div></div>
          </div>
        `).join('');
        wrap.querySelectorAll('.checklist-item').forEach(el => {
          el.addEventListener('click', () => {
            const k = el.dataset.key;
            checkState[k] = !checkState[k];
            if (Object.values(checkState).filter(Boolean).length === total) App.toast('🎉 All tasks confirmed! Ready for ICU handoff.','success');
            renderCL();
          });
        });
      };
      renderCL();

      document.getElementById('resetChecklist')?.addEventListener('click', () => {
        checkState = {};
        renderCL();
        App.toast('🔄 Checklist reset for new patient.');
      });
      document.getElementById('copyChecklist')?.addEventListener('click', async () => {
        const now = new Date().toISOString().slice(0,16).replace('T',' ');
        let text = `POST-INTUBATION CHECKLIST — ${now} UTC\n` + '─'.repeat(40) + '\n';
        CHECKLIST.forEach(item => { text += `[${checkState[item.key]?'✓':' '}] ${item.text.replace(/^\S+\s*/,'')}\n`; });
        try {
          await navigator.clipboard.writeText(text);
          App.toast('📋 Checklist copied to clipboard!','success');
        } catch(e) { App.toast('❌ Copy failed.','danger'); }
      });
    }
  },

  // ── Focus trap ─────────────────────────────────────
  _prevFocus: null,
  _trapFn: null,

  _trapFocus(modal) {
    this._prevFocus = document.activeElement;
    const focusable = modal.querySelectorAll('button,[href],input,select,textarea,[tabindex]:not([tabindex="-1"])');
    const first = focusable[0], last = focusable[focusable.length-1];
    this._trapFn = e => {
      if (e.key !== 'Tab') return;
      if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
      else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
    };
    modal.addEventListener('keydown', this._trapFn);
    setTimeout(() => first && first.focus(), 50);
  },

  _releaseFocus() {
    if (this._trapFn) { document.removeEventListener('keydown', this._trapFn); this._trapFn = null; }
    if (this._prevFocus) { this._prevFocus.focus(); this._prevFocus = null; }
  }
};

// ─── BOOT ─────────────────────────────────────────────
App.init();

</script>
<?= pwa_script_tag() . "\n" ?>
</body>
</html>
