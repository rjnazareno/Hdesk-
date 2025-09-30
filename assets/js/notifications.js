/**
 * IT Help Desk - Notification System
 * Handles browser notifications and update checking
 */

class NotificationSystem {
    constructor() {
        // Get ticket ID from global variable set by PHP
        this.ticketId = window.TICKET_ID;
        this.sessionResponseAdded = window.SESSION_RESPONSE_ADDED === 'true';
        this.updateChecker = null;
        this.init();
    }

    init() {
        console.log('Notification system ready for ticket:', this.ticketId);
        
        const enableBtn = document.getElementById('enableNotifications');
        const statusDiv = document.getElementById('notificationStatus');
        
        // Check if browser notifications are manually enabled and start checker if needed
        if (localStorage.getItem(`notifications_ticket_${this.ticketId}`) === 'enabled') {
            this.updateButtonState(true);
            statusDiv?.classList.remove('hidden');
            this.startUpdateChecker();
        }
        
        // Handle manual notification enable/disable
        enableBtn?.addEventListener('click', () => {
            if (Notification.permission === 'denied') {
                alert('Notifications are blocked. Please enable them in your browser settings and refresh the page.');
                return;
            }
            
            if (Notification.permission === 'default') {
                Notification.requestPermission().then((permission) => {
                    if (permission === 'granted') {
                        this.enableNotifications();
                    } else {
                        alert('Please allow notifications to use this feature.');
                    }
                });
            } else if (Notification.permission === 'granted') {
                if (localStorage.getItem(`notifications_ticket_${this.ticketId}`) === 'enabled') {
                    this.disableNotifications();
                } else {
                    this.enableNotifications();
                }
            }
        });
        
        // Test buttons functionality
        document.getElementById('testNotificationBtn')?.addEventListener('click', () => {
            if (Notification.permission === 'granted') {
                new Notification('Test Notification - IT Help Desk', {
                    body: `This is a test notification for Ticket #${this.ticketId}`,
                    icon: '/favicon.ico',
                    tag: `ticket-${this.ticketId}-test`
                });
                console.log('Test notification sent');
            } else {
                alert('Notifications not permitted. Permission: ' + Notification.permission);
            }
        });
        
        document.getElementById('checkNowBtn')?.addEventListener('click', () => {
            console.log('Manual update check triggered');
            this.checkForUpdates();
        });
        
        // Stop update checker when page is unloaded
        window.addEventListener('beforeunload', () => {
            if (this.updateChecker) {
                clearInterval(this.updateChecker);
            }
        });
        
        console.log('Notification system initialized for ticket:', this.ticketId);
        console.log('Notification permission:', Notification.permission);
        console.log('Notifications enabled:', localStorage.getItem(`notifications_ticket_${this.ticketId}`));
    }
    
    enableNotifications() {
        localStorage.setItem(`notifications_ticket_${this.ticketId}`, 'enabled');
        this.updateButtonState(true);
        document.getElementById('notificationStatus')?.classList.remove('hidden');
        
        this.startUpdateChecker();
        
        if (Notification.permission === 'granted') {
            new Notification('IT Help Desk - Notifications Enabled', {
                body: `You'll now receive desktop notifications for updates to Ticket #${this.ticketId}`,
                icon: '/favicon.ico',
                tag: `ticket-${this.ticketId}-enabled`
            });
        }
        
        console.log('Browser notifications enabled for ticket:', this.ticketId);
    }
    
    disableNotifications() {
        localStorage.removeItem(`notifications_ticket_${this.ticketId}`);
        this.updateButtonState(false);
        document.getElementById('notificationStatus')?.classList.add('hidden');
        
        this.stopUpdateChecker();
        
        console.log('Browser notifications disabled for ticket:', this.ticketId);
    }
    
    updateButtonState(enabled) {
        const enableBtn = document.getElementById('enableNotifications');
        if (!enableBtn) return;
        
        if (enabled) {
            enableBtn.innerHTML = '<i class="fas fa-bell-slash mr-2"></i>Disable Notifications';
            enableBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            enableBtn.classList.add('bg-gray-600', 'hover:bg-gray-700');
        } else {
            enableBtn.innerHTML = '<i class="fas fa-bell mr-2"></i>Enable Browser Notifications';
            enableBtn.classList.remove('bg-gray-600', 'hover:bg-gray-700');
            enableBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }
    }
    
    showInPageNotification(message) {
        const banner = document.createElement('div');
        banner.className = 'fixed top-4 right-4 bg-blue-600 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in';
        banner.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-bell mr-3"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(banner);
        
        setTimeout(() => {
            if (banner.parentNode) {
                banner.remove();
            }
        }, 5000);
    }
    
    startUpdateChecker() {
        console.log('Update checker disabled - Firebase FCM handles real-time notifications');
        // Polling disabled - Firebase FCM provides real-time notifications
        // this.updateChecker = setInterval(() => this.checkForUpdates(), 30000);
    }
    
    stopUpdateChecker() {
        if (this.updateChecker) {
            clearInterval(this.updateChecker);
            console.log('Update checker stopped');
        }
    }
    
    checkForUpdates() {
        console.log('Polling disabled - Firebase FCM handles real-time notifications');
        return; // Firebase FCM handles notifications
        
        /* LEGACY POLLING CODE DISABLED
        if (document.hidden) return;
        
        console.log('Checking for updates on ticket:', this.ticketId);
        
        fetch(`api/safe_check_updates.php?id=${this.ticketId}`)
            .then(response => {
                console.log('API Response status:', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Update check data:', data);
                if (data.hasUpdates) {
                    console.log('New updates found, showing notifications');
                    
                    this.showInPageNotification(data.message);
                    
                    const browserNotificationsEnabled = localStorage.getItem(`notifications_ticket_${this.ticketId}`) === 'enabled';
                    if (browserNotificationsEnabled && Notification.permission === 'granted') {
                        new Notification(`Ticket #${this.ticketId} - New Update`, {
                            body: data.message || 'New activity on your ticket',
                            icon: '/favicon.ico',
                            tag: `ticket-${this.ticketId}-update`,
                            requireInteraction: false
                        });
                        console.log('Desktop notification sent');
                    }
                    
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    console.log('No updates found');
                }
            })
            .catch(error => {
                console.error('Update check failed:', error);
                if (error.message.includes('404')) {
                    clearInterval(this.updateChecker);
                }
            });
        */ // END LEGACY POLLING CODE
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification system using global variables
    if (typeof window.TICKET_ID !== 'undefined') {
        window.notificationSystem = new NotificationSystem();
    }
});