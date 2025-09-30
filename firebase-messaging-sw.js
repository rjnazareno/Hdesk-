/**
 * Firebase Messaging Service Worker
 * Handles background push notifications
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

// Retrieve Firebase Messaging object
const messaging = firebase.messaging();

// Handle background messages
messaging.onBackgroundMessage(function(payload) {
  console.log('🔔 Received background message:', payload);
  
  const notificationTitle = payload.notification?.title || 'IT Help Desk';
  const notificationOptions = {
    body: payload.notification?.body || 'New message received',
    icon: '/favicon.ico',
    badge: '/favicon.ico',
    tag: 'it-help-desk-message',
    data: {
      url: payload.data?.url || '/'
    },
    actions: [
      {
        action: 'view',
        title: 'View Message'
      },
      {
        action: 'close', 
        title: 'Close'
      }
    ]
  };

  return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification clicks
self.addEventListener('notificationclick', function(event) {
  console.log('🔔 Notification clicked:', event);
  
  event.notification.close();
  
  if (event.action === 'view' || !event.action) {
    // Open or focus the IT Help Desk window
    const urlToOpen = event.notification.data?.url || '/';
    
    event.waitUntil(
      clients.matchAll({
        type: 'window',
        includeUncontrolled: true
      }).then(function(clientList) {
        // Try to find existing IT Help Desk window
        for (let i = 0; i < clientList.length; i++) {
          const client = clientList[i];
          if (client.url.includes('IThelp') && 'focus' in client) {
            return client.focus();
          }
        }
        
        // Open new window if none found
        if (clients.openWindow) {
          return clients.openWindow(urlToOpen);
        }
      })
    );
  }
});

// Handle service worker installation
self.addEventListener('install', function(event) {
  console.log('🔧 Firebase Service Worker installing...');
  self.skipWaiting();
});

self.addEventListener('activate', function(event) {
  console.log('✅ Firebase Service Worker activated');
  event.waitUntil(self.clients.claim());
});

console.log('🔔 Firebase Messaging Service Worker loaded');