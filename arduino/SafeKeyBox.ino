/*
 * SafeKeyBox.ino - Arduino-Code für die Schlüsselbox mit RFID/NFC
 * 
 * Dieses Programm steuert eine Schlüsselbox mit folgenden Funktionen:
 * - Erkennung, ob der Schlüssel vorhanden ist (Magnet-Sensor)
 * - RFID/NFC-Leser zur Benutzeridentifikation
 * - WiFi-Verbindung zur Kommunikation mit dem Server
 * - LED-Anzeige für den Status
 * 
 * Hardware:
 * - Arduino (z.B. ESP8266 oder ESP32 für WiFi)
 * - RFID/NFC-Leser (z.B. MFRC522)
 * - Magnet-Sensor (z.B. Reed-Schalter oder Hall-Sensor)
 * - LEDs (rot, grün, blau)
 * - Optional: Display
 */

// Bibliotheken einbinden
#include <SPI.h>
#include <MFRC522.h>  // RFID/NFC-Leser
#include <ESP8266WiFi.h>  // Für ESP8266, für ESP32 <WiFi.h> verwenden
#include <ESP8266HTTPClient.h>  // Für ESP8266, für ESP32 <HTTPClient.h> verwenden
#include <ArduinoJson.h>  // JSON-Verarbeitung

// Pins definieren
#define RST_PIN         D3  // Reset-Pin für RFID/NFC-Leser
#define SS_PIN          D4  // SDA/SS-Pin für RFID/NFC-Leser
#define MAGNET_SENSOR   D2  // Pin für den Magnet-Sensor
#define LED_RED         D8  // Pin für rote LED
#define LED_GREEN       D7  // Pin für grüne LED
#define LED_BLUE        D6  // Pin für blaue LED

// WLAN-Konfiguration
const char* ssid = "WLAN_NAME";  // WLAN-Name
const char* password = "WLAN_PASSWORT";  // WLAN-Passwort

// Server-Konfiguration
const char* serverUrl = "http://example.com/api/hardware_event.php";  // URL zum Server
const char* apiKey = "sk_hardware_XXXXXXXXXXXX";  // API-Schlüssel (aus hardware_auth.php)
const char* seriennummer = "BOX001";  // Seriennummer der Schlüsselbox

// Globale Variablen
MFRC522 rfid(SS_PIN, RST_PIN);  // RFID/NFC-Leser-Objekt
bool keyPresent = false;  // Ist der Schlüssel in der Box?
bool lastKeyPresent = false;  // Letzter bekannter Zustand des Schlüssels
unsigned long lastRfidCheck = 0;  // Zeitpunkt der letzten RFID/NFC-Prüfung
bool waitingForRfid = false;  // Warten wir auf eine RFID/NFC-Identifikation?

void setup() {
  // Serielle Kommunikation initialisieren
  Serial.begin(115200);
  Serial.println("\nSafeKeyBox - Initialisierung...");
  
  // Pins konfigurieren
  pinMode(MAGNET_SENSOR, INPUT_PULLUP);  // Magnet-Sensor mit Pull-up-Widerstand
  pinMode(LED_RED, OUTPUT);
  pinMode(LED_GREEN, OUTPUT);
  pinMode(LED_BLUE, OUTPUT);
  
  // LEDs ausschalten
  digitalWrite(LED_RED, LOW);
  digitalWrite(LED_GREEN, LOW);
  digitalWrite(LED_BLUE, LOW);
  
  // RFID/NFC-Leser initialisieren
  SPI.begin();
  rfid.PCD_Init();
  Serial.print("RFID/NFC-Leser initialisiert. Version: ");
  rfid.PCD_DumpVersionToSerial();
  
  // Mit WLAN verbinden
  connectToWiFi();
  
  // Initialen Schlüsselstatus prüfen
  keyPresent = isKeyPresent();
  lastKeyPresent = keyPresent;
  
  // Status-LED aktualisieren
  updateStatusLED();
  
  Serial.println("Initialisierung abgeschlossen.");
}

