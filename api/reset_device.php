<?php
/**
 * Device Reset API
 * 
 * Sendet Reset-Befehle an die Schlüsselbox und prüft den Status der Ausführung.
 * Nur für Administratoren zugänglich.
 */

session_start();

// Fehlerausgabe aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON Header setzen
header('Content-Type: application/json');

// Datenbank-Konfiguration einbinden
require_once '../system/config.php';

// Prüfen, ob Benutzer eingeloggt ist
if (!isset($_SESSION['benutzername'])) {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Nicht angemeldet'
    ]);
    exit;
}

// Prüfen, ob Benutzer Administrator ist
$stmt = $pdo->prepare("SELECT is_admin, seriennummer FROM benutzer WHERE benutzername = :benutzername");
$stmt->execute([':benutzername' => $_SESSION['benutzername']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !$user['is_admin']) {
    http_response_code(403);
    echo json_encode([
        'status' => 'error',
        'message' => 'Zugriff verweigert. Nur Administratoren können die Box zurücksetzen.'
    ]);
    exit;
}

$seriennummer = $user['seriennummer'];

try {
    // Prüfen, ob Status abgefragt wird
    if (isset($_GET['action']) && $_GET['action'] === 'check_status') {
        // Letzten Reset-Befehl für diese Seriennummer prüfen
        $stmt = $pdo->prepare("
            SELECT executed, executed_at, created_at 
            FROM device_reset_commands 
            WHERE seriennummer = :seriennummer 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([':seriennummer' => $seriennummer]);
        $lastReset = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lastReset) {
            echo json_encode([
                'status' => 'success',
                'executed' => (bool)$lastReset['executed'],
                'executed_at' => $lastReset['executed_at'],
                'created_at' => $lastReset['created_at']
            ]);
        } else {
            echo json_encode([
                'status' => 'success',
                'executed' => false
            ]);
        }
        exit;
    }
    
    // POST-Request: Reset-Befehl erstellen
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Prüfen, ob die Tabelle existiert, sonst erstellen
        $checkTable = $pdo->query("SHOW TABLES LIKE 'device_reset_commands'");
        if ($checkTable->rowCount() == 0) {
            // Tabelle erstellen (falls noch nicht vorhanden)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS device_reset_commands (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    seriennummer VARCHAR(50) NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    executed TINYINT(1) DEFAULT 0,
                    executed_at TIMESTAMP NULL,
                    initiated_by VARCHAR(100) DEFAULT NULL,
                    reason VARCHAR(255) DEFAULT NULL,
                    INDEX idx_seriennummer (seriennummer),
                    INDEX idx_executed (executed)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }

        // Reset-Befehl einfügen
        $stmt = $pdo->prepare("
            INSERT INTO device_reset_commands 
            (seriennummer, initiated_by, reason) 
            VALUES 
            (:seriennummer, :initiated_by, :reason)
        ");

        $stmt->execute([
            ':seriennummer' => $seriennummer,
            ':initiated_by' => $_SESSION['benutzername'],
            ':reason' => 'Manual reset via admin dashboard'
        ]);

        // Erfolgreiche Antwort
        echo json_encode([
            'status' => 'success',
            'message' => 'Reset-Befehl wurde erfolgreich gesendet.',
            'reset_id' => $pdo->lastInsertId()
        ]);
    } else {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Methode nicht erlaubt'
        ]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Datenbankfehler: ' . $e->getMessage()
    ]);
}
?>
