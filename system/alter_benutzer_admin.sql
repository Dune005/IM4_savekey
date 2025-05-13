-- alter_benutzer_admin.sql
-- Fügt die is_admin-Spalte zur Benutzertabelle hinzu

-- Überprüfen, ob die Spalte bereits existiert
SET @exists = 0;
SELECT 1 INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'benutzer' AND COLUMN_NAME = 'is_admin' AND TABLE_SCHEMA = DATABASE();

-- Spalte nur hinzufügen, wenn sie noch nicht existiert
SET @query = IF(@exists = 0, 
    'ALTER TABLE `benutzer` ADD COLUMN `is_admin` BOOLEAN NOT NULL DEFAULT FALSE AFTER `rfid_uid`',
    'SELECT "is_admin-Spalte existiert bereits" AS message');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
