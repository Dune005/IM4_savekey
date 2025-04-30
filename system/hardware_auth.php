<?php
// hardware_auth.php - Enthält Authentifizierungsinformationen für die Hardware

// API-Schlüssel für die Hardware-Authentifizierung
// In einer Produktionsumgebung sollte dieser Schlüssel sicher gespeichert werden
// und nicht im Code stehen
define('HARDWARE_API_KEY', 'sk_hardware_' . bin2hex(random_bytes(16)));

// Hinweis: Bei der ersten Ausführung wird ein zufälliger API-Schlüssel generiert.
// Dieser sollte dann in der Arduino-Konfiguration verwendet werden.
// In einer Produktionsumgebung sollte der Schlüssel manuell gesetzt und nicht
// bei jedem Aufruf neu generiert werden.
