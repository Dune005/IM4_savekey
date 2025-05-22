<?php
// test_push_simple.php - Einfache Testseite für Push-Benachrichtigungen
session_start();

// WebPush-Klassen importieren - müssen auf oberster Ebene stehen
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Fehlerausgabe aktivieren
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Überprüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['user_id'])) {
    echo "Nicht angemeldet. <a href='login.html'>Zum Login</a>";
    exit;
}

$message = '';
$error = '';
$debug = '';

// Konfiguration laden
try {
    require_once 'system/config.php';
    $debug .= "Config geladen<br>";

    if (file_exists('system/push_config.php')) {
        require_once 'system/push_config.php';
        $debug .= "Push-Config geladen<br>";
    } else {
        $error .= "Push-Config nicht gefunden<br>";
    }

    if (file_exists('system/push_notifications_config.php')) {
        require_once 'system/push_notifications_config.php';
        $debug .= "Push-Notifications-Config geladen<br>";
    } else {
        $error .= "Push-Notifications-Config nicht gefunden<br>";
    }

    if (file_exists('vendor/autoload.php')) {
        require_once 'vendor/autoload.php';
        $debug .= "Autoload geladen<br>";
    } else {
        $error .= "Autoload nicht gefunden<br>";
    }

    $debug .= "WebPush-Klassen importiert<br>";

} catch (Exception $e) {
    $error .= "Fehler beim Laden der Konfiguration: " . $e->getMessage() . "<br>";
}

// Test-Benachrichtigung senden
if (isset($_POST['send_test'])) {
    try {
        // Abonnements aus der Datenbank abrufen
        $stmt = $pdo->query("SELECT * FROM push_subscriptions");
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($subscriptions)) {
            $error .= "Keine Abonnements gefunden<br>";
        } else {
            $debug .= "Anzahl Abonnements: " . count($subscriptions) . "<br>";

            // VAPID-Konfiguration
            $auth = [
                'VAPID' => [
                    'subject' => VAPID_SUBJECT,
                    'publicKey' => VAPID_PUBLIC_KEY,
                    'privateKey' => VAPID_PRIVATE_KEY,
                ],
            ];

            $debug .= "VAPID-Konfiguration erstellt<br>";

            // WebPush-Instanz erstellen
            $webPush = new WebPush($auth);
            $debug .= "WebPush-Instanz erstellt<br>";

            // Payload für die Benachrichtigung
            $payload = [
                'title' => 'SaveKey Test-Benachrichtigung',
                'body' => 'Dies ist eine Test-Benachrichtigung vom SaveKey-System!',
                'data' => [
                    'url' => '/protected.html'
                ]
            ];

            $debug .= "Payload erstellt<br>";

            // Benachrichtigungen in die Warteschlange stellen
            foreach ($subscriptions as $sub) {
                $subscription = Subscription::create([
                    'endpoint' => $sub['endpoint'],
                    'keys' => [
                        'p256dh' => $sub['p256dh'],
                        'auth' => $sub['auth'],
                    ],
                ]);

                $webPush->queueNotification($subscription, json_encode($payload));
                $debug .= "Benachrichtigung für Endpoint " . substr($sub['endpoint'], 0, 30) . "... in die Warteschlange gestellt<br>";
            }

            // Benachrichtigungen senden
            $successCount = 0;
            $failCount = 0;
            $results = [];

            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();

                if ($report->isSuccess()) {
                    $successCount++;
                    $results[] = ['endpoint' => $endpoint, 'status' => 'success'];
                    $debug .= "Benachrichtigung erfolgreich gesendet an " . substr($endpoint, 0, 30) . "...<br>";
                } else {
                    $failCount++;
                    $reason = $report->getReason();
                    $results[] = ['endpoint' => $endpoint, 'status' => 'failed', 'reason' => $reason];
                    $debug .= "Fehler beim Senden der Benachrichtigung an " . substr($endpoint, 0, 30) . "...: " . $reason . "<br>";

                    // Entferne fehlgeschlagene Abonnements
                    if (in_array($reason, ['410 Gone', '404 Not Found'])) {
                        $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = :endpoint");
                        $stmt->execute([':endpoint' => $endpoint]);
                        $debug .= "Fehlgeschlagenes Abonnement gelöscht<br>";
                    }
                }
            }

            $message = "Benachrichtigungen gesendet: $successCount erfolgreich, $failCount fehlgeschlagen";
        }
    } catch (Exception $e) {
        $error .= "Fehler beim Senden der Test-Benachrichtigung: " . $e->getMessage() . "<br>";
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Push-Benachrichtigungen Test (Einfach)</title>
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
        .debug-message {
            color: #0c5460;
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
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
        <h1>Push-Benachrichtigungen Test (Einfach)</h1>

        <?php if (!empty($message)): ?>
            <div class="success-message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!empty($debug)): ?>
            <div class="debug-message"><?php echo $debug; ?></div>
        <?php endif; ?>

        <p>Auf dieser Seite können Sie eine einfache Test-Benachrichtigung senden.</p>

        <form method="post">
            <div class="button-container">
                <button type="submit" name="send_test">Test-Benachrichtigung senden</button>
            </div>
        </form>

        <div class="button-container">
            <a href="protected.html" class="back-button" style="text-decoration: none; display: inline-block; padding: 10px 15px; color: white;">Zurück zur geschützten Seite</a>
        </div>
    </div>
</body>
</html>
