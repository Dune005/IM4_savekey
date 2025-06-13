// login.js

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
  ['benutzername', 'password'].forEach(fieldId => {
    clearFieldError(fieldId);
  });
}

function validateField(fieldId, value) {
  const field = document.getElementById(fieldId);
  const isRequired = field.hasAttribute('required');
  
  if (isRequired && !value.trim()) {
    showFieldError(fieldId, 'Dieses Feld ist erforderlich.');
    return false;
  }
  
  clearFieldError(fieldId);
  return true;
}

// Event-Listener für Echtzeit-Validierung
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('loginForm');
  const fields = ['benutzername', 'password'];
  
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

document.getElementById("loginForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  
  // Alle vorherigen Fehler löschen
  clearAllFieldErrors();

  const benutzername = document.getElementById("benutzername").value.trim();
  const password = document.getElementById("password").value.trim();
  
  // Client-seitige Validierung
  let isValid = true;
  
  if (!validateField('benutzername', benutzername)) {
    isValid = false;
  }
  
  if (!validateField('password', password)) {
    isValid = false;
  }
  
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
    showFormMessage('Anmeldung wird verarbeitet...', 'info');
    
    const response = await fetch("api/login.php", {
      method: "POST",
      // credentials: 'include', // uncomment if front-end & back-end are on different domains
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({ benutzername, password }),
    });
    const result = await response.json();

    if (result.status === "success") {
      showFormMessage('Login erfolgreich! Sie werden weitergeleitet...', 'success');
      setTimeout(() => {
        window.location.href = "protected.html";
      }, 1000);
    } else {
      showFormMessage(result.message || "Login fehlgeschlagen. Bitte überprüfen Sie Ihre Eingaben.", 'error');
      // Fokus zurück auf Benutzername-Feld
      document.getElementById('benutzername').focus();
    }
  } catch (error) {
    console.error("Fehler:", error);
    showFormMessage('Ein Netzwerkfehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'error');
  }
});
