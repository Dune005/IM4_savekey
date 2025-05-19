<?php
// arduino_api.php - Endpoint for Arduino to send key and RFID data
header('Content-Type: application/json');

// Fehlerausgabe aktivieren für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Fehler in eine Datei protokollieren
ini_set('log_errors', 1);
ini_set('error_log', '../system/arduino_errors.log');

// Include database configuration
require_once '../system/config.php';

// Include hardware authentication
require_once '../system/hardware_auth.php';

// Include push notification configuration
require_once '../system/push_notifications_config.php';

// Include push configuration with VAPID keys
require_once '../system/push_config.php';

// Include WebPush library
require_once '../vendor/autoload.php';

// Import WebPush classes
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Verify API key for hardware authentication
function verifyApiKey() {
    $headers = getallheaders();
    $apiKey = $headers['X-Api-Key'] ?? '';

    if (empty($apiKey) || $apiKey !== HARDWARE_API_KEY) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized: Invalid API key"]);
        exit;
    }

    return true;
}

// Get JSON data from request
function getRequestData() {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid JSON data"]);
        exit;
    }

    return $data;
}

// Main processing logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify API key
    verifyApiKey();

    // Get request data
    $data = getRequestData();

    // Required fields
    $requiredFields = ['event_type', 'seriennummer'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Missing required field: $field"]);
            exit;
        }
    }

    $eventType = $data['event_type'];
    $seriennummer = $data['seriennummer'];

    try {
        // Process based on event type
        switch ($eventType) {
            case 'key_removed':
                // Key physically removed but not yet verified with RFID
                handleKeyRemoved($pdo, $data);
                break;

            case 'key_returned':
                // Key returned to the box
                handleKeyReturned($pdo, $data);
                break;

            case 'rfid_scan':
                // RFID/NFC scan performed
                handleRfidScan($pdo, $data);
                break;

            default:
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Unknown event type: $eventType"]);
                exit;
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Server error: " . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
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
            error_log("Keine Abonnements für Benutzer mit der Seriennummer $seriennummer gefunden");
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
                error_log("Push-Benachrichtigung erfolgreich gesendet an $endpoint");
            } else {
                $failCount++;
                $reason = $report->getReason();
                $results[] = ['endpoint' => $endpoint, 'status' => 'failed', 'reason' => $reason];
                error_log("Fehler beim Senden der Push-Benachrichtigung an $endpoint: $reason");

                // Entferne fehlgeschlagene Abonnements
                if (in_array($reason, ['410 Gone', '404 Not Found'])) {
                    $stmt = $pdo->prepare("DELETE FROM push_subscriptions WHERE endpoint = :endpoint");
                    $stmt->execute([':endpoint' => $endpoint]);
                    error_log("Fehlgeschlagenes Abonnement gelöscht: $endpoint");
                }
            }
        }

        return [
            'status' => 'success',
            'message' => "Benachrichtigungen gesendet: $successCount erfolgreich, $failCount fehlgeschlagen",
            'results' => $results
        ];
    } catch (Exception $e) {
        error_log("Fehler beim Senden der Push-Benachrichtigungen: " . $e->getMessage());
        return ['status' => 'error', 'message' => 'Fehler beim Senden der Benachrichtigungen: ' . $e->getMessage()];
    }
}

