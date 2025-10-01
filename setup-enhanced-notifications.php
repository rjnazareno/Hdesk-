<!DOCTYPE html>
<html>
<head>
    <title>📱 Install Message Tracking System</title>
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
        pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>

<div class="container">
    <h1>📱 Firebase Photo Notification System - Setup</h1>
    <p>This will install the enhanced notification system with photos and duplicate prevention.</p>

<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance()->getConnection();
        
        echo '<div class="info"><h3>🔧 Installing Enhanced Notification System...</h3></div>';
        
        // Read and execute the setup SQL
        $setupSQL = file_get_contents('setup_message_tracking.sql');
        
        if (!$setupSQL) {
            throw new Exception('Could not read setup_message_tracking.sql file');
        }
        
        // Split by semicolons and execute each statement
        $statements = explode(';', $setupSQL);
        $executedCount = 0;
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) continue;
            
            try {
                $db->exec($statement);
                $executedCount++;
            } catch (Exception $e) {
                // Some statements may fail if tables already exist - that's okay
                if (!strpos($e->getMessage(), 'already exists')) {
                    echo '<div class="warning"><h4>⚠️ Statement Warning</h4><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
                }
            }
        }
        
        echo '<div class="success"><h4>✅ Database Setup Complete</h4><p>Executed ' . $executedCount . ' SQL statements</p></div>';
        
        // Test the new system
        echo '<div class="info"><h3>🧪 Testing New System...</h3></div>';
        
        // Check if tables exist
        $tables = ['message_read_status', 'notification_sent_log'];
        foreach ($tables as $table) {
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $result = $stmt->fetchAll();
            if (count($result) > 0) {
                echo '<div class="success"><h4>✅ Table Created: ' . $table . '</h4></div>';
            } else {
                echo '<div class="error"><h4>❌ Table Missing: ' . $table . '</h4></div>';
            }
        }
        
        // Test MessageTracker class
        try {
            require_once 'includes/MessageTracker.php';
            $tracker = new MessageTracker();
            echo '<div class="success"><h4>✅ MessageTracker Class: Working</h4></div>';
        } catch (Exception $e) {
            echo '<div class="error"><h4>❌ MessageTracker Class Error</h4><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
        }
        
        // Test Firebase notifications class
        try {
            require_once 'includes/firebase_notifications.php';
            $firebase = new FirebaseNotificationSender();
            echo '<div class="success"><h4>✅ Firebase Notifications: Working</h4></div>';
        } catch (Exception $e) {
            echo '<div class="error"><h4>❌ Firebase Notifications Error</h4><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
        }
        
        echo '<div class="success">';
        echo '<h3>🎉 Enhanced Photo Notification System Ready!</h3>';
        echo '<h4>✅ New Features:</h4>';
        echo '<ul>';
        echo '<li>📸 <strong>User Photos in Notifications</strong> - Shows sender\'s avatar</li>';
        echo '<li>🚫 <strong>Duplicate Prevention</strong> - No more spam notifications</li>';
        echo '<li>👀 <strong>Read Tracking</strong> - Marks messages as seen when viewed</li>';
        echo '<li>📊 <strong>Notification Logging</strong> - Tracks all sent notifications</li>';
        echo '</ul>';
        echo '<h4>🎯 Next Steps:</h4>';
        echo '<p><a href="dashboard.php" style="background:#28a745;color:white;padding:12px 20px;text-decoration:none;border-radius:5px;margin:5px;">📱 Test Notifications</a> ';
        echo '<a href="debug-notification-status.php" style="background:#0D8ABC;color:white;padding:12px 20px;text-decoration:none;border-radius:5px;margin:5px;">🔍 Debug Status</a></p>';
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="error"><h4>❌ Installation Failed</h4><p>' . htmlspecialchars($e->getMessage()) . '</p></div>';
    }
} else {
    // Show setup form
    echo '<div class="info">';
    echo '<h3>🚀 Ready to Install?</h3>';
    echo '<p>This will:</p>';
    echo '<ul>';
    echo '<li>📊 Create message tracking tables</li>';
    echo '<li>🚫 Add duplicate notification prevention</li>';
    echo '<li>📸 Enable user photos in notifications</li>';
    echo '<li>👀 Track when messages are read</li>';
    echo '</ul>';
    echo '<p><strong>Note:</strong> This is safe to run multiple times.</p>';
    echo '</div>';
    
    echo '<form method="POST">';
    echo '<button type="submit" style="background:#28a745;font-size:18px;padding:15px 30px;">📱 Install Enhanced Notifications</button>';
    echo '</form>';
}
?>

</div>

</body>
</html>