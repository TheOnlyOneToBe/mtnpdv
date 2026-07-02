import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        points: Array,
        user: Object,
        centerLat: Number,
        centerLng: Number,
        zoomLevel: Number
    };

    static targets = ['mapContainer', 'viewToggle'];

    connect() {
        // Only initialize map if mapContainer target exists
        if (this.hasMapContainerTarget) {
            this.initializeMap();
        }
    }

    initializeMap() {
        const centerLat = this.centerLatValue || 3.8480;
        const centerLng = this.centerLngValue || 11.5021;
        const zoomLevel = this.zoomLevelValue || 6;

        // Initialize Leaflet map
        this.map = L.map(this.mapContainerTarget).setView([centerLat, centerLng], zoomLevel);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19,
        }).addTo(this.map);

        // Add user location if available
        if (this.userValue) {
            this.addUserMarker();
        }

        // Add PDV points if available
        if (this.pointsValue && this.pointsValue.length > 0) {
            this.addPointMarkers();
        }

        // Invalidate size to ensure map renders correctly
        setTimeout(() => {
            this.map.invalidateSize();
        }, 100);
    }

    addUserMarker() {
        const user = this.userValue;
        L.marker([user.lat, user.lng], {
            icon: L.icon({
                iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0OCA0OCIgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIj48Y2lyY2xlIGN4PSIyNCIgY3k9IjI0IiByPSIyMiIgZmlsbD0iIzNiODJmNiIvPjwvc3ZnPg==',
                iconSize: [32, 32],
                iconAnchor: [16, 32],
            })
        }).addTo(this.map).bindPopup('<strong>Ma position</strong>');
    }

    addPointMarkers() {
        const points = this.pointsValue;
        const markerGroup = L.featureGroup();

        points.forEach(point => {
            L.marker([point.lat, point.lng], {
                icon: L.icon({
                    iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCA0OCA0OCIgd2lkdGg9IjMyIiBoZWlnaHQ9IjMyIj48Y2lyY2xlIGN4PSIyNCIgY3k9IjI0IiByPSIyMiIgZmlsbD0iIzE2YTM0YSIvPjwvc3ZnPg==',
                    iconSize: [32, 32],
                    iconAnchor: [16, 32],
                })
            }).addTo(markerGroup).bindPopup(`<strong>${point.nom}</strong><br>${point.ville}`);
        });

        markerGroup.addTo(this.map);
    }

    toggleView(event) {
        const view = event.currentTarget.dataset.view;

        // Update active button
        if (this.hasViewToggleTarget) {
            this.viewToggleTargets.forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }

        // Handle view toggle logic
        // This can be extended to toggle between list and map views
        if (view === 'map') {
            // Make sure map is visible and initialized
            if (this.hasMapContainerTarget) {
                this.mapContainerTarget.style.display = 'block';
                if (this.map) {
                    this.map.invalidateSize();
                }
            }
        }
    }
}
