/**
 * Notification Center UI Component
 * Displays notification history and preferences
 */

class NotificationCenter {
    constructor() {
        this.isOpen = false;
        this.notifications = [];
        this.preferences = {};
        this.unreadCount = 0;
        
        console.log('🔔 Notification Center initializing...');
        this.init();
    }
    
    async init() {
        try {
            await this.loadPreferences();
            this.createUI();
            this.loadNotificationHistory();
            this.setupEventListeners();
            
            console.log('✅ Notification Center ready');
            
        } catch (error) {
            console.error('❌ Notification Center init failed:', error);
        }
    }
    
    async loadPreferences() {
        try {
            const response = await fetch('api/notification_preferences.php');
            const result = await response.json();
            
            if (result.success) {
                this.preferences = result.preferences;
            }
            
        } catch (error) {
            console.error('Error loading preferences:', error);
        }
    }
    
    createUI() {
        // Create notification bell icon
        const bellHTML = `
            <div id="notificationBell" class="relative cursor-pointer hover:bg-gray-100 p-2 rounded-lg transition-colors">
                <i class="fas fa-bell text-gray-600 text-lg"></i>
                <span id="notificationBadge" class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden">0</span>
            </div>
        `;
        
        // Add to header or navigation
        const headerNav = document.querySelector('.bg-white.shadow-lg nav, .bg-gray-800 nav, header nav');
        if (headerNav) {
            const bellContainer = document.createElement('div');
            bellContainer.innerHTML = bellHTML;
            headerNav.appendChild(bellContainer);
        }
        
        // Create notification center panel
        const centerHTML = `
            <div id="notificationCenter" class="fixed top-0 right-0 h-full w-96 bg-white shadow-xl transform translate-x-full transition-transform duration-300 z-50 border-l border-gray-200">
                <div class="flex flex-col h-full">
                    <!-- Header -->
                    <div class="bg-blue-600 text-white p-4 flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-bell mr-2"></i>
                            <h3 class="text-lg font-semibold">Notifications</h3>
                        </div>
                        <button id="closeNotificationCenter" class="text-blue-200 hover:text-white">
                            <i class="fas fa-times text-lg"></i>
                        </button>
                    </div>
                    
                    <!-- Tabs -->
                    <div class="flex border-b border-gray-200">
                        <button class="tab-btn flex-1 px-4 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700 active" data-tab="history">
                            History
                        </button>
                        <button class="tab-btn flex-1 px-4 py-3 text-sm font-medium text-gray-500 border-b-2 border-transparent hover:text-gray-700" data-tab="preferences">
                            Settings
                        </button>
                    </div>
                    
                    <!-- Content -->
                    <div class="flex-1 overflow-hidden">
                        <!-- History Tab -->
                        <div id="historyTab" class="h-full">
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-4">
                                    <span class="text-sm text-gray-600">Recent notifications</span>
                                    <button id="markAllRead" class="text-sm text-blue-600 hover:text-blue-700">Mark all read</button>
                                </div>
                            </div>
                            <div id="notificationList" class="flex-1 overflow-y-auto px-4 pb-4">
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-bell-slash text-3xl mb-2"></i>
                                    <p>No notifications yet</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Preferences Tab -->
                        <div id="preferencesTab" class="h-full overflow-y-auto p-4 hidden">
                            <div class="space-y-6">
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-3">Notification Types</h4>
                                    <div class="space-y-3">
                                        <label class="flex items-center justify-between">
                                            <span class="text-sm">New replies</span>
                                            <input type="checkbox" class="pref-toggle" data-pref="new_replies" checked>
                                        </label>
                                        <label class="flex items-center justify-between">
                                            <span class="text-sm">Status changes</span>
                                            <input type="checkbox" class="pref-toggle" data-pref="status_changes" checked>
                                        </label>
                                        <label class="flex items-center justify-between">
                                            <span class="text-sm">New tickets</span>
                                            <input type="checkbox" class="pref-toggle" data-pref="new_tickets" checked>
                                        </label>
                                        <label class="flex items-center justify-between">
                                            <span class="text-sm">Assignments</span>
                                            <input type="checkbox" class="pref-toggle" data-pref="assignments" checked>
                                        </label>
                                    </div>
                                </div>
                                
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-3">Delivery Methods</h4>
                                    <div class="space-y-3">
                                        <label class="flex items-center justify-between">
                                            <span class="text-sm">Browser notifications</span>
                                            <input type="checkbox" class="pref-toggle" data-pref="browser_notifications" checked>
                                        </label>
                                        <label class="flex items-center justify-between">
                                            <span class="text-sm">Email notifications</span>
                                            <input type="checkbox" class="pref-toggle" data-pref="email_notifications" checked>
                                        </label>
                                        <label class="flex items-center justify-between">
                                            <span class="text-sm">Sound alerts</span>
                                            <input type="checkbox" class="pref-toggle" data-pref="sound_notifications" checked>
                                        </label>
                                    </div>
                                </div>
                                
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-3">Schedule</h4>
                                    <div class="space-y-3">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm">Active hours</span>
                                            <div class="flex space-x-2 text-sm">
                                                <input type="time" class="pref-input border rounded px-2 py-1" data-pref="notification_hours_start" value="08:00">
                                                <span>to</span>
                                                <input type="time" class="pref-input border rounded px-2 py-1" data-pref="notification_hours_end" value="18:00">
                                            </div>
                                        </div>
                                        <label class="flex items-center justify-between">
                                            <span class="text-sm">Weekend notifications</span>
                                            <input type="checkbox" class="pref-toggle" data-pref="weekend_notifications">
                                        </label>
                                    </div>
                                </div>
                                
                                <button id="savePreferences" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition-colors">
                                    Save Preferences
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Overlay -->
            <div id="notificationOverlay" class="fixed inset-0 bg-black bg-opacity-25 hidden z-40"></div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', centerHTML);
    }
    
    setupEventListeners() {
        // Bell click to toggle center
        document.getElementById('notificationBell')?.addEventListener('click', () => {
            this.toggle();
        });
        
        // Close button
        document.getElementById('closeNotificationCenter')?.addEventListener('click', () => {
            this.close();
        });
        
        // Overlay click to close
        document.getElementById('notificationOverlay')?.addEventListener('click', () => {
            this.close();
        });
        
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                this.switchTab(btn.dataset.tab);
            });
        });
        
        // Mark all read
        document.getElementById('markAllRead')?.addEventListener('click', () => {
            this.markAllRead();
        });
        
        // Save preferences
        document.getElementById('savePreferences')?.addEventListener('click', () => {
            this.savePreferences();
        });
        
        // Preference change listeners
        document.querySelectorAll('.pref-toggle, .pref-input').forEach(input => {
            input.addEventListener('change', () => {
                this.updatePreference(input.dataset.pref, input.type === 'checkbox' ? input.checked : input.value);
            });
        });
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        const center = document.getElementById('notificationCenter');
        const overlay = document.getElementById('notificationOverlay');
        
        if (center && overlay) {
            center.classList.remove('translate-x-full');
            overlay.classList.remove('hidden');
            this.isOpen = true;
            
            // Load fresh data when opening
            this.loadNotificationHistory();
        }
    }
    
    close() {
        const center = document.getElementById('notificationCenter');
        const overlay = document.getElementById('notificationOverlay');
        
        if (center && overlay) {
            center.classList.add('translate-x-full');
            overlay.classList.add('hidden');
            this.isOpen = false;
        }
    }
    
    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
            if (btn.dataset.tab === tabName) {
                btn.classList.add('active', 'border-blue-500', 'text-blue-600');
            }
        });
        
        // Show/hide tab content
        document.getElementById('historyTab').classList.toggle('hidden', tabName !== 'history');
        document.getElementById('preferencesTab').classList.toggle('hidden', tabName !== 'preferences');
    }
    
    async loadNotificationHistory() {
        // This would typically load from server
        // For now, we'll show placeholder data
        const listContainer = document.getElementById('notificationList');
        if (!listContainer) return;
        
        // Simulate some notifications
        const mockNotifications = [
            {
                id: 1,
                type: 'new_reply',
                title: 'New Reply - Ticket #123',
                message: 'John Doe replied to your ticket',
                time: '2 minutes ago',
                read: false,
                ticketId: 123
            },
            {
                id: 2,
                type: 'status_change',
                title: 'Status Update - Ticket #456',
                message: 'Your ticket has been resolved',
                time: '1 hour ago',
                read: true,
                ticketId: 456
            }
        ];
        
        if (mockNotifications.length === 0) {
            listContainer.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-bell-slash text-3xl mb-2"></i>
                    <p>No notifications yet</p>
                </div>
            `;
            return;
        }
        
