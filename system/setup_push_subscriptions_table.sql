-- setup_push_subscriptions_table.sql
-- Erstellt die Tabelle für Push-Benachrichtigungs-Abonnements

-- Überprüfen, ob die Tabelle bereits existiert
SET @exists = 0;
SELECT 1 INTO @exists FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME = 'push_subscriptions' AND TABLE_SCHEMA = DATABASE();

-- Tabelle nur erstellen, wenn sie noch nicht existiert
SET @query = IF(@exists = 0, 
    'CREATE TABLE `push_subscriptions` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `user_id` INT(10) NULL DEFAULT NULL,
        `endpoint` VARCHAR(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `p256dh` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `auth` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
        `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        INDEX `idx_user_id` (`user_id`),
        UNIQUE INDEX `idx_endpoint` (`endpoint`(255)),
        CONSTRAINT `fk_push_subscriptions_benutzer`
            FOREIGN KEY (`user_id`)
            REFERENCES `benutzer` (`user_id`)
            ON DELETE CASCADE
            ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    'SELECT "Tabelle push_subscriptions existiert bereits" AS message');

PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
