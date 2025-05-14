# **SaveKey | Digitales Schlüssel­überwachungs­system**

### **Zielsetzung**

Unser Projekt erweitert die klassische Schlüsselbox um eine digitale Überwachung und Dokumentation. Ziel ist es, jederzeit festzustellen, ob sich der Schlüssel in der Box befindet, wer ihn entnommen hat und die Entnahme zu quittieren. Das System benachrichtigt alle registrierten Benutzer\*innen bei einer Entnahme und protokolliert automatisch die Zeit und den verantwortlichen Nutzer. Bleibt eine Bestätigung aus, wird die Besitzerin der Schlüsselbox gewarnt. Sobald der Schlüssel zurückgehängt wird, werden alle offenen Meldungen automatisch storniert.

### **Funktionsweise in fünf Zuständen**

1. **Ruhezustand**

   * Schlüssel liegt in der Box, keine Aktion.

2. **Entnahme erkannt**

   * Magnetsensor registriert Entfernung und startet einen Countdown. Die Zeit der Entnahme wird protokolliert.

3. **RFID-Quittierung**

   * Nutzer\*in hält einen gültigen RFID-/NFC-Tag an den Leser. Countdown wird abgebrochen, Meldung storniert, Nutzername und Uhrzeit werden eingetragen.

4. **Alternative Quittierung**

   * Wird kein RFID-Tag genutzt, kann die Person die Entnahme über einen per Push-Nachricht oder Link gesendeten Verifizierungs-Dialog bestätigen. Bei positiver Bestätigung erfolgt automatische Protokollierung.

5. **Alarmfall**

   * Meldet sich niemand innerhalb des eingestellten Zeitfensters, sendet das System eine Warn-E-Mail an die Schlüsselbox-Besitzerin mit dem Hinweis „Schlüssel wurde entnommen\!“.

Darüber hinaus wird bei **Stromausfall** eine eigene Warnung („Spannung weg“) ausgelöst, um Manipulationen oder unerwartete Ausfälle sofort sichtbar zu machen.

---

**Technische Umsetzung**

* **Hardware**

  * **Magnetsensor** (Reed-Kontakt) zur zuverlässigen Erkennung, ob der Schlüssel in der Halterung hängt.

  * **RFID-/NFC-Reader** für die Identifikation und Quittierung durch registrierte Personen.

  * **Arduino-kompatibles Microcontroller-Board** im Maker-Kit als zentrale Steuereinheit.

  * **WLAN-Modul** (integriert oder als externes Shield), um Sensordaten und Statusmeldungen an einen Server oder direkt in die Cloud zu übertragen.

  * **Spannungsversorgung** über Netzteil mit Backup-Kondensator, um bei kurzzeitigen Stromausfällen Warnungen auszulösen.

* **Firmware & Kommunikation**

  * Arduino-Sketch steuert die Sensoren, liest RFID-Daten aus, managt den Countdown und sendet alle Ereignisse per HTTP/HTTPS an einen Web-Service.

  * Abarbeitung der Zustände als einfacher endlicher Automat (Finite State Machine), um zuverlässige Übergänge zwischen „Schlüssel da“, „Entnommen – Bestätigung ausstehend“, „Bestätigt“, „Alarm“ und „Stromausfall“ zu gewährleisten.

* **Backend & Web-Interface**

  * **Datenbank** (z. B. MySQL oder Firebase) speichert Zeitstempel, Nutzer-IDs und Systemereignisse.

  * **REST-API** für Einträge neuer Events, Abfrage des aktuellen Box-Status und Senden von Quittierungs-Requests.

  * **Web-Front-End** visualisiert die aktuelle Situation in Echtzeit, zeigt ein Protokoll vergangener Entnahmen und bietet Administratoren Einstellmöglichkeiten (z. B. Timeout-Dauer, registrierte Nutzer:innen, E-Mail-Empfänger).

  * **Benachrichtigungsdienst** versendet Push-Nachrichten oder E-Mails bei Entnahmen und Stromausfällen.

Mit dieser Kombination aus schlichtem Magnet- und RFID-Sensor, Arduino-Steuerung und einer webbasierten Oberfläche entsteht ein plattformunabhängiges, skalierbares IoT-Gadget: eine digitale Schlüsselalarmanlage mit automatischem Tracking, Bestätigung und Alarm.

