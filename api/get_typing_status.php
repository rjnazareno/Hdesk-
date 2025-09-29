<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

// Verify user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $ticketId = filter_input(INPUT_GET, 'ticket_id', FILTER_VALIDATE_INT);
    $currentUserId = $_SESSION['user_id'];
    $currentUserType = $_SESSION['user_type'];
    
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
    
    // Check permissions
    if ($currentUserType === 'employee' && $ticket['employee_id'] != $currentUserId) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    // Check if the typing_status table exists
    $tableCheck = "SHOW TABLES LIKE 'typing_status'";
    $result = $db->query($tableCheck);
    
    if ($result->rowCount() === 0) {
        // Table doesn't exist yet, return no one typing
        echo json_encode([
            'success' => true, 
            'someone_typing' => false,
            'typing_users' => []
        ]);
        exit;
    }
    
    // Clean up old typing status records first (older than 30 seconds)
    $cleanupQuery = "
        UPDATE typing_status 
        SET is_typing = 0 
        WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 SECOND)";
    $db->exec($cleanupQuery);
    
    // Get currently typing users (exclude current user)
    $typingQuery = "
        SELECT ts.user_id, ts.user_type, ts.last_activity,
               CASE 
                   WHEN ts.user_type = 'it_staff' THEN COALESCE(staff.name, 'IT Support')
                   WHEN ts.user_type = 'employee' THEN COALESCE(emp.name, 'Employee')
                   ELSE 'Unknown User'
               END as user_name
        FROM typing_status ts
        LEFT JOIN it_staff staff ON (ts.user_type = 'it_staff' AND ts.user_id = staff.id)
        LEFT JOIN employees emp ON (ts.user_type = 'employee' AND ts.user_id = emp.id)
        WHERE ts.ticket_id = ? 
          AND ts.is_typing = 1
          AND ts.last_activity >= DATE_SUB(NOW(), INTERVAL 30 SECOND)
          AND NOT (ts.user_id = ? AND ts.user_type = ?)
        ORDER BY ts.last_activity DESC";
    
    $stmt = $db->prepare($typingQuery);
    $stmt->execute([$ticketId, $currentUserId, $currentUserType]);
    $typingUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $someoneTyping = count($typingUsers) > 0;
    
    echo json_encode([
        'success' => true,
        'someone_typing' => $someoneTyping,
        'typing_users' => $typingUsers,
        'count' => count($typingUsers)
    ]);
    
} catch (Exception $e) {
    error_log("Get typing status API error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Server error occurred',
        'someone_typing' => false,
        'typing_users' => []
    ]);
}
?>