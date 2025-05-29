async function checkAuth() {
  try {
    const response = await fetch("/api/protected.php", {
      credentials: "include",
    });

    if (response.status === 401) {
      window.location.href = "/login.html";
      return false;
    }

    const result = await response.json();

    // Globale Navigation wird durch global-auth.js behandelt
    // Hier kümmern wir uns nur um den protected-spezifischen Content

    // Display protected content div ohne redundante user info
    const protectedContent = document.getElementById("protectedContent");

    // Speichere die Seriennummer für spätere Verwendung
    const seriennummer = result.seriennummer;

    // Prüfen, ob der Benutzer ein Administrator ist
    const isAdmin = result.is_admin === true;

    // Zeige das Dashboard mit prominenter Statusanzeige an
    protectedContent.innerHTML = `
      <div class="dashboard-welcome">
        <div class="welcome-message">
          <h2>Willkommen zurück, ${result.vorname}!</h2>
          <p class="welcome-subtitle">Hier ist der aktuelle Status Ihrer Schlüsselbox <strong>${seriennummer}</strong></p>
        </div>
      </div>

      <div class="key-status-container prominent">
        <div class="status-header">
          <div class="status-title">
            <h2>Schlüsselstatus</h2>
            <p class="status-subtitle">Aktueller Zustand Ihres Schlüssels</p>
          </div>
        </div>
        <div id="keyStatus" class="key-status">Lade Status...</div>
        ${isAdmin ? `
        <div class="key-actions">
          <button id="takeKeyBtn" class="action-btn take-btn">
            <span class="btn-text">Schlüssel<br>entnehmen</span>
          </button>
          <button id="returnKeyBtn" class="action-btn return-btn">
            <span class="btn-text">Schlüssel<br>zurückgeben</span>
          </button>
        </div>
        ` : ''}
      </div>

      <div class="key-history-container">
        <div class="history-header" id="historyToggle">
          <h3>Schlüsselhistorie</h3>
          <i class="fas fa-chevron-down history-arrow"></i>
        </div>
        <div id="keyHistory" class="key-history collapsed">Lade Historie...</div>
      </div>

      <div class="rfid-management-container">
        <div class="rfid-header" id="rfidToggle">
          <h3><i class="fas fa-credit-card"></i> Meine Verifizierungsmethode</h3>
          <i class="fas fa-chevron-down rfid-arrow"></i>
        </div>
        
        <div id="rfidContent" class="rfid-content collapsed">
          <div id="rfidStatus" class="rfid-status">Lade Status Ihrer Zutrittskarte...</div>
          
          <div id="lastScannedRfid" class="last-scanned-rfid" style="display: none;">
            <div class="scanned-card-info">
              <h4><i class="fas fa-check-circle"></i> Neue Karte erkannt!</h4>
              <p>Karten-ID: <code id="lastScannedRfidUid"></code></p>
              <button id="useScannedRfidBtn" class="action-btn use-card-btn">
                <i class="fas fa-plus-circle"></i> Diese Karte verwenden
              </button>
            </div>
          </div>
          
          <div class="rfid-form-section">
            <h4>Karte oder Badge zuweisen</h4>
            <div class="rfid-form">
              <input type="text" id="rfidUid" placeholder="Karten-ID eingeben (z.B. 04:A3:2B:1E)" class="rfid-input" />
              <div class="button-group">
                <button id="assignRfidBtn" class="action-btn rfid-btn">
                  <i class="fas fa-link"></i> Zuweisen
                </button>
                <button id="removeRfidBtn" class="action-btn rfid-remove-btn">
                  <i class="fas fa-unlink"></i> Entfernen
                </button>
              </div>
            </div>
          </div>

          <div class="rfid-instructions">
            <h4><i class="fas fa-info-circle"></i> So funktioniert es:</h4>
            <ol class="instruction-steps">
              <li>Halten Sie Ihre Karte an das Lesegerät der Schlüsselbox</li>
              <li>Die Karten-ID erscheint automatisch hier im Dashboard</li>
              <li>Klicken Sie auf "Diese Karte verwenden" um sie zu aktivieren</li>
            </ol>
          </div>
        </div>
      </div>

      <div class="push-notification-container compact">
        <div class="push-notification-controls">
          <button id="subscribeButton" disabled>
            <i class="fas fa-bell"></i> Push-Benachrichtigungen aktivieren
          </button>
          <p id="pushStatus">Initialisiere...</p>
        </div>
      </div>
    `;

    // Lade den Schlüsselstatus und die Historie
    loadKeyStatus();
    loadKeyHistory();

    // Initialisiere die Toggle-Funktionalität für die Schlüsselhistorie
    initializeHistoryToggle();
    
    // Initialisiere die Toggle-Funktionalität für RFID-Management (für alle Benutzer)
    initializeRfidToggle();

    // Starte die automatische Aktualisierung des Schlüsselstatus (alle 5 Sekunden)
    setStatusUpdateInterval(5000);

    // RFID/NFC-Verwaltung für alle Benutzer einrichten
    const assignRfidBtn = document.getElementById('assignRfidBtn');
    if (assignRfidBtn) {
      assignRfidBtn.addEventListener('click', () => assignRfid());
    }

    const removeRfidBtn = document.getElementById('removeRfidBtn');
    if (removeRfidBtn) {
      removeRfidBtn.addEventListener('click', () => removeRfid());
    }

    // RFID/NFC-Status laden
    loadRfidStatus();

    // Event-Listener für den "Diese UID verwenden"-Button hinzufügen
    const useScannedRfidBtn = document.getElementById('useScannedRfidBtn');
    if (useScannedRfidBtn) {
      useScannedRfidBtn.addEventListener('click', useScannedRfid);
    }

    // Starte die Abfrage nach neuen RFID-Scans
    startRfidScanPolling();

    // Nur für Administratoren die Aktionsbuttons einrichten
    if (result.is_admin === true) {
      // Benutzernamen als Datenelement zum Button hinzufügen
      const takeKeyBtn = document.getElementById('takeKeyBtn');
      if (takeKeyBtn) {
        takeKeyBtn.dataset.username = result.benutzername;
        // Event-Listener für die Schlüsselaktionen hinzufügen
        takeKeyBtn.addEventListener('click', () => takeKey());
      }

      const returnKeyBtn = document.getElementById('returnKeyBtn');
      if (returnKeyBtn) {
        returnKeyBtn.addEventListener('click', () => returnKey());
      }
    }

    return true;
  } catch (error) {
    console.error("Auth check failed:", error);
    window.location.href = "/login.html";
    return false;
  }
}

