import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // Check for saved dark mode preference
        const isDarkMode = localStorage.getItem('darkMode') === 'true' ||
            window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        this.updateTheme(isDarkMode);
    }
    
    toggle() {
        const isDarkMode = !document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isDarkMode);
        this.updateTheme(isDarkMode);
    }
    
     updateTheme(isDark) {
        // Appliquer sur <html> au lieu de <body>
        if (isDark) {
            document.documentElement.classList.add('dark-mode');
        } else {
            document.documentElement.classList.remove('dark-mode');
        }
    }
}
