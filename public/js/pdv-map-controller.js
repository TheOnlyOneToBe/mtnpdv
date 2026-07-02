import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['mapContainer', 'listView', 'mapView', 'viewToggle'];
  static values = {
    centerLat: { type: Number, default: 3.8480 },
    centerLng: { type: Number, default: 11.5021 },
    zoomLevel: { type: Number, default: 6 },
  };

  connect() {
    this.map = null;
    this.markers = {};
    this.initializeMap();
  }

  initializeMap() {
    if (!this.mapContainerTarget) return;

    this.map = L.map(this.mapContainerTarget).setView(
      [this.centerLatValue, this.centerLngValue],
      this.zoomLevelValue
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap',
      maxZoom: 19,
    }).addTo(this.map);

    // Charger les PDV initiaux
    this.loadMarkersFromList();
  }

  loadMarkersFromList() {
    const pdvCards = document.querySelectorAll('[id^="pdv-"][id*="-"]');
    pdvCards.forEach((card) => {
      const pdvId = card.id.replace('pdv-', '');
      const nomPdv = card.querySelector('.card-header h5')?.textContent.trim() || '';
      const latMatch = card.textContent.match(/Lat:\s*([-\d.]+)/);
      const lngMatch = card.textContent.match(/Lng:\s*([-\d.]+)/);

      if (latMatch && lngMatch) {
        const lat = parseFloat(latMatch[1]);
        const lng = parseFloat(lngMatch[1]);
        this.addMarker(pdvId, nomPdv, lat, lng, card);
      }
    });
  }

  addMarker(pdvId, nomPdv, lat, lng, cardElement) {
    // Supprimer l'ancien marqueur s'il existe
    if (this.markers[pdvId]) {
      this.map.removeLayer(this.markers[pdvId]);
    }

    const marker = L.marker([lat, lng], {
      icon: this.createCustomIcon(cardElement),
    });

    // Créer le popup avec les infos du PDV
    const popupContent = this.createPopupContent(cardElement);
    marker.bindPopup(popupContent);

    marker.addTo(this.map);
    this.markers[pdvId] = marker;
  }

  createCustomIcon(cardElement) {
    // Déterminer la couleur basée sur le statut
    const statusBadge = cardElement.querySelector('.badge');
    let color = '#16a34a'; // vert (ACTIF)

    if (statusBadge) {
      if (statusBadge.classList.contains('bg-danger')) {
        color = '#dc2626'; // rouge (FERME)
      } else if (statusBadge.classList.contains('bg-warning')) {
        color = '#ea580c'; // orange (SUSPENDU)
      }
    }

    // Créer l'icône SVG
    const svgString = `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="32" height="32">
        <circle cx="24" cy="24" r="22" fill="${color}" opacity="0.9"/>
        <path d="M24 8 L32 32 H16 Z" fill="${color}"/>
      </svg>
    `;

    const svgBase64 = btoa(svgString);
    const iconUrl = `data:image/svg+xml;base64,${svgBase64}`;

    return L.icon({
      iconUrl: iconUrl,
      iconSize: [32, 32],
      iconAnchor: [16, 32],
      popupAnchor: [0, -32],
    });
  }

  createPopupContent(cardElement) {
    const nom = cardElement.querySelector('.card-header h5')?.textContent.trim() || '';
    const code = cardElement.textContent.match(/Code:.*?(PDV\d+)/)?.[1] || '';
    const ville = cardElement.textContent.match(/Département\/Ville:.*?(\w+)/)?.[1] || '';
    const gerant = cardElement.textContent.match(/Gérant:.*?(\w+\s+\w+)/)?.[1] || 'N/A';
    const statut = cardElement.querySelector('.badge')?.textContent.trim() || '';

    return `
      <div style="min-width: 280px;">
        <div class="mb-2">
          <h6 style="margin: 0 0 0.5rem 0;">
            <i class="fas fa-store" style="color: #1e40af;"></i>
            ${nom}
          </h6>
        </div>
        <div style="font-size: 0.9rem;">
          <p style="margin: 0.3rem 0;"><strong>Code:</strong> ${code}</p>
          <p style="margin: 0.3rem 0;"><strong>Ville:</strong> ${ville}</p>
          <p style="margin: 0.3rem 0;"><strong>Gérant:</strong> ${gerant}</p>
          <p style="margin: 0.3rem 0;">
            <strong>Statut:</strong>
            <span style="padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; color: white; background-color: ${this.getStatusColor(statut)};">
              ${statut}
            </span>
          </p>
        </div>
        <div style="margin-top: 0.5rem; border-top: 1px solid #ddd; padding-top: 0.5rem;">
          <small style="color: #666;">Cliquez sur les onglets pour plus d'options</small>
        </div>
      </div>
    `;
  }

  getStatusColor(statut) {
    switch (statut) {
      case 'ACTIF':
        return '#16a34a';
      case 'FERME':
        return '#dc2626';
      case 'SUSPENDU':
        return '#ea580c';
      default:
        return '#6b7280';
    }
  }

  toggleView(event) {
    const viewType = event.currentTarget.dataset.view;

    if (viewType === 'list') {
      this.listViewTarget.classList.remove('d-none');
      this.mapViewTarget.classList.add('d-none');
      document.querySelectorAll('[data-view]').forEach((btn) => {
        btn.classList.remove('active');
      });
      event.currentTarget.classList.add('active');
    } else if (viewType === 'map') {
      this.listViewTarget.classList.add('d-none');
      this.mapViewTarget.classList.remove('d-none');
      document.querySelectorAll('[data-view]').forEach((btn) => {
        btn.classList.remove('active');
      });
      event.currentTarget.classList.add('active');

      // Redessiner la carte
      setTimeout(() => {
        this.map.invalidateSize();
        this.loadMarkersFromList();
      }, 100);
    }
  }

  clearMarkers() {
    Object.values(this.markers).forEach((marker) => {
      this.map.removeLayer(marker);
    });
    this.markers = {};
  }

  filterMarkersBySearch(searchResults) {
    const visibleIds = new Set(
      searchResults.map((pdv) => pdv.getAttribute('id').replace('pdv-', ''))
    );

    Object.keys(this.markers).forEach((pdvId) => {
      if (visibleIds.has(pdvId)) {
        this.markers[pdvId].setOpacity(1);
      } else {
        this.markers[pdvId].setOpacity(0.3);
      }
    });

    // Centrer sur le premier résultat si disponible
    if (visibleIds.size > 0) {
      const firstId = Array.from(visibleIds)[0];
      const firstResult = document.getElementById(`pdv-${firstId}`);
      if (firstResult) {
        const latMatch = firstResult.textContent.match(/Lat:\s*([-\d.]+)/);
        const lngMatch = firstResult.textContent.match(/Lng:\s*([-\d.]+)/);
        if (latMatch && lngMatch) {
          const lat = parseFloat(latMatch[1]);
          const lng = parseFloat(lngMatch[1]);
          this.map.setView([lat, lng], 10);
        }
      }
    }
  }

  resetMarkers() {
    Object.values(this.markers).forEach((marker) => {
      marker.setOpacity(1);
    });
  }
}
