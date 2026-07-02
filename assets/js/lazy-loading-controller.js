import { Controller } from '@hotwired/stimulus';

export default class LazyLoadingController extends Controller {
    connect() {
        this.initializeLazyLoading();
    }

    initializeLazyLoading() {
        // Use Intersection Observer API for lazy loading
        if ('IntersectionObserver' in window) {
            this.observeImages();
        } else {
            // Fallback for older browsers
            this.loadAllImages();
        }
    }

    observeImages() {
        const options = {
            root: null,
            rootMargin: '50px',
            threshold: 0.01
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, options);

        // Observe all lazy-load images
        document.querySelectorAll('img[data-src]').forEach(img => {
            observer.observe(img);
        });

        // Observe lazy-load iframes and other media
        document.querySelectorAll('[data-src][data-lazy]').forEach(elem => {
            observer.observe(elem);
        });
    }

    loadImage(img) {
        const src = img.getAttribute('data-src');
        if (!src) return;

        img.addEventListener('load', () => {
            img.classList.add('loaded');
        });

        img.addEventListener('error', () => {
            img.classList.add('error');
        });

        // Fade in effect
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.3s ease-in';

        img.src = src;
        img.removeAttribute('data-src');

        setTimeout(() => {
            img.style.opacity = '1';
        }, 10);
    }

    loadAllImages() {
        document.querySelectorAll('img[data-src]').forEach(img => {
            this.loadImage(img);
        });
    }
}
