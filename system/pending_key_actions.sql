-- pending_key_actions.sql
-- Erstellt die Tabelle für ausstehende Schlüsselaktionen (vor RFID/NFC-Bestätigung)

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