        const notificationHTML = mockNotifications.map(notif => `
            <div class="notification-item p-3 border border-gray-200 rounded-lg mb-2 cursor-pointer hover:bg-gray-50 ${notif.read ? 'opacity-75' : 'bg-blue-50 border-blue-200'}" data-id="${notif.id}">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <div class="w-8 h-8 ${this.getNotificationIcon(notif.type)} rounded-full flex items-center justify-center">
                            <i class="fas ${this.getNotificationIconClass(notif.type)} text-sm"></i>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900">${notif.title}</p>
                        <p class="text-sm text-gray-600 mt-1">${notif.message}</p>
                        <p class="text-xs text-gray-500 mt-2">${notif.time}</p>
                    </div>
                    ${!notif.read ? '<div class="w-2 h-2 bg-blue-500 rounded-full flex-shrink-0"></div>' : ''}
                </div>
            </div>
        `).join('');
        
        listContainer.innerHTML = notificationHTML;
        
        // Add click listeners
        listContainer.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', () => {
                const notifId = item.dataset.id;
                const notification = mockNotifications.find(n => n.id == notifId);
                if (notification && notification.ticketId) {
                    window.location.href = `view_ticket.php?id=${notification.ticketId}`;
                }
            });
        });
        
        // Update unread count
        this.updateUnreadCount(mockNotifications.filter(n => !n.read).length);
    }
    
    getNotificationIcon(type) {
        const icons = {
            'new_reply': 'bg-blue-100 text-blue-600',
            'status_change': 'bg-orange-100 text-orange-600',
            'new_ticket': 'bg-green-100 text-green-600',
            'assignment': 'bg-purple-100 text-purple-600'
        };
        return icons[type] || 'bg-gray-100 text-gray-600';
    }
    
    getNotificationIconClass(type) {
        const icons = {
            'new_reply': 'fa-comment',
            'status_change': 'fa-info-circle',
            'new_ticket': 'fa-plus-circle',
            'assignment': 'fa-user-tag'
        };
        return icons[type] || 'fa-bell';
    }
    
    updateUnreadCount(count) {
        this.unreadCount = count;
        const badge = document.getElementById('notificationBadge');
        
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count.toString();
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }
    }
    
    markAllRead() {
        // Mark all notifications as read
        document.querySelectorAll('.notification-item').forEach(item => {
            item.classList.remove('bg-blue-50', 'border-blue-200');
            item.classList.add('opacity-75');
            const dot = item.querySelector('.bg-blue-500');
            if (dot) dot.remove();
        });
        
        this.updateUnreadCount(0);
    }
    
    async savePreferences() {
        try {
            const preferences = {};
            
            // Collect all preference values
            document.querySelectorAll('.pref-toggle').forEach(input => {
                preferences[input.dataset.pref] = input.checked ? 'enabled' : 'disabled';
            });
            
            document.querySelectorAll('.pref-input').forEach(input => {
                preferences[input.dataset.pref] = input.value;
            });
            
            const response = await fetch('api/notification_preferences.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ preferences })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Show success feedback
                const saveBtn = document.getElementById('savePreferences');
                const originalText = saveBtn.textContent;
                saveBtn.textContent = '✅ Saved!';
                saveBtn.classList.add('bg-green-600');
                
                setTimeout(() => {
                    saveBtn.textContent = originalText;
                    saveBtn.classList.remove('bg-green-600');
                }, 2000);
                
            } else {
                throw new Error(result.error || 'Failed to save preferences');
            }
            
        } catch (error) {
            console.error('Error saving preferences:', error);
            alert('Failed to save preferences. Please try again.');
        }
    }
    
    updatePreference(key, value) {
        this.preferences[key] = value;
        // Auto-save could be implemented here
    }
}

// Auto-initialize notification center
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        window.notificationCenter = new NotificationCenter();
    }, 2000);
});

export { NotificationCenter };