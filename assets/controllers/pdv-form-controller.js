import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['form', 'modal', 'spinner'];
  static values = {
    mode: String,
    submitUrl: String,
  };

  openCreateForm(event) {
    event.preventDefault();
    this.modeValue = 'create';
    this.modalTarget.classList.add('show');
    this.modalTarget.style.display = 'block';
    document.body.classList.add('modal-open');

    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.id = 'modal-backdrop';
    document.body.appendChild(backdrop);

    this.resetForm();
  }

  openEditForm(event) {
    event.preventDefault();
    this.modeValue = 'edit';
    this.modalTarget.classList.add('show');
    this.modalTarget.style.display = 'block';
    document.body.classList.add('modal-open');

    const backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.id = 'modal-backdrop';
    document.body.appendChild(backdrop);
  }

  closeModal(event) {
    event.preventDefault();
    this.modalTarget.classList.remove('show');
    this.modalTarget.style.display = 'none';
    document.body.classList.remove('modal-open');

    const backdrop = document.getElementById('modal-backdrop');
    if (backdrop) {
      backdrop.remove();
    }

    this.resetForm();
  }

  resetForm() {
    this.formTarget.reset();
    this.formTarget.classList.remove('was-validated');
  }

  handleSubmit(event) {
    event.preventDefault();

    if (!this.formTarget.checkValidity()) {
      this.formTarget.classList.add('was-validated');
      return;
    }

    this.spinnerTarget.classList.remove('d-none');

    const formData = new FormData(this.formTarget);

    fetch(this.submitUrlValue, {
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
        throw new Error('Erreur lors de l\'enregistrement');
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

        this.closeModal({ preventDefault: () => {} });
        this.showAlert('success', 'Point de vente enregistré avec succès');
      })
      .catch((error) => {
        console.error('Erreur:', error);
        this.showAlert('danger', 'Erreur lors de l\'enregistrement');
      })
      .finally(() => {
        this.spinnerTarget.classList.add('d-none');
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
