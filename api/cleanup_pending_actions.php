<?php
// cleanup_pending_actions.php - Bereinigungsscript für verwaiste pending Einträge
// Dieses Script kann manuell aufgerufen oder als Cron-Job eingerichtet werden

header('Content-Type: application/json');

// Include database configuration
require_once '../system/config.php';

// Include hardware authentication if called from external
if (isset($_GET['api_key'])) {
    require_once '../system/hardware_auth.php';
    
    if ($_GET['api_key'] !== HARDWARE_API_KEY) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized"]);
        exit;
    }
}

try {
    // 1. Lösche alle pending Einträge, die älter als 30 Minuten sind
    $cleanupExpiredStmt = $pdo->prepare("
        DELETE FROM pending_key_actions 
        WHERE status = 'pending' 
        AND timestamp < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
    ");
    $cleanupExpiredStmt->execute();
    $expiredCount = $cleanupExpiredStmt->rowCount();

    // 2. Für jede Seriennummer: Behalte nur den neuesten pending Eintrag, lösche alle anderen
    $cleanupDuplicatesStmt = $pdo->prepare("
        DELETE p1 FROM pending_key_actions p1
        INNER JOIN pending_key_actions p2 
        WHERE p1.seriennummer = p2.seriennummer 
        AND p1.status = 'pending' 
        AND p2.status = 'pending'
        AND p1.timestamp < p2.timestamp
    ");
    $cleanupDuplicatesStmt->execute();
    $duplicatesCount = $cleanupDuplicatesStmt->rowCount();

    // 3. Status-Abfrage für Monitoring
    $statusStmt = $pdo->prepare("
        SELECT 
            seriennummer,
            COUNT(*) as pending_count,
            MIN(timestamp) as oldest_pending,
            MAX(timestamp) as newest_pending
        FROM pending_key_actions 
        WHERE status = 'pending'
        GROUP BY seriennummer
    ");
    $statusStmt->execute();
    $currentPendingActions = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [
        "status" => "success",
        "message" => "Bereinigung abgeschlossen",
        "cleaned_up" => [
            "expired_entries" => $expiredCount,
            "duplicate_entries" => $duplicatesCount
        ],
        "current_pending_actions" => $currentPendingActions,
        "timestamp" => date('Y-m-d H:i:s')
    ];

    // Log das Ergebnis
    error_log("Pending Actions Cleanup: " . json_encode($result));

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error", 
        "message" => "Cleanup-Fehler: " . $e->getMessage()
    ]);
}
?>
