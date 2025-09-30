/**
 * Firebase Messaging Service Worker - Simple Version for Live Server
 * Upload this file to the ROOT of your domain (not inside IThelp folder)
 * Location: https://ithelp.resourcestaffonline.com/firebase-messaging-sw.js
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

// Handle background messages
messaging.onBackgroundMessage(function(payload) {
  console.log('🔔 Background message received:', payload);
  
  const notificationTitle = payload.notification?.title || 'IT Help Desk';
  const notificationOptions = {
    body: payload.notification?.body || 'New notification',
    icon: 'https://ithelp.resourcestaffonline.com/IThelp/favicon.ico',
    badge: 'https://ithelp.resourcestaffonline.com/IThelp/favicon.ico',
    tag: payload.data?.type || 'general',
    data: payload.data || {},
    requireInteraction: true,
    actions: [
      {
        action: 'view',
        title: '👁️ View',
      },
      {
        action: 'dismiss',
        title: '✖️ Dismiss'
      }
    ]
  };

  return self.registration.showNotification(notificationTitle, notificationOptions);
});

// Handle notification clicks
self.addEventListener('notificationclick', function(event) {
  console.log('🖱️ Notification clicked:', event);
  
  event.notification.close();
  
  if (event.action === 'view' || !event.action) {
    // Open the ticket or relevant page
    const data = event.notification.data || {};
    const url = data.action_url 
      ? `https://ithelp.resourcestaffonline.com/IThelp/${data.action_url}`
      : 'https://ithelp.resourcestaffonline.com/IThelp/dashboard.php';
    
    event.waitUntil(
      clients.openWindow(url)
    );
  }
});

console.log('🔥 Firebase Messaging Service Worker loaded successfully');