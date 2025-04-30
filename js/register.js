// register.js
document
  .getElementById("registerForm")
  .addEventListener("submit", async (e) => {
    e.preventDefault();

    // Get all form fields
    const vorname = document.getElementById("vorname").value.trim();
    const nachname = document.getElementById("nachname").value.trim();
    const benutzername = document.getElementById("benutzername").value.trim();
    const mail = document.getElementById("mail").value.trim();
    const password = document.getElementById("password").value.trim();
    const phone = document.getElementById("phone")?.value.trim() || null;

    try {
      console.log("Sende Registrierungsdaten:", { vorname, nachname, benutzername, mail, password: "***", phone });

      const response = await fetch("api/register.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: new URLSearchParams({
          vorname,
          nachname,
          benutzername,
          mail,
          password,
          phone
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
        alert("Registrierung erfolgreich! Sie können sich jetzt einloggen.");
        window.location.href = "login.html";
      } else {
        alert(result.message || "Registrierung fehlgeschlagen.");
      }
    } catch (error) {
      console.error("Netzwerkfehler:", error);
      alert("Ein Netzwerkfehler ist aufgetreten. Bitte überprüfen Sie Ihre Internetverbindung und versuchen Sie es erneut.");
    }
  });
