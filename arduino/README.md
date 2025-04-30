# SafeKeyBox - Arduino-Integration

Diese Dokumentation beschreibt die Integration eines Arduino-basierten Systems mit der SafeKeyBox-Webanwendung.

## Übersicht

Das Arduino-System überwacht den Status eines Schlüssels in einer Schlüsselbox und kommuniziert mit dem Server, um Entnahmen und Rückgaben zu protokollieren. Die Benutzeridentifikation erfolgt über RFID/NFC-Chips.

## Hardware-Anforderungen

- **Mikrocontroller**: Arduino mit WLAN-Fähigkeit (z.B. ESP8266 oder ESP32)
- **RFID/NFC-Leser**: MFRC522 oder kompatibel
- **Magnet-Sensor**: Reed-Schalter oder Hall-Sensor zur Erkennung des Schlüssels
- **LEDs**: Rot, Grün und Blau zur Statusanzeige
- **Optional**: Display zur Anzeige von Informationen

## Schaltplan

```
+---------------+
|   ESP8266     |
|               |
| D2 ---------> Magnet-Sensor
| D3 ---------> RST (MFRC522)
| D4 ---------> SDA/SS (MFRC522)
| D5 ---------> SCK (MFRC522)
| D6 ---------> LED Blau
| D7 ---------> LED Grün
| D8 ---------> LED Rot
| D9/MISO <---- MISO (MFRC522)
| D10/MOSI ----> MOSI (MFRC522)
| 3.3V --------> VCC (MFRC522, Magnet-Sensor)
| GND ---------> GND (MFRC522, Magnet-Sensor, LEDs)
+---------------+
```

## Software-Installation

1. Installieren Sie die Arduino IDE von [arduino.cc](https://www.arduino.cc/en/software)
2. Installieren Sie die folgenden Bibliotheken über den Bibliotheksverwalter:
   - MFRC522 (für den RFID/NFC-Leser)
   - ArduinoJson (für die JSON-Verarbeitung)
   - ESP8266WiFi oder WiFi (je nach verwendetem Mikrocontroller)
   - ESP8266HTTPClient oder HTTPClient (je nach verwendetem Mikrocontroller)

## Konfiguration

Bevor Sie den Code auf den Arduino hochladen, müssen Sie folgende Einstellungen anpassen:

1. WLAN-Konfiguration:
   ```cpp
   const char* ssid = "WLAN_NAME";  // WLAN-Name
   const char* password = "WLAN_PASSWORT";  // WLAN-Passwort
   ```

2. Server-Konfiguration:
   ```cpp
   const char* serverUrl = "http://example.com/api/hardware_event.php";  // URL zum Server
   const char* apiKey = "sk_hardware_XXXXXXXXXXXX";  // API-Schlüssel (aus hardware_auth.php)
   const char* seriennummer = "BOX001";  // Seriennummer der Schlüsselbox
   ```

   Den API-Schlüssel finden Sie in der Datei `system/hardware_auth.php` auf dem Server.

3. Pin-Konfiguration (falls abweichend):
   ```cpp
   #define RST_PIN         D3  // Reset-Pin für RFID/NFC-Leser
   #define SS_PIN          D4  // SDA/SS-Pin für RFID/NFC-Leser
   #define MAGNET_SENSOR   D2  // Pin für den Magnet-Sensor
   #define LED_RED         D8  // Pin für rote LED
   #define LED_GREEN       D7  // Pin für grüne LED
   #define LED_BLUE        D6  // Pin für blaue LED
   ```

## Funktionsweise

1. **Initialisierung**:
   - Verbindung mit dem WLAN
   - Initialisierung des RFID/NFC-Lesers
   - Prüfung des initialen Schlüsselstatus

2. **Schlüsselüberwachung**:
   - Kontinuierliche Überwachung des Magnet-Sensors
   - Bei Entnahme des Schlüssels: Senden einer "key_removed"-Nachricht an den Server
   - Bei Rückgabe des Schlüssels: Senden einer "key_returned"-Nachricht an den Server

3. **RFID/NFC-Identifikation**:
   - Nach Entnahme des Schlüssels: Warten auf RFID/NFC-Scan
   - Bei Erkennung eines RFID/NFC-Chips: Senden der UID an den Server
   - Server prüft, ob der Benutzer berechtigt ist und protokolliert die Entnahme

4. **Statusanzeige**:
   - Grüne LED: Schlüssel ist in der Box
   - Rote LED: Schlüssel ist nicht in der Box
   - Blaue LED (blinkend): Warten auf RFID/NFC-Identifikation
   - Blaue + Grüne LED (blinkend): Erfolgreiche RFID/NFC-Identifikation
   - Rote LED (blinkend): Fehler bei der RFID/NFC-Identifikation

## API-Endpunkte

Der Arduino kommuniziert mit dem Server über die folgenden API-Endpunkte:

### 1. Schlüsselentnahme

```
POST /api/hardware_event.php
Header: X-Api-Key: sk_hardware_XXXXXXXXXXXX
Content-Type: application/json

{
  "event_type": "key_removed",
  "seriennummer": "BOX001"
}
```

### 2. Schlüsselrückgabe

```
POST /api/hardware_event.php
Header: X-Api-Key: sk_hardware_XXXXXXXXXXXX
Content-Type: application/json

{
  "event_type": "key_returned",
  "seriennummer": "BOX001"
}
```

### 3. RFID/NFC-Scan

```
POST /api/hardware_event.php
Header: X-Api-Key: sk_hardware_XXXXXXXXXXXX
Content-Type: application/json

{
  "event_type": "rfid_scan",
  "seriennummer": "BOX001",
  "rfid_uid": "ABCD1234"
}
```

## Fehlerbehebung

### WLAN-Verbindungsprobleme

- Überprüfen Sie SSID und Passwort
- Stellen Sie sicher, dass der Router erreichbar ist
- Prüfen Sie, ob der Arduino die richtige WLAN-Bibliothek verwendet

### RFID/NFC-Leseprobleme

- Überprüfen Sie die Verkabelung des RFID/NFC-Lesers
- Stellen Sie sicher, dass die MFRC522-Bibliothek korrekt installiert ist
- Testen Sie den RFID/NFC-Leser mit einem einfachen Beispielcode

### Server-Kommunikationsprobleme

- Überprüfen Sie die Server-URL
- Stellen Sie sicher, dass der API-Schlüssel korrekt ist
- Prüfen Sie, ob der Server erreichbar ist
- Überprüfen Sie die Firewall-Einstellungen

## Erweiterungsmöglichkeiten

- **Display**: Hinzufügen eines OLED- oder LCD-Displays zur Anzeige von Informationen
- **Akustisches Feedback**: Hinzufügen eines Summers für akustische Signale
- **Batteriebetrieb**: Implementierung von Energiesparfunktionen für Batteriebetrieb
- **Mehrere Schlüssel**: Erweiterung des Systems für die Überwachung mehrerer Schlüssel
- **Biometrische Authentifizierung**: Integration eines Fingerabdrucksensors als Alternative zu RFID/NFC
