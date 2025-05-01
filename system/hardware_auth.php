<?php
// hardware_auth.php - Enthält Authentifizierungsinformationen für die Hardware

// API-Schlüssel für die Hardware-Authentifizierung
// Fester API-Schlüssel für die Produktion
define('HARDWARE_API_KEY', 'sk_hardware_safekey_12345');

// In einer Entwicklungsumgebung könnte man auch einen zufälligen Schlüssel generieren:
// define('HARDWARE_API_KEY', 'sk_hardware_' . bin2hex(random_bytes(16)));

// WICHTIG: Verwende diesen gleichen Schlüssel in deinem Arduino-Code!
