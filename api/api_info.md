# API-Dateien Übersicht

Dieses Dokument enthält eine Übersicht und Erklärung aller PHP-Dateien im Ordner "api" des SafeKey-Systems.

## hardware_event.php
Diese Datei dient als Schnittstelle zwischen der Hardware (Arduino-Schlüsselbox) und dem Backend-System. Sie empfängt Ereignisse vom Arduino und verarbeitet diese entsprechend.

**Hauptfunktionen:**
- Authentifizierung der Hardware mittels API-Schlüssel
- Verarbeitung verschiedener Ereignistypen:
  - `key_removed`: Wenn ein Schlüssel physisch entfernt wurde
  - `key_returned`: Wenn ein Schlüssel zurückgegeben wurde
  - `rfid_scan`: Wenn ein RFID/NFC-Scan durchgeführt wurde
- Speichern der Ereignisse in der Datenbank
- Überprüfung der Benutzerauthentifizierung via RFID/NFC

## key_action.php
Diese Datei verwaltet die Aktionen zum Entnehmen und Zurückgeben von Schlüsseln durch Benutzer über die Weboberfläche.

**Hauptfunktionen:**
- Überprüfung der Benutzerauthentifizierung
- Verarbeitung von zwei Hauptaktionen:
  - `take`: Schlüssel entnehmen
  - `return`: Schlüssel zurückgeben
- Überprüfung des aktuellen Schlüsselstatus vor der Aktion
- Protokollierung der Schlüsselaktionen in der Datenbank
- Verhinderung von unerlaubten Aktionen (z.B. Rückgabe eines Schlüssels, der nicht vom aktuellen Benutzer entnommen wurde)

## key_history.php
Diese Datei stellt die Entnahme- und Rückgabehistorie eines Schlüssels bereit.

**Hauptfunktionen:**
- Überprüfung der Benutzerauthentifizierung
- Abrufen der Schlüsselhistorie basierend auf der Seriennummer des Benutzers
- Formatierung der Historiendaten für die Anzeige
- Begrenzung der Ergebnisse auf die letzten 20 Einträge

## key_status.php
Diese Datei liefert Informationen über den aktuellen Status eines Schlüssels.

**Hauptfunktionen:**
- Überprüfung der Benutzerauthentifizierung
- Abrufen des aktuellen Schlüsselstatus basierend auf der Seriennummer des Benutzers
- Bereitstellung von Informationen wie:
  - Ist der Schlüssel verfügbar?
  - Wer hat den Schlüssel aktuell (falls nicht verfügbar)?
  - Wann wurde der Schlüssel entnommen?
  - Box-ID des Schlüssels

## keybox.php
Diese Datei liefert Informationen über den Inhalt einer Schlüsselbox basierend auf der Seriennummer.

**Hauptfunktionen:**
- Überprüfung der Benutzerauthentifizierung
- Überprüfung, ob der Benutzer Zugriff auf die angeforderte Schlüsselbox hat
- Bereitstellung von Beispielinhalten basierend auf der Seriennummer
- In einer echten Anwendung würde hier eine Datenbankabfrage durchgeführt werden

## login.php
Diese Datei verwaltet den Anmeldeprozess für Benutzer.

**Hauptfunktionen:**
- Verarbeitung von Anmeldedaten (Benutzername und Passwort)
- Überprüfung der Anmeldedaten gegen die Datenbank
- Erstellung einer Session für authentifizierte Benutzer
- Speichern von Benutzerinformationen in der Session (user_id, mail, vorname, nachname, benutzername, seriennummer)
- Rückgabe von JSON-Antworten für die Frontend-Integration

## logout.php
Diese Datei verwaltet den Abmeldeprozess für Benutzer.

**Hauptfunktionen:**
- Beenden der aktuellen Benutzersession
- Löschen aller Session-Daten
- Rückgabe einer Erfolgsantwort im JSON-Format

## protected.php
Diese Datei dient als API-Endpunkt, der Informationen über den angemeldeten Benutzer zurückgibt.

**Hauptfunktionen:**
- Überprüfung, ob ein Benutzer angemeldet ist
- Rückgabe von Benutzerinformationen im JSON-Format (user_id, mail, vorname, nachname, benutzername, seriennummer)
- Wird vom Frontend verwendet, um zu prüfen, ob ein Benutzer angemeldet ist und um Benutzerinformationen anzuzeigen

## register.php
Diese Datei verwaltet den Registrierungsprozess für neue Benutzer.

**Hauptfunktionen:**
- Validierung der Registrierungsdaten
- Überprüfung, ob der Benutzername oder die E-Mail bereits existiert
- Sicheres Hashen des Passworts
- Speichern des neuen Benutzers in der Datenbank mit allen erforderlichen Feldern (vorname, nachname, benutzername, mail, passwort, phone, seriennummer)
- Rückgabe von JSON-Antworten für die Frontend-Integration

## rfid_management.php
Diese Datei verwaltet die RFID/NFC-Zuordnungen für Benutzer.

**Hauptfunktionen:**
- Überprüfung der Benutzerauthentifizierung
- Verarbeitung von drei Hauptaktionen:
  - `assign_rfid`: Zuweisen einer RFID/NFC-UID zu einem Benutzer
  - `remove_rfid`: Entfernen einer RFID/NFC-UID von einem Benutzer
  - `get_rfid`: Abrufen der aktuellen RFID/NFC-UID eines Benutzers
- Überprüfung, ob eine RFID/NFC-UID bereits einem anderen Benutzer zugewiesen ist
- Aktualisierung der Benutzerdaten in der Datenbank
