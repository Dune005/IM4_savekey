// admin_register.js
document.addEventListener("DOMContentLoaded", function() {
  // Funktion zum Extrahieren von URL-Parametern
  function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    const results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
  }

  // Seriennummer aus der URL extrahieren
  const seriennummer = getUrlParameter('seriennummer');
  
  // Wenn keine Seriennummer in der URL vorhanden ist, zur Startseite umleiten
  if (!seriennummer) {
    alert("Keine gültige Seriennummer gefunden. Bitte scannen Sie den QR-Code erneut.");
    window.location.href = "index.html";
    return;
  }
  
  // Seriennummer in das Formularfeld eintragen
  document.getElementById("seriennummer").value = seriennummer;
  
  // Event-Listener für das Formular
  document.getElementById("adminRegisterForm").addEventListener("submit", async (e) => {
    e.preventDefault();

    // Alle Formularfelder abrufen
    const vorname = document.getElementById("vorname").value.trim();
    const nachname = document.getElementById("nachname").value.trim();
    const benutzername = document.getElementById("benutzername").value.trim();
    const mail = document.getElementById("mail").value.trim();
    const password = document.getElementById("password").value.trim();
    const phone = document.getElementById("phone")?.value.trim() || null;
    // Seriennummer ist bereits gesetzt und readonly

    try {
      console.log("Sende Admin-Registrierungsdaten:", { 
        vorname, 
        nachname, 
        benutzername, 
        mail, 
        password: "***", 
        phone, 
        seriennummer 
      });

      const response = await fetch("api/admin_register.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          vorname,
          nachname,
          benutzername,
          mail,
          password,
          phone,
          seriennummer
        }),
      });

      // Überprüfen, ob die Antwort gültiges JSON enthält
      const responseText = await response.text();
      console.log("Server-Antwort:", responseText);

      let result;
      try {
        result = JSON.parse(responseText);
      } catch (jsonError) {
        console.error("JSON-Parsing-Fehler:", jsonError);
        alert("Der Server hat eine ungültige Antwort zurückgegeben. Bitte kontaktieren Sie den Administrator.");
        return;
      }

      if (result.status === "success") {
        alert("Admin-Registrierung erfolgreich! Sie können sich jetzt als Administrator einloggen.");
        window.location.href = "login.html";
      } else {
        alert(result.message || "Admin-Registrierung fehlgeschlagen.");
      }
    } catch (error) {
      console.error("Netzwerkfehler:", error);
      alert("Ein Netzwerkfehler ist aufgetreten. Bitte überprüfen Sie Ihre Internetverbindung und versuchen Sie es erneut.");
    }
  });
});
