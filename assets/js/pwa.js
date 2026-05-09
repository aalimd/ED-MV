(function () {
  if (!('serviceWorker' in navigator)) return;
  if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') return;

  // ── Helpers ──────────────────────────────────────────────────────
  var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
  var isInStandaloneMode = window.matchMedia('(display-mode: standalone)').matches
    || window.navigator.standalone === true;
  var isAdminPath = /\/admin(?:\/|$)/.test(location.pathname);

  function injectAnimations() {
    if (document.getElementById('pwa-animations')) return;
    var s = document.createElement('style');
    s.id = 'pwa-animations';
    s.textContent = [
      '@keyframes slideUpBanner{from{opacity:0;transform:translate(-50%,30px)}to{opacity:1;transform:translate(-50%,0)}}',
      '@keyframes slideUpSheet{from{opacity:0;transform:translateY(100%)}to{opacity:1;transform:translateY(0)}}',
      '@keyframes fadeInOverlay{from{opacity:0}to{opacity:1}}'
    ].join('');
    document.head.appendChild(s);
  }

  // ── Service Worker Registration ──────────────────────────────────
  window.addEventListener('load', function () {
    // Robustly find the script element even with query strings (e.g., ?v=3)
    var script = document.currentScript;
    if (!script) {
      var scripts = document.getElementsByTagName('script');
      for (var i = 0; i < scripts.length; i++) {
        if (scripts[i].src && scripts[i].src.indexOf('/assets/js/pwa.js') !== -1) {
          script = scripts[i];
          break;
        }
      }
    }
    
    var scriptUrl = script ? script.src : new URL('pwa.js', location.href).toString();
    var workerUrl = new URL('../../sw.js', scriptUrl);
    var scopeUrl  = new URL('../../', scriptUrl);
    if (workerUrl.origin !== location.origin) {
      return;
    }
    
    console.log('PWA: Registering SW', { worker: workerUrl.toString(), scope: scopeUrl.pathname });
    
    navigator.serviceWorker.register(workerUrl, { scope: scopeUrl.pathname })
      .catch(function (err) { console.error('PWA: SW registration failed', err); });
  });

  // ── Universal Desktop/Android Install Banner ─────────────────────
  var deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
  });

  window.addEventListener('load', function () {
    // If it's iOS, the bottom sheet handles it. If already installed, do nothing.
    if (isAdminPath || isIos || isInStandaloneMode) return;
    if (sessionStorage.getItem('pwa_install_dismissed')) return;

    // Show banner universally on all non-iOS browsers (Safari Mac, Firefox, Chrome, Android)
    setTimeout(function () {
      console.log('PWA: Checking install banner conditions', { isIos, isInStandaloneMode, dismissed: sessionStorage.getItem('pwa_install_dismissed') });
      if (document.getElementById('pwa-install-banner')) return; // already exists
      
      injectAnimations();
      var banner = document.createElement('div');
      banner.id = 'pwa-install-banner';
      banner.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);width:calc(100% - 40px);max-width:400px;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,0.18);padding:16px;display:flex;align-items:center;gap:14px;z-index:9999;font-family:system-ui,sans-serif;animation:slideUpBanner 0.4s cubic-bezier(0.16,1,0.3,1);';
      banner.innerHTML = '<div style="width:44px;height:44px;background:var(--theme,#2563eb);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0">\uD83E\uDEC1</div>'
        + '<div style="flex:1;min-width:0"><div style="font-size:.92rem;font-weight:800;color:var(--text,#0f172a)">Install VentGuide Pro</div>'
        + '<div style="font-size:.72rem;color:var(--text-2,#475569);font-weight:500;margin-top:2px">Add to device for the full native app experience</div></div>'
        + '<div style="display:flex;gap:6px;flex-shrink:0">'
        + '<button id="pwa-dismiss" style="background:none;border:none;color:var(--text-3,#94a3b8);font-size:.78rem;font-weight:700;cursor:pointer;padding:8px;white-space:nowrap">Later</button>'
        + '<button id="pwa-install" style="background:var(--theme,#2563eb);color:#fff;border:none;border-radius:8px;padding:8px 14px;font-size:.82rem;font-weight:800;cursor:pointer;white-space:nowrap">Install</button>'
        + '</div>';
      document.body.appendChild(banner);

      document.getElementById('pwa-install').addEventListener('click', function () {
        if (deferredPrompt) {
          // Chrome/Android native install
          deferredPrompt.prompt();
          deferredPrompt.userChoice.then(function () { deferredPrompt = null; banner.remove(); });
        } else {
          // Safari Mac / Firefox fallback instruction
          alert("To install VentGuide Pro:\n\nMac Safari: Click 'File' > 'Add to Dock' or click the Share icon > 'Add to Dock'.\n\nOther browsers: Look for the Install icon in your address bar or browser menu.");
          banner.remove();
        }
      });
      document.getElementById('pwa-dismiss').addEventListener('click', function () {
        banner.remove();
        sessionStorage.setItem('pwa_install_dismissed', '1');
      });
    }, 1500); // 1.5s delay so it pops up nicely
  });

  // ── iOS Safari: Step-by-step Install Guide ───────────────────────
  window.addEventListener('load', function () {
    if (isAdminPath || !isIos || isInStandaloneMode) return;

    if (sessionStorage.getItem('pwa_ios_guide_dismissed')) return;

    setTimeout(function () {
      injectAnimations();

      var overlay = document.createElement('div');
      overlay.id = 'pwa-ios-overlay';
      overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9998;animation:fadeInOverlay 0.3s ease;';

      var sheet = document.createElement('div');
      sheet.id = 'pwa-ios-sheet';
      sheet.style.cssText = 'position:fixed;bottom:0;left:0;right:0;background:var(--surface,#fff);border-radius:24px 24px 0 0;padding:12px 24px 44px;z-index:9999;animation:slideUpSheet 0.4s cubic-bezier(0.16,1,0.3,1);font-family:system-ui,sans-serif;';

      var shareIcon = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><polyline points="16 6 12 2 8 6"/><line x1="12" y1="2" x2="12" y2="15"/></svg>';
      var plusIcon  = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>';

      sheet.innerHTML = '<div style="width:40px;height:4px;background:var(--border,#e2e8f0);border-radius:2px;margin:0 auto 20px;"></div>'
        + '<div style="text-align:center;margin-bottom:20px;">'
        + '<div style="font-size:2.4rem;margin-bottom:8px">\uD83E\uDEC1</div>'
        + '<div style="font-size:1.05rem;font-weight:800;color:var(--text,#0f172a)">Install VentGuide Pro</div>'
        + '<div style="font-size:.82rem;color:var(--text-2,#475569);font-weight:500;margin-top:6px;line-height:1.55">Follow 3 quick steps to add VentGuide Pro<br>to your iPhone Home Screen as a native app</div>'
        + '</div>'
        + '<div style="display:flex;flex-direction:column;gap:10px;margin-bottom:22px;">'
        // Step 1
        + '<div style="display:flex;align-items:center;gap:14px;background:var(--surface-2,#f8fafc);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:14px;">'
        + '<div style="width:36px;height:36px;background:#007aff;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' + shareIcon + '</div>'
        + '<div><div style="font-size:.88rem;font-weight:800;color:var(--text,#0f172a)">1 &mdash; Tap the Share button</div>'
        + '<div style="font-size:.75rem;color:var(--text-2,#475569);font-weight:500;margin-top:2px">The <strong style="font-size:.9rem">\u2191</strong> icon at the bottom of Safari</div></div></div>'
        // Step 2
        + '<div style="display:flex;align-items:center;gap:14px;background:var(--surface-2,#f8fafc);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:14px;">'
        + '<div style="width:36px;height:36px;background:#34c759;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">' + plusIcon + '</div>'
        + '<div><div style="font-size:.88rem;font-weight:800;color:var(--text,#0f172a)">2 &mdash; Add to Home Screen</div>'
        + '<div style="font-size:.75rem;color:var(--text-2,#475569);font-weight:500;margin-top:2px">Scroll down in the sheet and tap <strong>"Add to Home Screen"</strong></div></div></div>'
        // Step 3
        + '<div style="display:flex;align-items:center;gap:14px;background:var(--surface-2,#f8fafc);border:1px solid var(--border,#e2e8f0);border-radius:12px;padding:14px;">'
        + '<div style="width:36px;height:36px;background:var(--theme,#2563eb);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:20px;">\uD83E\uDEC1</div>'
        + '<div><div style="font-size:.88rem;font-weight:800;color:var(--text,#0f172a)">3 &mdash; Tap Add &amp; you\'re done!</div>'
        + '<div style="font-size:.75rem;color:var(--text-2,#475569);font-weight:500;margin-top:2px">VentGuide Pro will appear on your Home Screen</div></div></div>'
        + '</div>'
        + '<button id="pwa-ios-close" style="width:100%;padding:14px;background:var(--theme,#2563eb);color:#fff;border:none;border-radius:12px;font-size:.95rem;font-weight:800;cursor:pointer;touch-action:manipulation;">Got it!</button>'
        + '<button id="pwa-ios-never" style="width:100%;padding:10px;background:none;border:none;color:var(--text-3,#94a3b8);font-size:.78rem;font-weight:600;cursor:pointer;margin-top:4px;touch-action:manipulation;">Don\'t show again</button>';

      document.body.appendChild(overlay);
      document.body.appendChild(sheet);

      function closeSheet() { overlay.remove(); sheet.remove(); }

      document.getElementById('pwa-ios-close').addEventListener('click', function () {
        sessionStorage.setItem('pwa_ios_guide_dismissed', '1');
        closeSheet();
      });
      document.getElementById('pwa-ios-never').addEventListener('click', function () {
        localStorage.setItem('pwa_ios_guide_dismissed', '1'); // If they click 'never', we can respect it permanently via localstorage if you prefer, but let's just make it session to be extremely aggressive like you want:
        sessionStorage.setItem('pwa_ios_guide_dismissed', '1');
        closeSheet();
      });
      overlay.addEventListener('click', function () {
        sessionStorage.setItem('pwa_ios_guide_dismissed', '1');
        closeSheet();
      });
    }, 2500);
  });

  // ── Security: Anti-Copy, Anti-Screenshot, Anti-Zoom ─────────────
  window.addEventListener('DOMContentLoaded', function () {
    document.addEventListener('contextmenu', function (e) {
      if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') e.preventDefault();
    });
    document.addEventListener('copy', function (e) { e.preventDefault(); });
    document.addEventListener('cut',  function (e) { e.preventDefault(); });
    document.addEventListener('keydown', function (e) {
      if ((e.ctrlKey || e.metaKey) && ['c','s','p'].includes(e.key.toLowerCase())) e.preventDefault();
      if (e.key === 'PrintScreen' && navigator.clipboard) navigator.clipboard.writeText('');
    });
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) {
        document.body.style.filter = 'blur(12px)';
        document.body.style.pointerEvents = 'none';
      } else {
        document.body.style.filter = 'none';
        document.body.style.pointerEvents = 'auto';
      }
    });

    // Secondary zoom prevention layer (primary is inline <head> script)
    var lastTouchEnd = 0;
    document.addEventListener('gesturestart',  function (e) { e.preventDefault(); }, { passive: false });
    document.addEventListener('gesturechange', function (e) { e.preventDefault(); }, { passive: false });
    document.addEventListener('touchmove', function (e) {
      if (e.touches && e.touches.length > 1) e.preventDefault();
    }, { passive: false });
    document.addEventListener('touchend', function (e) {
      var now = Date.now();
      if (now - lastTouchEnd <= 300) e.preventDefault();
      lastTouchEnd = now;
    }, { passive: false });
  });
})();
