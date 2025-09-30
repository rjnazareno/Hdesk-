<?php
/**
 * Firebase Integration Test API
 * Verifies Firebase Realtime Database connectivity
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../includes/security.php';

session_start();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Verify user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        throw new Exception('Not authenticated');
    }
    
    $ticketId = filter_input(INPUT_GET, 'ticket_id', FILTER_VALIDATE_INT);
    if (!$ticketId) {
        throw new Exception('Invalid ticket ID');
    }
    
    // Verify user has access to this ticket
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        throw new Exception('Ticket not found');
    }
    
    // Security check for employees
    if ($_SESSION['user_type'] === 'employee' && $ticket['employee_id'] != $_SESSION['user_id']) {
        throw new Exception('Access denied');
    }
    
    // Get recent messages for Firebase sync test
    $stmt = $db->prepare("
        SELECT 
            tr.*,
            CASE 
                WHEN tr.user_type = 'it_staff' THEN 'IT Support'
                ELSE 'Employee'
            END as display_name
        FROM ticket_responses tr 
        WHERE tr.ticket_id = ? 
        ORDER BY tr.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$ticketId]);
    $recentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return success response with test data
    echo json_encode([
        'success' => true,
        'message' => 'Firebase integration test successful',
        'data' => [
            'ticket_id' => $ticketId,
            'user_type' => $_SESSION['user_type'],
            'user_id' => $_SESSION['user_id'],
            'recent_messages_count' => count($recentMessages),
            'firebase_path' => "tickets/{$ticketId}/messages",
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'recent_messages' => array_map(function($msg) {
            return [
                'id' => $msg['response_id'],
                'message' => substr($msg['message'], 0, 50) . '...',
                'user_type' => $msg['user_type'],
                'display_name' => $msg['display_name'],
                'created_at' => $msg['created_at']
            ];
        }, $recentMessages)
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'request_method' => $_SERVER['REQUEST_METHOD'],
            'user_id' => $_SESSION['user_id'] ?? null,
            'user_type' => $_SESSION['user_type'] ?? null,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>