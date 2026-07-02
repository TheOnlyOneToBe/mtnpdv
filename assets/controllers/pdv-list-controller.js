import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['list', 'emptyState'];

  handleDelete(event) {
    event.preventDefault();
    const form = event.currentTarget;
    const pdvName = form.dataset.pdvName;

    if (!confirm(`Êtes-vous sûr de vouloir supprimer "${pdvName}"?`)) {
      return;
    }

    const formData = new FormData(form);

    fetch(form.action, {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'text/vnd.turbo-stream.html,text/html,application/xhtml+xml',
      },
    })
      .then((response) => {
        if (response.ok) {
          return response.text();
        }
        throw new Error('Erreur lors de la suppression');
      })
      .then((html) => {
        Turbo.connectStreamSource(new (class {
          constructor(html) {
            this.html = html;
          }
          send(data) {}
          close() {}
          addEventListener(type, listener) {
            if (type === 'message') {
              setTimeout(() => listener({ data: this.html }), 0);
            }
          }
        })(html));

        this.showAlert('success', `"${pdvName}" a été supprimé avec succès`);
      })
      .catch((error) => {
        console.error('Erreur:', error);
        this.showAlert('danger', 'Erreur lors de la suppression');
      });
  }

  showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    const container = document.querySelector('main');
    container.insertBefore(alertDiv, container.firstChild);

    setTimeout(() => {
      alertDiv.remove();
    }, 5000);
  }
}