// Globale Variable für den Aktualisierungsintervall
let statusUpdateInterval = null;

// Funktion zum Laden des Schlüsselstatus
async function loadKeyStatus() {
  try {
    const keyStatusElement = document.getElementById("keyStatus");

    // Nur beim ersten Laden die "Lade Status..." Nachricht anzeigen
    if (!keyStatusElement.classList.contains('loaded')) {
      keyStatusElement.innerHTML = "Lade Status...";
    }

    // API-Anfrage senden, um den Status des Schlüssels zu laden
    const response = await fetch('api/key_status.php', {
      credentials: "include", // Wichtig, um die Session-Cookies zu senden
      cache: 'no-store' // Verhindert Caching der Antwort
    });

    if (!response.ok) {
      throw new Error(`HTTP-Fehler: ${response.status}`);
    }

    const data = await response.json();

    if (data.status === "success") {
      keyStatusElement.classList.add('loaded');
      const keyStatus = data.key_status;
      const isAvailable = keyStatus.is_available;
      const pendingRemoval = keyStatus.pending_removal;
      const unverifiedRemoval = keyStatus.unverified_removal;

      // Buttons aktivieren/deaktivieren basierend auf dem Status (nur für Admins)
      const takeKeyBtn = document.getElementById('takeKeyBtn');
      const returnKeyBtn = document.getElementById('returnKeyBtn');

      // Container-Element für die Farbänderung basierend auf dem Status
      const keyStatusContainer = document.querySelector('.key-status-container.prominent');

      // Wenn der Schlüssel verfügbar ist
      if (isAvailable) {
        // Container-Klassen für Status-Styling setzen
        if (keyStatusContainer) {
          keyStatusContainer.className = 'key-status-container prominent status-available';
        }

        // Nur Button-Eigenschaften ändern, wenn die Buttons existieren (für Admins)
        if (takeKeyBtn) takeKeyBtn.disabled = false;
        if (returnKeyBtn) returnKeyBtn.disabled = true;

        keyStatusElement.innerHTML = `
          <div class="key-available">
            <div class="key-icon available">
              <i class="fas fa-key"></i>
            </div>
            <div class="status-text">
              <h4>Schlüssel ist verfügbar</h4>
              <p>Der Schlüssel befindet sich in der Box und kann entnommen werden.</p>
            </div>
          </div>
        `;

        // Wenn der Schlüssel verfügbar ist, können wir das Aktualisierungsintervall auf einen längeren Zeitraum setzen
        setStatusUpdateInterval(5000); // Alle 5 Sekunden aktualisieren
      }
      // Wenn es eine ausstehende Entnahme gibt
      else if (pendingRemoval) {
        // Container-Klassen für Status-Styling setzen
        if (keyStatusContainer) {
          keyStatusContainer.className = 'key-status-container prominent status-pending';
        }

        // Nur Button-Eigenschaften ändern, wenn die Buttons existieren (für Admins)
        if (takeKeyBtn) takeKeyBtn.disabled = true;
        if (returnKeyBtn) returnKeyBtn.disabled = true;

        // Berechne die verbleibende Zeit bis zum Ablauf
        const expirationDate = new Date(keyStatus.pending_expiration);
        const now = new Date();
        const remainingTimeMs = expirationDate - now;
        const remainingMinutes = Math.floor(remainingTimeMs / 60000);
        const remainingSeconds = Math.floor((remainingTimeMs % 60000) / 1000);

        // Formatiere das Datum der Entnahme
        const pendingDate = new Date(keyStatus.pending_timestamp);
        const formattedDate = pendingDate.toLocaleDateString('de-DE') + ' ' + pendingDate.toLocaleTimeString('de-DE');

        keyStatusElement.innerHTML = `
          <div class="key-pending">
            <div class="key-icon pending">
              <i class="fas fa-clock"></i>
            </div>
            <div class="status-text">
              <h4>Schlüssel wurde entnommen - Verifizierung ausstehend</h4>
              <p>Der Schlüssel wurde am ${formattedDate} aus der Box entnommen.</p>
              <p>Verbleibende Zeit für die Verifizierung: <span class="countdown">${remainingMinutes}:${remainingSeconds.toString().padStart(2, '0')}</span></p>
              <p class="warning">Wenn sich niemand innerhalb von 5 Minuten mit einem RFID-Chip verifiziert, wird der Schlüssel als unrechtmäßig entnommen markiert!</p>
            </div>
          </div>
        `;

        // Bei ausstehender Entnahme häufiger aktualisieren
        setStatusUpdateInterval(5000); // Alle 5 Sekunden aktualisieren
      }
      // Wenn es eine abgelaufene, nicht verifizierte Entnahme gibt
      else if (unverifiedRemoval) {
        // Container-Klassen für Status-Styling setzen
        if (keyStatusContainer) {
          keyStatusContainer.className = 'key-status-container prominent status-stolen';
        }

        // Nur Button-Eigenschaften ändern, wenn die Buttons existieren (für Admins)
        if (takeKeyBtn) takeKeyBtn.disabled = true;
        if (returnKeyBtn) returnKeyBtn.disabled = true;

        // Formatiere das Datum der Entnahme
        const unverifiedDate = new Date(keyStatus.unverified_timestamp);
        const formattedDate = unverifiedDate.toLocaleDateString('de-DE') + ' ' + unverifiedDate.toLocaleTimeString('de-DE');

        keyStatusElement.innerHTML = `
          <div class="key-stolen">
            <div class="key-icon stolen">
              <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="status-text">
              <h4>WARNUNG: Schlüssel ohne Verifizierung entnommen!</h4>
              <p>Der Schlüssel wurde am ${formattedDate} aus der Box entnommen, aber niemand hat sich verifiziert.</p>
              <p class="warning">Bitte kontaktieren Sie umgehend den Schlüsselbesitzer oder den Administrator!</p>
              <p class="warning">Der Schlüssel wurde möglicherweise unrechtmäßig entnommen.</p>
            </div>
          </div>
        `;

        // Bei nicht verifizierter Entnahme häufiger aktualisieren
        setStatusUpdateInterval(10000); // Alle 10 Sekunden aktualisieren
      }
      // Wenn der Schlüssel von jemandem entnommen wurde
      else {
        // Container-Klassen für Status-Styling setzen
        if (keyStatusContainer) {
          keyStatusContainer.className = 'key-status-container prominent status-unavailable';
        }

        // Nur Button-Eigenschaften ändern, wenn die Buttons existieren (für Admins)
        if (takeKeyBtn) takeKeyBtn.disabled = true;

        // Formatiere das Datum
        const takeDate = new Date(keyStatus.take_time);
        const formattedDate = takeDate.toLocaleDateString('de-DE') + ' ' + takeDate.toLocaleTimeString('de-DE');

        const currentUser = keyStatus.current_user;
        // Prüfen, ob der takeKeyBtn existiert, bevor auf seine Eigenschaften zugegriffen wird
        const isCurrentUser = currentUser && takeKeyBtn && currentUser.benutzername === takeKeyBtn.dataset.username;

        // Nur Button-Eigenschaften ändern, wenn die Buttons existieren (für Admins)
        if (returnKeyBtn) returnKeyBtn.disabled = !isCurrentUser;

        // Prüfen, ob der Benutzer ein Admin ist (ob die Buttons existieren)
        const isAdmin = !!takeKeyBtn;

        keyStatusElement.innerHTML = `
          <div class="key-unavailable">
            <div class="key-icon unavailable">
              <i class="fas fa-key"></i>
            </div>
            <div class="status-text">
              <h4>Schlüssel ist nicht verfügbar</h4>
              <p>Der Schlüssel wurde am ${formattedDate} von ${currentUser.vorname} ${currentUser.nachname} entnommen.</p>
              ${isAdmin ? (isCurrentUser ? '<p class="user-action">Sie können den Schlüssel zurückgeben.</p>' : '<p class="user-action">Sie können den Schlüssel nicht zurückgeben, da Sie ihn nicht entnommen haben.</p>') : ''}
            </div>
          </div>
        `;

        // Wenn der Schlüssel entnommen wurde, können wir das Aktualisierungsintervall auf einen mittleren Zeitraum setzen
        setStatusUpdateInterval(15000); // Alle 15 Sekunden aktualisieren
      }
    } else {
      throw new Error(data.message || "Unbekannter Fehler beim Laden des Status");
    }
  } catch (error) {
    console.error("Fehler beim Laden des Schlüsselstatus:", error);
    document.getElementById("keyStatus").innerHTML = `
      <div class="error-message">
        Fehler beim Laden des Status: ${error.message}
        <button onclick="loadKeyStatus()">Erneut versuchen</button>
      </div>
    `;

    // Bei Fehlern trotzdem weiter versuchen zu aktualisieren
    setStatusUpdateInterval(30000); // Alle 30 Sekunden erneut versuchen
  }
}

