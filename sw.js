const CACHE_NAME = 'ghost-pix-v8';
const ASSETS = [
    'style.css?v=8.0',
    'script.js?v=8.0',
    'logo_premium.png?v=8.0'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS);
        })
    );
});

self.addEventListener('fetch', (event) => {
    event.respondWith(
        caches.match(event.request).then((response) => {
            return response || fetch(event.request);
        })
    );
});
