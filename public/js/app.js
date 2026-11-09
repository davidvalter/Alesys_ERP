/* /public/js/app.js */

/* ==========================================
   APP GLOBAL - ALESYS ERP
========================================== */

document.addEventListener('DOMContentLoaded', () => {

    /* =========================
       TOGGLE SIDEBAR
    ========================= */
    const toggleBtn = document.querySelector('.toggle-sidebar');
    const wrapper = document.querySelector('.app-wrapper');

    if (toggleBtn && wrapper) {
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            wrapper.classList.add('sidebar-collapsed');
        }

        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            wrapper.classList.toggle('sidebar-collapsed');

            localStorage.setItem(
                'sidebar-collapsed',
                wrapper.classList.contains('sidebar-collapsed')
            );
        });
    }

    /* =========================
       DROPDOWNS GLOBAIS
    ========================= */
    const dropdownButtons = document.querySelectorAll('[data-bs-toggle="dropdown"]');

    dropdownButtons.forEach((btn) => {
        const dropdownInstance = new bootstrap.Dropdown(btn);

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            dropdownInstance.toggle();
        });
    });

    // Fecha dropdown ao clicar fora
    document.addEventListener('click', function () {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });

        document.querySelectorAll('[data-bs-toggle="dropdown"].show').forEach(btn => {
            btn.classList.remove('show');
            btn.setAttribute('aria-expanded', 'false');
        });
    });

});

/* ==========================================
   MODAL GLOBAL - PADRÃO BOOTSTRAP
========================================== */
function abrirModal(id, options = {}) {
    const modalEl = document.getElementById(id);
    if (!modalEl) return null;

    let instance = bootstrap.Modal.getInstance(modalEl);

    if (!instance) {
        instance = new bootstrap.Modal(modalEl, {
            backdrop: options.backdrop ?? true,
            keyboard: options.keyboard ?? true,
            focus: options.focus ?? true
        });
    }

    instance.show();
    return instance;
}

function fecharModal(id) {
    const modalEl = document.getElementById(id);
    if (!modalEl) return;

    const instance = bootstrap.Modal.getInstance(modalEl);
    if (instance) {
        instance.hide();
    }
}

function toggleModal(id) {
    const modalEl = document.getElementById(id);
    if (!modalEl) return;

    let instance = bootstrap.Modal.getInstance(modalEl);

    if (!instance) {
        instance = new bootstrap.Modal(modalEl);
    }

    instance.toggle();
}

/* ==========================================
   TOAST GLOBAL - NOTIFICAÇÕES
========================================== */

function showToast(msg, tipo = 'success') {

    // Cria o container de toasts na primeira chamada
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '1090';
        document.body.appendChild(container);
    }

    const cores = {
        success: 'text-bg-success',
        error: 'text-bg-danger',
        warning: 'text-bg-warning',
        info: 'text-bg-info'
    };

    const el = document.createElement('div');
    el.className = `toast align-items-center border-0 ${cores[tipo] || cores.success}`;
    el.setAttribute('role', 'alert');
    el.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${msg}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto"
                    data-bs-dismiss="toast"></button>
        </div>`;

    container.appendChild(el);

    const toast = new bootstrap.Toast(el, { delay: 4000 });
    toast.show();

    // Remove do DOM após fechar (evita acúmulo)
    el.addEventListener('hidden.bs.toast', () => el.remove());
}