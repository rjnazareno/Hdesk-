<?php
/**
 * Migration script to update resolved tickets to closed status
 * Run this once to migrate existing data
 */
require_once '../config/database.php';

try {
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Update all resolved tickets to closed
    $stmt = $db->prepare("UPDATE tickets SET status = 'closed' WHERE status = 'resolved'");
    $result = $stmt->execute();
    
    $rowCount = $stmt->rowCount();
    
    echo "Migration completed successfully!\n";
    echo "Updated {$rowCount} tickets from 'resolved' to 'closed' status.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>