// Funktion zum Setzen des Aktualisierungsintervalls
function setStatusUpdateInterval(interval) {
  // Bestehenden Intervall löschen, falls vorhanden
  if (statusUpdateInterval) {
    clearInterval(statusUpdateInterval);
  }

  // Neuen Intervall setzen
  statusUpdateInterval = setInterval(loadKeyStatus, interval);
}

// Funktion zum Laden der Schlüsselhistorie
async function loadKeyHistory() {
  try {
    const keyHistoryElement = document.getElementById("keyHistory");
    keyHistoryElement.innerHTML = "Lade Historie...";

    // API-Anfrage senden, um die Historie des Schlüssels zu laden
    const response = await fetch('api/key_history.php', {
      credentials: "include" // Wichtig, um die Session-Cookies zu senden
    });

    if (!response.ok) {
      throw new Error(`HTTP-Fehler: ${response.status}`);
    }

    const data = await response.json();

    if (data.status === "success") {
      const history = data.history;

      if (history.length === 0) {
        keyHistoryElement.innerHTML = "<p>Keine Einträge in der Historie gefunden.</p>";
        return;
      }

      // Zeige nur die letzten 5 Einträge
      const recentHistory = history.slice(0, 5);
      const totalEntries = history.length;

      // Erstelle eine Timeline für die Historie
      let content = "<div class='timeline'>";

      recentHistory.forEach((entry, index) => {
        const takeDate = new Date(entry.take_time);
        const formattedTakeDate = takeDate.toLocaleDateString('de-DE') + ' ' + takeDate.toLocaleTimeString('de-DE');

        let returnInfo = "";
        if (entry.return_time) {
          const returnDate = new Date(entry.return_time);
          const formattedReturnDate = returnDate.toLocaleDateString('de-DE') + ' ' + returnDate.toLocaleTimeString('de-DE');

          // Berechne die Dauer
          const duration = Math.floor((returnDate - takeDate) / (1000 * 60)); // Dauer in Minuten
          let durationText = "";

          if (duration < 60) {
            durationText = `${duration} Minuten`;
          } else {
            const hours = Math.floor(duration / 60);
            const minutes = duration % 60;
            durationText = `${hours} Stunde${hours !== 1 ? 'n' : ''} ${minutes > 0 ? `und ${minutes} Minute${minutes !== 1 ? 'n' : ''}` : ''}`;
          }

          returnInfo = `
            <div class="timeline-return">
              <i class="timeline-icon return"></i>
              <div class="timeline-content">
                <h4>Zurückgegeben am ${formattedReturnDate}</h4>
                <p>Dauer: ${durationText}</p>
              </div>
            </div>
          `;
        } else {
          returnInfo = `
            <div class="timeline-pending">
              <i class="timeline-icon pending"></i>
              <div class="timeline-content">
                <h4>Noch nicht zurückgegeben</h4>
              </div>
            </div>
          `;
        }

        content += `
          <div class="timeline-item ${index === 0 ? 'latest' : ''}">
            <div class="timeline-take">
              <i class="timeline-icon take"></i>
              <div class="timeline-content">
                <h4>Entnommen am ${formattedTakeDate}</h4>
                <p>Von: ${entry.full_name}</p>
              </div>
            </div>
            ${returnInfo}
          </div>
        `;
      });

      content += "</div>";

      // Hinweis hinzufügen, wenn mehr als 5 Einträge vorhanden sind
      if (totalEntries > 5) {
        content += `
          <div class="history-info">
            <div class="history-notice">
              <i class="fas fa-info-circle"></i>
              <div class="notice-content">
                <p><strong>Hinweis:</strong> Es werden nur die letzten 5 Aktivitäten angezeigt (${totalEntries} Einträge insgesamt).</p>
                <p>Bei Notfällen oder für eine vollständige Historie kontaktieren Sie bitte den Support.</p>
              </div>
            </div>
          </div>
        `;
      }

      keyHistoryElement.innerHTML = content;
    } else {
      throw new Error(data.message || "Unbekannter Fehler beim Laden der Historie");
    }
  } catch (error) {
    console.error("Fehler beim Laden der Schlüsselhistorie:", error);
    document.getElementById("keyHistory").innerHTML = `
      <div class="error-message">
        Fehler beim Laden der Historie: ${error.message}
        <button onclick="loadKeyHistory()">Erneut versuchen</button>
      </div>
    `;
  }
}

