/*
 * Reset-Check Funktionalität für SaveKey
 * 
 * Diese Funktionen ermöglichen es, die Box über die Web-Oberfläche neu zu starten.
 * Füge diese Zeilen in deine savekey.ino ein:
 * 
 * 1. In loop() nach manageLED():
 *    
 *    static unsigned long lastResetCheck = 0;
 *    unsigned long now = millis();
 *    if (now - lastResetCheck >= 30000) {
 *      lastResetCheck = now;
 *      checkForResetCommand();
 *    }
 * 
 * 2. Diese Funktion am Ende der Datei vor der letzten Klammer hinzufügen
 */

void checkForResetCommand() {
  if (!wifiAvailable || WiFi.status() != WL_CONNECTED) {
    return;
  }

  HTTPClient http;
  String checkUrl = String(API_ENDPOINT) + "?action=check_reset&seriennummer=" + seriennummer;
  http.begin(checkUrl);
  http.addHeader("X-Api-Key", API_KEY);

  int httpResponseCode = http.GET();

  if (httpResponseCode == 200) {
    String response = http.getString();
    StaticJsonDocument<200> doc;
    DeserializationError error = deserializeJson(doc, response);

    if (!error && doc["reset"] == true) {
      Serial.println("🔄 Reset-Befehl empfangen vom Server!");
      Serial.println("Neustart wird in 2 Sekunden eingeleitet...");
      
      // LED kurz blinken lassen als Feedback
      for (int i = 0; i < 3; i++) {
        digitalWrite(LED_PIN, HIGH);
        delay(200);
        digitalWrite(LED_PIN, LOW);
        delay(200);
      }
      
      http.end();
      delay(2000);
      ESP.restart(); // Hardware-Neustart
    }
  } else if (httpResponseCode > 0) {
    Serial.print("Reset-Check HTTP Response Code: ");
    Serial.println(httpResponseCode);
  }

  http.end();
}
