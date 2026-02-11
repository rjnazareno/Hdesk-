<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <!-- Tailwind CSS: Uses CDN in development, local file in production -->
    <?php echo getTailwindCSS(); ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white border border-gray-200 p-8">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold">
                    <span class="text-emerald-500">H</span><span class="text-gray-900">desk</span>
                </h1>
                <p class="text-gray-600 mt-2"><?php echo defined('APP_TAGLINE') ? APP_TAGLINE : 'Multi-Department Service Portal'; ?></p>
            </div>

            <!-- Error Message -->
            <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php 
                    if ($_GET['error'] === 'invalid') {
                        echo 'Invalid username or password';
                    } elseif ($_GET['error'] === 'session') {
                        echo 'Your session has expired. Please login again.';
                    } else {
                        echo 'An error occurred. Please try again.';
                    }
                ?>
            </div>
            <?php endif; ?>

            <!-- Success Message -->
            <?php if (isset($_GET['success']) && $_GET['success'] === 'logout'): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                You have been logged out successfully.
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="login_process.php" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2 text-gray-600"></i>Username or Email
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        class="w-full px-4 py-3 border border-gray-200 bg-white text-gray-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition"
                        placeholder="Enter your username or email"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2 text-gray-600"></i>Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-3 border border-gray-200 bg-white text-gray-900 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition"
                        placeholder="Enter your password"
                    >
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="w-4 h-4 text-emerald-600 border-gray-300 bg-white focus:ring-emerald-500">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="forgot_password.php" class="text-sm text-emerald-600 hover:underline transition">Forgot password?</a>
                </div>

                <button 
                    type="submit"
                    class="w-full bg-emerald-600 text-white py-3 font-semibold hover:bg-emerald-700 transition"
                >
                    Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-gray-500 text-sm mt-6">
            &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
        </p>
    </div>
</body>
</html>
