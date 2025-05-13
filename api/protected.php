<?php
// protected.php (API that returns JSON about the logged-in user)
session_start();

if (!isset($_SESSION['user_id'])) {
    // Instead of redirect, return a 401 JSON response
    http_response_code(401);
    header('Content-Type: application/json');
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// If they are logged in, return user data with all available fields
echo json_encode([
    "status" => "success",
    "user_id" => $_SESSION['user_id'],
    "mail" => $_SESSION['mail'],
    "vorname" => $_SESSION['vorname'] ?? '',
    "nachname" => $_SESSION['nachname'] ?? '',
    "benutzername" => $_SESSION['benutzername'] ?? '',
    "seriennummer" => $_SESSION['seriennummer'] ?? '',
    "is_admin" => $_SESSION['is_admin'] ?? false
]);
