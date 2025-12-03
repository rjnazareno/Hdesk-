<?php
/**
 * User Account Fixer Utility
 * Use this to diagnose and fix user login issues
 */

require_once 'config/database.php';

// Configuration
$username = 'Rest.James';
$newPassword = 'Gr33n$$wRf'; // Set this if you want to reset password

echo "=== User Account Diagnostic Tool ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if user exists
    $stmt = $db->prepare('SELECT id, username, email, full_name, role, is_active FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "ERROR: User '$username' not found in database!\n";
        echo "Please check if the username is correct.\n";
        exit;
    }
    
    echo "User found:\n";
    echo "  ID: " . $user['id'] . "\n";
    echo "  Username: " . $user['username'] . "\n";
    echo "  Email: " . $user['email'] . "\n";
    echo "  Full Name: " . $user['full_name'] . "\n";
    echo "  Role: " . $user['role'] . "\n";
    echo "  Is Active: " . ($user['is_active'] ? 'YES' : 'NO (INACTIVE!)') . "\n\n";
    
    // Check password
    $stmt = $db->prepare('SELECT password FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $passData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $passwordVerified = password_verify($newPassword, $passData['password']);
    echo "Password Verification:\n";
    echo "  Testing password: '$newPassword'\n";
    echo "  Result: " . ($passwordVerified ? 'MATCHES' : 'DOES NOT MATCH') . "\n\n";
    
    // Provide fixes
    $needsFix = false;
    $fixes = [];
    
    if (!$user['is_active']) {
        $needsFix = true;
        $fixes[] = "Activate user account (set is_active = 1)";
    }
    
    if (!$passwordVerified) {
        $needsFix = true;
        $fixes[] = "Reset password to '$newPassword'";
    }
    
    if ($needsFix) {
        echo "PROBLEMS FOUND:\n";
        foreach ($fixes as $i => $fix) {
            echo "  " . ($i + 1) . ". $fix\n";
        }
        echo "\n";
        
        // Ask for confirmation
        echo "Do you want to fix these issues? (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = trim(fgets($handle));
        
        if (strtolower($line) === 'y' || strtolower($line) === 'yes') {
            // Apply fixes
            $updates = [];
            $params = [':username' => $username];
            
            if (!$user['is_active']) {
                $updates[] = 'is_active = 1';
                echo "  ✓ Activating user account...\n";
            }
            
            if (!$passwordVerified) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updates[] = 'password = :password';
                $params[':password'] = $hashedPassword;
                echo "  ✓ Resetting password...\n";
            }
            
            if (!empty($updates)) {
                $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE username = :username";
                $stmt = $db->prepare($sql);
                $stmt->execute($params);
                
                echo "\n✅ USER ACCOUNT FIXED!\n";
                echo "You can now login with:\n";
                echo "  Username: $username\n";
                echo "  Password: $newPassword\n";
            }
        } else {
            echo "No changes made.\n";
        }
    } else {
        echo "✅ No issues found! User should be able to login.\n";
        echo "If login still fails, check:\n";
        echo "  1. Username spelling (case-sensitive)\n";
        echo "  2. Password spelling\n";
        echo "  3. Browser cookies/cache\n";
        echo "  4. Session timeout settings\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
