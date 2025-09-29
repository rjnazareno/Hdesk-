<?php
/**
 * Simulate adding a response for notification testing
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
$username = $_SESSION['username'] ?? 'Test User';

// Option to simulate different user
$simulateOtherUser = isset($_GET['other_user']) && $_GET['other_user'] === '1';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Add a test response
    $stmt = $db->prepare("
        INSERT INTO ticket_responses (ticket_id, user_id, user_type, message, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    if ($simulateOtherUser) {
        // Simulate response from a different user
        $testUserId = ($userId == 1) ? 2 : 1; // Use different ID
        $testUserType = ($_SESSION['user_type'] === 'employee') ? 'it_staff' : 'employee';
        $testMessage = "Test response from OTHER USER at " . date('Y-m-d H:i:s') . " - This should trigger notification!";
    } else {
        $testUserId = $userId;
        $testUserType = $_SESSION['user_type'] ?? 'employee';
        $testMessage = "Test response from SAME USER at " . date('Y-m-d H:i:s') . " - This should NOT trigger notification.";
    }
    
    $stmt->execute([$ticketId, $testUserId, $testUserType, $testMessage]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Test response added successfully',
        'response_id' => $db->lastInsertId(),
        'ticket_id' => $ticketId
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>