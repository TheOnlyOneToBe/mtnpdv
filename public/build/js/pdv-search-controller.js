import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['searchInput', 'departmentSelect', 'gerantSelect', 'results', 'spinner', 'noResults'];
  static values = {
    searchUrl: String,
    debounceDelay: { type: Number, default: 300 },
  };

  connect() {
    this.debounceTimer = null;
  }

  onSearchInput(event) {
    clearTimeout(this.debounceTimer);
    this.debounceTimer = setTimeout(() => {
      this.performSearch();
    }, this.debounceDelayValue);
  }

  onFilterChange(event) {
    this.performSearch();
  }

  performSearch() {
    const searchTerm = this.searchInputTarget.value.trim();
    const department = this.departmentSelectTarget.value;
    const gerant = this.gerantSelectTarget.value;

    if (!searchTerm && !department && !gerant) {
      this.clearResults();
      document.getElementById('search-section').style.display = 'none';
      this.resetMapMarkers();
      return;
    }

    document.getElementById('search-section').style.display = 'block';
    this.spinnerTarget.classList.remove('d-none');

    const params = new URLSearchParams();
    if (searchTerm) params.append('q', searchTerm);
    if (department) params.append('department', department);
    if (gerant) params.append('gerant', gerant);

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

        // Update map markers based on search results
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
