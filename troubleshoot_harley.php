<?php
/**
 * Harley Database Connection Troubleshooter
 * Tests various connection scenarios to find the right configuration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Harley DB Connection Troubleshooter</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .pass { color: #4ec9b0; font-weight: bold; }
        .fail { color: #f48771; font-weight: bold; }
        .warn { color: #dcdcaa; font-weight: bold; }
        .section { background: #2d2d2d; padding: 15px; margin: 10px 0; border-left: 4px solid #007acc; }
        h1 { color: #4ec9b0; }
        h2 { color: #569cd6; }
        pre { background: #1e1e1e; padding: 10px; border: 1px solid #007acc; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔧 Harley Database Connection Troubleshooter</h1>
    
    <?php
    // Test configurations to try
    $testConfigs = [
        [
            'name' => 'Config 1: Remote host with calendartype credentials',
            'host' => 'srv1866.hstgr.io',
            'dbname' => 'u816220874_calendartype',
            'user' => 'u816220874_calendartype',
            'pass' => 'Gr33n$$wRf'
        ],
        [
            'name' => 'Config 2: Localhost with calendartype credentials',
            'host' => 'localhost',
            'dbname' => 'u816220874_calendartype',
            'user' => 'u816220874_calendartype',
            'pass' => 'Gr33n$$wRf'
        ],
        [
            'name' => 'Config 3: Localhost with main ticketing credentials',
            'host' => 'localhost',
            'dbname' => 'u816220874_calendartype',
            'user' => 'u816220874_ticketing',
            'pass' => 'Gr33n$$wRf'
        ],
        [
            'name' => 'Config 4: Remote with main ticketing credentials',
            'host' => 'srv1866.hstgr.io',
            'dbname' => 'u816220874_calendartype',
            'user' => 'u816220874_ticketing',
            'pass' => 'Gr33n$$wRf'
        ]
    ];
    
    $workingConfig = null;
    
    foreach ($testConfigs as $index => $config) {
        echo '<div class="section">';
        echo '<h2>' . htmlspecialchars($config['name']) . '</h2>';
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
            echo '<p>DSN: <code>' . htmlspecialchars($dsn) . '</code></p>';
            echo '<p>User: <code>' . htmlspecialchars($config['user']) . '</code></p>';
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ];
            
            $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
            
            echo '<p class="pass">✓ CONNECTION SUCCESSFUL!</p>';
            
            // Test querying employees table
            try {
                $stmt = $pdo->query("SELECT COUNT(*) as count FROM employees WHERE status = 'active'");
                $result = $stmt->fetch();
                echo '<p class="pass">✓ Successfully queried employees table</p>';
                echo '<p>Active Employees: <strong>' . $result['count'] . '</strong></p>';
                
                // Get sample employee
                $stmt = $pdo->query("SELECT fname, lname, email FROM employees WHERE status = 'active' LIMIT 1");
                $sample = $stmt->fetch();
                if ($sample) {
                    echo '<p>Sample: ' . htmlspecialchars($sample['fname'] . ' ' . $sample['lname']) . '</p>';
                }
                
                $workingConfig = $config;
                echo '<p class="pass">🎯 THIS CONFIGURATION WORKS!</p>';
                
            } catch (PDOException $e) {
                echo '<p class="warn">⚠ Connected but table query failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
            }
            
        } catch (PDOException $e) {
            echo '<p class="fail">✗ Connection failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        
        echo '</div>';
        
        // If we found a working config, highlight it
        if ($workingConfig === $config) {
            break;
        }
    }
    
    // Show recommendation
    echo '<div class="section">';
    echo '<h2>📋 Recommendation</h2>';
    
    if ($workingConfig) {
        echo '<p class="pass">✓ Found working configuration!</p>';
        echo '<h3>Update config/harley_config.php with these values:</h3>';
        echo '<pre>';
        echo "define('HARLEY_DB_HOST', '" . htmlspecialchars($workingConfig['host']) . "');\n";
        echo "define('HARLEY_DB_NAME', '" . htmlspecialchars($workingConfig['dbname']) . "');\n";
        echo "define('HARLEY_DB_USER', '" . htmlspecialchars($workingConfig['user']) . "');\n";
        echo "define('HARLEY_DB_PASS', '" . htmlspecialchars($workingConfig['pass']) . "');\n";
        echo '</pre>';
        
        echo '<p><strong>Copy the values above and update your harley_config.php file.</strong></p>';
    } else {
        echo '<p class="fail">✗ None of the configurations worked.</p>';
        echo '<p>Possible issues:</p>';
        echo '<ul>';
        echo '<li>Database <code>u816220874_calendartype</code> does not exist on this server</li>';
        echo '<li>Database user does not have access permissions</li>';
        echo '<li>Remote database access is disabled in cPanel</li>';
        echo '<li>Password is incorrect for this specific database</li>';
        echo '</ul>';
        
        echo '<h3>Next Steps:</h3>';
        echo '<ol>';
        echo '<li>Check if database <code>u816220874_calendartype</code> exists in cPanel → Databases</li>';
        echo '<li>Verify the database user has permissions for this database</li>';
        echo '<li>Check if you need to enable remote MySQL access in cPanel</li>';
        echo '<li>Confirm the password <code>Gr33n$$wRf</code> works for the Harley database user</li>';
        echo '</ol>';
    }
    
    echo '</div>';
    
    // Show current local database connection for comparison
    echo '<div class="section">';
    echo '<h2>Current Local Database (for comparison)</h2>';
    try {
        $localDb = Database::getInstance()->getConnection();
        echo '<p class="pass">✓ Local IT Help Desk database connected</p>';
        echo '<p>Database: <code>' . DB_NAME . '</code></p>';
        
        $stmt = $localDb->query("SELECT COUNT(*) as count FROM employees");
        $result = $stmt->fetch();
        echo '<p>Employees in local database: <strong>' . $result['count'] . '</strong></p>';
        
    } catch (Exception $e) {
        echo '<p class="fail">✗ Local DB error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    ?>
    
</body>
</html>
