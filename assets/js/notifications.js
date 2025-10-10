/**
 * Notifications System JavaScript
 * Handles notification dropdown, real-time updates, and interactions
 */

(function() {
    'use strict';
    
    let notificationsOpen = false;
    let notifications = [];
    let unreadCount = 0;
    
    /**
     * Initialize notifications system
     */
    function initNotifications() {
        // Create notifications dropdown HTML
        createNotificationsDropdown();
        
        // Attach event listeners
        attachEventListeners();
        
        // Load initial notifications
        loadNotifications();
        
        // Poll for new notifications every 30 seconds
        setInterval(pollNotifications, 30000);
    }
    
    /**
     * Create the notifications dropdown HTML
     */
    function createNotificationsDropdown() {
        const existingDropdown = document.getElementById('notifications-dropdown');
        if (existingDropdown) return;
        
        // Try to find the button
        let notifButton = document.querySelector('[title="Notifications"]');
        
        // If not found, try via bell icon
        if (!notifButton) {
            const bellIcon = document.querySelector('.fa-bell');
            if (bellIcon) {
                notifButton = bellIcon.closest('button');
            }
        }
        
        if (!notifButton) {
            console.error('Notifications button not found');
            return;
        }
        
        const dropdown = document.createElement('div');
        dropdown.id = 'notifications-dropdown';
        dropdown.className = 'w-96 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 hidden';
        dropdown.style.position = 'fixed';
        dropdown.style.zIndex = '9999';
        dropdown.style.maxHeight = '500px';
        dropdown.style.overflowY = 'auto';
        
        dropdown.innerHTML = `
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Notifications</h3>
                <div class="flex items-center space-x-2">
                    <button id="mark-all-read-btn" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400" title="Mark all as read">
                        <i class="fas fa-check-double"></i> Mark all read
                    </button>
                </div>
            </div>
            <div id="notifications-list" class="divide-y divide-gray-200 dark:divide-gray-700">
                <div class="p-4 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
            <div class="p-3 border-t border-gray-200 dark:border-gray-700 text-center">
                <a href="notifications.php" class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium">
                    View All Notifications <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        `;
        
        // Append dropdown to body instead of button container to avoid overflow issues
        document.body.appendChild(dropdown);
    }
    
    /**
     * Attach event listeners
     */
    function attachEventListeners() {
        // Notifications button click - try multiple selectors
        let notifButton = document.querySelector('[title="Notifications"]');
        
        // If not found, try via bell icon
        if (!notifButton) {
            const bellIcon = document.querySelector('.fa-bell');
            if (bellIcon) {
                notifButton = bellIcon.closest('button');
            }
        }
        
        if (notifButton) {
            notifButton.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleNotifications();
            });
        }
        
        // Mark all as read button
        document.addEventListener('click', function(e) {
            if (e.target.closest('#mark-all-read-btn')) {
                markAllAsRead();
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notifications-dropdown');
            const notifButton = document.querySelector('[title="Notifications"]') || 
                               document.querySelector('.fa-bell')?.closest('button');
            
            if (notificationsOpen && dropdown && !dropdown.contains(e.target) && 
                (!notifButton || !notifButton.contains(e.target))) {
                closeNotifications();
            }
        });
    }
    
    /**
     * Toggle notifications dropdown
     */
    function toggleNotifications() {
        // Make sure dropdown exists
        const dropdown = document.getElementById('notifications-dropdown');
        
        if (!dropdown) {
            createNotificationsDropdown();
        }
        
        if (notificationsOpen) {
            closeNotifications();
        } else {
            openNotifications();
        }
    }
    
    /**
     * Open notifications dropdown
     */
    function openNotifications() {
        const dropdown = document.getElementById('notifications-dropdown');
        
        if (dropdown) {
            // Position dropdown relative to notification button
            const notifButton = document.querySelector('[title="Notifications"]') || 
                               document.querySelector('.fa-bell')?.closest('button');
            
            if (notifButton) {
                const rect = notifButton.getBoundingClientRect();
                dropdown.style.position = 'fixed';
                dropdown.style.top = (rect.bottom + 8) + 'px'; // 8px gap below button
                dropdown.style.right = (window.innerWidth - rect.right) + 'px';
                dropdown.style.left = 'auto';
            }
            
            dropdown.classList.remove('hidden');
            notificationsOpen = true;
            loadNotifications();
        }
    }
    
    /**
     * Close notifications dropdown
     */
    function closeNotifications() {
        const dropdown = document.getElementById('notifications-dropdown');
        if (dropdown) {
            dropdown.classList.add('hidden');
            notificationsOpen = false;
        }
    }
    
    /**
     * Load notifications from server
     */
    function loadNotifications() {
        console.log('📡 Loading notifications from API...');
        
        fetch('../api/notifications.php?action=get_recent')
            .then(response => {
                console.log('API Response Status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('API Response Data:', data);
                if (data.success) {
                    notifications = data.notifications;
                    unreadCount = data.unread_count;
                    console.log(`✅ Loaded ${notifications.length} notifications, ${unreadCount} unread`);
                    updateNotificationBadge();
                    renderNotifications();
                } else {
                    console.error('❌ API returned success=false:', data);
                    showNotificationError();
                }
            })
            .catch(error => {
                console.error('❌ Error loading notifications:', error);
                console.error('Error details:', error.message);
                showNotificationError();
            });
    }
    
    /**
     * Poll for notification count (lightweight)
     */
    function pollNotifications() {
        fetch('../api/notifications.php?action=get_count')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.unread_count !== unreadCount) {
                    unreadCount = data.unread_count;
                    updateNotificationBadge();
                    
                    // If dropdown is open, reload notifications
                    if (notificationsOpen) {
                        loadNotifications();
                    }
                }
            })
            .catch(error => console.error('Error polling notifications:', error));
    }
    
    /**
     * Update notification badge
     */
    function updateNotificationBadge() {
        const notifButton = document.querySelector('[title="Notifications"]');
        if (!notifButton) return;
        
        let badge = notifButton.querySelector('.notification-badge');
        
        if (unreadCount > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notification-badge absolute top-0 right-0 w-5 h-5 bg-red-500 rounded-full text-white text-xs flex items-center justify-center font-bold';
                notifButton.appendChild(badge);
            }
            badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
        } else {
            if (badge) {
                badge.remove();
            }
        }
    }
    
    /**
     * Render notifications list
     */
    function renderNotifications() {
        const container = document.getElementById('notifications-list');
        if (!container) return;
        
        if (notifications.length === 0) {
            container.innerHTML = `
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-bell-slash text-4xl mb-2"></i>
                    <p>No notifications</p>
                </div>
            `;
            return;
        }
        
        container.innerHTML = notifications.map(notif => renderNotificationItem(notif)).join('');
        
        // Attach click handlers
        container.querySelectorAll('[data-notification-id]').forEach(item => {
            item.addEventListener('click', function() {
                const notifId = this.getAttribute('data-notification-id');
                const ticketId = this.getAttribute('data-ticket-id');
                handleNotificationClick(notifId, ticketId);
            });
        });
    }
    
    /**
     * Render single notification item
     */
    function renderNotificationItem(notif) {
        const isUnread = notif.is_read == 0;
        const icon = getNotificationIcon(notif.type);
        const color = getNotificationColor(notif.type);
        const timeAgo = getTimeAgo(notif.created_at);
        
        return `
            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition ${isUnread ? 'bg-blue-50 dark:bg-blue-900/20' : ''}" 
                 data-notification-id="${notif.id}" 
                 data-ticket-id="${notif.ticket_id || ''}">
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-10 h-10 rounded-full bg-${color}-100 dark:bg-${color}-900/30 flex items-center justify-center">
                            <i class="fas ${icon} text-${color}-600 dark:text-${color}-400"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            ${notif.title}
                            ${isUnread ? '<span class="ml-2 inline-block w-2 h-2 bg-blue-600 rounded-full"></span>' : ''}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            ${notif.message}
                        </p>
                        ${notif.ticket_title ? `<p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Ticket: ${notif.ticket_title}</p>` : ''}
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                            <i class="far fa-clock"></i> ${timeAgo}
                        </p>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Handle notification click
     */
    function handleNotificationClick(notificationId, ticketId) {
        // Mark as read
        markAsRead(notificationId);
        
        // Navigate to ticket if exists
        if (ticketId) {
            const isAdmin = window.location.href.includes('/admin/');
            const viewUrl = isAdmin ? `view_ticket.php?id=${ticketId}` : `view_ticket.php?id=${ticketId}`;
            window.location.href = viewUrl;
        }
    }
    
    /**
     * Mark single notification as read
     */
    function markAsRead(notificationId) {
        const formData = new FormData();
        formData.append('action', 'mark_read');
        formData.append('notification_id', notificationId);
        
        fetch('../api/notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
            }
        })
        .catch(error => console.error('Error marking as read:', error));
    }
    
    /**
     * Mark all notifications as read
     */
    function markAllAsRead() {
        const formData = new FormData();
        formData.append('action', 'mark_all_read');
        
        fetch('../api/notifications.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadNotifications();
                if (window.showToast) {
                    showToast('All notifications marked as read', 'success');
                }
            }
        })
        .catch(error => console.error('Error marking all as read:', error));
    }
    
    /**
     * Show error in notifications list
     */
    function showNotificationError() {
        const container = document.getElementById('notifications-list');
        if (container) {
            container.innerHTML = `
                <div class="p-4 text-center text-red-600">
                    <i class="fas fa-exclamation-circle"></i> Failed to load notifications
                </div>
            `;
        }
    }
    
    /**
     * Get notification icon
     */
    function getNotificationIcon(type) {
        const icons = {
            'ticket_assigned': 'fa-user-plus',
            'ticket_updated': 'fa-edit',
            'ticket_resolved': 'fa-check-circle',
            'ticket_created': 'fa-plus-circle',
            'comment_added': 'fa-comment',
            'status_changed': 'fa-exchange-alt',
            'priority_changed': 'fa-exclamation-circle'
        };
        return icons[type] || 'fa-bell';
    }
    
    /**
     * Get notification color
     */
    function getNotificationColor(type) {
        const colors = {
            'ticket_assigned': 'blue',
            'ticket_updated': 'yellow',
            'ticket_resolved': 'green',
            'ticket_created': 'purple',
            'comment_added': 'indigo',
            'status_changed': 'orange',
            'priority_changed': 'red'
        };
        return colors[type] || 'gray';
    }
    
    /**
     * Simple time ago function
     */
    function getTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) return 'just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
        if (seconds < 604800) return `${Math.floor(seconds / 86400)}d ago`;
        return date.toLocaleDateString();
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for all elements to be rendered
            setTimeout(initNotifications, 300);
        });
    } else {
        // DOM already loaded, wait a bit for dynamic elements
        setTimeout(initNotifications, 300);
    }
    
    // Export functions for external use
    window.NotificationsSystem = {
        reload: loadNotifications,
        markAsRead: markAsRead,
        markAllAsRead: markAllAsRead,
        init: initNotifications  // Allow manual initialization
    };
})();
