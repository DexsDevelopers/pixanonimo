const CACHE_NAME = 'ghost-pix-v9.4';
const ASSETS = [
    'style.css?v=125.3',
    'script.js?v=125.3',
    'logo_premium.png?v=9.1',
    'manifest.json?v=3.1'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('Cleaning old cache:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    event.respondWith(
        fetch(event.request).catch(async () => {
            const cached = await caches.match(event.request);
            if (cached) return cached;
            // Se falhar e não estiver no cache, deixa a rede lidar ou retorna erro
            return new Response('Network error occurred', {
                status: 408,
                headers: { 'Content-Type': 'text/plain' }
            });
        })
    );
});

// --- PUSH NOTIFICATIONS ---
self.addEventListener('push', (event) => {
    let data = { title: 'Ghost Pix', body: 'Nova notificação recebida!', icon: 'logo_premium.png' };

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || 'logo_premium.png',
        badge: 'logo_premium.png',
        vibrate: [100, 50, 100],
        data: {
            url: data.data ? data.data.url : 'dashboard.php'
        },
        actions: [
            { action: 'open', title: 'Ver Agora' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const urlToOpen = new URL(event.notification.data.url, self.location.origin).href;

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (let client of windowClients) {
                if (client.url === urlToOpen && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(urlToOpen);
            }
        })
    );
});
