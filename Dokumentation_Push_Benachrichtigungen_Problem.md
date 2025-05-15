# Dokumentation: Probleme mit Push-Benachrichtigungen im SaveKey-System

## Übersicht

Diese Dokumentation beschreibt die Implementierung von Push-Benachrichtigungen im SaveKey-System und die aufgetretenen Probleme. Die Push-Benachrichtigungen sollen Mitglieder einer Schlüsselbox informieren, wenn sich der Status ihrer Box ändert.

## Implementierte Komponenten

Folgende Komponenten wurden erfolgreich implementiert:

1. **Datenbank-Tabelle für Push-Subscriptions**
   - Eine neue Tabelle `push_subscriptions` zur Speicherung der Push-Benachrichtigungs-Abonnements
   - Verknüpfung mit Benutzern über die `user_id`

2. **Push-Benachrichtigungs-API**
   - API-Datei `api/push_notification.php` für die Verwaltung von Abonnements und das Senden von Benachrichtigungen
   - Funktionen zum Senden von Benachrichtigungen an bestimmte Benutzer oder Benutzer mit einer bestimmten Seriennummer

3. **Integration in die Hardware-Events**
   - Aktualisierung der `api/hardware_event.php`-Datei, um Push-Benachrichtigungen bei Statusänderungen zu senden
   - Unterstützung für verschiedene Ereignistypen: Schlüsselentnahme, Schlüsselrückgabe, RFID-Scan, verifizierte Entnahme

4. **Konfigurationssystem**
   - Konfigurationsdatei `system/push_notifications_config.php` für flexible Anpassung der Benachrichtigungen
   - Unterstützung für Platzhalter in den Benachrichtigungstexten

5. **Administrationsoberfläche**
   - Administrationsoberfläche `admin/push_notifications.php` zur Konfiguration der Push-Benachrichtigungen
   - Integration in die bestehende Admin-Oberfläche über einen Link in der Protected Page

6. **Service Worker und Client-Seite**
   - Service Worker `service-worker.js` für die Verarbeitung von Push-Benachrichtigungen
   - JavaScript-Datei `js/push-notifications.js` für die Client-Seite der Push-Benachrichtigungen
   - Integration in die Startseite `index.html`

7. **VAPID-Schlüssel**
   - Generierung und Konfiguration der VAPID-Schlüssel für die Web-Push-Authentifizierung

## Aufgetretene Probleme

### 1. Anzeige von PHP-Dateien als Quellcode

**Problem:**
Die PHP-Dateien `admin/push_notifications.php` und `admin/push_simple.php` werden im Browser als Quellcode angezeigt, anstatt ausgeführt zu werden. Dies betrifft nur bestimmte PHP-Dateien im `admin/`-Verzeichnis, während andere PHP-Dateien wie `admin/push_debug.php` korrekt ausgeführt werden.

**Durchgeführte Tests und Lösungsversuche:**

1. **Debug-Seite erstellt**
   - Eine Debug-Seite `admin/push_debug.php` wurde erstellt, um den Status der Push-Benachrichtigungen zu überprüfen
   - Diese Seite wird korrekt ausgeführt und zeigt, dass alle Komponenten (Datenbank, VAPID-Schlüssel, Composer-Abhängigkeiten) korrekt konfiguriert sind

2. **Vereinfachte Versionen erstellt**
   - Vereinfachte Versionen der problematischen Dateien wurden erstellt (`admin/push_simple.php` und `admin/simple_test.php`)
   - Auch diese vereinfachten Versionen werden als Quellcode angezeigt

3. **PHP-Konfiguration überprüft**
   - Die PHP-Konfiguration des Servers wurde überprüft (PHP 8.2)
   - Alle relevanten PHP-Einstellungen sind korrekt konfiguriert

4. **Dateiendungen und Berechtigungen überprüft**
   - Die Dateiendungen wurden überprüft und sind korrekt (`.php`)
   - Die Berechtigungen wurden auf `755` gesetzt

5. **.htaccess-Datei erstellt**
   - Eine `.htaccess`-Datei wurde erstellt, um den PHP-Handler zu konfigurieren
   - Dies hat das Problem nicht gelöst

6. **Einfache Test-Dateien erstellt**
   - Einfache PHP-Test-Dateien (`test.php` und `phpinfo.php`) wurden erstellt
   - Diese Dateien werden korrekt ausgeführt

### 2. Selektives Verhalten

**Beobachtung:**
Es ist auffällig, dass einige PHP-Dateien korrekt ausgeführt werden, während andere als Quellcode angezeigt werden. Dies deutet auf ein selektives Problem hin, das möglicherweise mit der Serverkonfiguration oder dem Inhalt der Dateien zusammenhängt.

**Mögliche Ursachen:**

1. **Serverkonfiguration**
   - Möglicherweise gibt es eine spezifische Serverkonfiguration, die bestimmte PHP-Dateien oder Verzeichnisse anders behandelt

2. **Dateiinhalt**
   - Der Inhalt der problematischen Dateien könnte ein bestimmtes Muster enthalten, das vom Server falsch interpretiert wird

3. **Caching-Probleme**
   - Es könnte ein Caching-Problem vorliegen, bei dem der Server alte Versionen der Dateien ausliefert

## Aktuelle Situation

Die Push-Benachrichtigungsfunktionalität ist technisch vollständig implementiert und funktioniert grundsätzlich:

1. Die Datenbank-Tabelle für Push-Subscriptions wurde erstellt und enthält bereits ein Abonnement
2. Die VAPID-Schlüssel wurden generiert und korrekt konfiguriert
3. Die Composer-Abhängigkeiten wurden installiert
4. Die Integration in die Hardware-Events wurde implementiert

Das einzige verbleibende Problem ist, dass die Administrationsoberfläche für Push-Benachrichtigungen nicht korrekt angezeigt wird, da die PHP-Dateien als Quellcode angezeigt werden.

## Empfehlungen für weitere Schritte

1. **Hosting-Anbieter kontaktieren**
   - Den Hosting-Anbieter kontaktieren und das Problem mit der selektiven Ausführung von PHP-Dateien schildern
   - Möglicherweise gibt es eine spezifische Serverkonfiguration, die angepasst werden muss

2. **Alternative Administrationsoberfläche**
   - Eine alternative Administrationsoberfläche erstellen, die auf einer anderen Technologie basiert (z.B. JavaScript mit AJAX-Aufrufen zu PHP-APIs)
   - Dies würde das Problem umgehen, da die PHP-Dateien dann nur als API-Endpunkte dienen würden

3. **Dateistruktur überprüfen**
   - Die Dateistruktur und -organisation überprüfen, um sicherzustellen, dass keine versteckten Probleme vorliegen
   - Möglicherweise gibt es Konflikte mit anderen Dateien oder Verzeichnissen

## Fazit

Die Push-Benachrichtigungsfunktionalität wurde erfolgreich implementiert, aber es gibt ein Problem mit der Anzeige bestimmter PHP-Dateien im Browser. Dieses Problem scheint spezifisch für den Server oder die Konfiguration zu sein und betrifft nur bestimmte PHP-Dateien im `admin/`-Verzeichnis.

Die grundlegende Funktionalität der Push-Benachrichtigungen ist vorhanden und kann genutzt werden, sobald das Problem mit der Administrationsoberfläche gelöst ist. Die Debug-Seite `admin/push_debug.php` funktioniert korrekt und zeigt, dass alle Komponenten korrekt konfiguriert sind.