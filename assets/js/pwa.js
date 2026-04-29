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
})();
