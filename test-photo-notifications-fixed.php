<?php
/**
 * Fixed Firebase Photo Notifications Test
 */
require_once __DIR__ . '/includes/firebase_notifications.php';
require_once __DIR__ . '/config/database.php';

$notificationSender = new FirebaseNotificationSender();
$db = Database::getInstance()->getConnection();

echo "<!DOCTYPE html>";
echo "<html><head><title>Firebase Photo Notification Tests</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
.test-section { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.success { color: #28a745; } .error { color: #dc3545; } .info { color: #17a2b8; }
.photo-grid { display: flex; gap: 15px; flex-wrap: wrap; margin: 15px 0; }
.photo-item { text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
.avatar { width: 80px; height: 80px; border-radius: 50%; border: 3px solid #0D8ABC; }
.result-box { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #0D8ABC; }
</style></head><body>";

echo "<h1>🖼️ Firebase Photo Notifications - Test Results</h1>";

// Test 1: Photo Generation
echo "<div class='test-section'>";
echo "<h2>Test 1: Photo URL Generation ✅</h2>";
echo "<p class='info'>Testing avatar generation for different users...</p>";

$testUsers = [
    ['id' => 1, 'name' => 'John Doe'],
    ['id' => 2, 'name' => 'Jane Smith'], 
    ['id' => 3, 'name' => 'IT Support'],
    ['id' => 4, 'name' => 'Admin User']
];

echo "<div class='photo-grid'>";
foreach ($testUsers as $user) {
    $photoUrl = $notificationSender->testPhotoGeneration($user['id'], $user['name']);
    echo "<div class='photo-item'>";
    echo "<img src='{$photoUrl}' alt='{$user['name']}' class='avatar'>";
    echo "<br><strong>{$user['name']}</strong>";
    echo "<br><small>ID: {$user['id']}</small>";
    echo "<br><a href='{$photoUrl}' target='_blank' style='font-size: 12px;'>View Full</a>";
    echo "</div>";
}
echo "</div>";
echo "</div>";

// Test 2: FCM Token Check
echo "<div class='test-section'>";
echo "<h2>Test 2: FCM Token Status 🔍</h2>";

$stmt = $db->prepare("SELECT token, user_id, user_type, created_at FROM fcm_tokens WHERE is_active = 1 ORDER BY created_at DESC LIMIT 5");
$stmt->execute();
$tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($tokens) {
    echo "<p class='success'>✅ Found " . count($tokens) . " active FCM token(s)</p>";
    echo "<div style='overflow-x: auto;'>";
    echo "<table style='width: 100%; border-collapse: collapse; margin-top: 10px;'>";
    echo "<tr style='background: #f8f9fa;'>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>User</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>Type</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>Token (Last 20 chars)</th>";
    echo "<th style='padding: 8px; border: 1px solid #ddd;'>Created</th>";
    echo "</tr>";
    
    foreach ($tokens as $token) {
        echo "<tr>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>User {$token['user_id']}</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$token['user_type']}</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd; font-family: monospace;'>..." . substr($token['token'], -20) . "</td>";
        echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$token['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
} else {
    echo "<div class='result-box' style='border-left-color: #ffc107;'>";
    echo "<p class='error'>❌ No FCM tokens found!</p>";
    echo "<p><strong>Action needed:</strong> Enable notifications first</p>";
    echo "<a href='dashboard.php' style='background: #0D8ABC; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>👉 Go to Dashboard</a>";
    echo "</div>";
}
echo "</div>";

// Test 3: Direct Notification Test (if tokens exist)
if ($tokens) {
    echo "<div class='test-section'>";
    echo "<h2>Test 3: Send Photo Notification 📱</h2>";
    
    $testToken = $tokens[0]; // Use first token
    $userId = $testToken['user_id'];
    $userType = $testToken['user_type'];
    $token = $testToken['token'];
    
    $photoUrl = $notificationSender->testPhotoGeneration($userId, "Test User {$userId}");
    
    $notification = [
        'title' => '📸 Photo Test Notification',
        'body' => "Hello User {$userId}! This is a test notification with your avatar photo.",
        'icon' => '/favicon.ico',
        'image' => $photoUrl,
        'click_action' => 'dashboard.php',
        'requireInteraction' => true,
        'data' => [
            'type' => 'photo_test',
            'user_id' => $userId,
            'user_type' => $userType,
            'action_url' => 'dashboard.php'
        ]
    ];
    
    echo "<div style='display: flex; gap: 20px; align-items: center; margin: 15px 0;'>";
    echo "<img src='{$photoUrl}' style='width: 100px; height: 100px; border-radius: 50%; border: 3px solid #0D8ABC;'>";
    echo "<div>";
    echo "<h4>Sending to: User {$userId} ({$userType})</h4>";
    echo "<p><strong>Photo URL:</strong> <a href='{$photoUrl}' target='_blank'>View Avatar</a></p>";
    echo "<p><strong>Token:</strong> ..." . substr($token, -20) . "</p>";
    echo "</div>";
    echo "</div>";
    
    $result = $notificationSender->sendNotification($token, $notification);
    
    echo "<div class='result-box'>";
    if ($result['success']) {
        echo "<h4 class='success'>✅ SUCCESS - Notification Sent!</h4>";
        echo "<p>📱 Check your browser/device for the notification with photo</p>";
        echo "<p><strong>Features tested:</strong></p>";
        echo "<ul>";
        echo "<li>✅ User avatar generation</li>";
        echo "<li>✅ Firebase FCM delivery</li>"; 
        echo "<li>✅ Large image in notification</li>";
        echo "<li>✅ Custom notification data</li>";
        echo "</ul>";
    } else {
        echo "<h4 class='error'>❌ FAILED - Notification Not Sent</h4>";
        echo "<p><strong>Error Details:</strong></p>";
        echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto;'>";
        print_r($result);
        echo "</pre>";
    }
    echo "</div>";
    
    echo "</div>";
}

// Test 4: Database Check
echo "<div class='test-section'>";
echo "<h2>Test 4: Database Status 🗄️</h2>";

$tables = ['fcm_tokens', 'tickets', 'employees', 'it_staff'];
foreach ($tables as $table) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM {$table}");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "<p class='success'>✅ {$table}: {$count} records</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ {$table}: Error - " . $e->getMessage() . "</p>";
    }
}
echo "</div>";

echo "<div class='test-section'>";
echo "<h2>🎯 Next Steps</h2>";
echo "<ul>";
echo "<li><strong>If notifications succeeded:</strong> Check your browser for the photo notification!</li>";
echo "<li><strong>If no FCM tokens:</strong> <a href='dashboard.php'>Enable notifications first</a></li>";
echo "<li><strong>Test real chat:</strong> <a href='view_ticket.php'>Open a ticket and send messages</a></li>";
echo "<li><strong>Check live server:</strong> Deploy and test on production</li>";
echo "</ul>";
echo "<p><a href='check-fcm-tokens.php' style='background: #28a745; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>🔍 Check Tokens</a>";
echo "<a href='dashboard.php' style='background: #0D8ABC; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px;'>🏠 Dashboard</a></p>";
echo "</div>";

echo "</body></html>";
?>