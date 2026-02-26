/**
 * Global Notification System
 * Replaces alert() calls with modern toast notifications
 */

class NotificationSystem {
    constructor() {
        this.container = null;
        this.init();
    }

    init() {
        // Create notification container if it doesn't exist
        if (!document.getElementById('notification-container')) {
            this.container = document.createElement('div');
            this.container.id = 'notification-container';
            this.container.className = 'notification-container';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('notification-container');
        }
    }

    show(type, message, duration = 5000) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;

        const icon = this.getIcon(type);
        const closeBtn = '<button class="notification-close" onclick="this.parentElement.remove()">&times;</button>';

        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-icon">${icon}</span>
                <span class="notification-message">${message}</span>
                ${closeBtn}
            </div>
        `;

        this.container.appendChild(notification);

        // Animate in
        setTimeout(() => notification.classList.add('show'), 10);

        // Auto remove after duration
        if (duration > 0) {
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.classList.remove('show');
                    setTimeout(() => notification.remove(), 300);
                }
            }, duration);
        }

        // Manual close
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    }

    getIcon(type) {
        const icons = {
            success: '<i class="bi bi-check-circle-fill"></i>',
            error: '<i class="bi bi-exclamation-triangle-fill"></i>',
            warning: '<i class="bi bi-exclamation-circle-fill"></i>',
            info: '<i class="bi bi-info-circle-fill"></i>'
        };
        return icons[type] || icons.info;
    }
}

// Global instance
const notificationSystem = new NotificationSystem();

// Global function for easy access
function showNotification(type, message, duration) {
    notificationSystem.show(type, message, duration);
}

// Override alert function globally
window.alert = function(message) {
    showNotification('info', message);
};

// Export for modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { NotificationSystem, showNotification };
}
