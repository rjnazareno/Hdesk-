<?php
/**
 * Notifications Diagnostic Page (Customer/Employee)
 * Check if notifications are working properly
 */

require_once __DIR__ . '/../config/config.php';

// Check login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['employee_id'])) {
    die("Please login first");
}

echo "<h1>Notifications System Diagnostic</h1>";
echo "<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { border-collapse: collapse; width: 100%; margin: 20px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
</style>";

// 1. Check Session
echo "<h2>1. Session Information</h2>";
echo "<table>";
echo "<tr><th>Key</th><th>Value</th></tr>";
echo "<tr><td>user_id</td><td>" . ($_SESSION['user_id'] ?? '<span class=\"error\">NOT SET</span>') . "</td></tr>";
echo "<tr><td>employee_id</td><td>" . ($_SESSION['employee_id'] ?? '<span class=\"error\">NOT SET</span>') . "</td></tr>";
echo "<tr><td>user_type</td><td>" . ($_SESSION['user_type'] ?? '<span class=\"error\">NOT SET</span>') . "</td></tr>";
echo "<tr><td>username</td><td>" . ($_SESSION['username'] ?? 'NOT SET') . "</td></tr>";
echo "<tr><td>role</td><td>" . ($_SESSION['role'] ?? 'NOT SET') . "</td></tr>";
echo "</table>";

// 2. Check Database Connection
echo "<h2>2. Database Connection</h2>";
try {
    $db = Database::getInstance()->getConnection();
    echo "<p class='success'>✓ Database connected successfully</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Database connection failed: " . $e->getMessage() . "</p>";
    die();
}

// 3. Check Notifications Table Structure
echo "<h2>3. Notifications Table Structure</h2>";
try {
    $stmt = $db->query("DESCRIBE notifications");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . $col['Field'] . "</td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . $col['Key'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    $hasEmployeeId = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'employee_id') {
            $hasEmployeeId = true;
            break;
        }
    }
    
    if (!$hasEmployeeId) {
        echo "<p class='error'>⚠ WARNING: 'employee_id' column is missing! Employee notifications won't work.</p>";
        echo "<p>Run this SQL: <code>ALTER TABLE notifications ADD COLUMN employee_id INT(11) NULL AFTER user_id;</code></p>";
    } else {
        echo "<p class='success'>✓ Table has employee_id column</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error checking table: " . $e->getMessage() . "</p>";
}

// 4. Check Notifications Count
echo "<h2>4. Notifications Count</h2>";
try {
    $sessionUserType = $_SESSION['user_type'] ?? 'employee';
    
    if ($sessionUserType === 'employee') {
        $userId = $_SESSION['employee_id'] ?? $_SESSION['user_id'];
        $userType = 'employee';
        $whereClause = 'employee_id = :id';
    } else {
        $userId = $_SESSION['user_id'];
        $userType = 'user';
        $whereClause = 'user_id = :id';
    }
    
    echo "<p class='info'>Searching for notifications where $whereClause (ID: $userId)</p>";
    
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE $whereClause");
    $stmt->execute([':id' => $userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total notifications: <strong>" . $result['total'] . "</strong></p>";
    
    if ($result['total'] == 0) {
        echo "<p class='error'>⚠ No notifications found for this user!</p>";
        echo "<p>To create test notifications, run the SQL file: <code>database/create_test_notifications.sql</code></p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

// 5. Show Recent Notifications
echo "<h2>5. Recent Notifications</h2>";
try {
    $stmt = $db->prepare("SELECT * FROM notifications WHERE $whereClause ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([':id' => $userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Type</th><th>Title</th><th>Message</th><th>Read</th><th>Created</th></tr>";
        foreach ($notifications as $n) {
            echo "<tr>";
            echo "<td>" . $n['id'] . "</td>";
            echo "<td>" . $n['type'] . "</td>";
            echo "<td>" . htmlspecialchars($n['title']) . "</td>";
            echo "<td>" . htmlspecialchars($n['message']) . "</td>";
            echo "<td>" . ($n['is_read'] ? 'Yes' : '<strong>No</strong>') . "</td>";
            echo "<td>" . $n['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>No notifications to display</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
}

// 6. Test API
echo "<h2>6. API Test</h2>";
echo "<p>Testing API endpoint: <code>../api/notifications.php?action=get_recent</code></p>";
echo "<pre>";
$apiUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") 
    . "://$_SERVER[HTTP_HOST]" 
    . dirname($_SERVER['REQUEST_URI']) 
    . "/api/notifications.php?action=get_recent";
echo "API URL: $apiUrl\n\n";
echo "</pre>";

echo "<p><a href='../api/notifications.php?action=get_recent' target='_blank'>Click here to test API</a></p>";

// 7. Check JavaScript
echo "<h2>7. JavaScript Check</h2>";
echo "<p>Check browser console for errors when clicking the bell icon</p>";
echo "<p>The API should be called automatically when you open the notifications dropdown</p>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Go Back</a></p>";
