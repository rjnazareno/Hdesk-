<?php
/**
 * Test Firebase Notification for Ticket Closure
 * This file tests if Firebase notifications work when a ticket is closed
 */

require_once 'config/database.php';
require_once 'includes/firebase_notifications.php';

// Test Firebase notification system
echo "<h1>🔥 Firebase Notification Test</h1>\n";

try {
    $firebaseNotifier = new FirebaseNotificationSender();
    
    // Test 1: Check if class loads properly
    echo "<h2>✅ Test 1: Firebase class loaded successfully</h2>\n";
    
    // Test 2: Test photo generation
    $testPhoto = $firebaseNotifier->testPhotoGeneration(1, 'Test User');
    echo "<h2>📸 Test 2: Photo generation</h2>\n";
    echo "<p>Generated photo URL: <a href='{$testPhoto}' target='_blank'>{$testPhoto}</a></p>\n";
    echo "<img src='{$testPhoto}' width='50' height='50' style='border-radius: 50%;'>\n";
    
    // Test 3: Test database connection for notifications
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM fcm_tokens WHERE is_active = 1");
    $stmt->execute();
    $tokenCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<h2>📱 Test 3: FCM Token Check</h2>\n";
    echo "<p>Active FCM tokens in database: {$tokenCount}</p>\n";
    
    if ($tokenCount > 0) {
        echo "<p style='color: green;'>✅ FCM tokens found - notifications should work!</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠️ No FCM tokens found - users need to allow notifications in browser first</p>\n";
    }
    
    // Test 4: Check if we have test tickets
    $stmt = $db->prepare("SELECT ticket_id, subject, status, employee_id FROM tickets ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>🎫 Test 4: Available Test Tickets</h2>\n";
    if (empty($tickets)) {
        echo "<p>No tickets found. Create a ticket first to test notifications.</p>\n";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
        echo "<tr><th>Ticket ID</th><th>Subject</th><th>Status</th><th>Employee ID</th><th>Test Action</th></tr>\n";
        foreach ($tickets as $ticket) {
            echo "<tr>\n";
            echo "<td>#{$ticket['ticket_id']}</td>\n";
            echo "<td>" . htmlspecialchars(substr($ticket['subject'], 0, 50)) . "</td>\n";
            echo "<td>{$ticket['status']}</td>\n";
            echo "<td>{$ticket['employee_id']}</td>\n";
            echo "<td><a href='?test_notification={$ticket['ticket_id']}' style='background: #007cba; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Test Close Notification</a></td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Test 5: Send actual test notification if requested
    if (isset($_GET['test_notification'])) {
        $testTicketId = intval($_GET['test_notification']);
        echo "<h2>🚀 Test 5: Sending Test Closure Notification</h2>\n";
        
        $result = $firebaseNotifier->sendTicketClosedNotification($testTicketId, 1, "Test closure notification from test script");
        
        echo "<h3>Notification Result:</h3>\n";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>\n";
        
        if ($result['success']) {
            echo "<p style='color: green; font-weight: bold;'>✅ Firebase notification sent successfully!</p>\n";
        } else {
            echo "<p style='color: red; font-weight: bold;'>❌ Firebase notification failed: " . ($result['error'] ?? 'Unknown error') . "</p>\n";
        }
    }
    
    echo "<hr>\n";
    echo "<h2>🎯 How to Test Full Workflow:</h2>\n";
    echo "<ol>\n";
    echo "<li>1️⃣ Open the main app in browser and allow notifications</li>\n";
    echo "<li>2️⃣ Login as employee and create a test ticket</li>\n";
    echo "<li>3️⃣ Login as IT staff (admin) and go to ticket view</li>\n";
    echo "<li>4️⃣ Change ticket status to 'Closed' using dropdown</li>\n";
    echo "<li>5️⃣ Check if employee receives Firebase notification 🔥</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
    echo "<pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "\n<hr>\n";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>\n";
?>