// Funktion zum Entnehmen des Schlüssels
async function takeKey() {
  try {
    // Bestätigungsdialog anzeigen
    if (!confirm("Möchten Sie den Schlüssel wirklich entnehmen?")) {
      return;
    }

    // Button deaktivieren, um mehrfache Klicks zu verhindern
    const takeKeyBtn = document.getElementById('takeKeyBtn');
    takeKeyBtn.disabled = true;
    takeKeyBtn.textContent = "Wird verarbeitet...";

    // API-Anfrage senden, um den Schlüssel zu entnehmen
    console.log('Sende Anfrage zum Entnehmen des Schlüssels...');

    const response = await fetch('api/key_action.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'take'
      }),
      credentials: "include"
    });

    // Prüfen, ob die Antwort ein gültiges JSON-Format hat
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      // Wenn die Antwort kein JSON ist, den Text der Antwort anzeigen
      const text = await response.text();
      console.error('Ungültige Antwort vom Server:', text);
      throw new Error('Ungültige Antwort vom Server: ' + text);
    }

    const data = await response.json();
    console.log('Antwort vom Server:', data);

    if (data.status === "success") {
      // Status und Historie neu laden
      loadKeyStatus();
      loadKeyHistory();
      alert("Schlüssel erfolgreich entnommen!");
    } else {
      alert(data.message || "Fehler beim Entnehmen des Schlüssels");
      takeKeyBtn.disabled = false;
      takeKeyBtn.textContent = "Schlüssel entnehmen";
    }
  } catch (error) {
    console.error("Fehler beim Entnehmen des Schlüssels:", error);
    alert("Fehler beim Entnehmen des Schlüssels: " + error.message);

    const takeKeyBtn = document.getElementById('takeKeyBtn');
    takeKeyBtn.disabled = false;
    takeKeyBtn.textContent = "Schlüssel entnehmen";
  }
}

