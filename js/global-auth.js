// global-auth.js - Globale Authentifizierung für alle Seiten

// Globale Variable für den aktuellen Login-Status
let currentUser = null;
let isLoggedIn = false;

// Funktion zum Überprüfen des Login-Status
async function checkGlobalAuthStatus() {
  try {
    const response = await fetch("/api/protected.php", {
      credentials: "include",
    });

    if (response.status === 401) {
      // Benutzer ist nicht eingeloggt
      isLoggedIn = false;
      currentUser = null;
      updateGlobalNavigation();
      return false;
    }

    const result = await response.json();
    
    // Benutzer ist eingeloggt
    isLoggedIn = true;
    currentUser = result;
    
    // Prüfe, ob wir auf Login/Register-Seiten sind und leite weiter
    const currentPath = window.location.pathname;
    const isLoginPage = currentPath.includes('login.html');
    const isRegisterPage = currentPath.includes('register.html');
    
    if (isLoginPage || isRegisterPage) {
      // Eingeloggte Benutzer zur protected Seite weiterleiten
      window.location.href = "/protected.html";
      return true;
    }
    
    updateGlobalNavigation();
    return true;
  } catch (error) {
    console.error("Global auth check failed:", error);
    isLoggedIn = false;
    currentUser = null;
    updateGlobalNavigation();
    return false;
  }
}

// Funktion zum Aktualisieren der globalen Navigation
function updateGlobalNavigation() {
  const navElement = document.querySelector('nav ul');
  const mobileMenuToggle = document.getElementById('mobileMenuToggle');
  const headerElement = document.querySelector('header');
  
  if (!navElement) return;

  // Prüfe, ob wir auf der Login- oder Register-Seite sind
  const currentPath = window.location.pathname;
  const isLoginPage = currentPath.includes('login.html');
  const isRegisterPage = currentPath.includes('register.html');
  
  // Auf Login/Register-Seiten keine Navigation anzeigen
  if (isLoginPage || isRegisterPage) {
    navElement.innerHTML = '';
    // Hamburger-Menu wird durch CSS gesteuert, kein manuelles Verstecken nötig
    if (headerElement) {
      headerElement.classList.remove('user-logged-in');
    }
    return;
  }

  if (isLoggedIn && currentUser) {
    // Header als "eingeloggt" markieren für mobile CSS
    if (headerElement) {
      headerElement.classList.add('user-logged-in');
    }
    
    // Hamburger-Menu wird durch CSS gesteuert (versteckt für eingeloggte User auf mobil)
    
    navElement.innerHTML = `
      <li class="header-user-dropdown">
        <div class="user-info-trigger" id="userInfoTrigger">
          <div class="user-avatar">
            <i class="fas fa-user"></i>
          </div>
          <div class="user-welcome">
            <span class="welcome-text">Willkommen,</span>
            <span class="user-name" id="headerUserName">${currentUser.vorname}</span>
          </div>
          <i class="fas fa-chevron-down dropdown-arrow"></i>
        </div>
        
        <div class="user-dropdown-menu" id="userDropdownMenu">
          <div class="dropdown-header">
            <div class="dropdown-avatar">
              <i class="fas fa-user"></i>
            </div>
            <div class="dropdown-user-info">
              <div class="dropdown-name" id="dropdownFullName">${currentUser.vorname} ${currentUser.nachname}</div>
              <div class="dropdown-email" id="dropdownEmail">${currentUser.mail}</div>
            </div>
          </div>
          <div class="dropdown-divider"></div>
          <div class="dropdown-user-details">
            <div class="user-detail-item">
              <div class="detail-label">
                <i class="fas fa-user"></i>
                <span>Benutzername</span>
              </div>
              <div class="detail-value" id="dropdownUsername">${currentUser.benutzername}</div>
            </div>
            <div class="user-detail-item">
              <div class="detail-label">
                <i class="fas fa-key"></i>
                <span>Schlüsselbox</span>
              </div>
              <div class="detail-value" id="dropdownSeriennummer">${currentUser.seriennummer}</div>
            </div>
            <div class="user-detail-item">
              <div class="detail-label">
                <i class="fas fa-shield-alt"></i>
                <span>Benutzerrolle</span>
              </div>
              <div class="detail-value role-${currentUser.is_admin ? 'admin' : 'user'}" id="dropdownRole">${currentUser.is_admin ? 'Administrator' : 'Benutzer'}</div>
            </div>
          </div>
          <div class="dropdown-divider"></div>
          <div class="dropdown-links">
            <a href="protected.html" class="dropdown-link">
              <i class="fas fa-tachometer-alt"></i>
              <span>Dashboard</span>
            </a>
            <a href="#" class="dropdown-link" id="showStatusLink">
              <i class="fas fa-eye"></i>
              <span>Status anzeigen</span>
            </a>
            <a href="#" class="dropdown-link" id="showHistoryLink">
              <i class="fas fa-history"></i>
              <span>Schlüsselhistorie</span>
            </a>
            <a href="#" class="dropdown-link" id="showRfidLink">
              <i class="fas fa-key"></i>
              <span>RFID verwalten</span>
            </a>
            <button class="dropdown-link logout-link" id="globalLogoutBtn">
              <i class="fas fa-sign-out-alt"></i>
              <span>Abmelden</span>
            </button>
          </div>
        </div>
      </li>
    `;

    // User-Initialen setzen
    const userInitials = getUserInitials(currentUser.vorname, currentUser.nachname);
    const userAvatars = document.querySelectorAll('.user-avatar i, .dropdown-avatar i');
    userAvatars.forEach(avatar => {
      avatar.style.display = 'none';
      avatar.parentElement.textContent = userInitials;
    });

    // Dropdown-Funktionalität initialisieren
    initializeGlobalUserDropdown();
    initializeGlobalDropdownLinks();
    
    // Logout-Funktionalität hinzufügen
    const globalLogoutBtn = document.getElementById('globalLogoutBtn');
    if (globalLogoutBtn) {
      globalLogoutBtn.addEventListener('click', handleGlobalLogout);
    }

  } else {
    // Header als "nicht eingeloggt" markieren für mobile CSS
    if (headerElement) {
      headerElement.classList.remove('user-logged-in');
    }
    
    // Hamburger-Menu wird durch CSS gesteuert (sichtbar nur auf mobil für nicht eingeloggte User)
    
    navElement.innerHTML = `
      <li><a href="login.html">Login</a></li>
      <li><a href="register.html">Registrieren</a></li>
    `;
  }
}

