<?php
/**
 * Show Harley Employees Table Structure
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/harley_config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Harley Employees Table</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .pass { color: #4ec9b0; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #444; padding: 8px; text-align: left; }
        th { background: #2d2d2d; color: #569cd6; }
        tr:hover { background: #2d2d2d; }
        pre { background: #2d2d2d; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
<h1>Harley 'employees' Table Structure</h1>

<?php

try {
    $harleyDb = getHarleyConnection();
    
    if (!$harleyDb) {
        throw new Exception("Cannot connect to Harley database");
    }
    
    echo '<p class="pass">✓ Connected to Harley database</p>';
    
    // Get row count
    $stmt = $harleyDb->query("SELECT COUNT(*) FROM employees");
    $count = $stmt->fetchColumn();
    echo "<p>Total rows: <strong>$count</strong></p>";
    
    // Get table structure
    echo '<h2>Table Structure:</h2>';
    $stmt = $harleyDb->query("DESCRIBE employees");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<table>';
    echo '<thead><tr><th>Column Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>';
    echo '<tbody>';
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>{$col['Field']}</strong></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo '</tbody></table>';
    
    // Get sample data (first 3 rows)
    echo '<h2>Sample Data (First 3 Rows):</h2>';
    $stmt = $harleyDb->query("SELECT * FROM employees LIMIT 3");
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
                $display = $val;
                if (strlen($display) > 50) {
                    $display = substr($display, 0, 50) . '...';
                }
                echo '<td>' . htmlspecialchars($display ?? 'NULL') . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    
    // Show first row in detail
    if (!empty($samples)) {
        echo '<h2>First Row (Detailed):</h2>';
        echo '<table>';
        echo '<thead><tr><th>Column</th><th>Value</th></tr></thead>';
        echo '<tbody>';
        foreach ($samples[0] as $key => $val) {
            echo "<tr><td><strong>$key</strong></td><td>" . htmlspecialchars($val ?? 'NULL') . "</td></tr>";
        }
        echo '</tbody></table>';
    }
    
    // Count active employees
    echo '<h2>Employee Counts by Status:</h2>';
    $stmt = $harleyDb->query("SELECT status, COUNT(*) as count FROM employees GROUP BY status");
    $statusCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<table>';
    echo '<thead><tr><th>Status</th><th>Count</th></tr></thead>';
    echo '<tbody>';
    foreach ($statusCounts as $row) {
        echo "<tr><td><strong>{$row['status']}</strong></td><td>{$row['count']}</td></tr>";
    }
    echo '</tbody></table>';
    
} catch (Exception $e) {
    echo '<p class="fail">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}

?>

</body>
</html>
