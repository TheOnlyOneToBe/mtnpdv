import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // Lazy load images with loading="lazy" attribute
        const lazyImages = document.querySelectorAll('img[loading="lazy"]');
        
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        observer.unobserve(img);
                    }
                });
            });
            
            lazyImages.forEach(img => observer.observe(img));
        }
    }
}
