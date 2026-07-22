import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['badge', 'dropdown'];
    static values = {
        isAgent: { type: Boolean, default: false },
    };

    connect() {
        this.knownNotificationIds = new Set();
        this.initialized = false;
        this.eventSource = null;
        this.reconnectDelay = 3000;

        this.updateNotifications();
        this.connectStream();

        this.handleVisibility = () => {
            if (document.hidden) {
                this.disconnectStream();
            } else {
                this.updateNotifications();
                this.connectStream();
            }
        };
        document.addEventListener('visibilitychange', this.handleVisibility);

        this.boundMarkRead = (e) => {
            const link = e.target.closest('[data-notification-id]');
            if (link) {
                const id = link.dataset.notificationId;
                if (id) {
                    this.markAsRead(id);
                }
            }
        };
        this.dropdownTarget?.addEventListener('click', this.boundMarkRead);
    }

    disconnect() {
        this.disconnectStream();
        document.removeEventListener('visibilitychange', this.handleVisibility);
        if (this.boundMarkRead && this.hasDropdownTarget) {
            this.dropdownTarget.removeEventListener('click', this.boundMarkRead);
        }
    }

    connectStream() {
        if (this.eventSource || !window.EventSource) {
            return;
        }

        this.eventSource = new EventSource('/notifications/stream');

        this.eventSource.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                this.handleNotificationUpdate(data);
            } catch (error) {
                console.error('Erreur parsing SSE notifications:', error);
            }
        };

        this.eventSource.onerror = () => {
            this.disconnectStream();
            setTimeout(() => this.connectStream(), this.reconnectDelay);
            this.reconnectDelay = Math.min(this.reconnectDelay * 2, 30000);
        };
    }

    disconnectStream() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }

    onDropdownOpen() {
        this.updateNotifications();
    }

    updateNotifications() {
        fetch('/notifications/non-lues')
            .then((response) => response.json())
            .then((data) => this.handleNotificationUpdate(data))
            .catch((error) => console.error('Error fetching notifications:', error));
    }

    handleNotificationUpdate(data) {
        this.updateBadge(data.count);
        this.updateDropdown(data.notifications);

        if (this.isAgentValue && data.pendingApprovisionnements !== undefined) {
            this.updateSidebarBadge(data.pendingApprovisionnements);
        }

        if (!this.initialized) {
            data.notifications.forEach((n) => this.knownNotificationIds.add(String(n.id)));
            this.initialized = true;
            return;
        }

        const newNotifications = data.notifications.filter(
            (n) => !this.knownNotificationIds.has(String(n.id)),
        );

        newNotifications.forEach((notification) => {
            this.knownNotificationIds.add(String(notification.id));
            this.showNotificationToast(notification);
        });

        data.notifications.forEach((n) => this.knownNotificationIds.add(String(n.id)));
    }

    showNotificationToast(notification) {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) {
            return;
        }

        const toastController = this.application.getControllerForElementAndIdentifier(
            toastContainer,
            'toast',
        );

        const message = notification.lien
            ? `${notification.message} — Cliquez pour voir les détails.`
            : notification.message;

        if (toastController) {
            toastController.info(message, notification.titre);

            if (notification.lien) {
                setTimeout(() => {
                    const lastToast = toastContainer.querySelector('.toast:last-child .toast-body');
                    if (lastToast && !lastToast.querySelector('.notification-toast-link')) {
                        const link = document.createElement('a');
                        link.href = notification.lien;
                        link.className = 'notification-toast-link btn btn-sm btn-outline-info mt-2';
                        link.innerHTML = '<i class="fas fa-external-link-alt"></i> Voir le détail';
                        link.addEventListener('click', () => this.markAsRead(notification.id));
                        lastToast.appendChild(link);
                    }
                }, 50);
            }
        }
    }

    updateBadge(count) {
        if (!this.hasBadgeTarget) {
            return;
        }

        if (count > 0) {
            this.badgeTarget.textContent = count;
            this.badgeTarget.classList.remove('d-none');
            this.badgeTarget.style.display = '';
        } else {
            this.badgeTarget.classList.add('d-none');
            this.badgeTarget.style.display = 'none';
        }
    }

    updateSidebarBadge(count) {
        const badge = document.getElementById('sidebar-approvisionnements-badge');
        if (!badge) {
            return;
        }

        if (count > 0) {
            badge.textContent = count;
            badge.style.display = '';
            badge.classList.remove('d-none');
        } else {
            badge.textContent = '';
            badge.style.display = 'none';
        }

        const navBadge = document.getElementById('sidebar-visites-nav-badge');
        if (navBadge) {
            if (count > 0) {
                navBadge.textContent = count;
                navBadge.style.display = '';
            } else {
                navBadge.textContent = '';
                navBadge.style.display = 'none';
            }
        }
    }

    updateDropdown(notifications) {
        if (!this.hasDropdownTarget) {
            return;
        }

        const dropdown = this.dropdownTarget;
        dropdown.innerHTML = '';

        if (notifications.length === 0) {
            dropdown.innerHTML = '<li class="dropdown-item text-muted"><i class="fas fa-bell-slash"></i> Aucune notification</li>';
            return;
        }

        notifications.forEach((notification) => {
            dropdown.appendChild(this.createNotificationItem(notification));
        });

        const divider = document.createElement('li');
        divider.innerHTML = '<hr class="dropdown-divider">';
        dropdown.appendChild(divider);

        const linkLi = document.createElement('li');
        const link = document.createElement('a');
        link.href = '/notifications/list';
        link.className = 'dropdown-item text-center small';
        link.innerHTML = '<i class="fas fa-eye"></i> Voir toutes les notifications';
        linkLi.appendChild(link);
        dropdown.appendChild(linkLi);
    }

    createNotificationItem(notification) {
        const li = document.createElement('li');
        const item = document.createElement('a');
        item.href = notification.lien || '#';
        item.className = 'dropdown-item notification-item notification-unread';
        item.dataset.notificationId = notification.id;

        const wrapper = document.createElement('div');
        wrapper.className = 'd-flex gap-2 align-items-start';

        const icon = document.createElement('i');
        icon.className = `fas ${notification.icone} text-${notification.couleur} mt-1`;

        const content = document.createElement('div');
        content.className = 'flex-grow-1';

        const title = document.createElement('strong');
        title.className = 'd-block';
        title.textContent = notification.titre;

        const message = document.createElement('div');
        message.className = 'small text-muted';
        message.textContent = notification.message.length > 80
            ? notification.message.substring(0, 80) + '…'
            : notification.message;

        const date = document.createElement('div');
        date.className = 'small text-muted';
        date.textContent = new Date(notification.dateCreation).toLocaleString('fr-FR');

        content.appendChild(title);
        content.appendChild(message);
        content.appendChild(date);
        wrapper.appendChild(icon);
        wrapper.appendChild(content);
        item.appendChild(wrapper);
        li.appendChild(item);

        return li;
    }

    markAsRead(notificationId) {
        fetch(`/notifications/${notificationId}/lire`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        })
            .then(() => this.updateNotifications())
            .catch((error) => console.error('Error marking notification as read:', error));
    }
}
