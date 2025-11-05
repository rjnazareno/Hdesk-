<?php
/**
 * Debug Employee Passwords
 * Check if employee passwords are properly hashed and can be verified
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Employee.php';
require_once __DIR__ . '/includes/Auth.php';

$db = Database::getInstance()->getConnection();

// Get employee passwords
$sql = "SELECT id, username, email, password, status FROM employees LIMIT 20";
$stmt = $db->prepare($sql);
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== EMPLOYEE PASSWORD DIAGNOSTIC ===\n\n";

foreach ($employees as $emp) {
    echo "ID: {$emp['id']} | Username: {$emp['username']} | Status: {$emp['status']}\n";
    echo "Email: {$emp['email']}\n";
    echo "Password Hash: " . substr($emp['password'], 0, 50) . "...\n";
    
    // Check if password is properly hashed
    $passwordInfo = password_get_info($emp['password']);
    echo "Hash Algorithm: " . ($passwordInfo['algo'] ? 'Yes (algo: ' . $passwordInfo['algo'] . ')' : 'NO - NOT HASHED') . "\n";
    
    // Check if it's MD5 (old format)
    if (strlen($emp['password']) === 32 && ctype_xdigit($emp['password'])) {
        echo "⚠️  WARNING: This looks like MD5 hash (32 hex chars) - NOT PHP password_hash!\n";
    }
    
    // Check if it looks like bcrypt
    if (strpos($emp['password'], '$2') === 0) {
        echo "✅ Hash format: bcrypt (correct)\n";
    } elseif (strpos($emp['password'], '$') === false) {
        echo "❌ Hash format: PLAIN TEXT or unsupported!\n";
    }
    
    echo "---\n\n";
}

// Test login with a known employee
echo "\n=== LOGIN TEST ===\n";
echo "Testing employee login with first employee...\n\n";

if (!empty($employees[0])) {
    $testEmp = $employees[0];
    $testPassword = 'test123'; // Test with common password
    
    $auth = new Auth();
    $employeeModel = new Employee();
    
    // Test verifyLogin
    $result = $employeeModel->verifyLogin($testEmp['username'], $testPassword);
    echo "Test username: {$testEmp['username']}\n";
    echo "Test password: {$testPassword}\n";
    echo "Login result: " . ($result ? "SUCCESS" : "FAILED") . "\n";
    
    // Show what we're comparing
    echo "\nPassword verification details:\n";
    echo "Hash from DB: " . $testEmp['password'] . "\n";
    echo "password_verify result: " . (password_verify($testPassword, $testEmp['password']) ? "MATCH" : "NO MATCH") . "\n";
}

echo "\n=== SUMMARY ===\n";
$hashCount = 0;
$plainCount = 0;
$md5Count = 0;

foreach ($employees as $emp) {
    if (strpos($emp['password'], '$2') === 0) {
        $hashCount++;
    } elseif (strlen($emp['password']) === 32 && ctype_xdigit($emp['password'])) {
        $md5Count++;
    } else {
        $plainCount++;
    }
}

echo "Bcrypt hashes: $hashCount\n";
echo "MD5 hashes: $md5Count\n";
echo "Plain text/other: $plainCount\n";

if ($md5Count > 0 || $plainCount > 0) {
    echo "\n⚠️  ISSUE FOUND: Not all passwords are properly hashed as bcrypt!\n";
    echo "This would cause login failures because password_verify() expects bcrypt format.\n";
}

?>
