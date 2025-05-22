<?php
// push_notification.php - API für Push-Benachrichtigungen

header('Content-Type: application/json');

// Pfade dynamisch anpassen, je nachdem, von wo die Datei eingebunden wird
$basePath = '';

// Prüfen, ob die Datei direkt aufgerufen wird oder eingebunden wird
$scriptPath = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
$includedPath = __FILE__;

// Wenn die Datei direkt aufgerufen wird (SCRIPT_FILENAME enthält push_notification.php)
if (strpos($scriptPath, 'push_notification.php') !== false) {
    $basePath = '../';
}
// Wenn die Datei von einer Datei im api-Verzeichnis eingebunden wird
else if (strpos($scriptPath, '/api/') !== false) {
    $basePath = '../';
}
// Wenn die Datei von einer Datei im Hauptverzeichnis eingebunden wird
else {
    $basePath = '';
}

// Prüfen, ob die Konfigurationsdateien bereits eingebunden wurden
if (!isset($pdo)) {
    // Versuche, die Konfigurationsdateien zu laden
    $configFile = $basePath . 'system/config.php';
    $pushConfigFile = $basePath . 'system/push_config.php';
    $autoloadFile = $basePath . 'vendor/autoload.php';

    if (file_exists($configFile)) {
        require_once $configFile;
    } else {
        // Versuche es mit dem alternativen Pfad
        $altConfigFile = dirname(__FILE__) . '/../system/config.php';
        if (file_exists($altConfigFile)) {
            require_once $altConfigFile;
        } else {
            throw new Exception("Konfigurationsdatei nicht gefunden: $configFile oder $altConfigFile");
        }
    }

    if (file_exists($pushConfigFile)) {
        require_once $pushConfigFile;
    } else {
        // Versuche es mit dem alternativen Pfad
        $altPushConfigFile = dirname(__FILE__) . '/../system/push_config.php';
        if (file_exists($altPushConfigFile)) {
            require_once $altPushConfigFile;
        } else {
            throw new Exception("Push-Konfigurationsdatei nicht gefunden: $pushConfigFile oder $altPushConfigFile");
        }
    }

    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
    } else {
        // Versuche es mit dem alternativen Pfad
        $altAutoloadFile = dirname(__FILE__) . '/../vendor/autoload.php';
        if (file_exists($altAutoloadFile)) {
            require_once $altAutoloadFile;
        } else {
            throw new Exception("Autoload-Datei nicht gefunden: $autoloadFile oder $altAutoloadFile");
        }
    }
}

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Überprüfen, ob der Benutzer angemeldet ist
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Abonnement-Anfrage verarbeiten
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            throw new Exception('Ungültige JSON-Daten empfangen');
        }

        if (!isset($data['subscription'])) {
            throw new Exception('Subscription-Daten fehlen');
        }

        $subscription = $data['subscription'];

        if (!isset($subscription['endpoint']) || !isset($subscription['keys']) ||
            !isset($subscription['keys']['p256dh']) || !isset($subscription['keys']['auth'])) {
            throw new Exception('Unvollständige Subscription-Daten');
        }

        $endpoint = $subscription['endpoint'];
        $p256dh = $subscription['keys']['p256dh'];
        $auth = $subscription['keys']['auth'];

        // Prüfen, ob das Abonnement bereits existiert
        $stmt = $pdo->prepare("SELECT id FROM push_subscriptions WHERE endpoint = :endpoint");
        $stmt->execute([':endpoint' => $endpoint]);
        $existingSubscription = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingSubscription) {
            // Abonnement aktualisieren
            $stmt = $pdo->prepare("
                UPDATE push_subscriptions
                SET user_id = :user_id, p256dh = :p256dh, auth = :auth
                WHERE endpoint = :endpoint
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':p256dh' => $p256dh,
                ':auth' => $auth,
                ':endpoint' => $endpoint
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Abonnement erfolgreich aktualisiert',
                'user_id' => $userId
            ]);
        } else {
            // Neues Abonnement erstellen
            $stmt = $pdo->prepare("
                INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth)
                VALUES (:user_id, :endpoint, :p256dh, :auth)
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':endpoint' => $endpoint,
                ':p256dh' => $p256dh,
                ':auth' => $auth
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Abonnement erfolgreich gespeichert',
                'user_id' => $userId
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Datenbankfehler: ' . $e->getMessage()]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

// Test-Benachrichtigung senden
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test']) && $_GET['test'] === 'true') {
    $payload = [
        'title' => 'SaveKey Test-Benachrichtigung',
        'body' => 'Dies ist eine Test-Benachrichtigung vom SaveKey-System!',
        'data' => [
            'url' => '/'
        ]
    ];

    sendPushNotifications($pdo, $payload);
    exit;
}

