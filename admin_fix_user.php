<?php
/**
 * User Account Admin Tool
 * Web-based utility to check and fix user accounts
 * 
 * SECURITY: Delete this file after use or protect with authentication!
 */

require_once 'config/database.php';

// No password protection - direct access
$access_granted = true;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Account Admin Tool</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <strong>⚠️ Security Warning:</strong> Delete this file after use!
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold mb-6">User Account Diagnostic Tool</h1>
            
            <!-- Check User Form -->
            <form method="POST" class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-gray-700 mb-2">Username:</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? 'Rest.James'); ?>" 
                                   class="w-full px-3 py-2 border rounded" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 mb-2">Test Password:</label>
                            <input type="text" name="test_password" value="<?php echo htmlspecialchars($_POST['test_password'] ?? 'Gr33n$$wRf'); ?>" 
                                   class="w-full px-3 py-2 border rounded" required>
                        </div>
                    </div>
                    <button type="submit" name="check_user" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                        Check User
                    </button>
                </form>

                <?php
                if (isset($_POST['check_user'])) {
                    $username = $_POST['username'];
                    $testPassword = $_POST['test_password'];
                    
                    try {
                        $db = Database::getInstance()->getConnection();
                        
                        // Check user
                        $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
                        $stmt->execute([$username]);
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$user) {
                            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">';
                            echo '❌ User not found: ' . htmlspecialchars($username);
                            echo '</div>';
                        } else {
                            // Display user info
                            echo '<div class="bg-blue-50 border border-blue-300 p-4 rounded mb-4">';
                            echo '<h3 class="font-bold text-lg mb-3">User Information:</h3>';
                            echo '<div class="grid grid-cols-2 gap-2 text-sm">';
                            echo '<div><strong>ID:</strong> ' . $user['id'] . '</div>';
                            echo '<div><strong>Username:</strong> ' . htmlspecialchars($user['username']) . '</div>';
                            echo '<div><strong>Email:</strong> ' . htmlspecialchars($user['email']) . '</div>';
                            echo '<div><strong>Full Name:</strong> ' . htmlspecialchars($user['full_name']) . '</div>';
                            echo '<div><strong>Role:</strong> ' . htmlspecialchars($user['role']) . '</div>';
                            echo '<div><strong>Is Active:</strong> ' . ($user['is_active'] ? '✅ YES' : '❌ NO (INACTIVE)') . '</div>';
                            echo '</div></div>';
                            
                            // Test password
                            $passwordMatch = password_verify($testPassword, $user['password']);
                            
                            if ($passwordMatch) {
                                echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                                echo '✅ Password matches!';
                                echo '</div>';
                            } else {
                                echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">';
                                echo '⚠️ Password does NOT match the one in database';
                                echo '</div>';
                            }
                            
                            // Check for issues
                            $issues = [];
                            if (!$user['is_active']) {
                                $issues[] = 'User account is INACTIVE (is_active = 0)';
                            }
                            if (!$passwordMatch) {
                                $issues[] = 'Password hash does not match';
                            }
                            
                            if (!empty($issues)) {
                                echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">';
                                echo '<strong>Issues Found:</strong><ul class="list-disc ml-6 mt-2">';
                                foreach ($issues as $issue) {
                                    echo '<li>' . $issue . '</li>';
                                }
                                echo '</ul></div>';
                                
                                // Fix form
                                echo '<form method="POST" class="bg-gray-50 p-4 rounded border">';
                                echo '<input type="hidden" name="admin_password" value="' . htmlspecialchars($admin_pass) . '">';
                                echo '<input type="hidden" name="username" value="' . htmlspecialchars($username) . '">';
                                echo '<input type="hidden" name="test_password" value="' . htmlspecialchars($testPassword) . '">';
                                echo '<h3 class="font-bold mb-3">Fix Issues:</h3>';
                                
                                if (!$user['is_active']) {
                                    echo '<label class="flex items-center mb-2">';
                                    echo '<input type="checkbox" name="activate_user" value="1" checked class="mr-2">';
                                    echo 'Activate user account (set is_active = 1)';
                                    echo '</label>';
                                }
                                
                                if (!$passwordMatch) {
                                    echo '<label class="flex items-center mb-2">';
                                    echo '<input type="checkbox" name="reset_password" value="1" checked class="mr-2">';
                                    echo 'Reset password to: <code class="ml-2 bg-gray-200 px-2 py-1 rounded">' . htmlspecialchars($testPassword) . '</code>';
                                    echo '</label>';
                                }
                                
                                echo '<button type="submit" name="fix_user" class="mt-4 bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700">';
                                echo 'Fix Account Now';
                                echo '</button>';
                                echo '</form>';
                            } else {
                                echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">';
                                echo '✅ No issues found! User should be able to login.';
                                echo '</div>';
                            }
                        }
                        
                    } catch (Exception $e) {
                        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">';
                        echo 'Error: ' . htmlspecialchars($e->getMessage());
                        echo '</div>';
                    }
                }
                
                // Handle fix
                if (isset($_POST['fix_user'])) {
                    $username = $_POST['username'];
                    $testPassword = $_POST['test_password'];
                    
                    try {
                        $db = Database::getInstance()->getConnection();
                        
                        $updates = [];
                        $params = [':username' => $username];
                        
                        if (isset($_POST['activate_user'])) {
                            $updates[] = 'is_active = 1';
                        }
                        
                        if (isset($_POST['reset_password'])) {
                            $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
                            $updates[] = 'password = :password';
                            $params[':password'] = $hashedPassword;
                        }
                        
                        if (!empty($updates)) {
                            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE username = :username";
                            $stmt = $db->prepare($sql);
                            $stmt->execute($params);
                            
                            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                            echo '✅ <strong>Account Fixed Successfully!</strong><br>';
                            echo 'Username: ' . htmlspecialchars($username) . '<br>';
                            echo 'Password: ' . htmlspecialchars($testPassword);
                            echo '</div>';
                            
                            echo '<a href="login.php" class="inline-block bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 mt-4">';
                            echo 'Go to Login Page';
                            echo '</a>';
                        }
                        
                    } catch (Exception $e) {
                        echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">';
                        echo 'Error: ' . htmlspecialchars($e->getMessage());
                        echo '</div>';
                    }
                }
                ?>
            </div>
    </div>
</body>
</html>
