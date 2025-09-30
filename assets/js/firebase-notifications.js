/**
 * Firebase Cloud Messaging Notification System
 * Handles real-time push notifications for IT Help Desk
 */

import { messaging } from './firebase-config.js';
import { getToken, onMessage } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-messaging.js";

class FirebaseNotificationService {
    constructor() {
        this.token = null;
        this.isSupported = false;
        this.userType = window.CURRENT_USER_TYPE || 'unknown';
        this.userName = window.CURRENT_USER_NAME || 'User';
        this.userId = window.USER_ID || 0;
        
        console.log('🔔 Firebase Notification Service initializing...');
        this.init();
    }
    
    async init() {
        try {
            // Check if messaging is supported
            if (!messaging) {
                console.warn('⚠️ Firebase Messaging not supported in this browser');
                this.showFallbackNotification();
                return;
            }
            
            this.isSupported = true;
            
            // Request notification permission
            const permission = await this.requestPermission();
            if (permission === 'granted') {
                await this.getToken();
                this.setupMessageListener();
                this.showNotificationStatus(true);
            } else {
                console.warn('⚠️ Notification permission denied');
                this.showNotificationStatus(false);
            }
            
        } catch (error) {
            console.error('❌ Firebase Notification Service init failed:', error);
            this.showFallbackNotification();
        }
    }
    
    async requestPermission() {
        try {
            if (!('Notification' in window)) {
                console.warn('⚠️ Browser does not support notifications');
                return 'denied';
            }
            
            let permission = Notification.permission;
            
            if (permission === 'default') {
                permission = await Notification.requestPermission();
            }
            
            console.log(`🔔 Notification permission: ${permission}`);
            return permission;
            
        } catch (error) {
            console.error('❌ Permission request failed:', error);
            return 'denied';
        }
    }
    
    async getToken() {
        try {
            // Register service worker first
            let swRegistration;
            try {
                swRegistration = await navigator.serviceWorker.register('./firebase-messaging-sw.js');
                console.log('✅ Service Worker registered:', swRegistration);
            } catch (swError) {
                console.warn('⚠️ Service Worker registration failed:', swError);
                // Continue without custom service worker
            }
            
            // VAPID key for your Firebase project
            const vapidKey = 'BPLmZDFhZTTD890E4iVhN1MhlcNY4dBehh7r0BPNZrbqf6_Wfo5j6qvkE0QOXAUfGPh6c2VkDiqt2LhNXJgpsAw';
            
            const tokenOptions = { 
                vapidKey: vapidKey
            };
            
            // Include service worker registration if available
            if (swRegistration) {
                tokenOptions.serviceWorkerRegistration = swRegistration;
            }
            
            const currentToken = await getToken(messaging, tokenOptions);
            
            if (currentToken) {
                console.log('🔑 FCM Token received:', currentToken.substring(0, 20) + '...');
                this.token = currentToken;
                
                // Send token to server for storage
                await this.saveTokenToServer(currentToken);
                
            } else {
                console.warn('⚠️ No registration token available');
            }
            
        } catch (error) {
            console.error('❌ Token generation failed:', error);
        }
    }
    
