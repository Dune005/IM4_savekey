#include <Wire.h>
#include <Adafruit_PN532.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>

// --- WLAN-Credentials ---
const char* ssid     = "tinkergarden";
const char* password = "strenggeheim";

// --- API-Konfiguration ---
const char* API_ENDPOINT = "https://savekey.klaus-klebband.ch/api/arduino_api.php"; // Ersetze mit deiner tatsächlichen Domain
const char* API_KEY = "sk_hardware_safekey_12345"; // Muss mit dem Wert in system/hardware_auth.php übereinstimmen

// --- I²C-Pins für PN532 ---
#define SDA_PIN     6
#define SCL_PIN     7

// --- PN532 / NFC-Pins ---
#define PN532_IRQ   2
#define PN532_RESET 3

// --- Magnetsensor-Pin ---
const int buttonPin = 8;

// --- Seriennummer der Box ---
// WICHTIG: Diese Seriennummer muss mit der Seriennummer in der Datenbank übereinstimmen,
// die dem Benutzer zugeordnet ist, der diese Box verwenden soll.
// Beispiele aus der Datenbank: Seriennummern beginnen mit 'A' oder 'B', z.B. 'A001', 'B002'
const char* seriennummer = "550"; // Eindeutige Seriennummer für diese Box

// --- Status-Variablen ---
bool keyPresent = true;
bool pendingVerification = false;
unsigned long verificationStartTime = 0;
const unsigned long verificationTimeout = 5 * 60 * 1000; // 5 Minuten in Millisekunden

// PN532-Objekt über Wire
Adafruit_PN532 nfc(PN532_IRQ, PN532_RESET, &Wire);

void setup() {
  Serial.begin(115200);
  while (!Serial) delay(10);

  // 1) WLAN verbinden
  Serial.printf("Connecting to WiFi '%s' …\n", ssid);
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.println("✅ WiFi connected!");
  Serial.print("🔗 IP address: ");
  Serial.println(WiFi.localIP());

  // 2) I²C für PN532 initialisieren
  Wire.begin(SDA_PIN, SCL_PIN);
  Serial.println("I2C started on SDA=6, SCL=7");

  // 3) PN532 initialisieren
  nfc.begin();
  uint32_t versiondata = nfc.getFirmwareVersion();
  if (!versiondata) {
    Serial.println("❌ Kein PN532 gefunden – Verbindung prüfen.");
    while (1);
  }
  Serial.print("✔️ PN5"); Serial.println((versiondata >> 24) & 0xFF, HEX);
  Serial.print("   Firmware Version: ");
  Serial.print((versiondata >> 16) & 0xFF, DEC);
  Serial.print('.');
  Serial.println((versiondata >> 8) & 0xFF, DEC);
  nfc.SAMConfig();
  Serial.println("Warte auf ein RFID/NFC Tag...");

  // 4) Magnetsensor-Pin als Input
  pinMode(buttonPin, INPUT_PULLDOWN);

  // 5) Initialen Zustand des Schlüssels prüfen
  keyPresent = (digitalRead(buttonPin) == 1); // 1 = Schlüssel hängt, 0 = Schlüssel entfernt
  Serial.print("Initialer Schlüsselstatus: ");
  Serial.println(keyPresent ? "Vorhanden" : "Entfernt");
}

void loop() {
  // --- Magnet­sensor (alle 10 ms) ---
  static unsigned long lastButtonCheck = 0;
  unsigned long now = millis();
  if (now - lastButtonCheck >= 10) {
    lastButtonCheck = now;
    int state = digitalRead(buttonPin);
    bool currentKeyPresent = (state == 1); // 1 = Schlüssel hängt, 0 = Schlüssel entfernt

    // Wenn sich der Schlüsselstatus geändert hat
    if (currentKeyPresent != keyPresent) {
      keyPresent = currentKeyPresent;

      if (!keyPresent) {
        // Schlüssel wurde entfernt
        Serial.println("Schlüssel wurde entfernt!");
        pendingVerification = true;
        verificationStartTime = millis();

        // API-Aufruf für Schlüsselentnahme
        sendKeyRemovedEvent();
      } else {
        // Schlüssel wurde zurückgegeben
        Serial.println("Schlüssel wurde zurückgegeben!");
        pendingVerification = false;

        // API-Aufruf für Schlüsselrückgabe
        sendKeyReturnedEvent();
      }
    }
  }

  // Prüfen, ob die Verifikationszeit abgelaufen ist
  if (pendingVerification && (millis() - verificationStartTime > verificationTimeout)) {
    Serial.println("Verifikationszeit abgelaufen! Schlüssel gilt als unrechtmäßig entnommen.");
    pendingVerification = false;
    // Hier könnte ein Alarm ausgelöst werden
  }

  // --- RFID/NFC nicht-blockierend (50 ms Timeout) ---
  uint8_t success;
  uint8_t uid[7];
  uint8_t uidLength;
  success = nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, &uidLength, 50);
  if (success) {
    // RFID-Tag erkannt
    String rfidUid = "";
    for (uint8_t i = 0; i < uidLength; i++) {
      char hex[3];
      sprintf(hex, "%02X", uid[i]);
      rfidUid += hex;
    }

    Serial.print("Tag erkannt, UID: ");
    Serial.println(rfidUid);

    // Wenn eine Verifikation aussteht und der Schlüssel entfernt wurde
    if (pendingVerification && !keyPresent) {
      Serial.println("RFID-Verifikation für Schlüsselentnahme!");
      sendRfidScanEvent(rfidUid);
      pendingVerification = false; // Verifikation abgeschlossen
    }
  }

  // Kurze Pause, um den Serial-Output nicht zu überfluten
  delay(5);
}

// Sendet ein Ereignis "Schlüssel entfernt" an den Server
void sendKeyRemovedEvent() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Keine WLAN-Verbindung!");
    return;
  }

  HTTPClient http;
  http.begin(API_ENDPOINT);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Api-Key", API_KEY);

  // JSON-Daten erstellen
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
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Keine WLAN-Verbindung!");
    return;
  }

  HTTPClient http;
  http.begin(API_ENDPOINT);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Api-Key", API_KEY);

  // JSON-Daten erstellen
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
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Keine WLAN-Verbindung!");
    return;
  }

  HTTPClient http;
  http.begin(API_ENDPOINT);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Api-Key", API_KEY);

  // JSON-Daten erstellen
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