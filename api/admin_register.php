<?php
// admin_register.php
// Fehlerausgabe unterdrücken
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header('Content-Type: application/json');

require_once '../system/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vorname = trim($_POST['vorname'] ?? '');
    $nachname = trim($_POST['nachname'] ?? '');
    $benutzername = trim($_POST['benutzername'] ?? '');
    $mail = trim($_POST['mail'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $phone = trim($_POST['phone'] ?? null);
    $seriennummer = trim($_POST['seriennummer'] ?? '');

    // Validate required fields
    if (!$mail || !$password || !$vorname || !$nachname || !$benutzername || !$seriennummer) {
        echo json_encode(["status" => "error", "message" => "Alle Felder außer Telefonnummer sind erforderlich"]);
        exit;
    }

    // Validate serial number format (example: alphanumeric check)
    if (!preg_match('/^[A-Za-z0-9]+$/', $seriennummer)) {
        echo json_encode(["status" => "error", "message" => "Ungültiges Format der Seriennummer"]);
        exit;
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT user_id FROM benutzer WHERE mail = :mail");
    $stmt->execute([':mail' => $mail]);
    if ($stmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "Diese E-Mail-Adresse wird bereits verwendet"]);
        exit;
    }

    // Check if username already exists
    $stmt = $pdo->prepare("SELECT user_id FROM benutzer WHERE benutzername = :benutzername");
    $stmt->execute([':benutzername' => $benutzername]);
    if ($stmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "Dieser Benutzername wird bereits verwendet"]);
        exit;
    }

    // Check if the serial number exists in the database
    // This is optional - if you want to validate that the serial number is valid
    // If you don't have a separate table for serial numbers, you can skip this check
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Insert the new user with all fields including seriennummer and is_admin set to TRUE
        $insert = $pdo->prepare("INSERT INTO benutzer (vorname, nachname, benutzername, mail, passwort, phone, seriennummer, is_admin)
                                VALUES (:vorname, :nachname, :benutzername, :mail, :passwort, :phone, :seriennummer, TRUE)");
        $insert->execute([
            ':vorname' => $vorname,
            ':nachname' => $nachname,
            ':benutzername' => $benutzername,
            ':mail' => $mail,
            ':passwort' => $hashedPassword,
            ':phone' => $phone,
            ':seriennummer' => $seriennummer
        ]);

        echo json_encode(["status" => "success"]);
    } catch (PDOException $e) {
        // Detaillierte Fehlermeldung zurückgeben
        echo json_encode([
            "status" => "error",
            "message" => "Datenbankfehler: " . $e->getMessage(),
            "code" => $e->getCode()
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Ungültige Anfragemethode"]);
}
?>
