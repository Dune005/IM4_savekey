// register.js

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
  ['vorname', 'nachname', 'benutzername', 'mail', 'password', 'phone', 'seriennummer'].forEach(fieldId => {
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

// Event-Listener für Echtzeit-Validierung
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('registerForm');
  const fields = ['vorname', 'nachname', 'benutzername', 'mail', 'password', 'phone', 'seriennummer'];
  
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
});

document
  .getElementById("registerForm")
  .addEventListener("submit", async (e) => {
    e.preventDefault();
    
    // Alle vorherigen Fehler löschen
    clearAllFieldErrors();

    // Get all form fields
    const vorname = document.getElementById("vorname").value.trim();
    const nachname = document.getElementById("nachname").value.trim();
    const benutzername = document.getElementById("benutzername").value.trim();
    const mail = document.getElementById("mail").value.trim();
    const password = document.getElementById("password").value.trim();
    const phone = document.getElementById("phone")?.value.trim() || null;
    const seriennummer = document.getElementById("seriennummer").value.trim();

    // Client-seitige Validierung
    let isValid = true;
    
    if (!validateField('vorname', vorname)) isValid = false;
    if (!validateField('nachname', nachname)) isValid = false;
    if (!validateField('benutzername', benutzername)) isValid = false;
    if (!validateField('mail', mail)) isValid = false;
    if (!validateField('password', password)) isValid = false;
    if (!validateField('seriennummer', seriennummer)) isValid = false;
    
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
      showFormMessage('Registrierung wird verarbeitet...', 'info');
      
      console.log("Sende Registrierungsdaten:", { vorname, nachname, benutzername, mail, password: "***", phone, seriennummer });

      const response = await fetch("api/register.php", {
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
        showFormMessage("Der Server hat eine ungültige Antwort zurückgegeben. Bitte kontaktieren Sie den Administrator.", 'error');
        return;
      }

      if (result.status === "success") {
        showFormMessage("Registrierung erfolgreich! Sie werden zum Login weitergeleitet...", 'success');
        setTimeout(() => {
          window.location.href = "login.html";
        }, 2000);
      } else {
        showFormMessage(result.message || "Registrierung fehlgeschlagen. Bitte überprüfen Sie Ihre Eingaben.", 'error');
      }
    } catch (error) {
      console.error("Netzwerkfehler:", error);
      showFormMessage("Ein Netzwerkfehler ist aufgetreten. Bitte überprüfen Sie Ihre Internetverbindung und versuchen Sie es erneut.", 'error');
    }
  });