// Funktion zum Senden von Push-Benachrichtigungen an alle Abonnenten
function sendPushNotifications($pdo, $payload) {
    try {
        $stmt = $pdo->query("SELECT * FROM push_subscriptions");
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($subscriptions)) {
            // Wenn die Funktion direkt über die API aufgerufen wird, geben wir JSON aus
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test'])) {
                echo json_encode(['status' => 'info', 'message' => 'Keine Abonnements gefunden']);
                return;
            }
            // Ansonsten geben wir ein Array zurück
            return ['status' => 'info', 'message' => 'Keine Abonnements gefunden'];
        }

        $auth = [
            'VAPID' => [
                'subject' => VAPID_SUBJECT,
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];

        $webPush = new WebPush($auth);
        $successCount = 0;
        $failCount = 0;

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh'],
                    'auth' => $sub['auth'],
                ],
            ]);

            $webPush->queueNotification($subscription, json_encode($payload));
        }

        $results = [];
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSuccess()) {
                $successCount++;
                $results[] = ['endpoint' => $endpoint, 'status' => 'success'];
            } else {
                $failCount++;
                $results[] = ['endpoint' => $endpoint, 'status' => 'failed', 'reason' => $report->getReason()];

                // Entferne fehlgeschlagene Abonnements
                if (in_array($report->getReason(), ['410 Gone', '404 Not Found'])) {
                    $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = :endpoint");
                    $stmt->execute([':endpoint' => $endpoint]);
                }
            }
        }

        $result = [
            'status' => 'success',
            'message' => "Benachrichtigungen gesendet: $successCount erfolgreich, $failCount fehlgeschlagen",
            'results' => $results
        ];

        // Wenn die Funktion direkt über die API aufgerufen wird, geben wir JSON aus
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test'])) {
            echo json_encode($result);
            return;
        }

        // Ansonsten geben wir ein Array zurück
        return $result;
    } catch (Exception $e) {
        $error = ['status' => 'error', 'message' => 'Fehler beim Senden der Benachrichtigungen: ' . $e->getMessage()];

        // Wenn die Funktion direkt über die API aufgerufen wird, geben wir JSON aus
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['test'])) {
            http_response_code(500);
            echo json_encode($error);
            return;
        }

        // Ansonsten geben wir ein Array zurück
        return $error;
    }
}

// Funktion zum Senden einer Push-Benachrichtigung an einen bestimmten Benutzer
function sendPushNotificationToUser($pdo, $userId, $payload) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM push_subscriptions WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($subscriptions)) {
            return ['status' => 'info', 'message' => 'Keine Abonnements für diesen Benutzer gefunden'];
        }

        $auth = [
            'VAPID' => [
                'subject' => VAPID_SUBJECT,
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];

        $webPush = new WebPush($auth);
        $successCount = 0;
        $failCount = 0;

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh'],
                    'auth' => $sub['auth'],
                ],
            ]);

            $webPush->queueNotification($subscription, json_encode($payload));
        }

        $results = [];
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSuccess()) {
                $successCount++;
                $results[] = ['endpoint' => $endpoint, 'status' => 'success'];
            } else {
                $failCount++;
                $results[] = ['endpoint' => $endpoint, 'status' => 'failed', 'reason' => $report->getReason()];

                // Entferne fehlgeschlagene Abonnements
                if (in_array($report->getReason(), ['410 Gone', '404 Not Found'])) {
                    $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = :endpoint");
                    $stmt->execute([':endpoint' => $endpoint]);
                }
            }
        }

        return [
            'status' => 'success',
            'message' => "Benachrichtigungen gesendet: $successCount erfolgreich, $failCount fehlgeschlagen",
            'results' => $results
        ];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Fehler beim Senden der Benachrichtigungen: ' . $e->getMessage()];
    }
}

// Funktion zum Senden von Push-Benachrichtigungen an alle Benutzer mit einer bestimmten Seriennummer
function sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload) {
    try {
        // Alle Benutzer mit dieser Seriennummer finden
        $stmt = $pdo->prepare("
            SELECT ps.*
            FROM push_subscriptions ps
            JOIN benutzer b ON ps.user_id = b.user_id
            WHERE b.seriennummer = :seriennummer
        ");
        $stmt->execute([':seriennummer' => $seriennummer]);
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($subscriptions)) {
            return ['status' => 'info', 'message' => 'Keine Abonnements für Benutzer mit dieser Seriennummer gefunden'];
        }

        $auth = [
            'VAPID' => [
                'subject' => VAPID_SUBJECT,
                'publicKey' => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ];

        $webPush = new WebPush($auth);
        $successCount = 0;
        $failCount = 0;

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh'],
                    'auth' => $sub['auth'],
                ],
            ]);

            $webPush->queueNotification($subscription, json_encode($payload));
        }

        $results = [];
        foreach ($webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if ($report->isSuccess()) {
                $successCount++;
                $results[] = ['endpoint' => $endpoint, 'status' => 'success'];
            } else {
                $failCount++;
                $results[] = ['endpoint' => $endpoint, 'status' => 'failed', 'reason' => $report->getReason()];

                // Entferne fehlgeschlagene Abonnements
                if (in_array($report->getReason(), ['410 Gone', '404 Not Found'])) {
                    $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = :endpoint");
                    $stmt->execute([':endpoint' => $endpoint]);
                }
            }
        }

        return [
            'status' => 'success',
            'message' => "Benachrichtigungen gesendet: $successCount erfolgreich, $failCount fehlgeschlagen",
            'results' => $results
        ];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Fehler beim Senden der Benachrichtigungen: ' . $e->getMessage()];
    }
}
?>
