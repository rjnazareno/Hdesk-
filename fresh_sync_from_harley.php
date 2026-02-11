<?php
/**
 * FRESH SYNC: Delete all IT Help Desk employees and sync from Harley ONLY
 * WARNING: This will delete ALL employees from IT Help Desk and re-import from Harley
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/harley_config.php';
require_once __DIR__ . '/models/Employee.php';

$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fresh Employee Sync from Harley</title>
    <style>
        body { font-family: -apple-system, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #dc3545; }
        .warning { background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .success { background: #d4edda; border: 2px solid #28a745; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .btn { display: inline-block; padding: 12px 24px; border-radius: 5px; text-decoration: none; font-weight: 600; margin: 10px 5px; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 13px; }
        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; }
        th { background: #f8f9fa; }
        .created { background: #d4edda; }
    </style>
</head>
<body>
<div class="container">

<?php

try {
    if (!$confirmed) {
        // Preview mode
        echo '<h1>⚠️ FRESH SYNC: Clear & Re-import from Harley</h1>';
        
        echo '<div class="warning">';
        echo '<h3 style="margin-top:0;">DANGER: This will DELETE ALL IT Help Desk employees</h3>';
        echo '<p><strong>What this script does:</strong></p>';
        echo '<ol>';
        echo '<li><strong>DELETE</strong> all employees from IT Help Desk database (clean slate)</li>';
        echo '<li><strong>FETCH</strong> all active employees from Harley calendar database ONLY</li>';
        echo '<li><strong>INSERT</strong> fresh records with correct data</li>';
        echo '</ol>';
        echo '<p><strong>⚠️ WARNING:</strong> Any manually added employees (not from Harley) will be deleted!</p>';
        echo '</div>';
        
        $localDb = Database::getInstance()->getConnection();
        $harleyDb = getHarleyConnection();
        
        // Count current employees
        $stmt = $localDb->query("SELECT COUNT(*) FROM employees");
        $currentCount = $stmt->fetchColumn();
        
        // Count Harley employees
        $stmt = $harleyDb->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
        $harleyCount = $stmt->fetchColumn();
        
        echo '<h2>Current Status:</h2>';
        echo '<table style="width:auto;">';
        echo '<tr><td><strong>IT Help Desk Employees (will be deleted):</strong></td><td style="color:#dc3545;font-weight:bold;">' . $currentCount . '</td></tr>';
        echo '<tr><td><strong>Harley Employees (will be imported):</strong></td><td style="color:#28a745;font-weight:bold;">' . $harleyCount . '</td></tr>';
        echo '</table>';
        
        echo '<div style="margin: 30px 0; text-align: center;">';
        echo '<a href="?confirm=yes" class="btn btn-danger" onclick="return confirm(\'Are you ABSOLUTELY SURE? This will delete ALL ' . $currentCount . ' employees and re-import ' . $harleyCount . ' from Harley. This cannot be undone!\');">🗑️ YES, DELETE ALL & FRESH SYNC FROM HARLEY</a>';
        echo '<a href="admin/customers.php" class="btn btn-secondary">✗ Cancel</a>';
        echo '</div>';
        
    } else {
        // Execute fresh sync
        echo '<h1>🔄 Fresh Sync in Progress...</h1>';
        
        $localDb = Database::getInstance()->getConnection();
        $harleyDb = getHarleyConnection();
        
        // Step 1: Delete all employees
        echo '<div class="warning">';
        echo '<h2>Step 1: Clearing IT Help Desk Employees</h2>';
        $stmt = $localDb->query("SELECT COUNT(*) FROM employees");
        $beforeCount = $stmt->fetchColumn();
        echo "<p>Current employees: <strong>$beforeCount</strong></p>";
        
        $stmt = $localDb->prepare("DELETE FROM employees");
        $stmt->execute();
        $deletedCount = $stmt->rowCount();
        
        echo "<p style='color:#dc3545;'>✓ Deleted <strong>$deletedCount</strong> employees</p>";
        echo '</div>';
        
        // Step 2: Fetch from Harley
        echo '<div class="success">';
        echo '<h2>Step 2: Fetching from Harley Calendar Database</h2>';
        
        $sql = "SELECT 
                    id,
                    fname,
                    lname,
                    email,
                    personal_email,
                    contact,
                    position,
                    company,
                    username,
                    password,
                    status,
                    role,
                    admin_rights_hdesk,
                    official_sched,
                    profile_picture
                FROM employees 
                WHERE status = 'active'
                ORDER BY lname, fname";
        
        $stmt = $harleyDb->prepare($sql);
        $stmt->execute();
        $harleyEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>✓ Fetched <strong>" . count($harleyEmployees) . "</strong> active employees from Harley</p>";
        echo '</div>';
        
        // Step 3: Insert into IT Help Desk
        echo '<h2>Step 3: Inserting Fresh Records</h2>';
        echo '<table>';
        echo '<thead><tr><th>#</th><th>Harley ID</th><th>Name</th><th>Email</th><th>Username</th><th>Role</th><th>Admin</th><th>Status</th></tr></thead>';
        echo '<tbody>';
        
        $employeeModel = new Employee();
        $created = 0;
        $errors = 0;
        
        foreach ($harleyEmployees as $index => $harleyEmp) {
            $fullName = trim($harleyEmp['fname'] . ' ' . $harleyEmp['lname']);
            
            try {
                $empData = [
                    'employee_id' => $harleyEmp['id'], // Store Harley's ID as-is (no HRLY- prefix)
                    'fname' => $harleyEmp['fname'],
                    'lname' => $harleyEmp['lname'],
                    'email' => $harleyEmp['email'],
                    'personal_email' => $harleyEmp['personal_email'],
                    'username' => $harleyEmp['username'],
                    'password' => !empty($harleyEmp['password']) ? password_hash($harleyEmp['password'], PASSWORD_DEFAULT) : password_hash('Welcome123!', PASSWORD_DEFAULT),
                    'company' => $harleyEmp['company'] ?? 'RSO',
                    'position' => $harleyEmp['position'],
                    'contact' => $harleyEmp['contact'],
                    'official_sched' => $harleyEmp['official_sched'],
                    'role' => $harleyEmp['role'] ?? 'employee',
                    'admin_rights_hdesk' => $harleyEmp['admin_rights_hdesk'],
                    'profile_picture' => $harleyEmp['profile_picture'],
                    'status' => 'active'
                ];
                
                $newId = $employeeModel->create($empData);
                
                if ($newId) {
                    $created++;
                    echo "<tr class='created'>";
                    echo "<td>" . ($index + 1) . "</td>";
                    echo "<td>{$harleyEmp['id']}</td>";
                    echo "<td>$fullName</td>";
                    echo "<td>{$harleyEmp['email']}</td>";
                    echo "<td>{$harleyEmp['username']}</td>";
                    echo "<td>{$harleyEmp['role']}</td>";
                    echo "<td>{$harleyEmp['admin_rights_hdesk']}</td>";
                    echo "<td style='color:#28a745;font-weight:bold;'>✓ Created (ID: $newId)</td>";
                    echo "</tr>";
                } else {
                    $errors++;
                    echo "<tr style='background:#f8d7da;'>";
                    echo "<td>" . ($index + 1) . "</td>";
                    echo "<td>{$harleyEmp['id']}</td>";
                    echo "<td>$fullName</td>";
                    echo "<td>{$harleyEmp['email']}</td>";
                    echo "<td>{$harleyEmp['username']}</td>";
                    echo "<td>{$harleyEmp['role']}</td>";
                    echo "<td>{$harleyEmp['admin_rights_hdesk']}</td>";
                    echo "<td style='color:#dc3545;'>✗ Create returned 0</td>";
                    echo "</tr>";
                }
            } catch (Exception $e) {
                $errors++;
                echo "<tr style='background:#f8d7da;'>";
                echo "<td>" . ($index + 1) . "</td>";
                echo "<td>{$harleyEmp['id']}</td>";
                echo "<td>$fullName</td>";
                echo "<td colspan='4'>" . htmlspecialchars($e->getMessage()) . "</td>";
                echo "<td style='color:#dc3545;'>✗ Error</td>";
                echo "</tr>";
            }
        }
        
        echo '</tbody></table>';
        
        // Summary
        echo '<div class="success">';
        echo '<h2>✅ Fresh Sync Complete!</h2>';
        echo "<p><strong>Successfully created:</strong> $created employees</p>";
        if ($errors > 0) {
            echo "<p style='color:#dc3545;'><strong>Errors:</strong> $errors</p>";
        }
        echo '</div>';
        
        // Final verification
        $stmt = $localDb->query("SELECT COUNT(*) FROM employees WHERE status = 'active'");
        $finalCount = $stmt->fetchColumn();
        
        $stmt = $localDb->query("SELECT COUNT(*) FROM employees WHERE admin_rights_hdesk = 'it'");
        $itCount = $stmt->fetchColumn();
        
        $stmt = $localDb->query("SELECT COUNT(*) FROM employees WHERE admin_rights_hdesk = 'hr'");
        $hrCount = $stmt->fetchColumn();
        
        $stmt = $localDb->query("SELECT COUNT(*) FROM employees WHERE admin_rights_hdesk = 'superadmin'");
        $superCount = $stmt->fetchColumn();
        
        echo '<h2>Final Statistics:</h2>';
        echo '<table style="width:auto;">';
        echo "<tr><td><strong>Total Active Employees:</strong></td><td style='font-weight:bold;color:#28a745;'>$finalCount</td></tr>";
        echo "<tr><td><strong>IT Admins:</strong></td><td>$itCount</td></tr>";
        echo "<tr><td><strong>HR Admins:</strong></td><td>$hrCount</td></tr>";
        echo "<tr><td><strong>Super Admins:</strong></td><td>$superCount</td></tr>";
        echo '</table>';
        
        echo '<p style="margin-top: 30px;">';
        echo '<a href="admin/customers.php" class="btn" style="background:#28a745;color:white;">✓ View Employees</a>';
        echo '<a href="admin/manage_employee_rights.php" class="btn" style="background:#0d6efd;color:white;">→ Manage Admin Rights</a>';
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
