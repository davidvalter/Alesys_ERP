/**
 * Controlador genérico para modais
 */
class ModalController {
    constructor(modalId, options = {}) {
        this.modal = document.getElementById(modalId);
        this.options = {
            onOpen: null,
            onClose: null,
            onSubmit: null,
            validate: null,
            ...options
        };
        
        this.init();
    }
    
    init() {
        // Fechar modal ao clicar no X, botão cancelar ou fora do modal
        this.modal.querySelectorAll('.close, .close-modal').forEach(btn => {
            btn.addEventListener('click', () => this.close());
        });
        
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });
        
        // Configurar submit do formulário se existir
        const form = this.modal.querySelector('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                
                if (this.options.validate && !this.options.validate()) {
                    return;
                }
                
                const formData = new FormData(form);
                const data = Object.fromEntries(formData.entries());
                
                if (this.options.onSubmit) {
                    this.options.onSubmit(data, () => this.close());
                }
            });
        }
    }
    
    open(data = null) {
        if (this.options.onOpen && data) {
            this.options.onOpen(data);
        }
        this.modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    close() {
        this.modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        const form = this.modal.querySelector('form');
        if (form) form.reset();
        
        if (this.options.onClose) {
            this.options.onClose();
        }
    }
}

// Exemplo de uso para o modal de Mercados
const mercadoModal = new ModalController('modalMercado', {
    onOpen: (data) => {
        if (data) {
            Object.keys(data).forEach(key => {
                const field = document.querySelector(`#formMercado [name="${key}"]`);
                if (field) {
                    field.value = data[key];
                }
            });
        }
    },
    validate: () => {
        // Lógica de validação específica para mercados
        return true;
    },
    onSubmit: (data, closeModal) => {
        // Lógica para salvar mercado
        console.log('Dados do mercado:', data);
        closeModal();
    }
});

// Exemplo de como abrir o modal
document.getElementById('btnNovoMercado').addEventListener('click', () => {
    document.getElementById('modalTitle').textContent = 'Novo Mercado';
    mercadoModal.open();
});
