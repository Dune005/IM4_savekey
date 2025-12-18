#include <Wire.h>
#include <Adafruit_PN532.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <esp_system.h>
#include <ESP32Servo.h>  // ← Servo-Bibliothek
#include <ArduinoOTA.h>  // ← OTA-Updates über WLAN

// --- WLAN-Credentials ---
const char* ssid     = "Igloo";
const char* password = "1glooVision";

// --- API-Konfiguration ---
const char* API_ENDPOINT = "https://savekey.klaus-klebband.ch/api/arduino_api.php";
const char* API_KEY = "sk_hardware_safekey_12345";

// --- I²C-Pins für PN532 ---
#define SDA_PIN     4
#define SCL_PIN     5

// --- PN532 / NFC-Pins ---
#define PN532_IRQ   2
#define PN532_RESET 3

// --- Magnetsensor-Pin ---
const int buttonPin = 1;

// --- LED-Pin ---
const int LED_PIN = 10;

// --- Servo-Pin ---
#define SERVO_PIN 6  // ← NEU: Servo an Pin 6

// --- Seriennummer der Box ---
const char* seriennummer = "550";

// --- Status-Variablen ---
bool keyPresent = true;
bool pendingVerification = false;
unsigned long verificationStartTime = 0;
const unsigned long verificationTimeout = 5 * 60 * 1000;

// --- LED-Timer Variablen ---
bool ledActive = false;
unsigned long ledStartTime = 0;
const unsigned long LED_DURATION = 3000;

// --- Servo-Variablen ---
Servo lockServo;  // Servo-Objekt
bool isLocked = true;  // Verschluss-Status (true = geschlossen bei 0°)
bool autoCloseActive = false;  // Auto-Close Timer aktiv
unsigned long servoOpenTime = 0;  // Zeitpunkt der Öffnung
const unsigned long AUTO_CLOSE_DELAY = 120000;  // 120 Sekunden = 2 Minuten

// --- Verbindungszustände & Retry-Timer ---
bool wifiAvailable = false;
bool nfcAvailable = false;
unsigned long nextWifiAttempt = 0;
unsigned long nextNfcAttempt = 0;
const unsigned long WIFI_RETRY_INTERVAL = 30000UL;
const unsigned long NFC_RETRY_INTERVAL = 15000UL;

// PN532-Objekt über Wire
Adafruit_PN532 nfc(PN532_IRQ, PN532_RESET, &Wire);

// Vorwärtsdeklarationen
void logResetReason();
bool connectToWiFi(uint8_t maxAttempts = 5, unsigned long attemptTimeout = 6000);
bool initializePN532(uint8_t maxAttempts = 3);
void startSuccessLED();
void manageLED();
void blinkStartupLED();
void sendKeyRemovedEvent();
void sendKeyReturnedEvent();
void sendRfidScanEvent(String rfidUid);
void toggleLock();  // ← NEU: Servo-Steuerung

