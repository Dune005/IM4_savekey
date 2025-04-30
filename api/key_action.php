<?php
// key_action.php - API zum Entnehmen oder Zurückgeben eines Schlüssels
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
    
    // Box-ID ermitteln (entweder aus dem letzten Log oder neu generieren)
    $boxId = $lastLog ? $lastLog['box_id'] : mt_rand(1000, 9999);
    
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
            ':box_id' => $boxId,
            ':benutzername' => $benutzername
        ]);
        
        echo json_encode([
            "status" => "success",
            "message" => "Schlüssel erfolgreich entnommen",
            "box_id" => $boxId
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
