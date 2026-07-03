import { Controller } from '@hotwired/stimulus';

class SidebarController extends Controller {
    static targets = [];

    connect() {
        this.isMobile = window.innerWidth <= 768;
        this.restoreState();
        this.initializeGroups();
        this.attachWindowListener();
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
        this.element.classList.toggle('collapsed');

        // En mobile, toggle 'open' au lieu de 'collapsed'
        if (this.isMobile) {
            this.element.classList.toggle('open');
            const isOpen = this.element.classList.contains('open');
            this.saveToggleState(isOpen ? 0 : 1); // Inverse car on sauvegarde 'collapsed'
        } else {
            const isCollapsed = this.element.classList.contains('collapsed');
            this.saveToggleState(isCollapsed ? 1 : 0);
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
                const isOpen = this.getState(groupName);
                if (isOpen) {
                    submenu.classList.add('open');
                    const chevron = button.querySelector('.chevron');
                    if (chevron) {
                        chevron.style.transform = 'rotate(180deg)';
                    }
                }
            }
        });
    }

    restoreState() {
        const collapsed = this.getToggleState();
        if (collapsed) {
            this.element.classList.add('collapsed');
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
            }
            // Quand on passe de desktop à mobile
            else if (!wasMobile && this.isMobile) {
                this.element.classList.remove('open');
                this.element.classList.add('collapsed');
            }
        });
    }
}

export default SidebarController;
