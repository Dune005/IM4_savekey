<?php
// generate_vapid_keys.php - Skript zum Generieren von VAPID-Schlüsseln für Push-Benachrichtigungen

// Prüfen, ob die WebPush-Bibliothek installiert ist
if (!file_exists('../vendor/autoload.php')) {
    echo "Fehler: Die WebPush-Bibliothek ist nicht installiert.\n";
    echo "Bitte führe zuerst 'composer install' aus.\n";
    exit(1);
}

require_once '../vendor/autoload.php';

use Minishlink\WebPush\VAPID;

// VAPID-Schlüssel generieren
echo "Generiere VAPID-Schlüssel für Push-Benachrichtigungen...\n";
$vapidKeys = VAPID::createVapidKeys();

echo "\nDeine VAPID-Schlüssel wurden erfolgreich generiert!\n";
echo "Bitte trage diese Schlüssel in die folgenden Dateien ein:\n\n";

echo "1. In system/push_config.php:\n";
echo "define('VAPID_PUBLIC_KEY', '" . $vapidKeys['publicKey'] . "');\n";
echo "define('VAPID_PRIVATE_KEY', '" . $vapidKeys['privateKey'] . "');\n";
echo "define('VAPID_SUBJECT', 'mailto:deine-email@beispiel.de');\n\n";

echo "2. In js/push-notifications.js:\n";
echo "const VAPID_PUBLIC_KEY = '" . $vapidKeys['publicKey'] . "';\n\n";

echo "Hinweis: Ersetze 'mailto:deine-email@beispiel.de' mit deiner eigenen E-Mail-Adresse.\n";
echo "Diese E-Mail-Adresse wird für die VAPID-Authentifizierung verwendet.\n";
?>
