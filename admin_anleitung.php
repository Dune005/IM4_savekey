<?php
// admin_anleitung.php
session_start();

// Überprüfen, ob der Benutzer angemeldet ist und Admin-Rechte hat
$is_admin = isset($_SESSION['user_id']) && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

if (!$is_admin):
?>
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zugriff verweigert – SaveKey</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
      body { 
        background-color: #fafafa; 
        font-family: 'Open Sans', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        margin: 0;
      }
      .error-card {
        background: white;
        padding: 3rem 2rem;
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        text-align: center;
        max-width: 450px;
        width: 90%;
      }
      .error-icon {
        font-size: 3rem;
        color: #dc2626;
        margin-bottom: 1.5rem;
      }
      h1 { font-size: 1.5rem; color: #2c3e50; margin-bottom: 1rem; }
      p { color: #4a5568; line-height: 1.6; margin-bottom: 2rem; }
      .back-btn {
        display: inline-block;
        padding: 12px 24px;
        background: #2c3e50;
        color: white;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        transition: transform 0.2s;
      }
      .back-btn:hover { transform: translateY(-2px); }
    </style>
  </head>
  <body>
    <div class="error-card">
      <i class="fas fa-lock error-icon"></i>
      <h1>Zugriff eingeschränkt</h1>
      <p>Dieser Bereich ist ausschliesslich für Administratoren reserviert. Du hast momentan nicht die erforderlichen Berechtigungen.</p>
      <a href="einrichtungsanleitung.html" class="back-btn">Zurück zur Anleitung</a>
    </div>
  </body>
</html>
<?php 
exit; 
endif; 
?>
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin-Anleitung – VR-Igloo Box</title>
    <meta name="description" content="Technische Anleitung für Administratoren der VR-Igloo Schlüsselbox.">
    
    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/assets/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/favicon-16x16.png">
    <link rel="manifest" href="/assets/site.webmanifest">
    
    <!-- Typography: Open Sans (VR-Igloo Design) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="js/global-auth.js"></script>
    <script src="js/mobile-nav.js"></script>
    <style>
      :root {
        --background-modern: #fafafa;
        --accent-orange: #f97316;
        --accent-orange-dark: #ea580c;
        --success-color: #22c55e;
        --warning-color: #f59e0b;
        --info-color: #3b82f6;
        --admin-red: #dc2626;
        --admin-red-dark: #b91c1c;
      }

      * {
        box-sizing: border-box;
      }
      
      html {
        overflow-x: hidden;
      }

      body {
        font-family: 'Open Sans', sans-serif;
        font-size: 16px;
        line-height: 1.6;
        overflow-x: hidden;
        background: var(--background-modern);
        color: var(--text-color);
        margin: 0;
      }
      
      .guide-container {
        max-width: 800px;
        padding: 0 1.5rem;
        width: 100%;
        margin: 0 auto;
      }
      
      /* Hero Section */
      .guide-hero {
        text-align: center;
        padding: 3rem 0 1.5rem;
      }
      
      .guide-hero .hero-badge {
        display: inline-block;
        background: linear-gradient(135deg, var(--admin-red) 0%, var(--admin-red-dark) 100%);
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 6px 14px;
        border-radius: 50px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 1rem;
      }
      
      .guide-hero h1 {
        font-size: 2.25rem;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: var(--secondary-color);
        margin: 0 0 0.5rem;
        line-height: 1.2;
      }
      
      .guide-hero .subtitle {
        font-size: 1.05rem;
        color: var(--dark-gray);
        margin: 0;
        font-weight: 400;
      }

      /* Back Link - Inside hero */
      .guide-back-link {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--dark-gray);
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 500;
        padding: 8px 16px;
        margin-top: 1.5rem;
        border-radius: 50px;
        background: #fff;
        border: 1px solid #e8e8e8;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
      }

      .guide-back-link:hover {
        color: var(--secondary-color);
        border-color: #d0d0d0;
      }

      .guide-back-link i {
        font-size: 0.8rem;
      }
      
      /* Step Container */
      .steps-container {
        display: flex;
        flex-direction: column;
        gap: 3rem;
        margin: 5.5rem 0 8.5rem;
      }
      
      /* Step Cards */
      .step-card {
        background: white;
        padding: 2rem 2rem 1.75rem;
        border-radius: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        position: relative;
        border: 1px solid #e8e8e8;
        transition: box-shadow 0.3s ease, transform 0.3s ease;
        text-align: left;
      }
      
      .step-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.06);
      }
      
      .step-card .step-number {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--admin-red) 0%, var(--admin-red-dark) 100%);
        color: white;
        border-radius: 50%;
        font-weight: 700;
        font-size: 1.1rem;
        position: absolute;
        top: -20px;
        left: 1.75rem;
        box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25);
      }
      
      .step-card .step-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 0.5rem;
        margin-bottom: 1.25rem;
      }

      .step-card .step-icon {
        width: 36px;
        height: 36px;
        min-width: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
      }

      .step-card .step-icon.red {
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        color: var(--admin-red);
      }

      .step-card .step-icon i {
        font-size: 1rem;
      }
      
      .step-card h3 {
        margin: 0;
        font-size: 1.25rem;
        color: var(--secondary-color);
        font-weight: 700;
      }
      
      .step-card p {
        font-size: 0.95rem;
        line-height: 1.65;
        margin: 0 0 1rem;
        color: #4a5568;
      }

      .step-card .section-label {
        font-weight: 700;
        color: var(--secondary-color);
        font-size: 0.95rem;
        margin-top: 1.25rem;
        margin-bottom: 0.5rem;
      }
      
      .step-card ul, .step-card ol {
        padding-left: 0;
        margin: 1rem 0;
        list-style: none;
      }
      
      .step-card li {
        margin-bottom: 0.5rem;
        line-height: 1.55;
        padding-left: 1.25rem;
        position: relative;
        font-size: 0.95rem;
        color: #4a5568;
      }

      .step-card ul li::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0.55rem;
        width: 5px;
        height: 5px;
        border-radius: 50%;
        background: var(--admin-red);
      }

      .step-card ol {
        counter-reset: step-counter;
      }

      .step-card ol li {
        counter-increment: step-counter;
      }

      .step-card ol li::before {
        content: counter(step-counter) ".";
        position: absolute;
        left: 0;
        color: var(--admin-red);
        font-weight: 700;
      }

      .step-card code {
        background: #f1f5f9;
        padding: 0.2rem 0.4rem;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        color: #e11d48;
      }

      /* Code Block */
      .code-block {
        background: #1e293b;
        color: #e2e8f0;
        padding: 1.25rem;
        border-radius: 12px;
        overflow-x: auto;
        margin: 1rem 0;
        font-family: 'Courier New', monospace;
        font-size: 0.85rem;
        line-height: 1.5;
      }

      /* Spec Table */
      .spec-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0 1.5rem;
        font-size: 0.9rem;
      }
      
      .spec-table th,
      .spec-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
      }
      
      .spec-table th {
        background: #f8fafc;
        font-weight: 700;
        color: var(--secondary-color);
      }

      /* Info Boxes */
      .info-box {
        padding: 1rem 1.25rem;
        margin: 1.5rem 0 0;
        border-radius: 12px;
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
      }

      .info-box.tip { background: #fffbeb; border: 1px solid #fde68a; }
      .info-box.tip .info-icon { color: #f59e0b; }
      .info-box.info { background: #eff6ff; border: 1px solid #bfdbfe; }
      .info-box.info .info-icon { color: #3b82f6; }
      .info-box.warning { background: #fff1f2; border: 1px solid #fecaca; }
      .info-box.warning .info-icon { color: #e11d48; }

      .info-box .info-icon {
        font-size: 1rem;
        flex-shrink: 0;
        margin-top: 2px;
      }

      .info-box .info-title {
        font-weight: 700;
        font-size: 0.9rem;
        margin: 0 0 0.25rem;
        color: var(--secondary-color);
      }

      .info-box p {
        font-size: 0.85rem;
        margin: 0;
        color: #4a5568;
        line-height: 1.5;
      }

      /* Support Card */
      .support-card {
        background: white;
        border: 1px solid #e8e8e8;
        border-radius: 14px;
        padding: 2rem;
        margin: 2rem 0;
        text-align: center;
      }

      .support-card h4 {
        margin: 0 0 0.5rem;
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--secondary-color);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
      }

      .support-card p {
        margin: 0 0 1.25rem;
        color: var(--dark-gray);
      }

      .support-contacts {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
      }

      .support-contact {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #f8fafc;
        border-radius: 8px;
        font-size: 0.9rem;
      }

      .support-contact a {
        color: var(--primary-color);
        text-decoration: none;
        font-weight: 600;
      }

      /* Footer */
      .guide-footer {
        text-align: center;
        padding: 2rem 0 3rem;
        color: var(--dark-gray);
        font-size: 0.85rem;
      }
      
      @media (max-width: 768px) {
        .guide-container {
          padding: 0 1rem;
        }
        
        .guide-hero {
          padding: 2.5rem 0 1rem;
        }
        
        .guide-hero h1 {
          font-size: 1.75rem;
        }
        
        .guide-hero .subtitle {
          font-size: 0.9rem;
        }

        .steps-container {
          gap: 2rem;
          margin: 4rem 0 2rem;
        }
        
        .step-card {
          display: block !important;
          flex-direction: unset !important;
          padding: 1.5rem 1.15rem 1.25rem;
          border-radius: 14px;
        }

        .step-card > * {
          display: block;
          width: 100%;
        }
        
        .step-card .step-number {
          display: flex !important;
          position: absolute !important;
          width: 34px;
          height: 34px;
          font-size: 0.95rem;
          top: -17px;
          left: 1.15rem;
        }

        .step-card .step-header {
          display: flex !important;
          flex-direction: row !important;
          align-items: center;
          gap: 0.75rem;
          margin-top: 0.35rem;
          width: auto !important;
        }

        .spec-table {
          display: block;
          width: 100%;
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
        }

        .info-box {
          display: flex !important;
          flex-direction: row !important;
          padding: 0.75rem 0.85rem;
          border-radius: 8px;
          margin-top: 0.85rem;
          width: auto !important;
        }

        .support-contacts {
          flex-direction: column;
          align-items: stretch;
          gap: 0.5rem;
        }

        .support-contact {
          justify-content: center;
          font-size: 0.8rem;
          padding: 0.5rem 0.75rem;
        }
      }

      @media (max-width: 400px) {
        .guide-hero {
          padding: 2rem 0 0.75rem;
        }

        .guide-hero h1 {
          font-size: 1.5rem;
        }

        .info-box {
          flex-direction: column !important;
          gap: 0.4rem;
        }

        .support-contact {
          font-size: 0.75rem;
        }
      }
    </style>
  </head>
  <body>
    <header class="header-dashboard" role="banner">
      <div class="container">
        <a href="index.html">
          <img src="images/logo_savekey_text.svg" alt="SaveKey Logo" class="logo">
        </a>
        
        <button id="mobileMenuToggle" class="mobile-menu-toggle" aria-label="Menü öffnen">
          <i class="fas fa-bars"></i>
        </button>

        <nav id="navigation" role="navigation" aria-label="Hauptnavigation">
          <ul>
            <li><a href="login.html" class="nav-link" data-text="Login">Login</a></li>
            <li><a href="register.html" class="nav-link" data-text="Registrieren">Registrieren</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <main>
      <div class="guide-container">
        <!-- Hero Section -->
        <div class="guide-hero">
          <span class="hero-badge">VR-Igloo Admin</span>
          <h1>Technische Dokumentation</h1>
          <p class="subtitle">Setup, Wartung und Troubleshooting der VR-Igloo Schlüsselbox.</p>
          <a href="einrichtungsanleitung.html" class="guide-back-link">
            <i class="fas fa-arrow-left" aria-hidden="true"></i> Zurück zur Anleitung
          </a>
        </div>
        
        <!-- Dokumentations-Container -->
        <div class="steps-container">
          <!-- Schritt 1: Hardware -->
          <article class="step-card">
            <div class="step-number">1</div>
            <div class="step-header">
              <div class="step-icon red">
                <i class="fas fa-microchip"></i>
              </div>
              <h3>Hardware-Setup</h3>
            </div>
            
            <p class="section-label">Komponenten:</p>
            <table class="spec-table">
              <thead>
                <tr>
                  <th>Komponente</th>
                  <th>Modell</th>
                  <th>Spezifikation</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Mikrocontroller</td>
                  <td>ESP32 C6 DevKitC-1-N8</td>
                  <td>WiFi 6, BLE 5.0</td>
                </tr>
                <tr>
                  <td>NFC-Reader</td>
                  <td>PN532</td>
                  <td>I²C Protocol</td>
                </tr>
                <tr>
                  <td>Servo-Motor</td>
                  <td>SG90 9g</td>
                  <td>3.3V, Pin 6 (PWM)</td>
                </tr>
                <tr>
                  <td>Magnetsensor</td>
                  <td>Reed-Switch</td>
                  <td>Pin 1 (INPUT_PULLDOWN)</td>
                </tr>
              </tbody>
            </table>
            
            <p class="section-label">Pin-Belegung (ESP32 C6):</p>
            <div class="code-block">
#define I2C_SDA 4
#define I2C_SCL 5
#define PN532_IRQ 2
#define PN532_RESET 3
#define MAGNETIC_SENSOR_PIN 1
#define LED_PIN 10
#define SERVO_PIN 6
            </div>

            <div class="info-box warning">
              <i class="fas fa-exclamation-triangle info-icon"></i>
              <div class="info-content">
                <p class="info-title">Wichtig!</p>
                <p>Der Servo darf nicht direkt an 5V angeschlossen werden, da der ESP32 C6 nur 3.3V GPIO-Pins hat. Bei höherer Last einen 5V-Regler verwenden.</p>
              </div>
            </div>
          </article>
          
          <!-- Schritt 2: Ersteinrichtung -->
          <article class="step-card">
            <div class="step-number">2</div>
            <div class="step-header">
              <div class="step-icon red">
                <i class="fas fa-user-shield"></i>
              </div>
              <h3>Ersteinrichtung (Admin-Konto)</h3>
            </div>
            
            <p class="section-label">QR-Code generieren:</p>
            <ol>
              <li>Öffne <code>/admin/generate_qr.php</code> (nur lokal zugänglich)</li>
              <li>Gib die Seriennummer der Box ein (z.B. <code>550</code>)</li>
              <li>Generiere den QR-Code und drucke ihn aus</li>
              <li>Link-Format: <code>admin_register.html?seriennummer=550</code></li>
            </ol>

            <div class="info-box info">
              <i class="fas fa-info-circle info-icon"></i>
              <div class="info-content">
                <p class="info-title">Admin-Rechte</p>
                <p>Der erste registrierte Benutzer einer Seriennummer erhält automatisch Admin-Rechte. Weitere Admins können in der DB manuell (<code>is_admin=1</code>) hinzugefügt werden.</p>
              </div>
            </div>
          </article>
          
          <!-- Schritt 3: Firmware -->
          <article class="step-card">
            <div class="step-number">3</div>
            <div class="step-header">
              <div class="step-icon red">
                <i class="fas fa-code"></i>
              </div>
              <h3>Firmware-Konfiguration</h3>
            </div>
            
            <p>Passe die Credentials in <code>system/arduino/savekey_neu_Servo_181225.ino</code> an:</p>
            <div class="code-block">
const char* ssid = "DEIN_WLAN_NAME";
const char* password = "DEIN_WLAN_PASSWORT";
const char* API_ENDPOINT = "https://your-domain.com/api/arduino_api.php";
const char* API_KEY = "abc123xyz456";
const char* seriennummer = "550";
            </div>

            <div class="info-box warning">
              <i class="fas fa-shield-alt info-icon"></i>
              <div class="info-content">
                <p class="info-title">Sicherheit</p>
                <p>API-Keys niemals im Git-Repository committen! Nutze Umgebungsvariablen oder separate Config-Dateien.</p>
              </div>
            </div>
          </article>
          
          <!-- Schritt 4: Logik -->
          <article class="step-card">
            <div class="step-number">4</div>
            <div class="step-header">
              <div class="step-icon red">
                <i class="fas fa-sitemap"></i>
              </div>
              <h3>System-Logik</h3>
            </div>
            
            <p class="section-label">States & Timer:</p>
            <ul>
              <li><strong>Auto-Close:</strong> Die Box schliesst 120 Sekunden nach der Öffnung automatisch.</li>
              <li><strong>Pending Verification:</strong> Nach Schlüsselentnahme muss eine RFID-Verifikation erfolgen.</li>
              <li><strong>Alarm-Timeout:</strong> Erfolgt innerhalb von 5 Min. keine Verifikation, wird ein Alarm-Log erstellt.</li>
            </ul>

            <div class="info-box tip">
              <i class="fas fa-lightbulb info-icon"></i>
              <div class="info-content">
                <p class="info-title">Wichtig bei RFID-Scan</p>
                <p>Wenn <code>pendingVerification</code> aktiv ist, wird bei einem Scan nur verifiziert, nicht die Box geschlossen.</p>
              </div>
            </div>
          </article>

          <!-- Schritt 5: API -->
          <article class="step-card">
            <div class="step-number">5</div>
            <div class="step-header">
              <div class="step-icon red">
                <i class="fas fa-exchange-alt"></i>
              </div>
              <h3>API-Endpunkte</h3>
            </div>
            
            <p>Alle Hardware-Events landen bei <code>api/arduino_api.php</code>. Authentifizierung via <code>X-Api-Key</code> Header.</p>
            
            <p class="section-label">Event-Typen:</p>
            <table class="spec-table">
              <tr>
                <td><code>rfid_auth_request</code></td>
                <td>Prüft Autorisierung für Öffnung</td>
              </tr>
              <tr>
                <td><code>key_removed</code></td>
                <td>Setzt pendingVerification auf true</td>
              </tr>
              <tr>
                <td><code>key_returned</code></td>
                <td>Setzt pendingVerification auf false</td>
              </tr>
            </table>
          </article>

          <!-- Schritt 6: Troubleshooting -->
          <article class="step-card">
            <div class="step-number">6</div>
            <div class="step-header">
              <div class="step-icon red">
                <i class="fas fa-wrench"></i>
              </div>
              <h3>Troubleshooting</h3>
            </div>
            
            <p class="section-label">Probleme & Lösungen:</p>
            <ul>
              <li><strong>Keine WLAN-Verbindung:</strong> Serial Monitor auf 115200 Baud prüfen.</li>
              <li><strong>Servo ruckelt:</strong> Stromversorgung prüfen (3.3V/1A Peak nötig).</li>
              <li><strong>RFID Scan schlägt fehl:</strong> I2C-Verkabelung (Pin 4/5) und IRQ-Pin kontrollieren.</li>
            </ul>

            <div class="info-box warning">
              <i class="fas fa-bug info-icon"></i>
              <div class="info-content">
                <p class="info-title">Debug-Modus</p>
                <p>Aktiviere <code>error_reporting(E_ALL)</code> in <code>arduino_api.php</code> für detaillierte Logs.</p>
              </div>
            </div>
          </article>
        </div>
        
        <!-- Support Card -->
        <div class="support-card">
          <h4><i class="fas fa-headset"></i> Technischer Support</h4>
          <p>Bei Fragen zur Hardware oder Backend-Infrastruktur:</p>
          <div class="support-contacts">
            <div class="support-contact">
              <strong>Claudio:</strong> <a href="mailto:claudio.riz@stud.fhgr.ch">claudio.riz@stud.fhgr.ch</a>
            </div>
            <div class="support-contact">
              <strong>Aaron:</strong> <a href="mailto:aaron.taeschler@stud.fhgr.ch">aaron.taeschler@stud.fhgr.ch</a>
            </div>
          </div>
        </div>

        <div class="admin-section" style="text-align: center; margin-bottom: 3rem; border: none; background: transparent;">
          <a href="einrichtungsanleitung.html" class="guide-back-link">
            <i class="fas fa-arrow-left"></i> Zurück zur Anleitung
          </a>
        </div>
      </div>
    </main>

    <footer class="guide-footer">
      <div class="guide-container">
        <p>© 2025 SaveKey – Box Admin Documentation</p>
      </div>
    </footer>
  </body>
</html>

