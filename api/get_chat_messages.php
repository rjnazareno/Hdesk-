<?php
/**
 * Get Chat Messages API
 * Returns formatted chat messages for real-time updates
 */

// Prevent any output before JSON
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(0);
ob_start();

try {
    session_start();
    require_once '../config/database.php';
    
    // Clear buffer and set JSON header
    ob_clean();
    header('Content-Type: application/json');
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    $ticketId = intval($_GET['ticket_id'] ?? 0);
    
    if ($ticketId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
        exit;
    }
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Check if ticket exists and user has permission
    $ticketSql = "SELECT employee_id FROM tickets WHERE ticket_id = ?";
    $stmt = $db->prepare($ticketSql);
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        exit;
    }
    
    // Check permissions
    if ($_SESSION['user_type'] === 'employee' && $ticket['employee_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Get responses with seen status using subquery to avoid duplicates
    $responsesSql = "
        SELECT r.*, 
               (SELECT COUNT(*) FROM message_seen ms 
                WHERE ms.response_id = r.response_id 
                AND ((r.user_type = 'employee' AND ms.seen_by_user_type = 'it_staff') 
                     OR (r.user_type = 'it_staff' AND ms.seen_by_user_type = 'employee'))) > 0 as is_seen
        FROM ticket_responses r
        WHERE r.ticket_id = ?
    ";
    
    // Filter internal responses for employees
    if ($_SESSION['user_type'] === 'employee') {
        $responsesSql .= " AND (r.is_internal = 0 OR r.is_internal IS NULL)";
    }
    
    $responsesSql .= " ORDER BY r.created_at ASC";
    
    $stmt = $db->prepare($responsesSql);
    $stmt->execute([$ticketId]);
    $responses = $stmt->fetchAll();
    
    // Format messages for frontend
    $messages = [];
    foreach ($responses as $response) {
        $formattedTime = date('g:i:s A', strtotime($response['created_at']));
        $messages[] = [
            'response_id' => $response['response_id'],
            'message' => $response['message'],
            'user_type' => $response['user_type'],
            'is_internal' => $response['is_internal'],
            'is_seen' => (bool)$response['is_seen'],
            'created_at' => $response['created_at'],
            'formatted_time' => $formattedTime,
            'debug_original_timestamp' => $response['created_at'],
            'debug_formatted' => $formattedTime
        ];
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    error_log("Get chat messages API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>