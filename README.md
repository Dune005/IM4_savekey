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

## Projektübersicht

**Kurzbeschreibung**
Dieses Projekt erweitert eine klassische Schlüsselbox um ein digitales Überwachungssystem. Es erkennt Entnahmen, fordert eine Bestätigung an und dokumentiert automatisch Zeitstempel und Nutzer:innen. Unbestätigte Entnahmen lösen Warnmeldungen aus.

### Features
- Erkennung von Schlüssellentnahme (Magnetsensor)
- Nutzer-Authentifizierung per RFID/NFC oder Push-Link
- Automatische Protokollierung von Uhrzeit und Nutzername
- Warn-E-Mail bei fehlender Bestätigung
- Stromausfall-Erkennung und Alarm

### Hardware
- Arduino-kompatibles Board
- Magnetsensor (Reed-Kontakt)
- RFID-/NFC-Reader
- WLAN-Modul
- Netzteil mit Backup-Kondensator

### Installation
1. Arduino IDE öffnen und Sketch hochladen
2. Sensoren und WLAN-Modul anschließen
3. Konfigurationsdatei (`config.json`) mit WLAN- und API-Daten anpassen

### Nutzung
- Web-Oberfläche starten: `http://<IP-Adresse>/`
- Entnahme quittieren per RFID-/NFC-Tag oder Link
- Admin-Einstellungen im Menü anpassen (Timeout, Nutzer:innen, E-Mail)


## Anleitungen
Eine Schritt-für-Schritt-Anleitung zur Reproduzierbarkeit des Projekts finden Sie in den folgenden Dokumenten in der richtigen Reihenfolge:
1. [Projektbeschrieb_savekey.md](Anleitungen/Projektbeschrieb_savekey.md) - Überblick über das Projekt und seine Ziele.
2. [Dokumentation_SaveKey.md](Anleitungen/Dokumentation_SaveKey.md) - Detaillierte technische Dokumentation.
3. [ANLEITUNG_ADMIN_SETUP.md](Anleitungen/ANLEITUNG_ADMIN_SETUP.md) - Anleitung zur Einrichtung der Admin-Funktionen.
4. [ANLEITUNG_DATENBANK_SETUP.md](Anleitungen/ANLEITUNG_DATENBANK_SETUP.md) - Anleitung zur Einrichtung der Datenbank.
5. [ANLEITUNG_QR_CODE_ADMIN.md](Anleitungen/ANLEITUNG_QR_CODE_ADMIN.md) - Anleitung zur Nutzung der QR-Code-Funktionalität.
6. [ANLEITUNG_RFID_LIVE_ANZEIGE.md](Anleitungen/ANLEITUNG_RFID_LIVE_ANZEIGE.md) - Anleitung zur Live-Anzeige der RFID-Daten.

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


