<?php
// push_notifications.php - Administrationsoberfläche für Push-Benachrichtigungen
session_start();

// Überprüfen, ob der Benutzer angemeldet ist und ein Administrator ist
require_once '../system/config.php';

// Stellen sicher, dass $pdo verfügbar ist
global $pdo;

// Aktiviere Fehlerausgabe für Debugging
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.html');
    exit;
}

// Push-Benachrichtigungskonfiguration laden
require_once '../system/push_notifications_config.php';

// Wenn das Formular abgesendet wurde, die Konfiguration aktualisieren
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_config'])) {
    try {
        // Konfigurationsdatei lesen
        $configFile = file_get_contents('../system/push_notifications_config.php');

        // Aktivierte/Deaktivierte Ereignisse aktualisieren
        foreach ($PUSH_NOTIFICATIONS_ENABLED as $event => $enabled) {
            $newValue = isset($_POST['enabled'][$event]) ? 'true' : 'false';
            $pattern = "/('$event'\s*=>\s*)(?:true|false)/";
            $replacement = "$1$newValue";
            $configFile = preg_replace($pattern, $replacement, $configFile);
        }

        // Titel und Text für Ereignisse aktualisieren
        foreach ($PUSH_NOTIFICATIONS_MESSAGES as $event => $messages) {
            if (isset($_POST['title'][$event])) {
                $newTitle = str_replace("'", "\'", $_POST['title'][$event]);
                $pattern = "/('$event'\s*=>\s*\[\s*'title'\s*=>\s*')([^']+)(')/";
                $replacement = "$1$newTitle$3";
                $configFile = preg_replace($pattern, $replacement, $configFile);
            }

            if (isset($_POST['body'][$event])) {
                $newBody = str_replace("'", "\'", $_POST['body'][$event]);
                $pattern = "/('$event'\s*=>\s*\[\s*'title'\s*=>\s*'[^']+',\s*'body'\s*=>\s*')([^']+)(')/";
                $replacement = "$1$newBody$3";
                $configFile = preg_replace($pattern, $replacement, $configFile);
            }
        }

        // URL aktualisieren
        if (isset($_POST['url'])) {
            $newUrl = str_replace("'", "\'", $_POST['url']);
            $pattern = "/(\\\$PUSH_NOTIFICATIONS_URL\s*=\s*')([^']+)(')/";
            $replacement = "$1$newUrl$3";
            $configFile = preg_replace($pattern, $replacement, $configFile);
        }

        // Konfigurationsdatei speichern
        file_put_contents('../system/push_notifications_config.php', $configFile);

        // Konfiguration neu laden
        require_once '../system/push_notifications_config.php';

        $successMessage = 'Konfiguration erfolgreich gespeichert.';
    } catch (Exception $e) {
        $errorMessage = 'Fehler beim Speichern der Konfiguration: ' . $e->getMessage();
    }
}

// Test-Benachrichtigung senden
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
    try {
        require_once '../api/push_notification.php';

        // Seriennummer aus der Datenbank abrufen
        $stmt = $pdo->prepare("SELECT seriennummer FROM benutzer WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $seriennummer = $user['seriennummer'] ?? '';

        if (empty($seriennummer)) {
            throw new Exception('Keine Seriennummer für diesen Benutzer gefunden. Bitte weisen Sie Ihrem Benutzerkonto eine Seriennummer zu.');
        }

        $payload = [
            'title' => 'SaveKey Test-Benachrichtigung',
            'body' => 'Dies ist eine Test-Benachrichtigung vom SaveKey-System!',
            'data' => [
                'url' => $PUSH_NOTIFICATIONS_URL,
                'event_type' => 'test',
                'seriennummer' => $seriennummer
            ]
        ];

        $result = sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload);

        if ($result['status'] === 'success') {
            $successMessage = 'Test-Benachrichtigung erfolgreich gesendet: ' . $result['message'];
        } else if ($result['status'] === 'info') {
            $errorMessage = 'Keine Abonnements gefunden: ' . $result['message'];
        } else {
            $errorMessage = 'Fehler beim Senden der Test-Benachrichtigung: ' . $result['message'];
        }
    } catch (Exception $e) {
        $errorMessage = 'Fehler beim Senden der Test-Benachrichtigung: ' . $e->getMessage();
    }
}

// HTML-Header
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Push-Benachrichtigungen - SaveKey Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .form-group textarea {
            height: 80px;
        }

        .event-config {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 4px;
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .success-message {
            padding: 10px;
            background-color: #d4edda;
            color: #155724;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .error-message {
            padding: 10px;
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        button {
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        button.secondary {
            background-color: var(--secondary-color);
        }

        button:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <img src="../images/logo_savekey_text_white.svg" alt="SaveKey Logo" class="logo">
            <nav>
                <ul>
                    <li><a href="../protected.html">Zurück</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="admin-container">
            <h1>Push-Benachrichtigungen Konfiguration</h1>

            <?php if (!empty($successMessage)): ?>
                <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>

            <?php if (!empty($errorMessage)): ?>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>

            <form method="post">
                <h2>Ereignisse</h2>

                <?php foreach ($PUSH_NOTIFICATIONS_ENABLED as $event => $enabled): ?>
                    <div class="event-config">
                        <div class="event-header">
                            <h3><?php echo htmlspecialchars(getEventName($event)); ?></h3>
                            <label>
                                <input type="checkbox" name="enabled[<?php echo htmlspecialchars($event); ?>]" <?php echo $enabled ? 'checked' : ''; ?>>
                                Aktiviert
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="title_<?php echo htmlspecialchars($event); ?>">Titel:</label>
                            <input type="text" id="title_<?php echo htmlspecialchars($event); ?>" name="title[<?php echo htmlspecialchars($event); ?>]" value="<?php echo htmlspecialchars($PUSH_NOTIFICATIONS_MESSAGES[$event]['title']); ?>">
                        </div>

                        <div class="form-group">
                            <label for="body_<?php echo htmlspecialchars($event); ?>">Text:</label>
                            <textarea id="body_<?php echo htmlspecialchars($event); ?>" name="body[<?php echo htmlspecialchars($event); ?>]"><?php echo htmlspecialchars($PUSH_NOTIFICATIONS_MESSAGES[$event]['body']); ?></textarea>
                        </div>
                    </div>
                <?php endforeach; ?>

                <h2>Allgemeine Einstellungen</h2>

                <div class="form-group">
                    <label for="url">URL bei Klick auf Benachrichtigung:</label>
                    <input type="text" id="url" name="url" value="<?php echo htmlspecialchars($PUSH_NOTIFICATIONS_URL); ?>">
                </div>

                <div class="button-container">
                    <button type="submit" name="save_config">Konfiguration speichern</button>
                    <button type="submit" name="send_test" class="secondary">Test-Benachrichtigung senden</button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 IM4 SaveKey System</p>
        </div>
    </footer>
</body>
</html>

<?php
// Hilfsfunktion zum Anzeigen eines benutzerfreundlichen Namens für ein Ereignis
function getEventName($event) {
    $names = [
        'key_removed' => 'Schlüssel entnommen',
        'key_returned' => 'Schlüssel zurückgegeben',
        'rfid_scan' => 'RFID/NFC-Scan',
        'key_removed_unverified' => 'Unbestätigte Schlüsselentnahme',
        'key_removed_verified' => 'Bestätigte Schlüsselentnahme'
    ];

    return $names[$event] ?? $event;
}
?>
