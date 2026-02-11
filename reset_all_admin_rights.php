<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Reset All Admin Rights</title></head><body>";
echo "<h1>Reset All Admin Rights</h1>";

// Handle POST request for confirmation
if ($_POST && isset($_POST['confirm']) && $_POST['confirm'] === 'YES_RESET_ALL_RIGHTS') {
    try {
        $db = Database::getInstance()->getConnection();
        
        echo "<h2>🔄 Resetting All Admin Rights...</h2>";
        
        // Get current state
        $sql = "SELECT COUNT(*) as total, 
                       SUM(CASE WHEN admin_rights_hdesk = 'hr' THEN 1 ELSE 0 END) as hr_count,
                       SUM(CASE WHEN admin_rights_hdesk = 'it' THEN 1 ELSE 0 END) as it_count,
                       SUM(CASE WHEN admin_rights_hdesk = 'superadmin' THEN 1 ELSE 0 END) as superadmin_count
                FROM employees";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $before = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Before Reset:</h3>";
        echo "<ul>";
        echo "<li><strong>Total Employees:</strong> {$before['total']}</li>";
        echo "<li><strong>HR Admins:</strong> {$before['hr_count']}</li>";
        echo "<li><strong>IT Admins:</strong> {$before['it_count']}</li>";
        echo "<li><strong>Super Admins:</strong> {$before['superadmin_count']}</li>";
        echo "</ul>";
        
        // Reset all admin rights (but keep superadmin protected)  
        // First, get count of current superadmins for protection
        $sqlCheck = "SELECT COUNT(*) as superadmin_count FROM employees WHERE admin_rights_hdesk = 'superadmin'";
        $stmtCheck = $db->prepare($sqlCheck);
        $stmtCheck->execute();
        $superadminCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Protecting {$superadminCheck['superadmin_count']} Super Admin accounts from reset...</strong></p>";
        
        // More explicit SQL to avoid affecting superadmin accounts
        $sql = "UPDATE employees 
                SET admin_rights_hdesk = NULL, role = 'employee' 
                WHERE (admin_rights_hdesk = 'hr' OR admin_rights_hdesk = 'it' OR admin_rights_hdesk IS NULL)
                AND admin_rights_hdesk != 'superadmin'";
        $stmt = $db->prepare($sql);
        $result = $stmt->execute();
        $affectedRows = $stmt->rowCount();
        
        // Verify superadmins are still there
        $stmtCheck->execute();
        $superadminAfter = $stmtCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($superadminAfter['superadmin_count'] != $superadminCheck['superadmin_count']) {
            echo "<p style='color: red;'>⚠️ WARNING: Super Admin count changed from {$superadminCheck['superadmin_count']} to {$superadminAfter['superadmin_count']}!</p>";
            echo "<p><a href='restore_superadmin.php'>🔧 Click here to restore Super Admin accounts</a></p>";
        } else {
            echo "<p style='color: green;'>✅ Super Admin accounts protected successfully.</p>";
        }
        
        if ($result) {
            echo "<p style='color: green;'>✅ <strong>SUCCESS!</strong> Reset {$affectedRows} employee admin rights.</p>";
            
            // Get state after reset
            $sql = "SELECT COUNT(*) as total, 
                           SUM(CASE WHEN admin_rights_hdesk = 'hr' THEN 1 ELSE 0 END) as hr_count,
                           SUM(CASE WHEN admin_rights_hdesk = 'it' THEN 1 ELSE 0 END) as it_count,
                           SUM(CASE WHEN admin_rights_hdesk = 'superadmin' THEN 1 ELSE 0 END) as superadmin_count,
                           SUM(CASE WHEN admin_rights_hdesk IS NULL THEN 1 ELSE 0 END) as regular_count
                    FROM employees";
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $after = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h3>After Reset:</h3>";
            echo "<ul>";
            echo "<li><strong>Total Employees:</strong> {$after['total']}</li>";
            echo "<li><strong>Regular Employees:</strong> {$after['regular_count']}</li>";
            echo "<li><strong>HR Admins:</strong> {$after['hr_count']}</li>";
            echo "<li><strong>IT Admins:</strong> {$after['it_count']}</li>";
            echo "<li><strong>Super Admins:</strong> {$after['superadmin_count']}</li>";
            echo "</ul>";
            
            echo "<div style='background-color: #e6ffe6; padding: 15px; border: 1px solid green; margin: 20px 0;'>";
            echo "<h3>✅ Admin Rights Reset Complete!</h3>";
            echo "<p><strong>Next Steps:</strong></p>";
            echo "<ol>";
            echo "<li><strong>Run Fresh Sync:</strong> <a href='fresh_sync_from_harley.php'>fresh_sync_from_harley.php</a> to get clean employee data</li>";
            echo "<li><strong>Manually assign admin rights:</strong> Go to <a href='admin/manage_employee_rights.php'>Manage Employee Rights</a></li>";
            echo "<li><strong>Test individual assignment:</strong> Use <a href='test_admin_rights.php'>test_admin_rights.php</a> to verify no bulk changes</li>";
            echo "</ol>";
            echo "</div>";
            
        } else {
            echo "<p style='color: red;'>❌ <strong>ERROR:</strong> Failed to reset admin rights.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ <strong>ERROR:</strong> " . $e->getMessage() . "</p>";
    }
    
} else {
    // Show confirmation form
    try {
        $db = Database::getInstance()->getConnection();
        
        echo "<div style='background-color: #fff3cd; padding: 15px; border: 1px solid orange; margin: 20px 0;'>";
        echo "<h2>⚠️ CRITICAL ISSUE DETECTED</h2>";
        echo "<p>The debug report shows <strong>115 employees all have ID = 0</strong> and <strong>all have HR admin rights</strong>.</p>";
        echo "<p>This is the exact cause of the bulk assignment bug - when you update admin rights for ID = 0, it affects ALL 115 employees.</p>";
        echo "</div>";
        
        // Show current state
        $sql = "SELECT admin_rights_hdesk, COUNT(*) as count FROM employees GROUP BY admin_rights_hdesk ORDER BY count DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Current Admin Rights Distribution</h2>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Admin Rights</th><th>Count</th></tr>";
        foreach ($distribution as $dist) {
            $bgcolor = '';
            if ($dist['admin_rights_hdesk'] === 'hr') $bgcolor = 'background-color: #ffe6e6;';
            elseif ($dist['admin_rights_hdesk'] === 'it') $bgcolor = 'background-color: #e6f3ff;';
            elseif ($dist['admin_rights_hdesk'] === 'superadmin') $bgcolor = 'background-color: #fff0e6;';
            
            echo "<tr style='{$bgcolor}'><td>" . ($dist['admin_rights_hdesk'] ?: 'Regular Employee') . "</td><td>{$dist['count']}</td></tr>";
        }
        echo "</table>";
        
        echo "<h2>🔄 Reset All Admin Rights</h2>";
        echo "<p>This will:</p>";
        echo "<ul>";
        echo "<li>✅ Reset ALL admin rights to NULL (regular employee)</li>";
        echo "<li>✅ Set role back to 'employee' for non-admin users</li>";
        echo "<li>🔒 <strong>PRESERVE Super Admin accounts</strong> (protected)</li>";
        echo "<li>🧹 Clean slate for proper admin assignment</li>";
        echo "</ul>";
        
        echo '<form method="POST" onsubmit="return confirm(\'Are you ABSOLUTELY sure you want to reset ALL admin rights? This cannot be undone!\')">';
        echo '<div style="background-color: #f8f9fa; padding: 15px; border: 1px solid #ddd; margin: 20px 0;">';
        echo '<label>';
        echo '<input type="checkbox" required> I understand this will reset ALL employee admin rights except Super Admin';
        echo '</label><br><br>';
        echo '<label>';
        echo '<strong>Type "YES_RESET_ALL_RIGHTS" to confirm:</strong><br>';
        echo '<input type="text" name="confirm" required placeholder="YES_RESET_ALL_RIGHTS" style="padding: 8px; width: 300px;">';
        echo '</label>';
        echo '</div>';
        echo '<button type="submit" style="padding: 15px 30px; background: #dc3545; color: white; border: none; font-size: 16px; cursor: pointer;">🔄 RESET ALL ADMIN RIGHTS</button>';
        echo '</form>';
        
        echo "<h3>⚡ Quick Links After Reset:</h3>";
        echo "<ul>";
        echo "<li><a href='fresh_sync_from_harley.php'>🔄 Fresh Sync from Harley</a> - Get clean employee data</li>";
        echo "<li><a href='debug_admin_rights.php'>🔍 Debug Admin Rights</a> - Check results</li>";
        echo "<li><a href='admin/manage_employee_rights.php'>👑 Manage Employee Rights</a> - Assign admin rights properly</li>";
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error checking current state: " . $e->getMessage() . "</p>";
    }
}

echo "</body></html>";
?>