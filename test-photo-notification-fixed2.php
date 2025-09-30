<?php
/**
 * Fixed Firebase Photo Notification Test
 */
require_once __DIR__ . '/includes/firebase_notifications.php';
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>Firebase Photo Test - Fixed</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .test{background:white;padding:20px;margin:15px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);} .success{color:#28a745;} .error{color:#dc3545;} .info{color:#17a2b8;} pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow-x:auto;}</style>";
echo "</head><body>";

echo "<h1>🖼️ Firebase Photo Notifications - Fixed Test</h1>";

$notificationSender = new FirebaseNotificationSender();
$db = Database::getInstance()->getConnection();

// Get a test token
$stmt = $db->prepare("SELECT token, user_id, user_type FROM fcm_tokens WHERE is_active = 1 LIMIT 1");
$stmt->execute();
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    echo "<div class='test'><p class='error'>❌ No FCM tokens found. <a href='dashboard.php'>Enable notifications first</a></p></div>";
    echo "</body></html>";
    exit;
}

$userId = $tokenData['user_id'];
$token = $tokenData['token'];
$photoUrl = $notificationSender->testPhotoGeneration($userId, "Test User {$userId}");

echo "<div class='test'>";
echo "<h3>🎯 Testing Corrected Photo Notification</h3>";
echo "<div style='display:flex;gap:20px;align-items:center;margin:15px 0;'>";
echo "<img src='{$photoUrl}' style='width:80px;height:80px;border-radius:50%;border:3px solid #0D8ABC;'>";
echo "<div>";
echo "<strong>Target:</strong> User {$userId}<br>";
echo "<strong>Token:</strong> ..." . substr($token, -20) . "<br>";
echo "<strong>Photo:</strong> <a href='{$photoUrl}' target='_blank'>View Avatar</a>";
echo "</div></div>";

// Test with proper format
$photoNotification = [
    'title' => '📸 Photo Notification Test',
    'body' => "Hello User {$userId}! Check out this notification with your photo.",
    'icon' => '/favicon.ico',
    'image' => $photoUrl, // This should show as large image
    'click_action' => 'dashboard.php',
    'requireInteraction' => true,
    'data' => [
        'type' => 'new_reply',
        'user_id' => (string)$userId,
        'ticket_id' => '123',
        'action_url' => 'dashboard.php',
        // Duplicate data for service worker
        'notification_title' => '📸 Photo Notification Test',
        'notification_body' => "Hello User {$userId}! Check out this notification with your photo.",
        'notification_image' => $photoUrl
    ]
];

echo "<h4>📋 Notification Payload:</h4>";
echo "<pre>" . print_r($photoNotification, true) . "</pre>";

echo "<h4>📱 Sending Notification...</h4>";

$result = $notificationSender->sendNotification($token, $photoNotification);

if ($result['success']) {
    echo "<div style='background:#d4edda;padding:15px;border-radius:5px;border-left:4px solid #28a745;'>";
    echo "<h4 class='success'>✅ SUCCESS - Photo Notification Sent!</h4>";
    echo "<p><strong>What to expect:</strong></p>";
    echo "<ul>";
    echo "<li>📱 <strong>Desktop:</strong> Notification should appear in system tray with photo</li>";
    echo "<li>📸 <strong>Photo:</strong> User avatar should be visible as large image</li>";
    echo "<li>🎯 <strong>Actions:</strong> Click should open dashboard</li>";
    echo "<li>⏰ <strong>Timing:</strong> Should appear within 5-10 seconds</li>";
    echo "</ul>";
    echo "<p class='info'>💡 <strong>Tip:</strong> If you only see basic text, the service worker might need to be moved to domain root.</p>";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;border-left:4px solid #dc3545;'>";
    echo "<h4 class='error'>❌ FAILED - Could Not Send Notification</h4>";
    echo "<p><strong>Error Details:</strong></p>";
    echo "<pre>" . print_r($result, true) . "</pre>";
    
    if (isset($result['error']['error']['message'])) {
        $errorMsg = $result['error']['error']['message'];
        if (strpos($errorMsg, 'Invalid value') !== false) {
            echo "<p class='info'>💡 <strong>Fix:</strong> Data format issue - check string conversions</p>";
        } elseif (strpos($errorMsg, 'Requested entity was not found') !== false) {
            echo "<p class='info'>💡 <strong>Fix:</strong> Invalid FCM token - user needs to re-enable notifications</p>";
        }
    }
    echo "</div>";
}

echo "</div>";

// Service Worker Check
echo "<div class='test'>";
echo "<h3>🔧 Service Worker Diagnostics</h3>";
echo "<p>If photos aren't showing, check these:</p>";

echo "<h4>1. Service Worker Location</h4>";
echo "<p>Service worker must be at domain root for full functionality:</p>";
echo "<ul>";
echo "<li>✅ <strong>Correct:</strong> https://ithelp.resourcestaffonline.com/firebase-messaging-sw.js</li>";
echo "<li>❌ <strong>Wrong:</strong> https://ithelp.resourcestaffonline.com/IThelp/firebase-messaging-sw.js</li>";
echo "</ul>";

echo "<h4>2. Browser Console Check</h4>";
echo "<p>Open browser DevTools (F12) → Console and look for:</p>";
echo "<ul>";
echo "<li><code>🔔 Background message received</code> - Service worker got the message</li>";
echo "<li><code>Service worker registration failed</code> - Registration issue</li>";
echo "<li><code>Firebase/messaging</code> errors - Firebase issues</li>";
echo "</ul>";

echo "<h4>3. Network Tab Check</h4>";
echo "<p>In DevTools → Network, look for:</p>";
echo "<ul>";
echo "<li>FCM API calls to <code>fcm.googleapis.com</code></li>";
echo "<li>Service worker file loads (200 status)</li>";
echo "<li>Image loads for your avatar photo</li>";
echo "</ul>";

echo "<h4>🎯 Quick Fixes:</h4>";
echo "<ol>";
echo "<li><strong>Copy service worker to root:</strong> <code>cp firebase-messaging-sw.js ../</code></li>";
echo "<li><strong>Clear browser cache:</strong> Hard reload (Ctrl+Shift+R)</li>";
echo "<li><strong>Re-enable notifications:</strong> Go to dashboard and enable again</li>";
echo "<li><strong>Check permissions:</strong> Browser should allow notifications</li>";
echo "</ol>";

echo "<p style='text-align:center;margin-top:20px;'>";
echo "<a href='javascript:location.reload()' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>🔄 Test Again</a>";
echo "<a href='dashboard.php' style='background:#0D8ABC;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-right:10px;'>🏠 Dashboard</a>";
echo "<a href='check-fcm-tokens.php' style='background:#17a2b8;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>🔍 Check Tokens</a>";
echo "</p>";

echo "</div>";

echo "</body></html>";
?>