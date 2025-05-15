<?php
// Vereinfachte Push-Benachrichtigungskonfigurationsseite
session_start();

// Aktiviere Fehlerausgabe für Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Überprüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.html');
    exit;
}

// Konfigurationsdateien laden
require_once '../system/config.php';
require_once '../system/push_notifications_config.php';

// Globale Variablen
global $pdo;

// Erfolgs- und Fehlermeldungen
$successMessage = '';
$errorMessage = '';

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
            throw new Exception('Keine Seriennummer für diesen Benutzer gefunden.');
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
        
        button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
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
            <h1>Push-Benachrichtigungen Test</h1>
            
            <?php if (!empty($successMessage)): ?>
                <div class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
                <div class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>
            
            <p>Diese vereinfachte Seite ermöglicht es dir, eine Test-Benachrichtigung zu senden.</p>
            
            <form method="post">
                <button type="submit" name="send_test">Test-Benachrichtigung senden</button>
            </form>
            
            <h2>Links</h2>
            <p><a href="push_debug.php">Zur Debug-Seite</a></p>
            <p><a href="../protected.html">Zurück zur geschützten Seite</a></p>
        </div>
    </main>
    
    <footer>
        <div class="container">
            <p>&copy; 2024 IM4 SaveKey System</p>
        </div>
    </footer>
</body>
</html>
