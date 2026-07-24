const CACHE = 'tms-v1';
const ASSETS = [
  '/tourism_system/',
  '/tourism_system/index.php',
  '/tourism_system/login.php',
  '/tourism_system/register.php',
  '/tourism_system/manifest.json',
  '/tourism_system/icon-192.png',
  '/tourism_system/icon-512.png'
];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))));
  self.clients.claim();
});

self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  e.respondWith(
    fetch(e.request).then(r => {
      const res = r.clone();
      caches.open(CACHE).then(c => c.put(e.request, res));
      return r;
    }).catch(() => caches.match(e.request).then(r => r || new Response('Offline', {status:503})))
  );
});