void setup() {
  Serial.begin(115200);
  delay(200);

  logResetReason();

  delay(2000);

  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);

  pinMode(buttonPin, INPUT_PULLDOWN);

  // Servo initialisieren - Box startet verschlossen
  lockServo.setPeriodHertz(50);
  lockServo.attach(SERVO_PIN, 500, 2400);
  lockServo.write(90);  // Startposition: geschlossen (90°)
  isLocked = true;
  autoCloseActive = false;
  Serial.println("🔒 Servo initialisiert - Box verschlossen (90°)");

  Wire.begin(SDA_PIN, SCL_PIN);
  delay(500);

  wifiAvailable = connectToWiFi();
  nextWifiAttempt = millis() + WIFI_RETRY_INTERVAL;

  // OTA-Funktionalität aktivieren (nur wenn WiFi verfügbar)
  if (wifiAvailable) {
    ArduinoOTA.setHostname("savekey_550");  // Hostname im Netzwerk
    ArduinoOTA.setPassword("savekey2025");  // Passwort für OTA-Updates
    
    ArduinoOTA.onStart([]() {
      String type = (ArduinoOTA.getCommand() == U_FLASH) ? "sketch" : "filesystem";
      Serial.println("🔄 OTA Update startet: " + type);
      digitalWrite(LED_PIN, HIGH);  // LED an während Update
    });
    
    ArduinoOTA.onEnd([]() {
      Serial.println("\n✅ OTA Update abgeschlossen");
      digitalWrite(LED_PIN, LOW);
    });
    
    ArduinoOTA.onProgress([](unsigned int progress, unsigned int total) {
      Serial.printf("Fortschritt: %u%%\r", (progress / (total / 100)));
    });
    
    ArduinoOTA.onError([](ota_error_t error) {
      Serial.printf("❌ OTA Error[%u]: ", error);
      if (error == OTA_AUTH_ERROR) Serial.println("Auth Failed");
      else if (error == OTA_BEGIN_ERROR) Serial.println("Begin Failed");
      else if (error == OTA_CONNECT_ERROR) Serial.println("Connect Failed");
      else if (error == OTA_RECEIVE_ERROR) Serial.println("Receive Failed");
      else if (error == OTA_END_ERROR) Serial.println("End Failed");
      digitalWrite(LED_PIN, LOW);
    });
    
    ArduinoOTA.begin();
    Serial.println("📡 OTA bereit - Hostname: savekey_550");
  }

  nfcAvailable = initializePN532();
  nextNfcAttempt = millis() + NFC_RETRY_INTERVAL;

  keyPresent = (digitalRead(buttonPin) == HIGH);
  Serial.print("Initialer Schlüsselstatus: ");
  Serial.println(keyPresent ? "Vorhanden" : "Entfernt");
  blinkStartupLED();
  Serial.println("✅ System bereit - LED leuchtet nur bei erfolgreicher Verifikation");
}

