## Inhaltsverzeichnis

1. [Projektübersicht](#projektübersicht)
2. [Anleitungen](#anleitungen)
3. [Flussdiagramm](#flussdiagramm)
4. [Komponentenplan](#komponentenplan)
5. [Steckschema](#steckschema)
6. [Screenshots / Bilder / ggf. GIFs](#screenshots--bilder--ggf-gifs)
7. [Bericht zum Umsetzungsprozess](#bericht-zum-umsetzungsprozess)
8. [Entwicklungsprozess](#entwicklungsprozess)
9. [Video-Dokumentation](#video-dokumentation)
10. [Lernfortschritt](#lernfortschritt)

# SaveKey | Digitales Schlüsselüberwachungssystem

**Kurzbeschreibung**  
SaveKey erweitert eine klassische Schlüsselbox um ein intelligentes digitales Überwachungssystem. Das System erkennt automatisch Schlüsselentnahmen, fordert eine Benutzerbestätigung an und dokumentiert lückenlos alle Aktivitäten mit Zeitstempel und Nutzerdaten. Unbestätigte Entnahmen lösen sofortige Warnmeldungen aus, um maximale Sicherheit zu gewährleisten.

## 🔑 Hauptfunktionen

### Kernfeatures
- **Automatische Erkennung** von Schlüsselentnahmen durch präzisen Magnetsensor
- **Duale Authentifizierung** via RFID/NFC-Tags oder webbasierte Push-Benachrichtigungen
- **Vollständige Protokollierung** mit Zeitstempel, Benutzername und Aktivitätstyp
- **Intelligentes Alarmsystem** bei fehlender Bestätigung oder Stromausfall
- **Webbasierte Verwaltung** mit responsiver Benutzeroberfläche

### Systemzustände
1. **Ruhezustand** - Schlüssel in der Box, System überwacht kontinuierlich
2. **Entnahme erkannt** - Countdown startet, Benachrichtigung wird versendet
3. **RFID-Quittierung** - Sofortige Bestätigung durch registrierte Tags
4. **Web-Quittierung** - Alternative Bestätigung über Push-Link oder QR-Code
5. **Alarmfall** - Automatische Warnmeldung bei fehlender Bestätigung

## 🛠️ Hardware-Komponenten

- **Arduino-kompatibles Microcontroller-Board** (ESP32/ESP8266)
- **Reed-Kontakt Magnetsensor** für zuverlässige Schlüsselerkennung
- **RFID/NFC-Reader** (RC522 oder PN532) für Benutzeridentifikation
- **Integriertes WLAN-Modul** für Internetverbindung
- **Netzteil mit Backup-Kondensator** für unterbrechungsfreien Betrieb

## 💻 Software-Stack

- **Backend**: PHP mit MySQL-Datenbank
- **Frontend**: Responsive HTML5/CSS3/JavaScript
- **Push-Dienst**: Web-Push-Benachrichtigungen
- **Firmware**: Arduino IDE kompatibel
- **API**: RESTful Web-Services für Hardware-Kommunikation

## 🚀 Quick-Start Installation

### Voraussetzungen
- Webserver mit PHP 7.4+ und MySQL
- Arduino IDE für Firmware-Upload
- Komponenten gemäß Hardware-Liste

### Setup-Schritte
1. **Repository klonen** und auf Webserver bereitstellen
2. **Datenbank einrichten** mit den SQL-Dateien aus `/system/`
3. **Arduino-Sketch hochladen** aus `/system/arduino/`
4. **Hardware verkabeln** gemäß Anschlussbelegung
5. **Konfiguration anpassen** in `system/config.php`
6. **Admin-Benutzer erstellen** über die Registrierungsseite

### Erste Schritte
- **Web-Interface aufrufen**: `https://savekey.klaus-klebband.ch/`
- **Admin-Panel konfigurieren**: Benutzer, Timeouts, E-Mail-Einstellungen
- **RFID-Tags registrieren** für autorisierte Benutzer
- **Push-Benachrichtigungen einrichten** für mobile Geräte

## 📋 Funktionsweise

Das System arbeitet ereignisgesteuert und reagiert auf folgende Trigger:
- **Magnetfeld-Änderung**: Erkennung von Schlüsselentnahme/-rückgabe
- **RFID-Scan**: Sofortige Benutzeridentifikation und -autorisierung
- **Web-Interaktion**: Alternative Authentifizierung über Browser
- **Timeout-Events**: Automatische Alarmauslösung bei fehlender Quittierung

## 📁 Projektstruktur

- `/api/` - Backend-API-Endpunkte für Hardware-Kommunikation
- `/admin/` - Admin-Tools für QR-Code-Generierung und Push-Management
- `/system/` - Konfigurationsdateien, Datenbankschemas und Arduino-Code
- `/Anleitungen/` - Detaillierte Setup- und Bedienungsanleitungen
- `/css/`, `/js/`, `/images/` - Frontend-Ressourcen

---

**Für detaillierte Installationsanleitungen und technische Dokumentation siehe `/Anleitungen/` Ordner.**



## Anleitungen
Eine Schritt-für-Schritt-Anleitung zur Reproduzierbarkeit des Projekts finden Sie in den folgenden Dokumenten in der richtigen Reihenfolge:
1. [Projektbeschrieb_savekey.md](Anleitungen/Projektbeschrieb_savekey.md) - Überblick über das Projekt und seine Ziele.
2. [ANLEITUNG_ADMIN_SETUP.md](Anleitungen/ANLEITUNG_ADMIN_SETUP.md) - Anleitung zur Einrichtung der Admin-Funktionen.
3. [ANLEITUNG_DATENBANK_SETUP.md](Anleitungen/ANLEITUNG_DATENBANK_SETUP.md) - Anleitung zur Einrichtung der Datenbank.
4. [ANLEITUNG_QR_CODE_ADMIN.md](Anleitungen/ANLEITUNG_QR_CODE_ADMIN.md) - Anleitung zur Nutzung der QR-Code-Funktionalität.
5. [ANLEITUNG_PUSH_BENACHRICHTIGUNGEN.md](Anleitungen/ANLEITUNG_PUSH_BENACHRICHTIGUNGEN.md) - Anleitung zur Einrichtung der Push-Funktionalität.


## Flussdiagramm
Die Flussdiagramme befinden sich im Ordner `images/dokumentation/`:
- [Entnahme (physisch)](images/dokumentation/Entnahme%20(physisch).png)
- [Rückgabe (physisch)](images/dokumentation/Rückgabe%20(physisch).png)
- [ScreenFlow (digital)](images/dokumentation/ScreenFlow%20(digital).png)

## Komponentenplan
Der Komponentenplan ist hier zu finden:
- [Komponentenplan.png](images/dokumentation/Komponentenplan.png)
oder als Text-Dokument:
- [Komponentenplan_SaveKey_Visuell.md](Anleitungen/Komponentenplan_SaveKey_Visuell.md)

## Steckschema
Das Steckschema ist hier zu finden:
- [Safekey Steckplatine.png](images/dokumentation/Safekey%20Steckplatine.png)

## Screenshots / Bilder / ggf. GIFs
Die Bilder vom Entwicklungsprozess befinden sich im Ordner `images/entwicklung/`:
- [BREADBOARD (1).jpg](images/entwicklung/BREADBOARD%20(1).jpg)
- [BREADBOARD (2).jpg](images/entwicklung/BREADBOARD%20(2).jpg)
- [PROTOYPING (1).jpg](images/entwicklung/PROTOYPING%20(1).jpg)
- [PROTOYPING (2).jpg](images/entwicklung/PROTOYPING%20(2).jpg)
- [PROTOYPING (3).jpg](images/entwicklung/PROTOYPING%20(3).jpg)
- [PROTOYPING (4).jpg](images/entwicklung/PROTOYPING%20(4).jpg)

## Bericht zum Umsetzungsprozess

### Entwicklungsprozess
1. UX Teil wie oben beschrieben.
2. Setup Server & DB, GitHub Repository erstellen, coden. (Anleitungen und Schritte im GitHub Repository)
3. Stecken des Prototyps
4. Troubleshooting:
   a. Steckkontakte nicht gut (Austausch der Steckbretter)
   b. Denkfehler: RFID und Reed Kontakt in Serie – technisch richtig, aber am Microcontroller falsch
5. Programmierung Arduino: Microcontroller programmieren, Daten einlesen und zuordnen
6. Vernetzung Microcontroller mit Server, Einträge in DB.
7. Erste erfolgreiche Probeläufe nach unserer Ursprungslogik
8. Erste Edgecases treten auf, werden notiert aber noch nicht behoben (z. B. dass man Schlüssel zurückgeben kann online, obwohl ihn jemand anderes geholt hat)
9. Entwicklung des Prototypen:
   a. Probleme: Steckkontakte, Nähe der Sensoren
   b. Lösung: Wago-Klemmen benutzen, Sensoren entfernen voneinander (> 5 cm)
   c. Neue Edgecases entdeckt:
      i. Wer weist den Admin zu?
      ii. Wie garantiert man, dass nur der Admin den Schlüssel manuell retournieren kann?
10. Funktion nun hergestellt:
    a. Behandlung und Behebung der Edgecases
    b. Weitere Gedanken für die effektive Anwendung: z. B. liefern wir die Boxen mit registrierten Badges aus, oder macht das der User selbst?
11. Pröbeln mit möglichen Zusatzfunktionen

## Video-Dokumentation
*Platzhalter für zukünftige Inhalte*


