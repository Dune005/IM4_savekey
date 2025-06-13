# Anleitung zur Einrichtung von Administrator-Benutzern

Diese Anleitung beschreibt, wie Sie Administrator-Benutzer im SaveKey-System einrichten können.

## Über Benutzerrollen

Das SaveKey-System unterscheidet zwischen zwei Benutzerrollen:

1. **Normale Benutzer**:
   - Können den Status ihres Schlüssels einsehen
   - Können die Schlüsselhistorie einsehen
   - Können KEINE Schlüssel entnehmen oder zurückgeben über die Weboberfläche
   - Können RFID/NFC-Chips zuweisen und entfernen

2. **Administrator-Benutzer**:
   - Haben alle Rechte der normalen Benutzer
   - Können Schlüssel über die Weboberfläche entnehmen und zurückgeben
   - Können RFID/NFC-Chips zuweisen und entfernen
   - Haben Zugriff auf alle Funktionen des Systems

## Datenbank-Änderungen

Um die Administrator-Funktionalität zu aktivieren, müssen Sie die Datenbank aktualisieren:

```sql
-- Führe die Datei system/alter_benutzer_admin.sql aus
SOURCE system/alter_benutzer_admin.sql;
```

Diese SQL-Datei fügt eine neue Spalte `is_admin` zur Tabelle `benutzer` hinzu. Der Standardwert ist `FALSE`, was bedeutet, dass alle bestehenden Benutzer als normale Benutzer eingestuft werden.

## Einen Administrator einrichten

Um einen bestehenden Benutzer zum Administrator zu machen, führen Sie das folgende SQL-Skript aus:

```sql
-- Führe die Datei system/set_admin_user.sql aus
SOURCE system/set_admin_user.sql;
```

Alternativ können Sie auch direkt einen SQL-Befehl ausführen:

```sql
-- Benutzer mit Benutzernamen 'admin' als Administrator setzen
UPDATE benutzer SET is_admin = TRUE WHERE benutzername = 'admin';

-- Überprüfen, ob der Benutzer als Administrator gesetzt wurde
SELECT user_id, benutzername, vorname, nachname, mail, is_admin
FROM benutzer
WHERE is_admin = TRUE;
```

## Neue Benutzer

Neue Benutzer, die sich über die Registrierungsseite anmelden, werden automatisch als normale Benutzer eingestuft. Um einen neuen Benutzer zum Administrator zu machen, müssen Sie nach der Registrierung den oben genannten SQL-Befehl ausführen.

## Sicherheitshinweise

- Vergeben Sie Administrator-Rechte nur an vertrauenswürdige Personen
- Administratoren können Schlüssel entnehmen und zurückgeben, was ein hohes Maß an Verantwortung erfordert
- Überwachen Sie regelmäßig die Liste der Administratoren, um sicherzustellen, dass keine unbefugten Personen Zugriff haben

## Überprüfen der Benutzerrollen

Um zu überprüfen, welche Benutzer Administrator-Rechte haben, führen Sie den folgenden SQL-Befehl aus:

```sql
SELECT user_id, benutzername, vorname, nachname, mail, is_admin
FROM benutzer
ORDER BY is_admin DESC, benutzername ASC;
```

Diese Abfrage zeigt alle Benutzer an, wobei Administratoren zuerst aufgelistet werden.
