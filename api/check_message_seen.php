<?php
/**
 * Check if Message is Already Seen API
 * Returns whether a specific message has been seen by the current user
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    if (!$auth->isLoggedIn()) {
        throw new Exception('Authentication required');
    }
    
    $userId = $auth->getUserId();
    $userType = $auth->getUserType();
    $db = Database::getInstance()->getConnection();
    
    // Handle both GET and POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $responseId = intval($input['response_id'] ?? $input['message_id'] ?? 0);
        $ticketId = intval($input['ticket_id'] ?? 0);
    } else {
        $responseId = intval($_GET['response_id'] ?? $_GET['message_id'] ?? 0);
        $ticketId = intval($_GET['ticket_id'] ?? 0);
    }
    
    if (!$responseId || !$ticketId) {
        throw new Exception('Invalid parameters');
    }
    
    // Check if message has been seen by current user
    $stmt = $db->prepare("
        SELECT COUNT(*) as seen_count
        FROM message_seen 
        WHERE response_id = ? 
        AND seen_by_user_id = ? 
        AND seen_by_user_type = ?
    ");
    $stmt->execute([$responseId, $userId, $userType]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $isSeen = $result['seen_count'] > 0;
    
    echo json_encode([
        'success' => true, 
        'seen' => $isSeen,
        'is_seen' => $isSeen,
        'response_id' => $responseId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>