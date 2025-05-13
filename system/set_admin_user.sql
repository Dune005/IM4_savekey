-- set_admin_user.sql
-- Setzt einen bestehenden Benutzer als Administrator

-- Beispiel: Benutzer mit Benutzernamen 'admin' als Administrator setzen
UPDATE benutzer SET is_admin = TRUE WHERE benutzername = 'admin';

-- Alternativ: Benutzer mit einer bestimmten E-Mail-Adresse als Administrator setzen
-- UPDATE benutzer SET is_admin = TRUE WHERE mail = 'admin@example.com';

-- Alternativ: Benutzer mit einer bestimmten ID als Administrator setzen
-- UPDATE benutzer SET is_admin = TRUE WHERE user_id = 1;

-- Überprüfen, ob der Benutzer als Administrator gesetzt wurde
SELECT user_id, benutzername, vorname, nachname, mail, is_admin 
FROM benutzer 
WHERE is_admin = TRUE;
