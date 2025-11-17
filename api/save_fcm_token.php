<?php
/**
 * Save FCM Token API Endpoint
 * Stores Firebase Cloud Messaging token for push notifications
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

header('Content-Type: application/json');

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

// Get request data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['token'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'FCM token is required'
    ]);
    exit;
}

$fcmToken = trim($data['token']);
$deviceType = $data['device_type'] ?? 'web';
$browser = $data['browser'] ?? '';

// Validate token
if (empty($fcmToken)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid FCM token'
    ]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get current user info
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];
    
    // Determine which table to update
    if ($userType === 'employee') {
        $table = 'employees';
    } else {
        $table = 'users';
    }
    
    // Check if fcm_token column exists
    $checkColumn = $db->query("SHOW COLUMNS FROM `{$table}` LIKE 'fcm_token'");
    
    if ($checkColumn->rowCount() === 0) {
        // Column doesn't exist, create it
        $alterSql = "ALTER TABLE `{$table}` ADD COLUMN `fcm_token` VARCHAR(255) NULL AFTER `profile_picture`";
        $db->exec($alterSql);
    }
    
    // Update FCM token
    $sql = "UPDATE `{$table}` 
            SET `fcm_token` = :token,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = :user_id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':token' => $fcmToken,
        ':user_id' => $userId
    ]);
    
    // Log the token save (optional)
    error_log("FCM Token saved for {$userType} ID {$userId}: " . substr($fcmToken, 0, 20) . "...");
    
    echo json_encode([
        'success' => true,
        'message' => 'FCM token saved successfully',
        'user_type' => $userType,
        'device_type' => $deviceType
    ]);
    
} catch (PDOException $e) {
    error_log("FCM Token Save Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred'
    ]);
}
