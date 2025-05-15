// SaveKey Service Worker für Push-Benachrichtigungen

self.addEventListener('install', event => {
    console.log('Service Worker installiert');
});

self.addEventListener('activate', event => {
    console.log('Service Worker aktiviert');
});

self.addEventListener('push', event => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'SaveKey Benachrichtigung';
    const options = {
        body: data.body || 'Es gibt eine neue Benachrichtigung von SaveKey!',
        icon: 'images/logo_savekey_text_white.svg',
        badge: 'images/logo_savekey_text_white.svg',
        data: data.data || {}
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    
    // Wenn eine URL in den Daten enthalten ist, diese öffnen
    if (event.notification.data && event.notification.data.url) {
        event.waitUntil(
            clients.openWindow(event.notification.data.url)
        );
    } else {
        // Ansonsten zur Hauptseite navigieren
        event.waitUntil(
            clients.openWindow('/')
        );
    }
});
