<?php
/**
 * Mark Message as Seen API
 * Handle seen indicators for chat messages
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
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    $responseId = intval($_POST['response_id'] ?? 0);
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    
    if (!$responseId || !$ticketId) {
        throw new Exception('Invalid parameters');
    }
    
    // Create message_seen table if it doesn't exist
    $createTableSql = "
        CREATE TABLE IF NOT EXISTS message_seen (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            response_id INT(11) NOT NULL,
            ticket_id INT(11) NOT NULL,
            seen_by_user_id INT(11) NOT NULL,
            seen_by_user_type ENUM('employee', 'it_staff') NOT NULL,
            seen_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_response_seen (response_id, seen_by_user_id, seen_by_user_type),
            INDEX idx_ticket_seen (ticket_id, seen_by_user_type),
            UNIQUE KEY unique_seen (response_id, seen_by_user_id, seen_by_user_type)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    $db->exec($createTableSql);
    
    // Verify the message exists and user has access
    $stmt = $db->prepare("
        SELECT tr.*, t.employee_id 
        FROM ticket_responses tr
        JOIN tickets t ON tr.ticket_id = t.ticket_id
        WHERE tr.response_id = ? AND tr.ticket_id = ?
    ");
    $stmt->execute([$responseId, $ticketId]);
    $response = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$response) {
        throw new Exception('Message not found');
    }
    
    // Check access permissions
    if ($userType === 'employee' && $response['employee_id'] != $userId) {
        throw new Exception('Access denied');
    }
    
    // Don't mark your own messages as seen by yourself
    if (($userType === 'it_staff' && $response['user_type'] === 'it_staff' && $response['user_id'] == $userId) ||
        ($userType === 'employee' && $response['user_type'] === 'employee' && $response['user_id'] == $userId)) {
        echo json_encode(['success' => true, 'message' => 'Own message, no need to mark as seen']);
        exit;
    }
    
    // Mark message as seen
    $stmt = $db->prepare("
        INSERT INTO message_seen (response_id, ticket_id, seen_by_user_id, seen_by_user_type, seen_at)
        VALUES (?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE seen_at = NOW()
    ");
    $stmt->execute([$responseId, $ticketId, $userId, $userType]);
    
    echo json_encode(['success' => true, 'message' => 'Message marked as seen']);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>