// Handle key removal event
function handleKeyRemoved($pdo, $data) {
    global $PUSH_NOTIFICATIONS_ENABLED, $PUSH_NOTIFICATIONS_MESSAGES, $PUSH_NOTIFICATIONS_URL;

    $seriennummer = $data['seriennummer'];
    $timestamp = date('Y-m-d H:i:s');

    // Create a pending key action record
    $stmt = $pdo->prepare("
        INSERT INTO pending_key_actions
        (seriennummer, action_type, timestamp, status)
        VALUES
        (:seriennummer, 'remove', :timestamp, 'pending')
    ");

    $stmt->execute([
        ':seriennummer' => $seriennummer,
        ':timestamp' => $timestamp
    ]);

    $actionId = $pdo->lastInsertId();

    // Set expiration time (5 minutes from now)
    $expirationTime = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Push-Benachrichtigung senden, wenn aktiviert
    if (isset($PUSH_NOTIFICATIONS_ENABLED) && $PUSH_NOTIFICATIONS_ENABLED['key_removed']) {
        error_log("Sende Push-Benachrichtigung für Schlüsselentnahme, Seriennummer: $seriennummer");

        $payload = [
            'title' => $PUSH_NOTIFICATIONS_MESSAGES['key_removed']['title'],
            'body' => $PUSH_NOTIFICATIONS_MESSAGES['key_removed']['body'],
            'data' => [
                'url' => $PUSH_NOTIFICATIONS_URL,
                'event_type' => 'key_removed',
                'seriennummer' => $seriennummer,
                'action_id' => $actionId
            ]
        ];

        // Sende Benachrichtigung an alle Benutzer mit dieser Seriennummer
        $result = sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload);
        error_log("Ergebnis der Push-Benachrichtigung: " . json_encode($result));
    } else {
        error_log("Push-Benachrichtigungen sind deaktiviert oder Konfiguration fehlt");
    }

    echo json_encode([
        "status" => "success",
        "message" => "Key removal recorded. Waiting for RFID verification.",
        "expiration_time" => $expirationTime
    ]);
}

