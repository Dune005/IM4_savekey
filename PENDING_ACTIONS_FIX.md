# Fix für Pending Key Actions Problem

## Problembeschreibung
Bei der erstmaligen Einrichtung/Registrierung von RFID-Badges entstanden durch mehrfache Schlüsselentnahmen/Zurücklegungen mehrere "pending" Einträge in der `pending_key_actions` Tabelle. Diese Einträge wurden nicht automatisch bereinigt und störten das System, da es permanent einen "pending" Status erwartete.

## Implementierte Lösung

### 1. Automatische Bereinigung bei neuen Events (Hauptlösung)
**Geänderte Dateien:**
- `api/arduino_api.php`
- `api/hardware_event.php`

**Implementierung:**
Vor jedem neuen Hardware-Event (key_removed, key_returned) werden alle bestehenden "pending" Einträge für diese Seriennummer automatisch gelöscht:

```php
// Clean up any existing pending actions for this serial number
$cleanupStmt = $pdo->prepare("
    DELETE FROM pending_key_actions 
    WHERE seriennummer = :seriennummer 
    AND status = 'pending'
");
$cleanupStmt->execute([':seriennummer' => $seriennummer]);
```

**Vorteile:**
- ✅ Löst das Hauptproblem sofort 
- ✅ Einfach und robust
- ✅ Verhindert zukünftige Akkumulation von pending Einträgen
- ✅ Loggt Bereinigungsaktionen für Debugging

### 2. Zusätzliches Cleanup-Script (Sicherheitsnetz)
**Neue Datei:** `api/cleanup_pending_actions.php`

**Funktionen:**
- Löscht alle pending Einträge älter als 30 Minuten
- Entfernt Duplikate (behält nur den neuesten pro Seriennummer)
- Bietet Status-Monitoring der aktuellen pending Actions
- Kann manuell oder als Cron-Job ausgeführt werden

**Aufruf:**
```bash
# Manuell
curl http://your-domain/api/cleanup_pending_actions.php

# Mit API-Key (für Cron-Jobs)
curl "http://your-domain/api/cleanup_pending_actions.php?api_key=YOUR_API_KEY"
```

## Warum diese Lösung?

### Edge-Case bei der Einrichtung
Das ursprüngliche Problem trat auf bei:
1. Schlüssel entnehmen (→ pending Entry)
2. Badge registrieren 
3. Schlüssel zurücklegen (für neuen Loop)
4. Schlüssel wieder entnehmen (→ weiterer pending Entry)
5. Badge verifizieren (→ nur ein Entry wird completed)
6. **Resultat:** Verwaister pending Entry stört das System

### Warum Cleanup bei jedem neuen Event?
- **State Machine Logik:** Ein neuer Hardware-Event bedeutet, dass der vorherige State irrelevant geworden ist
- **Robustheit:** Verhindert Akkumulation von verwaisten Einträgen
- **Einfachheit:** Keine komplexe Logik zur Unterscheidung "gültiger" vs. "verwaister" Einträge nötig

### Alternative Ansätze (verworfen)
1. **Komplexe State-Tracking:** Schwer zu implementieren und fehleranfällig
2. **Nur Cleanup-Script:** Reaktiv statt proaktiv
3. **Timeout-basierte Lösung:** Würde Edge-Case nicht lösen

## Testing

Nach der Implementierung sollte der Einrichtungsprozess reibungslos funktionieren:

1. Schlüssel entnehmen → pending Entry wird erstellt
2. Badge registrieren → RFID-Management 
3. Schlüssel zurücklegen → pending Entry wird bereinigt
4. Schlüssel wieder entnehmen → neuer sauberer pending Entry
5. Badge verifizieren → funktioniert sofort

## Monitoring

Das System loggt jetzt Bereinigungsaktionen:
```
error_log("Bereinigt $deletedCount pending Einträge für Seriennummer: $seriennummer");
```

Für zusätzliches Monitoring kann das Cleanup-Script verwendet werden, um den aktuellen Status der pending Actions zu überprüfen.
