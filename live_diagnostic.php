<?php
/**
 * Live Server Login Diagnostic Tool
 * This file helps diagnose login issues on the live server
 */

// Enable error reporting temporarily for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Login Diagnostic</title></head><body>";
echo "<h1>Live Server Login Diagnostic</h1>";
echo "<pre>";

// 1. Check PHP Configuration
echo "=== PHP Configuration ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "\n";
echo "Script Path: " . __DIR__ . "\n";

// 2. Check Session Configuration
echo "\n=== Session Configuration ===\n";
echo "Session Status: " . session_status() . " (1=disabled, 2=active)\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Name: " . session_name() . "\n";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session Started: Yes\n";
echo "Session ID: " . session_id() . "\n";

// 3. Test File Includes
echo "\n=== File Include Tests ===\n";
$files_to_check = [
    'config/config.php',
    'config/database.php',
    'includes/security.php'
];

foreach ($files_to_check as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        echo "✓ $file exists\n";
        try {
            require_once $full_path;
            echo "✓ $file included successfully\n";
        } catch (Exception $e) {
            echo "✗ $file include error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✗ $file not found at $full_path\n";
    }
}

// 4. Test Database Connection
echo "\n=== Database Connection Test ===\n";
try {
    if (defined('DB_HOST')) {
        echo "Database Config Loaded:\n";
        echo "  Host: " . DB_HOST . "\n";
        echo "  Database: " . DB_NAME . "\n";
        echo "  Username: " . DB_USER . "\n";
        
        $pdo = getDB();
        echo "✓ Database connection successful\n";
        
        // Test a simple query
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM it_staff WHERE is_active = 1");
        $result = $stmt->fetch();
        echo "✓ Active IT staff count: " . $result['count'] . "\n";
        
    } else {
        echo "✗ Database configuration not loaded\n";
    }
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

// 5. Test Security Functions
echo "\n=== Security Functions Test ===\n";
try {
    if (function_exists('escape')) {
        $test_string = "<script>alert('test')</script>";
        $escaped = escape($test_string);
        echo "✓ escape() function works: " . $escaped . "\n";
    } else {
        echo "✗ escape() function not found\n";
    }
} catch (Exception $e) {
    echo "✗ Security function error: " . $e->getMessage() . "\n";
}

// 6. Test Login Process Simulation
echo "\n=== Login Process Simulation ===\n";
try {
    // Simulate POST data
    $_POST['username'] = 'admin';
    $_POST['password'] = 'admin';
    $_POST['user_type'] = 'it_staff';
    
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? 'employee';
    
    echo "Simulated login attempt:\n";
    echo "  Username: $username\n";
    echo "  User Type: $userType\n";
    
    if (empty($username) || empty($password)) {
        echo "✗ Empty username or password\n";
    } else {
        $pdo = getDB();
        
        if ($userType === 'it_staff') {
            $stmt = $pdo->prepare("SELECT staff_id as id, name, username, email, password FROM it_staff WHERE username = ? AND is_active = 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user) {
                echo "✓ User found: " . $user['name'] . "\n";
                
                if (password_verify($password, $user['password'])) {
                    echo "✓ Password verified\n";
                    
                    // Test session setting
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = 'it_staff';
                    
                    echo "✓ Session variables set\n";
                    echo "  Session user_id: " . $_SESSION['user_id'] . "\n";
                    echo "  Session username: " . $_SESSION['username'] . "\n";
                    
                } else {
                    echo "✗ Password verification failed\n";
                }
            } else {
                echo "✗ User not found or not active\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "✗ Login simulation error: " . $e->getMessage() . "\n";
    echo "Error details:\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Trace: " . $e->getTraceAsString() . "\n";
}

// 7. Check Write Permissions
echo "\n=== File Permission Tests ===\n";
$dirs_to_check = [
    'uploads/tickets/',
    'config/',
    '.'
];

foreach ($dirs_to_check as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    if (is_dir($full_path)) {
        echo "$dir: " . (is_writable($full_path) ? "✓ Writable" : "✗ Not writable") . "\n";
    } else {
        echo "$dir: ✗ Directory not found\n";
    }
}

// 8. Server Environment
echo "\n=== Server Environment ===\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'Not set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "\n";
echo "HTTPS: " . ($_SERVER['HTTPS'] ?? 'Not set') . "\n";
echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'Not set') . "\n";

echo "\n=== Recommendations ===\n";
echo "1. If database connection fails, check your hosting provider's database settings\n";
echo "2. If session issues occur, contact your host about session configuration\n";
echo "3. If file permission errors appear, set directories to 755 and files to 644\n";
echo "4. Check your hosting provider's error logs for more details\n";

echo "</pre>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Upload this file to your live server and access it via browser</li>";
echo "<li>Check all the test results above</li>";
echo "<li>If any tests fail, that's likely the cause of your login issues</li>";
echo "<li>Contact your hosting provider if there are server configuration issues</li>";
echo "</ol>";
echo "</body></html>";
?>