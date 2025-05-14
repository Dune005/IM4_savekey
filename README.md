# SaveKey | Digitales Schlüsselüberwachungssystem

**Kurzbeschreibung**
Dieses Projekt erweitert eine klassische Schlüsselbox um ein digitales Überwachungssystem. Es erkennt Entnahmen, fordert eine Bestätigung an und dokumentiert automatisch Zeitstempel und Nutzer:innen. Unbestätigte Entnahmen lösen Warnmeldungen aus.

## Features
- Erkennung von Schlüssellentnahme (Magnetsensor)
- Nutzer-Authentifizierung per RFID/NFC oder Push-Link
- Automatische Protokollierung von Uhrzeit und Nutzername
- Warn-E-Mail bei fehlender Bestätigung
- Stromausfall-Erkennung und Alarm

## Hardware
- Arduino-kompatibles Board
- Magnetsensor (Reed-Kontakt)
- RFID-/NFC-Reader
- WLAN-Modul
- Netzteil mit Backup-Kondensator

## Installation
1. Arduino IDE öffnen und Sketch hochladen
2. Sensoren und WLAN-Modul anschließen
3. Konfigurationsdatei (`config.json`) mit WLAN- und API-Daten anpassen

## Nutzung
- Web-Oberfläche starten: `http://<IP-Adresse>/`
- Entnahme quittieren per RFID-/NFC-Tag oder Link
- Admin-Einstellungen im Menü anpassen (Timeout, Nutzer:innen, E-Mail)

