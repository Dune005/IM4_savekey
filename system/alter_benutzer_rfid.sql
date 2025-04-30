-- alter_benutzer_rfid.sql
-- Fügt das RFID/NFC-Feld zur Benutzertabelle hinzu

-- Überprüfen, ob die Spalte bereits existiert
SET @exists = 0;
SELECT 1 INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'benutzer' AND COLUMN_NAME = 'rfid_uid' AND TABLE_SCHEMA = DATABASE();

-- Spalte nur hinzufügen, wenn sie noch nicht existiert
SET @query = IF(@exists = 0, 
    'ALTER TABLE `benutzer` ADD COLUMN `rfid_uid` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `seriennummer`, ADD UNIQUE INDEX `idx_rfid_uid` (`rfid_uid`)',
    'SELECT "RFID-Spalte existiert bereits" AS message');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
