<?php
// push_debug.php - Debugging-Seite für Push-Benachrichtigungen
// Aktiviere Fehlerausgabe für Debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Push-Benachrichtigungen Debug</h1>";

// Überprüfen, ob die Konfigurationsdatei geladen werden kann
echo "<h2>1. Überprüfe Konfigurationsdateien</h2>";
try {
    echo "Versuche, system/config.php zu laden... ";
    require_once '../system/config.php';
    echo "<span style='color:green'>OK</span><br>";
    
    echo "Versuche, system/push_notifications_config.php zu laden... ";
    require_once '../system/push_notifications_config.php';
    echo "<span style='color:green'>OK</span><br>";
    
    echo "Versuche, api/push_notification.php zu laden... ";
    require_once '../api/push_notification.php';
    echo "<span style='color:green'>OK</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>FEHLER: " . $e->getMessage() . "</span><br>";
}

// Überprüfen, ob die Datenbankverbindung funktioniert
echo "<h2>2. Überprüfe Datenbankverbindung</h2>";
try {
    echo "Versuche, eine Verbindung zur Datenbank herzustellen... ";
    if (isset($pdo)) {
        echo "<span style='color:green'>OK</span><br>";
        
        // Überprüfen, ob die Tabelle push_subscriptions existiert
        echo "Überprüfe, ob die Tabelle push_subscriptions existiert... ";
        $stmt = $pdo->query("SHOW TABLES LIKE 'push_subscriptions'");
        if ($stmt->rowCount() > 0) {
            echo "<span style='color:green'>OK</span><br>";
            
            // Anzahl der Abonnements anzeigen
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM push_subscriptions");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "Anzahl der Abonnements: $count<br>";
        } else {
            echo "<span style='color:red'>FEHLER: Tabelle existiert nicht</span><br>";
            echo "Versuche, die Tabelle zu erstellen... ";
            try {
                $sql = file_get_contents('../system/setup_push_subscriptions_table.sql');
                $pdo->exec($sql);
                echo "<span style='color:green'>OK</span><br>";
            } catch (Exception $e) {
                echo "<span style='color:red'>FEHLER: " . $e->getMessage() . "</span><br>";
            }
        }
    } else {
        echo "<span style='color:red'>FEHLER: \$pdo ist nicht definiert</span><br>";
    }
} catch (Exception $e) {
    echo "<span style='color:red'>FEHLER: " . $e->getMessage() . "</span><br>";
}

// Überprüfen, ob die VAPID-Schlüssel konfiguriert sind
echo "<h2>3. Überprüfe VAPID-Schlüssel</h2>";
if (defined('VAPID_PUBLIC_KEY') && defined('VAPID_PRIVATE_KEY') && defined('VAPID_SUBJECT')) {
    echo "VAPID_PUBLIC_KEY: ";
    if (VAPID_PUBLIC_KEY === 'YOUR_VAPID_PUBLIC_KEY_HERE') {
        echo "<span style='color:red'>FEHLER: Standardwert nicht geändert</span><br>";
    } else {
        echo "<span style='color:green'>OK</span><br>";
    }
    
    echo "VAPID_PRIVATE_KEY: ";
    if (VAPID_PRIVATE_KEY === 'YOUR_VAPID_PRIVATE_KEY_HERE') {
        echo "<span style='color:red'>FEHLER: Standardwert nicht geändert</span><br>";
    } else {
        echo "<span style='color:green'>OK</span><br>";
    }
    
    echo "VAPID_SUBJECT: ";
    if (VAPID_SUBJECT === 'mailto:your-email@example.com') {
        echo "<span style='color:red'>FEHLER: Standardwert nicht geändert</span><br>";
    } else {
        echo "<span style='color:green'>OK</span><br>";
    }
} else {
    echo "<span style='color:red'>FEHLER: VAPID-Konstanten sind nicht definiert</span><br>";
}

// Überprüfen, ob die Composer-Abhängigkeiten installiert sind
echo "<h2>4. Überprüfe Composer-Abhängigkeiten</h2>";
if (file_exists('../vendor/autoload.php')) {
    echo "vendor/autoload.php: <span style='color:green'>OK</span><br>";
    
    echo "Versuche, die WebPush-Klasse zu laden... ";
    if (class_exists('Minishlink\WebPush\WebPush')) {
        echo "<span style='color:green'>OK</span><br>";
    } else {
        echo "<span style='color:red'>FEHLER: Klasse nicht gefunden</span><br>";
    }
} else {
    echo "<span style='color:red'>FEHLER: vendor/autoload.php nicht gefunden. Composer-Abhängigkeiten wurden nicht installiert.</span><br>";
}

// Überprüfen, ob die Seriennummer des Benutzers verfügbar ist
echo "<h2>5. Überprüfe Benutzerinformationen</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "Benutzer-ID: " . $_SESSION['user_id'] . "<br>";
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM benutzer WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "Benutzername: " . $user['benutzername'] . "<br>";
            echo "Seriennummer: ";
            if (!empty($user['seriennummer'])) {
                echo $user['seriennummer'] . "<br>";
            } else {
                echo "<span style='color:red'>FEHLER: Keine Seriennummer gefunden</span><br>";
            }
        } else {
            echo "<span style='color:red'>FEHLER: Benutzer nicht gefunden</span><br>";
        }
    } catch (Exception $e) {
        echo "<span style='color:red'>FEHLER: " . $e->getMessage() . "</span><br>";
    }
} else {
    echo "<span style='color:red'>FEHLER: Nicht angemeldet</span><br>";
}

// Anzeigen der Push-Benachrichtigungskonfiguration
echo "<h2>6. Push-Benachrichtigungskonfiguration</h2>";
echo "<pre>";
echo "PUSH_NOTIFICATIONS_ENABLED:\n";
print_r($PUSH_NOTIFICATIONS_ENABLED);
echo "\nPUSH_NOTIFICATIONS_MESSAGES:\n";
print_r($PUSH_NOTIFICATIONS_MESSAGES);
echo "\nPUSH_NOTIFICATIONS_URL: " . $PUSH_NOTIFICATIONS_URL;
echo "</pre>";

// Link zur Push-Benachrichtigungskonfiguration
echo "<h2>7. Links</h2>";
echo "<a href='push_notifications.php'>Zur Push-Benachrichtigungskonfiguration</a><br>";
echo "<a href='../protected.html'>Zurück zur geschützten Seite</a>";
?>
