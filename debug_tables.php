<?php
/**
 * Quick database table inspection
 */
require_once '../config/database.php';

session_start();

if (!isset($_SESSION['user_id'])) {
    die('Please login first');
}

try {
    $pdo = getDB();
    
    echo "<h2>Ticket Responses Table Structure</h2>";
    
    // Show table structure
    $stmt = $pdo->query("DESCRIBE ticket_responses");
    echo "<h3>Table Fields:</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch()) {
        print_r($row);
    }
    echo "</pre>";
    
    // Show recent responses
    $stmt = $pdo->query("SELECT * FROM ticket_responses WHERE ticket_id = 1 ORDER BY created_at DESC LIMIT 10");
    echo "<h3>Recent Responses for Ticket 1:</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch()) {
        print_r($row);
    }
    echo "</pre>";
    
    // Show tickets table structure for comparison
    $stmt = $pdo->query("DESCRIBE tickets");
    echo "<h3>Tickets Table Fields:</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch()) {
        print_r($row);
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>