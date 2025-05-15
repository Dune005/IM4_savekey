const VAPID_PUBLIC_KEY = 'YOUR_VAPID_PUBLIC_KEY_HERE';

self.addEventListener('install', event => {
    console.log('Service Worker installed');
});

self.addEventListener('activate', event => {
    console.log('Service Worker activated');
});

self.addEventListener('push', event => {
    const data = event.data ? event.data.json() : {};
    const title = data.title || 'New Notification';
    const options = {
        body: data.body || 'This is a push notification!',
        icon: 'images/icon-192x192.png' // Path to your notification icon
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    // Add logic to handle notification clicks (e.g., open a specific URL)
    console.log('Notification clicked');
});

async function subscribeUser() {
    if (!('serviceWorker' in navigator)) {
        console.log('Service workers are not supported.');
        return;
    }

    try {
        const registration = await navigator.serviceWorker.ready;
        const subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: VAPID_PUBLIC_KEY
        });

        // Send the subscription object to your server to store it
        await fetch('push_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ subscription: subscription })
        });

        document.getElementById('notificationStatus').textContent = 'Successfully subscribed to push!';
    } catch (error) {
        console.error('Failed to subscribe to push: ', error);
        document.getElementById('notificationStatus').textContent = 'Failed to subscribe.';
    }
}