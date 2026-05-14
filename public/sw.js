self.addEventListener('install', (e) => {
    self.skipWaiting();
});

self.addEventListener('activate', (e) => {
    return self.clients.claim();
});

self.addEventListener('fetch', (e) => {
    // Only intercept GET requests to avoid Response conversion errors on POST/etc
    if (e.request.method !== 'GET') return;

    e.respondWith(
        fetch(e.request).catch(() => {
            return caches.match(e.request).then((response) => {
                // Always return a Response object to avoid "Failed to convert value to 'Response'"
                return response || new Response('Offline content not available', {
                    status: 503,
                    statusText: 'Service Unavailable',
                    headers: new Headers({ 'Content-Type': 'text/plain' })
                });
            });
        })
    );
});
