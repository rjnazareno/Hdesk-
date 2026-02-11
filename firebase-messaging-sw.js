/**
 * Firebase Cloud Messaging Service Worker
 * ResolveIT Help Desk - Background Notification Handler
 * 
 * This file handles push notifications when the app is closed or in background
 */

// Import Firebase scriptsssssss
importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.7.0/firebase-messaging-compat.js');

// Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyD-vvCkZbG7fKwwH4QSVReSEJSBPWAUZ_4",
    authDomain: "resolveit-417da.firebaseapp.com",
    projectId: "resolveit-417da",
    storageBucket: "resolveit-417da.firebasestorage.app",
    messagingSenderId: "14301030590",
    appId: "1:14301030590:web:cf4900b203385add4256ef"
};

// Initialize Firebase app in service worker
firebase.initializeApp(firebaseConfig);

// Get Firebase Messaging instance
const messaging = firebase.messaging();

/**
 * Handle background messages
 * This is triggered when a notification is received while the app is in background
 */
messaging.onBackgroundMessage((payload) => {
    console.log('[firebase-messaging-sw.js] Received background message:', payload);
    
    // Extract notification data
    const notificationTitle = payload.notification?.title || 'ResolveIT Notification';
    const notificationBody = payload.notification?.body || 'You have a new notification';
    const notificationIcon = payload.notification?.icon || '/img/ResolveIT Logo Only without Background.png';
    const ticketId = payload.data?.ticket_id;
    const notificationType = payload.data?.type || 'default';
    const targetUrl = payload.data?.url || '/';
    
    // Notification options
    const notificationOptions = {
        body: notificationBody,
        icon: notificationIcon,
        badge: '/img/ResolveIT Logo Only without Background.png',
        tag: ticketId ? `ticket-${ticketId}` : 'notification',
        data: {
            ticketId: ticketId,
            ticket_id: ticketId,
            type: notificationType,
            url: targetUrl,
            timestamp: Date.now()
        },
        requireInteraction: true,
        vibrate: [200, 100, 200],
        actions: []
    };
    
    // Add action buttons based on notification type
    if (ticketId) {
        notificationOptions.actions = [
            {
                action: 'view',
                title: 'View Ticket',
                icon: '/img/view-icon.png'
            },
            {
                action: 'dismiss',
                title: 'Dismiss',
                icon: '/img/close-icon.png'
            }
        ];
    }
    
    // Show notification
    return self.registration.showNotification(notificationTitle, notificationOptions);
});

/**
 * Handle notification click event
 */
self.addEventListener('notificationclick', (event) => {
    console.log('[firebase-messaging-sw.js] Notification clicked:', event.notification.tag);
    
    event.notification.close();
    
    const action = event.action;
    const data = event.notification.data;
    
    // Handle different actions
    if (action === 'dismiss') {
        // Just close the notification
        return;
    }
    
    // Default action or 'view' action
    let targetUrl = '/';
    
    // Use the URL from backend if available
    if (data.url) {
        targetUrl = data.url;
    } else if (data.ticketId || data.ticket_id) {
        // Fallback: construct URL from ticket ID
        const ticketId = data.ticketId || data.ticket_id;
        targetUrl = `/customer/view_ticket.php?id=${ticketId}`;
    }
    
    // Open or focus the app window
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if there's already a window open
                for (let i = 0; i < clientList.length; i++) {
                    const client = clientList[i];
                    if (client.url.includes(targetUrl) && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // If no window is open, open a new one
                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
    );
});

/**
 * Handle notification close event
 */
self.addEventListener('notificationclose', (event) => {
    console.log('[firebase-messaging-sw.js] Notification closed:', event.notification.tag);
    
    // Optional: Track notification dismissals
    const data = event.notification.data;
    
    if (data.ticketId) {
        // Could send analytics event here
        console.log(`Notification for ticket ${data.ticketId} was closed`);
    }
});

/**
 * Service Worker activation
 */
self.addEventListener('activate', (event) => {
    console.log('[firebase-messaging-sw.js] Service Worker activated');
    event.waitUntil(clients.claim());
});

/**
 * Service Worker installation
 */
self.addEventListener('install', (event) => {
    console.log('[firebase-messaging-sw.js] Service Worker installed');
    self.skipWaiting();
});

/**
 * Handle push event (alternative to onBackgroundMessage)
 */
self.addEventListener('push', (event) => {
    if (event.data) {
        console.log('[firebase-messaging-sw.js] Push event received:', event.data.text());
        
        try {
            const payload = event.data.json();
            
            // Firebase SDK handles this automatically via onBackgroundMessage
            // This is a fallback in case the SDK doesn't handle it
            if (!payload.notification) {
                const notificationTitle = payload.data?.title || 'ResolveIT Notification';
                const notificationOptions = {
                    body: payload.data?.body || 'You have a new notification',
                    icon: '/img/ResolveIT Logo Only without Background.png',
                    badge: '/img/ResolveIT Logo Only without Background.png',
                    data: payload.data
                };
                
                event.waitUntil(
                    self.registration.showNotification(notificationTitle, notificationOptions)
                );
            }
        } catch (error) {
            console.error('[firebase-messaging-sw.js] Error parsing push data:', error);
        }
    }
});

console.log('[firebase-messaging-sw.js] Firebase Messaging Service Worker loaded');