// Funktion zum Zurückgeben des Schlüssels
async function returnKey() {
  try {
    // Bestätigungsdialog anzeigen
    if (!confirm("Möchten Sie den Schlüssel wirklich zurückgeben?")) {
      return;
    }

    // Button deaktivieren, um mehrfache Klicks zu verhindern
    const returnKeyBtn = document.getElementById('returnKeyBtn');
    returnKeyBtn.disabled = true;
    returnKeyBtn.textContent = "Wird verarbeitet...";

    // API-Anfrage senden, um den Schlüssel zurückzugeben
    console.log('Sende Anfrage zum Zurückgeben des Schlüssels...');

    const response = await fetch('api/key_action.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'return'
      }),
      credentials: "include"
    });

    // Prüfen, ob die Antwort ein gültiges JSON-Format hat
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      // Wenn die Antwort kein JSON ist, den Text der Antwort anzeigen
      const text = await response.text();
      console.error('Ungültige Antwort vom Server:', text);
      throw new Error('Ungültige Antwort vom Server: ' + text);
    }

    const data = await response.json();
    console.log('Antwort vom Server:', data);

    if (data.status === "success") {
      // Status und Historie neu laden
      loadKeyStatus();
      loadKeyHistory();
      alert("Schlüssel erfolgreich zurückgegeben!");
    } else {
      alert(data.message || "Fehler beim Zurückgeben des Schlüssels");
      returnKeyBtn.disabled = false;
      returnKeyBtn.textContent = "Schlüssel zurückgeben";
    }
  } catch (error) {
    console.error("Fehler beim Zurückgeben des Schlüssels:", error);
    alert("Fehler beim Zurückgeben des Schlüssels: " + error.message);

    const returnKeyBtn = document.getElementById('returnKeyBtn');
    returnKeyBtn.disabled = false;
    returnKeyBtn.textContent = "Schlüssel zurückgeben";
  }
}

