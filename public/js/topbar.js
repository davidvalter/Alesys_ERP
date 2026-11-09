// js/topbar.js
document.addEventListener('DOMContentLoaded', function() {
    const menuToggle = document.getElementById('menuToggle');
    const body = document.body;

    // Função compartilhada para alternar o menu
    function toggleMenu() {
        body.classList.toggle('menu-expanded');
        localStorage.setItem('menuExpanded', body.classList.contains('menu-expanded'));
        menuToggle.innerHTML = body.classList.contains('menu-expanded') 
            ? '<i class="fas fa-times"></i>' 
            : '<i class="fas fa-bars"></i>';
        
        // Dispara evento personalizado para notificar outras partes do sistema
        document.dispatchEvent(new CustomEvent('menuToggled', {
            detail: { expanded: body.classList.contains('menu-expanded') }
        }));
    }

    // Configura o toggle do menu
    if (menuToggle) {
        // Verifica estado inicial
        if (localStorage.getItem('menuExpanded') === 'true') {
            body.classList.add('menu-expanded');
            menuToggle.innerHTML = '<i class="fas fa-times"></i>';
        }

        // Adiciona o event listener
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            toggleMenu();
        });
    }

    // Observer para redimensionamento de tabelas (mantido do seu código original)
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                adjustTableLayout();
            }
        });
    });

    observer.observe(body, { attributes: true });

    function adjustTableLayout() {
        if (typeof table !== 'undefined' && typeof table.columns.adjust === 'function') {
            setTimeout(() => {
                table.columns.adjust().responsive.recalc();
            }, 100);
        }
    }
});