<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Admin Rights Debug</title></head><body>";
echo "<h1>Debug Admin Rights Assignment Issue</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check for duplicate employee IDs
    echo "<h2>1. Check for Duplicate Employee IDs</h2>";
    $sql = "SELECT id, COUNT(*) as count FROM employees WHERE id IS NOT NULL GROUP BY id HAVING COUNT(*) > 1 ORDER BY count DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicates)) {
        echo "<p style='color: green;'>✓ No duplicate employee IDs found.</p>";
    } else {
        echo "<p style='color: red;'>✗ Found duplicate employee IDs:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Employee ID</th><th>Count</th></tr>";
        foreach ($duplicates as $dup) {
            echo "<tr><td>{$dup['id']}</td><td>{$dup['count']}</td></tr>";
        }
        echo "</table>";
    }
    
    // Check for employees with same employee_id (strings from Harley)
    echo "<h2>2. Check for Duplicate Employee_ID Strings</h2>";
    $sql = "SELECT employee_id, COUNT(*) as count FROM employees WHERE employee_id IS NOT NULL GROUP BY employee_id HAVING COUNT(*) > 1 ORDER BY count DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $duplicateEmployeeIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicateEmployeeIds)) {
        echo "<p style='color: green;'>✓ No duplicate employee_id strings found.</p>";
    } else {
        echo "<p style='color: red;'>✗ Found duplicate employee_id strings:</p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Employee_ID</th><th>Count</th></tr>";
        foreach ($duplicateEmployeeIds as $dup) {
            echo "<tr><td>{$dup['employee_id']}</td><td>{$dup['count']}</td></tr>";
        }
        echo "</table>";
    }
    
    // Check current admin rights distribution
    echo "<h2>3. Current Admin Rights Distribution</h2>";
    $sql = "SELECT admin_rights_hdesk, COUNT(*) as count FROM employees GROUP BY admin_rights_hdesk ORDER BY count DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $rightsDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Admin Rights</th><th>Count</th></tr>";
    foreach ($rightsDistribution as $rights) {
        $color = '';
        if ($rights['admin_rights_hdesk'] === 'hr') $color = 'background-color: #e6ffe6;';
        elseif ($rights['admin_rights_hdesk'] === 'it') $color = 'background-color: #e6f3ff;';
        elseif ($rights['admin_rights_hdesk'] === 'superadmin') $color = 'background-color: #ffe6e6;';
        
        echo "<tr style='{$color}'><td>{$rights['admin_rights_hdesk']}</td><td>{$rights['count']}</td></tr>";
    }
    echo "</table>";
    
    // Show recent HR admin assignments
    echo "<h2>4. Recent HR Admin Employees</h2>";
    $sql = "SELECT id, fname, lname, username, employee_id, admin_rights_hdesk, created_at, updated_at 
            FROM employees 
            WHERE admin_rights_hdesk = 'hr' 
            ORDER BY updated_at DESC 
            LIMIT 10";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $hrAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($hrAdmins)) {
        echo "<p>No HR admins found.</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Employee_ID</th><th>Rights</th><th>Created</th><th>Updated</th></tr>";
        foreach ($hrAdmins as $admin) {
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>{$admin['fname']} {$admin['lname']}</td>";
            echo "<td>{$admin['username']}</td>";
            echo "<td>{$admin['employee_id']}</td>";
            echo "<td>{$admin['admin_rights_hdesk']}</td>";
            echo "<td>{$admin['created_at']}</td>";
            echo "<td>{$admin['updated_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check for any NULL or unusual IDs
    echo "<h2>5. Check for NULL or Unusual Employee IDs</h2>";
    $sql = "SELECT COUNT(*) as count FROM employees WHERE id IS NULL OR id = 0 OR id = ''";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $nullIds = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($nullIds['count'] > 0) {
        echo "<p style='color: red;'>✗ Found {$nullIds['count']} employees with NULL/0/empty IDs</p>";
        
        // Show them
        $sql = "SELECT id, fname, lname, username, employee_id, admin_rights_hdesk FROM employees WHERE id IS NULL OR id = 0 OR id = '' LIMIT 5";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $badIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Employee_ID</th><th>Rights</th></tr>";
        foreach ($badIds as $bad) {
            echo "<tr>";
            echo "<td style='background-color: #ffe6e6;'>{$bad['id']}</td>";
            echo "<td>{$bad['fname']} {$bad['lname']}</td>";
            echo "<td>{$bad['username']}</td>";
            echo "<td>{$bad['employee_id']}</td>";
            echo "<td>{$bad['admin_rights_hdesk']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✓ All employees have valid IDs</p>";
    }
    
    // Summary
    echo "<h2>6. Summary and Recommendations</h2>";
    
    $totalEmployees = 0;
    foreach ($rightsDistribution as $rights) {
        $totalEmployees += $rights['count'];
    }
    
    echo "<ul>";
    echo "<li><strong>Total Employees:</strong> {$totalEmployees}</li>";
    echo "<li><strong>HR Admins:</strong> " . (array_filter($rightsDistribution, fn($r) => $r['admin_rights_hdesk'] === 'hr')[0]['count'] ?? 0) . "</li>";
    echo "<li><strong>IT Admins:</strong> " . (array_filter($rightsDistribution, fn($r) => $r['admin_rights_hdesk'] === 'it')[0]['count'] ?? 0) . "</li>";
    echo "<li><strong>Super Admins:</strong> " . (array_filter($rightsDistribution, fn($r) => $r['admin_rights_hdesk'] === 'superadmin')[0]['count'] ?? 0) . "</li>";
    echo "</ul>";
    
    // Check if there are too many HR admins (indicating the bug)
    $hrCount = array_filter($rightsDistribution, fn($r) => $r['admin_rights_hdesk'] === 'hr')[0]['count'] ?? 0;
    if ($hrCount > 10) {
        echo "<div style='background-color: #ffe6e6; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
        echo "<strong>⚠ POTENTIAL BUG DETECTED:</strong> {$hrCount} HR admins seems unusually high. This might indicate the bulk assignment bug is active.";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>