// Funktion zum Laden des RFID/NFC-Status
async function loadRfidStatus() {
  try {
    const rfidStatusElement = document.getElementById("rfidStatus");
    rfidStatusElement.innerHTML = "Lade RFID/NFC-Status...";

    // API-Anfrage senden, um den RFID/NFC-Status zu laden
    const response = await fetch('api/rfid_management.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'get_rfid'
      }),
      credentials: "include"
    });

    const data = await response.json();

    if (data.status === "success") {
      const rfidUid = data.rfid_uid;
      const removeRfidBtn = document.getElementById('removeRfidBtn');

      if (rfidUid) {
        rfidStatusElement.innerHTML = `
          <div class="rfid-assigned">
            <i class="rfid-icon assigned"></i>
            <div class="status-text">
              <h4>RFID/NFC-Chip zugewiesen</h4>
              <p>Ihre aktuelle RFID/NFC-UID: <code>${rfidUid}</code></p>
            </div>
          </div>
        `;
        removeRfidBtn.disabled = false;
      } else {
        rfidStatusElement.innerHTML = `
          <div class="rfid-not-assigned">
            <i class="rfid-icon not-assigned"></i>
            <div class="status-text">
              <h4>Kein RFID/NFC-Chip zugewiesen</h4>
              <p>Sie haben noch keinen RFID/NFC-Chip mit Ihrem Konto verknüpft.</p>
            </div>
          </div>
        `;
        removeRfidBtn.disabled = true;
      }
    } else {
      throw new Error(data.message || "Unbekannter Fehler beim Laden des RFID/NFC-Status");
    }
  } catch (error) {
    console.error("Fehler beim Laden des RFID/NFC-Status:", error);
    document.getElementById("rfidStatus").innerHTML = `
      <div class="error-message">
        Fehler beim Laden des RFID/NFC-Status: ${error.message}
        <button onclick="loadRfidStatus()">Erneut versuchen</button>
      </div>
    `;
  }
}

// Funktion zum Zuweisen eines RFID/NFC-Chips
async function assignRfid() {
  try {
    const rfidUid = document.getElementById('rfidUid').value.trim();

    if (!rfidUid) {
      alert("Bitte geben Sie eine RFID/NFC-UID ein.");
      return;
    }

    // Bestätigungsdialog anzeigen
    if (!confirm(`Möchten Sie den RFID/NFC-Chip mit der UID "${rfidUid}" wirklich Ihrem Konto zuweisen?`)) {
      return;
    }

    // Button deaktivieren, um mehrfache Klicks zu verhindern
    const assignRfidBtn = document.getElementById('assignRfidBtn');
    assignRfidBtn.disabled = true;
    assignRfidBtn.textContent = "Wird verarbeitet...";

    // API-Anfrage senden, um den RFID/NFC-Chip zuzuweisen
    const response = await fetch('api/rfid_management.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'assign_rfid',
        rfid_uid: rfidUid
      }),
      credentials: "include"
    });

    const data = await response.json();

    if (data.status === "success") {
      // RFID/NFC-Status neu laden
      loadRfidStatus();
      alert("RFID/NFC-Chip erfolgreich zugewiesen!");
      document.getElementById('rfidUid').value = '';
    } else {
      alert(data.message || "Fehler beim Zuweisen des RFID/NFC-Chips");
    }

    // Button zurücksetzen
    assignRfidBtn.disabled = false;
    assignRfidBtn.textContent = "RFID/NFC zuweisen";
  } catch (error) {
    console.error("Fehler beim Zuweisen des RFID/NFC-Chips:", error);
    alert("Fehler beim Zuweisen des RFID/NFC-Chips: " + error.message);

    const assignRfidBtn = document.getElementById('assignRfidBtn');
    assignRfidBtn.disabled = false;
    assignRfidBtn.textContent = "RFID/NFC zuweisen";
  }
}

