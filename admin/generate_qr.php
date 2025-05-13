<?php
// generate_qr.php - Tool zum Generieren von QR-Codes für die Admin-Registrierung
// Dieses Skript sollte nur für Administratoren zugänglich sein

// Fehlerausgabe aktivieren für Entwicklungszwecke
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Überprüfen, ob der Benutzer angemeldet und ein Administrator ist
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: ../login.html');
    exit;
}

require_once '../system/config.php';
require_once '../system/token_utils.php';

// Aktuelle Domain ermitteln
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . '://' . $host;

// Seriennummer aus der Anfrage holen oder Standardwert verwenden
$seriennummer = $_GET['seriennummer'] ?? '';
$generatedUrl = '';
$qrCodeImage = '';

// Wenn eine Seriennummer angegeben wurde, URL und QR-Code generieren
if (!empty($seriennummer)) {
    $generatedUrl = generateAdminRegistrationUrl($seriennummer, $baseUrl);
    
    // QR-Code generieren (erfordert die Installation der PHP QR Code Bibliothek)
    // Hier wird nur der Link angezeigt, den man in einen Online-QR-Code-Generator einfügen kann
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>QR-Code Generator für Admin-Registrierung</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .qr-generator {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .result-container {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .url-display {
            word-break: break-all;
            padding: 10px;
            background-color: #eee;
            border-radius: 4px;
            margin: 10px 0;
            font-family: monospace;
        }
        .qr-placeholder {
            margin: 20px 0;
            padding: 20px;
            border: 2px dashed #ccc;
            text-align: center;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="qr-generator">
        <h1>QR-Code Generator für Admin-Registrierung</h1>
        
        <div class="info-box">
            <p><strong>Hinweis:</strong> Mit diesem Tool können Sie QR-Codes für die Admin-Registrierung generieren.</p>
            <p>Der generierte QR-Code enthält einen verschlüsselten Token, der die Seriennummer der Schlüsselbox enthält.</p>
            <p>Dieser Token ist 30 Tage gültig und kann nur einmal verwendet werden.</p>
        </div>
        
        <form method="get">
            <div>
                <label for="seriennummer">Seriennummer der Schlüsselbox:</label>
                <input type="text" id="seriennummer" name="seriennummer" value="<?php echo htmlspecialchars($seriennummer); ?>" required>
            </div>
            <button type="submit">QR-Code generieren</button>
        </form>
        
        <?php if (!empty($generatedUrl)): ?>
        <div class="result-container">
            <h3>Generierte URL:</h3>
            <div class="url-display"><?php echo htmlspecialchars($generatedUrl); ?></div>
            
            <h3>QR-Code:</h3>
            <div class="qr-placeholder">
                <p>Verwenden Sie die folgende URL in einem Online-QR-Code-Generator:</p>
                <a href="https://www.qr-code-generator.com/" target="_blank">QR Code Generator</a> oder
                <a href="https://www.qrcode-monkey.com/" target="_blank">QRCode Monkey</a>
            </div>
            
            <h3>Anleitung:</h3>
            <ol>
                <li>Kopieren Sie die oben generierte URL</li>
                <li>Öffnen Sie einen der empfohlenen QR-Code-Generatoren</li>
                <li>Fügen Sie die URL ein und generieren Sie den QR-Code</li>
                <li>Laden Sie den QR-Code herunter und drucken Sie ihn aus</li>
                <li>Legen Sie den QR-Code der Schlüsselbox mit der Seriennummer <?php echo htmlspecialchars($seriennummer); ?> bei</li>
            </ol>
        </div>
        <?php endif; ?>
        
        <p><a href="../protected.html">Zurück zur geschützten Seite</a></p>
    </div>
</body>
</html>
