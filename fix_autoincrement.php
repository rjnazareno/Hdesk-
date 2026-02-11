<?php
/**
 * Fix Auto-Increment on Tickets Table
 * Run this ONCE to fix the id column
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');

echo '<pre>';
echo "=== Fixing Tickets Table Auto-Increment ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Step 1: Check current table structure
    echo "1. Checking current tickets table structure...\n";
    $stmt = $db->query("SHOW CREATE TABLE tickets");
    $result = $stmt->fetch();
    echo "Current structure:\n";
    echo $result['Create Table'] . "\n\n";
    
    // Step 2: Check if there are any tickets with id = 0
    echo "2. Checking for tickets with id = 0...\n";
    $stmt = $db->query("SELECT COUNT(*) as count FROM tickets WHERE id = 0");
    $zeroIdCount = $stmt->fetch();
    echo "Found {$zeroIdCount['count']} ticket(s) with id = 0\n\n";
    
    if ($zeroIdCount['count'] > 0) {
        echo "3. Deleting tickets with id = 0 (these are invalid)...\n";
        $db->exec("DELETE FROM tickets WHERE id = 0");
        echo "   Deleted {$zeroIdCount['count']} invalid ticket(s)\n\n";
    }
    
    // Step 3: Get the max ID currently in use
    echo "4. Finding highest ticket ID...\n";
    $stmt = $db->query("SELECT MAX(id) as max_id FROM tickets");
    $maxResult = $stmt->fetch();
    $maxId = $maxResult['max_id'] ? (int)$maxResult['max_id'] : 0;
    $nextId = $maxId + 1;
    echo "   Highest ID: $maxId\n";
    echo "   Next ID will be: $nextId\n\n";
    
    // Step 4: Fix the id column to be AUTO_INCREMENT
    echo "5. Modifying tickets table to add AUTO_INCREMENT...\n";
    
    // First, let's make sure id is the primary key and auto_increment
    $sql = "ALTER TABLE tickets MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT";
    
    try {
        $db->exec($sql);
        echo "   ✓ Successfully set id column to AUTO_INCREMENT\n\n";
    } catch (PDOException $e) {
        // If that fails, try dropping and recreating
        echo "   First attempt failed, trying alternative method...\n";
        echo "   Error: " . $e->getMessage() . "\n\n";
        
        // Alternative: Drop primary key, modify, add back
        try {
            echo "   Attempting to fix primary key...\n";
            $db->exec("ALTER TABLE tickets DROP PRIMARY KEY");
            $db->exec("ALTER TABLE tickets MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY");
            echo "   ✓ Successfully fixed using alternative method\n\n";
        } catch (PDOException $e2) {
            echo "   ✗ Alternative method also failed: " . $e2->getMessage() . "\n";
            throw $e2;
        }
    }
    
    // Step 6: Set the auto_increment value
    echo "6. Setting AUTO_INCREMENT to start at $nextId...\n";
    $db->exec("ALTER TABLE tickets AUTO_INCREMENT = $nextId");
    echo "   ✓ AUTO_INCREMENT set to $nextId\n\n";
    
    // Step 7: Verify the fix
    echo "7. Verifying the fix...\n";
    $stmt = $db->query("SHOW CREATE TABLE tickets");
    $result = $stmt->fetch();
    
    if (strpos($result['Create Table'], 'AUTO_INCREMENT') !== false) {
        echo "   ✓ AUTO_INCREMENT is now enabled!\n\n";
        
        // Step 8: Test with a real insert
        echo "8. Testing with a real ticket insert...\n";
        $testTicket = [
            'ticket_number' => 'FIX-TEST-' . time(),
            'title' => 'Auto-increment test',
            'description' => 'Testing if auto-increment works',
            'category_id' => 1,
            'department_id' => null,
            'priority' => 'medium',
            'status' => 'pending',
            'submitter_id' => 1,
            'submitter_type' => 'employee',
            'assigned_to' => null,
            'attachments' => null
        ];
        
        $sql = "INSERT INTO tickets (ticket_number, title, description, category_id, department_id, priority, status, submitter_id, submitter_type, assigned_to, attachments) 
                VALUES (:ticket_number, :title, :description, :category_id, :department_id, :priority, :status, :submitter_id, :submitter_type, :assigned_to, :attachments)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($testTicket);
        $newId = $db->lastInsertId();
        
        echo "   New ticket ID from lastInsertId(): $newId\n";
        
        // Verify in database
        $checkStmt = $db->prepare("SELECT id, ticket_number FROM tickets WHERE ticket_number = :ticket_number");
        $checkStmt->execute([':ticket_number' => $testTicket['ticket_number']]);
        $created = $checkStmt->fetch();
        
        echo "   Ticket in database: ID = {$created['id']}, Ticket# = {$created['ticket_number']}\n";
        
        if ($created['id'] > 0 && $newId > 0) {
            echo "   ✓ SUCCESS! Auto-increment is working correctly!\n\n";
            
            // Clean up test ticket
            $db->exec("DELETE FROM tickets WHERE id = {$created['id']}");
            echo "   Test ticket cleaned up\n\n";
        } else {
            echo "   ✗ STILL BROKEN - ID is still 0 or lastInsertId returned 0\n\n";
        }
        
    } else {
        echo "   ✗ AUTO_INCREMENT not found in table structure\n\n";
    }
    
    echo "=== Fix Process Complete ===\n\n";
    echo "Try creating a ticket now!\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo '</pre>';
?>
