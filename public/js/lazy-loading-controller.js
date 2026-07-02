import { Controller } from '@hotwired/stimulus';

/**
 * Contrôleur de chargement différé des images.
 * Ajoute loading="lazy" aux images sans attribut explicite et
 * charge les images déclarées via data-src dès qu'elles approchent
 * du viewport (IntersectionObserver).
 */
export default class extends Controller {
    connect() {
        this.element.querySelectorAll('img:not([loading])').forEach((img) => {
            img.setAttribute('loading', 'lazy');
        });

        const deferred = this.element.querySelectorAll('img[data-src]');
        if (deferred.length === 0) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            deferred.forEach((img) => this.load(img));
            return;
        }

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    this.load(entry.target);
                    this.observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '200px' });

        deferred.forEach((img) => this.observer.observe(img));
    }

    disconnect() {
        if (this.observer) {
            this.observer.disconnect();
            this.observer = null;
        }
    }

    load(img) {
        img.src = img.dataset.src;
        delete img.dataset.src;
    }
}
