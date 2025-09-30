// Simple test service worker
console.log('🔥 Service Worker Test - Loading from correct location!');

// Test if this file is accessible
self.addEventListener('install', function(event) {
  console.log('✅ Service Worker installed successfully!');
});

self.addEventListener('activate', function(event) {
  console.log('✅ Service Worker activated!');
});

// This is just a test - replace with your full firebase-messaging-sw.js once path is confirmed