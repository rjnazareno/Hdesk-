<?php
/**
 * Find Missing Employees - Compare Harley vs IT Help Desk
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/harley_config.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Missing Employees Check</title>
    <style>
        body { font-family: -apple-system, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
        h1 { color: #0d6efd; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { border: 1px solid #dee2e6; padding: 8px; text-align: left; font-size: 13px; }
        th { background: #f8f9fa; font-weight: 600; }
        .missing { background: #fff3cd; }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 20px 0; }
        .stat-card { padding: 15px; border: 1px solid #dee2e6; border-radius: 5px; text-align: center; }
        .stat-card h3 { margin: 0; font-size: 32px; color: #0d6efd; }
    </style>
</head>
<body>
<div class="container">
<h1>🔍 Find Missing Employees (Harley vs IT Help Desk)</h1>

<?php

try {
    $harleyDb = getHarleyConnection();
    $localDb = Database::getInstance()->getConnection();
    
    // Get all active employees from Harley
    $sql = "SELECT id, CONCAT(fname, ' ', lname) as full_name, fname, lname, email, role, admin_rights_hdesk 
            FROM employees 
            WHERE status = 'active'
            ORDER BY lname, fname";
    
    $stmt = $harleyDb->prepare($sql);
    $stmt->execute();
    $harleyEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all active employees from IT Help Desk
    $sql = "SELECT employee_id, id, CONCAT(fname, ' ', lname) as full_name, fname, lname, email, role, admin_rights_hdesk 
            FROM employees 
            WHERE status = 'active'
            ORDER BY lname, fname";
    
    $stmt = $localDb->prepare($sql);
    $stmt->execute();
    $localEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create lookup array by employee_id (Harley ID)
    // Note: Local database stores employee_id as numbers (1, 2, 3...) from old records
    // or as strings like "HRLY-1", "HRLY-2" from new synced records
    $localByHarleyId = [];
    foreach ($localEmployees as $emp) {
        if ($emp['employee_id']) {
            // Store by both formats to handle old and new records
            $localByHarleyId[$emp['employee_id']] = $emp;
            
            // If it starts with HRLY-, also store by numeric ID
            if (strpos($emp['employee_id'], 'HRLY-') === 0) {
                $numericId = str_replace('HRLY-', '', $emp['employee_id']);
                $localByHarleyId[$numericId] = $emp;
            }
        }
    }
    
    // Find missing employees
    $missing = [];
    foreach ($harleyEmployees as $harleyEmp) {
        // Check both the numeric ID and the HRLY- prefixed version
        $harleyId = $harleyEmp['id'];
        $harleyIdPrefixed = 'HRLY-' . $harleyId;
        
        if (!isset($localByHarleyId[$harleyId]) && !isset($localByHarleyId[$harleyIdPrefixed])) {
            $missing[] = $harleyEmp;
        }
    }
    
    // Stats
    echo '<div class="stats">';
    echo "<div class='stat-card'><h3>" . count($harleyEmployees) . "</h3><p>Harley Employees</p></div>";
    echo "<div class='stat-card'><h3>" . count($localEmployees) . "</h3><p>IT Help Desk Employees</p></div>";
    echo "<div class='stat-card' style='border-color: " . (count($missing) > 0 ? '#dc3545' : '#28a745') . ";'><h3 style='color:" . (count($missing) > 0 ? '#dc3545' : '#28a745') . ";'>" . count($missing) . "</h3><p>Missing Employees</p></div>";
    echo '</div>';
    
    if (empty($missing)) {
        echo '<div style="background: #d4edda; padding: 20px; border-radius: 5px;">';
        echo '<h2 style="color: #155724; margin-top: 0;">✅ All employees synced!</h2>';
        echo '<p>All ' . count($harleyEmployees) . ' employees from Harley exist in IT Help Desk.</p>';
        echo '</div>';
    } else {
        echo '<div style="background: #fff3cd; padding: 20px; border-radius: 5px; margin-bottom: 20px;">';
        echo '<h2 style="color: #856404; margin-top: 0;">⚠️ ' . count($missing) . ' Missing Employee(s)</h2>';
        echo '<p>These employees exist in Harley but not in IT Help Desk:</p>';
        echo '</div>';
        
        echo '<table>';
        echo '<thead><tr><th>Harley ID</th><th>Name</th><th>Email</th><th>Role</th><th>Admin Rights</th></tr></thead>';
        echo '<tbody>';
        foreach ($missing as $emp) {
            echo "<tr class='missing'>";
            echo "<td><strong>{$emp['id']}</strong></td>";
            echo "<td>{$emp['full_name']}</td>";
            echo "<td>{$emp['email']}</td>";
            echo "<td>{$emp['role']}</td>";
            echo "<td>{$emp['admin_rights_hdesk']}</td>";
            echo "</tr>";
        }
        echo '</tbody></table>';
    }
    
    // Search for specific name
    if (!empty($missing)) {
        echo '<h2>Search for Specific Employee</h2>';
        $searchName = 'patacsil'; // Looking for Bon Patacsil
        
        foreach ($harleyEmployees as $emp) {
            if (stripos($emp['full_name'], $searchName) !== false || stripos($emp['email'], $searchName) !== false) {
                $inLocal = isset($localByHarleyId[$emp['id']]);
                echo '<div style="background: ' . ($inLocal ? '#d4edda' : '#f8d7da') . '; padding: 15px; border-radius: 5px; margin: 10px 0;">';
                echo '<h3 style="margin-top:0;">' . ($inLocal ? '✅' : '❌') . ' ' . htmlspecialchars($emp['full_name']) . '</h3>';
                echo '<p><strong>Harley ID:</strong> ' . $emp['id'] . '</p>';
                echo '<p><strong>Email:</strong> ' . ($emp['email'] ?: 'N/A') . '</p>';
                echo '<p><strong>Role:</strong> ' . $emp['role'] . '</p>';
                echo '<p><strong>Admin Rights:</strong> ' . ($emp['admin_rights_hdesk'] ?: 'None') . '</p>';
                echo '<p><strong>Status:</strong> ' . ($inLocal ? 'EXISTS in IT Help Desk' : 'MISSING from IT Help Desk') . '</p>';
                echo '</div>';
            }
        }
    }
    
    echo '<p style="margin-top: 30px;">';
    echo '<a href="sync_employees_from_harley.php" style="display:inline-block;padding:12px 24px;background:#0d6efd;color:white;text-decoration:none;border-radius:5px;font-weight:600;">🔄 Run Employee Sync</a>';
    echo '</p>';
    
} catch (Exception $e) {
    echo '<div style="background: #f8d7da; padding: 20px; border-radius: 5px;">';
    echo '<h2 style="color: #721c24; margin-top: 0;">❌ Error</h2>';
    echo '<p><strong>Message:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p><strong>File:</strong> ' . $e->getFile() . ' (Line ' . $e->getLine() . ')</p>';
    echo '</div>';
}

?>

</div>
</body>
</html>
