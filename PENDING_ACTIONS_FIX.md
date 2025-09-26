# Fix für Pending Key Actions Problem

## Problembeschreibung
Bei der erstmaligen Einrichtung/Registrierung von RFID-Badges entstanden durch mehrfache Schlüsselentnahmen/Zurücklegungen mehrere "pending" Einträge in der `pending_key_actions` Tabelle. Diese Einträge wurden nicht automatisch bereinigt und störten das System, da es permanent einen "pending" Status erwartete.

**UPDATE (August 2025):** Nach der ersten Implementierung wurde ein weiteres Problem identifiziert: Die automatische Bereinigung bei `key_returned` Events löschte pending Einträge, bevor sie korrekt verarbeitet werden konnten.

## Implementierte Lösung

### 1. Selektive Bereinigung nur bei key_removed Events (Korrigierte Hauptlösung)
**Geänderte Dateien:**
- `api/arduino_api.php`
- `api/hardware_event.php`

**Korrigierte Implementierung:**
- **Bei `key_removed`**: Bereinigung nur wenn bereits pending Einträge existieren
- **Bei `key_returned`**: KEINE automatische Bereinigung (normale Verarbeitung)

```php
// Nur bei key_removed Events:
$checkStmt = $pdo->prepare("
    SELECT COUNT(*) as pending_count 
    FROM pending_key_actions 
    WHERE seriennummer = :seriennummer 
    AND status = 'pending'
");
$checkStmt->execute([':seriennummer' => $seriennummer]);
$pendingCount = $checkStmt->fetchColumn();

if ($pendingCount > 0) {
    // Bereinigung nur wenn bereits Einträge vorhanden sind
    $cleanupStmt = $pdo->prepare("
        DELETE FROM pending_key_actions 
        WHERE seriennummer = :seriennummer 
        AND status = 'pending'
    ");
    $cleanupStmt->execute([':seriennummer' => $seriennummer]);
}
```

**Korrekte Verhalten:**
- ✅ **Schlüssel entnommen** → Pending Entry erstellt (alte werden bereinigt falls vorhanden)
- ✅ **RFID verifiziert** → Pending Entry wird auf 'completed' gesetzt
- ✅ **Schlüssel zurückgelegt** → key_logs Entry wird mit return_timestamp aktualisiert
- ✅ **Schlüssel ohne Verifizierung zurückgelegt** → Pending Entry wird auf 'completed' mit 'unknown_user' gesetzt

### 2. Zusätzliches Cleanup-Script (Sicherheitsnetz)
**Datei:** `api/cleanup_pending_actions.php` - Unverändert

## Warum diese korrigierte Lösung?

### Problem mit der ersten Version
Die ursprüngliche Bereinigung bei **allen** Events (auch `key_returned`) führte zu:
- Pending Einträge wurden gelöscht, bevor sie verarbeitet werden konnten
- Korrekte Verifizierungen gingen verloren
- System konnte nicht zwischen verifizierten und nicht-verifizierten Rückgaben unterscheiden

### Korrekte Event-Behandlung
- **`key_removed`**: Bereinigung alter pending Einträge sinnvoll (neuer State)
- **`key_returned`**: Normale Verarbeitung ohne Bereinigung (pending Entry soll verarbeitet werden)
- **`rfid_scan`**: Normale Verarbeitung (pending Entry wird auf completed gesetzt)

### State Machine Logik (Korrigiert)
```
Ruhezustand → Schlüssel entfernt (bereinigt alte pending, erstellt neuen)
            ↓
            RFID-Verifizierung (pending → completed) → Schlüssel zurückgelegt (key_logs aktualisiert)
            ↓ (ohne Verifizierung)
            Schlüssel zurückgelegt (pending → completed mit unknown_user)
```

## Testing der korrigierten Version

**Normaler Ablauf (verifiziert):**
1. Schlüssel entnehmen → pending Entry wird erstellt
2. Badge verifizieren → pending Entry wird 'completed', key_logs Entry erstellt
3. Schlüssel zurücklegen → key_logs Entry mit return_timestamp aktualisiert

**Ablauf ohne Verifizierung:**
1. Schlüssel entnehmen → pending Entry wird erstellt
2. Schlüssel zurücklegen → pending Entry wird 'completed' mit 'unknown_user'

**Einrichtungsprozess:**
1. Schlüssel entnehmen → pending Entry wird erstellt (alte bereinigt)
2. Badge registrieren → RFID-Management
3. Schlüssel zurücklegen → pending Entry wird 'completed' mit 'unknown_user'
4. Schlüssel wieder entnehmen → neuer sauberer pending Entry (alte bereinigt)
5. Badge verifizieren → funktioniert sofort

## Monitoring

Das System loggt jetzt korrigierte Bereinigungsaktionen:
```
error_log("Bereinigt $pendingCount vorherige pending Einträge für Seriennummer: $seriennummer (neuer key_removed Event)");
```
