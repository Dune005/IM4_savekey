# Anleitung: Live-Anzeige von RFID-Scans

Diese Anleitung beschreibt, wie die neue Funktionalität zur Live-Anzeige von RFID-Scans eingerichtet und verwendet wird.

## Übersicht

Die neue Funktionalität ermöglicht es, dass RFID-Chips, die an der Schlüsselbox gescannt werden, automatisch auf der Protected Page angezeigt werden. Administratoren können diese UIDs dann mit einem Klick übernehmen und ihrem Konto zuweisen.

## Einrichtung

### 1. Datenbanktabelle erstellen

Führen Sie das folgende SQL-Skript aus, um die benötigte Datenbanktabelle zu erstellen:

```sql
-- Führe die Datei system/setup_last_rfid_scans_table.sql aus
SOURCE system/setup_last_rfid_scans_table.sql;
```

Alternativ können Sie den Inhalt der Datei `system/setup_last_rfid_scans_table.sql` direkt in Ihrem MySQL-Client ausführen.

### 2. Dateien überprüfen

Stellen Sie sicher, dass die folgenden Dateien korrekt installiert sind:

- `api/last_rfid_scan.php`: API-Endpunkt zum Abrufen der zuletzt gescannten RFID-UID
- `api/arduino_api.php`: Aktualisierte Version mit Speicherung der RFID-UIDs
- `js/protected.js`: Aktualisierte Version mit Live-Anzeige der RFID-UIDs
- `css/style.css`: Aktualisierte Version mit Styling für die RFID-Anzeige

## Funktionsweise

### Für Benutzer

1. Wenn ein RFID-Chip an der Schlüsselbox gescannt wird, wird die UID automatisch in der Datenbank gespeichert.
2. Auf der Protected Page wird die zuletzt gescannte RFID-UID für Administratoren angezeigt.
3. Die Anzeige erscheint automatisch und verschwindet nach 10 Sekunden wieder.
4. Mit dem Button "Diese UID verwenden" kann die UID direkt in das Eingabefeld übernommen werden.
5. Anschließend kann die UID mit dem Button "RFID/NFC zuweisen" dem eigenen Konto zugewiesen werden.

### Für Administratoren

1. Loggen Sie sich als Administrator ein und navigieren Sie zur Protected Page.
2. Im Bereich "RFID/NFC-Verwaltung" werden zuletzt gescannte RFID-UIDs automatisch angezeigt.
3. Sie können die UID mit einem Klick übernehmen und Ihrem Konto zuweisen.
4. Die Anzeige wird alle 2 Sekunden aktualisiert, um neue Scans zu erkennen.

## Technische Details

### Datenfluss

1. Der Arduino sendet RFID-Scans an den API-Endpunkt `api/arduino_api.php`.
2. Die API speichert die RFID-UID in der Tabelle `last_rfid_scans`.
3. Die Protected Page fragt regelmäßig den API-Endpunkt `api/last_rfid_scan.php` ab.
4. Wenn eine neue RFID-UID gefunden wird, wird sie auf der Protected Page angezeigt.

### Sicherheit

- Nur authentifizierte Administratoren können die zuletzt gescannten RFID-UIDs sehen.
- Die Anzeige ist auf die letzten 10 Sekunden beschränkt, um Missbrauch zu verhindern.
- Die RFID-UIDs werden nur für Benutzer mit der passenden Seriennummer angezeigt.

## Fehlerbehebung

### Die RFID-UID wird nicht angezeigt

1. Überprüfen Sie, ob die Tabelle `last_rfid_scans` korrekt erstellt wurde.
2. Stellen Sie sicher, dass der Arduino korrekt konfiguriert ist und RFID-Scans sendet.
3. Überprüfen Sie die Browser-Konsole auf JavaScript-Fehler.
4. Stellen Sie sicher, dass Sie als Administrator angemeldet sind.

### Die RFID-UID kann nicht zugewiesen werden

1. Überprüfen Sie, ob die RFID-UID bereits einem anderen Benutzer zugewiesen ist.
2. Stellen Sie sicher, dass Sie als Administrator angemeldet sind.
3. Überprüfen Sie die Browser-Konsole auf JavaScript-Fehler.

## Anpassungen

Die Anzeigedauer der RFID-UID kann in der Datei `js/protected.js` angepasst werden:

```javascript
// Automatisch nach 10 Sekunden ausblenden (10000 Millisekunden)
setTimeout(() => {
  lastScannedRfidElement.style.display = 'none';
}, 10000);
```

Die Abfragefrequenz kann ebenfalls angepasst werden:

```javascript
// Dann alle 2 Sekunden wiederholen (2000 Millisekunden)
rfidScanPollingInterval = setInterval(checkForNewRfidScans, 2000);
```
