// Mobile Navigation Handler
// Kompaktes Dropdown-Menü für nicht eingeloggte Nutzer

document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuToggle = document.getElementById('mobileMenuToggle');
    const navigation = document.getElementById('navigation');
    const header = document.querySelector('header');
    
    // Funktion um zu prüfen ob wir in mobiler Ansicht sind
    function isMobileView() {
        return window.innerWidth <= 767;
    }
    
    // Menü schliessen
    function closeMenu() {
        if (navigation) {
            navigation.classList.remove('active');
        }
        if (mobileMenuToggle) {
            mobileMenuToggle.setAttribute('aria-expanded', 'false');
        }
    }
    
    // Menü öffnen
    function openMenu() {
        if (navigation) {
            navigation.classList.add('active');
        }
        if (mobileMenuToggle) {
            mobileMenuToggle.setAttribute('aria-expanded', 'true');
        }
    }
    
    // Menü toggle
    function toggleMenu() {
        if (navigation && navigation.classList.contains('active')) {
            closeMenu();
        } else {
            openMenu();
        }
    }
    
    // Hamburger-Menü nur bei mobiler Ansicht anzeigen
    function updateMenuVisibility() {
        if (mobileMenuToggle) {
            if (header && header.classList.contains('user-logged-in')) {
                mobileMenuToggle.style.display = 'none';
                closeMenu();
                return;
            }

            if (isMobileView()) {
                mobileMenuToggle.style.display = 'flex';
            } else {
                mobileMenuToggle.style.display = 'none';
                closeMenu();
            }
        }
    }
    
    // Initial
    updateMenuVisibility();
    
    if (mobileMenuToggle && navigation) {
        // Toggle-Button
        mobileMenuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            if (!isMobileView()) return;
            toggleMenu();
        });
        
        // Klick ausserhalb schliesst Menü
        document.addEventListener('click', function(event) {
            if (!isMobileView()) return;
            if (!navigation.classList.contains('active')) return;
            
            if (!navigation.contains(event.target) && !mobileMenuToggle.contains(event.target)) {
                closeMenu();
            }
        });
        
        // Menü-Links schliessen Menü
        navigation.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() {
                if (isMobileView()) {
                    closeMenu();
                }
            });
        });
        
        // Escape-Taste
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && navigation.classList.contains('active')) {
                closeMenu();
            }
        });
        
        // Resize-Handler
        window.addEventListener('resize', updateMenuVisibility);
    }
});
