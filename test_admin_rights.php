<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'models/Employee.php';

// Handle POST request (simulate the admin rights assignment)
if ($_POST) {
    $response = ['success' => false, 'message' => ''];
    
    try {
        $employeeId = $_POST['employee_id'] ?? null;
        $adminRights = $_POST['admin_rights'] ?? null;
        
        if (!$employeeId || !$adminRights) {
            throw new Exception('Missing employee ID or admin rights value');
        }
        
        echo "<h3>Testing Admin Rights Assignment</h3>";
        echo "<p><strong>Employee ID:</strong> {$employeeId}</p>";
        echo "<p><strong>Admin Rights:</strong> {$adminRights}</p>";
        
        // Check current state before update
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT id, fname, lname, admin_rights_hdesk FROM employees WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->execute([':id' => $employeeId]);
        $beforeEmployee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$beforeEmployee) {
            throw new Exception("Employee with ID {$employeeId} not found");
        }
        
        echo "<p><strong>Before Update:</strong> {$beforeEmployee['fname']} {$beforeEmployee['lname']} - {$beforeEmployee['admin_rights_hdesk']}</p>";
        
        // Test the actual update using Employee model
        $employee = new Employee();
        $updateData = ['admin_rights_hdesk' => $adminRights];
        $updateResult = $employee->update($employeeId, $updateData);
        
        if ($updateResult) {
            // Check result after update
            $stmt->execute([':id' => $employeeId]);
            $afterEmployee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p style='color: green;'><strong>After Update:</strong> {$afterEmployee['fname']} {$afterEmployee['lname']} - {$afterEmployee['admin_rights_hdesk']}</p>";
            
            // Check if OTHER employees were affected (this is the bug we're looking for)
            $sql = "SELECT COUNT(*) as count FROM employees WHERE admin_rights_hdesk = :rights AND id != :id";
            $stmt = $db->prepare($sql);
            $stmt->execute([':rights' => $adminRights, ':id' => $employeeId]);
            $othersWithSameRights = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Other employees with {$adminRights} rights:</strong> {$othersWithSameRights['count']}</p>";
            
            // Show recent employees with this right (excluding the one we just updated)
            $sql = "SELECT id, fname, lname, username, admin_rights_hdesk, updated_at 
                    FROM employees 
                    WHERE admin_rights_hdesk = :rights AND id != :id 
                    ORDER BY updated_at DESC 
                    LIMIT 5";
            $stmt = $db->prepare($sql);
            $stmt->execute([':rights' => $adminRights, ':id' => $employeeId]);
            $otherEmployees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($otherEmployees)) {
                echo "<p><strong>Other employees with {$adminRights} rights (last 5):</strong></p>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Rights</th><th>Updated</th></tr>";
                foreach ($otherEmployees as $emp) {
                    echo "<tr>";
                    echo "<td>{$emp['id']}</td>";
                    echo "<td>{$emp['fname']} {$emp['lname']}</td>";
                    echo "<td>{$emp['username']}</td>";
                    echo "<td>{$emp['admin_rights_hdesk']}</td>";
                    echo "<td>{$emp['updated_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            $response = ['success' => true, 'message' => 'Rights updated successfully'];
        } else {
            throw new Exception('Update failed');
        }
        
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e->getMessage()];
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
    if ($_POST['ajax'] ?? false) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Show form to test admin rights assignment
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Admin Rights Assignment</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-group { margin: 10px 0; }
        .form-group label { display: block; margin-bottom: 5px; }
        .form-group input, .form-group select { padding: 8px; width: 200px; }
        .btn { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; margin: 5px; }
        .btn:hover { background: #005a87; }
    </style>
</head>
<body>
    <h1>Test Admin Rights Assignment</h1>
    <p>This page tests the admin rights assignment functionality to help identify the bulk assignment bug.</p>
    
    <h2>Current Employees Sample</h2>
    <?php
    try {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT id, fname, lname, username, admin_rights_hdesk FROM employees ORDER BY id LIMIT 10";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Current Rights</th></tr>";
        foreach ($employees as $emp) {
            echo "<tr>";
            echo "<td>{$emp['id']}</td>";
            echo "<td>{$emp['fname']} {$emp['lname']}</td>";
            echo "<td>{$emp['username']}</td>";
            echo "<td>{$emp['admin_rights_hdesk']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error loading employees: " . $e->getMessage() . "</p>";
    }
    ?>
    
    <h2>Test Admin Rights Assignment</h2>
    <form method="POST">
        <div class="form-group">
            <label>Employee ID:</label>
            <input type="number" name="employee_id" required placeholder="Enter employee ID">
        </div>
        <div class="form-group">
            <label>Admin Rights:</label>
            <select name="admin_rights" required>
                <option value="">-- Select --</option>
                <option value="hr">HR Admin</option>
                <option value="it">IT Admin</option>
                <option value="superadmin">Super Admin</option>
                <option value="">Regular Employee (no admin)</option>
            </select>
        </div>
        <button type="submit" class="btn">Test Update</button>
    </form>
    
    <h3>Instructions:</h3>
    <ol>
        <li>Choose an employee ID from the table above</li>
        <li>Select the admin rights you want to assign</li>
        <li>Click "Test Update" to see what happens</li>
        <li>Check if only that employee was updated, or if others were affected too</li>
    </ol>
    
    <p><a href="debug_admin_rights.php">View Admin Rights Debug Report</a></p>
    
</body>
</html>