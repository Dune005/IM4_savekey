// Mobile Navigation Handler
// Dieses Script wird nur geladen wenn der Nutzer nicht eingeloggt ist

document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navigation = document.getElementById('navigation');
    
    // Funktion um zu prüfen ob wir in mobiler Ansicht sind
    function isMobileView() {
        return window.innerWidth <= 767;
    }
    
    // Hamburger-Menü nur bei mobiler Ansicht anzeigen
    function updateMenuVisibility() {
        if (mobileMenuToggle) {
            const header = document.querySelector('header'); // Header-Element holen
            // Prüfen, ob der Benutzer eingeloggt ist (anhand der Klasse am Header)
            if (header && header.classList.contains('user-logged-in')) {
                mobileMenuToggle.style.display = 'none';
                // Sicherstellen, dass das Menü geschlossen ist, wenn der Benutzer eingeloggt ist
                if (navigation) {
                    navigation.classList.remove('active');
                    const icon = mobileMenuToggle.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                    mobileMenuToggle.setAttribute('aria-label', 'Menü öffnen');
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                }
                return; // Funktion beenden, da der eingeloggte Status Vorrang hat
            }

            if (isMobileView()) {
                mobileMenuToggle.style.display = 'block';
            } else {
                mobileMenuToggle.style.display = 'none';
                // Menü schließen wenn zu Desktop gewechselt wird
                if (navigation) {
                    navigation.classList.remove('active');
                    const icon = mobileMenuToggle.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                    mobileMenuToggle.setAttribute('aria-label', 'Menü öffnen');
                    mobileMenuToggle.setAttribute('aria-expanded', 'false');
                }
            }
        }
    }
    
    // Initial die Sichtbarkeit setzen
    updateMenuVisibility();
    
    if (mobileMenuToggle && navigation) {
        mobileMenuToggle.addEventListener('click', function() {
            // Nur funktionieren wenn wir in mobiler Ansicht sind
            if (!isMobileView()) return;
            
            navigation.classList.toggle('active');
            
            // Icon zwischen Hamburger und X wechseln
            const icon = this.querySelector('i');
            if (navigation.classList.contains('active')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
                this.setAttribute('aria-label', 'Menü schließen');
                this.setAttribute('aria-expanded', 'true');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
                this.setAttribute('aria-label', 'Menü öffnen');
                this.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Menü schließen beim Klick außerhalb (nur mobil)
        document.addEventListener('click', function(event) {
            if (!isMobileView()) return;
            
            if (!mobileMenuToggle.contains(event.target) && !navigation.contains(event.target)) {
                navigation.classList.remove('active');
                const icon = mobileMenuToggle.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
                mobileMenuToggle.setAttribute('aria-label', 'Menü öffnen');
                mobileMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Menü-Sichtbarkeit bei Fenstergröße-Änderung aktualisieren
        window.addEventListener('resize', function() {
            updateMenuVisibility();
        });
    }
});
