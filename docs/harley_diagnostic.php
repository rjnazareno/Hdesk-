<?php
/**
 * Harley Database Diagnostic Script
 * Run this first to see what columns are available in your employees table
 * Upload to: https://harley.resourcestaffonline.com/Public/module/harley_diagnostic.php
 */

// Harley database configuration
$DB_HOST = 'localhost';
$DB_NAME = 'u816220874_harleyrss';
$DB_USER = 'u816220874_harley';
$DB_PASS = 'Z&e#mtcW3';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Harley Database Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .status { padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid; }
        .success { background: #e8f5e9; border-color: #4CAF50; color: #2e7d32; }
        .error { background: #ffebee; border-color: #f44336; color: #c62828; }
        .info { background: #e3f2fd; border-color: #2196F3; color: #1565c0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
        tr:hover { background: #f5f5f5; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; border: 1px solid #ddd; }
        .highlight { background: #fff176; padding: 2px 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Harley Database Diagnostic</h1>
        <p><strong>Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>

<?php
// Connect to database
echo "<div class='status info'><strong>Connecting to Harley database...</strong></div>";

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<div class='status success'>✅ Connected successfully to: <strong>$DB_NAME</strong></div>";
} catch (PDOException $e) {
    echo "<div class='status error'>❌ Connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "</div></body></html>";
    exit;
}

// Step 1: Find tables with 'employee' in the name
echo "<h2>📋 Step 1: Finding Employee Tables</h2>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE '%employee%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "<div class='status error'>❌ No tables found with 'employee' in the name</div>";
        echo "<p>Showing all tables instead:</p>";
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        echo "<div class='status success'>✅ Found " . count($tables) . " table(s) with 'employee' in name</div>";
    }
    
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li><strong>$table</strong></li>";
    }
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<div class='status error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Step 2: Show columns of 'employees' table
echo "<h2>📊 Step 2: Employees Table Structure</h2>";
try {
    $stmt = $pdo->query("DESCRIBE employees");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='status success'>✅ Found " . count($columns) . " columns in 'employees' table</div>";
    
    echo "<table>";
    echo "<tr><th>Column Name</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td class='highlight'>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Extract column names for copy-paste
    $columnNames = array_column($columns, 'Field');
    echo "<h3>✂️ Column Names (for copy-paste):</h3>";
    echo "<pre>" . implode(", ", $columnNames) . "</pre>";
    
} catch (PDOException $e) {
    echo "<div class='status error'>❌ Error reading table structure: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p>The 'employees' table might not exist. Check table names above.</p>";
}

// Step 3: Show sample data
echo "<h2>👥 Step 3: Sample Employee Data</h2>";
try {
    $stmt = $pdo->query("SELECT * FROM employees LIMIT 3");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($employees)) {
        echo "<div class='status error'>⚠️ No employees found in table</div>";
    } else {
        echo "<div class='status success'>✅ Showing first " . count($employees) . " employees</div>";
        
        echo "<table>";
        // Header
        echo "<tr>";
        foreach (array_keys($employees[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        // Data rows
        foreach ($employees as $emp) {
            echo "<tr>";
            foreach ($emp as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<div class='status error'>❌ Error fetching sample data: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Step 4: Count total employees
echo "<h2>📈 Step 4: Total Employee Count</h2>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM employees");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='status info'><strong>Total Employees:</strong> " . $count['total'] . "</div>";
    
} catch (PDOException $e) {
    echo "<div class='status error'>❌ Error counting employees: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Step 5: Suggested SQL for sync script
echo "<h2>💡 Step 5: Suggested SQL Query for Sync Script</h2>";
try {
    $stmt = $pdo->query("DESCRIBE employees");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnNames = array_column($columns, 'Field');
    
    // Build suggested query based on common field names
    $mapping = [
        'id' => 'employee_id',
        'fname' => 'fname',
        'lname' => 'lname',
        'email' => 'email',
    ];
    
    // Check for optional fields
    $optionalFields = ['phone', 'mobile', 'cell', 'telephone', 'department', 'dept', 'position', 'title', 'role', 'username', 'login'];
    
    echo "<p>Based on the columns found, here's the suggested query for your sync script:</p>";
    echo "<pre>";
    echo "SELECT \n";
    echo "    id as employee_id,\n";
    echo "    fname,\n";
    echo "    lname,\n";
    echo "    email";
    
    // Add optional fields if they exist
    $foundOptional = [];
    foreach ($optionalFields as $field) {
        if (in_array($field, $columnNames)) {
            $foundOptional[] = $field;
        }
    }
    
    if (!empty($foundOptional)) {
        echo ",\n    " . implode(",\n    ", $foundOptional);
    }
    
    echo "\nFROM employees\nWHERE 1=1\nORDER BY id";
    echo "</pre>";
    
    if (empty($foundOptional)) {
        echo "<div class='status info'>ℹ️ Note: No optional fields (phone, department, position, username) found in table. Query will only sync basic info.</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='status error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

?>

        <hr style='margin:30px 0;'>
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>✅ Review the column names and sample data above</li>
            <li>📝 Copy the suggested SQL query</li>
            <li>🔧 Update <code>harley_sync_script.php</code> with the correct columns</li>
            <li>🔄 Run the sync script again</li>
        </ul>
    </div>
</body>
</html>
