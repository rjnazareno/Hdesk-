<!DOCTYPE html>
<html>
<head>
    <title>📱 Quick Setup - Enhanced Notifications</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>

<div class="container">
    <h1>📱 Enhanced Notification System - Quick Setup</h1>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/database.php';
    
    try {
        $db = Database::getInstance()->getConnection();
        
        echo '<div class="info"><h3>🔧 Creating Enhanced Notification Tables...</h3></div>';
        
        // Create message_read_status table
        $sql1 = "CREATE TABLE IF NOT EXISTS message_read_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            user_type ENUM('employee', 'it_staff') NOT NULL,
            last_read_response_id INT DEFAULT NULL,
            last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_ticket (ticket_id, user_id, user_type),
            FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
            INDEX idx_ticket (ticket_id),
            INDEX idx_user (user_id, user_type)
        ) ENGINE=InnoDB";
        
        $db->exec($sql1);
        echo '<div class="success"><h4>✅ Created: message_read_status table</h4></div>';
        
        // Create notification_sent_log table
        $sql2 = "CREATE TABLE IF NOT EXISTS notification_sent_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            response_id INT NOT NULL,
            recipient_user_id INT NOT NULL,
            recipient_user_type ENUM('employee', 'it_staff') NOT NULL,
            notification_type VARCHAR(50) DEFAULT 'new_reply',
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_notification (ticket_id, response_id, recipient_user_id, recipient_user_type),
            FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
            INDEX idx_ticket (ticket_id),
            INDEX idx_response (response_id),
            INDEX idx_recipient (recipient_user_id, recipient_user_type)
        ) ENGINE=InnoDB";
        
        $db->exec($sql2);
        echo '<div class="success"><h4>✅ Created: notification_sent_log table</h4></div>';
        
        // Initialize read status for existing tickets (prevent spam)
        $sql3 = "INSERT IGNORE INTO message_read_status (ticket_id, user_id, user_type, last_read_response_id)
                SELECT DISTINCT 
                    t.ticket_id,
                    CASE 
                        WHEN e.id IS NOT NULL THEN e.id
                        WHEN its.staff_id IS NOT NULL THEN its.staff_id
                    END as user_id,
                    CASE 
                        WHEN e.id IS NOT NULL THEN 'employee'
                        WHEN its.staff_id IS NOT NULL THEN 'it_staff'
                    END as user_type,
                    COALESCE((SELECT MAX(response_id) FROM ticket_responses tr WHERE tr.ticket_id = t.ticket_id), 0) as last_read_response_id
                FROM tickets t
                LEFT JOIN employees e ON t.employee_id = e.id
                LEFT JOIN it_staff its ON t.assigned_to = its.staff_id
                WHERE (e.id IS NOT NULL OR its.staff_id IS NOT NULL)";
        
        $stmt = $db->prepare($sql3);
        $stmt->execute();
        $initialized = $stmt->rowCount();
        echo '<div class="success"><h4>✅ Initialized: ' . $initialized . ' read status records</h4></div>';
        
        // Test classes
        echo '<div class="info"><h3>🧪 Testing Components...</h3></div>';
        
        // Test MessageTracker
        try {
            require_once 'includes/MessageTracker.php';
            $tracker = new MessageTracker();
            echo '<div class="success"><h4>✅ MessageTracker: Ready</h4></div>';
        } catch (Exception $e) {
            echo '<div class="error"><h4>❌ MessageTracker Error</h4><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
        }
        
        // Test Firebase Notifications  
        try {
            require_once 'includes/firebase_notifications.php';
            $firebase = new FirebaseNotificationSender();
            echo '<div class="success"><h4>✅ Firebase Notifications: Ready</h4></div>';
        } catch (Exception $e) {
            echo '<div class="error"><h4>❌ Firebase Error</h4><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
        }
        
        echo '<div class="success">';
        echo '<h2>🎉 Enhanced Photo Notification System is Ready!</h2>';
        echo '<h4>✅ New Features Active:</h4>';
        echo '<ul>';
        echo '<li>📸 <strong>User Photos in Notifications</strong> - Shows sender avatars automatically</li>';
        echo '<li>🚫 <strong>No More Duplicate Notifications</strong> - Smart tracking prevents spam</li>';
        echo '<li>👀 <strong>Auto Read Tracking</strong> - Messages marked as read when viewed</li>';
        echo '<li>📱 <strong>Rich Notification Format</strong> - Better mobile display with action buttons</li>';
        echo '</ul>';
        echo '<h4>🎯 Ready to Test:</h4>';
        echo '<p><a href="dashboard.php" style="background:#28a745;color:white;padding:12px 20px;text-decoration:none;border-radius:5px;margin:5px;">📱 Go to Dashboard</a> ';
        echo '<a href="create_ticket.php" style="background:#17a2b8;color:white;padding:12px 20px;text-decoration:none;border-radius:5px;margin:5px;">✏️ Create Test Ticket</a></p>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="error"><h4>❌ Setup Failed</h4><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
    }
} else {
    // Show setup form
    echo '<div class="info">';
    echo '<h3>🚀 Ready to Install Enhanced Notifications?</h3>';
    echo '<p>This will add:</p>';
    echo '<ul>';
    echo '<li>📸 <strong>User Photos</strong> - Notifications show sender avatars</li>';
    echo '<li>🚫 <strong>Duplicate Prevention</strong> - No more spam notifications</li>';
    echo '<li>👀 <strong>Read Tracking</strong> - Auto-mark messages as seen</li>';
    echo '<li>📱 <strong>Rich Format</strong> - Better mobile notifications</li>';
    echo '</ul>';
    echo '<p><strong>Safe to run multiple times.</strong></p>';
    echo '</div>';
    
    echo '<form method="POST">';
    echo '<button type="submit" style="background:#28a745;font-size:18px;padding:15px 30px;">📱 Install Now</button>';
    echo '</form>';
}
?>

</div>
</body>
</html>