-- key_logs.sql
-- Erstellt die Tabelle für die Schlüsselentnahme und -rückgabe

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

-- Beispieldaten einfügen (optional)
-- INSERT INTO `key_logs` (`box_id`, `timestamp_take`, `timestamp_return`, `benutzername`) VALUES
-- (1001, '2023-06-01 08:30:00', '2023-06-01 17:15:00', 'benutzer1'),
-- (1001, '2023-06-02 09:00:00', '2023-06-02 16:45:00', 'benutzer2'),
-- (1001, '2023-06-05 08:45:00', NULL, 'benutzer1');