void loop() {
  // OTA-Updates verarbeiten (muss regelmäßig aufgerufen werden)
  if (wifiAvailable) {
    ArduinoOTA.handle();
  }
  
  manageLED();
  manageAutoClose();  // Auto-Close Timer überwachen

  // Reset-Check alle 30 Sekunden
  static unsigned long lastResetCheck = 0;
  static unsigned long lastButtonCheck = 0;
  unsigned long now = millis();
  
  if (now - lastResetCheck >= 30000) {
    lastResetCheck = now;
    checkForResetCommand();
  }

  if (now - lastButtonCheck >= 10) {
    lastButtonCheck = now;
    bool currentKeyPresent = (digitalRead(buttonPin) == HIGH);

    if (currentKeyPresent != keyPresent) {
      keyPresent = currentKeyPresent;

      if (!keyPresent) {
        Serial.println("Schlüssel wurde entfernt!");
        pendingVerification = true;
        verificationStartTime = millis();
        sendKeyRemovedEvent();
      } else {
        Serial.println("Schlüssel wurde zurückgegeben!");
        pendingVerification = false;
        sendKeyReturnedEvent();
      }
    }
  }

  if (pendingVerification && (millis() - verificationStartTime > verificationTimeout)) {
    Serial.println("Verifikationszeit abgelaufen! Schlüssel gilt als unrechtmäßig entnommen.");
    pendingVerification = false;
  }

  if (wifiAvailable && WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠️ WiFi-Verbindung verloren.");
    wifiAvailable = false;
    WiFi.disconnect(true);
    nextWifiAttempt = millis() + 1000;
  }

  if (!wifiAvailable && millis() >= nextWifiAttempt) {
    wifiAvailable = connectToWiFi();
    nextWifiAttempt = millis() + WIFI_RETRY_INTERVAL;
  }

  if (!nfcAvailable && millis() >= nextNfcAttempt) {
    nfcAvailable = initializePN532(1);
    nextNfcAttempt = millis() + NFC_RETRY_INTERVAL;
  }

  if (nfcAvailable) {
    uint8_t uid[7];
    uint8_t uidLength;
    bool success = nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, &uidLength, 50);

    if (success) {
      String rfidUid;
      for (uint8_t i = 0; i < uidLength; i++) {
        if (uid[i] < 0x10) {
          rfidUid += '0';
        }
        rfidUid += String(uid[i], HEX);
      }
      rfidUid.toUpperCase();

      Serial.print("Tag erkannt, UID: ");
      Serial.println(rfidUid);

      // PRIORITÄT 1: Ausstehende Verifikation (Schlüssel wurde entnommen)
      if (pendingVerification && !keyPresent) {
        Serial.println("RFID-Scan - Verifikation für Schlüsselentnahme...");
        bool authorized = sendRfidAuthRequest(rfidUid);
        
        if (authorized) {
          Serial.println("✅ Benutzer autorisiert - Entnahme wird verifiziert");
          sendRfidScanEvent(rfidUid);
          pendingVerification = false;
          startSuccessLED();
          // Box bleibt offen - Auto-Close Timer läuft weiter

          // WICHTIG: 3 Sekunden Pause, damit der gleiche Scan nicht sofort als "Schließen"-Befehl (Priorität 2) erkannt wird
          delay(3000);
        } else {
          Serial.println("❌ Benutzer nicht autorisiert - Verifikation fehlgeschlagen");
        }
      }
      // PRIORITÄT 2: Normale Tür-Öffner/Schließer Funktion
      else {
        Serial.println("RFID-Scan - Server wird um Freigabe gebeten...");
        bool authorized = sendRfidAuthRequest(rfidUid);
        
        if (authorized) {
          Serial.println("✅ Benutzer autorisiert!");
          
          // Toggle: Box öffnen ODER schließen
          if (isLocked) {
            Serial.println("→ Box wird geöffnet");
            openLock();
          } else {
            Serial.println("→ Box wird geschlossen");
            closeLock();
          }
          
          startSuccessLED();

          // WICHTIG: 3 Sekunden Pause, um mehrfaches Auslösen (Auf/Zu) bei langem Vorhalten zu verhindern
          delay(3000);
        } else {
          Serial.println("❌ Benutzer nicht autorisiert - Keine Aktion");
        }
      }
    }
  }

  delay(5);
}

// Servo-Steuerung: Box öffnen
void openLock() {
  if (isLocked) {
    Serial.println("🔓 Box öffnet - Servo dreht auf 0°");
    lockServo.write(0);
    isLocked = false;
    autoCloseActive = true;
    servoOpenTime = millis();
    delay(500);  // Servo Zeit geben, Position zu erreichen
  }
}

// Servo-Steuerung: Box schließen
void closeLock() {
  if (!isLocked) {
    Serial.println("🔒 Box schließt - Servo dreht auf 90°");
    lockServo.write(90);
    isLocked = true;
    autoCloseActive = false;
    delay(500);  // Servo Zeit geben, Position zu erreichen
  }
}

// Auto-Close Timer: Box schließt nach 60 Sekunden automatisch
void manageAutoClose() {
  if (autoCloseActive && (millis() - servoOpenTime >= AUTO_CLOSE_DELAY)) {
    Serial.println("⏰ Auto-Close: 1 Minute abgelaufen - Box wird geschlossen");
    closeLock();
  }
}

void logResetReason() {
  esp_reset_reason_t reason = esp_reset_reason();
  Serial.print("Reset-Grund: ");
  switch (reason) {
    case ESP_RST_POWERON:
      Serial.println("Power-On Reset (Stromzufuhr)");
      break;
    case ESP_RST_SW:
      Serial.println("Software Reset");
      break;
    case ESP_RST_BROWNOUT:
      Serial.println("Brownout Reset (Unterspannung)");
      break;
    case ESP_RST_DEEPSLEEP:
      Serial.println("Aufwachen aus Deep Sleep");
      break;
    default:
      Serial.println(reason);
      break;
  }
}

