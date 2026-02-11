<?php
/**
 * Ticket Creation Debug Script
 * Run this on production to see detailed error information
 */

// Enable error display temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ticket Creation Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .section { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #333; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Ticket Creation Debugging</h1>
    
    <?php
    // Test 1: Database Connection
    echo '<div class="section">';
    echo '<h2>1. Database Connection</h2>';
    try {
        $db = Database::getInstance()->getConnection();
        echo '<p class="pass">✓ Connected to database: ' . DB_NAME . '</p>';
        echo '<p>Host: ' . DB_HOST . '</p>';
        echo '<p>User: ' . DB_USER . '</p>';
    } catch (Exception $e) {
        echo '<p class="fail">✗ Database Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        die('</div></body></html>');
    }
    echo '</div>';
    
    // Test 2: Check tickets table
    echo '<div class="section">';
    echo '<h2>2. Tickets Table Structure</h2>';
    try {
        $stmt = $db->query("DESCRIBE tickets");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $requiredCols = ['id', 'ticket_number', 'title', 'description', 'category_id', 
                        'department_id', 'priority', 'status', 'submitter_id', 
                        'submitter_type', 'assigned_to', 'attachments', 'created_at'];
        
        $foundCols = array_column($columns, 'Field');
        
        foreach ($requiredCols as $col) {
            if (in_array($col, $foundCols)) {
                echo "<p class='pass'>✓ $col</p>";
            } else {
                echo "<p class='fail'>✗ MISSING: $col</p>";
            }
        }
    } catch (Exception $e) {
        echo '<p class="fail">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // Test 3: Check categories
    echo '<div class="section">';
    echo '<h2>3. Categories</h2>';
    try {
        $stmt = $db->query("SELECT id, name FROM categories LIMIT 10");
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($categories)) {
            echo '<p class="fail">✗ No categories found! This will cause ticket creation to fail.</p>';
        } else {
            echo '<p class="pass">✓ Found ' . count($categories) . ' categories</p>';
            echo '<ul>';
            foreach ($categories as $cat) {
                echo '<li>[' . $cat['id'] . '] ' . htmlspecialchars($cat['name']) . '</li>';
            }
            echo '</ul>';
        }
    } catch (Exception $e) {
        echo '<p class="fail">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // Test 4: Check employees
    echo '<div class="section">';
    echo '<h2>4. Employees</h2>';
    try {
        $stmt = $db->query("SELECT COUNT(*) as count FROM employees");
        $result = $stmt->fetch();
        
        if ($result['count'] == 0) {
            echo '<p class="fail">✗ No employees found!</p>';
        } else {
            echo '<p class="pass">✓ Found ' . $result['count'] . ' employees</p>';
        }
    } catch (Exception $e) {
        echo '<p class="fail">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // Test 5: Test actual INSERT statement
    echo '<div class="section">';
    echo '<h2>5. Test Ticket INSERT (Simulation)</h2>';
    try {
        $testData = [
            'ticket_number' => 'DEBUG-' . time(),
            'title' => 'Debug Test Ticket',
            'description' => 'This is a test ticket for debugging',
            'category_id' => 1,
            'department_id' => null,
            'priority' => 'medium',
            'status' => 'pending',
            'submitter_id' => 1,
            'submitter_type' => 'employee',
            'assigned_to' => null,
            'attachments' => null
        ];
        
        echo '<p>Preparing INSERT statement...</p>';
        
        $sql = "INSERT INTO tickets (ticket_number, title, description, category_id, department_id, priority, status, submitter_id, submitter_type, assigned_to, attachments) 
                VALUES (:ticket_number, :title, :description, :category_id, :department_id, :priority, :status, :submitter_id, :submitter_type, :assigned_to, :attachments)";
        
        $stmt = $db->prepare($sql);
        echo '<p class="pass">✓ Statement prepared</p>';
        
        echo '<p>Executing INSERT with test data...</p>';
        $result = $stmt->execute($testData);
        
        if ($result) {
            $lastId = $db->lastInsertId();
            echo '<p class="pass">✓ INSERT successful! Ticket ID: ' . $lastId . '</p>';
            
            // Clean up test data
            $db->prepare("DELETE FROM tickets WHERE id = :id")->execute([':id' => $lastId]);
            echo '<p>(Test ticket removed)</p>';
        } else {
            echo '<p class="fail">✗ INSERT returned false</p>';
            echo '<pre>' . print_r($stmt->errorInfo(), true) . '</pre>';
        }
        
    } catch (Exception $e) {
        echo '<p class="fail">✗ INSERT Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    echo '</div>';
    
    // Test 6: Check Upload Directory
    echo '<div class="section">';
    echo '<h2>6. Upload Directory</h2>';
    $uploadDir = __DIR__ . '/uploads/';
    if (is_dir($uploadDir)) {
        echo '<p class="pass">✓ Upload directory exists: ' . $uploadDir . '</p>';
        if (is_writable($uploadDir)) {
            echo '<p class="pass">✓ Upload directory is writable</p>';
        } else {
            echo '<p class="fail">✗ Upload directory is NOT writable! Check permissions.</p>';
        }
    } else {
        echo '<p class="fail">✗ Upload directory does not exist: ' . $uploadDir . '</p>';
    }
    echo '</div>';
    
    // Test 7: Check SLA Tracking Table
    echo '<div class="section">';
    echo '<h2>7. SLA Tracking Table</h2>';
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'sla_tracking'");
        if ($stmt->rowCount() > 0) {
            echo '<p class="pass">✓ sla_tracking table exists</p>';
        } else {
            echo '<p class="fail">✗ sla_tracking table MISSING! This may cause errors after ticket creation.</p>';
        }
    } catch (Exception $e) {
        echo '<p class="fail">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // Test 8: Check Notifications Table
    echo '<div class="section">';
    echo '<h2>8. Notifications Table</h2>';
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'notifications'");
        if ($stmt->rowCount() > 0) {
            echo '<p class="pass">✓ notifications table exists</p>';
        } else {
            echo '<p class="fail">✗ notifications table MISSING!</p>';
        }
    } catch (Exception $e) {
        echo '<p class="fail">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    echo '<div class="section">';
    echo '<h2>Summary</h2>';
    echo '<p>If all tests passed, the issue is likely:</p>';
    echo '<ul>';
    echo '<li>Invalid category_id being submitted from the form</li>';
    echo '<li>Session data issues (not logged in properly)</li>';
    echo '<li>Form validation blocking submission</li>';
    echo '<li>JavaScript errors preventing form submission</li>';
    echo '</ul>';
    echo '<p><strong>Next Step:</strong> Try creating a ticket and check the browser console for JavaScript errors.</p>';
    echo '</div>';
    ?>
    
</body>
</html>
