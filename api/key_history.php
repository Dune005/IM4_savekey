<?php
// key_history.php - API zum Abrufen der Entnahme-/Rückgabehistorie eines Schlüssels
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
    // Abfrage, um die Historie für diese Seriennummer zu finden
    // Wir nehmen an, dass die Tabelle "key_logs" heißt und über die Benutzer-Tabelle mit der Seriennummer verknüpft ist
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
        JOIN 
            benutzer b ON kl.benutzername = b.benutzername
        WHERE 
            b.seriennummer = :seriennummer
        ORDER BY 
            kl.timestamp_take DESC
        LIMIT 20
    ");
    
    $stmt->execute([':seriennummer' => $seriennummer]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatieren der Daten für die Anzeige
    $formattedHistory = [];
    foreach ($history as $entry) {
        $formattedHistory[] = [
            'box_id' => $entry['box_id'],
            'take_time' => $entry['timestamp_take'],
            'return_time' => $entry['timestamp_return'],
            'benutzername' => $entry['benutzername'],
            'vorname' => $entry['vorname'],
            'nachname' => $entry['nachname'],
            'full_name' => $entry['vorname'] . ' ' . $entry['nachname']
        ];
    }
    
    echo json_encode([
        "status" => "success",
        "history" => $formattedHistory
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Datenbankfehler: " . $e->getMessage()
    ]);
}
