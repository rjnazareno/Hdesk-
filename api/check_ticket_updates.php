<?php
/**
 * API endpoint to check for ticket updates for notifications
 */
require_once '../config/database.php';
require_once '../includes/security.php';

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
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get last check time from local storage equivalent or use time minus 2 minutes
    $sessionKey = "last_check_ticket_{$ticketId}";
    $lastCheck = $_SESSION[$sessionKey] ?? date('Y-m-d H:i:s', time() - 120);
    
    // Check for new responses since last check (excluding current user)
    $stmt = $db->prepare("
        SELECT COUNT(*) as new_responses,
               MAX(created_at) as latest_response_time
        FROM ticket_responses 
        WHERE ticket_id = ? AND created_at > ? AND responder_id != ?
    ");
    $stmt->execute([$ticketId, $lastCheck, $userId]);
    $result = $stmt->fetch();
    
    // Also get the latest response details for better messaging
    $stmt2 = $db->prepare("
        SELECT tr.message, tr.responder_id, tr.created_at,
               COALESCE(e.username, i.username, 'Unknown User') as username
        FROM ticket_responses tr
        LEFT JOIN employees e ON tr.responder_id = e.id
        LEFT JOIN it_staff i ON tr.responder_id = i.staff_id
        WHERE tr.ticket_id = ? AND tr.created_at > ? AND tr.responder_id != ?
        ORDER BY tr.created_at DESC
        LIMIT 1
    ");
    $stmt2->execute([$ticketId, $lastCheck, $userId]);
    $latestResponse = $stmt2->fetch();
    
    // Check for status changes (use ticket_id consistently)
    $stmt = $db->prepare("
        SELECT status, updated_at 
        FROM tickets 
        WHERE ticket_id = ? AND updated_at > ?
    ");
    $stmt->execute([$ticketId, $lastCheck]);
    $statusUpdate = $stmt->fetch();
    
    $hasUpdates = false;
    $message = '';
    
    if ($result['new_responses'] > 0) {
        $hasUpdates = true;
        if ($latestResponse) {
            $username = $latestResponse['username'] ?? 'Someone';
            if ($result['new_responses'] == 1) {
                $message = "{$username} added a new response";
            } else {
                $message = "{$result['new_responses']} new responses (latest from {$username})";
            }
        } else {
            $message = $result['new_responses'] == 1 ? 
                       'New response added' : 
                       "{$result['new_responses']} new responses added";
        }
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
    
    // Only update last check time if we're not immediately after posting
    // This prevents the session from being updated too quickly
    $currentTime = date('Y-m-d H:i:s');
    if (!$hasUpdates || (time() - strtotime($lastCheck)) > 5) {
        $_SESSION[$sessionKey] = $currentTime;
    }
    
    echo json_encode([
        'hasUpdates' => $hasUpdates,
        'message' => $message,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    error_log("Check ticket updates error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
}
?>