// Globale Logout-Funktion
async function handleGlobalLogout(e) {
  e.preventDefault();

  try {
    const response = await fetch("api/logout.php", {
      method: "GET",
      credentials: "include",
    });

    const result = await response.json();

    if (result.status === "success") {
      // Status zurücksetzen
      isLoggedIn = false;
      currentUser = null;
      
      // Navigation aktualisieren
      updateGlobalNavigation();
      
      // Zur Login-Seite weiterleiten
      window.location.href = "login.html";
    } else {
      console.error("Logout failed");
      alert("Logout fehlgeschlagen. Bitte versuchen Sie es erneut.");
    }
  } catch (error) {
    console.error("Logout error:", error);
    alert("Ein Fehler ist beim Logout aufgetreten!");
  }
}

// Globale Dropdown-Funktionalität
function initializeGlobalUserDropdown() {
  const userInfoTrigger = document.getElementById('userInfoTrigger');
  const userDropdownMenu = document.getElementById('userDropdownMenu');
  
  if (!userInfoTrigger || !userDropdownMenu) {
    return;
  }

  // Toggle dropdown on click
  userInfoTrigger.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const isOpen = userDropdownMenu.classList.contains('show');
    
    if (isOpen) {
      closeGlobalUserDropdown();
    } else {
      openGlobalUserDropdown();
    }
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    if (!userInfoTrigger.contains(e.target) && !userDropdownMenu.contains(e.target)) {
      closeGlobalUserDropdown();
    }
  });

  // Close dropdown on escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeGlobalUserDropdown();
    }
  });

  // Prevent dropdown from closing when clicking inside it
  userDropdownMenu.addEventListener('click', function(e) {
    e.stopPropagation();
  });
}

