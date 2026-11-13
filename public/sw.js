// Beginner Note: The service worker caches only public shell files, never private PHP pages.
const CACHE = 'ais-static-v1';
const STATIC_FILES = [
    'offline.html',
    'assets/css/app.css',
    'assets/js/app.js',
    'assets/icons/icon-192.svg',
    'assets/icons/icon-512.svg',
    'manifest.webmanifest',
];
self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(STATIC_FILES)));
    self.skipWaiting();
});
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) =>
                Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))),
            ),
    );
    self.clients.claim();
});
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    if (request.method !== 'GET' || url.origin !== self.location.origin) return;
    if (url.pathname.endsWith('.php') || url.pathname.includes('/api/')) {
        if (request.mode === 'navigate')
            event.respondWith(fetch(request).catch(() => caches.match('offline.html')));
        return;
    }
    event.respondWith(
        fetch(request).catch(async () => {
            const cached = await caches.match(request);
            if (cached) return cached;
            if (request.mode === 'navigate') return caches.match('offline.html');
            return Response.error();
        }),
    );
});
