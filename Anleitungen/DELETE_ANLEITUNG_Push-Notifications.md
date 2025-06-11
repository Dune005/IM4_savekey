# Anleitung: Push-Benachrichtigungen im SaveKey System

## 1. Einleitung

Dieses Dokument beschreibt die technische Implementierung der Push-Benachrichtigungen im SaveKey System. Push-Benachrichtigungen dienen dazu, Benutzer über wichtige Ereignisse im System zu informieren, auch wenn sie die Webanwendung gerade nicht aktiv nutzen. Beispiele hierfür sind die Entnahme oder Rückgabe eines Schlüssels.

## 2. Verwendete Technologien

Die Push-Implementierung basiert auf folgenden Kerntechnologien:

*   **Web Push API:** Ein Standard des W3C, der es Webanwendungen ermöglicht, Nachrichten von einem Server zu empfangen und anzuzeigen, auch wenn die Webseite nicht geöffnet ist.
*   **Service Worker:** JavaScript-Dateien, die im Hintergrund des Browsers laufen und unabhängig von der Webseite agieren. Sie sind essentiell für den Empfang und die Anzeige von Push-Nachrichten.
*   **VAPID (Voluntary Application Server Identification):** Ein Sicherheitsprotokoll, das es Anwendungsservern erlaubt, sich bei Push-Diensten zu identifizieren.
*   **PHP:** Serverseitige Logik für die Verwaltung von Abonnements und das Senden von Nachrichten.
*   **`minishlink/web-push`:** Eine PHP-Bibliothek zur Vereinfachung des Sendens von Web-Push-Nachrichten.
*   **MySQL/MariaDB:** Zur Speicherung der Push-Abonnements.

## 3. Client-Seite (Browser)

Die clientseitige Logik ist hauptsächlich in `js/push-notifications.js` und dem Service Worker `service-worker.js` implementiert.

### 3.1. Service Worker (`service-worker.js`)

*   **Registrierung:** Der Service Worker wird von `js/push-notifications.js` registriert, wenn die Seite geladen wird und der Browser Service Worker unterstützt.
*   **Empfang von Push-Nachrichten (`push`-Ereignis):**
    *   Wenn eine Push-Nachricht vom Server eintrifft, wird dieses Ereignis ausgelöst.
    *   Der Service Worker extrahiert die Daten aus der Nachricht (Titel, Text, Icon, Badge, zusätzliche Daten wie eine URL).
    *   Er zeigt dann eine Systembenachrichtigung über `self.registration.showNotification(title, options)` an.
*   **Klick auf Benachrichtigung (`notificationclick`-Ereignis):**
    *   Wenn der Benutzer auf die angezeigte Benachrichtigung klickt, wird dieses Ereignis ausgelöst.
    *   Die Benachrichtigung wird geschlossen (`event.notification.close()`).
    *   Der Browser öffnet ein neues Fenster oder einen neuen Tab:
        *   Entweder mit der URL, die in den Daten der Benachrichtigung (`event.notification.data.url`) übergeben wurde.
        *   Oder, falls keine spezifische URL vorhanden ist, mit der Startseite (`/`).

### 3.2. JavaScript-Logik (`js/push-notifications.js`)

*   **VAPID Public Key:** Eine Konstante `VAPID_PUBLIC_KEY` ist hier definiert. Dieser Schlüssel muss mit dem auf dem Server verwendeten Public Key übereinstimmen.
*   **Unterstützungsprüfung (`arePushNotificationsSupported`):** Prüft, ob der Browser `'serviceWorker'` und `'PushManager'` unterstützt.
*   **Service Worker Registrierung (`registerServiceWorker`):** Registriert `/service-worker.js`.
*   **Benachrichtigungs-Erlaubnis (`areNotificationsAllowed`):**
    *   Prüft den aktuellen Status der Benachrichtigungserlaubnis (`Notification.permission`).
    *   Falls der Status `'default'` ist (Benutzer wurde noch nicht gefragt), wird `Notification.requestPermission()` aufgerufen, um den Benutzer um Erlaubnis zu bitten.
*   **Push-Abonnement (`subscribeToPushNotifications`):**
    *   Holt die aktive Service Worker-Registrierung.
    *   Prüft, ob bereits ein Abonnement existiert (`registration.pushManager.getSubscription()`).
        *   **Bestehendes Abonnement:** Das Abonnement wird erneut an den Server gesendet. Dies stellt sicher, dass die `user_id` auf dem Server korrekt zugeordnet ist, falls sich der Benutzer z.B. auf einem neuen Gerät angemeldet hat oder die Session-Informationen aktualisiert werden müssen.
        *   **Neues Abonnement:** Wenn kein Abonnement existiert, wird ein neues erstellt mit `registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY) })`.
            *   `userVisibleOnly: true` ist eine Voraussetzung der meisten Push-Dienste und bedeutet, dass jede Push-Nachricht zu einer sichtbaren Benachrichtigung führen muss.
            *   `applicationServerKey` ist der VAPID Public Key, der vom Server zur Authentifizierung verwendet wird.
    *   **Senden des Abonnements an den Server:** Das Abonnement-Objekt (enthält `endpoint`, `keys.p256dh`, `keys.auth`) wird als JSON-Payload per POST-Request an `/api/push_notification.php` gesendet.
        *   `credentials: 'include'` wird verwendet, um sicherzustellen, dass Session-Cookies mitgesendet werden, damit der Server den angemeldeten Benutzer identifizieren kann.
