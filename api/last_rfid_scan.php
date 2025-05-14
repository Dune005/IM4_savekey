<?php
// last_rfid_scan.php - API zum Speichern und Abrufen der zuletzt gescannten RFID-UID
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

// Überprüfen, ob der Benutzer ein Administrator ist
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Nur Administratoren können auf diese Funktion zugreifen"]);
    exit;
}

// Aktion aus der Anfrage lesen
$action = $_POST['action'] ?? 'get';

switch ($action) {
    case 'get':
        getLastRfidScan($pdo);
        break;
    
    default:
        echo json_encode(["status" => "error", "message" => "Ungültige Aktion"]);
        break;
}

// Funktion zum Abrufen der zuletzt gescannten RFID-UID
function getLastRfidScan($pdo) {
    try {
        // Seriennummer des Benutzers abrufen
        $seriennummer = $_SESSION['seriennummer'] ?? '';
        
        if (empty($seriennummer)) {
            echo json_encode([
                "status" => "error",
                "message" => "Keine Seriennummer gefunden"
            ]);
            return;
        }
        
        // Zuletzt gescannte RFID-UID abrufen
        $stmt = $pdo->prepare("
            SELECT 
                rfid_uid, 
                timestamp,
                TIMESTAMPDIFF(SECOND, timestamp, NOW()) as seconds_ago
            FROM 
                last_rfid_scans 
            WHERE 
                seriennummer = :seriennummer
            ORDER BY 
                timestamp DESC 
            LIMIT 1
        ");
        
        $stmt->execute([':seriennummer' => $seriennummer]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Prüfen, ob der Scan innerhalb der letzten 10 Sekunden erfolgt ist
            if ($result['seconds_ago'] <= 10) {
                echo json_encode([
                    "status" => "success",
                    "has_recent_scan" => true,
                    "rfid_uid" => $result['rfid_uid'],
                    "timestamp" => $result['timestamp'],
                    "seconds_ago" => $result['seconds_ago']
                ]);
            } else {
                echo json_encode([
                    "status" => "success",
                    "has_recent_scan" => false,
                    "message" => "Kein aktueller RFID-Scan gefunden"
                ]);
            }
        } else {
            echo json_encode([
                "status" => "success",
                "has_recent_scan" => false,
                "message" => "Kein RFID-Scan gefunden"
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Datenbankfehler: " . $e->getMessage()
        ]);
    }
}
?>
