<?php
// keybox.php - API zum Abrufen des Inhalts einer Schlüsselbox basierend auf der Seriennummer
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

// Überprüfen, ob die Seriennummer in der Anfrage vorhanden ist
if (!isset($_GET['seriennummer'])) {
    echo json_encode(["status" => "error", "message" => "Seriennummer ist erforderlich"]);
    exit;
}

$seriennummer = trim($_GET['seriennummer']);

// Überprüfen, ob die Seriennummer mit der des angemeldeten Benutzers übereinstimmt
if ($seriennummer !== $_SESSION['seriennummer']) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Zugriff verweigert. Sie haben keinen Zugriff auf diese Schlüsselbox."]);
    exit;
}

try {
    // In einer echten Anwendung würden Sie hier eine Datenbankabfrage durchführen,
    // um den Inhalt der Schlüsselbox basierend auf der Seriennummer abzurufen
    
    // Beispielinhalt basierend auf der Seriennummer
    $content = [];
    
    if (strpos($seriennummer, 'A') === 0) {
        $content = [
            ["id" => 1, "name" => "Hauptschlüssel (A-Serie)", "status" => "verfügbar"],
            ["id" => 2, "name" => "Ersatzschlüssel für Haupteingang", "status" => "verfügbar"],
            ["id" => 3, "name" => "Schlüssel für Lagerraum", "status" => "ausgeliehen"]
        ];
    } elseif (strpos($seriennummer, 'B') === 0) {
        $content = [
            ["id" => 4, "name" => "Hauptschlüssel (B-Serie)", "status" => "verfügbar"],
            ["id" => 5, "name" => "Schlüssel für Büroräume", "status" => "verfügbar"],
            ["id" => 6, "name" => "Schlüssel für Konferenzraum", "status" => "verfügbar"]
        ];
    } else {
        $content = [
            ["id" => 7, "name" => "Standardschlüssel", "status" => "verfügbar"],
            ["id" => 8, "name" => "Zugangskarte", "status" => "verfügbar"]
        ];
    }
    
    echo json_encode([
        "status" => "success",
        "seriennummer" => $seriennummer,
        "content" => $content
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Fehler beim Abrufen des Schlüsselbox-Inhalts: " . $e->getMessage()
    ]);
}
