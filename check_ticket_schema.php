<?php
/**
 * Check Tickets Table Schema
 * Diagnostic script to verify database schema
 */

require_once __DIR__ . '/config/config.php';

echo "<h1>Tickets Table Schema Check</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if tickets table exists
    $stmt = $db->query("SHOW TABLES LIKE 'tickets'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "<p style='color: red;'>❌ Tickets table does NOT exist!</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ Tickets table exists</p>";
    
    // Get table structure
    echo "<h2>Table Structure:</h2>";
    $stmt = $db->query("DESCRIBE tickets");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $requiredColumns = [
        'id', 'ticket_number', 'title', 'description', 'category_id', 
        'priority', 'status', 'submitter_id', 'submitter_type', 
        'assigned_to', 'attachments', 'created_at', 'updated_at'
    ];
    
    $foundColumns = [];
    foreach ($columns as $col) {
        $foundColumns[] = $col['Field'];
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
    
    // Check for missing columns
    echo "<h2>Required Columns Check:</h2>";
    $missingColumns = array_diff($requiredColumns, $foundColumns);
    
    if (empty($missingColumns)) {
        echo "<p style='color: green;'>✓ All required columns exist</p>";
    } else {
        echo "<p style='color: red;'>❌ Missing columns:</p>";
        echo "<ul>";
        foreach ($missingColumns as $missing) {
            echo "<li style='color: red;'>$missing</li>";
        }
        echo "</ul>";
    }
    
    // Check categories table
    echo "<h2>Categories Table Check:</h2>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
    $categoryCount = $stmt->fetch();
    echo "<p>Categories in database: <strong>{$categoryCount['count']}</strong></p>";
    
    if ($categoryCount['count'] == 0) {
        echo "<p style='color: red;'>❌ No categories exist! Tickets require at least one category.</p>";
    }
    
    // Check employees table
    echo "<h2>Employees Table Check:</h2>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees");
    $employeeCount = $stmt->fetch();
    echo "<p>Employees in database: <strong>{$employeeCount['count']}</strong></p>";
    
    // Try a test insert (rolled back)
    echo "<h2>Test Insert (Dry Run):</h2>";
    try {
        $db->beginTransaction();
        
        $sql = "INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, submitter_id, submitter_type, assigned_to, attachments) 
                VALUES (:ticket_number, :title, :description, :category_id, :priority, :status, :submitter_id, :submitter_type, :assigned_to, :attachments)";
        
        $stmt = $db->prepare($sql);
        $result = $stmt->execute([
            ':ticket_number' => 'TEST-2025-0001',
            ':title' => 'Test Ticket',
            ':description' => 'Test description',
            ':category_id' => 1,
            ':priority' => 'medium',
            ':status' => 'pending',
            ':submitter_id' => 1,
            ':submitter_type' => 'employee',
            ':assigned_to' => null,
            ':attachments' => null
        ]);
        
        if ($result) {
            echo "<p style='color: green;'>✓ Test insert successful (rolled back)</p>";
        }
        
        $db->rollBack();
        
    } catch (Exception $e) {
        $db->rollBack();
        echo "<p style='color: red;'>❌ Test insert failed: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
