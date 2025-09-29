<?php
/**
 * API endpoint to check for ticket updates for notifications
 */
require_once '../config/database.php';
require_once '../includes/security.php';

session_start();
requireLogin();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Ticket ID required']);
    exit;
}

$ticketId = intval($_GET['id']);
$userId = $_SESSION['user_id'];

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get last check time from session or use current time minus 1 minute
    $sessionKey = "last_check_ticket_{$ticketId}";
    $lastCheck = $_SESSION[$sessionKey] ?? date('Y-m-d H:i:s', time() - 60);
    
    // Check for new responses since last check
    $stmt = $db->prepare("
        SELECT COUNT(*) as new_responses 
        FROM ticket_responses 
        WHERE ticket_id = ? AND created_at > ? AND user_id != ?
    ");
    $stmt->execute([$ticketId, $lastCheck, $userId]);
    $result = $stmt->fetch();
    
    // Check for status changes
    $stmt = $db->prepare("
        SELECT status, updated_at 
        FROM tickets 
        WHERE id = ? AND updated_at > ?
    ");
    $stmt->execute([$ticketId, $lastCheck]);
    $statusUpdate = $stmt->fetch();
    
    $hasUpdates = false;
    $message = '';
    
    if ($result['new_responses'] > 0) {
        $hasUpdates = true;
        $message = $result['new_responses'] == 1 ? 
                   'New response added' : 
                   "{$result['new_responses']} new responses added";
    } elseif ($statusUpdate) {
        $hasUpdates = true;
        $statusText = match($statusUpdate['status']) {
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'closed' => 'Closed',
            'resolved' => 'Closed', // Map resolved to closed
            default => ucfirst($statusUpdate['status'])
        };
        $message = "Status updated to: {$statusText}";
    }
    
    // Update last check time
    $_SESSION[$sessionKey] = date('Y-m-d H:i:s');
    
    echo json_encode([
        'hasUpdates' => $hasUpdates,
        'message' => $message,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>