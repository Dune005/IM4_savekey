-- alter_benutzer_seriennummer.sql
-- Fügt die Seriennummer-Spalte zur Benutzertabelle hinzu

-- Überprüfen, ob die Spalte bereits existiert
SET @exists = 0;
SELECT 1 INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'benutzer' AND COLUMN_NAME = 'seriennummer' AND TABLE_SCHEMA = DATABASE();

-- Spalte nur hinzufügen, wenn sie noch nicht existiert
SET @query = IF(@exists = 0, 
    'ALTER TABLE `benutzer` ADD COLUMN `seriennummer` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `phone`',
    'SELECT "Seriennummer-Spalte existiert bereits" AS message');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
