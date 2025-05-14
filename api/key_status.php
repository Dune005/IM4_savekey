<?php
// key_status.php - API zum Abrufen des aktuellen Status eines Schlüssels
// Fehlerausgabe unterdrücken
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

require_once '../system/config.php';

// Überprüfen, ob der Benutzer angemeldet ist
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Nicht autorisiert"]);
    exit;
}

// Seriennummer aus der Session holen
$seriennummer = $_SESSION['seriennummer'] ?? '';

if (empty($seriennummer)) {
    echo json_encode(["status" => "error", "message" => "Keine Seriennummer gefunden"]);
    exit;
}

try {
    // 1. Zuerst prüfen, ob es eine ausstehende Schlüsselaktion gibt
    $pendingStmt = $pdo->prepare("
        SELECT
            id,
            action_type,
            timestamp,
            status
        FROM
            pending_key_actions
        WHERE
            seriennummer = :seriennummer
            AND action_type = 'remove'
            AND status = 'pending'
            AND timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY
            timestamp DESC
        LIMIT 1
    ");

    $pendingStmt->execute([':seriennummer' => $seriennummer]);
    $pendingAction = $pendingStmt->fetch(PDO::FETCH_ASSOC);

    // 1.1 Prüfen, ob es eine abgelaufene, nicht verifizierte Schlüsselentnahme gibt
    $expiredPendingStmt = $pdo->prepare("
        SELECT
            id,
            action_type,
            timestamp,
            status
        FROM
            pending_key_actions
        WHERE
            seriennummer = :seriennummer
            AND action_type = 'remove'
            AND status = 'pending'
            AND timestamp < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY
            timestamp DESC
        LIMIT 1
    ");

    $expiredPendingStmt->execute([':seriennummer' => $seriennummer]);
    $expiredPendingAction = $expiredPendingStmt->fetch(PDO::FETCH_ASSOC);

    // 2. Dann den letzten Eintrag in key_logs prüfen
    $stmt = $pdo->prepare("
        SELECT
            kl.box_id,
            kl.timestamp_take,
            kl.timestamp_return,
            kl.benutzername,
            b.vorname,
            b.nachname
        FROM
            key_logs kl
        LEFT JOIN
            benutzer b ON kl.benutzername = b.benutzername
        WHERE
            b.seriennummer = :seriennummer
        ORDER BY
            kl.timestamp_take DESC
        LIMIT 1
    ");

    $stmt->execute([':seriennummer' => $seriennummer]);
    $lastLog = $stmt->fetch(PDO::FETCH_ASSOC);

    // Bestimmen, ob der Schlüssel verfügbar ist
    $isAvailable = true;
    $currentUser = null;
    $takeTime = null;
    $pendingRemoval = false;
    $pendingTimestamp = null;
    $pendingExpiration = null;
    $unverifiedRemoval = false;
    $unverifiedTimestamp = null;

    // Wenn es eine ausstehende Entnahme gibt, ist der Schlüssel nicht verfügbar
    if ($pendingAction && $pendingAction['action_type'] === 'remove' && $pendingAction['status'] === 'pending') {
        $isAvailable = false;
        $pendingRemoval = true;
        $pendingTimestamp = $pendingAction['timestamp'];
        $pendingExpiration = date('Y-m-d H:i:s', strtotime($pendingAction['timestamp'] . ' + 5 minutes'));
    }
    // Wenn es eine abgelaufene, nicht verifizierte Entnahme gibt, ist der Schlüssel nicht verfügbar
    elseif ($expiredPendingAction && $expiredPendingAction['action_type'] === 'remove' && $expiredPendingAction['status'] === 'pending') {
        $isAvailable = false;
        $unverifiedRemoval = true;
        $unverifiedTimestamp = $expiredPendingAction['timestamp'];
    }
    // Wenn es einen letzten Eintrag gibt und timestamp_return NULL ist, ist der Schlüssel nicht verfügbar
    elseif ($lastLog && $lastLog['timestamp_return'] === null) {
        $isAvailable = false;
        $currentUser = [
            'benutzername' => $lastLog['benutzername'],
            'vorname' => $lastLog['vorname'],
            'nachname' => $lastLog['nachname']
        ];
        $takeTime = $lastLog['timestamp_take'];
    }

    echo json_encode([
        "status" => "success",
        "key_status" => [
            "is_available" => $isAvailable,
            "current_user" => $currentUser,
            "take_time" => $takeTime,
            "box_id" => $lastLog ? $lastLog['box_id'] : null,
            "pending_removal" => $pendingRemoval,
            "pending_timestamp" => $pendingTimestamp,
            "pending_expiration" => $pendingExpiration,
            "unverified_removal" => $unverifiedRemoval,
            "unverified_timestamp" => $unverifiedTimestamp
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Datenbankfehler: " . $e->getMessage()
    ]);
}