// Handle key return event
function handleKeyReturned($pdo, $data) {
    global $PUSH_NOTIFICATIONS_ENABLED, $PUSH_NOTIFICATIONS_MESSAGES, $PUSH_NOTIFICATIONS_URL;

    $seriennummer = $data['seriennummer'];
    $timestamp = date('Y-m-d H:i:s');

    // Find the last open entry for this serial number
    $stmt = $pdo->prepare("
        SELECT
            kl.box_id,
            kl.timestamp_take,
            kl.benutzername
        FROM
            key_logs kl
        JOIN
            benutzer b ON kl.benutzername = b.benutzername
        WHERE
            b.seriennummer = :seriennummer
            AND kl.timestamp_return IS NULL
        ORDER BY
            kl.timestamp_take DESC
        LIMIT 1
    ");

    $stmt->execute([':seriennummer' => $seriennummer]);
    $lastLog = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check for pending key removal actions that haven't been verified
    $pendingStmt = $pdo->prepare("
        SELECT id, timestamp
        FROM pending_key_actions
        WHERE seriennummer = :seriennummer
        AND action_type = 'remove'
        AND status = 'pending'
        ORDER BY timestamp DESC
        LIMIT 1
    ");

    $pendingStmt->execute([':seriennummer' => $seriennummer]);
    $pendingAction = $pendingStmt->fetch(PDO::FETCH_ASSOC);

    // If there's a pending action, mark it as completed by 'unknown_user'
    if ($pendingAction) {
        $updatePendingStmt = $pdo->prepare("
            UPDATE pending_key_actions
            SET status = 'completed',
                completed_by = 'unknown_user',
                completed_at = :completed_at
            WHERE id = :id
        ");

        $updatePendingStmt->execute([
            ':completed_at' => $timestamp,
            ':id' => $pendingAction['id']
        ]);
    }

    // If there's an open key_logs entry, update it
    if ($lastLog) {
        // Update the record with return timestamp
        $updateStmt = $pdo->prepare("
            UPDATE key_logs
            SET timestamp_return = :timestamp_return
            WHERE box_id = :box_id AND timestamp_take = :timestamp_take
        ");

        $updateStmt->execute([
            ':timestamp_return' => $timestamp,
            ':box_id' => $lastLog['box_id'],
            ':timestamp_take' => $lastLog['timestamp_take']
        ]);

        // Push-Benachrichtigung senden, wenn aktiviert
        if (isset($PUSH_NOTIFICATIONS_ENABLED) && $PUSH_NOTIFICATIONS_ENABLED['key_returned']) {
            error_log("Sende Push-Benachrichtigung für Schlüsselrückgabe, Seriennummer: $seriennummer");

            $payload = [
                'title' => $PUSH_NOTIFICATIONS_MESSAGES['key_returned']['title'],
                'body' => $PUSH_NOTIFICATIONS_MESSAGES['key_returned']['body'],
                'data' => [
                    'url' => $PUSH_NOTIFICATIONS_URL,
                    'event_type' => 'key_returned',
                    'seriennummer' => $seriennummer,
                    'benutzername' => $lastLog['benutzername']
                ]
            ];

            // Sende Benachrichtigung an alle Benutzer mit dieser Seriennummer
            $result = sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload);
            error_log("Ergebnis der Push-Benachrichtigung: " . json_encode($result));
        } else {
            error_log("Push-Benachrichtigungen sind deaktiviert oder Konfiguration fehlt");
        }

        echo json_encode([
            "status" => "success",
            "message" => "Key return recorded successfully"
        ]);
    } else if ($pendingAction) {
        // If there's no open key_logs entry but there was a pending action,
        // we've successfully closed the pending action

        // Push-Benachrichtigung senden, wenn aktiviert
        if (isset($PUSH_NOTIFICATIONS_ENABLED) && $PUSH_NOTIFICATIONS_ENABLED['key_returned']) {
            error_log("Sende Push-Benachrichtigung für nicht verifizierte Schlüsselrückgabe, Seriennummer: $seriennummer");

            $payload = [
                'title' => $PUSH_NOTIFICATIONS_MESSAGES['key_returned']['title'],
                'body' => $PUSH_NOTIFICATIONS_MESSAGES['key_returned']['body'] . ' (Nicht verifizierte Entnahme)',
                'data' => [
                    'url' => $PUSH_NOTIFICATIONS_URL,
                    'event_type' => 'key_returned_unverified',
                    'seriennummer' => $seriennummer
                ]
            ];

            // Sende Benachrichtigung an alle Benutzer mit dieser Seriennummer
            $result = sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload);
            error_log("Ergebnis der Push-Benachrichtigung: " . json_encode($result));
        } else {
            error_log("Push-Benachrichtigungen sind deaktiviert oder Konfiguration fehlt");
        }

        echo json_encode([
            "status" => "success",
            "message" => "Unverified key return recorded successfully"
        ]);
    } else {
        // No open entry and no pending action
        echo json_encode([
            "status" => "error",
            "message" => "No open entry found for this serial number"
        ]);
    }
}

// Handle RFID scan event
function handleRfidScan($pdo, $data) {
    global $PUSH_NOTIFICATIONS_ENABLED, $PUSH_NOTIFICATIONS_MESSAGES, $PUSH_NOTIFICATIONS_URL;

    $seriennummer = $data['seriennummer'];
    $rfidUid = $data['rfid_uid'] ?? '';
    $timestamp = date('Y-m-d H:i:s');

    if (empty($rfidUid)) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing RFID UID"
        ]);
        return;
    }

    // Speichern der zuletzt gescannten RFID-UID für die Live-Anzeige
    try {
        // Prüfen, ob die Tabelle last_rfid_scans existiert
        $tableCheckStmt = $pdo->prepare("
            SELECT 1 FROM information_schema.tables
            WHERE table_schema = DATABASE()
            AND table_name = 'last_rfid_scans'
        ");
        $tableCheckStmt->execute();

        if ($tableCheckStmt->fetch()) {
            // Tabelle existiert, RFID-UID speichern
            $saveRfidStmt = $pdo->prepare("
                INSERT INTO last_rfid_scans
                (seriennummer, rfid_uid, timestamp)
                VALUES
                (:seriennummer, :rfid_uid, :timestamp)
            ");

            $saveRfidStmt->execute([
                ':seriennummer' => $seriennummer,
                ':rfid_uid' => $rfidUid,
                ':timestamp' => $timestamp
            ]);
        }
    } catch (Exception $e) {
        // Fehler beim Speichern der RFID-UID ignorieren, da dies nicht kritisch ist
        // und den Hauptprozess nicht beeinträchtigen soll
        error_log("Fehler beim Speichern der RFID-UID: " . $e->getMessage());
    }

    // Find user with this RFID UID
    $stmt = $pdo->prepare("
        SELECT benutzername, user_id, vorname, nachname
        FROM benutzer
        WHERE rfid_uid = :rfid_uid
    ");

    $stmt->execute([':rfid_uid' => $rfidUid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode([
            "status" => "error",
            "message" => "No user found with this RFID UID"
        ]);
        return;
    }

    // Find pending key action for this serial number
    $stmt = $pdo->prepare("
        SELECT id, timestamp
        FROM pending_key_actions
        WHERE seriennummer = :seriennummer
        AND action_type = 'remove'
        AND status = 'pending'
        AND timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY timestamp DESC
        LIMIT 1
    ");

    $stmt->execute([':seriennummer' => $seriennummer]);
    $pendingAction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pendingAction) {
        // Wenn keine ausstehende Aktion gefunden wurde, aber trotzdem ein RFID-Scan erfolgt ist
        if (isset($PUSH_NOTIFICATIONS_ENABLED) && $PUSH_NOTIFICATIONS_ENABLED['rfid_scan']) {
            error_log("Sende Push-Benachrichtigung für RFID-Scan ohne ausstehende Aktion, Seriennummer: $seriennummer");

            $payload = [
                'title' => $PUSH_NOTIFICATIONS_MESSAGES['rfid_scan']['title'],
                'body' => str_replace(
                    ['[VORNAME]', '[NACHNAME]', '[BENUTZERNAME]'],
                    [$user['vorname'], $user['nachname'], $user['benutzername']],
                    $PUSH_NOTIFICATIONS_MESSAGES['rfid_scan']['body']
                ),
                'data' => [
                    'url' => $PUSH_NOTIFICATIONS_URL,
                    'event_type' => 'rfid_scan',
                    'seriennummer' => $seriennummer,
                    'benutzername' => $user['benutzername']
                ]
            ];

            // Sende Benachrichtigung an alle Benutzer mit dieser Seriennummer
            $result = sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload);
            error_log("Ergebnis der Push-Benachrichtigung: " . json_encode($result));
        }

        echo json_encode([
            "status" => "error",
            "message" => "No pending key removal found or verification time expired"
        ]);
        return;
    }

    // Update pending action to completed
    $updateStmt = $pdo->prepare("
        UPDATE pending_key_actions
        SET status = 'completed',
            completed_by = :completed_by,
            completed_at = :completed_at
        WHERE id = :id
    ");

    $updateStmt->execute([
        ':completed_by' => $user['benutzername'],
        ':completed_at' => $timestamp,
        ':id' => $pendingAction['id']
    ]);

    // Create key log entry
    $boxId = mt_rand(1000, 9999); // Generate a random box ID

    $logStmt = $pdo->prepare("
        INSERT INTO key_logs
        (box_id, timestamp_take, benutzername)
        VALUES
        (:box_id, :timestamp_take, :benutzername)
    ");

    $logStmt->execute([
        ':box_id' => $boxId,
        ':timestamp_take' => $timestamp,
        ':benutzername' => $user['benutzername']
    ]);

    // Push-Benachrichtigung für verifizierte Schlüsselentnahme senden, wenn aktiviert
    if (isset($PUSH_NOTIFICATIONS_ENABLED) && $PUSH_NOTIFICATIONS_ENABLED['key_removed_verified']) {
        error_log("Sende Push-Benachrichtigung für verifizierte Schlüsselentnahme, Seriennummer: $seriennummer");

        $payload = [
            'title' => $PUSH_NOTIFICATIONS_MESSAGES['key_removed_verified']['title'],
            'body' => str_replace(
                ['[VORNAME]', '[NACHNAME]', '[BENUTZERNAME]'],
                [$user['vorname'], $user['nachname'], $user['benutzername']],
                $PUSH_NOTIFICATIONS_MESSAGES['key_removed_verified']['body']
            ),
            'data' => [
                'url' => $PUSH_NOTIFICATIONS_URL,
                'event_type' => 'key_removed_verified',
                'seriennummer' => $seriennummer,
                'benutzername' => $user['benutzername']
            ]
        ];

        // Sende Benachrichtigung an alle Benutzer mit dieser Seriennummer
        $result = sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload);
        error_log("Ergebnis der Push-Benachrichtigung: " . json_encode($result));
    } else {
        error_log("Push-Benachrichtigungen sind deaktiviert oder Konfiguration fehlt");
    }

    echo json_encode([
        "status" => "success",
        "message" => "RFID verification successful. Key removal logged.",
        "user" => $user['benutzername']
    ]);
}
?>