function openGlobalUserDropdown() {
  const userInfoTrigger = document.getElementById('userInfoTrigger');
  const userDropdownMenu = document.getElementById('userDropdownMenu');
  
  if (userInfoTrigger && userDropdownMenu) {
    userInfoTrigger.classList.add('active');
    userDropdownMenu.classList.add('show');
  }
}

function closeGlobalUserDropdown() {
  const userInfoTrigger = document.getElementById('userInfoTrigger');
  const userDropdownMenu = document.getElementById('userDropdownMenu');
  
  if (userInfoTrigger && userDropdownMenu) {
    userInfoTrigger.classList.remove('active');
    userDropdownMenu.classList.remove('show');
  }
}

// Helper function to get user initials
function getUserInitials(firstName, lastName) {
  const firstInitial = firstName ? firstName.charAt(0).toUpperCase() : '';
  const lastInitial = lastName ? lastName.charAt(0).toUpperCase() : '';
  return firstInitial + lastInitial;
}

// Globale Dropdown-Link-Funktionalität
function initializeGlobalDropdownLinks() {
  const showStatusLink = document.getElementById('showStatusLink');
  const showHistoryLink = document.getElementById('showHistoryLink');
  const showRfidLink = document.getElementById('showRfidLink');
  
  if (showStatusLink) {
    showStatusLink.addEventListener('click', function(e) {
      e.preventDefault();
      // Wenn wir bereits auf der protected Seite sind, scroll zu Status
      if (window.location.pathname.includes('protected.html')) {
        const keyStatusContainer = document.querySelector('.key-status-container');
        if (keyStatusContainer) {
          keyStatusContainer.scrollIntoView({ behavior: 'smooth' });
        }
      } else {
        // Sonst zur protected Seite wechseln
        window.location.href = 'protected.html';
      }
      closeGlobalUserDropdown();
    });
  }
  
  if (showHistoryLink) {
    showHistoryLink.addEventListener('click', function(e) {
      e.preventDefault();
      // Wenn wir bereits auf der protected Seite sind, scroll zu Historie
      if (window.location.pathname.includes('protected.html')) {
        const historyContainer = document.querySelector('.key-history-container');
        if (historyContainer) {
          // Historie aufklappen falls sie zugeklappt ist
          const historyContent = document.getElementById('keyHistory');
          if (historyContent && historyContent.classList.contains('collapsed')) {
            const historyToggle = document.getElementById('historyToggle');
            if (historyToggle) {
              historyToggle.click();
            }
          }
          historyContainer.scrollIntoView({ behavior: 'smooth' });
        }
      } else {
        // Sonst zur protected Seite wechseln
        window.location.href = 'protected.html#history';
      }
      closeGlobalUserDropdown();
    });
  }
  
  if (showRfidLink) {
    showRfidLink.addEventListener('click', function(e) {
      e.preventDefault();
      // Wenn wir bereits auf der protected Seite sind, scroll zu RFID
      if (window.location.pathname.includes('protected.html')) {
        const rfidContainer = document.querySelector('.rfid-management-container');
        if (rfidContainer) {
          // RFID-Bereich aufklappen falls er zugeklappt ist
          const rfidContent = document.getElementById('rfidContent');
          if (rfidContent && rfidContent.classList.contains('collapsed')) {
            const rfidToggle = document.getElementById('rfidToggle');
            if (rfidToggle) {
              rfidToggle.click();
            }
          }
          rfidContainer.scrollIntoView({ behavior: 'smooth' });
        }
      } else {
        // Sonst zur protected Seite wechseln
        window.location.href = 'protected.html#rfid';
      }
      closeGlobalUserDropdown();
    });
  }
}

// Initialisierung beim Laden der Seite
window.addEventListener("DOMContentLoaded", () => {
  checkGlobalAuthStatus();
});

// Export für andere Module
window.globalAuth = {
  checkGlobalAuthStatus,
  updateGlobalNavigation,
  isLoggedIn: () => isLoggedIn,
  getCurrentUser: () => currentUser
};
