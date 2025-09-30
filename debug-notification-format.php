<?php
/**
 * Firebase Notification Format Debugger
 */
require_once __DIR__ . '/includes/firebase_notifications.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>🔍 Firebase Notification Debug - Why No Photos?</h2>";

$notificationSender = new FirebaseNotificationSender();
$db = Database::getInstance()->getConnection();

// Get a test token
$stmt = $db->prepare("SELECT token, user_id, user_type FROM fcm_tokens WHERE is_active = 1 LIMIT 1");
$stmt->execute();
$tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$tokenData) {
    echo "<p>❌ No FCM tokens found. <a href='dashboard.php'>Enable notifications first</a></p>";
    exit;
}

echo "<div style='background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";

echo "<h3>🧪 Testing Different Notification Formats</h3>";

$userId = $tokenData['user_id'];
$token = $tokenData['token'];
$photoUrl = $notificationSender->testPhotoGeneration($userId, "Test User {$userId}");

echo "<p><strong>Testing with:</strong></p>";
echo "<ul>";
echo "<li>Token: ..." . substr($token, -20) . "</li>";
echo "<li>User: {$userId}</li>";
echo "<li>Photo: <a href='{$photoUrl}' target='_blank'>View Avatar</a></li>";
echo "</ul>";

// Test 1: Current format (what's failing)
echo "<h4>❌ Test 1: Current Format (Basic Notification)</h4>";
$basicNotification = [
    'title' => '📸 Basic Test',
    'body' => 'This should show as basic notification without photo',
    'icon' => '/favicon.ico',
    'image' => $photoUrl,  // This might not work
    'data' => [
        'type' => 'test',
        'user_id' => (string)$userId
    ]
];

echo "<details style='margin: 10px 0;'>";
echo "<summary>📋 Payload Structure</summary>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;'>";
print_r($basicNotification);
echo "</pre>";
echo "</details>";

// Test 2: Rich format for service worker
echo "<h4>✅ Test 2: Rich Format (Service Worker Processed)</h4>";
$richNotification = [
    // Remove title/body from root - let service worker handle it
    'data' => [
        'type' => 'new_reply',
        'title' => '📸 Rich Photo Test',
        'body' => 'This should show with photo via service worker',
        'image' => $photoUrl,
        'icon' => '/favicon.ico',
        'user_id' => (string)$userId,
        'ticket_id' => '123',
        'action_url' => 'dashboard.php'
    ]
];

echo "<details style='margin: 10px 0;'>";
echo "<summary>📋 Payload Structure</summary>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;'>";
print_r($richNotification);
echo "</pre>";
echo "</details>";

// Test 3: Hybrid format
echo "<h4>🔄 Test 3: Hybrid Format (Both Methods)</h4>";
$hybridNotification = [
    'title' => '📸 Hybrid Test',
    'body' => 'Fallback text if service worker fails',
    'icon' => '/favicon.ico',
    'data' => [
        'type' => 'new_reply',
        'title' => '📸 Rich Photo Test',
        'body' => 'This should show with photo via service worker',
        'image' => $photoUrl,
        'icon' => '/favicon.ico', 
        'user_id' => (string)$userId,
        'ticket_id' => '123',
        'action_url' => 'dashboard.php'
    ],
    // Add webpush specific config
    'webpush' => [
        'notification' => [
            'image' => $photoUrl,
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'requireInteraction' => true,
            'actions' => [
                ['action' => 'view', 'title' => '👁️ View'],
                ['action' => 'reply', 'title' => '💬 Reply']
            ]
        ]
    ]
];

echo "<details style='margin: 10px 0;'>";
echo "<summary>📋 Payload Structure</summary>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto;'>";
print_r($hybridNotification);
echo "</pre>";
echo "</details>";

echo "</div>";

// Send test notifications
echo "<div style='background: white; padding: 20px; margin: 15px 0; border-radius: 8px;'>";
echo "<h3>📱 Send Test Notifications</h3>";

$tests = [
    'Basic Format' => $basicNotification,
    'Rich Format (Data Only)' => $richNotification, 
    'Hybrid Format' => $hybridNotification
];

foreach ($tests as $testName => $notification) {
    echo "<h4>{$testName}</h4>";
    
    $result = $notificationSender->sendNotification($token, $notification);
    
    if ($result['success']) {
        echo "<p style='color: #28a745;'>✅ Sent successfully!</p>";
    } else {
        echo "<p style='color: #dc3545;'>❌ Failed to send</p>";
        echo "<details><summary>Error Details</summary>";
        echo "<pre style='background: #f8f8f8; padding: 10px;'>" . print_r($result, true) . "</pre>";
        echo "</details>";
    }
    
    echo "<p style='font-size: 12px; color: #666;'>Wait 3 seconds between tests...</p>";
    sleep(1); // Prevent spam
}

echo "</div>";

echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; border-left: 4px solid #0D8ABC;'>";
echo "<h3>🔧 Common Issues & Solutions</h3>";
echo "<ul>";
echo "<li><strong>Service worker not at domain root:</strong> Copy firebase-messaging-sw.js to https://ithelp.resourcestaffonline.com/firebase-messaging-sw.js</li>";
echo "<li><strong>Basic notifications only:</strong> Firebase is bypassing service worker - check console errors</li>";
echo "<li><strong>No photos:</strong> Service worker isn't processing notification data properly</li>";
echo "<li><strong>Wrong format:</strong> Need to use data-only notifications for rich display</li>";
echo "</ul>";

echo "<h4>🎯 Next Steps:</h4>";
echo "<ol>";
echo "<li>Check browser console for service worker errors</li>";
echo "<li>Verify service worker is registered at domain root</li>";
echo "<li>Look for notification with photo in system tray</li>";
echo "<li>Try different payload formats above</li>";
echo "</ol>";

echo "<p><a href='javascript:location.reload()' style='background: #0D8ABC; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px;'>🔄 Test Again</a></p>";
echo "</div>";
?>