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
    // Abfrage, um den letzten Eintrag für diese Seriennummer zu finden
    // Wir nehmen an, dass die Tabelle "key_logs" heißt und die Spalte "seriennummer" enthält
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
    
    if ($lastLog) {
        // Wenn es einen letzten Eintrag gibt und timestamp_return NULL ist, ist der Schlüssel nicht verfügbar
        if ($lastLog['timestamp_return'] === null) {
            $isAvailable = false;
            $currentUser = [
                'benutzername' => $lastLog['benutzername'],
                'vorname' => $lastLog['vorname'],
                'nachname' => $lastLog['nachname']
            ];
            $takeTime = $lastLog['timestamp_take'];
        }
    }
    
    echo json_encode([
        "status" => "success",
        "key_status" => [
            "is_available" => $isAvailable,
            "current_user" => $currentUser,
            "take_time" => $takeTime,
            "box_id" => $lastLog ? $lastLog['box_id'] : null
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Datenbankfehler: " . $e->getMessage()
    ]);
}
