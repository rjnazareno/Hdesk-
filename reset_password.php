<?php 
require_once __DIR__ . '/config/config.php';

// If already logged in, redirect
if (isLoggedIn()) {
    $userType = $_SESSION['user_type'];
    header('Location: ' . ($userType === 'employee' ? 'customer/dashboard.php' : 'admin/dashboard.php'));
    exit;
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = '';
$success = false;
$tokenValid = false;
$userEmail = '';

// Validate token
if ($token) {
    try {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT user_email, user_type, expires_at, used 
            FROM password_reset_tokens 
            WHERE token = :token 
            LIMIT 1
        ");
        $stmt->execute([':token' => $token]);
        $resetToken = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$resetToken) {
            $error = 'Invalid reset link';
        } elseif ($resetToken['used'] == 1) {
            $error = 'This reset link has already been used';
        } elseif (strtotime($resetToken['expires_at']) < time()) {
            $error = 'This reset link has expired. Please request a new one.';
        } else {
            $tokenValid = true;
            $userEmail = $resetToken['user_email'];
        }
    } catch (PDOException $e) {
        error_log("Reset password validation error: " . $e->getMessage());
        $error = 'An error occurred. Please try again.';
    }
} else {
    $error = 'No reset token provided';
}

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValid) {
    $newPassword = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get token info again
            $stmt = $db->prepare("SELECT user_email, user_type FROM password_reset_tokens WHERE token = :token");
            $stmt->execute([':token' => $token]);
            $resetToken = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password in appropriate table
            if ($resetToken['user_type'] === 'employee') {
                $stmt = $db->prepare("UPDATE employees SET password = :password WHERE email = :email");
            } else {
                $stmt = $db->prepare("UPDATE users SET password = :password WHERE email = :email");
            }
            
            $stmt->execute([
                ':password' => $hashedPassword,
                ':email' => $resetToken['user_email']
            ]);
            
            // Mark token as used
            $stmt = $db->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = :token");
            $stmt->execute([':token' => $token]);
            
            $success = true;
            
        } catch (PDOException $e) {
            error_log("Reset password error: " . $e->getMessage());
            $error = 'An error occurred while resetting your password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo defined('APP_NAME') ? APP_NAME : 'ServiceHub'; ?></title>
    <?php echo getTailwindCSS(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-slate-800/50 border border-slate-700/50 backdrop-blur-md rounded-lg shadow-2xl p-8">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <div class="w-16 h-16 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center">
                        <i class="fas fa-lock text-white text-2xl"></i>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">Reset Password</h1>
                <p class="text-slate-400 text-sm">Enter your new password</p>
            </div>

            <?php if ($success): ?>
                <!-- Success Message -->
                <div class="bg-green-900/30 border border-green-700/50 text-green-400 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle mt-0.5 mr-3"></i>
                        <div>
                            <p class="font-semibold mb-1">Password Reset Successful!</p>
                            <p class="text-sm">You can now login with your new password.</p>
                        </div>
                    </div>
                </div>
                
                <a href="login.php" class="block w-full text-center bg-gradient-to-r from-cyan-500 to-blue-600 text-white py-3 rounded-lg font-semibold hover:from-cyan-600 hover:to-blue-700 transition">
                    <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                </a>
                
            <?php elseif (!$tokenValid): ?>
                <!-- Invalid Token -->
                <div class="bg-red-900/30 border border-red-700/50 text-red-400 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                
                <a href="forgot_password.php" class="block w-full text-center bg-slate-700 text-white py-3 rounded-lg font-semibold hover:bg-slate-600 transition">
                    <i class="fas fa-redo mr-2"></i>Request New Reset Link
                </a>
                
            <?php else: ?>
                <!-- Error Message -->
                <?php if ($error): ?>
                <div class="bg-red-900/30 border border-red-700/50 text-red-400 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Reset Password Form -->
                <form method="POST" class="space-y-6" id="resetForm">
                    <div>
                        <label for="password" class="block text-sm font-medium text-white mb-2">
                            <i class="fas fa-lock mr-2"></i>New Password
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required
                            minlength="8"
                            class="w-full px-4 py-3 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition"
                            placeholder="Enter new password (min. 8 characters)"
                        >
                        <p class="text-xs text-slate-400 mt-1">
                            <i class="fas fa-info-circle mr-1"></i>Minimum 8 characters
                        </p>
                    </div>

                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-white mb-2">
                            <i class="fas fa-lock mr-2"></i>Confirm Password
                        </label>
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required
                            minlength="8"
                            class="w-full px-4 py-3 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition"
                            placeholder="Confirm new password"
                        >
                    </div>

                    <button 
                        type="submit"
                        class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white py-3 rounded-lg font-semibold hover:from-cyan-600 hover:to-blue-700 transition duration-200 shadow-lg hover:shadow-xl"
                    >
                        <i class="fas fa-check mr-2"></i>Reset Password
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="login.php" class="text-sm text-cyan-500 hover:text-cyan-400 transition">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Login
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <p class="text-center text-slate-400 text-sm mt-6">
            &copy; <?php echo date('Y'); ?> <?php echo defined('APP_NAME') ? APP_NAME : 'ServiceHub'; ?>. All rights reserved.
        </p>
    </div>

    <script>
        // Client-side password validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long!');
                return false;
            }
        });
    </script>
</body>
</html>
