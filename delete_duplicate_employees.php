<?php
/**
 * Delete Duplicate Employees with ID=0
 * These are old records before Harley sync
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Delete Duplicate Employees</title>
    <style>
        body { font-family: -apple-system, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #dc3545; }
        .warning { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .success { background: #d4edda; border: 2px solid #28a745; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; border-radius: 5px; text-decoration: none; font-weight: 600; margin: 10px 5px; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #f8f9fa; }
        .stats { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin: 20px 0; }
        .stat-card { padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; }
        .stat-card h3 { margin: 0; font-size: 32px; color: #dc3545; }
    </style>
</head>
<body>
<div class="container">

<?php

try {
    $db = Database::getInstance()->getConnection();
    
    if (!$confirmed) {
        // Preview mode - show what will be deleted
        echo '<h1>⚠️ Delete Duplicate Employees (ID=0)</h1>';
        
        echo '<div class="warning">';
        echo '<h3 style="margin-top:0;">WARNING: This will permanently delete duplicate employee records</h3>';
        echo '<p><strong>Problem:</strong> All employees exist twice in the database:</p>';
        echo '<ul>';
        echo '<li><strong>Old records (ID=0):</strong> Have employee_id as numbers (1, 2, 3...) and all marked as IT admins</li>';
        echo '<li><strong>New records (ID>0):</strong> Synced from Harley with employee_id like "HRLY-1", "HRLY-2", etc.</li>';
        echo '</ul>';
        echo '<p><strong>Solution:</strong> Delete all records where <code>id = 0</code> (the old duplicates)</p>';
        echo '</div>';
        
        // Count records to be deleted
        $stmt = $db->query("SELECT COUNT(*) FROM employees WHERE id = 0");
        $countToDelete = $stmt->fetchColumn();
        
        $stmt = $db->query("SELECT COUNT(*) FROM employees WHERE id != 0");
        $countToKeep = $stmt->fetchColumn();
        
        echo '<div class="stats">';
        echo "<div class='stat-card'><h3>$countToDelete</h3><p>Records to be DELETED (id=0)</p></div>";
        echo "<div class='stat-card' style='border-color: #28a745;'><h3>$countToKeep</h3><p>Records to KEEP (id>0)</p></div>";
        echo '</div>';
        
        // Show sample of records to be deleted
        echo '<h2>Sample Records to be Deleted (First 20)</h2>';
        $sql = "SELECT id, employee_id, CONCAT(fname, ' ', lname) as full_name, email, role, admin_rights_hdesk 
                FROM employees 
                WHERE id = 0 
                ORDER BY employee_id 
                LIMIT 20";
        
        $stmt = $db->query($sql);
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<table>';
        echo '<thead><tr><th>ID</th><th>Employee ID</th><th>Name</th><th>Email</th><th>Role</th><th>Admin Rights</th></tr></thead>';
        echo '<tbody>';
        foreach ($samples as $row) {
            echo "<tr style='background: #f8d7da;'>";
            echo "<td><strong>{$row['id']}</strong></td>";
            echo "<td>{$row['employee_id']}</td>";
            echo "<td>{$row['full_name']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['role']}</td>";
            echo "<td>{$row['admin_rights_hdesk']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
        
        // Show sample of records to keep
        echo '<h2>Sample Records to Keep (First 20)</h2>';
        $sql = "SELECT id, employee_id, CONCAT(fname, ' ', lname) as full_name, email, role, admin_rights_hdesk 
                FROM employees 
                WHERE id != 0 
                ORDER BY id 
                LIMIT 20";
        
        $stmt = $db->query($sql);
        $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo '<table>';
        echo '<thead><tr><th>ID</th><th>Employee ID</th><th>Name</th><th>Email</th><th>Role</th><th>Admin Rights</th></tr></thead>';
        echo '<tbody>';
        foreach ($samples as $row) {
            echo "<tr style='background: #d4edda;'>";
            echo "<td><strong>{$row['id']}</strong></td>";
            echo "<td>{$row['employee_id']}</td>";
            echo "<td>{$row['full_name']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['role']}</td>";
            echo "<td>{$row['admin_rights_hdesk']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
        
        echo '<div style="margin: 30px 0; text-align: center;">';
        echo '<a href="?confirm=yes" class="btn btn-danger" onclick="return confirm(\'Are you ABSOLUTELY SURE you want to delete ' . $countToDelete . ' duplicate employee records? This cannot be undone!\');">✓ YES, DELETE ' . $countToDelete . ' DUPLICATE RECORDS</a>';
        echo '<a href="check_employee_duplicates.php" class="btn btn-secondary">✗ Cancel</a>';
        echo '</div>';
        
    } else {
        // Execute deletion
        echo '<h1>🗑️ Deleting Duplicate Employees...</h1>';
        
        // Get count before deletion
        $stmt = $db->query("SELECT COUNT(*) FROM employees WHERE id = 0");
        $countBefore = $stmt->fetchColumn();
        
        // Delete records with id=0
        $stmt = $db->prepare("DELETE FROM employees WHERE id = 0");
        $stmt->execute();
        $deletedCount = $stmt->rowCount();
        
        // Get count after deletion
        $stmt = $db->query("SELECT COUNT(*) FROM employees");
        $countAfter = $stmt->fetchColumn();
        
        echo '<div class="success">';
        echo '<h2 style="margin-top:0;">✅ Deletion Complete!</h2>';
        echo "<p><strong>$deletedCount</strong> duplicate employee records deleted successfully</p>";
        echo "<p><strong>Remaining employees:</strong> $countAfter</p>";
        echo '</div>';
        
        // Show final stats
        echo '<h2>Final Statistics</h2>';
        echo '<div class="stats">';
        
        $stmt = $db->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
        $activeCount = $stmt->fetchColumn();
        echo "<div class='stat-card' style='border-color: #28a745;'><h3 style='color:#28a745;'>$activeCount</h3><p>Active Employees</p></div>";
        
        $stmt = $db->query("SELECT COUNT(*) FROM employees WHERE admin_rights_hdesk = 'it'");
        $itAdminCount = $stmt->fetchColumn();
        echo "<div class='stat-card'><h3 style='color:#0d6efd;'>$itAdminCount</h3><p>IT Admins</p></div>";
        
        echo '</div>';
        
        echo '<p style="margin-top: 30px;">';
        echo '<a href="check_employee_duplicates.php" class="btn btn-secondary">← Back to Duplicate Check</a>';
        echo '<a href="admin/manage_employee_rights.php" class="btn" style="background:#0d6efd;color:white;">View Admin Rights →</a>';
        echo '</p>';
    }
    
} catch (Exception $e) {
    echo '<div style="background: #f8d7da; padding: 20px; border-radius: 5px; margin: 20px 0;">';
    echo '<h2 style="color: #721c24; margin-top: 0;">❌ Error</h2>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . $e->getFile() . ' (Line ' . $e->getLine() . ')</p>';
    echo '<pre style="background:white;padding:10px;overflow-x:auto;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}

?>

</div>
</body>
</html>
