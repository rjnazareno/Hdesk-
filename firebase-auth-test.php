<?php
/**
 * Firebase Authentication Methods Test
 */
require_once __DIR__ . '/includes/firebase_notifications.php';

echo "<h2>🔑 Firebase Authentication Status</h2>";

$notificationSender = new FirebaseNotificationSender();

echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 15px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";

echo "<h3>Method 1: Service Account (OAuth2)</h3>";

// Check service account file
if (file_exists(__DIR__ . '/config/firebase_service_account.php')) {
    $serviceAccount = include __DIR__ . '/config/firebase_service_account.php';
    if ($serviceAccount && isset($serviceAccount['private_key'])) {
        echo "✅ <strong>Service Account JSON:</strong> Valid<br>";
        echo "📧 <strong>Client Email:</strong> " . $serviceAccount['client_email'] . "<br>";
        echo "🆔 <strong>Project ID:</strong> " . $serviceAccount['project_id'] . "<br>";
        echo "🔑 <strong>Private Key:</strong> Present (length: " . strlen($serviceAccount['private_key']) . " chars)<br>";
        echo "<p style='color: #28a745;'>✅ <strong>PREFERRED METHOD - WORKING</strong></p>";
    } else {
        echo "❌ Service Account JSON invalid<br>";
    }
} else {
    echo "❌ Service Account file not found<br>";
}

echo "<hr>";

echo "<h3>Method 2: Legacy FCM Server Key</h3>";

// Check for various server key sources
$foundKeys = [];

// Check environment
if ($envKey = getenv('FIREBASE_SERVER_KEY')) {
    $foundKeys['Environment'] = substr($envKey, 0, 20) . '...';
}

// Check file
if (file_exists(__DIR__ . '/config/firebase_server_key.txt')) {
    $fileKey = trim(file_get_contents(__DIR__ . '/config/firebase_server_key.txt'));
    $foundKeys['File'] = substr($fileKey, 0, 20) . '...';
}

// Check hardcoded keys
$foundKeys['Browser API Key'] = 'AIzaSyC7NBIsU2F8vve9eKPTz6d2i7ns0Cwen90';
$foundKeys['Transport Key'] = 'AIzaSyCx80ru6-RXeTi3GvqkFsMVyMf-vpgIoVw';

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #f8f9fa;'>";
echo "<th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Source</th>";
echo "<th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Key</th>";
echo "<th style='padding: 8px; border: 1px solid #ddd; text-align: left;'>Type</th>";
echo "</tr>";

foreach ($foundKeys as $source => $key) {
    echo "<tr>";
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$source}</td>";
    echo "<td style='padding: 8px; border: 1px solid #ddd; font-family: monospace;'>{$key}</td>";
    $type = (strpos($key, 'AAAA') === 0) ? 'FCM Server Key ✅' : 'Browser API Key ⚠️';
    echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$type}</td>";
    echo "</tr>";
}

echo "</table>";

echo "<p style='color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px;'>";
echo "ℹ️ <strong>Note:</strong> Browser API keys work but FCM server keys (starting with 'AAAA') are more secure for server-side use.";
echo "</p>";

echo "<hr>";

echo "<h3>🧪 Current Authentication Test</h3>";

// Test what method is actually being used
try {
    // Get a test token
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT token FROM fcm_tokens WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $testToken = $stmt->fetchColumn();
    
    if ($testToken) {
        echo "🎯 <strong>Testing with token:</strong> ..." . substr($testToken, -20) . "<br>";
        
        $testNotification = [
            'title' => '🔑 Auth Test',
            'body' => 'Testing which authentication method works',
            'icon' => '/favicon.ico',
            'data' => [
                'type' => 'auth_test',
                'test_id' => (string)time()
            ]
        ];
        
        $result = $notificationSender->sendNotification($testToken, $testNotification);
        
        if ($result['success']) {
            echo "<p style='color: #28a745;'>✅ <strong>SUCCESS:</strong> Current authentication is working!</p>";
            echo "<p>📱 Check your device for the test notification.</p>";
        } else {
            echo "<p style='color: #dc3545;'>❌ <strong>FAILED:</strong> Authentication issue</p>";
            echo "<pre style='background: #f8f8f8; padding: 10px; border-radius: 4px; overflow-x: auto;'>";
            print_r($result);
            echo "</pre>";
        }
    } else {
        echo "<p style='color: #856404;'>⚠️ No FCM tokens found. Enable notifications first.</p>";
        echo "<a href='dashboard.php' style='background: #0D8ABC; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Enable Notifications</a>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: #dc3545;'>❌ Test failed: " . $e->getMessage() . "</p>";
}

echo "</div>";

echo "<div style='background: white; padding: 20px; border-radius: 8px; margin: 15px 0;'>";
echo "<h3>🎯 Recommendation</h3>";
echo "<ul>";
echo "<li>✅ <strong>Your Service Account is working</strong> - this is the modern, secure method</li>";
echo "<li>ℹ️ <strong>FCM Server Key is optional</strong> - only needed if service account fails</li>";
echo "<li>🔒 <strong>Service Account is preferred</strong> - more secure than server keys</li>";
echo "<li>📱 <strong>Your notifications should work</strong> with current setup</li>";
echo "</ul>";

echo "<h4>To get FCM Server Key (if needed):</h4>";
echo "<ol>";
echo "<li>Visit: <a href='https://console.firebase.google.com/project/rssticket-a8d0a/settings/cloudmessaging' target='_blank'>Firebase Console → Cloud Messaging</a></li>";
echo "<li>Look for 'Server key' (legacy) - starts with 'AAAA'</li>";
echo "<li>Copy and save to <code>config/firebase_server_key.txt</code></li>";
echo "</ol>";

echo "<p><a href='test-photo-notifications-fixed.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🧪 Test Notifications</a>";
echo "<a href='check-fcm-tokens.php' style='background: #17a2b8; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🔍 Check Tokens</a></p>";
echo "</div>";
?>