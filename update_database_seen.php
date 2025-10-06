<?php
/**
 * Database Update Script - Add Seen Functionality
 * Run this once to add seen tracking to ticket_responses table
 */

require_once 'config/database.php';

echo "<h2>Database Update - Adding Seen Functionality</h2>";
echo "<pre>";

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    echo "Connected to database successfully!\n\n";
    
    // Check if columns already exist
    $checkQuery = "SHOW COLUMNS FROM ticket_responses LIKE 'is_seen'";
    $stmt = $conn->query($checkQuery);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Seen functionality already exists!\n";
        echo "No update needed.\n";
    } else {
        echo "Adding seen functionality columns...\n";
        
        // Add is_seen column
        $conn->exec("ALTER TABLE ticket_responses ADD COLUMN is_seen BOOLEAN DEFAULT FALSE AFTER is_internal");
        echo "✓ Added is_seen column\n";
        
        // Add seen_at column
        $conn->exec("ALTER TABLE ticket_responses ADD COLUMN seen_at TIMESTAMP NULL AFTER is_seen");
        echo "✓ Added seen_at column\n";
        
        // Add seen_by column
        $conn->exec("ALTER TABLE ticket_responses ADD COLUMN seen_by INT NULL AFTER seen_at");
        echo "✓ Added seen_by column\n";
        
        // Add user_type column
        $conn->exec("ALTER TABLE ticket_responses ADD COLUMN user_type ENUM('employee', 'it_staff') NOT NULL DEFAULT 'it_staff' AFTER responder_id");
        echo "✓ Added user_type column\n";
        
        // Add indexes
        $conn->exec("CREATE INDEX idx_seen_status ON ticket_responses(ticket_id, is_seen)");
        echo "✓ Added idx_seen_status index\n";
        
        $conn->exec("CREATE INDEX idx_user_type ON ticket_responses(user_type)");
        echo "✓ Added idx_user_type index\n";
        
        echo "\n";
        echo "Updating existing responses...\n";
        
        // Update existing IT staff responses
        $stmt = $conn->exec("
            UPDATE ticket_responses tr
            SET user_type = 'it_staff'
            WHERE EXISTS (SELECT 1 FROM it_staff WHERE staff_id = tr.responder_id)
        ");
        echo "✓ Updated $stmt IT staff responses\n";
        
        // Update existing employee responses
        $stmt = $conn->exec("
            UPDATE ticket_responses tr
            SET user_type = 'employee'
            WHERE EXISTS (SELECT 1 FROM employees WHERE id = tr.responder_id)
        ");
        echo "✓ Updated $stmt employee responses\n";
        
        echo "\n";
        echo "✅ DATABASE UPDATE COMPLETED SUCCESSFULLY!\n";
        echo "You can now delete this file (update_database_seen.php)\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nPlease contact your database administrator.\n";
}

echo "</pre>";
?>
