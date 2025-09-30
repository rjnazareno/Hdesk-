<?php
/**
 * Database Connection Test
 */

echo "<h2>Database Connection Test</h2>";

try {
    // Test the config
    require_once 'config/config.php';
    echo "<p>✅ Config loaded successfully</p>";
    echo "<p>DB_HOST: " . DB_HOST . "</p>";
    echo "<p>DB_NAME: " . DB_NAME . "</p>";
    echo "<p>DB_USER: " . DB_USER . "</p>";
    
    // Test raw PDO connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    echo "<p>✅ Raw PDO connection successful</p>";
    
    // Test Database class
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "<p>✅ Database class connection successful</p>";
    
    // Test query
    $stmt = $db->prepare("SHOW TABLES");
    $stmt->execute();
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p>✅ Database query successful. Tables found: " . count($tables) . "</p>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // Test FCM tokens table specifically
    try {
        $stmt = $db->prepare("SELECT COUNT(*) FROM fcm_tokens");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "<p>✅ FCM tokens table exists with " . $count . " tokens</p>";
    } catch (Exception $e) {
        echo "<p>⚠️ FCM tokens table issue: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Trace: " . $e->getTraceAsString() . "</p>";
}
?>