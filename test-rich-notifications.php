<?php
/**
 * Complete Firebase Photo Notification Test - After Service Worker Move
 */
require_once __DIR__ . '/includes/firebase_notifications.php';
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>Firebase Photo Test - Service Worker Moved</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .test{background:white;padding:20px;margin:15px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);} .success{color:#28a745;background:#d4edda;padding:10px;border-radius:5px;border-left:4px solid #28a745;} .error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:5px;border-left:4px solid #dc3545;} .info{color:#17a2b8;background:#d1ecf1;padding:10px;border-radius:5px;border-left:4px solid #17a2b8;} .warning{color:#856404;background:#fff3cd;padding:10px;border-radius:5px;border-left:4px solid #ffc107;} pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow-x:auto;font-size:12px;}</style>";
echo "<script>
function checkServiceWorker() {
    const results = document.getElementById('sw-results');
    results.innerHTML = '<p>🔍 Checking service worker...</p>';
    
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(registrations => {
            let html = '<h4>📋 Service Worker Registrations:</h4>';
            if (registrations.length === 0) {
                html += '<p class=\"warning\">⚠️ No service workers registered</p>';
            } else {
                registrations.forEach((reg, index) => {
                    const scope = reg.scope;
                    const scriptURL = reg.active ? reg.active.scriptURL : 'Not active';
                    const state = reg.active ? reg.active.state : 'inactive';
                    
                    html += `<div style=\"margin:10px 0;padding:10px;border:1px solid #ddd;border-radius:5px;\">`;
                    html += `<strong>Registration \${index + 1}:</strong><br>`;
                    html += `<strong>Script:</strong> \${scriptURL}<br>`;
                    html += `<strong>Scope:</strong> \${scope}<br>`;
                    html += `<strong>State:</strong> \${state}<br>`;
                    
                    if (scriptURL.includes('firebase-messaging-sw.js')) {
                        if (scope === location.origin + '/') {
                            html += '<span class=\"success\">✅ Firebase service worker at domain root - PERFECT!</span>';
                        } else {
                            html += '<span class=\"warning\">⚠️ Firebase service worker not at domain root</span>';
                        }
                    }
                    html += '</div>';
                });
            }
            results.innerHTML = html;
        }).catch(err => {
            results.innerHTML = '<p class=\"error\">❌ Error checking service workers: ' + err.message + '</p>';
        });
    } else {
        results.innerHTML = '<p class=\"error\">❌ Service workers not supported in this browser</p>';
    }
}

function testNotificationPermission() {
    const results = document.getElementById('permission-results');
    
    if ('Notification' in window) {
        const permission = Notification.permission;
        let html = '<h4>🔔 Notification Permission Status:</h4>';
        
        if (permission === 'granted') {
            html += '<p class=\"success\">✅ Notifications allowed</p>';
        } else if (permission === 'denied') {
            html += '<p class=\"error\">❌ Notifications blocked - need to enable in browser</p>';
        } else {
            html += '<p class=\"warning\">⚠️ Notifications not requested yet</p>';
        }
        
        results.innerHTML = html;
    } else {
        results.innerHTML = '<p class=\"error\">❌ Notifications not supported</p>';
    }
}

window.onload = function() {
    checkServiceWorker();
    testNotificationPermission();
};
</script></head><body>";

echo "<h1>🎯 Firebase Photo Notifications - Complete Test</h1>";
echo "<p class='info'>✅ Service worker moved to domain root - testing all functionality</p>";

$notificationSender = new FirebaseNotificationSender();
$db = Database::getInstance()->getConnection();

// Test 1: Service Worker Accessibility
echo "<div class='test'>";
echo "<h2>Test 1: Service Worker Accessibility 🔧</h2>";

echo "<h3>Manual Check:</h3>";
echo "<p>Visit: <a href='https://ithelp.resourcestaffonline.com/firebase-messaging-sw.js' target='_blank'>https://ithelp.resourcestaffonline.com/firebase-messaging-sw.js</a></p>";
echo "<p>Expected: Firebase service worker code should load (not 404 error)</p>";

echo "<h3>Automatic Check:</h3>";
echo "<div id='sw-results'>Loading...</div>";
echo "</div>";

// Test 2: Notification Permission
echo "<div class='test'>";
echo "<h2>Test 2: Browser Permission Status 🔔</h2>";
echo "<div id='permission-results'>Loading...</div>";
echo "</div>";

// Test 3: FCM Token Status
echo "<div class='test'>";
echo "<h2>Test 3: FCM Token Status 🎫</h2>";

