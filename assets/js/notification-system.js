/**
 * Notification System JavaScript
 * Facebook-style notification bell with dropdown
 */

class NotificationSystem {
    constructor() {
        this.notifications = [];
        this.isDropdownOpen = false;
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.loadNotifications();
        // Auto-refresh notifications every 30 seconds
        setInterval(() => this.loadNotifications(), 30000);
    }
    
    bindEvents() {
        const bell = document.getElementById('notificationBell');
        const dropdown = document.getElementById('notificationDropdown');
        const markAllReadBtn = document.getElementById('markAllRead');
        const clearAllBtn = document.getElementById('clearAll');
        
        // Toggle dropdown
        if (bell && dropdown) {
            bell.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleDropdown();
            });
        }
        
        // Mark all as read
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => this.markAllAsRead());
        }
        
        // Clear all notifications
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', () => this.clearAll());
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (bell && dropdown && !bell.contains(e.target) && !dropdown.contains(e.target)) {
                this.closeDropdown();
            }
        });
    }
    
    toggleDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        this.isDropdownOpen = !this.isDropdownOpen;
        dropdown.classList.toggle('hidden', !this.isDropdownOpen);
        
        if (this.isDropdownOpen) {
            this.loadNotifications();
        }
    }
    
    closeDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        dropdown.classList.add('hidden');
        this.isDropdownOpen = false;
    }
    
    async loadNotifications() {
        try {
            this.showLoading();
            
            const response = await fetch('api/notifications.php?action=get_notifications');
            const data = await response.json();
            
            if (data.success) {
                this.notifications = data.notifications;
                this.updateBadge(data.unread_count);
                this.renderNotifications(data.notifications);
            } else {
                throw new Error(data.error || 'Failed to load notifications');
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
            this.showError('Failed to load notifications');
        } finally {
            this.hideLoading();
        }
    }
    
    updateBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (!badge) return; // Exit if element doesn't exist
        
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
            
            // Add pulse animation for new notifications
            badge.style.animation = 'pulse 2s infinite';
        } else {
            badge.classList.add('hidden');
            badge.style.animation = 'none';
        }
    }
    
    renderNotifications(notifications) {
        const container = document.getElementById('notificationsList');
        const emptyState = document.getElementById('notificationsEmpty');
        
        // Exit early if elements don't exist (e.g., on employee pages)
        if (!container) return;
        
        if (notifications.length === 0) {
            container.innerHTML = '';
            if (emptyState) emptyState.classList.remove('hidden');
            return;
        }
        
        if (emptyState) emptyState.classList.add('hidden');
        
        container.innerHTML = notifications.map(notification => `
            <div class="notification-item px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors cursor-pointer ${notification.is_read ? '' : 'bg-blue-50'}" 
                 data-id="${notification.id}"
                 onclick="notificationSystem.handleNotificationClick(${notification.id}, '${notification.action_url || '#'}')">
                <div class="flex items-start space-x-3">
                    <!-- User Avatar -->
                    <div class="flex-shrink-0">
                        ${notification.user_photo ? 
                            `<img src="${notification.user_photo}" alt="User" class="w-8 h-8 rounded-full object-cover border border-gray-200">` :
                            `<div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center border border-gray-200">
                                <i class="fas fa-user text-white text-xs"></i>
                            </div>`
                        }
                    </div>
                    
                    <!-- Notification Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-1">
                            <h4 class="font-medium text-gray-900 text-sm truncate">${this.escapeHtml(notification.title)}</h4>
                            ${notification.is_read ? '' : '<div class="w-2 h-2 bg-blue-500 rounded-full ml-2 animate-pulse flex-shrink-0"></div>'}
                        </div>
                        <p class="text-gray-600 text-xs leading-relaxed line-clamp-2">${this.escapeHtml(notification.message)}</p>
                        <p class="text-gray-400 text-xs mt-1 flex items-center">
                            <i class="fas fa-clock mr-1"></i>
                            ${this.formatDate(notification.created_at)}
                        </p>
                    </div>
                    
                    <!-- Close Button -->
                    <div class="flex-shrink-0">
                        <button onclick="event.stopPropagation(); notificationSystem.markAsRead(${notification.id})" 
                                class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100 transition-colors" 
                                title="Mark as read">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    handleNotificationClick(id, actionUrl) {
        this.markAsRead(id);
        if (actionUrl && actionUrl !== '#') {
            // Small delay to allow mark as read to process
            setTimeout(() => {
                window.location.href = actionUrl;
            }, 100);
        }
    }
    
    async markAsRead(id) {
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=mark_read&id=${id}`
            });
            
            const data = await response.json();
            if (data.success) {
                // Update UI immediately
                const item = document.querySelector(`[data-id="${id}"]`);
                if (item) {
                    item.classList.remove('bg-blue-50');
                    const dot = item.querySelector('.bg-blue-500');
                    if (dot) dot.remove();
                }
                
                // Update badge
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    const currentCount = parseInt(badge.textContent) || 0;
                    this.updateBadge(Math.max(0, currentCount - 1));
                }
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=mark_all_read'
            });
            
            const data = await response.json();
            if (data.success) {
                this.loadNotifications(); // Reload to update UI
                this.showSuccess('All notifications marked as read');
            }
        } catch (error) {
            console.error('Error marking all as read:', error);
            this.showError('Failed to mark notifications as read');
        }
    }
    
    async clearAll() {
        if (!confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
            return;
        }
        
        try {
            const response = await fetch('api/notifications.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=clear_all'
            });
            
            const data = await response.json();
            if (data.success) {
                this.loadNotifications(); // Reload to update UI
                this.showSuccess('All notifications cleared');
            }
        } catch (error) {
            console.error('Error clearing notifications:', error);
            this.showError('Failed to clear notifications');
        }
    }
    
    showLoading() {
        const loadingElement = document.getElementById('notificationsLoading');
        const emptyElement = document.getElementById('notificationsEmpty');
        
        if (loadingElement) loadingElement.classList.remove('hidden');
        if (emptyElement) emptyElement.classList.add('hidden');
    }

    hideLoading() {
        const loadingElement = document.getElementById('notificationsLoading');
        if (loadingElement) loadingElement.classList.add('hidden');
    }

    showError(message) {
        const container = document.getElementById('notificationsList');
        if (!container) return; // Exit if element doesn't exist
        
        container.innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-exclamation-triangle text-red-400 text-2xl mb-2"></i>
                <p class="text-sm text-red-600">${message}</p>
                <button onclick="notificationSystem.loadNotifications()" class="text-xs text-blue-600 hover:text-blue-800 mt-2">
                    Try again
                </button>
            </div>
        `;
    }
    
    showSuccess(message) {
        // Create temporary success message
        const toast = document.createElement('div');
        toast.className = 'fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 transition-all duration-300';
        toast.innerHTML = `<i class="fas fa-check mr-2"></i>${message}`;
        document.body.appendChild(toast);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffMs = now - date;
        const diffMins = Math.floor(diffMs / 60000);
        const diffHours = Math.floor(diffMs / 3600000);
        const diffDays = Math.floor(diffMs / 86400000);
        
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        
        return date.toLocaleDateString();
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize notification system when DOM is loaded
let notificationSystem;
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('notificationBell')) {
        notificationSystem = new NotificationSystem();
    }
});

// Export for global access
window.NotificationSystem = NotificationSystem;