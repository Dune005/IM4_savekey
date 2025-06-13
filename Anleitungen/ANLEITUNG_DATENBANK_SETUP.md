# SaveKey Datenbank Setup - Vollständige Anleitung

Diese Anleitung führt dich durch die komplette Einrichtung der SaveKey Datenbank mit allen benötigten Tabellen und deren Beziehungen.

## Überblick über die SaveKey Datenbankarchitektur

Das SaveKey System verwendet eine MySQL/MariaDB Datenbank mit 6 Haupttabellen:

### Kerntabellen
- **`benutzer`** - Zentrale Benutzerverwaltung mit Authentifizierung
- **`key_logs`** - Protokollierung aller Schlüsselentnahmen und -rückgaben

### Arduino/Hardware Integration
- **`pending_key_actions`** - Ausstehende Aktionen vor RFID-Bestätigung
- **`last_rfid_scans`** - Live-Anzeige der letzten RFID-Scans
- **`sensordata`** - Sensordaten von den Schlüsselboxen

### Zusatzfunktionen
- **`push_subscriptions`** - Push-Benachrichtigungen für Benutzer

## Schritt 1: Basis-Datenbank erstellen

Erstelle zunächst die Hauptdatenbank und die Grundtabelle für Benutzer:

```sql
-- Erstelle die Datenbank (falls noch nicht vorhanden)
CREATE DATABASE IF NOT EXISTS savekey_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE savekey_db;

-- Basis-Benutzertabelle erstellen
SOURCE system/database.sql;
```

## Schritt 2: Benutzer-Erweiterungen hinzufügen

Erweitere die Benutzertabelle um die notwendigen Spalten für die Hardware-Integration:

```sql
-- RFID-Unterstützung hinzufügen
SOURCE system/alter_benutzer_rfid.sql;

-- Seriennummer für Schlüsselbox-Zuordnung hinzufügen
SOURCE system/alter_benutzer_seriennummer.sql;

-- Admin-Rechte hinzufügen
SOURCE system/alter_benutzer_admin.sql;
```

## Schritt 3: Hardware-Integration Tabellen

Erstelle die Tabellen für die Arduino/Hardware-Integration:

```sql
-- Ausstehende Aktionen und Protokollierung
SOURCE system/setup_arduino_api_tables.sql;

-- Live RFID-Scan Anzeige
SOURCE system/setup_last_rfid_scans_table.sql;
```

## Schritt 4: Push-Benachrichtigungen

Aktiviere Push-Benachrichtigungen (optional):

```sql
-- Push-Subscription Tabelle erstellen
SOURCE system/setup_push_subscriptions_table.sql;
```

## Schritt 5: Sensordaten-Tabelle

Falls du Sensordaten von den Schlüsselboxen erfassen möchtest, erstelle die entsprechende Tabelle:

```sql
-- Sensordaten-Tabelle (falls benötigt)
CREATE TABLE IF NOT EXISTS `sensordata` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `seriennummer` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `sensor_type` VARCHAR(50) NOT NULL,
  `value` DECIMAL(10,2) NOT NULL,
  `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_seriennummer` (`seriennummer`),
  INDEX `idx_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Tabellenbeziehungen und Datenfluss

### Beziehungsdiagramm
```
benutzer (Zentral)
├── benutzername → key_logs.benutzername (FK)
├── seriennummer → pending_key_actions.seriennummer
├── seriennummer → last_rfid_scans.seriennummer  
├── seriennummer → sensordata.seriennummer
├── user_id → push_subscriptions.user_id (FK)
└── rfid_uid → last_rfid_scans.rfid_uid
```

### Datenfluss im System
1. **Benutzer-Registrierung**: Neuer Eintrag in `benutzer` Tabelle
2. **Hardware-Zuordnung**: Seriennummer und RFID-UID werden zugewiesen
3. **Schlüsselentnahme**: 
   - Aktion wird in `pending_key_actions` gespeichert
   - Nach RFID-Bestätigung wird `key_logs` Eintrag erstellt
   - RFID-Scan wird in `last_rfid_scans` protokolliert
4. **Sensordaten**: Kontinuierliche Erfassung in `sensordata`
5. **Benachrichtigungen**: Push-Nachrichten über `push_subscriptions`

## Konfiguration und erste Daten

### Schritt 6.1: Benutzer mit Hardware verknüpfen

Nachdem die Tabellen erstellt wurden, musst du jedem Benutzer eine Seriennummer zuweisen. Diese Seriennummer muss mit der Seriennummer in deinem Arduino-Code übereinstimmen.

```sql
-- Beispiel: Seriennummer "A001" dem Benutzer "max_mustermann" zuweisen
UPDATE benutzer SET seriennummer = 'A001' WHERE benutzername = 'max_mustermann';
```

