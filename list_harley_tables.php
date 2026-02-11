<?php
/**
 * List all tables in Harley Calendar Database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/harley_config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Harley Database Tables</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .pass { color: #4ec9b0; font-weight: bold; }
        .fail { color: #f48771; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: #2d2d2d; color: #569cd6; }
        tr:hover { background: #2d2d2d; }
    </style>
</head>
<body>
<h1>Harley Calendar Database - Table List</h1>

<?php

try {
    $harleyDb = getHarleyConnection();
    
    if (!$harleyDb) {
        throw new Exception("Cannot connect to Harley database");
    }
    
    echo '<p class="pass">✓ Connected to Harley database</p>';
    echo '<p>Host: ' . HARLEY_DB_HOST . '</p>';
    echo '<p>Database: ' . HARLEY_DB_NAME . '</p>';
    
    // Get all tables
    $stmt = $harleyDb->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo '<h2>Tables (' . count($tables) . '):</h2>';
    echo '<table>';
    echo '<thead><tr><th>#</th><th>Table Name</th><th>Row Count</th></tr></thead>';
    echo '<tbody>';
    
    foreach ($tables as $index => $table) {
        try {
            $countStmt = $harleyDb->query("SELECT COUNT(*) FROM `$table`");
            $count = $countStmt->fetchColumn();
            echo "<tr><td>" . ($index + 1) . "</td><td><strong>$table</strong></td><td>$count rows</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td>" . ($index + 1) . "</td><td><strong>$table</strong></td><td class='fail'>Error: " . $e->getMessage() . "</td></tr>";
        }
    }
    
    echo '</tbody></table>';
    
    // Check for employee-related tables
    echo '<h2>Employee-Related Tables:</h2>';
    $employeeTables = array_filter($tables, function($table) {
        return stripos($table, 'emp') !== false || stripos($table, 'employee') !== false;
    });
    
    if (empty($employeeTables)) {
        echo '<p class="fail">No employee-related tables found!</p>';
    } else {
        echo '<ul>';
        foreach ($employeeTables as $table) {
            echo "<li><strong>$table</strong></li>";
        }
        echo '</ul>';
        
        // Show structure of first employee table
        $firstEmpTable = reset($employeeTables);
        echo "<h3>Structure of '$firstEmpTable':</h3>";
        $stmt = $harleyDb->query("DESCRIBE `$firstEmpTable`");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<table>';
        echo '<thead><tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>';
        echo '<tbody>';
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
        
        // Show sample data
        echo "<h3>Sample Data (First 5 rows):</h3>";
        $stmt = $harleyDb->query("SELECT * FROM `$firstEmpTable` LIMIT 5");
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($samples)) {
            echo '<table>';
            echo '<thead><tr>';
            foreach (array_keys($samples[0]) as $col) {
                echo "<th>$col</th>";
            }
            echo '</tr></thead><tbody>';
            
            foreach ($samples as $row) {
                echo '<tr>';
                foreach ($row as $val) {
                    echo '<td>' . htmlspecialchars($val ?? 'NULL') . '</td>';
                }
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
    }
    
} catch (Exception $e) {
    echo '<p class="fail">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

?>

</body>
</html>
