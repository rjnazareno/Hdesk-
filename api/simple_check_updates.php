<?php
/**
 * Simple API endpoint to check for ticket updates - matches dashboard pattern
 */
require_once '../config/database.php';

session_start();

// API-friendly authentication check
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required', 'redirect' => 'simple_login.php']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID required']);
    exit;
}

$ticketId = intval($_GET['id']);
$userId = $_SESSION['user_id'];

try {
    $pdo = getDB(); // Use the same method as dashboard
    
    // Get last check time - use a simple approach
    $sessionKey = "last_check_ticket_{$ticketId}";
    $lastCheck = $_SESSION[$sessionKey] ?? date('Y-m-d H:i:s', time() - 120);
    
    // Simple query to check for new responses
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as new_count
        FROM ticket_responses 
        WHERE ticket_id = ? AND created_at > ? AND responder_id != ?
    ");
    $stmt->execute([$ticketId, $lastCheck, $userId]);
    $result = $stmt->fetch();
    
    $hasUpdates = $result['new_count'] > 0;
    $message = $hasUpdates ? 
               ($result['new_count'] == 1 ? 'New response added' : $result['new_count'] . ' new responses') : 
               'No updates';
    
    // Update session timestamp
    $_SESSION[$sessionKey] = date('Y-m-d H:i:s');
    
    echo json_encode([
        'hasUpdates' => $hasUpdates,
        'message' => $message,
        'timestamp' => time(),
        'debug' => [
            'ticket_id' => $ticketId,
            'user_id' => $userId,
            'last_check' => $lastCheck,
            'new_count' => $result['new_count']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Simple API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'line' => $e->getLine()
    ]);
}
?>