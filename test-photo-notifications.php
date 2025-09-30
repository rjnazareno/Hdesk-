<?php
/**
 * Test Firebase Notifications with Photos
 */
require_once __DIR__ . '/includes/firebase_notifications.php';

// Test photo notification
$notificationSender = new FirebaseNotificationSender();

echo "<h2>🖼️ Testing Firebase Notifications with Photos</h2>";

// Test 1: New Reply Notification with Photo
echo "<h3>Test 1: New Reply with User Photo</h3>";
$result1 = $notificationSender->sendNewReplyNotification(
    123, // ticket ID
    1,   // from user ID (employee)
    'employee', // from user type
    'Hello, I need help with my computer. The screen keeps flickering and I cannot complete my work.'
);

echo "<pre>";
print_r($result1);
echo "</pre>";

// Test 2: Status Change Notification
echo "<h3>Test 2: Status Change with IT Support Photo</h3>";
$result2 = $notificationSender->sendStatusChangeNotification(
    123, // ticket ID
    'in_progress', // new status
    2 // changed by (IT staff ID)
);

echo "<pre>";
print_r($result2);
echo "</pre>";

// Test 3: Manual Notification with Custom Photo
echo "<h3>Test 3: Custom Notification with Specific Photo</h3>";

// Get test tokens
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT token FROM fcm_tokens WHERE is_active = 1 LIMIT 1");
$stmt->execute();
$testToken = $stmt->fetchColumn();

if ($testToken) {
    $customNotification = [
        'title' => '📸 Photo Notification Test',
        'body' => 'This notification includes a custom user photo!',
        'icon' => '/favicon.ico',
        'image' => 'https://ui-avatars.com/api/?name=John+Doe&size=200&background=0D8ABC&color=fff&bold=true',
        'click_action' => 'view_ticket.php?id=123',
        'requireInteraction' => true,
        'data' => [
            'type' => 'photo_test',
            'ticket_id' => '123',
            'action_url' => 'view_ticket.php?id=123'
        ]
    ];
    
    $result3 = $notificationSender->sendNotification($testToken, $customNotification);
    
    echo "<pre>";
    print_r($result3);
    echo "</pre>";
} else {
    echo "<p>❌ No FCM tokens found. Please enable notifications first.</p>";
}

echo "<hr>";
echo "<h3>🎨 Photo Options Available:</h3>";
echo "<ul>";
echo "<li><strong>UI Avatars:</strong> Auto-generated based on user names with custom colors</li>";
echo "<li><strong>Profile Photos:</strong> User uploaded photos (if available)</li>";
echo "<li><strong>Gravatar:</strong> Email-based avatars (optional)</li>";
echo "<li><strong>Custom Images:</strong> Any URL-accessible image</li>";
echo "</ul>";

echo "<h3>📱 Notification Features:</h3>";
echo "<ul>";
echo "<li><strong>Large Image:</strong> Shows user photo prominently</li>";
echo "<li><strong>Rich Actions:</strong> Reply, View, Dismiss buttons</li>";
echo "<li><strong>Smart Icons:</strong> Different emojis per notification type</li>";
echo "<li><strong>Environment Aware:</strong> Adapts URLs for local/live server</li>";
echo "</ul>";

echo "<p><a href='check-fcm-tokens.php'>🔍 Check FCM Tokens</a> | <a href='dashboard.php'>🏠 Back to Dashboard</a></p>";
?>