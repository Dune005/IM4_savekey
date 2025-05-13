// admin_register.js
document.addEventListener("DOMContentLoaded", function() {
  // Funktion zum Extrahieren von URL-Parametern
  function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    const results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
  }

  // Token aus der URL extrahieren
  const token = getUrlParameter('token');

  // Wenn kein Token in der URL vorhanden ist, prüfen ob eine direkte Seriennummer angegeben wurde (für Testzwecke)
  if (!token) {
    const seriennummer = getUrlParameter('seriennummer');

    if (!seriennummer) {
      alert("Kein gültiger Token gefunden. Bitte scannen Sie den QR-Code erneut.");
      window.location.href = "index.html";
      return;
    }

    // Seriennummer in das Formularfeld eintragen (nur für Testzwecke)
    document.getElementById("seriennummer").value = seriennummer;
    document.getElementById("token").value = "";
  } else {
    // Token im versteckten Feld speichern
    document.getElementById("token").value = token;

    // Seriennummer vom Server abrufen
    fetch("api/verify_token.php?token=" + encodeURIComponent(token))
      .then(response => response.json())
      .then(data => {
        if (data.status === "success") {
          // Seriennummer in das Formularfeld eintragen
          document.getElementById("seriennummer").value = data.seriennummer;
        } else {
          alert("Ungültiger oder abgelaufener Token. Bitte scannen Sie den QR-Code erneut.");
          window.location.href = "index.html";
        }
      })
      .catch(error => {
        console.error("Fehler beim Verifizieren des Tokens:", error);
        alert("Fehler beim Verifizieren des Tokens. Bitte versuchen Sie es später erneut.");
        window.location.href = "index.html";
      });
  }

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
    const seriennummer = document.getElementById("seriennummer").value.trim();
    const token = document.getElementById("token").value.trim();

    try {
      console.log("Sende Admin-Registrierungsdaten:", {
        vorname,
        nachname,
        benutzername,
        mail,
        password: "***",
        phone,
        seriennummer,
        token: token ? "***" : ""
      });

      // Entweder Token oder Seriennummer senden, je nachdem was verfügbar ist
      const formData = {
        vorname,
        nachname,
        benutzername,
        mail,
        password,
        phone
      };

      // Wenn ein Token vorhanden ist, diesen verwenden, sonst die Seriennummer
      if (token) {
        formData.token = token;
      } else {
        formData.seriennummer = seriennummer;
      }

      const response = await fetch("api/admin_register.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams(formData),
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
