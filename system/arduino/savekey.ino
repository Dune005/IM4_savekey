#include <Wire.h>
#include <Adafruit_PN532.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include <esp_system.h>

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
const int LED_PIN = 10; // Deine neue LED an Pin 10

// --- Seriennummer der Box ---
const char* seriennummer = "550";

// --- Status-Variablen ---
bool keyPresent = true;
bool pendingVerification = false;
unsigned long verificationStartTime = 0;
const unsigned long verificationTimeout = 5 * 60 * 1000; // 5 Minuten

// --- LED-Timer Variablen (non-blocking) ---
bool ledActive = false;
unsigned long ledStartTime = 0;
const unsigned long LED_DURATION = 3000; // 3 Sekunden LED-Dauer

// --- Verbindungszustände & Retry-Timer ---
bool wifiAvailable = false;
bool nfcAvailable = false;
unsigned long nextWifiAttempt = 0;
unsigned long nextNfcAttempt = 0;
const unsigned long WIFI_RETRY_INTERVAL = 30000UL; // 30 Sekunden
const unsigned long NFC_RETRY_INTERVAL = 15000UL;  // 15 Sekunden

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

void setup() {
  Serial.begin(115200);
  delay(200); // Seriellen Monitor nicht blockieren

  logResetReason();

  delay(2000); // Hardware nach Power-On stabilisieren lassen

  pinMode(LED_PIN, OUTPUT);
  digitalWrite(LED_PIN, LOW);

  pinMode(buttonPin, INPUT_PULLDOWN);

  Wire.begin(SDA_PIN, SCL_PIN);
  delay(500); // I2C/PN532 Zeit geben

  wifiAvailable = connectToWiFi();
  nextWifiAttempt = millis() + WIFI_RETRY_INTERVAL;

  nfcAvailable = initializePN532();
  nextNfcAttempt = millis() + NFC_RETRY_INTERVAL;

  keyPresent = (digitalRead(buttonPin) == HIGH);
  Serial.print("Initialer Schlüsselstatus: ");
  Serial.println(keyPresent ? "Vorhanden" : "Entfernt");
  blinkStartupLED();
  Serial.println("✅ System bereit - LED leuchtet nur bei erfolgreicher Verifikation");
}

void loop() {
  manageLED();

  static unsigned long lastButtonCheck = 0;
  unsigned long now = millis();
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

      if (pendingVerification && !keyPresent) {
        Serial.println("RFID-Verifikation für Schlüsselentnahme!");
        startSuccessLED();
        sendRfidScanEvent(rfidUid);
        pendingVerification = false;
      }
    }
  }

  delay(5);
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
    delay(700); // PN532 ausreichend Zeit geben

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

// LED für erfolgreiche Verifikation starten (non-blocking)
void startSuccessLED() {
  if (!ledActive) {
    Serial.println("✅ LED: Erfolgreiche Verifikation - LED startet für 3 Sekunden");
    digitalWrite(LED_PIN, HIGH);
  }
  ledActive = true;
  ledStartTime = millis();
}

// LED-Timer verwalten (non-blocking)
void manageLED() {
  if (ledActive && (millis() - ledStartTime >= LED_DURATION)) {
    digitalWrite(LED_PIN, LOW);
    ledActive = false;
    Serial.println("💡 LED: Aus (3 Sekunden abgelaufen)");
  }
}

// Sendet ein Ereignis "Schlüssel entfernt" an den Server
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

// Sendet ein Ereignis "Schlüssel zurückgegeben" an den Server
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

// Sendet ein Ereignis "RFID-Scan" an den Server
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