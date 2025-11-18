<?php 
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Mailer.php';

// If already logged in, redirect
if (isLoggedIn()) {
    $userType = $_SESSION['user_type'];
    header('Location: ' . ($userType === 'employee' ? 'customer/dashboard.php' : 'admin/dashboard.php'));
    exit;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if email exists in employees or users table
            $userType = null;
            $userName = null;
            
            // Check employees table
            $stmt = $db->prepare("SELECT id, CONCAT(fname, ' ', lname) as name FROM employees WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $employee = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($employee) {
                $userType = 'employee';
                $userName = $employee['name'];
            } else {
                // Check users table
                $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $userType = 'user';
                    $userName = $user['full_name'];
                }
            }
            
            // Always show success message (security: don't reveal if email exists)
            $success = true;
            
            // If user found, create reset token and send email
            if ($userType) {
                // Generate secure token
                $token = bin2hex(random_bytes(32));
                $expiresAt = date('Y-m-d H:i:s', strtotime('+24 hours'));
                
                // Save token to database
                $stmt = $db->prepare("
                    INSERT INTO password_reset_tokens (user_email, user_type, token, expires_at) 
                    VALUES (:email, :user_type, :token, :expires_at)
                ");
                $stmt->execute([
                    ':email' => $email,
                    ':user_type' => $userType,
                    ':token' => $token,
                    ':expires_at' => $expiresAt
                ]);
                
                // Send reset email
                $resetLink = BASE_URL . "reset_password.php?token=" . $token;
                
                $mailer = new Mailer();
                $mailer->sendPasswordResetEmail($email, $userName, $resetLink);
            }
            
        } catch (PDOException $e) {
            error_log("Forgot password error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
            $success = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - IT Help Desk</title>
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
                        <i class="fas fa-key text-white text-2xl"></i>
                    </div>
                </div>
                <h1 class="text-2xl font-bold text-white mb-2">Forgot Password?</h1>
                <p class="text-slate-400 text-sm">Enter your email to reset your password</p>
            </div>

            <?php if ($success): ?>
                <!-- Success Message -->
                <div class="bg-green-900/30 border border-green-700/50 text-green-400 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle mt-0.5 mr-3"></i>
                        <div>
                            <p class="font-semibold mb-1">Check your email</p>
                            <p class="text-sm">If an account exists with that email, we've sent password reset instructions.</p>
                        </div>
                    </div>
                </div>
                
                <a href="login.php" class="block w-full text-center bg-slate-700 text-white py-3 rounded-lg font-semibold hover:bg-slate-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Login
                </a>
            <?php else: ?>
                <!-- Error Message -->
                <?php if ($error): ?>
                <div class="bg-red-900/30 border border-red-700/50 text-red-400 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- Forgot Password Form -->
                <form method="POST" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-white mb-2">
                            <i class="fas fa-envelope mr-2"></i>Email Address
                        </label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            required
                            class="w-full px-4 py-3 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition"
                            placeholder="Enter your email address"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        >
                    </div>

                    <button 
                        type="submit"
                        class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white py-3 rounded-lg font-semibold hover:from-cyan-600 hover:to-blue-700 transition duration-200 shadow-lg hover:shadow-xl"
                    >
                        <i class="fas fa-paper-plane mr-2"></i>Send Reset Link
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
            &copy; <?php echo date('Y'); ?> ResolveIT. All rights reserved.
        </p>
    </div>
</body>
</html>
