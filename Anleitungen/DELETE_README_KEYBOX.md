# SaveKey Box System

Dieses System besteht aus einer Arduino-basierten Schlüsselbox mit Magnetsensor und RFID/NFC-Leser sowie einer PHP-API, die die Kommunikation mit der Datenbank ermöglicht.

## Funktionsweise

1. Der Benutzer entnimmt einen Schlüssel aus der Box (Magnetsensor erkennt die Entnahme)
2. Die Box sendet ein "key_removed" Ereignis an den Server
3. Der Benutzer hat 5 Minuten Zeit, um sich mit seinem RFID/NFC-Chip zu verifizieren
4. Nach erfolgreicher Verifikation wird die Schlüsselentnahme in der Datenbank protokolliert
5. Bei Rückgabe des Schlüssels wird ein "key_returned" Ereignis gesendet und in der Datenbank vermerkt

## Installation

### Arduino-Setup

1. Installiere die erforderlichen Bibliotheken in der Arduino IDE:
   - WiFi (bereits in ESP32 enthalten)
   - HTTPClient (bereits in ESP32 enthalten)
   - ArduinoJson (über Bibliotheksverwalter installieren)
   - Adafruit_PN532 (über Bibliotheksverwalter installieren)

2. Passe die WLAN- und API-Daten direkt in der `arduino.ino` Datei an:
   ```cpp
   // --- WLAN-Credentials ---
   const char* ssid     = "dein_wlan_name";
   const char* password = "dein_wlan_passwort";

   // --- API-Konfiguration ---
   const char* API_ENDPOINT = "http://dein-server.com/api/arduino_api.php";
   const char* API_KEY = "dein_api_key";
   ```

3. Lade den Arduino-Code auf dein ESP32-Board hoch

### Server-Setup

1. Kopiere die Datei `api/arduino_api.php` in dein Webserver-Verzeichnis
2. Stelle sicher, dass die Datenbanktabellen `pending_key_actions` und `key_logs` existieren
3. Generiere einen API-Schlüssel in `system/hardware_auth.php` und verwende diesen in deiner Arduino-Konfiguration

## Hardware-Verbindungen

- **Magnetsensor**: An Pin 8 angeschlossen (LOW = Schlüssel vorhanden, HIGH = Schlüssel entfernt)
- **PN532 RFID/NFC-Leser**:
  - SDA: Pin 6
  - SCL: Pin 7
  - IRQ: Pin 2
  - RESET: Pin 3

## Sicherheitshinweise

1. **WLAN-Credentials und API-Schlüssel**: Für eine Produktionsumgebung solltest du diese Werte vor dem Hochladen des Codes ändern
2. **HTTPS**: Für Produktionsumgebungen sollte die Kommunikation über HTTPS erfolgen
3. **Physische Sicherheit**: Stelle sicher, dass der Arduino nicht leicht zugänglich ist, da der Code die Zugangsdaten enthält

## Fehlerbehebung

- **Arduino kann keine Verbindung zum WLAN herstellen**: Überprüfe die WLAN-Credentials in der arduino.ino Datei
- **Arduino kann keine Verbindung zum Server herstellen**: Überprüfe die API-Endpoint-URL und den API-Schlüssel
- **RFID/NFC-Leser wird nicht erkannt**: Überprüfe die Verkabelung und die I²C-Adresse

## Erweiterungsmöglichkeiten

- LED-Anzeige für den Status der Schlüsselbox
- Akustisches Signal bei erfolgreicher/fehlgeschlagener Verifikation
- Lokale Speicherung von Ereignissen bei fehlender Internetverbindung
- Verschlüsselung der Kommunikation zwischen Arduino und Server
