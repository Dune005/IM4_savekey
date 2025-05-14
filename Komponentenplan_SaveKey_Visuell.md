# Visueller Komponentenplan SaveKey-System

```
+------------------------------------------+
|                                          |
|  +----------------------------------+    |
|  |        Hardware-Komponenten      |    |
|  +----------------------------------+    |
|                                          |
|  +-------------+      +-------------+    |
|  | Magnetsensor|      | RFID/NFC-   |    |
|  | (Reed)      |      | Leser (PN532)|    |
|  +------+------+      +------+------+    |
|         |                     |           |
|         v                     v           |
|  +----------------------------------+    |
|  |        ESP32 Arduino-Board       |    |
|  |  (Finite State Machine Firmware) |    |
|  +----------------+------------------    |
|                   |                      |
|                   | HTTP/HTTPS (JSON)    |
|                   | API-Key              |
|                   v                      |
+------------------------------------------+
                    |
                    |
+------------------------------------------+
|                                          |
|  +----------------------------------+    |
|  |        Backend-Server            |    |
|  +----------------------------------+    |
|                                          |
|  +-------------+      +-------------+    |
|  | API-Endpunkte|      | Ereignis-   |    |
|  | (PHP)        |      | verarbeitung |    |
|  +------+------+      +------+------+    |
|         |                     |           |
|         v                     v           |
|  +----------------------------------+    |
|  |        MySQL-Datenbank           |    |
|  |  (Benutzer, Aktionen, Logs)      |    |
|  +----------------+------------------    |
|                   |                      |
|                   |                      |
+------------------+|+---------------------+
                   ||
                   || HTTP/HTTPS
                   || (JSON/Form-Data)
                   vv
+------------------------------------------+
|                                          |
|  +----------------------------------+    |
|  |        Web-Frontend              |    |
|  +----------------------------------+    |
|                                          |
|  +-------------+      +-------------+    |
|  | Schlüssel-  |      | Benutzer-   |    |
|  | status      |      | verwaltung   |    |
|  +-------------+      +-------------+    |
|                                          |
|  +-------------+      +-------------+    |
|  | RFID-       |      | Schlüssel-  |    |
|  | Verwaltung  |      | historie     |    |
|  +-------------+      +-------------+    |
|                                          |
+------------------------------------------+
        |                    |
        |                    |
        v                    v
+----------------+  +------------------+
| Benutzer       |  | Administrator    |
| (RFID-Scan,    |  | (Weboberfläche,  |
| Push-Link)     |  | Verwaltung)      |
+----------------+  +------------------+
```

## Kommunikationsfluss

1. **Hardware → Backend**:
   - ESP32 erkennt Schlüsselentnahme (Magnetsensor) oder RFID-Scan
   - Sendet HTTP-Request mit JSON-Daten an `arduino_api.php`
   - Authentifizierung über API-Schlüssel

2. **Backend → Datenbank**:
   - PHP-Skripte verarbeiten eingehende Ereignisse
   - Speichern Daten in entsprechenden Tabellen
   - Prüfen RFID-UIDs gegen Benutzerdatenbank

3. **Frontend → Backend**:
   - Web-Interface fragt Daten vom Server ab
   - Administratoren können Einstellungen ändern
   - Live-Aktualisierung des Schlüsselstatus

4. **Backend → Benutzer**:
   - Sendet E-Mail-Benachrichtigungen bei Alarmen
   - Stellt Quittierungslinks für Schlüsselentnahmen bereit

## Datenfluss und Dateizuordnung

### Hardware-Ereignisse → API-Dateien
- **Magnetsensor (Schlüsselentnahme/-rückgabe)**:
  - Sendet Daten an `api/arduino_api.php` oder `api/hardware_event.php`
  - Ereignistypen: `key_removed`, `key_returned`
  - Daten werden in `pending_key_actions` Tabelle gespeichert

- **RFID/NFC-Leser**:
  - Sendet Daten an `api/arduino_api.php` oder `api/hardware_event.php`
  - Ereignistyp: `rfid_scan`
  - Verifiziert Benutzer gegen `benutzer` Tabelle
  - Bei erfolgreicher Verifikation: Aktualisierung in `key_logs` Tabelle
  - Optional: Speichert letzte Scans in `last_rfid_scans` Tabelle

### Backend-Verarbeitung → Datenbank-Tabellen
- **Schlüsselentnahme-Prozess**:
  1. `pending_key_actions`: Temporäre Speicherung der Entnahme (vor RFID-Verifikation)
  2. `key_logs`: Permanente Speicherung nach erfolgreicher Verifikation (mit `timestamp_take`)

- **Schlüsselrückgabe-Prozess**:
  1. `key_logs`: Aktualisierung des bestehenden Eintrags (Setzen von `timestamp_return`)

### Frontend-Anfragen → API-Dateien
- **Schlüsselstatus**: Abfrage über `api/key_status.php`
- **Schlüsselhistorie**: Abfrage über `api/key_history.php`
- **Benutzer-/RFID-Verwaltung**: Interaktion mit `api/register.php`, `api/admin_register.php`, `api/rfid_management.php`
- **Manuelle Schlüsselaktionen**: Verarbeitung durch `api/key_action.php`
