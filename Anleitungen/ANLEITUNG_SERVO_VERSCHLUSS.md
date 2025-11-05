# Anleitung: Automatischer Servo-Verschluss

## Übersicht

Die SaveKey-Box verfügt nun über einen automatischen Verschlussmechanismus mit einem Micro Servo 9g SG90. Der neue Ablauf ermöglicht eine zweistufige Sicherheit:

1. **RFID-Autorisierung VOR der Entnahme** → Box öffnet sich
2. **Schlüsselentnahme** → Physische Entfernung des Schlüssels
3. **RFID-Verifikation NACH der Entnahme** → Protokollierung des Benutzers
4. **Auto-Close nach 2 Minuten** → Box schließt automatisch
5. **Toggle-Funktion** → RFID kann Box jederzeit öffnen/schließen (außer bei ausstehender Verifikation)

## Hardware-Setup

### Komponenten
- **Micro Servo 9g SG90**
- **ESP32 C6 DevKitC-1-N8**
- **Verkabelung**:
  - Signal (Orange/Gelb): Pin 6 (PWM)
  - VCC (Rot): 3.3V Pin
  - GND (Braun/Schwarz): Ground Pin

### Servo-Positionen
- **90° = Verschlossen** (Platte blockiert die Box)
- **0° = Geöffnet** (Box kann geöffnet werden)

## Funktionsablauf

### 1. Box öffnen (Normale Nutzung)

```
Benutzer → RFID-Chip an Sensor halten
         ↓
    Server prüft:
    - Existiert Benutzer mit dieser RFID-UID?
    - Hat Benutzer die richtige Seriennummer?
         ↓
    ✅ Autorisiert → Servo dreht auf 0° (ÖFFNEN)
                   → LED leuchtet 3 Sekunden
                   → Auto-Close Timer startet (120s)
         ↓
    Benutzer öffnet Box und entnimmt Schlüssel
         ↓
    Magnetsensor erkennt Entnahme → pendingVerification = true
         ↓
    Benutzer hält RFID-Chip nochmal an Sensor (Verifikation)
         ↓
    PRIORITÄT: Nur verifizieren, Box bleibt offen!
         ↓
    System protokolliert: Wer, Wann
         ↓
    Nach 120 Sekunden: Servo schließt automatisch auf 90°
```

### 2. Box manuell schließen (Toggle-Funktion)

```
Box ist offen (0°) UND keine Verifikation aussteht
         ↓
    Benutzer hält RFID-Chip an Sensor
         ↓
    Server prüft Autorisierung
         ↓
    ✅ Autorisiert → Servo dreht auf 90° (SCHLIESSEN)
                   → Auto-Close Timer wird beendet
```

### 3. Schlüssel zurückgeben

```
Benutzer legt Schlüssel zurück in die Box
         ↓
    Magnetsensor erkennt Rückgabe
         ↓
    System protokolliert Rückgabezeit
         ↓
    Box bleibt offen bis:
    - RFID-Scan zum manuellen Schließen ODER
    - Auto-Close nach 120 Sekunden
```

### 4. Nicht autorisierter Zugriff

```
Unbekannter RFID-Chip → Server findet keinen Benutzer
                      → ❌ Nicht autorisiert
                      → Box bleibt verschlossen
                      → Keine Aktion
```

## Arduino-Code Details

### Wichtige Variablen
```cpp
bool isLocked = true;              // Verschluss-Status
bool autoCloseActive = false;      // Auto-Close Timer aktiv
unsigned long servoOpenTime = 0;   // Zeitpunkt der Öffnung
const unsigned long AUTO_CLOSE_DELAY = 120000;  // 120 Sekunden = 2 Minuten
bool pendingVerification = false;  // Wartet auf Verifikation nach Schlüsselentnahme
```

### Wichtige Funktionen

#### `openLock()`
Öffnet die Box und startet den Auto-Close Timer:
```cpp
void openLock() {
  if (isLocked) {
    lockServo.write(0);         // Servo auf 0° drehen (ÖFFNEN)
    isLocked = false;
    autoCloseActive = true;
    servoOpenTime = millis();   // Timer starten
  }
}
```

#### `closeLock()`
Schließt die Box:
```cpp
void closeLock() {
  if (!isLocked) {
    lockServo.write(90);        // Servo auf 90° drehen (SCHLIESSEN)
    isLocked = true;
    autoCloseActive = false;
  }
}
```

#### `manageAutoClose()`
Überwacht den Auto-Close Timer:
```cpp
void manageAutoClose() {
  if (autoCloseActive && (millis() - servoOpenTime >= AUTO_CLOSE_DELAY)) {
    closeLock();  // Nach 120 Sekunden automatisch schließen
  }
}
```

#### RFID-Scan Logik mit Prioritäten
```cpp
// PRIORITÄT 1: Ausstehende Verifikation (Schlüssel wurde entnommen)
if (pendingVerification && !keyPresent) {
  // Nur verifizieren, Box bleibt offen!
  bool authorized = sendRfidAuthRequest(rfidUid);
  if (authorized) {
    sendRfidScanEvent(rfidUid);
    pendingVerification = false;
    // Auto-Close Timer läuft weiter
  }
}
// PRIORITÄT 2: Normale Tür-Öffner/Schließer Funktion
else {
  bool authorized = sendRfidAuthRequest(rfidUid);
  if (authorized) {
    // Toggle: Box öffnen ODER schließen
    if (isLocked) {
      openLock();   // 90° → 0°
    } else {
      closeLock();  // 0° → 90°
    }
  }
}
```

## Backend-API Details

### Neuer Event-Typ: `rfid_auth_request`

