
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
        'Accept': 'text/vnd.turbo-stream.html,text/html',
      },
    })
      .then(response => response.text())
      .then(html => {
        Turbo.connectStreamSource(new (class {
          constructor(html) { this.html = html; }
          send(data) {}
          close() {}
          addEventListener(type, listener) {
            if (type === 'message') {
              setTimeout(() => listener({ data: this.html }), 0);
            }
          }
        })(html));
      })
      .catch(error => {
        console.error('Erreur:', error);
        modalBody.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement du formulaire</div>';
      });
  }

  closeModal(event) {
    if (event) event.preventDefault();
    const modal = bootstrap.Modal.getInstance(document.getElementById('pdvModal'));
    if (modal) modal.hide();
  }

  handleSubmitStart(event) {
    if (this.hasSpinnerTarget) {
      this.spinnerTarget.classList.remove('d-none');
    }
  }

  handleSubmitEnd(event) {
    if (this.hasSpinnerTarget) {
      this.spinnerTarget.classList.add('d-none');
    }

    if (event.detail.success) {
      this.closeModal();
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
    container.insertBefore(alertDiv, container.firstChild);

    setTimeout(() => {
      alertDiv.remove();
    }, 5000);
  }
}

