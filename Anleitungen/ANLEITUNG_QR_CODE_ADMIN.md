# Anleitung zur Erstellung von QR-Codes für die Admin-Registrierung

Diese Anleitung beschreibt, wie Sie QR-Codes erstellen können, die zur Registrierung von Administratoren für Ihre SaveKey-Schlüsselboxen verwendet werden können.

## Überblick

Wenn ein Kunde eine neue SaveKey-Schlüsselbox kauft, sollte ein QR-Code beiliegen, der zur Admin-Registrierung für diese spezifische Box verwendet werden kann. Der QR-Code enthält einen Link zur Admin-Registrierungsseite mit einem verschlüsselten Token, der die Seriennummer der Box enthält.

## Sicherheitskonzept

Um zu verhindern, dass unbefugte Personen die Seriennummer manipulieren können, verwenden wir ein Token-basiertes System:

1. Die Seriennummer wird in einen verschlüsselten Token umgewandelt
2. Der Token wird als URL-Parameter verwendet
3. Der Token enthält ein Ablaufdatum (standardmäßig 30 Tage)
4. Der Server entschlüsselt den Token und extrahiert die Seriennummer

Dieses Verfahren bietet mehrere Sicherheitsvorteile:
- Die Seriennummer ist in der URL nicht sichtbar
- Der Token kann nur einmal verwendet werden
- Der Token läuft nach einer bestimmten Zeit ab
- Der Token kann nur vom Server entschlüsselt werden

## URL-Format

Der QR-Code enthält einen Link im folgenden Format:

```
https://ihre-domain.com/admin_register.html?token=VERSCHLÜSSELTER_TOKEN
```

Wobei:
- `ihre-domain.com` ist die Domain, auf der Ihre SaveKey-Anwendung gehostet wird
- `VERSCHLÜSSELTER_TOKEN` ist ein verschlüsselter String, der die Seriennummer und weitere Informationen enthält

## Beispiel

Für eine Schlüsselbox mit der Seriennummer "A001" könnte der QR-Code auf einen Link wie diesen verweisen:

```
https://ihre-domain.com/admin_register.html?token=eyJzZXJpZW5udW1tZXIiOiJBMDAxIiwidGltZXN0YW1wIjoxNjM0NTY3ODkwLCJleHBpcmVzIjoxNjM3MTU5ODkwfQ
```

## Erstellung von QR-Codes

Es gibt verschiedene Möglichkeiten, QR-Codes mit verschlüsselten Tokens zu erstellen:

### 1. Integriertes QR-Code-Generator-Tool

Die SaveKey-Anwendung enthält ein integriertes Tool zum Generieren von QR-Codes mit verschlüsselten Tokens:

1. Melden Sie sich als Administrator an
2. Navigieren Sie zu `admin/generate_qr.php`
3. Geben Sie die Seriennummer der Schlüsselbox ein
4. Klicken Sie auf "QR-Code generieren"
5. Verwenden Sie die generierte URL mit einem Online-QR-Code-Generator

Das Tool generiert automatisch einen verschlüsselten Token, der die Seriennummer enthält und 30 Tage gültig ist.

### 2. Online QR-Code-Generatoren

Nachdem Sie die URL mit dem verschlüsselten Token generiert haben, können Sie kostenlose Online-Tools verwenden, um QR-Codes zu erstellen:

- [QR Code Generator](https://www.qr-code-generator.com/)
- [QRCode Monkey](https://www.qrcode-monkey.com/)
- [GoQR.me](https://goqr.me/)

Geben Sie einfach die vollständige URL mit dem Token ein und laden Sie den generierten QR-Code herunter.

### 3. Batch-Erstellung mit einem Skript

Für die Massenproduktion können Sie ein PHP-Skript verwenden, das die Token-Generierungsfunktion nutzt:

```php
<?php
// QR-Code-Batch-Generator
require_once 'system/token_utils.php';

// Basis-URL
$baseUrl = "https://ihre-domain.com";

// Liste der Seriennummern
$seriennummern = ["A001", "A002", "A003", "B001", "B002"];

// Ausgabe der URLs für jede Seriennummer
foreach ($seriennummern as $seriennummer) {
    $url = generateAdminRegistrationUrl($seriennummer, $baseUrl);
    echo "Seriennummer: $seriennummer\n";
    echo "URL: $url\n\n";

    // Hier könnten Sie die URL direkt an eine QR-Code-Bibliothek übergeben
    // oder in eine Datei schreiben, die später verarbeitet wird
}
```

Dieses Skript nutzt die integrierte Token-Generierungsfunktion, um sicherzustellen, dass alle Tokens mit dem gleichen Verschlüsselungsalgorithmus erstellt werden.

## Drucken und Verpacken

1. Drucken Sie die QR-Codes auf einem hochwertigen Drucker aus
2. Schneiden Sie sie aus und legen Sie sie der Schlüsselbox bei
3. Fügen Sie eine kurze Anleitung hinzu, die erklärt, dass dieser QR-Code zur Registrierung des Administrators für die Box verwendet werden soll

## Sicherheitsvorteile des Token-Systems

Das Token-basierte System bietet folgende Sicherheitsvorteile:

1. **Verschleierung der Seriennummer**: Die Seriennummer ist in der URL nicht sichtbar, was Manipulationen erschwert
2. **Zeitliche Begrenzung**: Tokens laufen nach 30 Tagen ab, was das Risiko von Missbrauch reduziert
3. **Kryptografische Sicherheit**: Die Verschlüsselung mit AES-256-CBC bietet ein hohes Maß an Sicherheit
4. **Einmalverwendung**: Theoretisch könnte das System erweitert werden, um Tokens nach der Verwendung zu invalidieren

## Sicherheitshinweise

- Bewahren Sie die QR-Codes dennoch sicher auf, da jeder, der Zugriff auf einen QR-Code hat, sich als Administrator für die entsprechende Box registrieren kann
- Der geheime Schlüssel für die Token-Verschlüsselung (`TOKEN_SECRET_KEY` in `system/token_utils.php`) sollte in einer Produktionsumgebung sicher aufbewahrt werden
- Dokumentieren Sie, welche QR-Codes mit welchen Boxen ausgeliefert wurden
- Erwägen Sie die Implementierung einer zusätzlichen Verifizierung, z.B. per E-Mail, bevor ein Administrator-Konto aktiviert wird

## Fehlerbehebung

- **QR-Code wird nicht erkannt**: Stellen Sie sicher, dass der QR-Code eine ausreichende Größe und Qualität hat
- **Token wird als ungültig erkannt**: Überprüfen Sie, ob der Token abgelaufen ist (nach 30 Tagen) oder ob der geheime Schlüssel geändert wurde
- **Verschlüsselungsfehler**: Stellen Sie sicher, dass die OpenSSL-Erweiterung in PHP aktiviert ist
- **URL ist zu lang**: Wenn die URL zu lang für einen QR-Code ist, reduzieren Sie die Datenmenge im Token oder verwenden Sie einen QR-Code mit höherer Dichte
