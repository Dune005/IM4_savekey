<?php
// Einfache Test-Datei ohne Abhängigkeiten
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einfacher PHP-Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .success {
            color: green;
            font-weight: bold;
        }
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Einfacher PHP-Test</h1>
    
    <p>Diese Seite testet, ob PHP korrekt verarbeitet wird.</p>
    
    <h2>PHP-Version</h2>
    <p>PHP-Version: <span class="success"><?php echo phpversion(); ?></span></p>
    
    <h2>Datum und Uhrzeit</h2>
    <p>Aktuelles Datum und Uhrzeit: <span class="success"><?php echo date('Y-m-d H:i:s'); ?></span></p>
    
    <h2>Links</h2>
    <p><a href="push_notifications.php">Zur Push-Benachrichtigungskonfiguration</a></p>
    <p><a href="push_debug.php">Zur Debug-Seite</a></p>
    <p><a href="../protected.html">Zurück zur geschützten Seite</a></p>
</body>
</html>
