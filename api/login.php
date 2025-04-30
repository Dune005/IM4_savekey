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
    $mail    = trim($_POST['mail'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!$mail || !$password) {
        echo json_encode(["status" => "error", "message" => "E-Mail und Passwort sind erforderlich"]);
        exit;
    }

    // Check user in DB - using the new field names
    $stmt = $pdo->prepare("SELECT user_id, passwort, vorname, nachname, benutzername FROM benutzer WHERE mail = :mail");
    $stmt->execute([':mail' => $mail]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password - using passwort field instead of password
    if ($user && password_verify($password, $user['passwort'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['mail']   = $mail;
        $_SESSION['vorname'] = $user['vorname'];
        $_SESSION['nachname'] = $user['nachname'];
        $_SESSION['benutzername'] = $user['benutzername'];

        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
