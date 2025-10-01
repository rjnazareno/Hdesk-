<!DOCTYPE html>
<html>
<head>
    <title>🔍 Notification Debug Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Self-Notification Debug Tool</h1>
        <p><strong>Purpose:</strong> Debug why you're still receiving notifications for your own messages.</p>
        
        <?php
        session_start();
        
        if (isset($_SESSION['user_id'])) {
            echo '<div class="debug-section success">';
            echo '<h3>✅ Session Information</h3>';
            echo '<pre>';
            echo 'User ID: ' . $_SESSION['user_id'] . "\n";
            echo 'User Type: ' . $_SESSION['user_type'] . "\n"; 
            echo 'Username: ' . ($_SESSION['username'] ?? 'Not set') . "\n";
            echo 'User Data Name: ' . ($_SESSION['user_data']['name'] ?? 'Not set') . "\n";
            echo '</pre>';
            echo '</div>';
        } else {
            echo '<div class="debug-section error">';
            echo '<h3>❌ Not Logged In</h3>';
            echo '<p>Please log in first to test notifications.</p>';
            echo '</div>';
        }
        ?>
        
        <div class="debug-section info">
            <h3>🛠️ Recent Fixes Applied</h3>
            <ul>
                <li><strong>Firebase Chat (JavaScript):</strong> Added user ID comparison for self-notification prevention</li>
                <li><strong>Firebase Notifications (PHP):</strong> Enhanced self-notification checks with logging</li>
                <li><strong>Debug Logging:</strong> Added detailed logs to track notification decisions</li>
            </ul>
        </div>
        
        <div class="debug-section warning">
            <h3>⚠️ Testing Instructions</h3>
            <ol>
                <li><strong>Clear Browser Cache:</strong> Clear cache and reload pages to get updated JavaScript</li>
                <li><strong>Check Browser Console:</strong> Open F12 Developer Tools → Console tab</li>
                <li><strong>Send a test message:</strong> Reply to a ticket and watch console logs</li>
                <li><strong>Look for logs like:</strong>
                    <pre>🚫 Skipping self-notification for user: [Your Name] UserID: [Your ID]
DEBUG: Checking self-notification - fromUserId: X, recipientId: Y</pre>
                </li>
                <li><strong>Check server logs:</strong> Look in your XAMPP error logs for debug messages</li>
            </ol>
        </div>
        
        <div class="debug-section info">
            <h3>📋 What to Check</h3>
            <ul>
                <li><strong>Browser notifications vs in-app notifications:</strong> The "💬 New Reply" might be a browser push notification, not an in-app notification</li>
                <li><strong>Multiple tabs:</strong> Close all other tabs of the IT Help system</li>
                <li><strong>Service Worker:</strong> Browser might have cached the old notification service worker</li>
                <li><strong>Firebase tokens:</strong> Multiple devices registered for notifications</li>
            </ul>
        </div>
        
        <div class="debug-section">
            <h3>🧪 Quick Tests</h3>
            <button onclick="testBrowserNotifications()">Test Browser Notification Permission</button>
            <button onclick="testConsoleLogging()">Test Console Logging</button>
            <button onclick="clearServiceWorker()">Clear Service Worker Cache</button>
            
            <div id="testResults" style="margin-top: 15px;"></div>
        </div>
        
        <div class="debug-section error">
            <h3>🚨 If Still Getting Self-Notifications</h3>
            <ol>
                <li><strong>Hard refresh:</strong> Ctrl+F5 or Ctrl+Shift+R</li>
                <li><strong>Incognito mode:</strong> Test in private browsing</li>
                <li><strong>Different browser:</strong> Try Chrome/Firefox/Edge</li>
                <li><strong>Check mobile:</strong> If you have the system open on mobile</li>
                <li><strong>Server restart:</strong> Restart XAMPP Apache service</li>
            </ol>
        </div>
    </div>

    <script>
        function testBrowserNotifications() {
            const results = document.getElementById('testResults');
            
            if (!('Notification' in window)) {
                results.innerHTML = '<div class="error">❌ Browser notifications not supported</div>';
                return;
            }
            
            results.innerHTML = `<div class="info">
                📱 Notification Permission: ${Notification.permission}<br>
                🔔 Service Worker: ${navigator.serviceWorker ? 'Supported' : 'Not supported'}<br>
                🌐 HTTPS: ${location.protocol === 'https:' ? 'Yes' : 'No (required for notifications)'}
            </div>`;
        }
        
        function testConsoleLogging() {
            console.log('🧪 Testing notification debug logging...');
            console.log('User ID from global:', window.USER_ID);
            console.log('User Type from global:', window.CURRENT_USER_TYPE);
            console.log('User Name from global:', window.CURRENT_USER_NAME);
            
            document.getElementById('testResults').innerHTML = '<div class="success">✅ Check browser console (F12) for debug info</div>';
        }
        
        async function clearServiceWorker() {
            if ('serviceWorker' in navigator) {
                try {
                    const registrations = await navigator.serviceWorker.getRegistrations();
                    for (let registration of registrations) {
                        await registration.unregister();
                    }
                    document.getElementById('testResults').innerHTML = '<div class="success">✅ Service workers cleared. Refresh the page.</div>';
                } catch (error) {
                    document.getElementById('testResults').innerHTML = '<div class="error">❌ Error clearing service workers: ' + error.message + '</div>';
                }
            } else {
                document.getElementById('testResults').innerHTML = '<div class="warning">⚠️ Service workers not supported</div>';
            }
        }
    </script>
</body>
</html>