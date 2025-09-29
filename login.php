<?php
require_once 'includes/auth.php';
require_once 'includes/security.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } elseif (!checkRateLimit('login', 5, 300)) { // 5 attempts per 5 minutes
        $error = 'Too many login attempts. Please try again later.';
        logSecurityEvent('rate_limit_exceeded', ['action' => 'login']);
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $userType = $_POST['user_type'] ?? 'employee';
        
        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } else {
            $loginSuccess = false;
            
            if ($userType === 'employee') {
                $loginSuccess = $auth->loginEmployee($username, $password);
            } elseif ($userType === 'it') {
                $loginSuccess = $auth->loginITStaff($username, $password);
            }
            
            if ($loginSuccess) {
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
                logSecurityEvent('failed_login', [
                    'username' => $username,
                    'user_type' => $userType
                ]);
            }
        }
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape(APP_NAME); ?> - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <i class="fas fa-ticket-alt text-blue-600 text-6xl mb-4"></i>
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                <?php echo escape(APP_NAME); ?>
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Sign in to your account
            </p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-md">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo escape($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-md">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo escape($success); ?>
            </div>
        <?php endif; ?>

        <form class="mt-8 space-y-6" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo escape($csrfToken); ?>">
            
            <div class="space-y-4">
                <!-- User Type Selection -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Login as:</label>
                    <div class="flex space-x-4">
                        <label class="flex items-center">
                            <input type="radio" name="user_type" value="employee" class="h-4 w-4 text-blue-600" 
                                   <?php echo (!isset($_POST['user_type']) || $_POST['user_type'] === 'employee') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-sm text-gray-700">Employee</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="user_type" value="it" class="h-4 w-4 text-blue-600"
                                   <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'it') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-sm text-gray-700">IT Staff</span>
                        </label>
                    </div>
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="sr-only">Username</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input id="username" name="username" type="text" required 
                               class="appearance-none relative block w-full px-10 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Username" 
                               value="<?php echo escape($_POST['username'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="password" name="password" type="password" required 
                               class="appearance-none relative block w-full px-10 py-3 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                               placeholder="Password">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <button type="button" onclick="togglePassword()" class="text-gray-400 hover:text-gray-600">
                                <i id="password-toggle" class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <button type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <i class="fas fa-sign-in-alt text-blue-500 group-hover:text-blue-400"></i>
                    </span>
                    Sign In
                </button>
            </div>
        </form>

        <div class="text-center">
            <p class="text-xs text-gray-500">
                Having trouble logging in? Contact your IT administrator.
            </p>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('password-toggle');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Auto-focus username field
        document.getElementById('username').focus();
    </script>
</body>
</html>