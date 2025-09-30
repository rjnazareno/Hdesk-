<?php
/**
 * Check Database Table Structure
 */

require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h2>Database Table Structure Check</h2>";
    
    // Check employees table structure
    echo "<h3>Employees Table Structure:</h3>";
    $stmt = $db->prepare("DESCRIBE employees");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check it_staff table structure
    echo "<h3>IT Staff Table Structure:</h3>";
    $stmt = $db->prepare("DESCRIBE it_staff");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check tickets table structure
    echo "<h3>Tickets Table Structure:</h3>";
    $stmt = $db->prepare("DESCRIBE tickets");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Sample data check
    echo "<h3>Sample Data Check:</h3>";
    
    // Show first employee
    try {
        $stmt = $db->prepare("SELECT * FROM employees LIMIT 1");
        $stmt->execute();
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($sample) {
            echo "<p><strong>Sample Employee:</strong></p>";
            echo "<pre>" . print_r($sample, true) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p>Employee data error: " . $e->getMessage() . "</p>";
    }
    
    // Show first ticket
    try {
        $stmt = $db->prepare("SELECT * FROM tickets LIMIT 1");
        $stmt->execute();
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($sample) {
            echo "<p><strong>Sample Ticket:</strong></p>";
            echo "<pre>" . print_r($sample, true) . "</pre>";
        }
    } catch (Exception $e) {
        echo "<p>Ticket data error: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>