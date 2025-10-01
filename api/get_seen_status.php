<?php
/**
 * Get Seen Status API
 * Retrieve seen status for messages in a ticket
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
    
    $ticketId = intval($_GET['ticket_id'] ?? 0);
    
    if (!$ticketId) {
        throw new Exception('Invalid ticket ID');
    }
    
    // Verify user has access to this ticket
    $stmt = $db->prepare("SELECT employee_id FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        throw new Exception('Ticket not found');
    }
    
    if ($userType === 'employee' && $ticket['employee_id'] != $userId) {
        throw new Exception('Access denied');
    }
    
    // Get seen status for messages in this ticket
    // Only get messages that were sent by the current user (to show if others have seen them)
    $stmt = $db->prepare("
        SELECT ms.response_id, ms.seen_at, ms.seen_by_user_type,
               CASE 
                   WHEN ms.seen_by_user_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                   WHEN ms.seen_by_user_type = 'it_staff' THEN its.name
               END as seen_by_name
        FROM message_seen ms
        LEFT JOIN employees e ON ms.seen_by_user_id = e.id AND ms.seen_by_user_type = 'employee'
        LEFT JOIN it_staff its ON ms.seen_by_user_id = its.staff_id AND ms.seen_by_user_type = 'it_staff'
        JOIN ticket_responses tr ON ms.response_id = tr.response_id
        WHERE ms.ticket_id = ? 
        AND tr.user_id = ? 
        AND tr.user_type = ?
        AND ms.seen_by_user_id != ?
        ORDER BY ms.seen_at DESC
    ");
    $stmt->execute([$ticketId, $userId, $userType, $userId]);
    $seenMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'seen_messages' => $seenMessages
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>