bool connectToWiFi(uint8_t maxAttempts, unsigned long attemptTimeout) {
  WiFi.mode(WIFI_STA);

  for (uint8_t attempt = 1; attempt <= maxAttempts; ++attempt) {
    Serial.printf("📡 WiFi-Verbindungsversuch %u/%u\n", attempt, maxAttempts);
    WiFi.begin(ssid, password);

    unsigned long start = millis();
    while (WiFi.status() != WL_CONNECTED && (millis() - start) < attemptTimeout) {
      delay(200);
      Serial.print('.');
    }
    Serial.println();

    if (WiFi.status() == WL_CONNECTED) {
      Serial.print("✅ WiFi verbunden, IP: ");
      Serial.println(WiFi.localIP());
      return true;
    }

    Serial.println("❌ WiFi-Verbindung fehlgeschlagen");
    WiFi.disconnect(true);
    delay(500);
  }

  Serial.println("⚠️ WiFi offline – System arbeitet lokal weiter.");
  return false;
}

bool initializePN532(uint8_t maxAttempts) {
  for (uint8_t attempt = 1; attempt <= maxAttempts; ++attempt) {
    Serial.printf("🔧 NFC-Initialisierung Versuch %u/%u\n", attempt, maxAttempts);
    nfc.begin();
    delay(700);

    uint32_t versiondata = nfc.getFirmwareVersion();
    if (versiondata) {
      Serial.print("✔️ PN5");
      Serial.println((versiondata >> 24) & 0xFF, HEX);
      Serial.print("   Firmware Version: ");
      Serial.print((versiondata >> 16) & 0xFF, DEC);
      Serial.print('.');
      Serial.println((versiondata >> 8) & 0xFF, DEC);
      nfc.SAMConfig();
      Serial.println("NFC erfolgreich initialisiert.");
      return true;
    }

    Serial.println("❌ PN532 nicht erreichbar – erneuter Versuch folgt.");
    delay(800);
  }

  Serial.println("⚠️ NFC offline – RFID-Scans werden übersprungen.");
  return false;
}

void blinkStartupLED() {
  const uint8_t blinkCount = 2;
  const unsigned long blinkDuration = 120;
  for (uint8_t i = 0; i < blinkCount; ++i) {
    digitalWrite(LED_PIN, HIGH);
    delay(blinkDuration);
    digitalWrite(LED_PIN, LOW);
    delay(blinkDuration);
  }
}

void startSuccessLED() {
  if (!ledActive) {
    Serial.println("✅ LED: Erfolgreiche Verifikation - LED startet für 3 Sekunden");
    digitalWrite(LED_PIN, HIGH);
  }
  ledActive = true;
  ledStartTime = millis();
}

void manageLED() {
  if (ledActive && (millis() - ledStartTime >= LED_DURATION)) {
    digitalWrite(LED_PIN, LOW);
    ledActive = false;
    Serial.println("💡 LED: Aus (3 Sekunden abgelaufen)");
  }
}

// RFID-Autorisierungsanfrage an Server
bool sendRfidAuthRequest(String rfidUid) {
  if (!wifiAvailable || WiFi.status() != WL_CONNECTED) {
    Serial.println("Keine WLAN-Verbindung – RFID-Autorisierung nicht möglich.");
    return false;
  }

  HTTPClient http;
  http.begin(API_ENDPOINT);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Api-Key", API_KEY);

  StaticJsonDocument<200> doc;
  doc["event_type"] = "rfid_auth_request";
  doc["seriennummer"] = seriennummer;
  doc["rfid_uid"] = rfidUid;
  doc["timestamp"] = millis();

  String jsonData;
  serializeJson(doc, jsonData);

  int httpResponseCode = http.POST(jsonData);

  bool authorized = false;
  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("HTTP Response: " + response);

    // Parse JSON response
    StaticJsonDocument<200> responseDoc;
    DeserializationError error = deserializeJson(responseDoc, response);

    if (!error && responseDoc.containsKey("authorized")) {
      authorized = responseDoc["authorized"];
    }
  } else {
    Serial.print("Fehler beim HTTP-Request: ");
    Serial.println(httpResponseCode);
  }

  http.end();
  return authorized;
}

