<?php
require 'vendor/autoload.php'; // If you're using Composer

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Replace with your actual VAPID keys
$vapidPublicKey = 'YOUR_VAPID_PUBLIC_KEY_HERE';
$vapidPrivateKey = 'YOUR_VAPID_PRIVATE_KEY_HERE';

// Database connection (replace with your credentials)
$servername = "localhost";
$username = "your_db_user";
$password = "your_db_password";
$dbname = "your_db_name";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection error']);
    exit;
}

// Handle subscription request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscription'])) {
    $subscriptionData = json_decode(file_get_contents('php://input'), true);

    if (isset($subscriptionData['subscription'])) {
        $endpoint = $subscriptionData['subscription']['endpoint'];
        $p256dh = $subscriptionData['subscription']['keys']['p256dh'];
        $auth = $subscriptionData['subscription']['keys']['auth'];

        try {
            $stmt = $conn->prepare("INSERT INTO subscriptions (endpoint, p256dh, auth) VALUES (:endpoint, :p256dh, :auth)");
            $stmt->bindParam(':endpoint', $endpoint);
            $stmt->bindParam(':p256dh', $p256dh);
            $stmt->bindParam(':auth', $auth);
            $stmt->execute();
            http_response_code(201);
            echo json_encode(['success' => 'Subscription saved']);
        } catch(PDOException $e) {
            // Consider checking for duplicate entries
            error_log("Error saving subscription: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save subscription']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid subscription data']);
    }
    exit;
}

// Function to send a push notification to a specific subscription
function sendPushNotification($subscription, $payload, $publicKey, $privateKey) {
    $auth = [
        'VAPID' => [
            'subject' => 'mailto:your-email@example.com', // Replace with your email
            'publicKey' => $publicKey,
            'privateKey' => $privateKey,
        ],
    ];

    $webPush = new WebPush($auth);
    $webPush->sendNotification(
        $subscription,
        json_encode($payload)
    );

    foreach ($webPush->flush() as $report) {
        $endpoint = $report->getRequest()->getUri()->__toString();

        if ($report->isSuccess()) {
            echo "[v] Message sent successfully for subscription {$endpoint}.<br>\n";
        } else {
            echo "[x] Message failed to send for subscription {$endpoint}: {$report->getReason()}<br>\n";
        }
    }
}

// Example of how you might trigger a push notification
if (isset($_GET['send']) && $_GET['send'] === 'true') {
    $payload = [
        'title' => 'Test Push Notification',
        'body' => 'This is a test message sent from the server!',
        // You can add more data here
    ];

    try {
        $stmt = $conn->query("SELECT * FROM subscriptions");
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub['endpoint'],
                'keys' => [
                    'p256dh' => $sub['p256dh'],
                    'auth' => $sub['auth'],
                ],
            ]);
            sendPushNotification($subscription, $payload, $vapidPublicKey, $vapidPrivateKey);
        }
    } catch(PDOException $e) {
        error_log("Error fetching subscriptions: " . $e->getMessage());
        echo "Error sending push notifications.";
    }
}

$conn = null; // Close the database connection
?>