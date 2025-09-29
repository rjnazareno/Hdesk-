<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        echo json_encode([
            'error' => 'Not authenticated',
            'session_data' => [
                'user_id' => $_SESSION['user_id'] ?? 'not set',
                'user_type' => $_SESSION['user_type'] ?? 'not set'
            ]
        ]);
        exit;
    }
    
    // Check database connection
    $testQuery = "SELECT 1 as test";
    $stmt = $db->prepare($testQuery);
    $stmt->execute();
    $testResult = $stmt->fetch();
    
    // Check if tables exist
    $tables = [];
    
    // Check tickets table
    try {
        $stmt = $db->prepare("DESCRIBE tickets");
        $stmt->execute();
        $tables['tickets'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $tables['tickets'] = 'Error: ' . $e->getMessage();
    }
    
    // Check ticket_responses table
    try {
        $stmt = $db->prepare("DESCRIBE ticket_responses");
        $stmt->execute();
        $tables['ticket_responses'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $tables['ticket_responses'] = 'Error: ' . $e->getMessage();
    }
    
    echo json_encode([
        'success' => true,
        'database_connection' => 'OK',
        'test_query' => $testResult,
        'session_info' => [
            'user_id' => $_SESSION['user_id'],
            'user_type' => $_SESSION['user_type']
        ],
        'tables' => $tables,
        'db_config' => [
            'host' => DB_HOST,
            'name' => DB_NAME,
            'user' => DB_USER
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}
?>