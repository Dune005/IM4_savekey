<?php
// rfid_management.php - API zum Verwalten von RFID/NFC-Zuordnungen
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

// Überprüfen, ob der Benutzer ein Administrator ist für Zuweisungs- und Entfernungsaktionen
$action = $_POST['action'] ?? '';
if (($action === 'assign_rfid' || $action === 'remove_rfid') &&
    (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true)) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Nur Administratoren können RFID/NFC-UIDs zuweisen oder entfernen"]);
    exit;
}

// Aktion wurde bereits oben aus der Anfrage gelesen

switch ($action) {
    case 'assign_rfid':
        assignRfid($pdo);
        break;

    case 'remove_rfid':
        removeRfid($pdo);
        break;

    case 'get_rfid':
        getRfid($pdo);
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Ungültige Aktion"]);
        break;
}

// Funktion zum Zuweisen einer RFID/NFC-UID zu einem Benutzer
function assignRfid($pdo) {
    $rfidUid = trim($_POST['rfid_uid'] ?? '');
    $benutzername = $_SESSION['benutzername'];

    if (empty($rfidUid)) {
        echo json_encode(["status" => "error", "message" => "RFID/NFC UID ist erforderlich"]);
        return;
    }

    try {
        // Überprüfen, ob die RFID/NFC-UID bereits einem anderen Benutzer zugewiesen ist
        $stmt = $pdo->prepare("
            SELECT benutzername FROM benutzer WHERE rfid_uid = :rfid_uid AND benutzername != :benutzername
        ");

        $stmt->execute([
            ':rfid_uid' => $rfidUid,
            ':benutzername' => $benutzername
        ]);

        if ($stmt->fetch()) {
            echo json_encode([
                "status" => "error",
                "message" => "Diese RFID/NFC-UID ist bereits einem anderen Benutzer zugewiesen"
            ]);
            return;
        }

        // RFID/NFC-UID dem Benutzer zuweisen
        $stmt = $pdo->prepare("
            UPDATE benutzer SET rfid_uid = :rfid_uid WHERE benutzername = :benutzername
        ");

        $stmt->execute([
            ':rfid_uid' => $rfidUid,
            ':benutzername' => $benutzername
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "RFID/NFC-UID erfolgreich zugewiesen"
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Datenbankfehler: " . $e->getMessage()
        ]);
    }
}

// Funktion zum Entfernen einer RFID/NFC-UID von einem Benutzer
function removeRfid($pdo) {
    $benutzername = $_SESSION['benutzername'];

    try {
        // RFID/NFC-UID vom Benutzer entfernen
        $stmt = $pdo->prepare("
            UPDATE benutzer SET rfid_uid = NULL WHERE benutzername = :benutzername
        ");

        $stmt->execute([':benutzername' => $benutzername]);

        echo json_encode([
            "status" => "success",
            "message" => "RFID/NFC-UID erfolgreich entfernt"
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Datenbankfehler: " . $e->getMessage()
        ]);
    }
}

// Funktion zum Abrufen der RFID/NFC-UID eines Benutzers
function getRfid($pdo) {
    $benutzername = $_SESSION['benutzername'];

    try {
        // RFID/NFC-UID des Benutzers abrufen
        $stmt = $pdo->prepare("
            SELECT rfid_uid FROM benutzer WHERE benutzername = :benutzername
        ");

        $stmt->execute([':benutzername' => $benutzername]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "status" => "success",
            "rfid_uid" => $result['rfid_uid'] ?? null
        ]);

    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Datenbankfehler: " . $e->getMessage()
        ]);
    }
}
