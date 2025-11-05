<?php
/**
 * Fix Tickets Table - Add AUTO_INCREMENT to ID column
 */

require_once __DIR__ . '/config/config.php';

echo "<h1>Fix Tickets Table - Add AUTO_INCREMENT</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>Step 1: Check current structure</h2>";
    $checkSql = "SHOW CREATE TABLE tickets";
    $stmt = $db->query($checkSql);
    $result = $stmt->fetch();
    echo "<pre>" . htmlspecialchars($result['Create Table']) . "</pre>";
    
    echo "<h2>Step 2: Adding AUTO_INCREMENT to id column...</h2>";
    
    // First, make sure id is primary key
    $sql1 = "ALTER TABLE tickets DROP PRIMARY KEY";
    try {
        $db->exec($sql1);
        echo "<p style='color: orange;'>✓ Dropped existing primary key</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>No existing primary key (this is OK)</p>";
    }
    
    // Add primary key with auto_increment
    $sql2 = "ALTER TABLE tickets MODIFY COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY";
    $db->exec($sql2);
    echo "<p style='color: green;'>✓ Added AUTO_INCREMENT to id column and set as PRIMARY KEY</p>";
    
    echo "<h2>Step 3: Verify the fix</h2>";
    $verifySql = "DESCRIBE tickets";
    $verifyStmt = $db->query($verifySql);
    $columns = $verifyStmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $fixed = false;
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'id' && strpos($col['Extra'], 'auto_increment') !== false) {
            $fixed = true;
        }
    }
    echo "</table>";
    
    if ($fixed) {
        echo "<h2 style='color: green;'>✓ SUCCESS! ID column now has AUTO_INCREMENT</h2>";
        echo "<p>You can now create tickets normally!</p>";
    } else {
        echo "<h2 style='color: red;'>✗ FAILED! AUTO_INCREMENT not added</h2>";
    }
    
    echo "<h2>Step 4: Test insert with new structure</h2>";
    $testSql = "INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, submitter_id, submitter_type) 
                VALUES ('TEST-FIX-" . time() . "', 'Test After Fix', 'Testing auto increment', 1, 'low', 'pending', 1, 'employee')";
    $db->exec($testSql);
    $testId = $db->lastInsertId();
    echo "<p><strong>Test Insert ID:</strong> $testId</p>";
    
    if ($testId > 0) {
        echo "<p style='color: green; font-size: 20px;'>✓ TEST PASSED! lastInsertId() returns: $testId</p>";
        
        // Clean up test
        $db->exec("DELETE FROM tickets WHERE id = $testId");
        echo "<p style='color: orange;'>Test ticket deleted.</p>";
    } else {
        echo "<p style='color: red; font-size: 20px;'>✗ TEST FAILED! Still returning 0</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='admin/create_ticket.php'>← Go back to Create Ticket</a></p>";
