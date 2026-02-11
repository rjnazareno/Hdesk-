<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Profile Bug Diagnostic</title></head><body>";
echo "<h1>🔍 Profile Bug Diagnostic</h1>";

echo "<div style='background-color: #fff3cd; padding: 15px; border: 1px solid orange; margin: 20px 0;'>";
echo "<h2>🚨 IDENTIFIED ISSUE</h2>";
echo "<p>All users see the same profile because <strong>ALL employees have id = 0</strong></p>";
echo "<p>When you login → <code>\$_SESSION['user_id'] = 0</code> → Profile loads first employee with id=0 (Reneeca)</p>";
echo "</div>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check current session if available
    echo "<h2>1. Current Session Analysis</h2>";
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (!empty($_SESSION)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Session Variable</th><th>Value</th></tr>";
        
        $sessionVars = ['user_id', 'user_type', 'username', 'full_name', 'logged_in'];
        foreach ($sessionVars as $var) {
            if (isset($_SESSION[$var])) {
                $value = $_SESSION[$var];
                $highlight = ($var === 'user_id' && $value == 0) ? 'background-color: #ffe6e6;' : '';
                echo "<tr style='{$highlight}'><td><strong>{$var}</strong></td><td>{$value}</td></tr>";
            }
        }
        echo "</table>";
        
        if ($_SESSION['user_id'] == 0) {
            echo "<p style='color: red;'><strong>⚠️ PROBLEM CONFIRMED:</strong> user_id = 0 causes profile to load wrong employee!</p>";
        }
    } else {
        echo "<p>No active session found. Please log in first.</p>";
    }
    
    // Check employees with id = 0
    echo "<h2>2. Employees with ID = 0</h2>";
    $sql = "SELECT id, username, fname, lname, email, employee_id FROM employees WHERE id = 0 LIMIT 5";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $zeroIdEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($zeroIdEmployees)) {
        echo "<p style='color: red;'><strong>Found " . count($zeroIdEmployees) . " employees with id = 0:</strong></p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Employee_ID</th></tr>";
        foreach ($zeroIdEmployees as $emp) {
            $highlight = ($emp['fname'] === 'Reneeca') ? 'background-color: #ffe6e6;' : '';
            echo "<tr style='{$highlight}'>";
            echo "<td>{$emp['id']}</td>";
            echo "<td>{$emp['username']}</td>";
            echo "<td>{$emp['fname']} {$emp['lname']}</td>";
            echo "<td>{$emp['email']}</td>";
            echo "<td>{$emp['employee_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Profile Query Logic:</strong></p>";
        echo "<code>SELECT * FROM employees WHERE id = 0</code> → Returns first match (Reneeca)</p>";
    }
    
    // Test Employee::findById with id = 0
    echo "<h2>3. Employee::findById(0) Test</h2>";
    require_once 'models/Employee.php';
    $employee = new Employee();
    $result = $employee->findById(0);
    
    if ($result) {
        echo "<p><strong>Employee::findById(0) returns:</strong></p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td style='background-color: #ffe6e6;'>{$result['id']}</td></tr>";
        echo "<tr><td>Username</td><td>{$result['username']}</td></tr>";
        echo "<tr><td>Full Name</td><td>{$result['fname']} {$result['lname']}</td></tr>";
        echo "<tr><td>Email</td><td>{$result['email']}</td></tr>";
        echo "</table>";
        
        if ($result['fname'] === 'Reneeca') {
            echo "<p style='color: red;'><strong>✅ CONFIRMED:</strong> This is why everyone sees Reneeca's profile!</p>";
        }
    }
    
    // Show solution
    echo "<h2>4. 💡 Solution</h2>";
    echo "<div style='background-color: #e6ffe6; padding: 15px; border: 1px solid green;'>";
    echo "<h3>Fix Options:</h3>";
    echo "<ol>";
    echo "<li><strong>Fresh Sync (Recommended):</strong> Run <a href='fresh_sync_from_harley.php'>fresh_sync_from_harley.php</a> to give each employee unique IDs</li>";
    echo "<li><strong>Profile Fix:</strong> Modify profile to use employee_id instead of id</li>";
    echo "<li><strong>Session Fix:</strong> Update login to use employee_id instead of database id</li>";
    echo "</ol>";
    echo "<p><strong>Root Cause:</strong> All employees sharing id=0 breaks individual user identification</p>";
    echo "</div>";
    
    // Count total employees with id = 0
    $sql = "SELECT COUNT(*) as count FROM employees WHERE id = 0";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $totalZeroIds = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div style='background-color: #fff3cd; padding: 15px; border: 1px solid orange; margin: 20px 0;'>";
    echo "<h3>📊 Summary</h3>";
    echo "<ul>";
    echo "<li><strong>Total employees with id=0:</strong> {$totalZeroIds['count']}</li>";
    echo "<li><strong>Problem:</strong> All logins get user_id = 0 → Same profile shown</li>";
    echo "<li><strong>Fix:</strong> Run Fresh Sync to assign unique IDs</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='fresh_sync_from_harley.php'>🔄 Run Fresh Sync Now</a> | <a href='debug_admin_rights.php'>← Debug Report</a></p>";
echo "</body></html>";
?>