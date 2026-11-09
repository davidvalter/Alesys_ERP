// js/sidebar.js
document.addEventListener('DOMContentLoaded', function() {
    // Controle especial para o item "Compras" e outros submenus
    document.querySelectorAll('.has-submenu > .menu-link').forEach(menuLink => {
        menuLink.addEventListener('click', function(e) {
            if (!document.body.classList.contains('menu-expanded')) {
                e.preventDefault();
                document.dispatchEvent(new CustomEvent('toggleMenuRequest'));
            }
        });
    });

    // Escuta eventos de toggle vindo do topbar
    document.addEventListener('toggleMenuRequest', function() {
        const menuToggle = document.getElementById('menuToggle');
        if (menuToggle) {
            menuToggle.click();
        }
    });
});