void sendKeyRemovedEvent() {
  if (!wifiAvailable || WiFi.status() != WL_CONNECTED) {
    Serial.println("Keine WLAN-Verbindung – Event 'key_removed' wird nicht gesendet.");
    return;
  }

  HTTPClient http;
  http.begin(API_ENDPOINT);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Api-Key", API_KEY);

  StaticJsonDocument<200> doc;
  doc["event_type"] = "key_removed";
  doc["seriennummer"] = seriennummer;
  doc["timestamp"] = millis();

  String jsonData;
  serializeJson(doc, jsonData);

  int httpResponseCode = http.POST(jsonData);

  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("HTTP Response: " + response);
  } else {
    Serial.print("Fehler beim HTTP-Request: ");
    Serial.println(httpResponseCode);
  }

  http.end();
}

void sendKeyReturnedEvent() {
  if (!wifiAvailable || WiFi.status() != WL_CONNECTED) {
    Serial.println("Keine WLAN-Verbindung – Event 'key_returned' wird nicht gesendet.");
    return;
  }

  HTTPClient http;
  http.begin(API_ENDPOINT);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Api-Key", API_KEY);

  StaticJsonDocument<200> doc;
  doc["event_type"] = "key_returned";
  doc["seriennummer"] = seriennummer;
  doc["timestamp"] = millis();

  String jsonData;
  serializeJson(doc, jsonData);

  int httpResponseCode = http.POST(jsonData);

  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("HTTP Response: " + response);
  } else {
    Serial.print("Fehler beim HTTP-Request: ");
    Serial.println(httpResponseCode);
  }

  http.end();
}

void sendRfidScanEvent(String rfidUid) {
  if (!wifiAvailable || WiFi.status() != WL_CONNECTED) {
    Serial.println("Keine WLAN-Verbindung – RFID-Scan wird nicht gesendet.");
    return;
  }

  HTTPClient http;
  http.begin(API_ENDPOINT);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Api-Key", API_KEY);

  StaticJsonDocument<200> doc;
  doc["event_type"] = "rfid_scan";
  doc["seriennummer"] = seriennummer;
  doc["rfid_uid"] = rfidUid;
  doc["timestamp"] = millis();

  String jsonData;
  serializeJson(doc, jsonData);

  int httpResponseCode = http.POST(jsonData);

  if (httpResponseCode > 0) {
    String response = http.getString();
    Serial.println("HTTP Response: " + response);
  } else {
    Serial.print("Fehler beim HTTP-Request: ");
    Serial.println(httpResponseCode);
  }

  http.end();
}

void checkForResetCommand() {
  if (!wifiAvailable || WiFi.status() != WL_CONNECTED) {
    return;
  }

  HTTPClient http;
  String checkUrl = String(API_ENDPOINT) + "?action=check_reset&seriennummer=" + seriennummer;
  http.begin(checkUrl);
  http.addHeader("X-Api-Key", API_KEY);

  int httpResponseCode = http.GET();

  if (httpResponseCode == 200) {
    String response = http.getString();
    StaticJsonDocument<200> doc;
    DeserializationError error = deserializeJson(doc, response);

    if (!error && doc["reset"] == true) {
      Serial.println("🔄 Reset-Befehl empfangen vom Server!");
      Serial.println("Neustart wird in 2 Sekunden eingeleitet...");
      
      // LED kurz blinken lassen als Feedback
      for (int i = 0; i < 3; i++) {
        digitalWrite(LED_PIN, HIGH);
        delay(200);
        digitalWrite(LED_PIN, LOW);
        delay(200);
      }
      
      http.end();
      delay(2000);
      ESP.restart(); // Hardware-Neustart
    }
  } else if (httpResponseCode > 0) {
    Serial.print("Reset-Check HTTP Response Code: ");
    Serial.println(httpResponseCode);
  }

  http.end();
}