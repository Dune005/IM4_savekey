<?php
// test_push.php - Testseite für Push-Benachrichtigungen
session_start();

// Überprüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}

// Konfiguration und Funktionen laden
try {
    require_once 'system/config.php';
    require_once 'system/push_notifications_config.php';
    require_once 'api/push_notification.php';
} catch (Exception $e) {
    die('Fehler beim Laden der erforderlichen Dateien: ' . $e->getMessage());
}

$message = '';
$error = '';

// Test-Benachrichtigung senden
if (isset($_POST['send_test'])) {
    try {
        $payload = [
            'title' => 'SaveKey Test-Benachrichtigung',
            'body' => 'Dies ist eine Test-Benachrichtigung vom SaveKey-System!',
            'data' => [
                'url' => '/protected.html'
            ]
        ];

        // Sende an alle Benutzer
        $result = sendPushNotifications($pdo, $payload);

        if ($result['status'] === 'success') {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        $error = 'Fehler beim Senden der Test-Benachrichtigung: ' . $e->getMessage();
    }
}

// Benachrichtigung an den aktuellen Benutzer senden
if (isset($_POST['send_to_me'])) {
    try {
        $userId = $_SESSION['user_id'];

        // Benutzerinformationen für Platzhalter abrufen
        $stmt = $pdo->prepare("SELECT vorname, nachname FROM benutzer WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $payload = [
            'title' => 'SaveKey Persönliche Benachrichtigung',
            'body' => 'Hallo ' . $user['vorname'] . ' ' . $user['nachname'] . ', dies ist eine persönliche Test-Benachrichtigung!',
            'data' => [
                'url' => '/protected.html'
            ]
        ];

        // Sende an den aktuellen Benutzer
        $result = sendPushNotificationToUser($pdo, $userId, $payload);

        if ($result['status'] === 'success') {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } catch (Exception $e) {
        $error = 'Fehler beim Senden der persönlichen Benachrichtigung: ' . $e->getMessage();
    }
}

// Benachrichtigung an alle Benutzer mit der gleichen Seriennummer senden
if (isset($_POST['send_to_box'])) {
    try {
        $seriennummer = $_SESSION['seriennummer'];

        if (empty($seriennummer)) {
            $error = 'Keine Seriennummer gefunden';
        } else {
            $payload = [
                'title' => 'SaveKey Box-Benachrichtigung',
                'body' => 'Dies ist eine Test-Benachrichtigung für alle Benutzer der Box mit der Seriennummer ' . $seriennummer,
                'data' => [
                    'url' => '/protected.html',
                    'seriennummer' => $seriennummer
                ]
            ];

            // Sende an alle Benutzer mit der gleichen Seriennummer
            $result = sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload);

            if ($result['status'] === 'success') {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    } catch (Exception $e) {
        $error = 'Fehler beim Senden der Box-Benachrichtigung: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Push-Benachrichtigungen Test</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .success-message {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .button-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        button {
            padding: 10px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #2980b9;
        }
        .back-button {
            background-color: #7f8c8d;
        }
        .back-button:hover {
            background-color: #6c7a7d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Push-Benachrichtigungen Test</h1>

        <?php if (!empty($message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <p>Auf dieser Seite können Sie verschiedene Push-Benachrichtigungen testen.</p>

        <form method="post">
            <div class="button-container">
                <button type="submit" name="send_test">Test-Benachrichtigung an alle senden</button>
                <button type="submit" name="send_to_me">Benachrichtigung nur an mich senden</button>
                <button type="submit" name="send_to_box">Benachrichtigung an alle Benutzer meiner Box senden</button>
            </div>
        </form>

        <div class="button-container">
            <a href="protected.html" class="back-button" style="text-decoration: none; display: inline-block; padding: 10px 15px; color: white;">Zurück zur geschützten Seite</a>
        </div>
    </div>
</body>
</html>
