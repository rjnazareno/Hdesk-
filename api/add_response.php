<?php
/**
 * Add Response API - Clean Version
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
    
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    $message = trim($_POST['message'] ?? '');
    $isInternal = isset($_POST['is_internal']) && $_POST['is_internal'] === '1';
    
    // Validate input
    if ($ticketId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
        exit;
    }
    
    if (empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message cannot be empty']);
        exit;
    }
    
    if (strlen($message) > 10000) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message too long']);
        exit;
    }
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Check if ticket exists and user has permission
    $ticketSql = "SELECT employee_id, status FROM tickets WHERE ticket_id = ?";
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
    
    // Don't allow responses to closed tickets
    if ($ticket['status'] === 'closed') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot respond to closed ticket']);
        exit;
    }
    
    // Employees cannot create internal responses
    if ($_SESSION['user_type'] === 'employee' && $isInternal) {
        $isInternal = false;
    }
    
    // Insert response
    $insertSql = "
        INSERT INTO ticket_responses 
        (ticket_id, user_id, user_type, message, is_internal, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ";
    
    $userType = $_SESSION['user_type'] === 'employee' ? 'employee' : 'it_staff';
    
    $stmt = $db->prepare($insertSql);
    $stmt->execute([
        $ticketId,
        $_SESSION['user_id'],
        $userType,
        $message,
        $isInternal ? 1 : 0
    ]);
    
    $responseId = $db->lastInsertId();
    
    // Update ticket's updated_at timestamp and status if needed
    $updateSql = "UPDATE tickets SET updated_at = NOW()";
    
    // If IT staff is responding and ticket is "open", change to "in_progress"
    if ($_SESSION['user_type'] === 'it_staff' && $ticket['status'] === 'open') {
        $updateSql .= ", status = 'in_progress'";
    }
    
    $updateSql .= " WHERE ticket_id = ?";
    $stmt = $db->prepare($updateSql);
    $stmt->execute([$ticketId]);
    
    // Get the actual timestamp from the database
    $stmt = $db->prepare("SELECT created_at FROM ticket_responses WHERE response_id = ?");
    $stmt->execute([$responseId]);
    $timestampResult = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'message' => 'Response added successfully',
        'response_id' => $responseId,
        'timestamp' => $timestampResult['created_at']
    ]);

} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    error_log("Add response API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>