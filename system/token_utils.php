<?php
// token_utils.php - Funktionen zum Verschlüsseln und Entschlüsseln von Tokens

// Geheimer Schlüssel für die Verschlüsselung
// In einer Produktionsumgebung sollte dieser in einer separaten Konfigurationsdatei gespeichert werden
define('TOKEN_SECRET_KEY', 'safekey_token_secret_key_2023');

/**
 * Generiert einen verschlüsselten Token aus einer Seriennummer
 * 
 * @param string $seriennummer Die Seriennummer der Schlüsselbox
 * @return string Der verschlüsselte Token
 */
function generateSerialToken($seriennummer) {
    // Aktuelle Zeit hinzufügen, um den Token einzigartig zu machen
    $data = [
        'seriennummer' => $seriennummer,
        'timestamp' => time(),
        // Optional: Ablaufzeit hinzufügen
        'expires' => time() + (30 * 24 * 60 * 60) // 30 Tage gültig
    ];
    
    // Daten in JSON umwandeln
    $jsonData = json_encode($data);
    
    // Verschlüsseln mit OpenSSL
    $method = 'AES-256-CBC';
    $ivlen = openssl_cipher_iv_length($method);
    $iv = openssl_random_pseudo_bytes($ivlen);
    
    $encrypted = openssl_encrypt($jsonData, $method, TOKEN_SECRET_KEY, 0, $iv);
    
    // IV und verschlüsselte Daten kombinieren und base64-kodieren
    $token = base64_encode($iv . $encrypted);
    
    // URL-sicher machen
    $token = str_replace(['+', '/', '='], ['-', '_', ''], $token);
    
    return $token;
}

/**
 * Entschlüsselt einen Token und gibt die Seriennummer zurück
 * 
 * @param string $token Der verschlüsselte Token
 * @return string|false Die Seriennummer oder false bei Fehler oder abgelaufenem Token
 */
function decodeSerialToken($token) {
    try {
        // URL-sichere Zeichen zurück konvertieren
        $token = str_replace(['-', '_'], ['+', '/'], $token);
        $token = base64_decode($token);
        
        if ($token === false) {
            return false;
        }
        
        $method = 'AES-256-CBC';
        $ivlen = openssl_cipher_iv_length($method);
        
        // IV extrahieren
        $iv = substr($token, 0, $ivlen);
        $encrypted = substr($token, $ivlen);
        
        // Entschlüsseln
        $jsonData = openssl_decrypt($encrypted, $method, TOKEN_SECRET_KEY, 0, $iv);
        
        if ($jsonData === false) {
            return false;
        }
        
        // JSON dekodieren
        $data = json_decode($jsonData, true);
        
        if (!isset($data['seriennummer']) || !isset($data['expires'])) {
            return false;
        }
        
        // Prüfen, ob der Token abgelaufen ist
        if ($data['expires'] < time()) {
            return false;
        }
        
        return $data['seriennummer'];
    } catch (Exception $e) {
        // Bei Fehlern false zurückgeben
        return false;
    }
}

/**
 * Generiert eine URL für die Admin-Registrierung mit verschlüsseltem Token
 * 
 * @param string $seriennummer Die Seriennummer der Schlüsselbox
 * @param string $baseUrl Die Basis-URL (optional)
 * @return string Die vollständige URL mit Token
 */
function generateAdminRegistrationUrl($seriennummer, $baseUrl = '') {
    if (empty($baseUrl)) {
        // Aktuelle Domain und Pfad ermitteln
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $baseUrl = $protocol . '://' . $host;
    }
    
    $token = generateSerialToken($seriennummer);
    return $baseUrl . '/admin_register.html?token=' . $token;
}
?>
