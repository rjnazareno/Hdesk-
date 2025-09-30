<?php
/**
 * Complete Firebase FCM Test - Proper Implementation
 */

session_start();

// Mock user session for testing (remove this in production)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'employee';
    $_SESSION['fname'] = 'Test';
    $_SESSION['lname'] = 'User';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔥 Real Firebase FCM Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold mb-6 text-center">🔥 Firebase FCM Test (Real Implementation)</h1>
            
            <!-- Status Display -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="font-bold text-blue-800 mb-2">🔑 FCM Status</h3>
                    <div id="fcmStatus" class="text-sm">⏳ Initializing...</div>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="font-bold text-green-800 mb-2">💾 Token Status</h3>
                    <div id="tokenStatus" class="text-sm">⏳ Waiting...</div>
                </div>
            </div>

            <!-- Test Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <button onclick="initializeFCM()" 
                        class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors">
                    🚀 Initialize FCM
                </button>
                <button onclick="testServerNotification()" 
                        class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition-colors">
                    📨 Test Server FCM
                </button>
            </div>

            <!-- Console Output -->
            <div class="bg-gray-50 border rounded-lg p-4">
                <h3 class="font-bold mb-3">📝 Console Output:</h3>
                <div id="console" class="bg-black text-green-400 p-4 rounded font-mono text-sm h-64 overflow-y-auto">
                    <div>🔥 Firebase FCM Test Ready</div>
                    <div>User ID: <?php echo $_SESSION['user_id']; ?> (<?php echo $_SESSION['user_type']; ?>)</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include your Firebase notification system -->
    <script>
        // Set user data for Firebase notifications
        window.USER_ID = <?php echo $_SESSION['user_id']; ?>;
        window.CURRENT_USER_TYPE = '<?php echo $_SESSION['user_type']; ?>';
        window.CURRENT_USER_NAME = '<?php echo $_SESSION['fname'] . ' ' . $_SESSION['lname']; ?>';
    </script>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-app.js";
        import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-messaging.js";

        // Firebase config
        const firebaseConfig = {
            apiKey: "AIzaSyC7NBIsU2F8vve9eKPTz6d2i7ns0Cwen90",
            authDomain: "rssticket-a8d0a.firebaseapp.com",
            databaseURL: "https://rssticket-a8d0a-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "rssticket-a8d0a",
            storageBucket: "rssticket-a8d0a.firebasestorage.app",
            messagingSenderId: "410726919561",
            appId: "1:410726919561:web:409adc98f34498ee984558"
        };

        let app, messaging;
        
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
        window.initializeFCM = async function() {
            try {
                log('🔥 Initializing Firebase...', 'info');
                
                app = initializeApp(firebaseConfig);
                
                if ('serviceWorker' in navigator) {
                    // Register service worker
                    const registration = await navigator.serviceWorker.register('/firebase-messaging-sw.js');
                    log('✅ Service Worker registered', 'success');
                    
                    messaging = getMessaging(app);
                    document.getElementById('fcmStatus').innerHTML = '✅ <span class="text-green-600">Ready</span>';
                    
                    // Request permission
                    const permission = await Notification.requestPermission();
                    
                    if (permission === 'granted') {
                        log('✅ Notification permission granted', 'success');
                        
                        // Get FCM token
                        const vapidKey = 'BPLmZDFhZTTD890E4iVhN1MhlcNY4dBehh7r0BPNZrbqf6_Wfo5j6qvkE0QOXAUfGPh6c2VkDiqt2LhNXJgpsAw';
                        const currentToken = await getToken(messaging, { vapidKey });
                        
                        if (currentToken) {
                            log('🔑 FCM Token: ' + currentToken.substring(0, 20) + '...', 'success');
                            
                            // Save token to server
                            const response = await fetch('api/save_fcm_token.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    token: currentToken
                                })
                            });
                            
                            const result = await response.json();
                            
                            if (result.success) {
                                log('✅ Token saved to database', 'success');
                                document.getElementById('tokenStatus').innerHTML = '✅ <span class="text-green-600">Saved</span>';
                            } else {
                                log('❌ Token save failed: ' + result.error, 'error');
                                document.getElementById('tokenStatus').innerHTML = '❌ <span class="text-red-600">Failed</span>';
                            }
                            
                        } else {
                            log('❌ No FCM token available', 'error');
                        }
                        
                        // Listen for foreground messages
                        onMessage(messaging, (payload) => {
                            log('📨 Foreground message received!', 'success');
                            log('Title: ' + payload.notification.title, 'info');
                            log('Body: ' + payload.notification.body, 'info');
                            
                            // Show custom notification
                            if (Notification.permission === 'granted') {
                                new Notification(payload.notification.title, {
                                    body: payload.notification.body,
                                    icon: payload.notification.icon || '/favicon.ico',
                                    badge: '/favicon.ico'
                                });
                            }
                        });
                        
                    } else {
                        log('❌ Notification permission denied', 'error');
                    }
                    
                } else {
                    log('❌ Service Worker not supported', 'error');
                }
                
            } catch (error) {
                log('❌ Firebase initialization failed: ' + error.message, 'error');
                document.getElementById('fcmStatus').innerHTML = '❌ <span class="text-red-600">Failed</span>';
            }
        };

        // Test server notification
        window.testServerNotification = async function() {
            try {
                log('📨 Testing server-side FCM notification...', 'info');
                
                const response = await fetch('test-firebase-server.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'test_type=new_reply&ticket_id=1&message=This is a real FCM test from server!'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    log('✅ Server test completed', 'success');
                    log('Result: ' + JSON.stringify(result.result), 'info');
                    
                    if (result.result.success) {
                        log('🎉 FCM notification sent successfully!', 'success');
                    } else {
                        log('⚠️ Notification failed: ' + result.result.error, 'warning');
                    }
                } else {
                    log('❌ Server test failed: ' + result.error, 'error');
                }
                
            } catch (error) {
                log('❌ Server test error: ' + error.message, 'error');
            }
        };

        // Auto-initialize
        setTimeout(() => {
            log('🚀 Auto-initializing FCM...', 'info');
            initializeFCM();
        }, 1000);
        
    </script>
</body>
</html>