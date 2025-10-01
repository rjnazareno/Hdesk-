<?php
/**
 * Mark All Messages as Seen API
 * Marks all messages in a ticket as seen when user opens the ticket
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
    
    // Handle both JSON and form data
    $input = json_decode(file_get_contents('php://input'), true);
    $ticketId = intval($input['ticket_id'] ?? $_POST['ticket_id'] ?? 0);
    
    if (!$ticketId) {
        throw new Exception('Invalid ticket ID');
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
    
    // Verify user has access to this ticket
    $stmt = $db->prepare("SELECT employee_id FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        throw new Exception('Ticket not found');
    }
    
    // Check access permissions
    if ($userType === 'employee' && $ticket['employee_id'] != $userId) {
        throw new Exception('Access denied');
    }
    
    // Get all responses in this ticket that are not from the current user
    $stmt = $db->prepare("
        SELECT response_id 
        FROM ticket_responses 
        WHERE ticket_id = ? 
        AND NOT (user_type = ? AND user_id = ?)
        AND is_internal = 0
    ");
    $stmt->execute([$ticketId, $userType, $userId]);
    $responses = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $markedCount = 0;
    
    // Mark all messages from other users as seen
    foreach ($responses as $responseId) {
        $stmt = $db->prepare("
            INSERT IGNORE INTO message_seen (response_id, ticket_id, seen_by_user_id, seen_by_user_type, seen_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        if ($stmt->execute([$responseId, $ticketId, $userId, $userType])) {
            if ($stmt->rowCount() > 0) {
                $markedCount++;
            }
        }
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Marked {$markedCount} messages as seen",
        'marked_count' => $markedCount
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>