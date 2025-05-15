<?php
// push_notifications_config.php - Konfiguration für Push-Benachrichtigungen

// Aktivieren oder Deaktivieren von Push-Benachrichtigungen für verschiedene Ereignisse
$PUSH_NOTIFICATIONS_ENABLED = [
    // Schlüsselentnahme
    'key_removed' => true,
    
    // Schlüsselrückgabe
    'key_returned' => true,
    
    // RFID/NFC-Scan
    'rfid_scan' => false,
    
    // Schlüsselentnahme ohne Verifizierung (nach Ablauf des Timeouts)
    'key_removed_unverified' => true,
    
    // Schlüsselentnahme mit erfolgreicher Verifizierung
    'key_removed_verified' => true
];

// Titel und Text für verschiedene Ereignistypen
// Verfügbare Platzhalter:
// [VORNAME] - Vorname des Benutzers
// [NACHNAME] - Nachname des Benutzers
// [BENUTZERNAME] - Benutzername des Benutzers
$PUSH_NOTIFICATIONS_MESSAGES = [
    'key_removed' => [
        'title' => 'SaveKey: Schlüssel entnommen',
        'body' => 'Ein Schlüssel wurde aus der Box entnommen. Warte auf Verifizierung...'
    ],
    
    'key_returned' => [
        'title' => 'SaveKey: Schlüssel zurückgegeben',
        'body' => 'Ein Schlüssel wurde in die Box zurückgelegt.'
    ],
    
    'rfid_scan' => [
        'title' => 'SaveKey: RFID/NFC-Scan',
        'body' => 'Ein RFID/NFC-Chip wurde von [VORNAME] [NACHNAME] gescannt.'
    ],
    
    'key_removed_unverified' => [
        'title' => 'SaveKey: Unbestätigte Schlüsselentnahme',
        'body' => 'Ein Schlüssel wurde entnommen, aber nicht innerhalb von 5 Minuten verifiziert!'
    ],
    
    'key_removed_verified' => [
        'title' => 'SaveKey: Schlüsselentnahme bestätigt',
        'body' => 'Die Schlüsselentnahme wurde von [VORNAME] [NACHNAME] erfolgreich verifiziert.'
    ]
];

// URL, zu der der Benutzer weitergeleitet wird, wenn er auf die Benachrichtigung klickt
$PUSH_NOTIFICATIONS_URL = '/protected.html';
?>