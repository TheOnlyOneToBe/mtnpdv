import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container'];
    static values = {
        autoClose: { type: Boolean, default: true },
        autoCloseDelay: { type: Number, default: 5000 },
    };

    connect() {
        if (!document.getElementById('toast-container')) {
            this.createContainer();
        }
    }

    createContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    /**
     * Show a success toast
     * @param {string} message - The message to display
     * @param {string} title - Optional title
     */
    success(message, title = 'Succès') {
        this.show(message, title, 'success', 'check-circle');
    }

    /**
     * Show a danger/error toast
     * @param {string} message - The message to display
     * @param {string} title - Optional title
     */
    danger(message, title = 'Erreur') {
        this.show(message, title, 'danger', 'exclamation-circle');
    }

    /**
     * Show a warning toast
     * @param {string} message - The message to display
     * @param {string} title - Optional title
     */
    warning(message, title = 'Attention') {
        this.show(message, title, 'warning', 'exclamation-triangle');
    }

    /**
     * Show an info toast
     * @param {string} message - The message to display
     * @param {string} title - Optional title
     */
    info(message, title = 'Information') {
        this.show(message, title, 'info', 'info-circle');
    }

    /**
     * Show a confirmation toast
     * @param {string} message - The message to display
     * @param {Function} onConfirm - Callback on confirm
     * @param {Function} onCancel - Callback on cancel
     */
    confirmation(message, onConfirm, onCancel) {
        const container = document.getElementById('toast-container') || this.createContainer();
        const toastId = 'toast-' + Date.now();

        const html = `
            <div id="${toastId}" class="toast show" role="alert">
                <div class="toast-header bg-warning text-dark">
                    <i class="fas fa-question-circle me-2"></i>
                    <strong class="me-auto">Confirmation</strong>
                    <button type="button" class="btn-close btn-close-dark" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    <p class="mb-3">${this.escapeHtml(message)}</p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-success" onclick="window.__toastConfirm('${toastId}')">
                            <i class="fas fa-check"></i> Confirmer
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="window.__toastCancel('${toastId}')">
                            <i class="fas fa-times"></i> Annuler
                        </button>
                    </div>
                </div>
            </div>
        `;

        const toast = document.createElement('div');
        toast.innerHTML = html;
        container.appendChild(toast.firstElementChild);

        // Store callbacks globally
        window.__toastConfirm = (id) => {
            document.getElementById(id)?.remove();
            onConfirm?.();
        };
        window.__toastCancel = (id) => {
            document.getElementById(id)?.remove();
            onCancel?.();
        };
    }

    /**
     * Show a generic toast
     * @private
     */
    show(message, title, type, icon) {
        const container = document.getElementById('toast-container') || this.createContainer();
        const toastId = 'toast-' + Date.now();

        const bgClass = this.getBgClass(type);
        const textClass = type === 'warning' ? 'text-dark' : 'text-white';

        const html = `
            <div id="${toastId}" class="toast show" role="alert">
                <div class="toast-header ${bgClass} ${textClass}">
                    <i class="fas fa-${icon} me-2"></i>
                    <strong class="me-auto">${this.escapeHtml(title)}</strong>
                    <button type="button" class="btn-close ${type === 'warning' ? 'btn-close-dark' : ''}" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${this.escapeHtml(message)}
                </div>
            </div>
        `;

        const toast = document.createElement('div');
        toast.innerHTML = html;
        const toastElement = toast.firstElementChild;
        container.appendChild(toastElement);

        // Initialize Bootstrap toast
        const bsToast = new bootstrap.Toast(toastElement);
        bsToast.show();

        // Auto remove from DOM when hidden
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });

        // Auto close after delay
        if (this.autoCloseValue) {
            setTimeout(() => {
                if (toastElement.parentElement) {
                    const bsToast = bootstrap.Toast.getInstance(toastElement) || new bootstrap.Toast(toastElement);
                    bsToast.hide();
                }
            }, this.autoCloseDelayValue);
        }
    }

    /**
     * Get Bootstrap background class based on toast type
     * @private
     */
    getBgClass(type) {
        const classes = {
            success: 'bg-success',
            danger: 'bg-danger',
            warning: 'bg-warning',
            info: 'bg-info',
        };
        return classes[type] || 'bg-secondary';
    }

    /**
     * Escape HTML to prevent XSS
     * @private
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