void loop() {
  // WLAN-Verbindung prüfen und ggf. wiederherstellen
  if (WiFi.status() != WL_CONNECTED) {
    connectToWiFi();
  }
  
  // Schlüsselstatus prüfen
  keyPresent = isKeyPresent();
  
  // Wenn sich der Schlüsselstatus geändert hat
  if (keyPresent != lastKeyPresent) {
    if (keyPresent) {
      // Schlüssel wurde zurückgegeben
      Serial.println("Schlüssel wurde zurückgegeben");
      sendKeyReturned();
      waitingForRfid = false;
    } else {
      // Schlüssel wurde entnommen
      Serial.println("Schlüssel wurde entnommen");
      sendKeyRemoved();
      waitingForRfid = true;
      lastRfidCheck = millis();
    }
    
    lastKeyPresent = keyPresent;
    updateStatusLED();
  }
  
  // RFID/NFC-Karte prüfen, wenn wir auf eine Identifikation warten
  if (waitingForRfid) {
    checkRfidCard();
    
    // Blaue LED blinken lassen, um anzuzeigen, dass wir auf RFID/NFC warten
    if ((millis() / 500) % 2 == 0) {
      digitalWrite(LED_BLUE, HIGH);
    } else {
      digitalWrite(LED_BLUE, LOW);
    }
  }
  
  // Kurze Pause
  delay(100);
}

// Prüft, ob der Schlüssel in der Box ist
bool isKeyPresent() {
  // Bei einem Reed-Schalter oder Hall-Sensor:
  // LOW = Magnet vorhanden (Schlüssel in der Box)
  // HIGH = Kein Magnet (Schlüssel nicht in der Box)
  return digitalRead(MAGNET_SENSOR) == LOW;
}

// Verbindet mit dem WLAN
void connectToWiFi() {
  Serial.print("Verbinde mit WLAN ");
  Serial.print(ssid);
  
  // Blaue LED einschalten während der Verbindung
  digitalWrite(LED_BLUE, HIGH);
  
  // Mit WLAN verbinden
  WiFi.begin(ssid, password);
  
  // Warten bis verbunden oder Timeout
  int timeout = 0;
  while (WiFi.status() != WL_CONNECTED && timeout < 20) {
    delay(500);
    Serial.print(".");
    timeout++;
  }
  
  // Blaue LED ausschalten
  digitalWrite(LED_BLUE, LOW);
  
  if (WiFi.status() == WL_CONNECTED) {
    Serial.println(" verbunden!");
    Serial.print("IP-Adresse: ");
    Serial.println(WiFi.localIP());
  } else {
    Serial.println(" Verbindung fehlgeschlagen!");
  }
}

// Aktualisiert die Status-LED
void updateStatusLED() {
  if (keyPresent) {
    // Grüne LED ein, andere aus
    digitalWrite(LED_GREEN, HIGH);
    digitalWrite(LED_RED, LOW);
    if (!waitingForRfid) {
      digitalWrite(LED_BLUE, LOW);
    }
  } else {
    // Rote LED ein, grüne aus
    digitalWrite(LED_GREEN, LOW);
    digitalWrite(LED_RED, HIGH);
  }
}

// Sendet eine Nachricht an den Server, dass der Schlüssel entnommen wurde
void sendKeyRemoved() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Keine WLAN-Verbindung. Kann Schlüsselentnahme nicht senden.");
    return;
  }
  
  HTTPClient http;
  http.begin(serverUrl);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Api-Key", apiKey);
  
  // JSON-Daten erstellen
  StaticJsonDocument<200> doc;
  doc["event_type"] = "key_removed";
  doc["seriennummer"] = seriennummer;
  
  String jsonData;
  serializeJson(doc, jsonData);
  
  // Anfrage senden
  int httpCode = http.POST(jsonData);
  
  if (httpCode > 0) {
    String payload = http.getString();
    Serial.println("Server-Antwort: " + payload);
  } else {
    Serial.println("Fehler bei der HTTP-Anfrage: " + http.errorToString(httpCode));
  }
  
  http.end();
}

