(function () {
  if (!('serviceWorker' in navigator)) return;
  if (!window.isSecureContext && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') return;

  window.addEventListener('load', function () {
    const script = document.currentScript || document.querySelector('script[src$="/assets/js/pwa.js"]');
    const scriptUrl = script ? script.src : new URL('/assets/js/pwa.js', location.origin).toString();
    const workerUrl = new URL('../../sw.js', scriptUrl);
    const scopeUrl = new URL('../../', scriptUrl);

    navigator.serviceWorker.register(workerUrl, { scope: scopeUrl.pathname })
      .catch(function () {
        // Install support should never block normal app usage.
      });
  });

  // Custom Install App UI
  let deferredPrompt;
  window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent the mini-infobar from appearing on mobile
    e.preventDefault();
    deferredPrompt = e;
    
    // Check if we've already dismissed it recently
    if (sessionStorage.getItem('pwa_install_dismissed')) return;

    // Create the install banner
    const banner = document.createElement('div');
    banner.style.cssText = 'position:fixed;bottom:20px;left:50%;transform:translateX(-50%);width:calc(100% - 40px);max-width:400px;background:var(--surface,#fff);border:1px solid var(--border,#e2e8f0);border-radius:16px;box-shadow:0 10px 40px rgba(0,0,0,0.15);padding:16px;display:flex;align-items:center;gap:16px;z-index:9999;font-family:system-ui,sans-serif;animation:slideUpBanner 0.4s cubic-bezier(0.16,1,0.3,1);';
    banner.innerHTML = `
      <div style="width:44px;height:44px;background:var(--theme,#2563eb);border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;flex-shrink:0">🫁</div>
      <div style="flex:1">
        <div style="font-size:0.95rem;font-weight:800;color:var(--text,#0f172a);margin-bottom:2px">Install VentGuide Pro</div>
        <div style="font-size:0.75rem;color:var(--text-2,#475569);font-weight:500">Get the full app experience offline</div>
      </div>
      <div style="display:flex;gap:8px">
        <button id="pwa-dismiss" style="background:none;border:none;color:var(--text-3,#94a3b8);font-size:0.8rem;font-weight:700;cursor:pointer;padding:8px">Later</button>
        <button id="pwa-install" style="background:var(--theme,#2563eb);color:#fff;border:none;border-radius:8px;padding:8px 14px;font-size:0.85rem;font-weight:700;cursor:pointer;box-shadow:0 4px 10px rgba(37,99,235,0.3)">Install</button>
      </div>
    `;

    // Add animation style if not exists
    if (!document.getElementById('pwa-animations')) {
      const style = document.createElement('style');
      style.id = 'pwa-animations';
      style.textContent = '@keyframes slideUpBanner { from { opacity:0; transform:translate(-50%, 30px); } to { opacity:1; transform:translate(-50%, 0); } }';
      document.head.appendChild(style);
    }

    document.body.appendChild(banner);

    document.getElementById('pwa-install').addEventListener('click', async () => {
      banner.remove();
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      if (outcome === 'accepted') {
        console.log('User accepted the install prompt');
      }
      deferredPrompt = null;
    });

    document.getElementById('pwa-dismiss').addEventListener('click', () => {
      banner.remove();
      sessionStorage.setItem('pwa_install_dismissed', 'true');
    });
  });

  // ── Security Measures (Anti-Copy, Anti-Screenshot, Anti-Zoom) ──
  window.addEventListener('DOMContentLoaded', () => {
    // Disable right-click / context menu
    document.addEventListener('contextmenu', e => {
      // Allow context menu only on inputs
      if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
        e.preventDefault();
      }
    });
    
    // Disable copy/cut globally
    document.addEventListener('copy', e => e.preventDefault());
    document.addEventListener('cut', e => e.preventDefault());
    
    // Prevent print and copy keyboard shortcuts
    document.addEventListener('keydown', e => {
      if ((e.ctrlKey || e.metaKey) && ['c', 's', 'p'].includes(e.key.toLowerCase())) {
        e.preventDefault();
      }
      if (e.key === 'PrintScreen') {
        navigator.clipboard.writeText(''); // Attempt to clear clipboard
      }
    });

    // Blur app in background to prevent OS task-switcher screenshots
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        document.body.style.filter = 'blur(12px)';
        document.body.style.pointerEvents = 'none';
      } else {
        document.body.style.filter = 'none';
        document.body.style.pointerEvents = 'auto';
      }
    });

    // Aggressive iOS Safari zoom prevention (matches main app)
    let lastTouchEnd = 0;
    
    document.addEventListener('gesturestart', e => e.preventDefault(), { passive: false });
    document.addEventListener('gesturechange', e => e.preventDefault(), { passive: false });
    
    document.addEventListener('touchmove', e => {
      if (e.touches && e.touches.length > 1) e.preventDefault();
    }, { passive: false });
    
    document.addEventListener('touchend', e => {
      const now = Date.now();
      if (now - lastTouchEnd <= 300) {
        e.preventDefault();
      }
      lastTouchEnd = now;
    }, { passive: false });
  });
})();
