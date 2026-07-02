/**
 * Toast Helper - Utility functions for displaying toasts
 *
 * Usage examples:
 *
 * 1. Success toast:
 *    toastHelper.success('Opération réussie');
 *    toastHelper.success('Profil mis à jour', 'Succès');
 *
 * 2. Error/Danger toast:
 *    toastHelper.danger('Une erreur est survenue');
 *    toastHelper.danger('Email déjà utilisé', 'Erreur');
 *
 * 3. Warning toast:
 *    toastHelper.warning('Attention: données sensibles');
 *    toastHelper.warning('Cette action est irréversible', 'Attention');
 *
 * 4. Info toast:
 *    toastHelper.info('Chargement en cours...');
 *    toastHelper.info('Opération en cours', 'Info');
 *
 * 5. Confirmation toast:
 *    toastHelper.confirmation(
 *      'Êtes-vous sûr de vouloir supprimer?',
 *      () => { console.log('Confirmed'); },
 *      () => { console.log('Cancelled'); }
 *    );
 */

export const toastHelper = {
    /**
     * Get toast controller instance
     */
    getController() {
        const element = document.querySelector('[data-controller~="toast"]');
        return element ? window.Stimulus.Application.current.getControllerForElementAndIdentifier(element, 'toast') : null;
    },

    /**
     * Show success toast
     */
    success(message, title = 'Succès') {
        const controller = this.getController();
        if (controller) {
            controller.success(message, title);
        }
    },

    /**
     * Show error/danger toast
     */
    danger(message, title = 'Erreur') {
        const controller = this.getController();
        if (controller) {
            controller.danger(message, title);
        }
    },

    /**
     * Show warning toast
     */
    warning(message, title = 'Attention') {
        const controller = this.getController();
        if (controller) {
            controller.warning(message, title);
        }
    },

    /**
     * Show info toast
     */
    info(message, title = 'Information') {
        const controller = this.getController();
        if (controller) {
            controller.info(message, title);
        }
    },

    /**
     * Show confirmation toast
     */
    confirmation(message, onConfirm, onCancel) {
        const controller = this.getController();
        if (controller) {
            controller.confirmation(message, onConfirm, onCancel);
        }
    },

    /**
     * Show notification via Turbo Stream
     * POST to /toast/{type} with message and optional title
     */
    async showViaStream(type, message, title = '') {
        try {
            const formData = new FormData();
            formData.append('message', message);
            if (title) {
                formData.append('title', title);
            }

            const response = await fetch(`/toast/${type}`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                }
            });

            if (!response.ok) {
                console.error(`Failed to show ${type} toast:`, response.statusText);
            }
        } catch (error) {
            console.error('Error showing toast:', error);
        }
    },

    /**
     * Convenience methods for Turbo Stream
     */
    successStream(message, title = 'Succès') {
        return this.showViaStream('success', message, title);
    },

    dangerStream(message, title = 'Erreur') {
        return this.showViaStream('danger', message, title);
    },

    warningStream(message, title = 'Attention') {
        return this.showViaStream('warning', message, title);
    },

    infoStream(message, title = 'Information') {
        return this.showViaStream('info', message, title);
    }
};

// Export for use in other modules
export default toastHelper;