// Sendet eine Nachricht an den Server, dass der Schlüssel zurückgegeben wurde
void sendKeyReturned() {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Keine WLAN-Verbindung. Kann Schlüsselrückgabe nicht senden.");
    return;
  }
  
  HTTPClient http;
  http.begin(serverUrl);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Api-Key", apiKey);
  
  // JSON-Daten erstellen
  StaticJsonDocument<200> doc;
  doc["event_type"] = "key_returned";
  doc["seriennummer"] = seriennummer;
  
  String jsonData;
  serializeJson(doc, jsonData);
  
  // Anfrage senden
  int httpCode = http.POST(jsonData);
  
  if (httpCode > 0) {
    String payload = http.getString();
    Serial.println("Server-Antwort: " + payload);
  } else {
    Serial.println("Fehler bei der HTTP-Anfrage: " + http.errorToString(httpCode));
  }
  
  http.end();
}

// Prüft, ob eine RFID/NFC-Karte vorhanden ist und sendet die UID an den Server
void checkRfidCard() {
  // Nur prüfen, wenn keine Karte erkannt wurde oder nach einer Pause
  if (millis() - lastRfidCheck < 500) {
    return;
  }
  
  lastRfidCheck = millis();
  
  // Prüfen, ob eine neue Karte vorhanden ist
  if (!rfid.PICC_IsNewCardPresent()) {
    return;
  }
  
  // UID der Karte lesen
  if (!rfid.PICC_ReadCardSerial()) {
    return;
  }
  
  // UID als String formatieren
  String uid = "";
  for (byte i = 0; i < rfid.uid.size; i++) {
    uid += (rfid.uid.uidByte[i] < 0x10 ? "0" : "");
    uid += String(rfid.uid.uidByte[i], HEX);
  }
  uid.toUpperCase();
  
  Serial.print("RFID/NFC-Karte erkannt: ");
  Serial.println(uid);
  
  // UID an den Server senden
  sendRfidUid(uid);
  
  // RFID/NFC-Karte stoppen
  rfid.PICC_HaltA();
  rfid.PCD_StopCrypto1();
}

// Sendet die RFID/NFC-UID an den Server
void sendRfidUid(String uid) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("Keine WLAN-Verbindung. Kann RFID/NFC-UID nicht senden.");
    return;
  }
  
  HTTPClient http;
  http.begin(serverUrl);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Api-Key", apiKey);
  
  // JSON-Daten erstellen
  StaticJsonDocument<200> doc;
  doc["event_type"] = "rfid_scan";
  doc["seriennummer"] = seriennummer;
  doc["rfid_uid"] = uid;
  
  String jsonData;
  serializeJson(doc, jsonData);
  
  // Anfrage senden
  int httpCode = http.POST(jsonData);
  
  if (httpCode > 0) {
    String payload = http.getString();
    Serial.println("Server-Antwort: " + payload);
    
    // Prüfen, ob die Anfrage erfolgreich war
    StaticJsonDocument<512> response;
    DeserializationError error = deserializeJson(response, payload);
    
    if (!error && response["status"] == "success") {
      // Erfolgreiche Identifikation
      Serial.println("RFID/NFC-Identifikation erfolgreich");
      
      // Grüne und blaue LED kurz blinken lassen
      for (int i = 0; i < 3; i++) {
        digitalWrite(LED_GREEN, HIGH);
        digitalWrite(LED_BLUE, HIGH);
        delay(200);
        digitalWrite(LED_GREEN, LOW);
        digitalWrite(LED_BLUE, LOW);
        delay(200);
      }
      
      // Nicht mehr auf RFID/NFC warten
      waitingForRfid = false;
      updateStatusLED();
    } else {
      // Fehler bei der Identifikation
      Serial.println("RFID/NFC-Identifikation fehlgeschlagen");
      
      // Rote LED kurz blinken lassen
      for (int i = 0; i < 3; i++) {
        digitalWrite(LED_RED, HIGH);
        delay(200);
        digitalWrite(LED_RED, LOW);
        delay(200);
      }
      
      // Weiterhin auf RFID/NFC warten
      updateStatusLED();
    }
  } else {
    Serial.println("Fehler bei der HTTP-Anfrage: " + http.errorToString(httpCode));
  }
  
  http.end();
}
