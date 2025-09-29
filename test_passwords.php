<?php
/**
 * Password testing script to find the correct passwords for existing employees
 * This will test common passwords against your hashed passwords in the database
 */

require_once 'config/database.php';

// Common passwords to test
$common_passwords = [
    'password',
    'password123',
    '123456',
    'admin',
    'admin123',
    'user123',
    'test',
    'test123',
    'qwerty',
    '12345678',
    'welcome',
    'welcome123'
];

try {
    $pdo = getDB();
    
    echo "<h2>Testing Employee Passwords</h2>\n";
    echo "<p>Testing common passwords against your hashed passwords...</p>\n\n";
    
    // Get some sample employees
    $stmt = $pdo->prepare("SELECT id, fname, lname, username, password FROM employees WHERE status = 'active' LIMIT 10");
    $stmt->execute();
    $employees = $stmt->fetchAll();
    
    foreach ($employees as $employee) {
        echo "<strong>{$employee['fname']} {$employee['lname']} ({$employee['username']}):</strong><br>\n";
        echo "Hash: " . substr($employee['password'], 0, 30) . "...<br>\n";
        
        $found = false;
        foreach ($common_passwords as $test_password) {
            if (password_verify($test_password, $employee['password'])) {
                echo "✅ <span style='color: green;'>Password found: <strong>{$test_password}</strong></span><br>\n";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo "❌ <span style='color: red;'>Password not found among common passwords</span><br>\n";
        }
        
        echo "<br>\n";
    }
    
    // Also test IT staff
    echo "<h2>Testing IT Staff Passwords</h2>\n";
    $stmt = $pdo->prepare("SELECT staff_id, name, username, password FROM it_staff WHERE is_active = 1");
    $stmt->execute();
    $staff = $stmt->fetchAll();
    
    foreach ($staff as $member) {
        echo "<strong>{$member['name']} ({$member['username']}):</strong><br>\n";
        echo "Hash: " . substr($member['password'], 0, 30) . "...<br>\n";
        
        $found = false;
        foreach ($common_passwords as $test_password) {
            if (password_verify($test_password, $member['password'])) {
                echo "✅ <span style='color: green;'>Password found: <strong>{$test_password}</strong></span><br>\n";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo "❌ <span style='color: red;'>Password not found among common passwords</span><br>\n";
        }
        
        echo "<br>\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; line-height: 1.6; }
h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
strong { color: #007bff; }
</style>