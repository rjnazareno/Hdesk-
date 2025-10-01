<?php
/**
 * Save Firebase Cloud Messaging Token
 * Stores user FCM tokens for push notifications
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/database.php';
session_start();

// Verify user is logged in (allow manual testing)
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    // Check if this is a manual test
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['manual_user_id']) && isset($input['manual_user_type'])) {
        // Create fake session for testing
        $_SESSION['user_id'] = intval($input['manual_user_id']);
        $_SESSION['user_type'] = $input['manual_user_type'];
    } else {
        echo json_encode(['success' => false, 'error' => 'Not authenticated - no session found']);
        exit;
    }
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['token'])) {
        echo json_encode(['success' => false, 'error' => 'No token provided']);
        exit;
    }
    
    $token = trim($input['token']);
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];
    
    if (empty($token)) {
        echo json_encode(['success' => false, 'error' => 'Empty token']);
        exit;
    }
    
    // Create FCM tokens table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS fcm_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type ENUM('employee', 'it_staff') NOT NULL,
        token TEXT NOT NULL,
        device_info VARCHAR(255) DEFAULT NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_token (user_id, user_type, token(100)),
        INDEX idx_user_type_active (user_type, is_active),
        INDEX idx_user_id_type (user_id, user_type)
    )";
    
    $db->exec($createTable);
    
    // Insert or update token
    $sql = "INSERT INTO fcm_tokens (user_id, user_type, token) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE 
            updated_at = NOW(), 
            is_active = 1";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([$userId, $userType, $token]);
    
    if ($result) {
        // Clean up old tokens for this user (keep only the latest 3)
        $cleanup = "DELETE FROM fcm_tokens 
                   WHERE user_id = ? AND user_type = ? 
                   AND id NOT IN (
                       SELECT * FROM (
                           SELECT id FROM fcm_tokens 
                           WHERE user_id = ? AND user_type = ? 
                           ORDER BY updated_at DESC 
                           LIMIT 3
                       ) AS keep_tokens
                   )";
        
        $cleanupStmt = $db->prepare($cleanup);
        $cleanupStmt->execute([$userId, $userType, $userId, $userType]);
        
        echo json_encode([
            'success' => true,
            'message' => 'FCM token saved successfully',
            'user_id' => $userId,
            'user_type' => $userType
        ]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save token']);
    }
    
} catch (Exception $e) {
    error_log("FCM Token Save Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Server error occurred',

    ]);
}
?>