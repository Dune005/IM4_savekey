# Anleitung zum Einrichten der Datenbank für die Arduino-API

Um die Arduino-API mit deiner Datenbank zu verwenden, musst du einige Tabellen und Spalten hinzufügen. Diese Anleitung führt dich durch den Prozess.

## Schritt 1: Seriennummer-Spalte zur Benutzertabelle hinzufügen

Die Seriennummer ist wichtig, um Benutzer mit ihren Schlüsselboxen zu verknüpfen. Führe das folgende SQL-Skript aus, um die Spalte hinzuzufügen:

```sql
-- Führe die Datei system/alter_benutzer_seriennummer.sql aus
SOURCE system/alter_benutzer_seriennummer.sql;
```

## Schritt 2: Tabellen für die Arduino-API erstellen

Die Arduino-API benötigt zwei Haupttabellen:
1. `pending_key_actions`: Speichert ausstehende Schlüsselaktionen vor der RFID-Verifikation
2. `key_logs`: Protokolliert alle Schlüsselentnahmen und -rückgaben

Führe das folgende SQL-Skript aus, um diese Tabellen zu erstellen:

```sql
-- Führe die Datei system/setup_arduino_api_tables.sql aus
SOURCE system/setup_arduino_api_tables.sql;
```

## Schritt 3: Seriennummern den Benutzern zuweisen

Nachdem die Tabellen erstellt wurden, musst du jedem Benutzer eine Seriennummer zuweisen. Diese Seriennummer muss mit der Seriennummer in deinem Arduino-Code übereinstimmen.

```sql
-- Beispiel: Seriennummer "A001" dem Benutzer "max_mustermann" zuweisen
UPDATE benutzer SET seriennummer = 'A001' WHERE benutzername = 'max_mustermann';
```

## Schritt 4: RFID-UIDs den Benutzern zuweisen

Damit Benutzer sich mit ihren RFID-Chips authentifizieren können, musst du die RFID-UIDs in der Datenbank speichern. Dies kann entweder manuell oder über die Weboberfläche erfolgen.

```sql
-- Beispiel: RFID-UID "04A2B3C4" dem Benutzer "max_mustermann" zuweisen
UPDATE benutzer SET rfid_uid = '04A2B3C4' WHERE benutzername = 'max_mustermann';
```

## Schritt 5: API-Schlüssel für die Hardware-Authentifizierung einrichten

Die Arduino-API verwendet einen API-Schlüssel zur Authentifizierung. Dieser Schlüssel ist in der Datei `system/hardware_auth.php` definiert und muss auch im Arduino-Code verwendet werden.

Der aktuelle API-Schlüssel ist: `sk_hardware_safekey_12345`

## Schritt 6: Arduino-Code anpassen

Stelle sicher, dass der Arduino-Code die richtige Seriennummer und den richtigen API-Schlüssel verwendet:

```cpp
// --- API-Konfiguration ---
const char* API_ENDPOINT = "http://deine-domain.com/api/arduino_api.php";
const char* API_KEY = "sk_hardware_safekey_12345";

// --- Seriennummer der Box ---
const char* seriennummer = "A001"; // Muss mit der Seriennummer in der Datenbank übereinstimmen
```

## Fehlerbehebung

Wenn du Probleme mit der Datenbankverbindung hast, überprüfe die Datei `system/config.php` und stelle sicher, dass die Verbindungsdaten korrekt sind.

Wenn die Arduino-API Fehler zurückgibt, überprüfe die Logs deines Webservers und stelle sicher, dass die API-Endpunkte korrekt konfiguriert sind.
