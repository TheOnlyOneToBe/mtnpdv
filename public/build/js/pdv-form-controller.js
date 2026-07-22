import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['form', 'modal', 'spinner'];
  static values = {
    mode: String,
    submitUrl: String,
  };

  openCreateForm(event) {
    if (event) event.preventDefault();
    this.modeValue = 'create';
    this.loadForm(this.submitUrlValue);
  }

  openEditForm(event) {
    if (event) event.preventDefault();
    this.modeValue = 'edit';
    const url = event.currentTarget.dataset.pdvUrl;
    this.loadForm(url);
  }

  loadForm(url) {
    const modalBody = document.getElementById('pdv-form-modal-body');
    modalBody.innerHTML = '<p class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span> Chargement...</p>';

    const modal = new bootstrap.Modal(document.getElementById('pdvModal'));
    modal.show();

    fetch(url, {
      headers: {
        'Accept': 'text/vnd.turbo-stream.html, text/html',
      },
    })
      .then(response => {
        if (!response.ok) {
          throw new Error('Erreur HTTP ' + response.status);
        }
        const contentType = response.headers.get('Content-Type') || '';
        // Stocker le type de contenu pour le traitement
        this._lastContentType = contentType;
        return response.text();
      })
      .then(html => {
        const contentType = this._lastContentType || '';

        if (contentType.includes('turbo-stream')) {
          // Utilisation correcte de l'API Turbo pour rendre un turbo-stream
          if (typeof Turbo !== 'undefined' && Turbo.renderStreamMessage) {
            Turbo.renderStreamMessage(html);
          } else {
            // Fallback : extraire le contenu du template turbo-stream manuellement
            this._renderTurboStream(html, modalBody);
          }
        } else {
          // Réponse HTML classique : afficher directement dans le modal
          modalBody.innerHTML = html;
        }
      })
      .catch(error => {
        console.error('Erreur chargement formulaire PDV:', error);
        modalBody.innerHTML = '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Erreur lors du chargement du formulaire. Veuillez réessayer.</div>';
      });
  }

  /**
   * Fallback manuel : parse et applique un turbo-stream <turbo-stream action="update" target="...">
   */
  _renderTurboStream(html, fallbackContainer) {
    try {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const streams = doc.querySelectorAll('turbo-stream');

      if (streams.length === 0) {
        // Pas de turbo-stream trouvé, afficher le HTML brut
        fallbackContainer.innerHTML = html;
        return;
      }

      streams.forEach(stream => {
        const action = stream.getAttribute('action');
        const target = stream.getAttribute('target');
        const template = stream.querySelector('template');

        if (!template || !target) return;

        const targetEl = document.getElementById(target);
        if (!targetEl) return;

        const content = template.content.cloneNode(true);

        if (action === 'update' || action === 'replace') {
          targetEl.innerHTML = '';
          targetEl.appendChild(content);
        } else if (action === 'append') {
          targetEl.appendChild(content);
        } else if (action === 'prepend') {
          targetEl.insertBefore(content, targetEl.firstChild);
        } else if (action === 'remove') {
          targetEl.remove();
        }
      });
    } catch (e) {
      console.error('Erreur rendu turbo-stream:', e);
      fallbackContainer.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement. Veuillez réessayer.</div>';
    }
  }

  closeModal(event) {
    if (event) event.preventDefault();
    const modal = bootstrap.Modal.getInstance(document.getElementById('pdvModal'));
    if (modal) modal.hide();
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
