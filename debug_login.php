<?php
/**
 * Debug Login - Check why kiras001 can't login
 */

require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<h2>Login Debug for kiras001</h2>";
echo "<pre>";

// Check if user exists
$stmt = $db->prepare("SELECT id, username, email, password, status, role, admin_rights_hdesk FROM employees WHERE username = :username");
$stmt->execute([':username' => 'kiras001']);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if ($employee) {
    echo "✓ Employee found in database:\n";
    echo "  ID: " . $employee['id'] . "\n";
    echo "  Username: " . $employee['username'] . "\n";
    echo "  Email: " . $employee['email'] . "\n";
    echo "  Password: " . $employee['password'] . "\n";
    echo "  Status: " . $employee['status'] . "\n";
    echo "  Role: " . $employee['role'] . "\n";
    echo "  Admin Rights: " . $employee['admin_rights_hdesk'] . "\n";
    echo "\n";
    
    // Test password comparison
    $testPassword = '123456';
    echo "Password Test:\n";
    echo "  Test Password: '$testPassword'\n";
    echo "  DB Password: '" . $employee['password'] . "'\n";
    echo "  String Match: " . ($testPassword === $employee['password'] ? "YES ✓" : "NO ✗") . "\n";
    echo "  String Match (==): " . ($testPassword == $employee['password'] ? "YES ✓" : "NO ✗") . "\n";
    echo "\n";
    
    // Check status
    if ($employee['status'] !== 'active') {
        echo "⚠️ PROBLEM: Status is '{$employee['status']}' but findByUsername() requires 'active'\n";
        echo "   Fix: UPDATE employees SET status = 'active' WHERE username = 'kiras001';\n";
    }
    
} else {
    echo "✗ Employee NOT found with username 'kiras001'\n";
    echo "\nSearching for similar usernames:\n";
    
    $stmt = $db->prepare("SELECT username, email, status FROM employees WHERE username LIKE :username");
    $stmt->execute([':username' => '%kiras%']);
    $similar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($similar) {
        foreach ($similar as $emp) {
            echo "  - {$emp['username']} ({$emp['email']}) - Status: {$emp['status']}\n";
        }
    } else {
        echo "  No similar usernames found\n";
    }
}

echo "\n";
echo "All employees in database:\n";
$stmt = $db->query("SELECT id, username, email, status FROM employees ORDER BY id LIMIT 10");
$all = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($all as $emp) {
    echo "  - ID: {$emp['id']}, Username: {$emp['username']}, Status: {$emp['status']}\n";
}

echo "</pre>";
