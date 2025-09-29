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
    echo json_encode(['error' => 'Authentication required', 'redirect' => 'login.php']);
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
    
    // Get last check time - use a simple approach with better timing
    $sessionKey = "last_check_ticket_{$ticketId}";
    $lastCheck = $_SESSION[$sessionKey] ?? date('Y-m-d H:i:s', time() - 180); // 3 minutes lookback
    
    // Simple query to check for new responses - using correct field name
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as new_count,
               MAX(created_at) as latest_time
        FROM ticket_responses 
        WHERE ticket_id = ? AND created_at > ? AND user_id != ?
    ");
    $stmt->execute([$ticketId, $lastCheck, $userId]);
    $result = $stmt->fetch();
    
    // Also get the username of the person who responded
    $usernameStmt = $pdo->prepare("
        SELECT 
            tr.user_type,
            tr.user_id,
            CASE 
                WHEN tr.user_type = 'employee' THEN COALESCE(e.username, CONCAT(e.fname, ' ', e.lname))
                WHEN tr.user_type = 'it_staff' THEN COALESCE(i.username, i.name)
                ELSE 'Unknown User'
            END as username
        FROM ticket_responses tr
        LEFT JOIN employees e ON tr.user_id = e.id AND tr.user_type = 'employee'
        LEFT JOIN it_staff i ON tr.user_id = i.user_id AND tr.user_type = 'it_staff'
        WHERE tr.ticket_id = ? AND tr.created_at > ? AND tr.user_id != ?
        ORDER BY tr.created_at DESC
        LIMIT 1
    ");
    $usernameStmt->execute([$ticketId, $lastCheck, $userId]);
    $userResult = $usernameStmt->fetch();
    
    $hasUpdates = $result['new_count'] > 0;
    $message = 'No updates';
    
    if ($hasUpdates) {
        $username = $userResult['username'] ?? 'Someone';
        if ($result['new_count'] == 1) {
            $message = $username . ' added a new response';
        } else {
            $message = $result['new_count'] . ' new responses (latest from ' . $username . ')';
        }
    }
    
    // Only update session timestamp if no updates (to avoid missing rapid responses)
    if (!$hasUpdates) {
        $_SESSION[$sessionKey] = date('Y-m-d H:i:s');
    } else {
        // Delay the session update when there are updates
        $_SESSION[$sessionKey] = date('Y-m-d H:i:s', time() - 30);
    }
    
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