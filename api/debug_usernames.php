<?php
/**
 * Debug username resolution
 */
require_once '../config/database.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

header('Content-Type: application/json');

try {
    $pdo = getDB();
    
    // Get recent responses with all details
    $stmt = $pdo->prepare("
        SELECT 
            tr.*,
            e.username as employee_username,
            e.fname as employee_fname,
            e.lname as employee_lname,
            i.username as it_username,
            i.name as it_name
        FROM ticket_responses tr
        LEFT JOIN employees e ON tr.user_id = e.id
        LEFT JOIN it_staff i ON tr.user_id = i.staff_id
        WHERE tr.ticket_id = 1
        ORDER BY tr.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $responses = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'responses' => $responses,
        'current_user' => [
            'id' => $_SESSION['user_id'],
            'type' => $_SESSION['user_type'] ?? 'unknown'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>