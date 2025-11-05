-- Device Reset Commands Table Setup
-- Diese Tabelle speichert Reset-Befehle für Schlüsselboxen
-- Wird automatisch beim ersten Aufruf von reset_device.php erstellt

CREATE TABLE IF NOT EXISTS device_reset_commands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    seriennummer VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    executed TINYINT(1) DEFAULT 0,
    executed_at TIMESTAMP NULL,
    initiated_by VARCHAR(100) DEFAULT NULL,
    reason VARCHAR(255) DEFAULT NULL,
    INDEX idx_seriennummer (seriennummer),
    INDEX idx_executed (executed)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
