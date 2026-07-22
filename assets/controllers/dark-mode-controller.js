import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        // Initialize dark mode from localStorage or system preference
        this.initializeDarkMode();

        // Watch for system dark mode changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('darkMode')) {
                    this.updateTheme(e.matches);
                }
            });
        }
    }

    initializeDarkMode() {
        const savedDarkMode = localStorage.getItem('darkMode');
        const prefersDarkMode = window.matchMedia('(prefers-color-scheme: dark)').matches;

        let isDarkMode = savedDarkMode !== null ? JSON.parse(savedDarkMode) : prefersDarkMode;

        this.updateTheme(isDarkMode);
    }

    toggle() {
        const isDarkMode = document.documentElement.classList.contains('dark-mode');
        this.updateTheme(!isDarkMode);
        localStorage.setItem('darkMode', JSON.stringify(!isDarkMode));
    }

    updateTheme(isDark) {
        if (isDark) {
            document.documentElement.classList.add('dark-mode');
            document.documentElement.style.colorScheme = 'dark';
        } else {
            document.documentElement.classList.remove('dark-mode');
            document.documentElement.style.colorScheme = 'light';
        }

        this.updateToggleIcon();
    }

    updateToggleIcon() {
        const toggle = this.element.querySelector('.dark-mode-toggle i');
        if (!toggle) return;

        const isDarkMode = document.documentElement.classList.contains('dark-mode');
        toggle.className = isDarkMode ? 'fas fa-sun' : 'fas fa-moon';
    }
}
