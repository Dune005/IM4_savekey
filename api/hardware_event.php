<?php
// hardware_event.php - API zum Empfangen von Ereignissen vom Arduino
// Fehlerausgabe unterdrücken
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

require_once '../system/config.php';
require_once '../system/hardware_auth.php'; // Enthält den API-Schlüssel für die Hardware-Authentifizierung

// Überprüfen, ob die Anfrage einen gültigen API-Schlüssel enthält
$headers = getallheaders();
$apiKey = isset($headers['X-Api-Key']) ? $headers['X-Api-Key'] : '';

if ($apiKey !== HARDWARE_API_KEY) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Ungültiger API-Schlüssel"]);
    exit;
}

// Daten aus der Anfrage lesen
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "Keine gültigen Daten empfangen"]);
    exit;
}

// Überprüfen, ob alle erforderlichen Felder vorhanden sind
$requiredFields = ['event_type', 'seriennummer'];
foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        echo json_encode(["status" => "error", "message" => "Feld '$field' fehlt"]);
        exit;
    }
}

$eventType = $data['event_type'];
$seriennummer = $data['seriennummer'];

try {
    // Je nach Ereignistyp unterschiedliche Aktionen ausführen
    switch ($eventType) {
        case 'key_removed':
            // Schlüssel wurde physisch entfernt, aber noch nicht per RFID/NFC bestätigt
            // Wir speichern dieses Ereignis in einer temporären Tabelle
            handleKeyRemoved($pdo, $data);
            break;
            
        case 'key_returned':
            // Schlüssel wurde zurückgegeben
            handleKeyReturned($pdo, $data);
            break;
            
        case 'rfid_scan':
            // RFID/NFC-Scan wurde durchgeführt
            handleRfidScan($pdo, $data);
            break;
            
        default:
            echo json_encode(["status" => "error", "message" => "Unbekannter Ereignistyp: $eventType"]);
            exit;
    }
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Fehler bei der Verarbeitung des Ereignisses: " . $e->getMessage()
    ]);
}

// Funktion zum Verarbeiten des Ereignisses "Schlüssel entfernt"
function handleKeyRemoved($pdo, $data) {
    $seriennummer = $data['seriennummer'];
    
    // Speichern des Ereignisses in der temporären Tabelle
    $stmt = $pdo->prepare("
        INSERT INTO pending_key_actions 
            (seriennummer, action_type, timestamp, status) 
        VALUES 
            (:seriennummer, 'remove', NOW(), 'pending')
    ");
    
    $stmt->execute([':seriennummer' => $seriennummer]);
    
    echo json_encode([
        "status" => "success",
        "message" => "Schlüsselentnahme registriert. Warte auf RFID/NFC-Bestätigung.",
        "action_id" => $pdo->lastInsertId()
    ]);
}

// Funktion zum Verarbeiten des Ereignisses "Schlüssel zurückgegeben"
function handleKeyReturned($pdo, $data) {
    $seriennummer = $data['seriennummer'];
    
    // Suchen des letzten offenen Eintrags für diese Seriennummer
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
    
    if (!$lastLog) {
        echo json_encode([
            "status" => "error",
            "message" => "Kein offener Eintrag für diese Seriennummer gefunden"
        ]);
        return;
    }
    
    // Aktualisieren des Eintrags mit der Rückgabezeit
    $stmt = $pdo->prepare("
        UPDATE key_logs 
        SET timestamp_return = NOW() 
        WHERE box_id = :box_id 
        AND benutzername = :benutzername 
        AND timestamp_take = :timestamp_take
        AND timestamp_return IS NULL
    ");
    
    $stmt->execute([
        ':box_id' => $lastLog['box_id'],
        ':benutzername' => $lastLog['benutzername'],
        ':timestamp_take' => $lastLog['timestamp_take']
    ]);
    
    echo json_encode([
        "status" => "success",
        "message" => "Schlüssel erfolgreich zurückgegeben"
    ]);
}

// Funktion zum Verarbeiten des Ereignisses "RFID/NFC-Scan"
function handleRfidScan($pdo, $data) {
    if (!isset($data['rfid_uid'])) {
        echo json_encode(["status" => "error", "message" => "RFID/NFC UID fehlt"]);
        return;
    }
    
    $seriennummer = $data['seriennummer'];
    $rfidUid = $data['rfid_uid'];
    
    // Suchen des Benutzers anhand der RFID/NFC UID
    $stmt = $pdo->prepare("
        SELECT 
            user_id,
            benutzername,
            vorname,
            nachname
        FROM 
            benutzer
        WHERE 
            rfid_uid = :rfid_uid
            AND seriennummer = :seriennummer
    ");
    
    $stmt->execute([
        ':rfid_uid' => $rfidUid,
        ':seriennummer' => $seriennummer
    ]);
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode([
            "status" => "error",
            "message" => "Kein Benutzer mit dieser RFID/NFC UID gefunden"
        ]);
        return;
    }
    
    // Suchen des letzten ausstehenden Ereignisses für diese Seriennummer
    $stmt = $pdo->prepare("
        SELECT 
            id,
            action_type,
            timestamp
        FROM 
            pending_key_actions
        WHERE 
            seriennummer = :seriennummer
            AND status = 'pending'
        ORDER BY 
            timestamp DESC
        LIMIT 1
    ");
    
    $stmt->execute([':seriennummer' => $seriennummer]);
    $pendingAction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pendingAction) {
        echo json_encode([
            "status" => "error",
            "message" => "Keine ausstehende Aktion für diese Seriennummer gefunden"
        ]);
        return;
    }
    
    // Aktualisieren des Status der ausstehenden Aktion
    $stmt = $pdo->prepare("
        UPDATE pending_key_actions 
        SET status = 'completed', 
            completed_by = :benutzername,
            completed_at = NOW()
        WHERE id = :id
    ");
    
    $stmt->execute([
        ':id' => $pendingAction['id'],
        ':benutzername' => $user['benutzername']
    ]);
    
    // Wenn es sich um eine Entnahme handelt, einen neuen Eintrag in key_logs erstellen
    if ($pendingAction['action_type'] === 'remove') {
        // Box-ID ermitteln oder generieren
        $stmt = $pdo->prepare("
            SELECT 
                kl.box_id
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
        
        $boxId = $lastLog ? $lastLog['box_id'] : mt_rand(1000, 9999);
        
        // Neuen Eintrag für die Entnahme erstellen
        $stmt = $pdo->prepare("
            INSERT INTO key_logs 
                (box_id, timestamp_take, benutzername) 
            VALUES 
                (:box_id, NOW(), :benutzername)
        ");
        
        $stmt->execute([
            ':box_id' => $boxId,
            ':benutzername' => $user['benutzername']
        ]);
        
        echo json_encode([
            "status" => "success",
            "message" => "Schlüsselentnahme durch " . $user['vorname'] . " " . $user['nachname'] . " bestätigt",
            "user" => [
                "benutzername" => $user['benutzername'],
                "name" => $user['vorname'] . " " . $user['nachname']
            ]
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "message" => "RFID/NFC-Scan verarbeitet, aber keine passende Aktion gefunden"
        ]);
    }
}
