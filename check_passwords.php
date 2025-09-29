<?php
/**
 * IT Staff Password Checker and Fixer
 * This will show you what's in your database and optionally fix passwords
 */

require_once 'config/database.php';

echo "<h2>IT Staff Password Checker</h2>\n";
echo "<style>body { font-family: Arial, sans-serif; padding: 20px; }</style>\n";

try {
    $pdo = getDB();
    
    // Get IT staff records
    $stmt = $pdo->prepare("SELECT staff_id, name, username, password FROM it_staff WHERE is_active = 1");
    $stmt->execute();
    $staff = $stmt->fetchAll();
    
    echo "<h3>Current IT Staff in Database:</h3>\n";
    
    foreach ($staff as $member) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 5px;'>\n";
        echo "<strong>ID:</strong> {$member['staff_id']}<br>\n";
        echo "<strong>Name:</strong> " . htmlspecialchars($member['name']) . "<br>\n";
        echo "<strong>Username:</strong> " . htmlspecialchars($member['username']) . "<br>\n";
        echo "<strong>Password:</strong> " . htmlspecialchars(substr($member['password'], 0, 50)) . 
             (strlen($member['password']) > 50 ? '...' : '') . "<br>\n";
        
        // Check if password is hashed
        if (substr($member['password'], 0, 4) === '$2y$') {
            echo "<span style='color: green;'>✅ Password is properly hashed</span><br>\n";
            
            // Test common passwords
            $testPasswords = ['admin123', 'admin', 'password123', 'password'];
            foreach ($testPasswords as $testPass) {
                if (password_verify($testPass, $member['password'])) {
                    echo "<span style='color: blue;'>🔓 Password is: <strong>{$testPass}</strong></span><br>\n";
                    break;
                }
            }
        } else {
            echo "<span style='color: orange;'>⚠️ Password appears to be plain text: <strong>{$member['password']}</strong></span><br>\n";
            
            // Offer to hash it
            if (isset($_GET['hash_password']) && $_GET['hash_password'] == $member['staff_id']) {
                $hashedPassword = password_hash($member['password'], PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE it_staff SET password = ? WHERE staff_id = ?");
                if ($updateStmt->execute([$hashedPassword, $member['staff_id']])) {
                    echo "<span style='color: green;'>✅ Password has been hashed!</span><br>\n";
                } else {
                    echo "<span style='color: red;'>❌ Failed to hash password</span><br>\n";
                }
            } else {
                echo "<a href='?hash_password={$member['staff_id']}' style='color: blue; text-decoration: none;'>🔒 Click to hash this password</a><br>\n";
            }
        }
        echo "</div>\n";
    }
    
    echo "<hr>";
    echo "<h3>Login Test:</h3>";
    echo "<form method='post'>";
    echo "<input type='text' name='test_username' placeholder='Username' value='admin'> ";
    echo "<input type='password' name='test_password' placeholder='Password' value='admin123'> ";
    echo "<button type='submit' name='test_login'>Test Login</button>";
    echo "</form>";
    
    if (isset($_POST['test_login'])) {
        $testUsername = $_POST['test_username'] ?? '';
        $testPassword = $_POST['test_password'] ?? '';
        
        echo "<h4>Testing login for: {$testUsername}</h4>";
        
        $stmt = $pdo->prepare("SELECT staff_id, name, username, password FROM it_staff WHERE username = ? AND is_active = 1");
        $stmt->execute([$testUsername]);
        $user = $stmt->fetch();
        
        if (!$user) {
            echo "<span style='color: red;'>❌ User not found</span>";
        } else {
            echo "<span style='color: green;'>✅ User found</span><br>";
            
            // Test password verification
            if (password_verify($testPassword, $user['password'])) {
                echo "<span style='color: green;'>✅ Password verification successful (hashed)</span>";
            } elseif ($testPassword === $user['password']) {
                echo "<span style='color: orange;'>⚠️ Password match successful (plain text)</span>";
            } else {
                echo "<span style='color: red;'>❌ Password verification failed</span>";
                echo "<br>Expected: " . htmlspecialchars($user['password']);
                echo "<br>Provided: " . htmlspecialchars($testPassword);
            }
        }
    }
    
} catch (Exception $e) {
    echo "<span style='color: red;'>❌ Database Error: " . htmlspecialchars($e->getMessage()) . "</span>";
}

echo "<br><br><p><strong>Note:</strong> Delete this file after use for security reasons!</p>";
?>