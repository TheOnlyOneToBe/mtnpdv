import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.convertFlashesToToasts();
        // Also listen for Turbo render events for new flashes
        document.addEventListener('turbo:render', () => {
            this.convertFlashesToToasts();
        });
    }

    /**
     * Convert Flash messages to Toasts
     * Maps Bootstrap alert types to toast types
     */
    convertFlashesToToasts() {
        const flashes = this.element.querySelectorAll('[data-flash-type]');

        flashes.forEach((flashElement) => {
            const type = flashElement.getAttribute('data-flash-type');
            const message = flashElement.textContent.trim();

            if (message) {
                this.showToast(type, message);
            }

            // Remove the original flash alert from DOM
            flashElement.remove();
        });
    }

    /**
     * Show toast based on flash type
     */
    showToast(type, message) {
        const toastContainer = document.querySelector('[data-controller~="toast"]');
        if (!toastContainer) {
            console.warn('Toast container not found');
            return;
        }

        // Map Bootstrap alert classes to toast types
        const typeMap = {
            'success': 'success',
            'danger': 'danger',
            'warning': 'warning',
            'info': 'info',
            'error': 'danger',  // Alias for danger
        };

        const toastType = typeMap[type] || 'info';
        const titleMap = {
            'success': 'Succès',
            'danger': 'Erreur',
            'warning': 'Attention',
            'info': 'Information',
            'error': 'Erreur',
        };

        const title = titleMap[type] || 'Notification';

        // Get toast controller
        const application = window.Stimulus?.Application?.current || window.Stimulus?.application;
        if (application) {
            const toastController = application.getControllerForElementAndIdentifier(
                toastContainer,
                'toast'
            );

            if (toastController) {
                toastController[toastType](message, title);
            } else {
                console.warn('Toast controller not found');
            }
        } else {
            console.warn('Stimulus application not found');
        }
    }
}
