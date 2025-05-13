<?php
// verify_token.php - Verifiziert einen Token und gibt die Seriennummer zurück
// Fehlerausgabe unterdrücken
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');

// Token-Hilfsfunktionen einbinden
require_once '../system/token_utils.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Token aus der Anfrage holen
    $token = $_GET['token'] ?? '';
    
    if (empty($token)) {
        echo json_encode([
            "status" => "error",
            "message" => "Kein Token angegeben"
        ]);
        exit;
    }
    
    // Token entschlüsseln
    $seriennummer = decodeSerialToken($token);
    
    if ($seriennummer === false) {
        echo json_encode([
            "status" => "error",
            "message" => "Ungültiger oder abgelaufener Token"
        ]);
        exit;
    }
    
    // Seriennummer zurückgeben
    echo json_encode([
        "status" => "success",
        "seriennummer" => $seriennummer
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Ungültige Anfragemethode"
    ]);
}
?>
