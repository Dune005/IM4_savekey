-- setup_arduino_api_tables.sql
-- Erstellt die notwendigen Tabellen für die Arduino-API

-- 1. Tabelle für ausstehende Schlüsselaktionen (vor RFID/NFC-Bestätigung)
CREATE TABLE IF NOT EXISTS `pending_key_actions` (
  `id` INT(10) NOT NULL AUTO_INCREMENT,
  `seriennummer` VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_type` ENUM('remove', 'return') NOT NULL,
  `timestamp` DATETIME NOT NULL,
  `status` ENUM('pending', 'completed', 'expired') NOT NULL DEFAULT 'pending',
  `completed_by` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `completed_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_seriennummer` (`seriennummer`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Tabelle für die Schlüsselentnahme und -rückgabe
CREATE TABLE IF NOT EXISTS `key_logs` (
  `box_id` INT(10) NOT NULL,
  `timestamp_take` DATETIME(3) NOT NULL,
  `timestamp_return` DATETIME(3) NULL DEFAULT NULL,
  `benutzername` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`box_id`, `timestamp_take`),
  CONSTRAINT `fk_key_logs_benutzer`
    FOREIGN KEY (`benutzername`)
    REFERENCES `benutzer` (`benutzername`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Überprüfen, ob die RFID-Spalte in der Benutzertabelle existiert
SET @exists = 0;
SELECT 1 INTO @exists FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'benutzer' AND COLUMN_NAME = 'rfid_uid' AND TABLE_SCHEMA = DATABASE();

-- RFID-Spalte nur hinzufügen, wenn sie noch nicht existiert
SET @query = IF(@exists = 0, 
    'ALTER TABLE `benutzer` ADD COLUMN `rfid_uid` VARCHAR(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `seriennummer`, ADD UNIQUE INDEX `idx_rfid_uid` (`rfid_uid`)',
    'SELECT "RFID-Spalte existiert bereits" AS message');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
