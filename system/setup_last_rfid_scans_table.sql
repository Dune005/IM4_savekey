-- setup_last_rfid_scans_table.sql
-- Erstellt die Tabelle für die zuletzt gescannten RFID-UIDs

-- Überprüfen, ob die Tabelle bereits existiert
SET @exists = 0;
SELECT 1 INTO @exists FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME = 'last_rfid_scans' AND TABLE_SCHEMA = DATABASE();

-- Tabelle nur erstellen, wenn sie noch nicht existiert
SET @query = IF(@exists = 0, 
    'CREATE TABLE `last_rfid_scans` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `seriennummer` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `rfid_uid` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `timestamp` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_seriennummer` (`seriennummer`),
        INDEX `idx_timestamp` (`timestamp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    'SELECT "Tabelle last_rfid_scans existiert bereits" AS message');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ausgabe einer Erfolgsmeldung
SELECT "Setup der Tabelle last_rfid_scans abgeschlossen" AS message;
