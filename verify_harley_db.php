<?php
/**
 * Verify Harley Database Connection
 * Confirms we're connecting to the live Harley HRIS database
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/harley_config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Harley Database Verification</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .info { color: blue; font-weight: bold; }
        .section { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #007acc; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #007acc; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Harley HRIS Database Verification</h1>
    
    <?php
    echo '<div class="section">';
    echo '<h2>Current Configuration</h2>';
    echo '<table>';
    echo '<tr><th>Setting</th><th>Value</th></tr>';
    echo '<tr><td>Database Host</td><td class="info">' . HARLEY_DB_HOST . '</td></tr>';
    echo '<tr><td>Database Name</td><td class="info">' . HARLEY_DB_NAME . '</td></tr>';
    echo '<tr><td>Database User</td><td class="info">' . HARLEY_DB_USER . '</td></tr>';
    echo '<tr><td>Password Set</td><td class="pass">✓ Yes (' . strlen(HARLEY_DB_PASS) . ' characters)</td></tr>';
    echo '<tr><td>Sync Enabled</td><td class="' . (HARLEY_SYNC_ENABLED ? 'pass' : 'fail') . '">' . (HARLEY_SYNC_ENABLED ? '✓ Yes' : '✗ No') . '</td></tr>';
    echo '<tr><td>Employees Table</td><td>' . HARLEY_EMPLOYEES_TABLE . '</td></tr>';
    echo '</table>';
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>Connection Test</h2>';
    
    try {
        $harleyDb = getHarleyConnection();
        
        if ($harleyDb) {
            echo '<p class="pass">✓ Successfully connected to Harley database!</p>';
            
            // Test query to get employee count
            echo '<h3>Employee Statistics:</h3>';
            
            // Total employees
            $stmt = $harleyDb->query("SELECT COUNT(*) as total FROM " . HARLEY_EMPLOYEES_TABLE);
            $totalResult = $stmt->fetch();
            echo '<p>Total Employees: <strong>' . $totalResult['total'] . '</strong></p>';
            
            // Active employees
            $stmt = $harleyDb->query("SELECT COUNT(*) as active FROM " . HARLEY_EMPLOYEES_TABLE . " WHERE status = 'active'");
            $activeResult = $stmt->fetch();
            echo '<p>Active Employees: <strong class="pass">' . $activeResult['active'] . '</strong></p>';
            
            // Inactive employees
            $stmt = $harleyDb->query("SELECT COUNT(*) as inactive FROM " . HARLEY_EMPLOYEES_TABLE . " WHERE status = 'inactive'");
            $inactiveResult = $stmt->fetch();
            echo '<p>Inactive Employees: <strong>' . $inactiveResult['inactive'] . '</strong></p>';
            
            // Recent employees (last 30 days)
            $stmt = $harleyDb->query("SELECT COUNT(*) as recent FROM " . HARLEY_EMPLOYEES_TABLE . " WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $recentResult = $stmt->fetch();
            echo '<p>New Employees (Last 30 Days): <strong>' . $recentResult['recent'] . '</strong></p>';
            
            // Sample employees
            echo '<h3>Sample Employees (First 10 Active):</h3>';
            $stmt = $harleyDb->query("SELECT id, fname, lname, email, position, company, status FROM " . HARLEY_EMPLOYEES_TABLE . " WHERE status = 'active' ORDER BY lname, fname LIMIT 10");
            $employees = $stmt->fetchAll();
            
            if (!empty($employees)) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Name</th><th>Email</th><th>Position</th><th>Company</th><th>Status</th></tr>';
                foreach ($employees as $emp) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($emp['id']) . '</td>';
                    echo '<td>' . htmlspecialchars($emp['fname'] . ' ' . $emp['lname']) . '</td>';
                    echo '<td>' . htmlspecialchars($emp['email']) . '</td>';
                    echo '<td>' . htmlspecialchars($emp['position']) . '</td>';
                    echo '<td>' . htmlspecialchars($emp['company']) . '</td>';
                    echo '<td><span class="pass">' . htmlspecialchars($emp['status']) . '</span></td>';
                    echo '</tr>';
                }
                echo '</table>';
            }
            
            // Check for admin_rights_hdesk column
            echo '<h3>Table Structure Verification:</h3>';
            $stmt = $harleyDb->query("DESCRIBE " . HARLEY_EMPLOYEES_TABLE);
            $columns = $stmt->fetchAll();
            $columnNames = array_column($columns, 'Field');
            
            $requiredColumns = ['id', 'fname', 'lname', 'email', 'username', 'password', 'status', 'admin_rights_hdesk'];
            foreach ($requiredColumns as $col) {
                if (in_array($col, $columnNames)) {
                    echo '<p class="pass">✓ Column: ' . $col . '</p>';
                } else {
                    echo '<p class="fail">✗ Missing column: ' . $col . '</p>';
                }
            }
            
        } else {
            echo '<p class="fail">✗ Failed to connect to Harley database</p>';
            echo '<p>Check the credentials in config/harley_config.php</p>';
        }
        
    } catch (Exception $e) {
        echo '<p class="fail">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>Confirmation</h2>';
    if (HARLEY_DB_NAME === 'u816220874_calendartype' && HARLEY_DB_HOST === 'srv1866.hstgr.io') {
        echo '<p class="pass">✓✓✓ CONFIRMED: System is configured to use LIVE Harley database (u816220874_calendartype)</p>';
    } else {
        echo '<p class="fail">✗ WARNING: Not using expected live database configuration!</p>';
    }
    echo '</div>';
    ?>
    
</body>
</html>
