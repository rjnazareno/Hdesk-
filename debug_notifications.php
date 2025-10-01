<?php
/**
 * Debug Notifications - Check for self-notifications
 */
require_once '../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>🔍 Notification Debug Report</h2>\n";
    
    // Check for notifications in the database
    $stmt = $db->prepare("
        SELECT id, user_id, user_type, type, title, message, is_read, created_at 
        FROM notifications 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($notifications)) {
        echo "<p>✅ No in-app notifications found in database.</p>\n";
    } else {
        echo "<h3>📋 Recent In-App Notifications:</h3>\n";
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>ID</th><th>User</th><th>Type</th><th>Title</th><th>Message</th><th>Created</th></tr>\n";
        
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['user_id']} ({$notif['user_type']})</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>" . substr($notif['message'], 0, 50) . "...</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Check FCM tokens to see who might receive Firebase notifications
    $stmt = $db->prepare("
        SELECT user_id, user_type, COUNT(*) as token_count
        FROM fcm_tokens 
        WHERE is_active = 1
        GROUP BY user_id, user_type
    ");
    $stmt->execute();
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>📱 Active Firebase Token Users:</h3>\n";
    if (empty($tokens)) {
        echo "<p>No active FCM tokens found.</p>\n";
    } else {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>User ID</th><th>User Type</th><th>Active Tokens</th></tr>\n";
        
        foreach ($tokens as $token) {
            echo "<tr>";
            echo "<td>{$token['user_id']}</td>";
            echo "<td>{$token['user_type']}</td>";
            echo "<td>{$token['token_count']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Check recent responses to see what might trigger notifications
    $stmt = $db->prepare("
        SELECT response_id, ticket_id, user_id, user_type, 
               LEFT(message, 50) as message_preview, created_at
        FROM ticket_responses 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>💬 Recent Ticket Responses:</h3>\n";
    if (empty($responses)) {
        echo "<p>No recent responses found.</p>\n";
    } else {
        echo "<table border='1' cellpadding='5'>\n";
        echo "<tr><th>Response ID</th><th>Ticket ID</th><th>User ID</th><th>Type</th><th>Message Preview</th><th>Created</th></tr>\n";
        
        foreach ($responses as $response) {
            echo "<tr>";
            echo "<td>{$response['response_id']}</td>";
            echo "<td>{$response['ticket_id']}</td>";
            echo "<td>{$response['user_id']}</td>";
            echo "<td>{$response['user_type']}</td>";
            echo "<td>{$response['message_preview']}...</td>";
            echo "<td>{$response['created_at']}</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    echo "<hr>";
    echo "<p><strong>🛠️ Fix Applied:</strong> Self-notification prevention added to Firebase notification system.</p>";
    echo "<p><strong>📝 Note:</strong> The notification you received was likely a Firebase push notification, not an in-app notification.</p>";
    echo "<p><strong>✅ Solution:</strong> The system now checks if fromUserId == recipientId before sending notifications.</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>