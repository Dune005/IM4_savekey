# Anleitung zur Einrichtung von Push-Benachrichtigungen

Diese Anleitung beschreibt, wie du Push-Benachrichtigungen für das SaveKey-System einrichtest.

## Übersicht

Push-Benachrichtigungen ermöglichen es, Benutzer in Echtzeit über Änderungen am Schlüsselstatus zu informieren, auch wenn sie die Website nicht geöffnet haben. Dies ist besonders nützlich, um Benutzer über Schlüsselentnahmen oder -rückgaben zu informieren.

## Voraussetzungen

- Ein Webserver mit HTTPS (Push-Benachrichtigungen funktionieren nur über sichere Verbindungen)
- PHP 7.4 oder höher
- Composer (für die Installation der WebPush-Bibliothek)
- MySQL-Datenbank

## Schritt 1: Datenbanktabelle erstellen

Führe das folgende SQL-Skript aus, um die benötigte Datenbanktabelle zu erstellen:

```sql
-- Führe die Datei system/setup_push_subscriptions_table.sql aus
SOURCE system/setup_push_subscriptions_table.sql;
```

## Schritt 2: Composer-Abhängigkeiten installieren

Installiere die WebPush-Bibliothek mit Composer:

```bash
composer install
```

Dies wird die in der `composer.json`-Datei definierten Abhängigkeiten installieren.

## Schritt 3: VAPID-Schlüssel generieren

VAPID-Schlüssel werden für die Authentifizierung von Push-Benachrichtigungen verwendet. Du kannst sie mit dem folgenden Befehl generieren:

```bash
./vendor/bin/web-push generate-vapid-keys
```

Alternativ kannst du auch einen Online-Generator verwenden:
https://web-push-codelab.glitch.me/

Trage die generierten Schlüssel in die Datei `system/push_config.php` ein:

```php
define('VAPID_PUBLIC_KEY', 'dein_generierter_öffentlicher_schlüssel');
define('VAPID_PRIVATE_KEY', 'dein_generierter_privater_schlüssel');
define('VAPID_SUBJECT', 'mailto:deine-email@beispiel.de');
```

Trage den öffentlichen Schlüssel auch in die Datei `js/push-notifications.js` ein:

```javascript
const VAPID_PUBLIC_KEY = 'dein_generierter_öffentlicher_schlüssel';
```

## Schritt 4: Testen der Push-Benachrichtigungen

1. Öffne die Startseite des SaveKey-Systems
2. Klicke auf den Button "Push-Benachrichtigungen aktivieren"
3. Erlaube die Benachrichtigungen im Browser-Dialog
4. Teste die Benachrichtigungen, indem du folgende URL aufrufst:
   `https://deine-domain.com/api/push_notification.php?test=true`

## Schritt 5: Integration in das Schlüsselbox-System

Um Push-Benachrichtigungen bei Statusänderungen der Schlüsselbox zu senden, füge den folgenden Code in die entsprechenden Dateien ein:

### In `api/hardware_event.php`:

```php
// Nach erfolgreicher Verarbeitung eines Ereignisses
require_once '../api/push_notification.php';

$payload = [
    'title' => 'SaveKey Statusänderung',
    'body' => 'Der Status deiner Schlüsselbox hat sich geändert: ' . $eventType,
    'data' => [
        'url' => '/protected.html'
    ]
];

// Sende Benachrichtigung an alle Benutzer mit dieser Seriennummer
sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload);
```

### In `api/key_action.php`:

```php
// Nach erfolgreicher Schlüsselentnahme oder -rückgabe
require_once 'push_notification.php';

$payload = [
    'title' => 'SaveKey Schlüsselaktion',
    'body' => 'Eine Schlüsselaktion wurde durchgeführt: ' . $action,
    'data' => [
        'url' => '/protected.html'
    ]
];

// Sende Benachrichtigung an den Benutzer
sendPushNotificationToUser($pdo, $userId, $payload);
```

## Fehlerbehebung

### Push-Benachrichtigungen werden nicht angezeigt

- Stelle sicher, dass deine Website über HTTPS läuft
- Überprüfe, ob der Browser Push-Benachrichtigungen unterstützt
- Überprüfe, ob der Benutzer Push-Benachrichtigungen für deine Website erlaubt hat
- Überprüfe die Browser-Konsole auf Fehler

### Fehler beim Speichern des Abonnements

- Stelle sicher, dass die Datenbanktabelle `push_subscriptions` korrekt erstellt wurde
- Überprüfe die Datenbankverbindung in `system/config.php`
- Überprüfe die Berechtigungen des Datenbankbenutzers

### Fehler beim Senden von Push-Benachrichtigungen

- Stelle sicher, dass die VAPID-Schlüssel korrekt sind
- Überprüfe, ob die WebPush-Bibliothek korrekt installiert wurde
- Überprüfe die Server-Logs auf Fehler