*   **Initialisierung (`initPushNotifications`):**
    *   Diese Funktion steuert die UI-Elemente (Statusanzeige, Abonnement-Button).
    *   Sie ruft die oben genannten Funktionen auf, um den Service Worker zu registrieren und den Abonnement-Prozess zu starten, falls notwendig.

## 4. Server-Seite (PHP)

Die serverseitige Logik ist hauptsächlich in `api/push_notification.php`, `system/push_notifications_config.php`, `system/push_config.php` und den API-Endpunkten wie `api/arduino_api.php` implementiert.

### 4.1. Abonnement-Verwaltung (`api/push_notification.php`)

*   **Empfang von Abonnement-Daten:** Das Skript verarbeitet POST-Anfragen, die JSON-Daten mit dem Abonnement-Objekt vom Client enthalten.
*   **Benutzeridentifikation:** Es greift auf die PHP-Session zu, um die `user_id` des angemeldeten Benutzers zu ermitteln.
*   **Datenbankinteraktion:**
    *   Es wird geprüft, ob ein Abonnement mit dem gegebenen `endpoint` bereits in der `push_subscriptions`-Tabelle existiert.
    *   **Aktualisierung:** Wenn ja, wird der bestehende Eintrag aktualisiert (insbesondere die `user_id`, `p256dh` und `auth`-Schlüssel).
    *   **Neuerstellung:** Wenn nein, wird ein neuer Eintrag in `push_subscriptions` mit `user_id`, `endpoint`, `p256dh` und `auth` erstellt.
*   **Antwort an den Client:** Sendet eine JSON-Antwort über den Erfolg oder Misserfolg der Operation.

### 4.2. Senden von Push-Benachrichtigungen

*   **Konfiguration (`system/push_notifications_config.php`):**
    *   `$PUSH_NOTIFICATIONS_ENABLED`: Ein Array, das festlegt, für welche Systemereignisse (z.B. `'key_removed'`, `'key_returned'`) Push-Benachrichtigungen aktiviert sind.
    *   `$PUSH_NOTIFICATIONS_MESSAGES`: Ein assoziatives Array, das für jedes Ereignis den Titel und den Text der Benachrichtigung definiert. Es können Platzhalter wie `[VORNAME]`, `[NACHNAME]` verwendet werden, die serverseitig ersetzt werden.
    *   `$PUSH_NOTIFICATIONS_URL`: Die Standard-URL, die geöffnet wird, wenn ein Benutzer auf eine Benachrichtigung klickt (kann durch Daten in der Push-Nachricht überschrieben werden).
*   **VAPID-Schlüssel (`system/push_config.php`):**
    *   Diese Datei (nicht direkt eingesehen, aber referenziert) enthält die `VAPID_PUBLIC_KEY` und `VAPID_PRIVATE_KEY`. Diese sind essentiell für die Authentifizierung des Anwendungsservers gegenüber den Push-Diensten. Der Public Key muss mit dem im Client-JavaScript (`js/push-notifications.js`) übereinstimmen.
*   **PHP-Bibliothek `minishlink/web-push`:**
    *   Diese Bibliothek wird verwendet, um das Erstellen und Senden von Web-Push-Nachrichten zu vereinfachen. Sie kümmert sich um die Verschlüsselung und die Kommunikation mit den verschiedenen Push-Diensten (z.B. FCM für Chrome, APNS für Safari).
*   **Sende-Funktionen:**
    *   `sendPushNotifications($pdo, $payload)` (in `api/push_notification.php`): Sendet eine Nachricht an *alle* Abonnenten. Wird z.B. für Testbenachrichtigungen aus dem Admin-Bereich verwendet.
    *   `sendPushNotificationToUser($pdo, $userId, $payload)` (in `api/push_notification.php`): Sendet eine Nachricht an einen *spezifischen* Benutzer.
    *   `sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload)` (in `api/push_notification.php` und `api/arduino_api.php`): Sendet Nachrichten an alle Benutzer, die mit einer bestimmten Geräteseriennummer verknüpft sind.
    *   **Ablauf des Sendens:**
        1.  Die relevanten Abonnements werden aus der `push_subscriptions`-Datenbanktabelle basierend auf den Kriterien (alle, spezifischer User, Seriennummer) geladen.
        2.  Ein `WebPush`-Objekt wird mit den VAPID-Authentifizierungsdaten initialisiert.
        3.  Für jedes Abonnement wird die Nachricht (als JSON-Payload) in die Warteschlange gestellt (`$webPush->queueNotification()`). Der Payload enthält typischerweise `title`, `body` und `data` (mit einer `url`).
        4.  Mit `$webPush->flush()` werden alle Nachrichten in der Warteschlange an die jeweiligen Push-Dienste gesendet.
        5.  Die Bibliothek gibt Rückmeldung über Erfolg oder Misserfolg jeder einzelnen Sendung.
        6.  **Fehlerbehandlung:** Wenn ein Push-Dienst meldet, dass ein Abonnement ungültig ist (z.B. HTTP-Status 404 oder 410), wird der entsprechende Eintrag aus der `push_subscriptions`-Tabelle gelöscht, um die Datenbank sauber zu halten.
