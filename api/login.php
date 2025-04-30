<?php
// login.php
// Fehlerausgabe unterdrücken
error_reporting(0);
ini_set('display_errors', 0);

ini_set('session.cookie_httponly', 1);
// ini_set('session.cookie_secure', 1); // if using HTTPS
session_start();
header('Content-Type: application/json');

require_once '../system/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $benutzername = trim($_POST['benutzername'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$benutzername || !$password) {
        echo json_encode(["status" => "error", "message" => "Benutzername und Passwort sind erforderlich"]);
        exit;
    }

    // Check user in DB - using the new field names
    $stmt = $pdo->prepare("SELECT user_id, passwort, vorname, nachname, benutzername, mail, seriennummer FROM benutzer WHERE benutzername = :benutzername");
    $stmt->execute([':benutzername' => $benutzername]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password - using passwort field instead of password
    if ($user && password_verify($password, $user['passwort'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['mail'] = $user['mail'];
        $_SESSION['vorname'] = $user['vorname'];
        $_SESSION['nachname'] = $user['nachname'];
        $_SESSION['benutzername'] = $user['benutzername'];
        $_SESSION['seriennummer'] = $user['seriennummer'];

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Ungültiger Benutzername oder Passwort"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Ungültige Anfragemethode"]);
}
