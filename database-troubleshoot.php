<?php
/**
 * Database Connection Troubleshooter
 */

echo "<h2>🔧 Database Connection Diagnostics</h2>";

// Test 1: Check if config file loads
echo "<h3>Test 1: Configuration Check</h3>";
try {
    require_once __DIR__ . '/config/config.php';
    echo "✅ Config file loaded successfully<br>";
    echo "📊 Database Config:<br>";
    echo "- Host: " . DB_HOST . "<br>";
    echo "- Database: " . DB_NAME . "<br>";
    echo "- User: " . DB_USER . "<br>";
    echo "- Password: " . (DB_PASS ? "Set (length: " . strlen(DB_PASS) . ")" : "Empty") . "<br>";
} catch (Exception $e) {
    echo "❌ Config error: " . $e->getMessage() . "<br>";
    exit;
}

// Test 2: Check MySQL connection
echo "<h3>Test 2: MySQL Connection Test</h3>";

// Try different connection combinations
$testConfigs = [
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => 'ithelp'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => DB_NAME],
    ['host' => DB_HOST, 'user' => DB_USER, 'pass' => DB_PASS, 'db' => DB_NAME],
    ['host' => 'localhost', 'user' => 'root', 'pass' => 'root', 'db' => 'ithelp'],
];

foreach ($testConfigs as $i => $config) {
    echo "<h4>Config " . ($i + 1) . ": {$config['host']} / {$config['user']} / {$config['db']}</h4>";
    
    try {
        $dsn = "mysql:host={$config['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['user'], $config['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        echo "✅ MySQL connection successful<br>";
        
        // Test database existence
        $stmt = $pdo->prepare("SHOW DATABASES LIKE ?");
        $stmt->execute([$config['db']]);
        if ($stmt->fetch()) {
            echo "✅ Database '{$config['db']}' exists<br>";
            
            // Test connection with database
            $dsn_with_db = "mysql:host={$config['host']};dbname={$config['db']};charset=utf8mb4";
            $pdo_db = new PDO($dsn_with_db, $config['user'], $config['pass']);
            echo "✅ Database connection successful<br>";
            
            // Test table existence
            $stmt = $pdo_db->prepare("SHOW TABLES");
            $stmt->execute();
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "📋 Tables found: " . implode(', ', $tables) . "<br>";
            
            echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>✅ WORKING CONFIG FOUND!</strong><br>";
            echo "Use these settings in config.php:<br>";
            echo "- DB_HOST: '{$config['host']}'<br>";
            echo "- DB_USER: '{$config['user']}'<br>";
            echo "- DB_PASS: '{$config['pass']}'<br>";
            echo "- DB_NAME: '{$config['db']}'<br>";
            echo "</div>";
            
            break; // Stop testing once we find working config
            
        } else {
            echo "❌ Database '{$config['db']}' does not exist<br>";
            
            // Show available databases
            $stmt = $pdo->prepare("SHOW DATABASES");
            $stmt->execute();
            $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo "📋 Available databases: " . implode(', ', $databases) . "<br>";
        }
        
    } catch (PDOException $e) {
        echo "❌ Connection failed: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
}

// Test 3: XAMPP Status Check
echo "<h3>Test 3: XAMPP Environment Check</h3>";
echo "📊 PHP Version: " . PHP_VERSION . "<br>";
echo "📊 Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "<br>";
echo "📊 Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";

// Check if MySQL extension is loaded
if (extension_loaded('pdo_mysql')) {
    echo "✅ PDO MySQL extension loaded<br>";
} else {
    echo "❌ PDO MySQL extension not loaded<br>";
}

echo "<h3>💡 Common XAMPP Solutions:</h3>";
echo "<ul>";
echo "<li><strong>Default XAMPP:</strong> host='localhost', user='root', password='', database='ithelp'</li>";
echo "<li><strong>Create database:</strong> Visit <a href='http://localhost/phpmyadmin'>phpMyAdmin</a> and create 'ithelp' database</li>";
echo "<li><strong>Import SQL:</strong> Import your database_setup.sql file</li>";
echo "<li><strong>Check XAMPP:</strong> Ensure MySQL service is running in XAMPP Control Panel</li>";
echo "</ul>";

echo "<p><a href='http://localhost/phpmyadmin' target='_blank' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Open phpMyAdmin</a></p>";
?>