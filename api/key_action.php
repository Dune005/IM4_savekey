<?php
// key_action.php - API zum Entnehmen oder Zurückgeben eines Schlüssels
// WebPush-Klassen importieren - müssen auf oberster Ebene stehen
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Fehlerausgabe unterdrücken
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

// Fehlerausgabe unterdrücken, damit keine HTML-Fehler ausgegeben werden
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Eigenen Error-Handler registrieren, der Fehler als JSON zurückgibt
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    $error = [
        'status' => 'error',
        'message' => 'PHP-Fehler: ' . $errstr,
        'details' => [
            'file' => $errfile,
            'line' => $errline,
            'type' => $errno
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($error);
    exit;
}

// Error-Handler registrieren
set_error_handler('jsonErrorHandler', E_ALL);

// Exception-Handler registrieren
function jsonExceptionHandler($exception) {
    $error = [
        'status' => 'error',
        'message' => 'PHP-Exception: ' . $exception->getMessage(),
        'details' => [
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]
    ];

    header('Content-Type: application/json');
    echo json_encode($error);
    exit;
}

// Exception-Handler registrieren
set_exception_handler('jsonExceptionHandler');

try {
    // Absolute Pfade verwenden, um Probleme mit relativen Pfaden zu vermeiden
    $basePath = dirname(__FILE__) . '/../';

    require_once $basePath . 'system/config.php';

    // Push-Benachrichtigungen laden
    // Konfiguration für Push-Benachrichtigungen laden
    if (file_exists($basePath . 'system/push_notifications_config.php')) {
        require_once $basePath . 'system/push_notifications_config.php';
        error_log("Push-Benachrichtigungskonfiguration geladen");
    } else {
        error_log("Push-Benachrichtigungskonfiguration nicht gefunden: " . $basePath . 'system/push_notifications_config.php');
        // Dummy-Variablen für Push-Benachrichtigungen, damit der Code nicht bricht
        $PUSH_NOTIFICATIONS_ENABLED = [
            'key_removed' => false,
            'key_returned' => false
        ];
        $PUSH_NOTIFICATIONS_MESSAGES = [];
        $PUSH_NOTIFICATIONS_URL = '/protected.html';
    }

    // VAPID-Konfiguration laden
    if (file_exists($basePath . 'system/push_config.php')) {
        require_once $basePath . 'system/push_config.php';
        error_log("VAPID-Konfiguration geladen");
    } else {
        error_log("VAPID-Konfiguration nicht gefunden: " . $basePath . 'system/push_config.php');
    }

    // Composer Autoload laden
    if (file_exists($basePath . 'vendor/autoload.php')) {
        require_once $basePath . 'vendor/autoload.php';
        error_log("Composer Autoload geladen");
    } else {
        error_log("Composer Autoload nicht gefunden: " . $basePath . 'vendor/autoload.php');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Fehler beim Laden der erforderlichen Dateien: " . $e->getMessage()]);
    exit;
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
                $reason = $report->getReason();
                $results[] = ['endpoint' => $endpoint, 'status' => 'failed', 'reason' => $reason];

                // Entferne fehlgeschlagene Abonnements
                if (in_array($reason, ['410 Gone', '404 Not Found'])) {
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

// Überprüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Nicht autorisiert"]);
    exit;
}

// Überprüfen, ob der Benutzer ein Administrator ist
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Nur Administratoren können Schlüssel entnehmen oder zurückgeben"]);
    exit;
}

// Überprüfen, ob die Aktion angegeben wurde
if (!isset($_POST['action'])) {
    echo json_encode(["status" => "error", "message" => "Keine Aktion angegeben"]);
    exit;
}

$action = $_POST['action'];
$benutzername = $_SESSION['benutzername'];
$seriennummer = $_SESSION['seriennummer'] ?? '';

if (empty($seriennummer)) {
    echo json_encode(["status" => "error", "message" => "Keine Seriennummer gefunden"]);
    exit;
}

try {
    // Aktuellen Status des Schlüssels prüfen
    $stmt = $pdo->prepare("
        SELECT
            kl.box_id,
            kl.timestamp_take,
            kl.timestamp_return,
            kl.benutzername
        FROM
            key_logs kl
        JOIN
            benutzer b ON kl.benutzername = b.benutzername
        WHERE
            b.seriennummer = :seriennummer
        ORDER BY
            kl.timestamp_take DESC
        LIMIT 1
    ");

    $stmt->execute([':seriennummer' => $seriennummer]);
    $lastLog = $stmt->fetch(PDO::FETCH_ASSOC);

    // Box-ID aus dem letzten Log holen oder eine neue generieren
    $boxId = $lastLog ? $lastLog['box_id'] : mt_rand(1000, 9999);

    // Für eine neue Entnahme eine neue Box-ID generieren, um Primärschlüsselkonflikte zu vermeiden
    $newBoxId = mt_rand(1000, 9999);

    // Prüfen, ob der Schlüssel verfügbar ist
    $isAvailable = true;
    if ($lastLog && $lastLog['timestamp_return'] === null) {
        $isAvailable = false;
    }

    if ($action === 'take') {
        // Schlüssel kann nur entnommen werden, wenn er verfügbar ist
        if (!$isAvailable) {
            echo json_encode([
                "status" => "error",
                "message" => "Der Schlüssel ist bereits entnommen von " . $lastLog['benutzername']
            ]);
            exit;
        }

        // Neuen Eintrag für die Entnahme erstellen
        $stmt = $pdo->prepare("
            INSERT INTO key_logs
                (box_id, timestamp_take, benutzername)
            VALUES
                (:box_id, NOW(), :benutzername)
        ");

        $stmt->execute([
            ':box_id' => $newBoxId,
            ':benutzername' => $benutzername
        ]);

        // Push-Benachrichtigung senden, wenn aktiviert
        try {
            // Prüfen, ob die Konfigurationsvariablen existieren
            if (isset($PUSH_NOTIFICATIONS_ENABLED) && isset($PUSH_NOTIFICATIONS_MESSAGES) &&
                isset($PUSH_NOTIFICATIONS_URL) && $PUSH_NOTIFICATIONS_ENABLED['key_removed']) {

                // Benutzerinformationen für Platzhalter abrufen
                $stmt = $pdo->prepare("SELECT vorname, nachname FROM benutzer WHERE benutzername = :benutzername");
                $stmt->execute([':benutzername' => $benutzername]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Standardwerte für den Fall, dass keine Benutzerdaten gefunden wurden
                $vorname = isset($user['vorname']) ? $user['vorname'] : 'Unbekannt';
                $nachname = isset($user['nachname']) ? $user['nachname'] : 'Unbekannt';

                // Standardnachricht, falls die Konfiguration fehlt
                $title = 'Schlüssel entnommen';
                $body = 'Der Schlüssel wurde von ' . $benutzername . ' entnommen.';

                // Wenn die Konfiguration vorhanden ist, verwenden wir sie
                if (isset($PUSH_NOTIFICATIONS_MESSAGES['key_removed']['title'])) {
                    $title = $PUSH_NOTIFICATIONS_MESSAGES['key_removed']['title'];
                }

                if (isset($PUSH_NOTIFICATIONS_MESSAGES['key_removed']['body'])) {
                    $body = str_replace(
                        ['[VORNAME]', '[NACHNAME]', '[BENUTZERNAME]'],
                        [$vorname, $nachname, $benutzername],
                        $PUSH_NOTIFICATIONS_MESSAGES['key_removed']['body']
                    );
                }

                $payload = [
                    'title' => $title,
                    'body' => $body,
                    'data' => [
                        'url' => $PUSH_NOTIFICATIONS_URL,
                        'event_type' => 'key_removed',
                        'seriennummer' => $seriennummer,
                        'benutzername' => $benutzername
                    ]
                ];

                // Prüfen, ob die Funktion existiert
                if (function_exists('sendPushNotificationsForSeriennummer')) {
                    // Sende Benachrichtigung an alle Benutzer mit dieser Seriennummer
                    $result = sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload);
                    error_log("Push-Benachrichtigung gesendet: " . json_encode($result));
                } else {
                    error_log("Funktion sendPushNotificationsForSeriennummer existiert nicht");
                }
            } else {
                error_log("Push-Benachrichtigungen sind deaktiviert oder Konfiguration fehlt");
            }
        } catch (Exception $e) {
            // Fehler beim Senden der Push-Benachrichtigung protokollieren, aber den Prozess fortsetzen
            error_log("Fehler beim Senden der Push-Benachrichtigung: " . $e->getMessage());
        }

        echo json_encode([
            "status" => "success",
            "message" => "Schlüssel erfolgreich entnommen",
            "box_id" => $newBoxId
        ]);

    } elseif ($action === 'return') {
        // Schlüssel kann nur zurückgegeben werden, wenn er nicht verfügbar ist
        if ($isAvailable) {
            echo json_encode([
                "status" => "error",
                "message" => "Der Schlüssel ist bereits in der Box"
            ]);
            exit;
        }

        // Prüfen, ob der aktuelle Benutzer den Schlüssel entnommen hat
        if ($lastLog['benutzername'] !== $benutzername) {
            echo json_encode([
                "status" => "error",
                "message" => "Sie können nur Schlüssel zurückgeben, die Sie selbst entnommen haben"
            ]);
            exit;
        }

        // Eintrag für die Rückgabe aktualisieren
        $stmt = $pdo->prepare("
            UPDATE key_logs
            SET timestamp_return = NOW()
            WHERE box_id = :box_id
            AND benutzername = :benutzername
            AND timestamp_return IS NULL
        ");

        $stmt->execute([
            ':box_id' => $boxId,
            ':benutzername' => $benutzername
        ]);

        // Push-Benachrichtigung senden, wenn aktiviert
        try {
            // Prüfen, ob die Konfigurationsvariablen existieren
            if (isset($PUSH_NOTIFICATIONS_ENABLED) && isset($PUSH_NOTIFICATIONS_MESSAGES) &&
                isset($PUSH_NOTIFICATIONS_URL) && $PUSH_NOTIFICATIONS_ENABLED['key_returned']) {

                // Benutzerinformationen für Platzhalter abrufen
                $stmt = $pdo->prepare("SELECT vorname, nachname FROM benutzer WHERE benutzername = :benutzername");
                $stmt->execute([':benutzername' => $benutzername]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Standardwerte für den Fall, dass keine Benutzerdaten gefunden wurden
                $vorname = isset($user['vorname']) ? $user['vorname'] : 'Unbekannt';
                $nachname = isset($user['nachname']) ? $user['nachname'] : 'Unbekannt';

                // Standardnachricht, falls die Konfiguration fehlt
                $title = 'Schlüssel zurückgegeben';
                $body = 'Der Schlüssel wurde von ' . $benutzername . ' zurückgegeben.';

                // Wenn die Konfiguration vorhanden ist, verwenden wir sie
                if (isset($PUSH_NOTIFICATIONS_MESSAGES['key_returned']['title'])) {
                    $title = $PUSH_NOTIFICATIONS_MESSAGES['key_returned']['title'];
                }

                if (isset($PUSH_NOTIFICATIONS_MESSAGES['key_returned']['body'])) {
                    $body = str_replace(
                        ['[VORNAME]', '[NACHNAME]', '[BENUTZERNAME]'],
                        [$vorname, $nachname, $benutzername],
                        $PUSH_NOTIFICATIONS_MESSAGES['key_returned']['body']
                    );
                }

                $payload = [
                    'title' => $title,
                    'body' => $body,
                    'data' => [
                        'url' => $PUSH_NOTIFICATIONS_URL,
                        'event_type' => 'key_returned',
                        'seriennummer' => $seriennummer,
                        'benutzername' => $benutzername
                    ]
                ];

                // Prüfen, ob die Funktion existiert
                if (function_exists('sendPushNotificationsForSeriennummer')) {
                    // Sende Benachrichtigung an alle Benutzer mit dieser Seriennummer
                    $result = sendPushNotificationsForSeriennummer($pdo, $seriennummer, $payload);
                    error_log("Push-Benachrichtigung gesendet: " . json_encode($result));
                } else {
                    error_log("Funktion sendPushNotificationsForSeriennummer existiert nicht");
                }
            } else {
                error_log("Push-Benachrichtigungen sind deaktiviert oder Konfiguration fehlt");
            }
        } catch (Exception $e) {
            // Fehler beim Senden der Push-Benachrichtigung protokollieren, aber den Prozess fortsetzen
            error_log("Fehler beim Senden der Push-Benachrichtigung: " . $e->getMessage());
        }

        echo json_encode([
            "status" => "success",
            "message" => "Schlüssel erfolgreich zurückgegeben",
            "box_id" => $boxId
        ]);

    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Ungültige Aktion. Erlaubte Aktionen: take, return"
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Datenbankfehler: " . $e->getMessage()
    ]);
}
