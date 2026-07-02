import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur du mode sombre.
 * Applique la classe "dark-mode" sur <body>, persiste le choix
 * dans localStorage et met à jour l'icône du bouton de bascule.
 */
export default class extends Controller {
    static STORAGE_KEY = 'mtnpdv-dark-mode';

    connect() {
        this.apply(this.isEnabled());
    }

    toggle(event) {
        if (event) {
            event.preventDefault();
        }
        const enabled = !this.isEnabled();
        localStorage.setItem(this.constructor.STORAGE_KEY, enabled ? '1' : '0');
        this.apply(enabled);
    }

    isEnabled() {
        return localStorage.getItem(this.constructor.STORAGE_KEY) === '1';
    }

    apply(enabled) {
        document.body.classList.toggle('dark-mode', enabled);

        const icon = this.element.querySelector('.dark-mode-toggle i');
        if (icon) {
            icon.classList.toggle('fa-moon', !enabled);
            icon.classList.toggle('fa-sun', enabled);
        }
    }
}
