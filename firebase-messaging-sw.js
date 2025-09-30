/**
 * Firebase Messaging Service Worker - Production Ready
 * Automatically detects environment (local vs live) and adjusts paths
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

// Detect environment and set base URL
const isLive = self.location.hostname === 'ithelp.resourcestaffonline.com';
const baseUrl = isLive 
  ? 'https://ithelp.resourcestaffonline.com/IThelp'
  : 'http://localhost/IThelp';
const iconUrl = isLive 
  ? 'https://ithelp.resourcestaffonline.com/IThelp/favicon.ico'
  : '/IThelp/favicon.ico';

// Handle background messages
messaging.onBackgroundMessage(function(payload) {
  console.log('🔔 Background message received:', payload);
  
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
        icon: iconUrl,
        image: payload.notification?.image || payload.data?.image,
        badge: iconUrl,
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
        icon: iconUrl,
        image: payload.notification?.image || payload.data?.image,
        badge: iconUrl,
        tag: `status-${data.ticket_id}`,
        requireInteraction: false,
        vibrate: [100, 50, 100],
        data: data,
        actions: [
          { action: 'view', title: '👁️ View Ticket' },
          { action: 'dismiss', title: '❌ Dismiss' }
        ]
      }
    },
    
    'new_ticket': {
      title: `🎫 ${baseTitle}`,
      options: {
        body: baseBody,
        icon: iconUrl,
        image: payload.notification?.image || payload.data?.image,
        badge: iconUrl,
        tag: `ticket-${data.ticket_id}`,
        requireInteraction: true,
        vibrate: [300, 200, 300],
        data: data,
        actions: [
          { action: 'assign', title: '👤 Assign' },
          { action: 'view', title: '👁️ View Ticket' },
          { action: 'dismiss', title: '❌ Dismiss' }
        ]
      }
    },
    
    'assignment': {
      title: `👤 ${baseTitle}`,
      options: {
        body: baseBody,
        icon: iconUrl,
        image: payload.notification?.image || payload.data?.image,
        badge: iconUrl,
        tag: `assign-${data.ticket_id}`,
        requireInteraction: true,
        vibrate: [150, 100, 150],
        data: data,
        actions: [
          { action: 'accept', title: '✅ Accept' },
          { action: 'view', title: '👁️ View Ticket' },
          { action: 'dismiss', title: '❌ Dismiss' }
        ]
      }
    }
  };
  
  return configs[type] || {
    title: baseTitle,
    options: {
      body: baseBody,
      icon: iconUrl,
      badge: iconUrl,
      tag: 'general',
      data: data,
      actions: [
        { action: 'view', title: '👁️ View' },
        { action: 'dismiss', title: '❌ Dismiss' }
      ]
    }
  };
}

// Handle notification clicks
self.addEventListener('notificationclick', function(event) {
  console.log('🖱️ Notification clicked:', event);
  
  event.notification.close();
  
  const data = event.notification.data || {};
  const action = event.action;
  
  // Handle different actions
  if (action === 'dismiss') {
    return; // Just close the notification
  }
  
  // Determine target URL based on action and data
  let targetUrl = `${baseUrl}/dashboard.php`;
  
  if (action === 'view' || !action) {
    if (data.action_url) {
      targetUrl = `${baseUrl}/${data.action_url}`;
    } else if (data.ticket_id) {
      targetUrl = `${baseUrl}/view_ticket.php?id=${data.ticket_id}`;
    }
  } else if (action === 'reply' && data.ticket_id) {
    targetUrl = `${baseUrl}/view_ticket.php?id=${data.ticket_id}#reply`;
  } else if (action === 'assign' && data.ticket_id) {
    targetUrl = `${baseUrl}/view_ticket.php?id=${data.ticket_id}#assign`;
  } else if (action === 'accept' && data.ticket_id) {
    targetUrl = `${baseUrl}/view_ticket.php?id=${data.ticket_id}#accept`;
  }
  
  // Open the target URL
  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true })
      .then(function(clientList) {
        // Try to focus existing window first
        for (let i = 0; i < clientList.length; i++) {
          const client = clientList[i];
          if (client.url.indexOf(baseUrl) !== -1 && 'focus' in client) {
            client.navigate(targetUrl);
            return client.focus();
          }
        }
        
        // Open new window if no existing window found
        if (clients.openWindow) {
          return clients.openWindow(targetUrl);
        }
      })
  );
});

// Handle service worker updates
self.addEventListener('message', function(event) {
  console.log('📨 Service Worker message:', event.data);
  
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});

// Background sync for offline notification storage
self.addEventListener('sync', function(event) {
  if (event.tag === 'notification-sync') {
    event.waitUntil(syncNotifications());
  }
});

async function syncNotifications() {
  // Sync offline notifications when connection is restored
  console.log('🔄 Syncing offline notifications...');
}

console.log('🔥 Firebase Messaging Service Worker loaded successfully for:', baseUrl);