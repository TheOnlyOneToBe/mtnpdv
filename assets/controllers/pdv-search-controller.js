import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['searchInput', 'departmentSelect', 'gerantSelect', 'statutSelect', 'categorieSelect', 'results', 'spinner', 'noResults'];
  static values = {
    searchUrl: String,
    debounceDelay: { type: Number, default: 300 },
    page: { type: Number, default: 1 },
  };

  connect() {
    this.debounceTimer = null;
  }

  onSearchInput(event) {
    this.pageValue = 1;
    clearTimeout(this.debounceTimer);
    this.debounceTimer = setTimeout(() => {
      this.performSearch();
    }, this.debounceDelayValue);
  }

  onFilterChange(event) {
    this.pageValue = 1;
    this.performSearch();
  }

  performSearch(event) {
    if (event && event.currentTarget && event.currentTarget.dataset.pdvSearchPageValue) {
      this.pageValue = parseInt(event.currentTarget.dataset.pdvSearchPageValue);
      if (event.currentTarget.dataset.pdvSearchSearchTermValue) {
        this.searchInputTarget.value = event.currentTarget.dataset.pdvSearchSearchTermValue;
      }
      if (event.currentTarget.dataset.pdvSearchDepartmentValue && this.hasDepartmentSelectTarget) {
        this.departmentSelectTarget.value = event.currentTarget.dataset.pdvSearchDepartmentValue;
      }
      if (event.currentTarget.dataset.pdvSearchGerantValue && this.hasGerantSelectTarget) {
        this.gerantSelectTarget.value = event.currentTarget.dataset.pdvSearchGerantValue;
      }
      if (event.currentTarget.dataset.pdvSearchStatutValue && this.hasStatutSelectTarget) {
        this.statutSelectTarget.value = event.currentTarget.dataset.pdvSearchStatutValue;
      }
      if (event.currentTarget.dataset.pdvSearchCategorieValue && this.hasCategorieSelectTarget) {
        this.categorieSelectTarget.value = event.currentTarget.dataset.pdvSearchCategorieValue;
      }
    }

    const searchTerm = this.searchInputTarget.value.trim();
    const department = this.hasDepartmentSelectTarget ? this.departmentSelectTarget.value : '';
    const gerant = this.hasGerantSelectTarget ? this.gerantSelectTarget.value : '';
    const statut = this.hasStatutSelectTarget ? this.statutSelectTarget.value : '';
    const categorie = this.hasCategorieSelectTarget ? this.categorieSelectTarget.value : '';

    if (!searchTerm && !department && !gerant && !statut && !categorie) {
      this.clearResults();
      document.getElementById('search-section').style.display = 'none';
      document.getElementById('pdv-list-container').style.display = 'block';
      this.resetMapMarkers();
      return;
    }

    document.getElementById('search-section').style.display = 'block';
    document.getElementById('pdv-list-container').style.display = 'none';
    this.spinnerTarget.classList.remove('d-none');

    const params = new URLSearchParams();
    if (searchTerm) params.append('q', searchTerm);
    if (department) params.append('department', department);
    if (gerant) params.append('gerant', gerant);
    if (statut) params.append('statut', statut);
    if (categorie) params.append('categorie', categorie);
    if (this.pageValue > 1) params.append('page', this.pageValue);

    fetch(`${this.searchUrlValue}?${params.toString()}`, {
      headers: {
        'Accept': 'text/vnd.turbo-stream.html,text/html',
        'X-Requested-With': 'XMLHttpRequest',
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
              setTimeout(() => {
                listener({ data: this.html });
              }, 0);
            }
          }
        })(html));

        setTimeout(() => {
          const searchResults = document.querySelectorAll('#search-results [id^="pdv-"]');
          this.updateMapForSearchResults(searchResults);
        }, 100);
      })
      .catch(error => {
        console.error('Erreur:', error);
        this.spinnerTarget.classList.add('d-none');
        this.showError('Erreur lors de la recherche');
      });
  }

  updateMapForSearchResults(searchResults) {
    const mapController = this.application.getControllerForElementAndIdentifier(
      this.element,
      'pdv-map'
    );
    if (mapController && mapController.filterMarkersBySearch) {
      mapController.filterMarkersBySearch(Array.from(searchResults));
    }
  }

  resetMapMarkers() {
    const mapController = this.application.getControllerForElementAndIdentifier(
      this.element,
      'pdv-map'
    );
    if (mapController && mapController.resetMarkers) {
      mapController.resetMarkers();
    }
  }

  clearResults() {
    this.resultsTarget.innerHTML = '';
    this.noResultsTarget.classList.add('d-none');
  }

  showError(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show';
    alertDiv.innerHTML = `
      <i class="fas fa-exclamation-triangle"></i>
      ${message}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

    this.resultsTarget.innerHTML = '';
    this.resultsTarget.appendChild(alertDiv);
  }
}
