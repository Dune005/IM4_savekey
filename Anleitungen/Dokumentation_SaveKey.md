# SaveKey - Digitales Schlüsselüberwachungssystem

## Inhaltsverzeichnis
1. [Projektübersicht](#projektübersicht)
2. [Funktionsweise](#funktionsweise)
3. [Systemarchitektur](#systemarchitektur)
4. [Hardware-Komponenten](#hardware-komponenten)
5. [Software-Komponenten](#software-komponenten)
6. [Datenbank-Struktur](#datenbank-struktur)
7. [Benutzerrollen](#benutzerrollen)
8. [Installation und Einrichtung](#installation-und-einrichtung)
9. [Fehlerbehebung](#fehlerbehebung)
10. [Erweiterungsmöglichkeiten](#erweiterungsmöglichkeiten)

## Projektübersicht

SaveKey ist ein digitales Überwachungssystem für Schlüsselboxen, das eine klassische Schlüsselbox um moderne IoT-Funktionalitäten erweitert. Das System erkennt automatisch, wenn ein Schlüssel entnommen wird, fordert eine Benutzerauthentifizierung an und protokolliert alle Aktionen in einer Datenbank. Bei unbestätigten Entnahmen werden Warnmeldungen ausgelöst.

### Hauptfunktionen
- Automatische Erkennung von Schlüsselentnahmen mittels Magnetsensor
- Benutzerauthentifizierung per RFID/NFC-Chip
- Detaillierte Protokollierung aller Schlüsselaktionen
- Warnmeldungen bei unbestätigten Entnahmen
- Stromausfallerkennung und Alarmierung
- Webbasierte Benutzeroberfläche zur Verwaltung und Überwachung

## Funktionsweise

Das SaveKey-System arbeitet in fünf definierten Zuständen:

### 1. Ruhezustand
- Der Schlüssel befindet sich in der Box
- Keine Aktion erforderlich
- System überwacht kontinuierlich den Magnetsensor

### 2. Entnahme erkannt
- Der Magnetsensor registriert die Entfernung des Schlüssels
- Ein 5-Minuten-Countdown wird gestartet
- Die Entnahmezeit wird in der Datenbank als "ausstehend" protokolliert
- Der Schlüssel wird sofort als "nicht verfügbar" markiert

### 3. RFID-Quittierung
- Der Benutzer hält seinen RFID/NFC-Chip an den Leser
- Das System identifiziert den Benutzer anhand der RFID-UID
- Der Countdown wird abgebrochen
- Die Entnahme wird mit Benutzername und Zeitstempel bestätigt

### 4. Alternative Quittierung
- Falls kein RFID/NFC-Chip verwendet wird, kann die Entnahme über die Weboberfläche bestätigt werden
- Nur Administratoren können diese Funktion nutzen
- Die Bestätigung wird mit Benutzername und Zeitstempel protokolliert

### 5. Alarmfall
- Wenn innerhalb von 5 Minuten keine Bestätigung erfolgt
- Das System markiert die Entnahme als "unbefugt"
- Eine Warnmeldung wird an registrierte Benutzer gesendet

Zusätzlich wird bei einem Stromausfall eine eigene Warnung ausgelöst, um Manipulationen oder unerwartete Ausfälle sofort zu erkennen.

## Systemarchitektur

Das SaveKey-System besteht aus drei Hauptkomponenten:

1. **Hardware (Arduino)**
   - Erfasst physische Ereignisse (Schlüsselentnahme, RFID-Scans)
   - Sendet Ereignisse an den Server
   - Kommuniziert über WLAN mit dem Backend

2. **Backend (PHP/MySQL)**
   - Verarbeitet Ereignisse von der Hardware
   - Speichert Daten in der Datenbank
   - Stellt API-Endpunkte für Hardware und Frontend bereit

3. **Frontend (HTML/CSS/JavaScript)**
   - Bietet Benutzeroberfläche zur Interaktion mit dem System
   - Zeigt Schlüsselstatus und -historie an
   - Ermöglicht Administratoren die Verwaltung von Benutzern und RFID-Chips

Die Kommunikation zwischen den Komponenten erfolgt über HTTP/HTTPS mit JSON als Datenformat.

## Hardware-Komponenten

### Benötigte Hardware
- **ESP32 C6 DevKitC-1-N8** (8 MB SPI Flash)
- **Magnetsensor (Reed-Kontakt)** zur Erkennung der Schlüsselentnahme
- **RFID/NFC-Leser** (PN532) für die Benutzerauthentifizierung
- **Status-LED** für visuelles Feedback bei Initialisierung und erfolgreicher Verifikation
- **Micro Servo 9g SG90** für automatischen Verschlussmechanismus
- **USB-Stromversorgung** direkt über den USB-Port des ESP32 C6 (keine externe Batterie nötig)
- **WLAN-Modul** (bereits im ESP32 C6 integriert)

### Anschlussplan (ESP32 C6)
- **Magnetsensor**: An Pin 1 (INPUT_PULLDOWN - LOW = Schlüssel vorhanden, HIGH = Schlüssel entfernt)
- **Status-LED**: An Pin 10 (für visuelles Feedback)
- **PN532 RFID/NFC-Leser** (I²C-Verbindung):
  - SDA: Pin 4
  - SCL: Pin 5
  - IRQ: Pin 2
  - RESET: Pin 3
- **Micro Servo 9g SG90** (Automatischer Verschluss):
  - Signal: Pin 6 (PWM)
  - VCC: 3.3V Pin
  - GND: Ground Pin
  - Position geschlossen: 0°
  - Position geöffnet: 90° (Vierteldrehung)
- **Stromversorgung**: Direkt über USB-Port des ESP32 C6

## Software-Komponenten

### Arduino-Firmware (ESP32 C6)
- Programmiert in C++ mit der Arduino IDE für ESP32 C6
- Benötigte Bibliotheken:
  - WiFi (für ESP32)
  - HTTPClient (für ESP32)
  - ArduinoJson
  - Adafruit_PN532
  - Wire (I²C-Kommunikation)
  - ESP32Servo (für Servo-Motor-Steuerung)
- Implementiert als Finite State Machine (FSM)
- **LED-Verhalten**: 
  - Blinkt 2x bei Systeminitialisierung
  - Leuchtet 3 Sekunden bei erfolgreicher RFID-Verifikation
- **Servo-Verhalten**:
  - Startet in geschlossener Position (0°)
  - Öffnet auf 90° bei erfolgreicher RFID-Verifikation
  - Toggle-Mechanismus: abwechselndes Öffnen/Schließen
- **Non-blocking Design**: Ermöglicht parallele RFID-Scans, LED-Verwaltung und Servo-Steuerung
- Sendet Ereignisse an den Server über HTTP-Requests

### Backend (PHP)
- API-Endpunkte für die Hardware-Kommunikation:
  - `arduino_api.php`: Hauptendpunkt für Arduino-Ereignisse
  - `hardware_event.php`: Verarbeitet Hardware-Ereignisse
- API-Endpunkte für das Frontend:
  - `key_status.php`: Liefert den aktuellen Schlüsselstatus
  - `key_history.php`: Liefert die Schlüsselhistorie
  - `key_action.php`: Ermöglicht Administratoren die Schlüsselverwaltung
  - `rfid_management.php`: Verwaltet RFID/NFC-Chips

### Frontend (HTML/CSS/JavaScript)
- Responsive Weboberfläche
- Echtzeit-Aktualisierung des Schlüsselstatus
- Anzeige der Schlüsselhistorie
- Administratorfunktionen für Benutzer- und RFID-Verwaltung

## Datenbank-Struktur

Die Datenbank besteht aus drei Haupttabellen:

### 1. `benutzer`
- `user_id`: Eindeutige Benutzer-ID (Primärschlüssel)
- `vorname`: Vorname des Benutzers
- `nachname`: Nachname des Benutzers
- `benutzername`: Eindeutiger Benutzername für die Anmeldung
- `passwort`: Gehashtes Passwort
- `mail`: E-Mail-Adresse des Benutzers
- `phone`: Telefonnummer (optional)
- `seriennummer`: Seriennummer der zugewiesenen Schlüsselbox
- `rfid_uid`: UID des RFID/NFC-Chips des Benutzers
- `is_admin`: Boolean-Wert, der angibt, ob der Benutzer Administrator ist

### 2. `pending_key_actions`
- `id`: Eindeutige ID (Primärschlüssel)
- `seriennummer`: Seriennummer der Schlüsselbox
- `action_type`: Art der Aktion ('remove' oder 'return')
- `timestamp`: Zeitstempel der Aktion
- `status`: Status der Aktion ('pending', 'completed', 'expired')
- `completed_by`: Benutzername, der die Aktion abgeschlossen hat
- `completed_at`: Zeitstempel der Abschlussbestätigung

### 3. `key_logs`
- `box_id`: ID der Schlüsselbox
- `timestamp_take`: Zeitstempel der Schlüsselentnahme (Teil des Primärschlüssels)
- `timestamp_return`: Zeitstempel der Schlüsselrückgabe (kann NULL sein)
- `benutzername`: Benutzername, der den Schlüssel entnommen hat

## Benutzerrollen

Das SaveKey-System unterscheidet zwischen zwei Benutzerrollen:

### 1. Normale Benutzer
- Können den Status ihres Schlüssels einsehen
- Können die Schlüsselhistorie einsehen
- Können KEINE Schlüssel über die Weboberfläche entnehmen oder zurückgeben
- Können KEINE RFID/NFC-Chips zuweisen oder entfernen

### 2. Administrator-Benutzer
- Haben alle Rechte der normalen Benutzer
- Können Schlüssel über die Weboberfläche entnehmen und zurückgeben
- Können RFID/NFC-Chips zuweisen und entfernen
- Haben Zugriff auf alle Funktionen des Systems

## Installation und Einrichtung

### 1. Datenbank-Setup
1. Erstelle eine MySQL-Datenbank
2. Führe die SQL-Skripte aus:
   ```sql
   SOURCE system/database.sql;
   SOURCE system/alter_benutzer_seriennummer.sql;
   SOURCE system/alter_benutzer_rfid.sql;
   SOURCE system/alter_benutzer_admin.sql;
   SOURCE system/setup_arduino_api_tables.sql;
   ```
3. Weise Benutzern Seriennummern zu:
   ```sql
   UPDATE benutzer SET seriennummer = 'A001' WHERE benutzername = 'max_mustermann';
   ```
4. Setze einen Administrator:
   ```sql
   UPDATE benutzer SET is_admin = TRUE WHERE benutzername = 'admin';
   ```

### 2. ESP32 C6-Setup
1. Installiere die erforderlichen Bibliotheken in der Arduino IDE
2. Wähle "ESP32 C6 Dev Module" als Board in der Arduino IDE
3. Passe die WLAN- und API-Daten in der `savekey.ino` Datei an:
   ```cpp
   // --- WLAN-Credentials ---
   const char* ssid     = "dein_wlan_name";
   const char* password = "dein_wlan_passwort";

   // --- API-Konfiguration ---
   const char* API_ENDPOINT = "http://deine-domain.com/api/arduino_api.php";
   const char* API_KEY = "sk_hardware_savekey_12345";

   // --- Seriennummer der Box ---
   const char* seriennummer = "550"; // Muss mit der Seriennummer in der Datenbank übereinstimmen
   ```
4. Überprüfe die Pin-Konfiguration:
   ```cpp
   #define SDA_PIN     4    // I²C Data
   #define SCL_PIN     5    // I²C Clock
   #define PN532_IRQ   2    // NFC Interrupt
   #define PN532_RESET 3    // NFC Reset
   const int buttonPin = 1; // Magnetsensor
   const int LED_PIN = 10;  // Status-LED
   #define SERVO_PIN 6      // Servo-Motor (PWM)
   ```
5. Lade den Code auf dein ESP32 C6 Board hoch (verwende `savekey_neu_Servo.ino` für Servo-Unterstützung)
6. Verbinde die Stromversorgung über USB-Port

### 3. Webserver-Setup
1. Kopiere alle Dateien auf deinen Webserver
2. Konfiguriere die Datenbankverbindung in `system/config.php`
3. Stelle sicher, dass der Webserver PHP unterstützt

## Fehlerbehebung

### Hardware-Probleme
- **Arduino kann keine Verbindung zum WLAN herstellen**: Überprüfe die WLAN-Credentials
- **Arduino kann keine Verbindung zum Server herstellen**: Überprüfe die API-Endpoint-URL und den API-Schlüssel
- **RFID/NFC-Leser wird nicht erkannt**: Überprüfe die Verkabelung und die I²C-Adresse
- **Magnetsensor funktioniert nicht korrekt**: Überprüfe die Verkabelung und die Polarität des Magneten

### Software-Probleme
- **API-Fehler**: Überprüfe die Logs deines Webservers
- **Datenbank-Fehler**: Stelle sicher, dass die Datenbank korrekt eingerichtet ist
- **Frontend-Probleme**: Überprüfe die Browser-Konsole auf JavaScript-Fehler

## Erweiterungsmöglichkeiten

- **Mehrere Schlüssel pro Box**: Erweiterung des Systems für mehrere Schlüssel in einer Box
- **Mobile App**: Entwicklung einer nativen App für iOS und Android
- **E-Mail-Benachrichtigungen**: Automatische E-Mail-Benachrichtigungen bei Schlüsselaktionen
- **SMS-Benachrichtigungen**: Integration eines SMS-Dienstes für Benachrichtigungen
- **Statistiken und Berichte**: Erweiterte Auswertungsmöglichkeiten der Schlüsselnutzung
- **Integration mit Gebäudemanagementsystemen**: Anbindung an bestehende Systeme
