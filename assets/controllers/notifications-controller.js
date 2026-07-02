import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['badge', 'dropdown'];
    static outlets = ['modal'];

    connect() {
        this.updateNotifications();
        // Poll for new notifications every 30 seconds
        setInterval(() => this.updateNotifications(), 30000);
    }

    updateNotifications() {
        fetch('/notifications/non-lues')
            .then(response => response.json())
            .then(data => {
                this.updateBadge(data.count);
                this.updateDropdown(data.notifications);
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    updateBadge(count) {
        if (this.hasBadgeTarget) {
            if (count > 0) {
                this.badgeTarget.textContent = count;
                this.badgeTarget.classList.remove('d-none');
            } else {
                this.badgeTarget.classList.add('d-none');
            }
        }
    }

    updateDropdown(notifications) {
        if (!this.hasDropdownTarget) return;

        const dropdown = this.dropdownTarget;
        dropdown.innerHTML = '';

        if (notifications.length === 0) {
            dropdown.innerHTML = '<div class="dropdown-item text-muted"><i class="fas fa-bell-slash"></i> Aucune notification</div>';
            return;
        }

        notifications.forEach(notification => {
            const item = this.createNotificationItem(notification);
            dropdown.appendChild(item);
        });

        // Add divider and link to all notifications
        const divider = document.createElement('div');
        divider.className = 'dropdown-divider';
        dropdown.appendChild(divider);

        const link = document.createElement('a');
        link.href = '/notifications/list';
        link.className = 'dropdown-item text-center small';
        link.innerHTML = '<i class="fas fa-eye"></i> Voir toutes les notifications';
        dropdown.appendChild(link);
    }

    createNotificationItem(notification) {
        const item = document.createElement('a');
        item.href = notification.lien || '#';
        item.className = 'dropdown-item';
        item.dataset.notificationId = notification.id;

        const icon = document.createElement('i');
        icon.className = `fas ${notification.icone} text-${notification.couleur}`;

        const title = document.createElement('strong');
        title.textContent = notification.titre;

        const message = document.createElement('div');
        message.className = 'small text-muted';
        message.textContent = notification.message;

        const date = document.createElement('div');
        date.className = 'small text-muted';
        date.textContent = new Date(notification.dateCreation).toLocaleString('fr-FR');

        item.appendChild(icon);
        item.appendChild(document.createTextNode(' '));
        item.appendChild(title);
        item.appendChild(document.createElement('br'));
        item.appendChild(message);
        item.appendChild(document.createElement('br'));
        item.appendChild(date);

        item.addEventListener('click', (e) => {
            if (notification.lien) {
                this.markAsRead(notification.id);
            }
        });

        return item;
    }

    markAsRead(notificationId) {
        fetch(`/notifications/${notificationId}/lire`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
        }).then(() => {
            this.updateNotifications();
        }).catch(error => console.error('Error marking notification as read:', error));
    }
}
