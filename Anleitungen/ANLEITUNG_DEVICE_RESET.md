# Device Reset - Anleitung

## Übersicht
Mit dem Device Reset Tool kannst du deine SaveKey-Schlüsselbox über das Web-Interface neu starten. Dies ist nützlich für:
- Behebung von Verbindungsproblemen
- Neuinitialisierung der Sensoren
- Zurücksetzen nach Firmware-Updates
- Debugging und Fehlerbehebung

## Installation

### 1. Datenbank (optional)
Die Tabelle wird automatisch beim ersten Aufruf erstellt. Falls du sie manuell erstellen möchtest:

```sql
-- Führe diese SQL-Datei aus:
system/setup_device_reset_table.sql
```

### 2. Arduino Code anpassen
Öffne `system/arduino/savekey.ino` und füge folgendes hinzu:

**In der `loop()` Funktion nach `manageLED();`:**
```cpp
// Reset-Check alle 30 Sekunden
static unsigned long lastResetCheck = 0;
unsigned long now = millis();
if (now - lastResetCheck >= 30000) {
  lastResetCheck = now;
  checkForResetCommand();
}
```

**Am Ende der Datei vor der letzten Klammer:**
```cpp
void checkForResetCommand() {
  if (!wifiAvailable || WiFi.status() != WL_CONNECTED) {
    return;
  }

  HTTPClient http;
  String checkUrl = String(API_ENDPOINT) + "?action=check_reset&seriennummer=" + seriennummer;
  http.begin(checkUrl);
  http.addHeader("X-Api-Key", API_KEY);

  int httpResponseCode = http.GET();

  if (httpResponseCode == 200) {
    String response = http.getString();
    StaticJsonDocument<200> doc;
    DeserializationError error = deserializeJson(doc, response);

    if (!error && doc["reset"] == true) {
      Serial.println("🔄 Reset-Befehl empfangen vom Server!");
      Serial.println("Neustart wird in 2 Sekunden eingeleitet...");
      
      // LED kurz blinken lassen als Feedback
      for (int i = 0; i < 3; i++) {
        digitalWrite(LED_PIN, HIGH);
        delay(200);
        digitalWrite(LED_PIN, LOW);
        delay(200);
      }
      
      http.end();
      delay(2000);
      ESP.restart(); // Hardware-Neustart
    }
  }

  http.end();
}
```

Den vollständigen Code findest du auch in: `system/arduino/reset_check_code.ino`

### 3. Firmware hochladen
Lade die angepasste Firmware auf deinen ESP32 hoch.

## Verwendung

### Web-Interface
1. Öffne im Browser:
   ```
   https://savekey.klaus-klebband.ch/tools/reset_device.php
   ```

2. Klicke auf "🔄 Box jetzt neu starten"

3. Bestätige den Dialog

4. Die Box wird innerhalb von 30 Sekunden neu gestartet

### Mit Passwortschutz (optional)
Falls du die Sicherheit erhöhen möchtest:

1. Öffne `tools/reset_device.php`
2. Entferne die Kommentare bei der Authentifizierung:
   ```php
   $required_password = 'dein_sicheres_passwort_hier';
   if (!isset($_GET['password']) || $_GET['password'] !== $required_password) {
       die('❌ Zugriff verweigert. Bitte Passwort angeben: ?password=xxx');
   }
   ```
3. Ändere `dein_sicheres_passwort_hier` zu einem sicheren Passwort
4. Rufe das Tool dann auf mit:
   ```
   https://savekey.klaus-klebband.ch/tools/reset_device.php?password=dein_passwort
   ```

## Funktionsweise

### Ablauf
1. **Befehl erstellen**: Web-Interface speichert Reset-Befehl in Datenbank
2. **Polling**: ESP32 fragt alle 30 Sekunden nach Reset-Befehlen
3. **Empfang**: Bei positivem Befehl wird der Eintrag als "ausgeführt" markiert
4. **Neustart**: ESP32 startet nach 2 Sekunden neu (mit LED-Feedback)
5. **Initialisierung**: Box startet neu und initialisiert alle Komponenten

### Datenbank-Tabelle
```sql
device_reset_commands (
  id,                 -- Eindeutige ID
  seriennummer,       -- Welche Box betroffen ist (z.B. "550")
  created_at,         -- Wann wurde der Befehl erstellt
  executed,           -- 0 = ausstehend, 1 = ausgeführt
  executed_at,        -- Wann wurde der Befehl ausgeführt
  initiated_by,       -- Wer hat den Reset ausgelöst
  reason              -- Grund für den Reset
)
```

### API-Endpoint
```
GET /api/arduino_api.php?action=check_reset&seriennummer=550
Header: X-Api-Key: [DEIN_API_KEY]

Response:
{
  "reset": true/false,
  "message": "..."
}
```

## Status-Übersicht
Das Web-Interface zeigt dir:
- ✅ Ausgeführte Reset-Befehle mit Zeitstempel
- ⏳ Ausstehende Reset-Befehle
- Anzahl der wartenden Befehle für deine Box

## Troubleshooting

### Reset funktioniert nicht
1. **Prüfe Serial Monitor**: Siehst du "Reset-Check HTTP Response Code"?
2. **Prüfe WLAN-Verbindung**: Ist die Box online?
3. **Prüfe Datenbank**: Existiert die Tabelle `device_reset_commands`?
4. **Prüfe API-Key**: Stimmt der API-Key in Arduino und `system/hardware_auth.php` überein?

### Box startet sofort neu (Endlosschleife)
Falls die Box immer wieder neu startet:
1. Lösche alle ausstehenden Befehle:
   ```sql
   DELETE FROM device_reset_commands WHERE seriennummer = '550' AND executed = 0;
   ```

### Mehrere Befehle in der Warteschlange
Die Box führt beim nächsten Check nur den neuesten Befehl aus. Ältere werden automatisch ignoriert.

## Sicherheitshinweise

1. **Kein öffentlicher Zugriff**: Die Box kann von jedem mit Zugriff auf das Tool neu gestartet werden
2. **Passwortschutz empfohlen**: Aktiviere den optionalen Passwortschutz
3. **Logs überwachen**: Prüfe regelmäßig die Reset-Historie
4. **Rate Limiting**: Setze nicht zu viele Reset-Befehle hintereinander ab

## Erweiterte Optionen

### Automatisches Cleanup alter Befehle
Füge zur Datenbank einen Event hinzu (optional):
```sql
CREATE EVENT IF NOT EXISTS cleanup_old_reset_commands
ON SCHEDULE EVERY 7 DAY
DO
  DELETE FROM device_reset_commands 
  WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
```

### Integration ins Admin-Interface
Du kannst den Reset-Button auch in `protected.html` integrieren:
```javascript
async function resetDevice() {
    if (!confirm('Box neu starten?')) return;
    
    const response = await fetch('/tools/reset_device.php', {
        method: 'POST',
        credentials: 'include'
    });
    
    if (response.ok) {
        alert('Reset-Befehl gesendet! Box startet in 30 Sekunden neu.');
    }
}
```

## Support
Bei Fragen oder Problemen:
1. Prüfe die Logs in `system/arduino_errors.log`
2. Überprüfe den Serial Monitor des ESP32
3. Teste den API-Endpoint manuell mit curl:
   ```bash
   curl -H "X-Api-Key: DEIN_API_KEY" \
        "https://savekey.klaus-klebband.ch/api/arduino_api.php?action=check_reset&seriennummer=550"
   ```
