<?php
/**
 * Direct Database Insert Test
 * Test if we can insert into tickets table directly
 */

require_once __DIR__ . '/config/config.php';

echo "<h1>Direct Ticket Insert Test</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Test data
    $testData = [
        'ticket_number' => 'TEST-' . time(),
        'title' => 'Direct Test Ticket',
        'description' => 'Testing direct database insert',
        'category_id' => 1,
        'priority' => 'medium',
        'status' => 'pending',
        'submitter_id' => 1,
        'submitter_type' => 'employee',
        'assigned_to' => null,
        'attachments' => null
    ];
    
    echo "<h2>Test Data:</h2>";
    echo "<pre>" . print_r($testData, true) . "</pre>";
    
    // Prepare statement
    $sql = "INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, submitter_id, submitter_type, assigned_to, attachments) 
            VALUES (:ticket_number, :title, :description, :category_id, :priority, :status, :submitter_id, :submitter_type, :assigned_to, :attachments)";
    
    echo "<h2>SQL Query:</h2>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    
    $stmt = $db->prepare($sql);
    
    echo "<h2>Executing Insert...</h2>";
    $result = $stmt->execute($testData);
    
    echo "<p><strong>Execute result:</strong> " . ($result ? 'TRUE' : 'FALSE') . "</p>";
    echo "<p><strong>Row count:</strong> " . $stmt->rowCount() . "</p>";
    
    $lastId = $db->lastInsertId();
    echo "<p><strong>Last Insert ID:</strong> " . $lastId . "</p>";
    
    if ($result && $lastId) {
        echo "<p style='color: green; font-size: 20px;'>✓ SUCCESS! Ticket inserted with ID: $lastId</p>";
        
        // Try to fetch it back
        $fetchSql = "SELECT * FROM tickets WHERE id = :id";
        $fetchStmt = $db->prepare($fetchSql);
        $fetchStmt->execute([':id' => $lastId]);
        $ticket = $fetchStmt->fetch();
        
        echo "<h2>Fetched Ticket:</h2>";
        echo "<pre>" . print_r($ticket, true) . "</pre>";
        
        // Clean up - delete the test ticket
        $deleteSql = "DELETE FROM tickets WHERE id = :id";
        $deleteStmt = $db->prepare($deleteSql);
        $deleteStmt->execute([':id' => $lastId]);
        echo "<p style='color: orange;'>Test ticket deleted.</p>";
        
    } else {
        echo "<p style='color: red; font-size: 20px;'>✗ FAILED!</p>";
        echo "<h2>Error Info:</h2>";
        echo "<pre>" . print_r($stmt->errorInfo(), true) . "</pre>";
        echo "<pre>" . print_r($db->errorInfo(), true) . "</pre>";
    }
    
    // Check table structure
    echo "<h2>Tickets Table Structure:</h2>";
    $structSql = "DESCRIBE tickets";
    $structStmt = $db->query($structSql);
    $structure = $structStmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($structure as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if ID column is auto_increment
    $hasAutoIncrement = false;
    foreach ($structure as $col) {
        if ($col['Field'] === 'id' && strpos($col['Extra'], 'auto_increment') !== false) {
            $hasAutoIncrement = true;
        }
    }
    
    if ($hasAutoIncrement) {
        echo "<p style='color: green;'>✓ ID column has AUTO_INCREMENT</p>";
    } else {
        echo "<p style='color: red;'>✗ WARNING: ID column does NOT have AUTO_INCREMENT!</p>";
        echo "<p>This is the problem! The ID column needs AUTO_INCREMENT.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