**Arduino sendet:**
```json
{
  "event_type": "rfid_auth_request",
  "seriennummer": "550",
  "rfid_uid": "a1b2c3d4",
  "timestamp": 12345678
}
```

**Server antwortet:**
```json
{
  "status": "success",
  "authorized": true,
  "message": "User authorized to open box",
  "user": {
    "benutzername": "max_mustermann",
    "vorname": "Max",
    "nachname": "Mustermann"
  }
}
```

**Bei nicht autorisiertem Zugriff:**
```json
{
  "status": "error",
  "authorized": false,
  "message": "No user found with this RFID UID"
}
```

### Validierung im Backend

Die Funktion `handleRfidAuthRequest()` prüft:

1. **RFID-UID existiert?**
   ```php
   SELECT * FROM benutzer WHERE rfid_uid = :rfid_uid
   ```

2. **Seriennummer stimmt überein?**
   ```php
   if ($user['seriennummer'] !== $seriennummer) {
       return false;  // Nicht autorisiert
   }
   ```

3. **Autorisierung erteilen:**
   ```php
   return true;  // Arduino öffnet Box
   ```

## Sicherheitsmerkmale

### Prioritätsbasierte RFID-Logik
1. **Höchste Priorität**: Verifikation nach Schlüsselentnahme
   - Wenn `pendingVerification = true` und Schlüssel draussen
   - RFID-Scan verifiziert NUR, schließt Box NICHT
   - Box bleibt offen bis Auto-Close (120s)
2. **Normale Priorität**: Toggle-Funktion
   - Wenn keine Verifikation aussteht
   - RFID-Scan öffnet/schließt Box (Toggle)

### Zweistufige Verifikation
1. **Vor der Entnahme**: RFID-Check ob Benutzer autorisiert ist
2. **Nach der Entnahme**: RFID-Scan zur Protokollierung

### Auto-Close Timer
- Box schließt automatisch nach 120 Sekunden (2 Minuten)
- Verhindert, dass die Box versehentlich offen bleibt
- Funktioniert auch bei Server-Ausfall (autonome Arduino-Funktion)
- Kann durch manuellen RFID-Scan vorzeitig beendet werden

### Offline-Robustheit
- RFID-Auth benötigt Server-Verbindung
- Auto-Close funktioniert offline
- Bei WLAN-Ausfall: Box bleibt verschlossen (Fail-Safe)

## Troubleshooting

### Servo reagiert nicht
1. **Stromversorgung prüfen**: 3.3V und GND korrekt angeschlossen?
2. **Pin-Konfiguration prüfen**: `#define SERVO_PIN 6` in `savekey_neu_Servo.ino`
3. **Serial Monitor**: Zeigt "🔓 Box öffnet" / "🔒 Box schließt"?

### Box öffnet nicht bei RFID-Scan
1. **WLAN-Verbindung aktiv?** → Serial Monitor prüfen
2. **Benutzer in Datenbank?** → `SELECT * FROM benutzer WHERE rfid_uid = 'xxx'`
3. **Seriennummer korrekt?** → Benutzer und Arduino müssen gleiche Seriennummer haben
4. **API-Response prüfen**: Serial Monitor zeigt Server-Antwort

### Box schließt zu früh/spät
1. **Auto-Close Delay anpassen**: `const unsigned long AUTO_CLOSE_DELAY = 120000;` (in Millisekunden)
2. **Beispiel für 3 Minuten**: `const unsigned long AUTO_CLOSE_DELAY = 180000;`
3. **Beispiel für 1 Minute**: `const unsigned long AUTO_CLOSE_DELAY = 60000;`
4. **Aktuell**: 120 Sekunden (2 Minuten)

### Servo zittert oder macht Geräusche
1. **Stromversorgung zu schwach**: Eventuell externe 5V-Versorgung nutzen
2. **Position erreicht**: Nach Bewegung `delay(500)` abwarten

## Anpassungen

### Auto-Close Zeit ändern
In `savekey_neu_Servo.ino`:
```cpp
// Standard: 120 Sekunden (2 Minuten)
const unsigned long AUTO_CLOSE_DELAY = 120000;

// Beispiel: 180 Sekunden (3 Minuten)
const unsigned long AUTO_CLOSE_DELAY = 180000;

// Beispiel: 60 Sekunden (1 Minute)
const unsigned long AUTO_CLOSE_DELAY = 60000;
```

### Servo-Positionen anpassen
Falls die Mechanik anders montiert ist:
```cpp
// Aktuelle Werte
lockServo.write(90);  // Geschlossen
lockServo.write(0);   // Geöffnet

// Beispiel: Umgekehrte Montage
lockServo.write(0);   // Geschlossen
lockServo.write(90);  // Geöffnet

// Beispiel: Andere Winkel
lockServo.write(180); // Geschlossen
lockServo.write(90);  // Geöffnet
```

## Zusammenfassung

Der neue Servo-Verschluss bietet:

✅ **Automatische Zugangskontrolle** via RFID vor Entnahme  
✅ **Intelligente Toggle-Funktion** für flexibles Öffnen/Schließen  
✅ **Prioritätslogik** - Verifikation hat Vorrang vor Toggle  
✅ **Auto-Close nach 2 Minuten** für automatisches Verschließen  
✅ **Autonome Funktion** auch bei Server-Ausfall  
✅ **Schlank gehaltener Arduino-Code** mit Server-basierter Geschäftslogik  
✅ **Servo-Positionen**: 90° = geschlossen, 0° = geöffnet  

Bei Fragen oder Problemen: Serial Monitor aktivieren und Logs analysieren! 🔧