### Schritt 6.2: RFID-UIDs den Benutzern zuweisen

Damit Benutzer sich mit ihren RFID-Chips authentifizieren können, musst du die RFID-UIDs in der Datenbank speichern. Dies kann entweder manuell oder über die Weboberfläche erfolgen.

```sql
-- Beispiel: RFID-UID "04A2B3C4" dem Benutzer "max_mustermann" zuweisen
UPDATE benutzer SET rfid_uid = '04A2B3C4' WHERE benutzername = 'max_mustermann';
```

### Schritt 6.3: Admin-Benutzer einrichten

Erstelle einen Administrator-Benutzer für die Verwaltung:

```sql
-- Admin-Benutzer erstellen (Skript anpassen nach Bedarf)
SOURCE system/set_admin_user.sql;
```

### Schritt 6.4: API-Konfiguration

Die Arduino-API verwendet einen API-Schlüssel zur Authentifizierung. Dieser Schlüssel ist in der Datei `system/hardware_auth.php` definiert und muss auch im Arduino-Code verwendet werden.

Der aktuelle API-Schlüssel ist: `sk_hardware_safekey_12345`

### Schritt 6.5: Arduino-Hardware konfigurieren

Stelle sicher, dass der Arduino-Code die richtige Seriennummer und den richtigen API-Schlüssel verwendet:

```cpp
// --- API-Konfiguration ---
const char* API_ENDPOINT = "http://deine-domain.com/api/arduino_api.php";
const char* API_KEY = "sk_hardware_safekey_12345";

// --- Seriennummer der Box ---
const char* seriennummer = "A001"; // Muss mit der Seriennummer in der Datenbank übereinstimmen
```

## Vollständige Installation (Ein-Klick Setup)

Für eine komplette Neuinstallation kannst du alle Schritte in einem Durchgang ausführen:

```sql
-- Vollständige SaveKey Datenbank Installation
USE savekey_db;

-- 1. Basis-Strukturen
SOURCE system/database.sql;
SOURCE system/alter_benutzer_rfid.sql;
SOURCE system/alter_benutzer_seriennummer.sql;
SOURCE system/alter_benutzer_admin.sql;

-- 2. Hardware-Integration
SOURCE system/setup_arduino_api_tables.sql;
SOURCE system/setup_last_rfid_scans_table.sql;

-- 3. Zusatzfunktionen
SOURCE system/setup_push_subscriptions_table.sql;

-- 4. Admin-Benutzer erstellen
SOURCE system/set_admin_user.sql;
```

## Tabellen-Details

### `benutzer` (Kerntabelle)
- **user_id**: Primärschlüssel
- **vorname, nachname**: Benutzerdaten
- **benutzername**: Eindeutiger Login-Name
- **passwort**: Verschlüsseltes Passwort
- **mail, phone**: Kontaktdaten
- **rfid_uid**: RFID-Chip Identifikation
- **seriennummer**: Zugeordnete Schlüsselbox
- **admin**: Admin-Rechte (0/1)

### `key_logs` (Protokollierung)
- **box_id**: Schlüsselbox-Nummer
- **timestamp_take**: Entnahme-Zeitstempel
- **timestamp_return**: Rückgabe-Zeitstempel (NULL = noch nicht zurückgegeben)
- **benutzername**: Wer den Schlüssel hat

### `pending_key_actions` (Hardware-Integration)
- **seriennummer**: Betroffene Schlüsselbox
- **action_type**: 'remove' oder 'return'
- **status**: 'pending', 'completed', 'expired'
- **completed_by**: RFID-UID des bestätigenden Nutzers

### `last_rfid_scans` (Live-Monitoring)
- **seriennummer**: Schlüsselbox-Identifikation
- **rfid_uid**: Gescannte RFID-UID
- **timestamp**: Scan-Zeitpunkt

### `push_subscriptions` (Benachrichtigungen)
- **user_id**: Verknüpfung zum Benutzer
- **endpoint, p256dh, auth**: Push-Service Daten

### `sensordata` (Sensordaten)
- **seriennummer**: Schlüsselbox-Identifikation  
- **sensor_type**: Art des Sensors (Temperatur, Feuchtigkeit, etc.)
- **value**: Messwert
- **timestamp**: Mess-Zeitpunkt

## Fehlerbehebung

Wenn du Probleme mit der Datenbankverbindung hast, überprüfe die Datei `system/config.php` und stelle sicher, dass die Verbindungsdaten korrekt sind.

Wenn die Arduino-API Fehler zurückgibt, überprüfe die Logs deines Webservers und stelle sicher, dass die API-Endpunkte korrekt konfiguriert sind.
