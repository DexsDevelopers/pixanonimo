const CACHE_NAME = 'ghost-pix-v1';
const ASSETS = [
    'style.css?v=5.1',
    'script.js?v=5.1',
    'ghost.jpg?v=5.0'
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
