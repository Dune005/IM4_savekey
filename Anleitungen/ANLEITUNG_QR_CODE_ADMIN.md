# Anleitung zur Erstellung von QR-Codes für die Admin-Registrierung

Diese Anleitung beschreibt, wie Sie QR-Codes erstellen können, die zur Registrierung von Administratoren für Ihre SafeKey-Schlüsselboxen verwendet werden können.

## Überblick

Wenn ein Kunde eine neue SafeKey-Schlüsselbox kauft, sollte ein QR-Code beiliegen, der zur Admin-Registrierung für diese spezifische Box verwendet werden kann. Der QR-Code enthält einen Link zur Admin-Registrierungsseite mit der Seriennummer der Box als Parameter.

## URL-Format

Der QR-Code sollte einen Link im folgenden Format enthalten:

```
https://ihre-domain.com/admin_register.html?seriennummer=BOXNUMMER
```

Wobei:
- `ihre-domain.com` ist die Domain, auf der Ihre SafeKey-Anwendung gehostet wird
- `BOXNUMMER` ist die eindeutige Seriennummer der Schlüsselbox (z.B. A001, B002, etc.)

## Beispiel

Für eine Schlüsselbox mit der Seriennummer "A001" würde der QR-Code auf folgenden Link verweisen:

```
https://ihre-domain.com/admin_register.html?seriennummer=A001
```

## Erstellung von QR-Codes

Es gibt verschiedene Möglichkeiten, QR-Codes zu erstellen:

### 1. Online QR-Code-Generatoren

Sie können kostenlose Online-Tools verwenden, um QR-Codes zu erstellen:

- [QR Code Generator](https://www.qr-code-generator.com/)
- [QRCode Monkey](https://www.qrcode-monkey.com/)
- [GoQR.me](https://goqr.me/)

Geben Sie einfach die vollständige URL (wie oben beschrieben) ein und laden Sie den generierten QR-Code herunter.

### 2. Batch-Erstellung mit einem Skript

Wenn Sie viele QR-Codes auf einmal erstellen müssen, können Sie ein Skript verwenden. Hier ist ein einfaches Python-Beispiel:

```python
import qrcode
import os

# Basis-URL
base_url = "https://ihre-domain.com/admin_register.html?seriennummer="

# Liste der Seriennummern
seriennummern = ["A001", "A002", "A003", "B001", "B002"]

# Verzeichnis für die QR-Codes erstellen
os.makedirs("qrcodes", exist_ok=True)

# QR-Codes für jede Seriennummer erstellen
for seriennummer in seriennummern:
    # Vollständige URL
    url = base_url + seriennummer
    
    # QR-Code erstellen
    qr = qrcode.QRCode(
        version=1,
        error_correction=qrcode.constants.ERROR_CORRECT_L,
        box_size=10,
        border=4,
    )
    qr.add_data(url)
    qr.make(fit=True)
    
    # QR-Code als Bild speichern
    img = qr.make_image(fill_color="black", back_color="white")
    img.save(f"qrcodes/qrcode_{seriennummer}.png")
    
    print(f"QR-Code für Seriennummer {seriennummer} erstellt")
```

Um dieses Skript zu verwenden, müssen Sie das Python-Paket `qrcode` installieren:

```
pip install qrcode[pil]
```

## Drucken und Verpacken

1. Drucken Sie die QR-Codes auf einem hochwertigen Drucker aus
2. Schneiden Sie sie aus und legen Sie sie der Schlüsselbox bei
3. Fügen Sie eine kurze Anleitung hinzu, die erklärt, dass dieser QR-Code zur Registrierung des Administrators für die Box verwendet werden soll

## Sicherheitshinweise

- Bewahren Sie die QR-Codes sicher auf, da jeder, der Zugriff auf einen QR-Code hat, sich als Administrator für die entsprechende Box registrieren kann
- Überlegen Sie, ob Sie zusätzliche Sicherheitsmaßnahmen implementieren möchten, z.B. einen Einmal-Code, der zusätzlich zur Seriennummer erforderlich ist
- Dokumentieren Sie, welche QR-Codes mit welchen Boxen ausgeliefert wurden

## Fehlerbehebung

- **QR-Code wird nicht erkannt**: Stellen Sie sicher, dass der QR-Code eine ausreichende Größe und Qualität hat
- **Link funktioniert nicht**: Überprüfen Sie, ob die Domain und der Pfad korrekt sind
- **Seriennummer wird nicht erkannt**: Stellen Sie sicher, dass die Seriennummer im korrekten Format ist und als URL-Parameter übergeben wird
