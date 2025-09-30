<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔥 Firebase Notification Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <h1 class="text-3xl font-bold mb-6 text-center">🔥 Firebase Notification Test</h1>
            
            <!-- Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-bold text-blue-800 mb-2">🔑 Firebase Config</h3>
                    <div id="configStatus" class="text-sm">⏳ Checking...</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="font-bold text-green-800 mb-2">🔔 Notifications</h3>
                    <div id="permissionStatus" class="text-sm">⏳ Checking...</div>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h3 class="font-bold text-purple-800 mb-2">📱 Service Worker</h3>
                    <div id="swStatus" class="text-sm">⏳ Checking...</div>
                </div>
            </div>
            
            <!-- Test Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <button onclick="testFirebaseConnection()" 
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    🔥 Test Firebase Connection
                </button>
                <button onclick="requestNotificationPermission()" 
                        class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                    🔔 Request Notification Permission
                </button>
                <button onclick="testNotification()" 
                        class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-colors">
                    📨 Send Test Notification
                </button>
                <button onclick="testReplyNotification()" 
                        class="bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition-colors">
                    💬 Test Reply Notification
                </button>
            </div>
            
            <!-- Console Output -->
            <div class="bg-gray-50 border rounded-lg p-4">
                <h3 class="font-bold mb-3">📝 Console Output:</h3>
                <div id="console" class="bg-black text-green-400 p-4 rounded font-mono text-sm h-64 overflow-y-auto">
                    <div>🔥 Firebase Notification Test Ready</div>
                    <div>📍 Checking Firebase configuration...</div>
                </div>
            </div>
        </div>
        
        <!-- Instructions -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
            <h2 class="text-xl font-bold text-yellow-800 mb-4">📋 Setup Instructions:</h2>
            <ol class="list-decimal list-inside space-y-2 text-yellow-700">
                <li><strong>Get VAPID Key:</strong> Firebase Console → Project Settings → Cloud Messaging → Generate key pair</li>
                <li><strong>Update firebase-notifications.js:</strong> Replace 'BLC_your_vapid_key_here' with your VAPID key</li>
                <li><strong>Get Server Key:</strong> Copy Server key from same Cloud Messaging page</li>
                <li><strong>Update firebase_notifications.php:</strong> Replace 'YOUR_FIREBASE_SERVER_KEY_HERE' with server key</li>
                <li><strong>Test:</strong> Click buttons above to verify everything works</li>
            </ol>
        </div>
    </div>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-app.js";
        import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-messaging.js";
        import { getDatabase, ref, push, serverTimestamp } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-database.js";

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

        let app, messaging, database;
        
        // Console logging
        function log(message, type = 'info') {
            const console = document.getElementById('console');
            const colors = {
                success: 'text-green-400',
                error: 'text-red-400',
                warning: 'text-yellow-400',
                info: 'text-blue-400'
            };
            
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = colors[type] || colors.info;
            logEntry.textContent = `[${timestamp}] ${message}`;
            console.appendChild(logEntry);
            console.scrollTop = console.scrollHeight;
        }
        
        // Initialize Firebase
        try {
            app = initializeApp(firebaseConfig);
            database = getDatabase(app);
            
            if ('serviceWorker' in navigator && 'Notification' in window) {
                messaging = getMessaging(app);
            }
            
            document.getElementById('configStatus').innerHTML = '✅ <span class="text-green-600">Connected</span>';
            log('✅ Firebase initialized successfully', 'success');
            
        } catch (error) {
            document.getElementById('configStatus').innerHTML = '❌ <span class="text-red-600">Failed</span>';
            log('❌ Firebase initialization failed: ' + error.message, 'error');
        }
        
        // Check notification permission
        function checkPermission() {
            const permission = Notification.permission;
            const statusEl = document.getElementById('permissionStatus');
            
            switch (permission) {
                case 'granted':
                    statusEl.innerHTML = '✅ <span class="text-green-600">Granted</span>';
                    log('✅ Notification permission granted', 'success');
                    break;
                case 'denied':
                    statusEl.innerHTML = '❌ <span class="text-red-600">Denied</span>';
                    log('❌ Notification permission denied', 'error');
                    break;
                default:
                    statusEl.innerHTML = '⚠️ <span class="text-yellow-600">Not requested</span>';
                    log('⚠️ Notification permission not requested', 'warning');
            }
        }
        
        // Check service worker
        function checkServiceWorker() {
            const statusEl = document.getElementById('swStatus');
            
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.getRegistration('/firebase-messaging-sw.js')
                    .then(registration => {
                        if (registration) {
                            statusEl.innerHTML = '✅ <span class="text-green-600">Active</span>';
                            log('✅ Service Worker registered', 'success');
                        } else {
                            statusEl.innerHTML = '⚠️ <span class="text-yellow-600">Not found</span>';
                            log('⚠️ Service Worker not found', 'warning');
                        }
                    })
                    .catch(error => {
                        statusEl.innerHTML = '❌ <span class="text-red-600">Error</span>';
                        log('❌ Service Worker error: ' + error.message, 'error');
                    });
            } else {
                statusEl.innerHTML = '❌ <span class="text-red-600">Not supported</span>';
                log('❌ Service Worker not supported', 'error');
            }
        }
        
        // Test Firebase connection
        window.testFirebaseConnection = async function() {
            try {
                log('🔥 Testing Firebase connection...', 'info');
                
                const testRef = ref(database, 'test-connection');
                const result = await push(testRef, {
                    test: true,
                    timestamp: serverTimestamp(),
                    message: 'Connection test from notification test page'
                });
                
                log('✅ Firebase connection successful! Key: ' + result.key, 'success');
                
            } catch (error) {
                log('❌ Firebase connection failed: ' + error.message, 'error');
            }
        };
        
        // Request notification permission
        window.requestNotificationPermission = async function() {
            try {
                log('🔔 Requesting notification permission...', 'info');
                
                const permission = await Notification.requestPermission();
                
                if (permission === 'granted') {
                    log('✅ Notification permission granted', 'success');
                    checkPermission();
                    
                    // Try to get FCM token
                    if (messaging) {
                        try {
                            // VAPID key from your Firebase project
                            const vapidKey = 'BPLmZDFhZTTD890E4iVhN1MhlcNY4dBehh7r0BPNZrbqf6_Wfo5j6qvkE0QOXAUfGPh6c2VkDiqt2LhNXJgpsAw';
                            
                            if (vapidKey !== 'BLC_your_vapid_key_here') {
                                const token = await getToken(messaging, { vapidKey });
                                log('🔑 FCM Token received: ' + token.substring(0, 20) + '...', 'success');
                            } else {
                                log('⚠️ VAPID key not configured. Update firebase-notifications.js', 'warning');
                            }
                            
                        } catch (error) {
                            log('❌ FCM Token error: ' + error.message, 'error');
                        }
                    }
                } else {
                    log('❌ Notification permission denied', 'error');
                    checkPermission();
                }
                
            } catch (error) {
                log('❌ Permission request error: ' + error.message, 'error');
            }
        };
        
        // Test notification
        window.testNotification = function() {
            if (Notification.permission === 'granted') {
                log('📨 Sending test notification...', 'info');
                
                const notification = new Notification('🔥 Firebase Test', {
                    body: 'This is a test notification from your IT Help Desk Firebase setup!',
                    icon: '/favicon.ico',
                    tag: 'firebase-test'
                });
                
                notification.onclick = function() {
                    window.focus();
                    notification.close();
                };
                
                log('✅ Test notification sent', 'success');
            } else {
                log('❌ Notifications not permitted. Click "Request Permission" first.', 'error');
            }
        };
        
        // Test reply notification (simulated)
        window.testReplyNotification = function() {
            if (Notification.permission === 'granted') {
                log('💬 Sending test reply notification...', 'info');
                
                const notification = new Notification('💬 New Reply - Ticket #123', {
                    body: 'John Doe replied: "Thanks for your help with the printer issue!"',
                    icon: '/favicon.ico',
                    tag: 'reply-test',
                    requireInteraction: true,
                    actions: [
                        { action: 'reply', title: '💬 Reply' },
                        { action: 'view', title: '👁️ View Ticket' }
                    ]
                });
                
                notification.onclick = function() {
                    log('👆 Notification clicked!', 'info');
                    window.focus();
                    notification.close();
                };
                
                log('✅ Reply notification sent', 'success');
            } else {
                log('❌ Notifications not permitted. Click "Request Permission" first.', 'error');
            }
        };
        
        // Setup message listener
        if (messaging) {
            onMessage(messaging, (payload) => {
                log('📨 Foreground message received: ' + JSON.stringify(payload), 'info');
            });
        }
        
        // Initial checks
        setTimeout(() => {
            checkPermission();
            checkServiceWorker();
        }, 1000);
        
    </script>
</body>
</html>