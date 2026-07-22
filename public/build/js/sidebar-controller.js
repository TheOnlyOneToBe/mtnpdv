import { Controller } from '@hotwired/stimulus';

class SidebarController extends Controller {
    static targets = [];

    connect() {
        this.isMobile = window.innerWidth <= 768;
        this.restoreState();
        this.initializeGroups();
        this.attachWindowListener();
        this.attachFlyoutClickHandlers();
        this.attachOutsideClickHandler();

        // Écouter le clic sur le bouton toggle externe (dans la navbar)
        this.externalToggle = document.getElementById('sidebarToggleBtn');
        if (this.externalToggle) {
            this.externalToggleBound = (e) => this.toggle(e);
            this.externalToggle.addEventListener('click', this.externalToggleBound);
        }
    }

    disconnect() {
        // Nettoyer l'écouteur pour éviter les fuites mémoire
        if (this.externalToggle && this.externalToggleBound) {
            this.externalToggle.removeEventListener('click', this.externalToggleBound);
        }
        // Nettoyer les écouteurs flyout/outside
        if (this.flyoutClickHandler) {
            this.element.removeEventListener('click', this.flyoutClickHandler);
        }
        if (this.outsideClickHandler) {
            document.removeEventListener('click', this.outsideClickHandler);
        }
    }

    attachFlyoutClickHandlers() {
        // En mode collapsed-desktop, un clic sur un nav-group-toggle doit ouvrir/coller le flyout
        this.flyoutClickHandler = (event) => {
            if (!this.element.classList.contains('collapsed-desktop')) return;
            const btn = event.target.closest('.nav-group-toggle');
            if (!btn) return;
            // Empêcher le toggleGroup classique (qui ouvre/ferme le submenu sous la ligne)
            event.preventDefault();
            event.stopPropagation();
            const group = btn.getAttribute('data-group');
            const groupEl = btn.closest('.nav-group');
            if (!groupEl) return;
            const wasOpen = groupEl.classList.contains('flyout-open');
            // Fermer tous les autres flyouts
            this.element.querySelectorAll('.nav-group.flyout-open').forEach(g => {
                if (g !== groupEl) g.classList.remove('flyout-open');
            });
            if (wasOpen) {
                groupEl.classList.remove('flyout-open');
            } else {
                groupEl.classList.add('flyout-open');
            }
        };
        this.element.addEventListener('click', this.flyoutClickHandler);
    }

    attachOutsideClickHandler() {
        this.outsideClickHandler = (event) => {
            if (!this.element.classList.contains('collapsed-desktop')) return;
            if (!this.element.contains(event.target)) {
                this.element.querySelectorAll('.nav-group.flyout-open').forEach(g => {
                    g.classList.remove('flyout-open');
                });
            }
        };
        document.addEventListener('click', this.outsideClickHandler);
    }

    toggleGroup(event) {
        event.preventDefault();
        const button = event.currentTarget;
        const group = button.getAttribute('data-group');
        const submenu = document.getElementById(`nav-group-${group}`);
        const chevron = button.querySelector('.chevron');

        if (submenu) {
            submenu.classList.toggle('open');
            if (chevron) {
                chevron.style.transform = submenu.classList.contains('open')
                    ? 'rotate(180deg)'
                    : 'rotate(0deg)';
            }
            this.saveState(group, submenu.classList.contains('open'));
        }
    }

    toggle(event) {
        if (event) event.preventDefault();
        // Sur desktop, on "collapse" en mode icônes (sidebar reste visible, juste réduite)
        // Sur mobile, on ouvre/ferme en overlay
        if (this.isMobile) {
            this.element.classList.toggle('collapsed');
            this.element.classList.toggle('open');
            const isOpen = this.element.classList.contains('open');
            this.saveToggleState(isOpen ? 0 : 1);
        } else {
            this.element.classList.toggle('collapsed-desktop');
            const isIcon = this.element.classList.contains('collapsed-desktop');
            this.saveToggleState(isIcon ? 1 : 0);
        }
    }

    closeOnMobile() {
        if (this.isMobile) {
            this.element.classList.remove('open');
            this.element.classList.add('collapsed');
            this.saveToggleState(1);
        }
    }

    initializeGroups() {
        const groups = new Set();
        document.querySelectorAll('[data-group]').forEach(button => {
            groups.add(button.getAttribute('data-group'));
        });

        groups.forEach(groupName => {
            const button = document.querySelector(`[data-group="${groupName}"].nav-group-toggle`);
            const submenu = document.getElementById(`nav-group-${groupName}`);

            if (button && submenu) {
                const state = localStorage.getItem(`sidebar-group-${groupName}`);
                const chevron = button.querySelector('.chevron');
                if (state !== null) {
                    const isOpen = state === '1';
                    if (isOpen) {
                        submenu.classList.add('open');
                        if (chevron) {
                            chevron.style.transform = 'rotate(180deg)';
                        }
                    } else {
                        submenu.classList.remove('open');
                        if (chevron) {
                            chevron.style.transform = 'rotate(0deg)';
                        }
                    }
                } else {
                    // Si pas d'état en localStorage, on garde l'état initial rendu par Twig (qui ouvre si actif)
                    if (submenu.classList.contains('open')) {
                        if (chevron) {
                            chevron.style.transform = 'rotate(180deg)';
                        }
                    }
                }
            }
        });
    }

    restoreState() {
        const collapsed = this.getToggleState();
        if (!collapsed) return;
        if (this.isMobile) {
            this.element.classList.add('collapsed');
        } else {
            this.element.classList.add('collapsed-desktop');
        }
    }

    saveState(group, isOpen) {
        localStorage.setItem(`sidebar-group-${group}`, isOpen ? '1' : '0');
    }

    getState(group) {
        return localStorage.getItem(`sidebar-group-${group}`) === '1';
    }

    saveToggleState(collapsed) {
        localStorage.setItem('sidebar-collapsed', collapsed ? '1' : '0');
    }

    getToggleState() {
        return localStorage.getItem('sidebar-collapsed') === '1';
    }

    attachWindowListener() {
        window.addEventListener('resize', () => {
            const wasMobile = this.isMobile;
            this.isMobile = window.innerWidth <= 768;

            // Quand on passe de mobile à desktop
            if (wasMobile && !this.isMobile) {
                this.element.classList.remove('open', 'collapsed');
                // Restaurer l'état desktop depuis localStorage
                if (this.getToggleState()) {
                    this.element.classList.add('collapsed-desktop');
                }
            }
            // Quand on passe de desktop à mobile
            else if (!wasMobile && this.isMobile) {
                this.element.classList.remove('collapsed-desktop');
                this.element.classList.add('collapsed');
            }
        });
    }
}

export default SidebarController;