// Funktion zum Entfernen eines RFID/NFC-Chips
async function removeRfid() {
  try {
    // Bestätigungsdialog anzeigen
    if (!confirm("Möchten Sie den RFID/NFC-Chip wirklich von Ihrem Konto entfernen?")) {
      return;
    }

    // Button deaktivieren, um mehrfache Klicks zu verhindern
    const removeRfidBtn = document.getElementById('removeRfidBtn');
    removeRfidBtn.disabled = true;
    removeRfidBtn.textContent = "Wird verarbeitet...";

    // API-Anfrage senden, um den RFID/NFC-Chip zu entfernen
    const response = await fetch('api/rfid_management.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'remove_rfid'
      }),
      credentials: "include"
    });

    const data = await response.json();

    if (data.status === "success") {
      // RFID/NFC-Status neu laden
      loadRfidStatus();
      alert("RFID/NFC-Chip erfolgreich entfernt!");
    } else {
      alert(data.message || "Fehler beim Entfernen des RFID/NFC-Chips");
      removeRfidBtn.disabled = false;
      removeRfidBtn.textContent = "RFID/NFC entfernen";
    }
  } catch (error) {
    console.error("Fehler beim Entfernen des RFID/NFC-Chips:", error);
    alert("Fehler beim Entfernen des RFID/NFC-Chips: " + error.message);

    const removeRfidBtn = document.getElementById('removeRfidBtn');
    removeRfidBtn.disabled = false;
    removeRfidBtn.textContent = "RFID/NFC entfernen";
  }
}

// Hash-Link Handling für externe Navigation
function handleHashLinks() {
  const hash = window.location.hash;
  
  if (hash === '#history') {
    setTimeout(() => {
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
    }, 500);
  } else if (hash === '#rfid') {
    setTimeout(() => {
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
    }, 500);
  }
}

// Check auth when page loads
window.addEventListener("load", () => {
  // Warte auf die globale Authentifizierung, dann prüfe den lokalen Zustand
  setTimeout(() => {
    if (window.globalAuth && window.globalAuth.isLoggedIn()) {
      checkAuth().then(() => {
        // Nach dem Laden des Contents, Hash-Links verarbeiten
        handleHashLinks();
      });
    }
  }, 100);
});

// Globale Variable für das RFID-Scan-Polling-Intervall
let rfidScanPollingInterval = null;

// Funktion zum Starten des RFID-Scan-Pollings
function startRfidScanPolling() {
  // Sofort beim Start einmal ausführen
  checkForNewRfidScans();

  // Dann alle 2 Sekunden wiederholen
  rfidScanPollingInterval = setInterval(checkForNewRfidScans, 2000);
}

// Globale Variable für den Timer zum Ausblenden der RFID-Anzeige
let rfidDisplayTimer = null;

// Funktion zum Überprüfen, ob neue RFID-Scans vorliegen
async function checkForNewRfidScans() {
  try {
    // API-Anfrage senden, um die zuletzt gescannte RFID-UID abzurufen
    const response = await fetch('api/last_rfid_scan.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: new URLSearchParams({
        action: 'get'
      }),
      credentials: "include",
      cache: 'no-store' // Verhindert Caching der Antwort
    });

    if (!response.ok) {
      throw new Error(`HTTP-Fehler: ${response.status}`);
    }

    const data = await response.json();

    if (data.status === "success") {
      const lastScannedRfidElement = document.getElementById('lastScannedRfid');
      const lastScannedRfidUidElement = document.getElementById('lastScannedRfidUid');

      if (data.has_recent_scan) {
        // Prüfen, ob es sich um eine neue UID handelt oder die Anzeige bereits sichtbar ist
        const currentUid = lastScannedRfidUidElement.textContent;
        const newUid = data.rfid_uid;
        const isVisible = lastScannedRfidElement.style.display === 'block';

        // Nur aktualisieren, wenn es eine neue UID ist oder die Anzeige nicht sichtbar ist
        if (currentUid !== newUid || !isVisible) {
          console.log("Neue RFID-UID erkannt:", newUid);

          // Zeige die zuletzt gescannte RFID-UID an
          lastScannedRfidUidElement.textContent = newUid;
          lastScannedRfidElement.style.display = 'block';

          // Bestehenden Timer löschen, falls vorhanden
          if (rfidDisplayTimer) {
            clearTimeout(rfidDisplayTimer);
          }

          // Neuen Timer setzen - Automatisch nach 10 Sekunden ausblenden
          rfidDisplayTimer = setTimeout(() => {
            console.log("Timer abgelaufen, blende RFID-Anzeige aus");
            lastScannedRfidElement.style.display = 'none';
            rfidDisplayTimer = null;
          }, 10000);
        }
      }
    }
  } catch (error) {
    console.error("Fehler beim Abrufen der zuletzt gescannten RFID-UID:", error);
    // Fehler still behandeln, da dies im Hintergrund läuft und den Benutzer nicht stören soll
  }
}

