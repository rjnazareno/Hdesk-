<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

// Verify user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
        exit;
    }
    
    $ticketId = filter_var($input['ticket_id'] ?? 0, FILTER_VALIDATE_INT);
    $isTyping = (bool)($input['is_typing'] ?? false);
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];
    
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
    if ($userType === 'employee' && $ticket['employee_id'] != $userId) {
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    // Create typing_status table if it doesn't exist
    $createTableQuery = "
        CREATE TABLE IF NOT EXISTS typing_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            user_type ENUM('employee', 'it_staff') NOT NULL,
            is_typing BOOLEAN DEFAULT FALSE,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_ticket_id (ticket_id),
            INDEX idx_user (user_id, user_type),
            UNIQUE KEY unique_user_ticket (ticket_id, user_id, user_type)
        )";
    $db->exec($createTableQuery);
    
    if ($isTyping) {
        // User is typing - insert or update record
        $upsertQuery = "
            INSERT INTO typing_status (ticket_id, user_id, user_type, is_typing, last_activity) 
            VALUES (?, ?, ?, 1, NOW())
            ON DUPLICATE KEY UPDATE 
                is_typing = 1, 
                last_activity = NOW()";
        $stmt = $db->prepare($upsertQuery);
        $stmt->execute([$ticketId, $userId, $userType]);
    } else {
        // User stopped typing - update or delete record
        $updateQuery = "
            UPDATE typing_status 
            SET is_typing = 0, last_activity = NOW() 
            WHERE ticket_id = ? AND user_id = ? AND user_type = ?";
        $stmt = $db->prepare($updateQuery);
        $stmt->execute([$ticketId, $userId, $userType]);
    }
    
    // Clean up old typing status records (older than 30 seconds)
    $cleanupQuery = "
        UPDATE typing_status 
        SET is_typing = 0 
        WHERE last_activity < DATE_SUB(NOW(), INTERVAL 30 SECOND)";
    $db->exec($cleanupQuery);
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Typing status API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error occurred']);
}
?>