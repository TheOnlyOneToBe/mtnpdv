import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = ['mapContainer', 'listView', 'mapView', 'viewToggle'];
  static values = {
    centerLat: { type: Number, default: 3.8480 },
    centerLng: { type: Number, default: 11.5021 },
    zoomLevel: { type: Number, default: 6 },
    pdvs: { type: Array, default: [] }, // Add pdvs value to accept all PDVs data
  };

  connect() {
    this.map = null;
    this.markers = {};
    this.highlightedMarker = null;
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

    this.loadAllMarkers();
  }

  loadAllMarkers() {
    // Load markers from pdvsValue which has all PDVs data
    this.pdvsValue.forEach((pdv) => {
      this.addMarker(pdv.id, pdv.nomPdv, pdv.lat, pdv.lng, pdv);
    });

    // Fit bounds to all markers
    if (Object.keys(this.markers).length > 0) {
      const group = L.featureGroup(Object.values(this.markers));
      this.map.fitBounds(group.getBounds().pad(0.1));
    }
  }

  addMarker(pdvId, nomPdv, lat, lng, pdvData) {
    if (this.markers[pdvId]) {
      this.map.removeLayer(this.markers[pdvId]);
    }

    const marker = L.marker([lat, lng], {
      icon: this.createCustomIcon(pdvData),
    });

    const popupContent = this.createPopupContent(pdvData);
    marker.bindPopup(popupContent);

    // Add click listener to popup to go to show page
    marker.on('click', () => {
      // Do nothing, let popup open
    });

    marker.addTo(this.map);
    this.markers[pdvId] = marker;
  }

  createCustomIcon(pdvData) {
    let color = '#16a34a'; // Default: ACTIF

    if (pdvData.statut === 'FERME') {
      color = '#dc2626';
    } else if (pdvData.statut === 'SUSPENDU') {
      color = '#ea580c';
    } else if (pdvData.statut === 'INACTIF') {
      color = '#6b7280';
    }

    const svgString = `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" width="36" height="36">
        <circle cx="24" cy="24" r="22" fill="${color}" opacity="0.95"/>
        <path d="M24 8 L32 32 H16 Z" fill="${color}"/>
      </svg>
    `;

    const svgBase64 = btoa(svgString);
    const iconUrl = `data:image/svg+xml;base64,${svgBase64}`;

    return L.icon({
      iconUrl: iconUrl,
      iconSize: [36, 36],
      iconAnchor: [18, 36],
      popupAnchor: [0, -36],
    });
  }

  createHighlightedIcon(pdvData) {
    let color = '#16a34a'; // Default: ACTIF

    if (pdvData.statut === 'FERME') {
      color = '#dc2626';
    } else if (pdvData.statut === 'SUSPENDU') {
      color = '#ea580c';
    } else if (pdvData.statut === 'INACTIF') {
      color = '#6b7280';
    }

    const svgString = `
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60" width="48" height="48">
        <circle cx="30" cy="30" r="28" fill="#fbbf24" opacity="0.3" />
        <circle cx="30" cy="30" r="22" fill="${color}" opacity="1"/>
        <path d="M30 10 L40 40 H20 Z" fill="${color}"/>
      </svg>
    `;

    const svgBase64 = btoa(svgString);
    const iconUrl = `data:image/svg+xml;base64,${svgBase64}`;

    return L.icon({
      iconUrl: iconUrl,
      iconSize: [48, 48],
      iconAnchor: [24, 48],
      popupAnchor: [0, -48],
    });
  }

  createPopupContent(pdvData) {
    const showUrl = `/admin/pdv/${pdvData.id}`;
    return `
      <div style="min-width: 280px;">
        <div class="mb-2">
          <h6 style="margin: 0 0 0.5rem 0;">
            <i class="fas fa-store" style="color: #1e40af;"></i>
            ${pdvData.nomPdv}
          </h6>
        </div>
        <div style="font-size: 0.9rem;">
          <p style="margin: 0.3rem 0;"><strong>Code:</strong> ${pdvData.codeRef}</p>
          <p style="margin: 0.3rem 0;"><strong>Ville:</strong> ${pdvData.ville}</p>
          ${pdvData.adresse ? `<p style="margin: 0.3rem 0;"><strong>Adresse:</strong> ${pdvData.adresse}</p>` : ''}
          <p style="margin: 0.3rem 0;"><strong>Téléphone:</strong> ${pdvData.telephone}</p>
          ${pdvData.gerant ? `<p style="margin: 0.3rem 0;"><strong>Gérant:</strong> ${pdvData.gerant}</p>` : ''}
          <p style="margin: 0.3rem 0;">
            <strong>Statut:</strong>
            <span style="padding: 2px 6px; border-radius: 3px; font-size: 0.8rem; color: white; background-color: ${this.getStatusColor(pdvData.statut)};">
              ${pdvData.statut}
            </span>
          </p>
        </div>
        <div style="margin-top: 0.75rem; border-top: 1px solid #ddd; padding-top: 0.75rem; display: flex; gap: 0.5rem;">
          <a href="${showUrl}" class="btn btn-sm btn-info"><i class="fas fa-eye"></i> Détails</a>
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

  highlightPdv(pdvId) {
    // Reset previous highlight
    if (this.highlightedMarker && this.highlightedMarker.pdvId) {
      const prevPdv = this.pdvsValue.find(p => p.id === this.highlightedMarker.pdvId);
      if (prevPdv && this.markers[this.highlightedMarker.pdvId]) {
        this.markers[this.highlightedMarker.pdvId].setIcon(this.createCustomIcon(prevPdv));
      }
    }

    // Highlight new PDV
    if (this.markers[pdvId]) {
      const pdvData = this.pdvsValue.find(p => p.id === pdvId);
      if (pdvData) {
        this.markers[pdvId].setIcon(this.createHighlightedIcon(pdvData));
        this.highlightedMarker = { pdvId: pdvId };
        this.markers[pdvId].openPopup();
      }
    }
  }

  centerOnPdv(pdvId) {
    if (this.markers[pdvId]) {
      const latLng = this.markers[pdvId].getLatLng();
      this.map.setView(latLng, 14);
      this.highlightPdv(pdvId);
    }
  }

  filterMarkersBySearch(searchResults) {
    const visibleIds = new Set(
      searchResults.map((pdv) => pdv.getAttribute('id').replace('pdv-', ''))
    );

    Object.keys(this.markers).forEach((pdvId) => {
      if (visibleIds.has(pdvId)) {
        this.markers[pdvId].setOpacity(1);
      } else {
        this.markers[pdvId].setOpacity(0.2);
      }
    });

    if (visibleIds.size > 0) {
      const firstId = Array.from(visibleIds)[0];
      this.centerOnPdv(firstId);
    }
  }

  resetMarkers() {
    // Reset all markers to full opacity and normal icon
    this.pdvsValue.forEach((pdv) => {
      if (this.markers[pdv.id]) {
        this.markers[pdv.id].setOpacity(1);
        this.markers[pdv.id].setIcon(this.createCustomIcon(pdv));
      }
    });

    // Fit bounds to all markers
    if (Object.keys(this.markers).length > 0) {
      const group = L.featureGroup(Object.values(this.markers));
      this.map.fitBounds(group.getBounds().pad(0.1));
    }

    this.highlightedMarker = null;
  }
}
