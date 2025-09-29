<?php
// Clean output buffer to ensure clean JSON response
ob_start();

// Suppress error display for clean JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once '../config/database.php';
session_start();

// Clear any previous output
ob_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Verify user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $ticketId = filter_input(INPUT_GET, 'ticket_id', FILTER_VALIDATE_INT);
    $afterCount = filter_input(INPUT_GET, 'after_count', FILTER_VALIDATE_INT) ?: 0;
    $userType = $_SESSION['user_type'];
    $userId = $_SESSION['user_id'];
    
    if (!$ticketId) {
        echo json_encode(['success' => false, 'error' => 'Invalid ticket ID']);
        exit;
    }
    
    // Verify ticket exists and user has access
    $ticketQuery = "SELECT employee_id FROM tickets WHERE ticket_id = ?";
    $stmt = $db->prepare($ticketQuery);
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        exit;
    }
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        exit;
    }
    
    // Check permissions
    if ($userType === 'employee' && $ticket['employee_id'] != $userId) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    // Get current total response count
    $countQuery = "SELECT COUNT(*) as total FROM ticket_responses WHERE ticket_id = ?";
    
    // Filter internal responses for employees
    if ($userType === 'employee') {
        $countQuery .= " AND (is_internal = 0 OR is_internal IS NULL)";
    }
    
    $stmt = $db->prepare($countQuery);
    $stmt->execute([$ticketId]);
    $currentCount = $stmt->fetch()['total'];
    
    // If no new responses, return early
    if ($currentCount <= $afterCount) {
        echo json_encode([
            'success' => true,
            'new_responses' => [],
            'total_count' => $currentCount
        ]);
        exit;
    }
    
    // Get new responses
    $responsesQuery = "
        SELECT tr.response_id as id, tr.*, tr.created_at,
               CASE 
                   WHEN tr.user_type = 'it_staff' THEN 'IT Support'
                   ELSE 'Employee'
               END as display_name
        FROM ticket_responses tr 
        WHERE tr.ticket_id = ?";
    
    // Filter internal responses for employees
    if ($userType === 'employee') {
        $responsesQuery .= " AND (tr.is_internal = 0 OR tr.is_internal IS NULL)";
    }
    
    $responsesQuery .= " ORDER BY tr.created_at ASC LIMIT " . (int)$afterCount . ", " . (int)($currentCount - $afterCount);
    
    $stmt = $db->prepare($responsesQuery);
    $stmt->execute([$ticketId]);
    $newResponses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format responses for display
    $formattedResponses = [];
    foreach ($newResponses as $response) {
        $formattedResponses[] = [
            'id' => $response['id'],
            'user_type' => $response['user_type'],
            'display_name' => $response['display_name'],
            'message' => htmlspecialchars($response['message']),
            'is_internal' => (bool)$response['is_internal'],
            'created_at' => $response['created_at'],
            'formatted_date' => date('M j, Y \a\t g:i A', strtotime($response['created_at']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'new_responses' => $formattedResponses,
        'total_count' => $currentCount,
        'new_count' => count($formattedResponses)
    ]);
    
} catch (Exception $e) {
    error_log("Get latest responses API error: " . $e->getMessage());
    ob_clean(); // Clear any previous output
    echo json_encode([
        'success' => false, 
        'error' => 'Server error occurred: ' . $e->getMessage(),
        'new_responses' => [],
        'debug' => [
            'ticket_id' => $ticketId ?? 'not set',
            'after_count' => $afterCount ?? 'not set',
            'user_type' => $userType ?? 'not set'
        ]
    ]);
}

// Ensure no additional output after JSON
ob_end_flush();
exit;
?>