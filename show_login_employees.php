<?php
/**
 * Show All Employees Who Can Log In
 * Lists employees with login credentials
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');

$db = Database::getInstance()->getConnection();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employees with Login Access</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; }
        .stats { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-box { display: inline-block; margin: 10px 20px; padding: 15px 25px; background: #007acc; color: white; border-radius: 5px; }
        .stat-box h2 { margin: 0; font-size: 36px; }
        .stat-box p { margin: 5px 0 0 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0; }
        th { background: #007acc; color: white; padding: 12px; text-align: left; position: sticky; top: 0; }
        td { padding: 10px 12px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f0f8ff; }
        .active { color: green; font-weight: bold; }
        .inactive { color: red; }
        .admin-badge { background: #ffc107; color: #000; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .search-box { background: white; padding: 15px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input[type="text"] { padding: 8px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
    <script>
        function filterTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('employeeTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            }
        }
    </script>
</head>
<body>
    <h1>👥 Employees with Login Access</h1>
    
    <?php
    // Get statistics
    $stats = [];
    
    // Total employees
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees");
    $stats['total'] = $stmt->fetch()['count'];
    
    // Active employees
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'");
    $stats['active'] = $stmt->fetch()['count'];
    
    // Employees with username (can log in)
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees WHERE username IS NOT NULL AND username != '' AND status = 'active'");
    $stats['can_login'] = $stmt->fetch()['count'];
    
    // Admins
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees WHERE admin_rights_hdesk = 1 AND status = 'active'");
    $stats['admins'] = $stmt->fetch()['count'];
    
    echo '<div class="stats">';
    echo '<div class="stat-box"><h2>' . $stats['total'] . '</h2><p>Total Employees</p></div>';
    echo '<div class="stat-box"><h2>' . $stats['active'] . '</h2><p>Active</p></div>';
    echo '<div class="stat-box" style="background: #28a745;"><h2>' . $stats['can_login'] . '</h2><p>Can Log In</p></div>';
    echo '<div class="stat-box" style="background: #ffc107; color: #000;"><h2>' . $stats['admins'] . '</h2><p>Admins</p></div>';
    echo '</div>';
    
    echo '<div class="search-box">';
    echo '<input type="text" id="searchInput" onkeyup="filterTable()" placeholder="Search by name, email, username, position...">';
    echo '</div>';
    
    // Get all employees who can log in
    $sql = "SELECT 
                id,
                fname,
                lname,
                email,
                personal_email,
                username,
                position,
                company,
                contact,
                status,
                admin_rights_hdesk,
                created_at
            FROM employees 
            WHERE username IS NOT NULL 
            AND username != ''
            ORDER BY 
                status = 'active' DESC,
                admin_rights_hdesk DESC,
                lname, 
                fname";
    
    $stmt = $db->query($sql);
    $employees = $stmt->fetchAll();
    
    if (empty($employees)) {
        echo '<p>No employees with login credentials found.</p>';
    } else {
        echo '<table id="employeeTable">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>#</th>';
        echo '<th>Name</th>';
        echo '<th>Username</th>';
        echo '<th>Email</th>';
        echo '<th>Position</th>';
        echo '<th>Company</th>';
        echo '<th>Contact</th>';
        echo '<th>Status</th>';
        echo '<th>Admin</th>';
        echo '<th>Created</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($employees as $index => $emp) {
            $statusClass = $emp['status'] === 'active' ? 'active' : 'inactive';
            $rowStyle = $emp['status'] !== 'active' ? 'opacity: 0.5;' : '';
            
            echo '<tr style="' . $rowStyle . '">';
            echo '<td>' . ($index + 1) . '</td>';
            echo '<td><strong>' . htmlspecialchars($emp['fname'] . ' ' . $emp['lname']) . '</strong></td>';
            echo '<td><code>' . htmlspecialchars($emp['username']) . '</code></td>';
            echo '<td>' . htmlspecialchars($emp['email']) . '</td>';
            echo '<td>' . htmlspecialchars($emp['position']) . '</td>';
            echo '<td>' . htmlspecialchars($emp['company']) . '</td>';
            echo '<td>' . htmlspecialchars($emp['contact'] ?? 'N/A') . '</td>';
            echo '<td class="' . $statusClass . '">' . strtoupper($emp['status']) . '</td>';
            echo '<td>' . ($emp['admin_rights_hdesk'] == 1 ? '<span class="admin-badge">ADMIN</span>' : '-') . '</td>';
            echo '<td>' . date('Y-m-d', strtotime($emp['created_at'])) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    // Show employees WITHOUT login credentials
    echo '<h2 style="margin-top: 40px;">❌ Employees WITHOUT Login Access</h2>';
    
    $sqlNoLogin = "SELECT 
                id,
                fname,
                lname,
                email,
                position,
                company,
                status
            FROM employees 
            WHERE (username IS NULL OR username = '')
            AND status = 'active'
            ORDER BY lname, fname";
    
    $stmtNoLogin = $db->query($sqlNoLogin);
    $noLoginEmployees = $stmtNoLogin->fetchAll();
    
    if (empty($noLoginEmployees)) {
        echo '<p style="color: green;">✓ All active employees have login credentials!</p>';
    } else {
        echo '<p style="color: orange;">⚠ ' . count($noLoginEmployees) . ' active employee(s) cannot log in (no username set)</p>';
        echo '<table>';
        echo '<thead>';
        echo '<tr>';
        echo '<th>#</th>';
        echo '<th>Name</th>';
        echo '<th>Email</th>';
        echo '<th>Position</th>';
        echo '<th>Company</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($noLoginEmployees as $index => $emp) {
            echo '<tr>';
            echo '<td>' . ($index + 1) . '</td>';
            echo '<td>' . htmlspecialchars($emp['fname'] . ' ' . $emp['lname']) . '</td>';
            echo '<td>' . htmlspecialchars($emp['email']) . '</td>';
            echo '<td>' . htmlspecialchars($emp['position']) . '</td>';
            echo '<td>' . htmlspecialchars($emp['company']) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    ?>
    
    <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
        <strong>📌 Note:</strong> Only employees with a <code>username</code> can log in to the IT Help Desk system.
        Usernames and passwords are synced from the Harley HRIS database.
    </div>
    
</body>
</html>