*   **Auslöser für Benachrichtigungen (z.B. `api/arduino_api.php`):**
    *   API-Endpunkte wie `api/arduino_api.php` empfangen Daten von externen Quellen (z.B. dem Arduino-Board, wenn ein Schlüsselereignis stattfindet).
    *   Basierend auf dem empfangenen Ereignistyp (z.B. `'key_removed'`) und der Konfiguration in `system/push_notifications_config.php`:
        *   Wird geprüft, ob für dieses Ereignis Benachrichtigungen aktiviert sind.
        *   Ein entsprechender Payload (Titel, Text, Daten) wird zusammengestellt, wobei Platzhalter ggf. durch Benutzerdaten ersetzt werden.
        *   Die passende Sende-Funktion (z.B. `sendPushNotificationsForSeriennummer`) wird aufgerufen.

## 5. Datenbank (`push_subscriptions` Tabelle)

Die Tabelle `push_subscriptions` ist zentral für die Speicherung der Abonnements. Sie hat typischerweise folgende Spalten:

*   `id`: Eindeutiger Primärschlüssel.
*   `user_id`: Fremdschlüssel zur `benutzer`-Tabelle. Verknüpft das Abonnement mit einem Benutzerkonto. Kann `NULL` sein, falls Abonnements ohne Benutzerzuordnung erlaubt wären (aktuell nicht der Fall).
*   `endpoint`: Die eindeutige URL des Push-Dienstes, an die Nachrichten für dieses spezifische Abonnement gesendet werden müssen. Diese URL ist spezifisch für einen Browser auf einem Gerät.
*   `p256dh`: Der Public Key des Clients (Elliptic Curve Diffie-Hellman Key), der für die Verschlüsselung der Push-Nachricht verwendet wird.
*   `auth`: Ein Authentifizierungsgeheimnis, das ebenfalls für die Verschlüsselung benötigt wird.
*   `created_at`: Zeitstempel der Erstellung des Abonnements.

## 6. Typischer Ablauf einer Benachrichtigung (Beispiel: Schlüssel entnommen)

1.  Ein Benutzer entnimmt einen Schlüssel aus der SaveKey-Box.
2.  Das Arduino-Board registriert dieses Ereignis und sendet eine HTTP-POST-Anfrage mit dem Ereignistyp (`'key_removed'`) und der `seriennummer` der Box an `api/arduino_api.php`.
3.  `api/arduino_api.php` validiert die Anfrage und prüft in `system/push_notifications_config.php`, ob für `'key_removed'` Benachrichtigungen aktiviert sind.
4.  Wenn ja, wird die Nachrichtenvorlage für `'key_removed'` aus `system/push_notifications_config.php` geholt.
5.  Die Funktion `sendPushNotificationsForSeriennummer` wird aufgerufen.
6.  Diese Funktion fragt die `push_subscriptions`-Tabelle ab, um alle Abonnements von Benutzern zu finden, die mit der gegebenen `seriennummer` verknüpft sind.
7.  Für jedes gefundene Abonnement wird mithilfe der `minishlink/web-push`-Bibliothek eine verschlüsselte Push-Nachricht erstellt und an den im `endpoint` des Abonnements spezifizierten Push-Dienst gesendet.
8.  Der Push-Dienst leitet die Nachricht an den entsprechenden Client-Browser weiter.
9.  Im Browser des Clients empfängt der `service-worker.js` das `'push'`-Ereignis.
10. Der Service Worker parst die Nachricht und zeigt eine Systembenachrichtigung mit Titel und Text an.
11. Wenn der Benutzer auf die Benachrichtigung klickt, behandelt der Service Worker das `'notificationclick'`-Ereignis und leitet den Benutzer zur in der Nachricht oder Konfiguration definierten URL (z.B. `/protected.html`) weiter.

## 7. Wichtige Dateien im Überblick

*   `/js/push-notifications.js`: Clientseitige Logik für Abonnement und UI.
*   `/service-worker.js`: Empfängt und zeigt Push-Nachrichten im Browser an, behandelt Klicks.
*   `/api/push_notification.php`: Serverseitige API zum Speichern von Abonnements und Senden von Nachrichten.
*   `/system/push_notifications_config.php`: Konfiguration für Ereignisse, Nachrichtentexte und Ziel-URL.
*   `/system/push_config.php`: Enthält die VAPID Public und Private Keys.
*   `/vendor/minishlink/web-push/`: Die verwendete PHP-Bibliothek.
*   `/api/arduino_api.php`: Beispielhafter API-Endpunkt, der Push-Benachrichtigungen als Reaktion auf Hardware-Ereignisse auslöst.
*   `/admin/push_notifications.php`: Administrationsseite zur Konfiguration und zum Testen von Push-Benachrichtigungen.
