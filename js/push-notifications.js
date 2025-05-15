// push-notifications.js - Funktionen für Push-Benachrichtigungen

// VAPID-Schlüssel für Web-Push-Benachrichtigungen
// Dieser Schlüssel muss mit dem öffentlichen Schlüssel in system/push_config.php übereinstimmen
const VAPID_PUBLIC_KEY = 'BKfIC_-g8p8PlrK3ao3oglWLDYvKyYy29OWJsluKZGggHfzg4W9aP6zDLGoTfbxhAtjRNg9ixPhlOFQvLZCbIvU';

// Service Worker registrieren
async function registerServiceWorker() {
    if ('serviceWorker' in navigator) {
        try {
            const registration = await navigator.serviceWorker.register('/service-worker.js');
            console.log('Service Worker erfolgreich registriert mit Scope:', registration.scope);
            return registration;
        } catch (error) {
            console.error('Service Worker Registrierung fehlgeschlagen:', error);
            return null;
        }
    } else {
        console.warn('Service Worker werden von diesem Browser nicht unterstützt');
        return null;
    }
}

// Umwandlung des Base64-String in Uint8Array für applicationServerKey
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

// Push-Benachrichtigungen abonnieren
async function subscribeToPushNotifications() {
    try {
        const registration = await navigator.serviceWorker.ready;
        
        // Prüfen, ob bereits ein Abonnement besteht
        let subscription = await registration.pushManager.getSubscription();
        
        if (subscription) {
            console.log('Bereits abonniert:', subscription);
            return { status: 'already-subscribed', subscription };
        }
        
        // Neues Abonnement erstellen
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY)
        });
        
        console.log('Neues Abonnement erstellt:', subscription);
        
        // Abonnement an den Server senden
        const response = await fetch('/api/push_notification.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ subscription })
        });
        
        const result = await response.json();
        
        if (result.status === 'success') {
            return { status: 'success', subscription };
        } else {
            console.error('Fehler beim Speichern des Abonnements:', result.message);
            return { status: 'error', message: result.message };
        }
    } catch (error) {
        console.error('Fehler beim Abonnieren von Push-Benachrichtigungen:', error);
        return { 
            status: 'error', 
            message: error.message,
            details: error.name === 'NotAllowedError' ? 'Benachrichtigungen wurden vom Benutzer blockiert' : 'Unbekannter Fehler'
        };
    }
}

// Prüfen, ob Push-Benachrichtigungen unterstützt werden
function arePushNotificationsSupported() {
    return 'serviceWorker' in navigator && 'PushManager' in window;
}

// Prüfen, ob Benachrichtigungen erlaubt sind
async function areNotificationsAllowed() {
    if (!('Notification' in window)) {
        return false;
    }
    
    if (Notification.permission === 'granted') {
        return true;
    }
    
    if (Notification.permission === 'denied') {
        return false;
    }
    
    // Wenn der Status 'default' ist, fragen wir nach Erlaubnis
    try {
        const permission = await Notification.requestPermission();
        return permission === 'granted';
    } catch (error) {
        console.error('Fehler beim Anfordern der Benachrichtigungserlaubnis:', error);
        return false;
    }
}

// Initialisierung der Push-Benachrichtigungen
async function initPushNotifications() {
    const pushStatus = document.getElementById('pushStatus');
    const subscribeButton = document.getElementById('subscribeButton');
    
    if (!pushStatus || !subscribeButton) {
        console.warn('Push-Benachrichtigungselemente nicht gefunden');
        return;
    }
    
    // Prüfen, ob Push-Benachrichtigungen unterstützt werden
    if (!arePushNotificationsSupported()) {
        pushStatus.textContent = 'Push-Benachrichtigungen werden von diesem Browser nicht unterstützt.';
        subscribeButton.disabled = true;
        return;
    }
    
    // Service Worker registrieren
    const registration = await registerServiceWorker();
    if (!registration) {
        pushStatus.textContent = 'Service Worker konnte nicht registriert werden.';
        subscribeButton.disabled = true;
        return;
    }
    
    // Prüfen, ob bereits ein Abonnement besteht
    const subscription = await registration.pushManager.getSubscription();
    if (subscription) {
        pushStatus.textContent = 'Sie haben Push-Benachrichtigungen bereits abonniert.';
        subscribeButton.disabled = true;
        return;
    }
    
    // Button aktivieren
    subscribeButton.disabled = false;
    pushStatus.textContent = 'Klicken Sie auf den Button, um Push-Benachrichtigungen zu abonnieren.';
    
    // Event-Listener für den Button
    subscribeButton.addEventListener('click', async () => {
        subscribeButton.disabled = true;
        pushStatus.textContent = 'Abonniere Push-Benachrichtigungen...';
        
        // Prüfen, ob Benachrichtigungen erlaubt sind
        const allowed = await areNotificationsAllowed();
        if (!allowed) {
            pushStatus.textContent = 'Benachrichtigungen wurden blockiert. Bitte erlauben Sie Benachrichtigungen in Ihren Browsereinstellungen.';
            subscribeButton.disabled = false;
            return;
        }
        
        // Push-Benachrichtigungen abonnieren
        const result = await subscribeToPushNotifications();
        
        if (result.status === 'success' || result.status === 'already-subscribed') {
            pushStatus.textContent = 'Sie haben Push-Benachrichtigungen erfolgreich abonniert.';
            subscribeButton.disabled = true;
        } else {
            pushStatus.textContent = `Fehler beim Abonnieren: ${result.message || 'Unbekannter Fehler'}`;
            subscribeButton.disabled = false;
        }
    });
}

// Initialisierung, wenn das DOM geladen ist
document.addEventListener('DOMContentLoaded', initPushNotifications);
