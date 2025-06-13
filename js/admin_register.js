// admin_register.js

// Utility-Funktionen für Accessibility
function showFormMessage(message, type = 'error') {
  const messageContainer = document.getElementById('form-messages');
  messageContainer.textContent = message;
  messageContainer.className = type === 'success' ? 'success-message' : 'error-message';
  messageContainer.classList.remove('sr-only');
  
  // Nach 5 Sekunden wieder verstecken
  setTimeout(() => {
    messageContainer.classList.add('sr-only');
  }, 5000);
}

function showFieldError(fieldId, message) {
  const field = document.getElementById(fieldId);
  const errorContainer = document.getElementById(`${fieldId}-error`);
  
  if (field && errorContainer) {
    field.classList.add('error');
    field.setAttribute('aria-invalid', 'true');
    errorContainer.textContent = message;
    errorContainer.style.display = 'block';
  }
}

function clearFieldError(fieldId) {
  const field = document.getElementById(fieldId);
  const errorContainer = document.getElementById(`${fieldId}-error`);
  
  if (field && errorContainer) {
    field.classList.remove('error');
    field.setAttribute('aria-invalid', 'false');
    errorContainer.textContent = '';
    errorContainer.style.display = 'none';
  }
}

function clearAllFieldErrors() {
  ['vorname', 'nachname', 'benutzername', 'mail', 'password', 'phone'].forEach(fieldId => {
    clearFieldError(fieldId);
  });
}

function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function validateField(fieldId, value) {
  const field = document.getElementById(fieldId);
  const isRequired = field.hasAttribute('required');
  
  if (isRequired && !value.trim()) {
    showFieldError(fieldId, 'Dieses Feld ist erforderlich.');
    return false;
  }
  
  // Spezifische Validierungen
  if (fieldId === 'mail' && value.trim() && !validateEmail(value)) {
    showFieldError(fieldId, 'Bitte geben Sie eine gültige E-Mail-Adresse ein.');
    return false;
  }
  
  if (fieldId === 'password' && value.trim() && value.length < 6) {
    showFieldError(fieldId, 'Das Passwort muss mindestens 6 Zeichen lang sein.');
    return false;
  }
  
  if (fieldId === 'benutzername' && value.trim() && value.length < 3) {
    showFieldError(fieldId, 'Der Benutzername muss mindestens 3 Zeichen lang sein.');
    return false;
  }
  
  clearFieldError(fieldId);
  return true;
}

document.addEventListener("DOMContentLoaded", function() {
  // Event-Listener für Echtzeit-Validierung
  const fields = ['vorname', 'nachname', 'benutzername', 'mail', 'password', 'phone'];
  
  // Blur-Event für Echtzeit-Validierung
  fields.forEach(fieldId => {
    const field = document.getElementById(fieldId);
    if (field) {
      field.addEventListener('blur', function() {
        validateField(fieldId, this.value);
      });
      
      // Fehler löschen beim Tippen
      field.addEventListener('input', function() {
        if (this.classList.contains('error')) {
          clearFieldError(fieldId);
        }
      });
    }
  });

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
      showFormMessage("Kein gültiger Token gefunden. Bitte scannen Sie den QR-Code erneut.", 'error');
      setTimeout(() => {
        window.location.href = "index.html";
      }, 3000);
      return;
    }

    // Seriennummer in das Formularfeld eintragen (nur für Testzwecke)
    document.getElementById("seriennummer").value = seriennummer;
    document.getElementById("token").value = "";
  } else {
    // Token im versteckten Feld speichern
    document.getElementById("token").value = token;

    // Seriennummer vom Server abrufen
    showFormMessage("Token wird verifiziert...", 'info');
    
    fetch("api/verify_token.php?token=" + encodeURIComponent(token))
      .then(response => response.json())
      .then(data => {
        if (data.status === "success") {
          // Seriennummer in das Formularfeld eintragen
          document.getElementById("seriennummer").value = data.seriennummer;
          showFormMessage("Token erfolgreich verifiziert. Sie können sich jetzt registrieren.", 'success');
        } else {
          showFormMessage("Ungültiger oder abgelaufener Token. Sie werden zur Startseite weitergeleitet.", 'error');
          setTimeout(() => {
            window.location.href = "index.html";
          }, 3000);
        }
      })
      .catch(error => {
        console.error("Fehler beim Verifizieren des Tokens:", error);
        showFormMessage("Fehler beim Verifizieren des Tokens. Sie werden zur Startseite weitergeleitet.", 'error');
        setTimeout(() => {
          window.location.href = "index.html";
        }, 3000);
      });
  }

  // Event-Listener für das Formular
  document.getElementById("adminRegisterForm").addEventListener("submit", async (e) => {
    e.preventDefault();
    
    // Alle vorherigen Fehler löschen
    clearAllFieldErrors();

    // Alle Formularfelder abrufen
    const vorname = document.getElementById("vorname").value.trim();
    const nachname = document.getElementById("nachname").value.trim();
    const benutzername = document.getElementById("benutzername").value.trim();
    const mail = document.getElementById("mail").value.trim();
    const password = document.getElementById("password").value.trim();
    const phone = document.getElementById("phone")?.value.trim() || null;
    const seriennummer = document.getElementById("seriennummer").value.trim();
    const token = document.getElementById("token").value.trim();

    // Client-seitige Validierung
    let isValid = true;
    
    if (!validateField('vorname', vorname)) isValid = false;
    if (!validateField('nachname', nachname)) isValid = false;
    if (!validateField('benutzername', benutzername)) isValid = false;
    if (!validateField('mail', mail)) isValid = false;
    if (!validateField('password', password)) isValid = false;
    
    // Optionales Telefon-Feld validieren, falls ausgefüllt
    if (phone && !validateField('phone', phone)) isValid = false;
    
    if (!isValid) {
      showFormMessage('Bitte korrigieren Sie die markierten Fehler.', 'error');
      // Fokus auf das erste Fehlerfeld setzen
      const firstErrorField = document.querySelector('.error');
      if (firstErrorField) {
        firstErrorField.focus();
      }
      return;
    }

    try {
      showFormMessage('Admin-Registrierung wird verarbeitet...', 'info');
      
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
        showFormMessage("Der Server hat eine ungültige Antwort zurückgegeben. Bitte kontaktieren Sie den Administrator.", 'error');
        return;
      }

      if (result.status === "success") {
        showFormMessage("Admin-Registrierung erfolgreich! Sie werden zum Login weitergeleitet...", 'success');
        setTimeout(() => {
          window.location.href = "login.html";
        }, 2000);
      } else {
        showFormMessage(result.message || "Admin-Registrierung fehlgeschlagen. Bitte überprüfen Sie Ihre Eingaben.", 'error');
      }
    } catch (error) {
      console.error("Netzwerkfehler:", error);
      showFormMessage("Ein Netzwerkfehler ist aufgetreten. Bitte überprüfen Sie Ihre Internetverbindung und versuchen Sie es erneut.", 'error');
    }
  });
});
