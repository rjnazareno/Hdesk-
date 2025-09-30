<?php
/**
 * User Notification Preferences API
 * Manages user preferences for Firebase notifications
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/database.php';
session_start();

// Verify user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Create notification preferences table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS notification_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type ENUM('employee', 'it_staff') NOT NULL,
        preference_key VARCHAR(100) NOT NULL,
        preference_value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_pref (user_id, user_type, preference_key),
        INDEX idx_user (user_id, user_type)
    )";
    
    $db->exec($createTable);
    
    $method = $_SERVER['REQUEST_METHOD'];
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];
    
    if ($method === 'GET') {
        // Get user preferences
        $stmt = $db->prepare("
            SELECT preference_key, preference_value 
            FROM notification_preferences 
            WHERE user_id = ? AND user_type = ?
        ");
        $stmt->execute([$userId, $userType]);
        $prefs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        // Default preferences
        $defaultPrefs = [
            'new_replies' => 'enabled',
            'status_changes' => 'enabled',
            'new_tickets' => 'enabled',
            'assignments' => 'enabled',
            'email_notifications' => 'enabled',
            'browser_notifications' => 'enabled',
            'sound_notifications' => 'enabled',
            'notification_hours_start' => '08:00',
            'notification_hours_end' => '18:00',
            'weekend_notifications' => 'disabled'
        ];
        
        // Merge with user preferences
        $preferences = array_merge($defaultPrefs, $prefs);
        
        echo json_encode([
            'success' => true,
            'preferences' => $preferences
        ]);
        
    } elseif ($method === 'POST') {
        // Update user preferences
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input || !isset($input['preferences'])) {
            echo json_encode(['success' => false, 'error' => 'No preferences provided']);
            exit;
        }
        
        $preferences = $input['preferences'];
        $updated = 0;
        
        // Begin transaction
        $db->beginTransaction();
        
        try {
            $stmt = $db->prepare("
                INSERT INTO notification_preferences (user_id, user_type, preference_key, preference_value)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE preference_value = VALUES(preference_value), updated_at = NOW()
            ");
            
            foreach ($preferences as $key => $value) {
                if (is_string($value) || is_numeric($value)) {
                    $stmt->execute([$userId, $userType, $key, $value]);
                    $updated++;
                }
            }
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Preferences updated successfully',
                'updated_count' => $updated
            ]);
            
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    error_log("Notification preferences error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred',
        'debug' => $e->getMessage()
    ]);
}
?>