    async saveTokenToServer(token) {
        try {
            const response = await fetch('api/save_fcm_token.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    token: token,
                    user_type: this.userType,
                    user_id: this.userId
                })
            });
            
            const result = await response.json();
            if (result.success) {
                console.log('✅ FCM token saved to server');
            } else {
                console.error('❌ Failed to save FCM token:', result.error);
            }
            
        } catch (error) {
            console.error('❌ Error saving FCM token:', error);
        }
    }
    
    setupMessageListener() {
        try {
            onMessage(messaging, (payload) => {
                console.log('📨 Foreground message received:', payload);
                
                // Extract notification data
                const notification = payload.notification || {};
                const data = payload.data || {};
                
                // Show custom notification
                this.showCustomNotification({
                    title: notification.title || 'IT Help Desk',
                    body: notification.body || 'New update',
                    icon: notification.icon || '/favicon.ico',
                    data: data,
                    action: data.action || 'view'
                });
                
                // Handle specific notification types
                this.handleNotificationAction(data);
            });
            
            console.log('✅ Foreground message listener setup complete');
            
        } catch (error) {
            console.error('❌ Message listener setup failed:', error);
        }
    }
    
    showCustomNotification(options) {
        // Create in-app notification banner
        const notificationBanner = document.createElement('div');
        notificationBanner.className = 'fixed top-4 right-4 bg-white border border-gray-200 rounded-lg shadow-lg z-50 max-w-sm transform transition-all duration-300 ease-in-out';
        notificationBanner.style.transform = 'translateX(100%)';
        
        const iconMap = {
            'new_reply': 'fa-comment',
            'status_change': 'fa-info-circle',
            'new_ticket': 'fa-plus-circle',
            'assignment': 'fa-user-tag',
            'default': 'fa-bell'
        };
        
        const colorMap = {
            'new_reply': 'text-blue-600 bg-blue-50',
            'status_change': 'text-orange-600 bg-orange-50',
            'new_ticket': 'text-green-600 bg-green-50',
            'assignment': 'text-purple-600 bg-purple-50',
            'default': 'text-gray-600 bg-gray-50'
        };
        
        const notificationType = options.data.type || 'default';
        const iconClass = iconMap[notificationType] || iconMap.default;
        const colorClass = colorMap[notificationType] || colorMap.default;
        
        notificationBanner.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 ${colorClass} rounded-full flex items-center justify-center">
                            <i class="fas ${iconClass} text-sm"></i>
                        </div>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900">${options.title}</p>
                        <p class="text-sm text-gray-600 mt-1">${options.body}</p>
                        ${options.data.ticket_id ? `<p class="text-xs text-gray-500 mt-1">Ticket #${options.data.ticket_id}</p>` : ''}
                    </div>
                    <div class="flex-shrink-0 ml-2">
                        <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    </div>
                </div>
                ${options.data.action_url ? `
                <div class="mt-3 pt-3 border-t border-gray-100">
                    <button onclick="window.location.href='${options.data.action_url}'" 
                            class="text-sm bg-blue-600 text-white px-3 py-1 rounded hover:bg-blue-700 transition-colors">
                        View Details
                    </button>
                </div>` : ''}
            </div>
        `;
        
        document.body.appendChild(notificationBanner);
        
        // Slide in animation
        setTimeout(() => {
            notificationBanner.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto-hide after 8 seconds
        setTimeout(() => {
            if (notificationBanner.parentNode) {
                notificationBanner.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notificationBanner.parentNode) {
                        notificationBanner.remove();
                    }
                }, 300);
            }
        }, 8000);
        
        // Also show browser notification if page is not visible
        if (document.hidden && 'Notification' in window && Notification.permission === 'granted') {
            new Notification(options.title, {
                body: options.body,
                icon: options.icon,
                tag: `ithelp-${options.data.ticket_id || Date.now()}`,
                data: options.data
            });
        }
    }
    
    handleNotificationAction(data) {
        switch (data.action) {
            case 'new_reply':
                // Refresh chat if on the same ticket
                if (window.TICKET_ID && data.ticket_id == window.TICKET_ID) {
                    console.log('🔄 Refreshing chat for new reply...');
                    // The Firebase real-time listener should handle this automatically
                }
                break;
                
            case 'status_change':
                // Reload page to show updated status
                setTimeout(() => {
                    if (window.TICKET_ID && data.ticket_id == window.TICKET_ID) {
                        window.location.reload();
                    }
                }, 2000);
                break;
                
            case 'new_ticket':
                // Show notification to IT staff for new tickets
                if (this.userType === 'it_staff') {
                    this.showNewTicketAlert(data);
                }
                break;
                
            default:
                console.log('📨 Notification received:', data);
        }
    }
    
    showNewTicketAlert(data) {
        // Special handling for new ticket notifications to IT staff
        const alertDiv = document.createElement('div');
        alertDiv.className = 'fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 bg-white rounded-lg shadow-xl z-50 border border-gray-200';
        alertDiv.innerHTML = `
            <div class="p-6 max-w-md">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-plus-circle text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900">New Ticket Created</h3>
                        <p class="text-sm text-gray-600">Priority: ${data.priority || 'Normal'}</p>
                    </div>
                </div>
                <p class="text-gray-700 mb-4">${data.subject || 'New support request needs attention'}</p>
                <div class="flex space-x-3">
                    <button onclick="window.location.href='view_ticket.php?id=${data.ticket_id}'" 
                            class="flex-1 bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors">
                        View Ticket
                    </button>
                    <button onclick="this.closest('.fixed').remove()" 
                            class="flex-1 bg-gray-200 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-300 transition-colors">
                        Later
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(alertDiv);
    }
    
    showNotificationStatus(enabled) {
        const statusDiv = document.getElementById('notificationStatus') || this.createStatusDiv();
        
        if (enabled) {
            statusDiv.innerHTML = '🔔 <span class="text-green-600 text-xs">Push notifications active</span>';
            statusDiv.className = 'fixed bottom-16 right-4 bg-white border border-green-200 px-2 py-1 rounded shadow-sm z-40';
        } else {
            statusDiv.innerHTML = '🔕 <span class="text-gray-600 text-xs">Notifications disabled</span>';
            statusDiv.className = 'fixed bottom-16 right-4 bg-white border border-gray-200 px-2 py-1 rounded shadow-sm z-40';
        }
        
        // Auto-hide after 5 seconds
        setTimeout(() => {
            if (statusDiv.parentNode) {
                statusDiv.remove();
            }
        }, 5000);
    }
    
    showFallbackNotification() {
        console.log('📱 Setting up fallback notification polling...');
        
        // Fallback to existing notification system if Firebase not available
        const statusDiv = document.createElement('div');
        statusDiv.innerHTML = '📱 <span class="text-blue-600 text-xs">Using fallback notifications</span>';
        statusDiv.className = 'fixed bottom-16 right-4 bg-white border border-blue-200 px-2 py-1 rounded shadow-sm z-40';
        statusDiv.id = 'notificationStatus';
        document.body.appendChild(statusDiv);
        
        setTimeout(() => statusDiv.remove(), 5000);
    }
    
    createStatusDiv() {
        const statusDiv = document.createElement('div');
        statusDiv.id = 'notificationStatus';
        document.body.appendChild(statusDiv);
        return statusDiv;
    }
    
    // Public method to send notification (for testing)
    async testNotification(type = 'test') {
        const testData = {
            title: 'Test Notification',
            body: 'This is a test notification from Firebase',
            data: {
                type: type,
                ticket_id: window.TICKET_ID || '123',
                action: 'test'
            }
        };
        
        this.showCustomNotification(testData);
        console.log('🧪 Test notification sent');
    }
}

// Export for global use
export { FirebaseNotificationService };

// Auto-initialize when imported
let notificationService = null;
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
        notificationService = new FirebaseNotificationService();
        window.firebaseNotifications = notificationService;
        console.log('🔔 Firebase Notification Service ready');
    }, 1000);
});