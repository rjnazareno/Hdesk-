/**
 * Firebase Messaging Service Worker - Enhanced
 * Handles background push notifications with rich interactions for IT Help Desk
 */

// Import Firebase scripts for service worker
importScripts('https://www.gstatic.com/firebasejs/10.5.0/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.5.0/firebase-messaging-compat.js');

// Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyC7NBIsU2F8vve9eKPTz6d2i7ns0Cwen90",
  authDomain: "rssticket-a8d0a.firebaseapp.com",
  databaseURL: "https://rssticket-a8d0a-default-rtdb.asia-southeast1.firebasedatabase.app",
  projectId: "rssticket-a8d0a",
  storageBucket: "rssticket-a8d0a.firebasestorage.app",
  messagingSenderId: "410726919561",
  appId: "1:410726919561:web:409adc98f34498ee984558"
};

// Initialize Firebase
firebase.initializeApp(firebaseConfig);
const messaging = firebase.messaging();

// Enhanced notification handling based on type
messaging.onBackgroundMessage(function(payload) {
  console.log('🔔 Received background message:', payload);
  
  const notificationData = payload.data || {};
  const notificationType = notificationData.type || 'default';
  
  // Customize notification based on type
  const notificationConfig = getNotificationConfig(notificationType, payload);
  
  return self.registration.showNotification(
    notificationConfig.title, 
    notificationConfig.options
  );
});

function getNotificationConfig(type, payload) {
  const baseTitle = payload.notification?.title || 'IT Help Desk';
  const baseBody = payload.notification?.body || 'New update available';
  const data = payload.data || {};
  
  const configs = {
    'new_reply': {
      title: `💬 ${baseTitle}`,
      options: {
        body: baseBody,
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: `reply-${data.ticket_id}`,
        requireInteraction: true,
        vibrate: [200, 100, 200],
        data: data,
        actions: [
          { action: 'reply', title: '💬 Reply' },
          { action: 'view', title: '👁️ View Ticket' },
          { action: 'dismiss', title: '❌ Dismiss' }
        ]
      }
    },
    
    'status_change': {
      title: `📋 ${baseTitle}`,
      options: {
        body: baseBody,
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: `status-${data.ticket_id}`,
        requireInteraction: false,
        vibrate: [100, 50, 100],
        data: data,
        actions: [
          { action: 'view', title: '👁️ View Details' },
          { action: 'dismiss', title: '✅ OK' }
        ]
      }
    },
    
    'new_ticket': {
      title: `🆕 ${baseTitle}`,
      options: {
        body: baseBody,
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: `new-ticket-${data.ticket_id}`,
        requireInteraction: true,
        vibrate: [300, 200, 300],
        data: data,
        actions: [
          { action: 'assign', title: '👤 Take Ticket' },
          { action: 'view', title: '👁️ View Ticket' },
          { action: 'later', title: '⏰ Later' }
        ]
      }
    },
    
    'default': {
      title: baseTitle,
      options: {
        body: baseBody,
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: 'ithelp-general',
        requireInteraction: false,
        data: data,
        actions: [
          { action: 'view', title: '👁️ View' },
          { action: 'dismiss', title: '❌ Dismiss' }
        ]
      }
    }
  };
  
  return configs[type] || configs['default'];
}

// Enhanced notification click handling
self.addEventListener('notificationclick', function(event) {
  console.log('🔔 Notification clicked:', event);
  
  event.notification.close();
  
  const action = event.action;
  const data = event.notification.data || {};
  const ticketId = data.ticket_id;
  
  // Handle different actions
  switch (action) {
    case 'reply':
      handleReplyAction(ticketId, data);
      break;
      
    case 'assign':
      handleAssignAction(ticketId, data);
      break;
      
    case 'dismiss':
    case 'later':
      // Just close the notification
      return;
      
    case 'view':
    default:
      handleViewAction(ticketId, data);
      break;
  }
});

function handleReplyAction(ticketId, data) {
  const url = ticketId ? `view_ticket.php?id=${ticketId}&focus=reply` : 'dashboard.php';
  openOrFocusWindow(url);
}

function handleViewAction(ticketId, data) {
  const url = data.action_url || 
             (ticketId ? `view_ticket.php?id=${ticketId}` : 'dashboard.php');
  openOrFocusWindow(url);
}

function handleAssignAction(ticketId, data) {
  if (!ticketId) return;
  
  // Show immediate feedback
  self.registration.showNotification('🔄 Assigning Ticket...', {
    body: `Taking ticket #${ticketId}`,
    icon: '/favicon.ico',
    tag: `assign-progress-${ticketId}`,
    requireInteraction: false
  });
  
  // Open the ticket immediately
  openOrFocusWindow(`view_ticket.php?id=${ticketId}&assign=true`);
}

function openOrFocusWindow(url) {
  event.waitUntil(
    clients.matchAll({
      type: 'window',
      includeUncontrolled: true
    }).then(function(clientList) {
      // Try to find existing window with same domain
      for (const client of clientList) {
        if (client.url.includes('IThelp') && 'focus' in client) {
          // Navigate to the desired URL and focus
          return client.focus().then(() => {
            // Try to navigate if possible
            if ('navigate' in client) {
              return client.navigate(url);
            } else {
              // Fallback: send message to client to navigate
              client.postMessage({
                type: 'NAVIGATE',
                url: url
              });
            }
          });
        }
      }
      
      // If no existing window, open a new one
      if (clients.openWindow) {
        return clients.openWindow(url);
      }
    })
  );
}

// Handle messages from the main thread
self.addEventListener('message', function(event) {
  console.log('🔔 SW received message:', event.data);
  
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// Handle notification close events
self.addEventListener('notificationclose', function(event) {
  console.log('🔔 Notification closed:', event.notification.tag);
});

// Service worker lifecycle
self.addEventListener('install', function(event) {
  console.log('🔧 Firebase Service Worker installing...');
  self.skipWaiting();
});

self.addEventListener('activate', function(event) {
  console.log('✅ Firebase Service Worker activated');
  
  event.waitUntil(
    Promise.all([
      self.clients.claim(),
      // Clean up old notifications
      self.registration.getNotifications().then(function(notifications) {
        const now = Date.now();
        const oldNotifications = notifications.filter(notification => {
          // Remove notifications older than 1 hour
          return notification.timestamp && (now - notification.timestamp) > 3600000;
        });
        
        oldNotifications.forEach(notification => notification.close());
        console.log(`🧹 Cleaned up ${oldNotifications.length} old notifications`);
      })
    ])
  );
});

console.log('🔔 Enhanced Firebase Messaging Service Worker loaded');