// Enhanced Notification System JavaScript
class EnhancedNotificationManager {
    constructor() {
        this.notificationBell = document.getElementById('notificationBell');
        this.notificationDropdown = document.getElementById('notificationDropdown');
        this.bellBadge = document.getElementById('bellBadge');
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.startAutoRefresh();
        this.loadNotifications();
    }

    setupEventListeners() {
        if (this.notificationBell && this.notificationDropdown) {
            this.notificationBell.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown();
            });

            window.addEventListener('click', (e) => {
                if (!this.notificationDropdown.contains(e.target) && !this.notificationBell.contains(e.target)) {
                    this.notificationDropdown.classList.remove('show');
                }
            });
        }
    }

    toggleDropdown() {
        if (this.notificationDropdown) {
            this.notificationDropdown.classList.toggle('show');
        }
    }

    async loadNotifications() {
        try {
            const response = await fetch('check_new_tickets_enhanced.php');
            const data = await response.json();
            
            this.updateNotificationCount(data.new_count);
            this.updateNotificationList(data.notifications_html);
            this.updateSidebarBadge(data.total_pending);
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    updateNotificationCount(count) {
        if (this.bellBadge) {
            if (count > 0) {
                this.bellBadge.textContent = count;
                this.bellBadge.style.display = 'block';
            } else {
                this.bellBadge.style.display = 'none';
            }
        }
    }

    updateNotificationList(html) {
        if (this.notificationDropdown) {
            const notifList = this.notificationDropdown.querySelector('.notif-list');
            if (notifList) {
                notifList.innerHTML = html;
            }
        }
    }

    updateSidebarBadge(count) {
        const sidebarBadge = document.getElementById('pendingBadge');
        if (sidebarBadge) {
            sidebarBadge.textContent = count;
            sidebarBadge.style.display = count > 0 ? 'block' : 'none';
        }
    }

    startAutoRefresh() {
        // Refresh every 10 seconds
        setInterval(() => {
            this.loadNotifications();
        }, 10000);
    }

    // Method to mark notifications as read
    async markAsRead(ticketId) {
        try {
            const response = await fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ticket_id: ticketId })
            });
            const result = await response.json();
            if (result.success) {
                this.loadNotifications();
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const notificationManager = new EnhancedNotificationManager();
    
    // Make it globally available
    window.notificationManager = notificationManager;
});

// Add enhanced CSS for notifications
const style = document.createElement('style');
style.textContent = `
    .notif-item {
        padding: 12px;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
    }
    
    .notif-item:hover {
        background-color: #f8f9fa;
    }
    
    .notif-item:last-child {
        border-bottom: none;
    }
    
    .priority-badge {
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 11px;
        color: white;
        font-weight: bold;
    }
    
    .priority-low { background-color: #28a745; }
    .priority-medium { background-color: #ffc107; color: #000; }
    .priority-high { background-color: #dc3545; }
    
    .action-btn {
        padding: 4px 8px;
        background-color: #007bff;
        color: white;
        text-decoration: none;
        border-radius: 3px;
        font-size: 12px;
        margin-right: 5px;
    }
    
    .action-btn:hover {
        background-color: #0056b3;
    }
    
    .notif-empty {
        padding: 20px;
        text-align: center;
        color: #666;
    }
    
    .notification-dropdown {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        width: 350px;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .notification-dropdown.show {
        display: block;
    }
    
    .notif-header {
        padding: 10px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #eee;
        font-weight: bold;
    }
    
    .notif-title {
        padding: 5px 10px;
        color: #666;
        font-size: 12px;
    }
`;
document.head.appendChild(style);
