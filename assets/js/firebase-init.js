/**
 * Firebase Initialization & Push Notification Handler
 * ResolveIT Help Desk - FCM Integration
 */

// Firebase Configuration
const firebaseConfig = {
    apiKey: "AIzaSyD-vvCkZbG7fKwwH4QSVReSEJSBPWAUZ_4",
    authDomain: "resolveit-417da.firebaseapp.com",
    projectId: "resolveit-417da",
    storageBucket: "resolveit-417da.firebasestorage.app",
    messagingSenderId: "14301030590",
    appId: "1:14301030590:web:cf4900b203385add4256ef",
    measurementId: "G-GDC09XTMMP"
};

// Initialize Firebase App
let app;
let messaging;
let analytics;

// Check if Firebase is loaded
if (typeof firebase !== 'undefined') {
    try {
        // Initialize Firebase
        app = firebase.initializeApp(firebaseConfig);
        
        // Initialize Analytics
        if (firebase.analytics) {
            analytics = firebase.analytics(app);
            console.log('✅ Firebase Analytics initialized');
        }
        
        // Initialize Cloud Messaging
        if (firebase.messaging) {
            // Check if browser supports notifications
            if ('Notification' in window && 'serviceWorker' in navigator) {
                // Register service worker first and wait for it to be ready
                navigator.serviceWorker.register('/firebase-messaging-sw.js')
                    .then((registration) => {
                        console.log('✅ Service Worker registered:', registration);
                        
                        // Wait for service worker to be active
                        return navigator.serviceWorker.ready;
                    })
                    .then(() => {
                        console.log('✅ Service Worker is ready');
                        messaging = firebase.messaging(app);
                        console.log('✅ Firebase Cloud Messaging initialized');
                    })
                    .catch((error) => {
                        console.error('❌ Service Worker registration failed:', error);
                    });
            } else {
                console.warn('⚠️ Browser does not support notifications or service workers');
            }
        }
        
        console.log('✅ Firebase initialized successfully');
    } catch (error) {
        console.error('❌ Firebase initialization error:', error);
    }
} else {
    console.error('❌ Firebase SDK not loaded');
}

/**
 * Request notification permission and get FCM token
 */
async function requestNotificationPermission() {
    try {
        // Wait for service worker to be ready
        if ('serviceWorker' in navigator) {
            await navigator.serviceWorker.ready;
        }
        
        // Check if messaging is available
        if (!messaging) {
            console.warn('⚠️ FCM not available');
            return null;
        }
        
        // Request permission
        const permission = await Notification.requestPermission();
        
        if (permission === 'granted') {
            console.log('✅ Notification permission granted');
            
            // Get FCM token
            const token = await messaging.getToken({
                vapidKey: 'BO3LtJTs6d9JzKVhNWIaz6wKbptPkvGfALQa5MLGLEnhB92leeLMO6sNIRbv4RyGpUAB5Zg4pPYyRe8eIoP_UXY'
            });
            
            if (token) {
                console.log('✅ FCM Token:', token);
                
                // Save token to server
                await saveFCMToken(token);
                
                return token;
            } else {
                console.warn('⚠️ No registration token available');
                return null;
            }
        } else if (permission === 'denied') {
            console.warn('⚠️ Notification permission denied');
            return null;
        } else {
            console.log('ℹ️ Notification permission dismissed');
            return null;
        }
    } catch (error) {
        console.error('❌ Error requesting notification permission:', error);
        return null;
    }
}

/**
 * Save FCM token to server
 */
async function saveFCMToken(token) {
    try {
        const response = await fetch('/api/save_fcm_token.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                token: token,
                device_type: 'web',
                browser: navigator.userAgent
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('✅ FCM token saved to server');
        } else {
            console.error('❌ Failed to save FCM token:', data.error);
        }
    } catch (error) {
        console.error('❌ Error saving FCM token:', error);
    }
}

/**
 * Handle foreground messages (when app is open)
 */
