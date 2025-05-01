#include <Wire.h>
#include <Adafruit_PN532.h>

#define PN532_IRQ   (2)
#define PN532_RESET (3)
#define SDA_PIN     6
#define SCL_PIN     7

const int buttonPin = 8;

Adafruit_PN532 nfc(PN532_IRQ, PN532_RESET, &Wire);

void setup() {
  Serial.begin(115200);
  pinMode(buttonPin, INPUT_PULLDOWN);

  while (!Serial) delay(10);

  Serial.println("PN532 NFC Reader Test (ESP32-C6, I2C)");
  Wire.begin(SDA_PIN, SCL_PIN);

  nfc.begin();
  uint32_t versiondata = nfc.getFirmwareVersion();
  if (!versiondata) {
    Serial.println("Kein PN532 gefunden – Verbindung prüfen.");
    while (1);
  }

  Serial.print("Found chip PN5"); Serial.println((versiondata >> 24) & 0xFF, HEX);
  Serial.print("Firmware Version: "); Serial.print((versiondata >> 16) & 0xFF, DEC);
  Serial.print('.'); Serial.println((versiondata >> 8) & 0xFF, DEC);

  nfc.SAMConfig();
  Serial.println("Warte auf ein RFID/NFC Tag...");
}

void loop() {
  // --- TASTER ABFRAGEN ALLE 10ms ---
  static unsigned long lastButtonCheck = 0;
  unsigned long currentMillis = millis();

  if (currentMillis - lastButtonCheck >= 10) {
    lastButtonCheck = currentMillis;
    int buttonState = digitalRead(buttonPin);
    Serial.println(buttonState); // 1 = gedrückt, 0 = offen
  }

  // --- RFID NICHT-BLOCKIEREND LESEN ---
  uint8_t success;
  uint8_t uid[7];
  uint8_t uidLength;

  // Nur EINMAL pro Loop testen
  success = nfc.readPassiveTargetID(PN532_MIFARE_ISO14443A, uid, &uidLength, 50); // Timeout 50ms

  if (success) {
    Serial.print("Tag erkannt, UID: ");
    for (uint8_t i = 0; i < uidLength; i++) {
      Serial.print(uid[i], HEX); Serial.print(" ");
    }
    Serial.println();
  }
}