<?php
require_once 'config/database.php';
require_once 'includes/security.php';

session_start();

$error = '';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ?');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $userType = $_POST['user_type'] ?? 'employee';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $pdo = getDB();
            
            if ($userType === 'it_staff') {
                $stmt = $pdo->prepare("SELECT staff_id as id, name, username, email, password FROM it_staff WHERE username = ? AND is_active = 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                // Simple password comparison (no hashing)
                if ($user && $password === $user['password']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = 'it_staff';
                    $_SESSION['user_data'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['name'],
                        'email' => $user['email']
                    ];
                    
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                $stmt = $pdo->prepare("SELECT id, fname, lname, username, email, password FROM employees WHERE username = ? AND status = 'active'");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                // Simple password comparison (no hashing)
                if ($user && $password === $user['password']) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = 'employee';
                    $_SESSION['user_data'] = [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'name' => $user['fname'] . ' ' . $user['lname'],
                        'email' => $user['email']
                    ];
                    
                    header('Location: dashboard.php');
                    exit;
                }
            }
            
            $error = 'Invalid username or password.';
            
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Login system error. Please try again.';
        }
    }
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Support - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-ticket text-blue-600 text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">IT Support System</h2>
                <p class="text-gray-600 mt-2">Sign in to your account</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" class="space-y-6">
                <!-- User Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Login as:</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="user_type" value="employee" class="sr-only peer" checked>
                            <div class="bg-gray-50 peer-checked:bg-blue-50 peer-checked:border-blue-200 border-2 border-gray-200 rounded-lg p-3 text-center transition">
                                <i class="fas fa-user text-gray-600 peer-checked:text-blue-600 mb-1"></i>
                                <div class="text-sm font-medium text-gray-700 peer-checked:text-blue-700">Employee</div>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="user_type" value="it_staff" class="sr-only peer">
                            <div class="bg-gray-50 peer-checked:bg-blue-50 peer-checked:border-blue-200 border-2 border-gray-200 rounded-lg p-3 text-center transition">
                                <i class="fas fa-cog text-gray-600 peer-checked:text-blue-600 mb-1"></i>
                                <div class="text-sm font-medium text-gray-700 peer-checked:text-blue-700">IT Staff</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <div class="relative">
                        <input type="text" id="username" name="username" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pl-11"
                               placeholder="Enter your username">
                        <i class="fas fa-user absolute left-3 top-3.5 text-gray-400"></i>
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pl-11"
                               placeholder="Enter your password">
                        <i class="fas fa-lock absolute left-3 top-3.5 text-gray-400"></i>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-150 transform hover:scale-105">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Sign In
                </button>
            </form>

            <!-- Demo Credentials -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Test Login Credentials:</h3>
                <div class="space-y-2 text-xs">
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <strong class="text-blue-800">IT Staff:</strong>
                        <div class="text-blue-700">Username: admin | Password: admin123</div>
                    </div>
                    <div class="bg-green-50 p-3 rounded-lg">
                        <strong class="text-green-800">Employee:</strong>
                        <div class="text-green-700">Username: john | Password: password123</div>
                        <div class="text-green-600 text-xs mt-1">Also try: jane, mike</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-6 text-sm text-gray-500">
            <i class="fas fa-shield-alt mr-1"></i>
            Secure IT Support System
        </div>
    </div>
</body>
</html>