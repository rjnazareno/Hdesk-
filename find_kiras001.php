<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Find Employee: kiras001</title></head><body>";
echo "<h1>🔍 Search Results for Username: kiras001</h1>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Search for exact username match
    echo "<h2>Exact Username Match</h2>";
    $sql = "SELECT id, username, fname, lname, email, employee_id, admin_rights_hdesk, role, status, created_at 
            FROM employees 
            WHERE username = :username";
    $stmt = $db->prepare($sql);
    $stmt->execute([':username' => 'kiras001']);
    $exactMatch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($exactMatch) {
        echo "<div style='background-color: #e6ffe6; padding: 15px; border: 1px solid green; margin: 20px 0;'>";
        echo "<h3>✅ Found Employee: kiras001</h3>";
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td><strong>ID</strong></td><td style='background-color: " . ($exactMatch['id'] == 0 ? '#ffe6e6' : '#e6ffe6') . ";'>{$exactMatch['id']}</td></tr>";
        echo "<tr><td><strong>Username</strong></td><td>{$exactMatch['username']}</td></tr>";
        echo "<tr><td><strong>Full Name</strong></td><td>{$exactMatch['fname']} {$exactMatch['lname']}</td></tr>";
        echo "<tr><td><strong>Email</strong></td><td>{$exactMatch['email']}</td></tr>";
        echo "<tr><td><strong>Employee ID</strong></td><td>{$exactMatch['employee_id']}</td></tr>";
        echo "<tr><td><strong>Admin Rights</strong></td><td>";
        if ($exactMatch['admin_rights_hdesk']) {
            echo "<span style='background-color: #fff3cd; padding: 2px 6px; border-radius: 3px;'>{$exactMatch['admin_rights_hdesk']}</span>";
        } else {
            echo "<span style='color: #666;'>Regular Employee</span>";
        }
        echo "</td></tr>";
        echo "<tr><td><strong>Role</strong></td><td>{$exactMatch['role']}</td></tr>";
        echo "<tr><td><strong>Status</strong></td><td><span style='color: " . ($exactMatch['status'] === 'active' ? 'green' : 'red') . ";'>{$exactMatch['status']}</span></td></tr>";
        echo "<tr><td><strong>Created</strong></td><td>{$exactMatch['created_at']}</td></tr>";
        echo "</table>";
        echo "</div>";
        
        // Quick actions for this employee
        echo "<h3>🔧 Quick Actions for kiras001</h3>";
        echo "<div style='background-color: #f8f9fa; padding: 15px; border: 1px solid #ddd;'>";
        
        if ($exactMatch['admin_rights_hdesk'] === 'superadmin') {
            echo "<p style='color: green;'><strong>✅ This is a Super Admin account</strong></p>";
            echo "<p>Super Admin rights are already assigned and protected.</p>";
        } else {
            echo "<form method='POST' style='display: inline-block; margin-right: 10px;'>";
            echo "<input type='hidden' name='employee_id' value='{$exactMatch['id']}'>";
            echo "<input type='hidden' name='action' value='make_superadmin'>";
            echo "<button type='submit' style='background: #dc3545; color: white; border: none; padding: 10px 15px; cursor: pointer;'>🔑 Make Super Admin</button>";
            echo "</form>";
            
            echo "<form method='POST' style='display: inline-block; margin-right: 10px;'>";
            echo "<input type='hidden' name='employee_id' value='{$exactMatch['id']}'>";
            echo "<input type='hidden' name='action' value='make_it_admin'>";
            echo "<button type='submit' style='background: #007bff; color: white; border: none; padding: 10px 15px; cursor: pointer;'>💼 Make IT Admin</button>";
            echo "</form>";
            
            echo "<form method='POST' style='display: inline-block;'>";
            echo "<input type='hidden' name='employee_id' value='{$exactMatch['id']}'>";
            echo "<input type='hidden' name='action' value='make_hr_admin'>";
            echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 10px 15px; cursor: pointer;'>👥 Make HR Admin</button>";
            echo "</form>";
        }
        echo "</div>";
        
    } else {
        echo "<div style='background-color: #ffe6e6; padding: 15px; border: 1px solid red; margin: 20px 0;'>";
        echo "<h3>❌ No Exact Match Found</h3>";
        echo "<p>No employee found with username: <strong>kiras001</strong></p>";
        echo "</div>";
    }
    
    // Search for similar usernames (fuzzy search)
    echo "<h2>Similar Usernames</h2>";
    $sql = "SELECT id, username, fname, lname, admin_rights_hdesk 
            FROM employees 
            WHERE username LIKE :username 
            ORDER BY username";
    $stmt = $db->prepare($sql);
    $stmt->execute([':username' => '%kiras%']);
    $similarMatches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($similarMatches)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Name</th><th>Admin Rights</th></tr>";
        foreach ($similarMatches as $match) {
            $highlight = ($match['username'] === 'kiras001') ? 'background-color: #e6ffe6;' : '';
            echo "<tr style='{$highlight}'>";
            echo "<td>{$match['id']}</td>";
            echo "<td><strong>{$match['username']}</strong></td>";
            echo "<td>{$match['fname']} {$match['lname']}</td>";
            echo "<td>{$match['admin_rights_hdesk']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No similar usernames found containing 'kiras'.</p>";
    }
    
    // Handle form submissions
    if ($_POST && isset($_POST['action'])) {
        $employeeId = intval($_POST['employee_id']);
        $action = $_POST['action'];
        
        $adminRight = null;
        $actionName = '';
        
        switch ($action) {
            case 'make_superadmin':
                $adminRight = 'superadmin';
                $actionName = 'Super Admin';
                break;
            case 'make_it_admin':
                $adminRight = 'it';
                $actionName = 'IT Admin';
                break;
            case 'make_hr_admin':
                $adminRight = 'hr';
                $actionName = 'HR Admin';
                break;
        }
        
        if ($adminRight) {
            $sql = "UPDATE employees SET admin_rights_hdesk = :admin_right, role = 'internal' WHERE id = :id";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([':admin_right' => $adminRight, ':id' => $employeeId]);
            
            if ($result) {
                echo "<div style='background-color: #e6ffe6; padding: 15px; border: 1px solid green; margin: 20px 0;'>";
                echo "<h3>✅ Success!</h3>";
                echo "<p>Employee ID {$employeeId} (kiras001) has been granted <strong>{$actionName}</strong> rights.</p>";
                echo "<p><a href='#' onclick='window.location.reload()'>Refresh page</a> to see updated rights.</p>";
                echo "</div>";
            } else {
                echo "<div style='background-color: #ffe6e6; padding: 15px; border: 1px solid red; margin: 20px 0;'>";
                echo "<h3>❌ Failed</h3>";
                echo "<p>Failed to update admin rights for employee ID {$employeeId}.</p>";
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