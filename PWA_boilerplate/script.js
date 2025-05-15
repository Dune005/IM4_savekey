document.addEventListener('DOMContentLoaded', () => {
    const subscribeButton = document.getElementById('subscribeButton');
    const notificationStatus = document.getElementById('notificationStatus');

    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                console.log('Service Worker registered with scope:', registration.scope);
                return registration.pushManager.getSubscription();
            })
            .then(subscription => {
                if (subscription) {
                    notificationStatus.textContent = 'Already subscribed to push!';
                    subscribeButton.disabled = true;
                } else {
                    subscribeButton.addEventListener('click', subscribeUser);
                }
            })
            .catch(error => {
                console.error('Error registering service worker:', error);
                notificationStatus.textContent = 'Service worker registration failed.';
            });
    } else {
        notificationStatus.textContent = 'Service workers are not supported.';
        subscribeButton.disabled = true;
    }

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

            notificationStatus.textContent = 'Successfully subscribed to push!';
            subscribeButton.disabled = true;
        } catch (error) {
            console.error('Failed to subscribe to push: ', error);
            notificationStatus.textContent = 'Failed to subscribe.';
        }
    }
});