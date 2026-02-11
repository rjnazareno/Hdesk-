<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Restore Super Admin Accounts</title></head><body>";
echo "<h1>🔧 Restore Super Admin Accounts</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if there are any superadmin accounts currently
    echo "<h2>1. Current Super Admin Status</h2>";
    $sql = "SELECT COUNT(*) as superadmin_count FROM employees WHERE admin_rights_hdesk = 'superadmin'";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $currentSuperAdmins = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Current Super Admins:</strong> {$currentSuperAdmins['superadmin_count']}</p>";
    
    if ($currentSuperAdmins['superadmin_count'] == 0) {
        echo "<div style='background-color: #ffe6e6; padding: 15px; border: 1px solid red; margin: 20px 0;'>";
        echo "<h3>❌ NO SUPER ADMIN ACCOUNTS FOUND!</h3>";
        echo "<p>The reset accidentally affected superadmin accounts. Let me restore them.</p>";
        echo "</div>";
        
        // Look for likely superadmin candidates based on usernames or specific criteria
        echo "<h2>2. Searching for Super Admin Candidates</h2>";
        
        // Common superadmin usernames and employee IDs that should be superadmin
        $knownSuperAdmins = [
            ['field' => 'username', 'value' => 'admin'],
            ['field' => 'username', 'value' => 'superadmin'],
            ['field' => 'username', 'value' => 'administrator'],
            ['field' => 'employee_id', 'value' => '1'], // Usually the first employee
            ['field' => 'fname', 'value' => 'Super', 'lname' => 'Admin'],
        ];
        
        $foundCandidates = [];
        
        foreach ($knownSuperAdmins as $criteria) {
            if (isset($criteria['lname'])) {
                $sql = "SELECT id, username, fname, lname, employee_id, admin_rights_hdesk 
                        FROM employees 
                        WHERE {$criteria['field']} = :value AND lname = :lname 
                        LIMIT 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([':value' => $criteria['value'], ':lname' => $criteria['lname']]);
            } else {
                $sql = "SELECT id, username, fname, lname, employee_id, admin_rights_hdesk 
                        FROM employees 
                        WHERE {$criteria['field']} = :value 
                        LIMIT 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([':value' => $criteria['value']]);
            }
            
            $candidate = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($candidate) {
                $foundCandidates[] = $candidate;
            }
        }
        
        if (!empty($foundCandidates)) {
            echo "<h3>Found Super Admin Candidates:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Employee_ID</th><th>Current Rights</th><th>Action</th></tr>";
            
            foreach ($foundCandidates as $candidate) {
                echo "<tr>";
                echo "<td>{$candidate['id']}</td>";
                echo "<td>{$candidate['username']}</td>";
                echo "<td>{$candidate['fname']} {$candidate['lname']}</td>";
                echo "<td>{$candidate['employee_id']}</td>";
                echo "<td>{$candidate['admin_rights_hdesk']}</td>";
                echo "<td>";
                echo "<form method='POST' style='display: inline;'>";
                echo "<input type='hidden' name='restore_superadmin' value='{$candidate['id']}'>";
                echo "<input type='hidden' name='username' value='{$candidate['username']}'>";
                echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 5px 10px; cursor: pointer;'>Restore as Super Admin</button>";
                echo "</form>";
                echo "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Also provide manual form
        echo "<h3>Manual Super Admin Restoration</h3>";
        echo "<p>If you know the specific employee who should be super admin:</p>";
        echo "<form method='POST'>";
        echo "<label><strong>Employee ID:</strong> <input type='number' name='manual_superadmin_id' required></label><br><br>";
        echo "<label><strong>Username:</strong> <input type='text' name='manual_username' required></label><br><br>";
        echo "<button type='submit' style='background: #007bff; color: white; border: none; padding: 10px 20px; cursor: pointer;'>Restore as Super Admin</button>";
        echo "</form>";
        
    } else {
        echo "<div style='background-color: #e6ffe6; padding: 15px; border: 1px solid green; margin: 20px 0;'>";
        echo "<h3>✅ Super Admin Accounts are Safe</h3>";
        echo "<p>Found {$currentSuperAdmins['superadmin_count']} super admin account(s). They were not affected by the reset.</p>";
        echo "</div>";
        
        // Show current superadmins
        $sql = "SELECT id, username, fname, lname, employee_id, admin_rights_hdesk FROM employees WHERE admin_rights_hdesk = 'superadmin'";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $superAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Current Super Admin Accounts:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Employee_ID</th><th>Rights</th></tr>";
        foreach ($superAdmins as $admin) {
            echo "<tr style='background-color: #fff0e6;'>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>{$admin['username']}</td>";
            echo "<td>{$admin['fname']} {$admin['lname']}</td>";
            echo "<td>{$admin['employee_id']}</td>";
            echo "<td>{$admin['admin_rights_hdesk']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Handle POST requests for restoration
    if ($_POST) {
        if (isset($_POST['restore_superadmin'])) {
            $employeeId = intval($_POST['restore_superadmin']);
            $username = $_POST['username'];
            
            $sql = "UPDATE employees SET admin_rights_hdesk = 'superadmin', role = 'internal' WHERE id = :id";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([':id' => $employeeId]);
            
            if ($result) {
                echo "<div style='background-color: #e6ffe6; padding: 15px; border: 1px solid green; margin: 20px 0;'>";
                echo "<h3>✅ Super Admin Restored!</h3>";
                echo "<p>Successfully restored <strong>{$username}</strong> (ID: {$employeeId}) as Super Admin.</p>";
                echo "<p><a href='#' onclick='window.location.reload()'>Refresh page</a> to see updated status.</p>";
                echo "</div>";
            } else {
                echo "<div style='background-color: #ffe6e6; padding: 15px; border: 1px solid red; margin: 20px 0;'>";
                echo "<h3>❌ Restoration Failed</h3>";
                echo "<p>Failed to restore {$username} as Super Admin.</p>";
                echo "</div>";
            }
        } elseif (isset($_POST['manual_superadmin_id'])) {
            $employeeId = intval($_POST['manual_superadmin_id']);
            $username = $_POST['manual_username'];
            
            // First check if employee exists
            $sql = "SELECT id, fname, lname FROM employees WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $employeeId]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($employee) {
                $sql = "UPDATE employees SET admin_rights_hdesk = 'superadmin', role = 'internal' WHERE id = :id";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([':id' => $employeeId]);
                
                if ($result) {
                    echo "<div style='background-color: #e6ffe6; padding: 15px; border: 1px solid green; margin: 20px 0;'>";
                    echo "<h3>✅ Super Admin Restored!</h3>";
                    echo "<p>Successfully restored <strong>{$employee['fname']} {$employee['lname']}</strong> (ID: {$employeeId}) as Super Admin.</p>";
                    echo "<p><a href='#' onclick='window.location.reload()'>Refresh page</a> to see updated status.</p>";
                    echo "</div>";
                } else {
                    echo "<div style='background-color: #ffe6e6; padding: 15px; border: 1px solid red; margin: 20px 0;'>";
                    echo "<h3>❌ Restoration Failed</h3>";
                    echo "<p>Failed to restore employee as Super Admin.</p>";
                    echo "</div>";
                }
            } else {
                echo "<div style='background-color: #ffe6e6; padding: 15px; border: 1px solid red; margin: 20px 0;'>";
                echo "<h3>❌ Employee Not Found</h3>";
                echo "<p>No employee found with ID: {$employeeId}</p>";
                echo "</div>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<br><p><a href='debug_admin_rights.php'>← Back to Debug Report</a></p>";
echo "</body></html>";
?>