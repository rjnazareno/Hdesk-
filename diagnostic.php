<?php
/**
 * Diagnostic Script
 * Check database connection and table structure
 */

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');

echo '<pre>';
echo "=== IT Help Desk Diagnostic ===\n\n";

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
try {
    $db = Database::getInstance()->getConnection();
    echo "   ✓ Database connected successfully\n";
    echo "   Database: " . DB_NAME . "\n\n";
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n\n";
    die();
}

// Test 2: Check if tables exist
echo "2. Checking Required Tables...\n";
$requiredTables = ['tickets', 'categories', 'departments', 'employees', 'users', 'sla_tracking'];
foreach ($requiredTables as $table) {
    $stmt = $db->query("SHOW TABLES LIKE '$table'");
    if ($stmt->rowCount() > 0) {
        echo "   ✓ Table '$table' exists\n";
    } else {
        echo "   ✗ Table '$table' MISSING\n";
    }
}

// Test 3: Check tickets table structure
echo "\n3. Checking 'tickets' Table Structure...\n";
try {
    $stmt = $db->query("DESCRIBE tickets");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $requiredColumns = ['id', 'ticket_number', 'title', 'description', 'category_id', 'department_id', 'priority', 'status', 'submitter_id', 'submitter_type', 'assigned_to', 'attachments'];
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $columns)) {
            echo "   ✓ Column '$col' exists\n";
        } else {
            echo "   ✗ Column '$col' MISSING\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error checking table structure: " . $e->getMessage() . "\n";
}

// Test 4: Check if categories exist
echo "\n4. Checking Categories...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
    $result = $stmt->fetch();
    echo "   Total categories: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        echo "   ✓ Categories available\n";
        
        $stmt = $db->query("SELECT id, name FROM categories LIMIT 5");
        $cats = $stmt->fetchAll();
        echo "   Sample categories:\n";
        foreach ($cats as $cat) {
            echo "     - [{$cat['id']}] {$cat['name']}\n";
        }
    } else {
        echo "   ✗ No categories found in database\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking categories: " . $e->getMessage() . "\n";
}

// Test 5: Check if employees exist
echo "\n5. Checking Employees...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM employees");
    $result = $stmt->fetch();
    echo "   Total employees: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        echo "   ✓ Employees exist\n";
    } else {
        echo "   ✗ No employees found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking employees: " . $e->getMessage() . "\n";
}

// Test 6: Check departments
echo "\n6. Checking Departments...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM departments");
    $result = $stmt->fetch();
    echo "   Total departments: " . $result['count'] . "\n";
    
    if ($result['count'] > 0) {
        echo "   ✓ Departments available\n";
        
        $stmt = $db->query("SELECT id, name FROM departments");
        $depts = $stmt->fetchAll();
        echo "   Departments:\n";
        foreach ($depts as $dept) {
            echo "     - [{$dept['id']}] {$dept['name']}\n";
        }
    } else {
        echo "   ✗ No departments found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error checking departments: " . $e->getMessage() . "\n";
}

// Test 7: Test ticket insertion
echo "\n7. Testing Ticket Creation (Dry Run)...\n";
try {
    $testData = [
        'ticket_number' => 'TEST-' . time(),
        'title' => 'Test Ticket',
        'description' => 'Diagnostic test',
        'category_id' => 1,
        'department_id' => null,
        'priority' => 'medium',
        'status' => 'pending',
        'submitter_id' => 1,
        'submitter_type' => 'employee',
        'assigned_to' => null,
        'attachments' => null
    ];
    
    echo "   Preparing SQL statement...\n";
    $sql = "INSERT INTO tickets (ticket_number, title, description, category_id, department_id, priority, status, submitter_id, submitter_type, assigned_to, attachments) 
            VALUES (:ticket_number, :title, :description, :category_id, :department_id, :priority, :status, :submitter_id, :submitter_type, :assigned_to, :attachments)";
    
    $stmt = $db->prepare($sql);
    echo "   ✓ SQL prepared successfully\n";
    
    // Don't actually execute - this is just a diagnostic
    echo "   (Skipping actual execution for diagnostic mode)\n";
    
} catch (Exception $e) {
    echo "   ✗ Error during ticket creation test: " . $e->getMessage() . "\n";
}

echo "\n=== Diagnostic Complete ===\n";
echo "\nIf all tests passed, the issue may be:\n";
echo "- Invalid category_id being submitted\n";
echo "- Missing employee ID in session\n";
echo "- File upload directory permission issues\n";
echo "</pre>";
?>
