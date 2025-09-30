/**
 * Firebase Configuration for IT Help Desk
 * Real-time messaging and notifications
 */

import { initializeApp } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-app.js";
import { getDatabase } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-database.js";
import { getMessaging } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-messaging.js";

// Your Firebase configuration
const firebaseConfig = {
  apiKey: "AIzaSyC7NBIsU2F8vve9eKPTz6d2i7ns0Cwen90",
  authDomain: "rssticket-a8d0a.firebaseapp.com",
  databaseURL: "https://rssticket-a8d0a-default-rtdb.asia-southeast1.firebasedatabase.app",
  projectId: "rssticket-a8d0a",
  storageBucket: "rssticket-a8d0a.firebasestorage.app",
  messagingSenderId: "410726919561",
  appId: "1:410726919561:web:409adc98f34498ee984558",
  measurementId: "G-2T29LBJG4P"
};

// Initialize Firebase
console.log('🔥 Initializing Firebase...');
const app = initializeApp(firebaseConfig);
const database = getDatabase(app);

// Initialize messaging for push notifications (optional)
let messaging = null;
try {
  if ('serviceWorker' in navigator && 'Notification' in window) {
    // Detect environment and use appropriate service worker
    const isLive = window.location.hostname === 'ithelp.resourcestaffonline.com';
    const swPath = isLive 
      ? '/firebase-messaging-sw.js'  // Root level for live server
      : './firebase-messaging-sw.js'; // Local path for development
    
    // Register service worker with environment-specific path
    navigator.serviceWorker.register(swPath)
      .then((registration) => {
        console.log('✅ Service Worker registered:', registration);
        messaging = getMessaging(app);
        console.log('🔔 Firebase Messaging initialized');
      })
      .catch((error) => {
        console.error('❌ Service Worker registration failed:', error);
        // Try fallback path
        const fallbackPath = isLive ? './firebase-messaging-sw.js' : '/firebase-messaging-sw.js';
        return navigator.serviceWorker.register(fallbackPath);
      })
      .then((registration) => {
        if (registration) {
          console.log('✅ Service Worker registered (fallback):', registration);
          messaging = getMessaging(app);
        }
      })
      .catch((error) => {
        console.error('❌ Service Worker fallback also failed:', error);
      });
  }
} catch (error) {
  console.log('⚠️ Firebase Messaging not supported:', error.message);
}

// Export for use in other modules
export { database, messaging, app };

console.log('✅ Firebase configuration loaded successfully');