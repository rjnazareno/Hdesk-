<?php
require_once 'config/database.php';

$db = Database::getInstance()->getConnection();
$username = 'Rest.James';

// Check users table
$stmt = $db->prepare('SELECT id, username, email, role, is_active FROM users WHERE username = ?');
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "User found in 'users' table:\n";
    echo "ID: " . $user['id'] . "\n";
    echo "Username: " . $user['username'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Role: " . $user['role'] . "\n";
    echo "Is Active: " . $user['is_active'] . "\n";
    
    // Test password
    $stmt2 = $db->prepare('SELECT password FROM users WHERE username = ?');
    $stmt2->execute([$username]);
    $passData = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    $testPassword = 'Gr33n$$wRf';
    $verified = password_verify($testPassword, $passData['password']);
    echo "\nPassword verification test: " . ($verified ? "SUCCESS" : "FAILED") . "\n";
    echo "Password hash in DB: " . $passData['password'] . "\n";
} else {
    echo "User NOT found in 'users' table\n";
}