// Funktion zum Verwenden der zuletzt gescannten RFID-UID
function useScannedRfid() {
  const lastScannedRfidUidElement = document.getElementById('lastScannedRfidUid');
  const rfidUidInput = document.getElementById('rfidUid');

  if (lastScannedRfidUidElement && rfidUidInput) {
    rfidUidInput.value = lastScannedRfidUidElement.textContent;

    // Ausblenden der Anzeige
    const lastScannedRfidElement = document.getElementById('lastScannedRfid');
    if (lastScannedRfidElement) {
      lastScannedRfidElement.style.display = 'none';

      // Timer löschen, da die Anzeige manuell ausgeblendet wurde
      if (rfidDisplayTimer) {
        clearTimeout(rfidDisplayTimer);
        rfidDisplayTimer = null;
      }
    }
  }
}

// Stoppe die automatische Aktualisierung, wenn die Seite verlassen wird
window.addEventListener("beforeunload", () => {
  // Intervalle löschen
  if (statusUpdateInterval) {
    clearInterval(statusUpdateInterval);
  }

  if (rfidScanPollingInterval) {
    clearInterval(rfidScanPollingInterval);
  }

  // Timer löschen
  if (rfidDisplayTimer) {
    clearTimeout(rfidDisplayTimer);
  }
});

// Helper function to get user initials (wird auch von global-auth.js verwendet)
function getUserInitials(firstName, lastName) {
  const firstInitial = firstName ? firstName.charAt(0).toUpperCase() : '';
  const lastInitial = lastName ? lastName.charAt(0).toUpperCase() : '';
  return firstInitial + lastInitial;
}

// Initialisiert die Toggle-Funktionalität für die Schlüsselhistorie
function initializeHistoryToggle() {
  const historyToggle = document.getElementById('historyToggle');
  const keyHistory = document.getElementById('keyHistory');
  
  if (historyToggle && keyHistory) {
    historyToggle.addEventListener('click', function() {
      const arrow = historyToggle.querySelector('.history-arrow');
      
      if (keyHistory.classList.contains('collapsed')) {
        // Historie ausklappen
        keyHistory.classList.remove('collapsed');
        keyHistory.classList.add('expanded');
        arrow.classList.remove('fa-chevron-down');
        arrow.classList.add('fa-chevron-up');
      } else {
        // Historie einklappen
        keyHistory.classList.remove('expanded');
        keyHistory.classList.add('collapsed');
        arrow.classList.remove('fa-chevron-up');
        arrow.classList.add('fa-chevron-down');
      }
    });
  }
}

// Initialisiert die Toggle-Funktionalität für RFID-Management
function initializeRfidToggle() {
  const rfidToggle = document.getElementById('rfidToggle');
  const rfidContent = document.getElementById('rfidContent');
  
  if (rfidToggle && rfidContent) {
    rfidToggle.addEventListener('click', function() {
      const arrow = rfidToggle.querySelector('.rfid-arrow');
      
      if (rfidContent.classList.contains('collapsed')) {
        // RFID-Bereich ausklappen
        rfidContent.classList.remove('collapsed');
        rfidContent.classList.add('expanded');
        arrow.classList.remove('fa-chevron-down');
        arrow.classList.add('fa-chevron-up');
      } else {
        // RFID-Bereich einklappen
        rfidContent.classList.remove('expanded');
        rfidContent.classList.add('collapsed');
        arrow.classList.remove('fa-chevron-up');
        arrow.classList.add('fa-chevron-down');
      }
    });
  }
}
