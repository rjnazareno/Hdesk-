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

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Add a test response
    $stmt = $db->prepare("
        INSERT INTO ticket_responses (ticket_id, user_id, message, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $testMessage = "Test response added at " . date('Y-m-d H:i:s') . " for notification testing.";
    $stmt->execute([$ticketId, $userId, $testMessage]);
    
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