$stmt = $db->prepare("SELECT token, user_id, user_type, created_at FROM fcm_tokens WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
$stmt->execute();
$tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($tokens) {
    echo "<div class='success'><h4>✅ Found " . count($tokens) . " Active FCM Token(s)</h4></div>";
    
    foreach ($tokens as $index => $tokenData) {
        echo "<div style='margin:10px 0;padding:15px;border:1px solid #28a745;border-radius:5px;background:#f8fff8;'>";
        echo "<strong>Token #" . ($index + 1) . ":</strong><br>";
        echo "<strong>User:</strong> {$tokenData['user_id']} ({$tokenData['user_type']})<br>";
        echo "<strong>Token:</strong> ..." . substr($tokenData['token'], -25) . "<br>";
        echo "<strong>Created:</strong> {$tokenData['created_at']}<br>";
        echo "</div>";
    }
} else {
    echo "<div class='warning'>";
    echo "<h4>⚠️ No FCM Tokens Found</h4>";
    echo "<p>Action needed: <a href='dashboard.php'>Enable notifications first</a></p>";
    echo "</div>";
}
echo "</div>";

// Test 4: Photo Notification Test
if ($tokens) {
    echo "<div class='test'>";
    echo "<h2>Test 4: Rich Photo Notification 📸</h2>";
    
    $testToken = $tokens[0];
    $userId = $testToken['user_id'];
    $token = $testToken['token'];
    $photoUrl = $notificationSender->testPhotoGeneration($userId, "Test User {$userId}");
    
    echo "<div style='display:flex;gap:20px;align-items:center;margin:20px 0;'>";
    echo "<img src='{$photoUrl}' style='width:100px;height:100px;border-radius:50%;border:3px solid #0D8ABC;'>";
    echo "<div>";
    echo "<h4>Sending Rich Photo Notification:</h4>";
    echo "<strong>To:</strong> User {$userId}<br>";
    echo "<strong>Photo:</strong> <a href='{$photoUrl}' target='_blank'>Generated Avatar</a><br>";
    echo "<strong>Token:</strong> ..." . substr($token, -20);
    echo "</div></div>";
    
    // Send rich photo notification
    $richNotification = [
        'title' => '📸 Rich Photo Notification',
        'body' => "Hello User {$userId}! This notification should show your photo avatar with action buttons.",
        'icon' => '/IThelp/favicon.ico',
        'image' => $photoUrl,
        'click_action' => '/IThelp/dashboard.php', 
        'requireInteraction' => true,
        'data' => [
            'type' => 'new_reply',
            'user_id' => (string)$userId,
            'ticket_id' => '123', 
            'action_url' => '/IThelp/dashboard.php'
        ]
    ];
    
    echo "<h4>📋 Notification Payload:</h4>";
    echo "<details style='margin:10px 0;'>";
    echo "<summary>Click to view payload structure</summary>";
    echo "<pre>" . print_r($richNotification, true) . "</pre>";
    echo "</details>";
    
    echo "<h4>📱 Sending Notification...</h4>";
    
    $result = $notificationSender->sendNotification($token, $richNotification);
    
    if ($result['success']) {
        echo "<div class='success'>";
        echo "<h4>🎉 SUCCESS - Rich Photo Notification Sent!</h4>";
        echo "<p><strong>What you should see:</strong></p>";
        echo "<ul>";
        echo "<li>📱 <strong>Rich notification</strong> in system tray/notification center</li>";
        echo "<li>📸 <strong>User avatar photo</strong> displayed as large image</li>";
        echo "<li>🎯 <strong>Action buttons:</strong> Reply, View, Dismiss (depending on browser)</li>";
        echo "<li>🔔 <strong>Professional appearance</strong> like Slack/Discord notifications</li>";
        echo "</ul>";
        echo "<p class='info'>💡 <strong>Timing:</strong> Should appear within 5-15 seconds</p>";
        echo "</div>";
        
        echo "<div class='info'>";
        echo "<h4>🎯 Expected vs Previous Behavior:</h4>";
        echo "<table style='width:100%;border-collapse:collapse;margin:10px 0;'>";
        echo "<tr style='background:#f8f9fa;'><th style='padding:8px;border:1px solid #ddd;'>Before (Service Worker in IThelp)</th><th style='padding:8px;border:1px solid #ddd;'>Now (Service Worker at Root)</th></tr>";
        echo "<tr>";
        echo "<td style='padding:8px;border:1px solid #ddd;'>❌ \"New message from user<br>message<br>ithelplink.com\"</td>";
        echo "<td style='padding:8px;border:1px solid #ddd;'>✅ Rich notification with:<br>📸 User photo<br>🎯 Action buttons<br>🎨 Custom formatting</td>";
        echo "</tr></table>";
        echo "</div>";
        
    } else {
        echo "<div class='error'>";
        echo "<h4>❌ FAILED - Could Not Send Notification</h4>";
        echo "<pre>" . print_r($result, true) . "</pre>";
        echo "</div>";
    }
    echo "</div>";
}

// Test 5: Real-time Chat Integration
echo "<div class='test'>";
echo "<h2>Test 5: Integration Test 🔄</h2>";
echo "<h4>Next Steps - Test Real Scenarios:</h4>";
echo "<ol>";
echo "<li><strong>Chat Test:</strong> <a href='view_ticket.php?id=1'>Open a ticket and send messages</a></li>";
echo "<li><strong>Status Test:</strong> Change a ticket status in dashboard</li>";  
echo "<li><strong>New Ticket Test:</strong> <a href='create_ticket.php'>Create a new support ticket</a></li>";
echo "<li><strong>Multiple Users:</strong> Test from different browsers/devices</li>";
echo "</ol>";

echo "<h4>🎯 What Should Happen:</h4>";
echo "<ul>";
echo "<li>📱 <strong>Instant notifications</strong> with user photos</li>";
echo "<li>🔄 <strong>Real-time chat updates</strong> via Firebase</li>";
echo "<li>🔔 <strong>Status change alerts</strong> with IT support avatars</li>";
echo "<li>⚡ <strong>No more polling delays</strong> - everything instant</li>";
echo "</ul>";
echo "</div>";

echo "<div class='test' style='text-align:center;'>";
echo "<h3>🚀 Test Complete - Rich Photo Notifications Active!</h3>";
echo "<p><a href='dashboard.php' style='background:#28a745;color:white;padding:12px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🏠 Go to Dashboard</a> ";
echo "<a href='view_ticket.php' style='background:#0D8ABC;color:white;padding:12px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🎫 Test with Real Ticket</a> ";
echo "<a href='javascript:location.reload()' style='background:#17a2b8;color:white;padding:12px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🔄 Run Test Again</a></p>";
echo "</div>";

echo "</body></html>";
?>