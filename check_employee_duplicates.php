<?php
/**
 * Check for Duplicate Employees and Admin Rights Issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Duplicates Check</title>
    <style>
        body { font-family: -apple-system, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1400px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #dc3545; }
        h2 { color: #0d6efd; margin-top: 30px; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #f8f9fa; font-weight: 600; }
        .duplicate { background: #fff3cd; }
        .error { background: #f8d7da; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; }
        .stat-card h3 { margin: 0 0 5px 0; font-size: 28px; color: #0d6efd; }
        .stat-card p { margin: 0; color: #666; font-size: 14px; }
    </style>
</head>
<body>
<div class="container">
<h1>🔍 Employee Duplicates & Admin Rights Check</h1>

<?php

try {
    $db = Database::getInstance()->getConnection();
    
    // Get stats
    echo '<div class="stats">';
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees");
    $totalCount = $stmt->fetchColumn();
    echo "<div class='stat-card'><h3>$totalCount</h3><p>Total Employees</p></div>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'");
    $activeCount = $stmt->fetchColumn();
    echo "<div class='stat-card'><h3>$activeCount</h3><p>Active Employees</p></div>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees WHERE admin_rights_hdesk = 'it'");
    $itAdminCount = $stmt->fetchColumn();
    echo "<div class='stat-card' style='border-color: #dc3545;'><h3>$itAdminCount</h3><p>IT Admins</p></div>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees WHERE admin_rights_hdesk = 'hr'");
    $hrAdminCount = $stmt->fetchColumn();
    echo "<div class='stat-card'><h3>$hrAdminCount</h3><p>HR Admins</p></div>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees WHERE admin_rights_hdesk = 'superadmin'");
    $superAdminCount = $stmt->fetchColumn();
    echo "<div class='stat-card'><h3>$superAdminCount</h3><p>Super Admins</p></div>";
    
    echo '</div>';
    
    // Check for duplicates by email
    echo '<h2>1. Duplicate Emails</h2>';
    $sql = "SELECT email, COUNT(*) as count, GROUP_CONCAT(id ORDER BY id) as ids, 
            GROUP_CONCAT(CONCAT(fname, ' ', lname) SEPARATOR ' | ') as names,
            GROUP_CONCAT(employee_id ORDER BY id) as employee_ids
            FROM employees 
            WHERE email IS NOT NULL AND email != ''
            GROUP BY email 
            HAVING count > 1 
            ORDER BY count DESC";
    
    $stmt = $db->query($sql);
    $duplicateEmails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicateEmails)) {
        echo '<p style="color: green;">✅ No duplicate emails found</p>';
    } else {
        echo '<p style="color: red;">❌ Found ' . count($duplicateEmails) . ' emails with duplicates:</p>';
        echo '<table>';
        echo '<thead><tr><th>Email</th><th>Count</th><th>IDs</th><th>Names</th><th>Employee IDs (Harley)</th></tr></thead>';
        echo '<tbody>';
        foreach ($duplicateEmails as $row) {
            echo "<tr class='duplicate'>";
            echo "<td>{$row['email']}</td>";
            echo "<td><strong>{$row['count']}</strong></td>";
            echo "<td>{$row['ids']}</td>";
            echo "<td>{$row['names']}</td>";
            echo "<td>{$row['employee_ids']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
    }
    
    // Check for duplicates by name
    echo '<h2>2. Duplicate Names</h2>';
    $sql = "SELECT CONCAT(fname, ' ', lname) as full_name, COUNT(*) as count, 
            GROUP_CONCAT(id ORDER BY id) as ids,
            GROUP_CONCAT(email SEPARATOR ' | ') as emails,
            GROUP_CONCAT(employee_id ORDER BY id) as employee_ids
            FROM employees 
            WHERE fname IS NOT NULL AND lname IS NOT NULL
            GROUP BY fname, lname 
            HAVING count > 1 
            ORDER BY count DESC";
    
    $stmt = $db->query($sql);
    $duplicateNames = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicateNames)) {
        echo '<p style="color: green;">✅ No duplicate names found</p>';
    } else {
        echo '<p style="color: red;">❌ Found ' . count($duplicateNames) . ' names with duplicates:</p>';
        echo '<table>';
        echo '<thead><tr><th>Full Name</th><th>Count</th><th>IDs</th><th>Emails</th><th>Employee IDs (Harley)</th></tr></thead>';
        echo '<tbody>';
        foreach ($duplicateNames as $row) {
            echo "<tr class='duplicate'>";
            echo "<td>{$row['full_name']}</td>";
            echo "<td><strong>{$row['count']}</strong></td>";
            echo "<td>{$row['ids']}</td>";
            echo "<td>{$row['emails']}</td>";
            echo "<td>{$row['employee_ids']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
    }
    
    // Check for duplicate employee_ids
    echo '<h2>3. Duplicate Employee IDs (Harley IDs)</h2>';
    $sql = "SELECT employee_id, COUNT(*) as count, 
            GROUP_CONCAT(id ORDER BY id) as ids,
            GROUP_CONCAT(CONCAT(fname, ' ', lname) SEPARATOR ' | ') as names,
            GROUP_CONCAT(email SEPARATOR ' | ') as emails
            FROM employees 
            WHERE employee_id IS NOT NULL
            GROUP BY employee_id 
            HAVING count > 1 
            ORDER BY count DESC";
    
    $stmt = $db->query($sql);
    $duplicateEmpIds = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($duplicateEmpIds)) {
        echo '<p style="color: green;">✅ No duplicate employee_ids found</p>';
    } else {
        echo '<p style="color: red;">❌ Found ' . count($duplicateEmpIds) . ' employee_ids with duplicates:</p>';
        echo '<table>';
        echo '<thead><tr><th>Employee ID (Harley)</th><th>Count</th><th>Local IDs</th><th>Names</th><th>Emails</th></tr></thead>';
        echo '<tbody>';
        foreach ($duplicateEmpIds as $row) {
            echo "<tr class='duplicate'>";
            echo "<td><strong>{$row['employee_id']}</strong></td>";
            echo "<td><strong>{$row['count']}</strong></td>";
            echo "<td>{$row['ids']}</td>";
            echo "<td>{$row['names']}</td>";
            echo "<td>{$row['emails']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
    }
    
    // Show employees with IT admin rights
    echo '<h2>4. Employees with IT Admin Rights</h2>';
    $sql = "SELECT id, employee_id, CONCAT(fname, ' ', lname) as full_name, email, username, role, admin_rights_hdesk, status
            FROM employees 
            WHERE admin_rights_hdesk = 'it'
            ORDER BY id
            LIMIT 50";
    
    $stmt = $db->query($sql);
    $itAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo '<p>Showing first 50 of ' . $itAdminCount . ' IT admins:</p>';
    echo '<table>';
    echo '<thead><tr><th>ID</th><th>Emp ID</th><th>Name</th><th>Email</th><th>Username</th><th>Role</th><th>Admin Rights</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    foreach ($itAdmins as $row) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['employee_id']}</td>";
        echo "<td>{$row['full_name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['username']}</td>";
        echo "<td>{$row['role']}</td>";
        echo "<td><strong>{$row['admin_rights_hdesk']}</strong></td>";
        echo "<td>{$row['status']}</td>";
        echo "</tr>";
    }
    echo '</tbody></table>';
    
    // Check employee_id distribution
    echo '<h2>5. Employee ID (Harley ID) Analysis</h2>';
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees WHERE employee_id IS NULL");
    $nullEmpId = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees WHERE employee_id IS NOT NULL");
    $hasEmpId = $stmt->fetchColumn();
    
    echo '<div class="stats">';
    echo "<div class='stat-card'><h3>$hasEmpId</h3><p>Have employee_id</p></div>";
    echo "<div class='stat-card'><h3>$nullEmpId</h3><p>NULL employee_id</p></div>";
    echo '</div>';
    
    if ($nullEmpId > 0) {
        echo '<p style="color: orange;">⚠️ ' . $nullEmpId . ' employees without employee_id (not synced from Harley)</p>';
        
        // Show employees without employee_id
        $sql = "SELECT id, CONCAT(fname, ' ', lname) as full_name, email, username, created_at
                FROM employees 
                WHERE employee_id IS NULL
                ORDER BY id
                LIMIT 20";
        
        $stmt = $db->query($sql);
        $noEmpId = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<p>First 20 employees without employee_id:</p>';
        echo '<table>';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Created At</th></tr></thead>';
        echo '<tbody>';
        foreach ($noEmpId as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['full_name']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['username']}</td>";
            echo "<td>{$row['created_at']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
    }
    
} catch (Exception $e) {
    echo '<div style="background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;">';
    echo '<h2 style="color: #721c24; margin-top: 0;">Error</h2>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . $e->getFile() . ' (Line ' . $e->getLine() . ')</p>';
    echo '</div>';
}

?>

<h2>💡 Recommended Actions</h2>
<ol>
    <li>If duplicates found by employee_id: <strong>Delete the duplicate records</strong></li>
    <li>If 115 IT admins is wrong: <strong>Check Harley database - they may all have admin_rights_hdesk='it'</strong></li>
    <li>If employees without employee_id exist: <strong>These are old records, consider deleting or updating</strong></li>
</ol>

</div>
</body>
</html>