if (messaging) {
    messaging.onMessage((payload) => {
        console.log('📨 Foreground message received:', payload);
        
        const notificationTitle = payload.notification.title;
        const notificationOptions = {
            body: payload.notification.body,
            icon: payload.notification.icon || '/img/ResolveIT Logo Only without Background.png',
            badge: '/img/ResolveIT Logo Only without Background.png',
            tag: payload.data?.ticket_id || 'default',
            data: payload.data,
            requireInteraction: true
        };
        
        // Show notification using Service Worker (supports actions)
        if ('serviceWorker' in navigator && Notification.permission === 'granted') {
            navigator.serviceWorker.ready.then(registration => {
                registration.showNotification(notificationTitle, {
                    ...notificationOptions,
                    actions: [
                        {
                            action: 'view',
                            title: 'View Ticket',
                            icon: '/img/ResolveIT Logo Only without Background.png'
                        },
                        {
                            action: 'dismiss',
                            title: 'Dismiss'
                        }
                    ]
                });
            });
        }
        
        // Update notification badge/count in UI
        updateNotificationBadge();
    });
}

/**
 * Update notification badge count in navigation
 */
function updateNotificationBadge() {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        const currentCount = parseInt(badge.textContent) || 0;
        badge.textContent = currentCount + 1;
        badge.classList.remove('hidden');
    }
}

/**
 * Initialize notifications on page load
 */
document.addEventListener('DOMContentLoaded', async function() {
    // Check if user is logged in
    const isLoggedIn = document.body.dataset.userLoggedIn === 'true';
    
    console.log('🔍 DOMContentLoaded - isLoggedIn:', isLoggedIn, 'messaging:', !!messaging);
    
    if (isLoggedIn) {
        // Wait for messaging to be initialized (max 5 seconds)
        let attempts = 0;
        const maxAttempts = 50; // 5 seconds (50 * 100ms)
        
        const waitForMessaging = setInterval(async () => {
            attempts++;
            
            if (messaging) {
                clearInterval(waitForMessaging);
                console.log('✅ Messaging ready, checking permission...');
                
                const permission = Notification.permission;
                console.log('🔔 Current permission:', permission);
                
                if (permission === 'default') {
                    // Show custom permission request UI
                    showNotificationPrompt();
                } else if (permission === 'granted') {
                    // Already granted, get token
                    console.log('✅ Permission already granted, getting token...');
                    await requestNotificationPermission();
                }
            } else if (attempts >= maxAttempts) {
                clearInterval(waitForMessaging);
                console.warn('⚠️ Messaging not initialized after 5 seconds');
            }
        }, 100);
    }
});

/**
 * Show custom notification permission prompt
 */
function showNotificationPrompt() {
    const promptHTML = `
        <div id="notification-prompt" class="fixed top-4 right-4 z-50 bg-slate-800/95 backdrop-blur-md border border-cyan-500/50 rounded-lg p-4 shadow-xl max-w-sm animate-slide-in">
            <div class="flex items-start space-x-3">
                <div class="flex-shrink-0">
                    <i class="fas fa-bell text-cyan-400 text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-white font-semibold mb-1">Enable Notifications?</h3>
                    <p class="text-sm text-slate-300 mb-3">Get instant alerts when your tickets are updated</p>
                    <div class="flex space-x-2">
                        <button onclick="enableNotifications()" class="flex-1 px-3 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white text-sm rounded hover:from-cyan-600 hover:to-blue-700 transition">
                            <i class="fas fa-check mr-1"></i> Enable
                        </button>
                        <button onclick="dismissNotificationPrompt()" class="px-3 py-2 bg-slate-700 text-slate-300 text-sm rounded hover:bg-slate-600 transition">
                            Later
                        </button>
                    </div>
                </div>
                <button onclick="dismissNotificationPrompt()" class="flex-shrink-0 text-slate-400 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', promptHTML);
}

/**
 * Enable notifications (called from prompt button)
 */
async function enableNotifications() {
    dismissNotificationPrompt();
    await requestNotificationPermission();
}

/**
 * Dismiss notification prompt
 */
function dismissNotificationPrompt() {
    const prompt = document.getElementById('notification-prompt');
    if (prompt) {
        prompt.classList.add('animate-slide-out');
        setTimeout(() => prompt.remove(), 300);
    }
}

// Add CSS animation
const style = document.createElement('style');
style.textContent = `
    @keyframes slide-in {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slide-out {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
    
    .animate-slide-in {
        animation: slide-in 0.3s ease-out;
    }
    
    .animate-slide-out {
        animation: slide-out 0.3s ease-in;
    }
`;
document.head.appendChild(style);

// Export for use in other scripts
window.ResolveITNotifications = {
    requestPermission: requestNotificationPermission,
    messaging: messaging,
    app: app
};
