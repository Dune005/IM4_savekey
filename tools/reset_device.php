<?php
/**
 * Device Reset Tool
 * 
 * Dieses Script sendet einen Reset-Befehl an die Schlüsselbox.
 * Die Box wird beim nächsten Check (innerhalb von 30 Sekunden) neu gestartet.
 * 
 * Verwendung: Einfach im Browser aufrufen: https://savekey.klaus-klebband.ch/tools/reset_device.php
 */

// Fehlerausgabe aktivieren
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Datenbank-Konfiguration einbinden
require_once '../system/config.php';

// Seriennummer der Box (anpassen falls mehrere Boxen vorhanden)
$seriennummer = '550';

// Optional: Einfache Authentifizierung (Passwortschutz)
// Entferne die Kommentare und ändere das Passwort für zusätzliche Sicherheit
/*
$required_password = 'dein_sicheres_passwort_hier';
if (!isset($_GET['password']) || $_GET['password'] !== $required_password) {
    die('❌ Zugriff verweigert. Bitte Passwort angeben: ?password=xxx');
}
*/

?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaveKey - Device Reset</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196F3;
        }
        .warning {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .success {
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
        }
        button {
            background: #2196F3;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #1976D2;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .status {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 SaveKey Device Reset</h1>
        
        <div class="info">
            <strong>ℹ️ Information:</strong><br>
            Dieser Reset startet die Schlüsselbox neu und initialisiert alle Sensoren und Verbindungen erneut.
            <br><br>
            <strong>Seriennummer:</strong> <code><?php echo htmlspecialchars($seriennummer); ?></code>
        </div>

        <div class="warning">
            <strong>⚠️ Hinweis:</strong><br>
            Der Reset wird beim nächsten Check-Intervall (innerhalb von 30 Sekunden) ausgeführt.
            Die Box wird kurz offline sein und dann automatisch neu starten.
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                // Prüfen, ob die Tabelle existiert, sonst erstellen
                $checkTable = $pdo->query("SHOW TABLES LIKE 'device_reset_commands'");
                if ($checkTable->rowCount() == 0) {
                    // Tabelle erstellen
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
                    echo '<div class="success">✅ Tabelle "device_reset_commands" wurde erstellt.</div>';
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
                    ':initiated_by' => 'web_interface',
                    ':reason' => 'Manual reset via tools/reset_device.php'
                ]);

                echo '<div class="success">
                    <strong>✅ Reset-Befehl erfolgreich gesendet!</strong><br><br>
                    Die Box wird innerhalb der nächsten 30 Sekunden neu gestartet.<br>
                    Du kannst diese Seite schließen.
                </div>';

                // Offene Befehle anzeigen
                $countStmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM device_reset_commands 
                    WHERE seriennummer = :seriennummer 
                    AND executed = 0
                ");
                $countStmt->execute([':seriennummer' => $seriennummer]);
                $count = $countStmt->fetchColumn();

                if ($count > 1) {
                    echo '<div class="info">
                        Es gibt <strong>' . $count . '</strong> ausstehende Reset-Befehle für diese Box.
                    </div>';
                }

            } catch (Exception $e) {
                echo '<div class="error">
                    <strong>❌ Fehler beim Senden des Reset-Befehls:</strong><br>
                    ' . htmlspecialchars($e->getMessage()) . '
                </div>';
            }
        }
        ?>

        <form method="POST" onsubmit="return confirm('Möchtest du die Schlüsselbox wirklich neu starten?');">
            <button type="submit">🔄 Box jetzt neu starten</button>
        </form>

        <hr style="margin: 30px 0; border: none; border-top: 1px solid #ddd;">

        <h3>📊 Status der Reset-Befehle</h3>
        <?php
        try {
            // Prüfen ob Tabelle existiert
            $checkTable = $pdo->query("SHOW TABLES LIKE 'device_reset_commands'");
            if ($checkTable->rowCount() > 0) {
                $statusStmt = $pdo->prepare("
                    SELECT * FROM device_reset_commands 
                    WHERE seriennummer = :seriennummer 
                    ORDER BY created_at DESC 
                    LIMIT 10
                ");
                $statusStmt->execute([':seriennummer' => $seriennummer]);
                $commands = $statusStmt->fetchAll(PDO::FETCH_ASSOC);

                if (count($commands) > 0) {
                    echo '<table style="width: 100%; border-collapse: collapse; margin-top: 15px;">';
                    echo '<tr style="background: #f5f5f5; text-align: left;">
                            <th style="padding: 10px; border: 1px solid #ddd;">Erstellt</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Status</th>
                            <th style="padding: 10px; border: 1px solid #ddd;">Ausgeführt</th>
                          </tr>';
                    
                    foreach ($commands as $cmd) {
                        $status = $cmd['executed'] ? '✅ Ausgeführt' : '⏳ Ausstehend';
                        $executedAt = $cmd['executed_at'] ? date('d.m.Y H:i:s', strtotime($cmd['executed_at'])) : '-';
                        $createdAt = date('d.m.Y H:i:s', strtotime($cmd['created_at']));
                        
                        echo '<tr>
                                <td style="padding: 10px; border: 1px solid #ddd;">' . $createdAt . '</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">' . $status . '</td>
                                <td style="padding: 10px; border: 1px solid #ddd;">' . $executedAt . '</td>
                              </tr>';
                    }
                    
                    echo '</table>';
                } else {
                    echo '<p style="color: #666;">Noch keine Reset-Befehle vorhanden.</p>';
                }
            } else {
                echo '<p style="color: #666;">Tabelle existiert noch nicht. Wird beim ersten Reset erstellt.</p>';
            }
        } catch (Exception $e) {
            echo '<p style="color: #666;">Status konnte nicht geladen werden.</p>';
        }
        ?>

    </div>
</body>
</html>
