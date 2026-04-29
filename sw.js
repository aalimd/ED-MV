const CACHE_NAME = 'edmv-pwa-v1';
const STATIC_PATHS = [
  'manifest.webmanifest',
  'offline.html',
  'assets/css/auth.css',
  'assets/css/admin.css',
  'assets/js/pwa.js',
  'assets/pwa/icon.svg',
  'assets/pwa/icon-192.png',
  'assets/pwa/icon-512.png',
  'assets/pwa/maskable-512.png',
  'assets/pwa/apple-touch-icon.png'
];

const scopeUrl = new URL(self.registration.scope);
const offlineUrl = new URL('offline.html', scopeUrl).toString();
const staticUrls = STATIC_PATHS.map((path) => new URL(path, scopeUrl).toString());

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(staticUrls))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin || !url.href.startsWith(self.registration.scope)) return;

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match(offlineUrl, { ignoreSearch: true }))
    );
    return;
  }

  const isStaticAsset = staticUrls.includes(url.toString())
    || url.pathname.includes('/assets/')
    || /\.(?:css|js|png|svg|webmanifest|ico|woff2?)$/i.test(url.pathname);

  if (!isStaticAsset) return;

  event.respondWith(
    caches.match(request).then((cached) => {
      if (cached) return cached;

      return fetch(request).then((response) => {
        if (response.ok && response.type === 'basic') {
          const copy = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
        }
        return response;
      });
    })
  );
});
