<?php
/**
 * Cleanup Invalid FCM Tokens
 */
require_once __DIR__ . '/includes/firebase_notifications.php';
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $notificationSender = new FirebaseNotificationSender();
    
    // Get all active tokens
    $stmt = $db->prepare("SELECT id, token, user_id FROM fcm_tokens WHERE is_active = 1");
    $stmt->execute();
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $removed = 0;
    $kept = 0;
    
    foreach ($tokens as $tokenData) {
        $token = $tokenData['token'];
        $tokenId = $tokenData['id'];
        
        // Test token with minimal notification
        $testNotification = [
            'title' => 'Test',
            'body' => 'Token validation',
            'data' => ['type' => 'test']
        ];
        
        $result = $notificationSender->sendNotification($token, $testNotification);
        
        if (!$result['success']) {
            // Check if error indicates invalid token
            $errorCode = '';
            if (isset($result['error']['error']['details'][0]['errorCode'])) {
                $errorCode = $result['error']['error']['details'][0]['errorCode'];
            }
            
            if ($errorCode === 'UNREGISTERED' || 
                (isset($result['error']['error']['code']) && $result['error']['error']['code'] == 404)) {
                
                // Remove invalid token
                $deleteStmt = $db->prepare("DELETE FROM fcm_tokens WHERE id = ?");
                $deleteStmt->execute([$tokenId]);
                $removed++;
            } else {
                $kept++;
            }
        } else {
            $kept++;
        }
        
        // Small delay to avoid rate limiting
        usleep(100000); // 0.1 second
    }
    
    echo json_encode([
        'success' => true,
        'removed' => $removed,
        'kept' => $kept,
        'total_tested' => count($tokens)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>