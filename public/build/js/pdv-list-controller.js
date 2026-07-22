import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['list'];

  handleDelete(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const pdvName = form.dataset.pdvName;

    if (!confirm(`Êtes-vous sûr de vouloir supprimer "${pdvName}" ?`)) {
      return;
    }

    const formData = new FormData(form);

    fetch(form.action, {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'text/vnd.turbo-stream.html, text/html',
      },
    })
      .then(response => {
        if (!response.ok) {
          throw new Error('Erreur HTTP ' + response.status);
        }
        const contentType = response.headers.get('Content-Type') || '';
        this._lastDeleteContentType = contentType;
        return response.text();
      })
      .then(html => {
        const contentType = this._lastDeleteContentType || '';

        if (contentType.includes('turbo-stream')) {
          // Utiliser l'API Turbo correcte pour rendre un stream statique
          if (typeof Turbo !== 'undefined' && Turbo.renderStreamMessage) {
            Turbo.renderStreamMessage(html);
            this.showAlert('success', `"${pdvName}" a été supprimé avec succès`);
          } else {
            // Fallback : parser manuellement le turbo-stream
            this._applyTurboStream(html);
            this.showAlert('success', `"${pdvName}" a été supprimé avec succès`);
          }
        } else {
          // Pas de turbo-stream, recharger la page
          window.location.reload();
        }
      })
      .catch(error => {
        console.error('Erreur suppression PDV:', error);
        this.showAlert('danger', `Erreur lors de la suppression de "${pdvName}". Veuillez réessayer.`);
      });
  }

  /**
   * Parse et applique manuellement un turbo-stream <turbo-stream action="remove" target="...">
   */
  _applyTurboStream(html) {
    try {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const streams = doc.querySelectorAll('turbo-stream');

      streams.forEach(stream => {
        const action = stream.getAttribute('action');
        const target = stream.getAttribute('target');
        const template = stream.querySelector('template');

        const targetEl = target ? document.getElementById(target) : null;

        if (action === 'remove' && targetEl) {
          targetEl.remove();
        } else if ((action === 'update' || action === 'replace') && targetEl && template) {
          const content = template.content.cloneNode(true);
          targetEl.innerHTML = '';
          targetEl.appendChild(content);
        }
      });
    } catch (e) {
      console.error('Erreur application turbo-stream:', e);
      // Recharger la page en cas d'erreur
      window.location.reload();
    }
  }

  showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('#pdv-alerts');
    if (container) {
      container.insertBefore(alertDiv, container.firstChild);

      setTimeout(() => {
        alertDiv.remove();
      }, 5000);
    }
  }
}
