<?php
/**
 * Get Chat Messages API - CLEAN VERSION
 * Returns all messages for a ticket with proper seen status
 */

session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$ticketId = isset($_GET['ticket_id']) ? intval($_GET['ticket_id']) : 0;

if ($ticketId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];
    
    // Verify access to ticket
    if ($userType === 'employee') {
        $stmt = $conn->prepare("SELECT employee_id FROM tickets WHERE ticket_id = ?");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$ticket || $ticket['employee_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    // Get all messages
    $query = "
        SELECT 
            tr.response_id,
            tr.ticket_id,
            tr.responder_id,
            tr.message,
            tr.is_internal,
            COALESCE(tr.is_seen, 0) as is_seen,
            tr.seen_at,
            COALESCE(tr.user_type, 'it_staff') as user_type,
            tr.created_at,
            CASE 
                WHEN COALESCE(tr.user_type, 'it_staff') = 'it_staff' THEN its.name
                WHEN tr.user_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                ELSE 'Unknown'
            END as sender_name
        FROM ticket_responses tr
        LEFT JOIN it_staff its ON COALESCE(tr.user_type, 'it_staff') = 'it_staff' AND tr.responder_id = its.staff_id
        LEFT JOIN employees e ON tr.user_type = 'employee' AND tr.responder_id = e.id
        WHERE tr.ticket_id = ?
        AND (tr.is_internal = 0 OR ? = 'it_staff')
        ORDER BY tr.created_at ASC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->execute([$ticketId, $userType]);
    $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format messages
    $messages = [];
    foreach ($responses as $response) {
        $responseUserType = $response['user_type'] ?: 'it_staff';
        $isMyMessage = ($responseUserType === $userType && $response['responder_id'] == $userId);
        
        $messages[] = [
            'response_id' => $response['response_id'],
            'message' => $response['message'],
            'sender_name' => $response['sender_name'],
            'user_type' => $responseUserType,
            'is_internal' => (bool)$response['is_internal'],
            'is_seen' => (bool)$response['is_seen'],
            'seen_at' => $response['seen_at'],
            'created_at' => $response['created_at'],
            'formatted_time' => date('g:i A', strtotime($response['created_at'])),
            'is_my_message' => $isMyMessage
        ];
    }
    
    // Mark messages as seen
    if (count($messages) > 0) {
        // Check if columns exist before trying to update
        $checkCol = $conn->query("SHOW COLUMNS FROM ticket_responses LIKE 'is_seen'");
        if ($checkCol->rowCount() > 0) {
            $markSeenQuery = "
                UPDATE ticket_responses 
                SET is_seen = 1, 
                    seen_at = NOW(), 
                    seen_by = ? 
                WHERE ticket_id = ? 
                AND COALESCE(is_seen, 0) = 0
                AND NOT (COALESCE(user_type, 'it_staff') = ? AND responder_id = ?)
            ";
            $stmt = $conn->prepare($markSeenQuery);
            $stmt->execute([$userId, $ticketId, $userType, $userId]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'total' => count($messages)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
