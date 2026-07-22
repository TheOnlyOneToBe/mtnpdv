
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['form', 'modal', 'spinner'];
  static values = {
    mode: String,
    submitUrl: String,
  };

  modalInstance = null;

  connect() {
    // Initialize the modal instance when the controller connects
    if (this.hasModalTarget) {
      this.modalInstance = new bootstrap.Modal(this.modalTarget);
    }
  }

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
    console.log('Loading form from URL:', url);
    const modalBody = document.getElementById('pdv-form-modal-body');
    modalBody.innerHTML = '<p class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span> Chargement...</p>';

    if (this.modalInstance) {
      this.modalInstance.show();
    } else if (this.hasModalTarget) {
      this.modalInstance = new bootstrap.Modal(this.modalTarget);
      this.modalInstance.show();
    }

    fetch(url, {
      headers: {
        'Accept': 'text/vnd.turbo-stream.html,text/html;q=0.9',
      },
    })
      .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', Object.fromEntries(response.headers.entries()));
        return response.text();
      })
      .then(html => {
        console.log('Received HTML:', html);
        Turbo.renderStreamMessage(html);
      })
      .catch(error => {
        console.error('Erreur:', error);
        modalBody.innerHTML = '<div class="alert alert-danger">Erreur lors du chargement du formulaire: ' + error.message + '</div>';
      });
  }

  closeModal(event) {
    if (event) event.preventDefault();
    if (this.modalInstance) {
      this.modalInstance.hide();
    }
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

