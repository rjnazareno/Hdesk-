<?php require_once __DIR__ . '/config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IT Help Desk</title>
    
    <!-- Tailwind CSS: Uses CDN in development, local file in production -->
    <?php echo getTailwindCSS(); ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-slate-800/50 border border-slate-700/50 backdrop-blur-md rounded-lg shadow-2xl p-8">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <div class="flex justify-center mb-4">
                    <img src="img/ResolveIT Logo Only without Background.png" alt="ResolveIT Logo" class="h-24 w-auto">
                </div>
                <h1 class="text-3xl font-bold">
                    <span class="text-slate-300">Resolve</span><span class="text-cyan-500">IT</span>
                </h1>
                <p class="text-slate-400 mt-2">IT Help Desk System</p>
            </div>

            <!-- Error Message -->
            <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-900/30 border border-red-700/50 text-red-400 px-4 py-3 rounded-lg mb-6">
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
            <div class="bg-green-900/30 border border-green-700/50 text-green-400 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                You have been logged out successfully.
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="login_process.php" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-white mb-2">
                        <i class="fas fa-user mr-2"></i>Username or Email
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        class="w-full px-4 py-3 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition"
                        placeholder="Enter your username or email"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-white mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-3 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:outline-none focus:border-cyan-500 focus:ring-2 focus:ring-cyan-500/50 transition"
                        placeholder="Enter your password"
                    >
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="w-4 h-4 text-cyan-500 border-slate-600 bg-slate-700/50 rounded focus:ring-cyan-500 focus:ring-offset-slate-800">
                        <span class="ml-2 text-sm text-slate-300">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-cyan-500 hover:text-cyan-400 transition">Forgot password?</a>
                </div>

                <button 
                    type="submit"
                    class="w-full bg-gradient-to-r from-cyan-500 to-blue-600 text-white py-3 rounded-lg font-semibold hover:from-cyan-600 hover:to-blue-700 transition duration-200 shadow-lg hover:shadow-xl"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>
        </div>

        <p class="text-center text-slate-400 text-sm mt-6">
            &copy; <?php echo date('Y'); ?> ResolveIT. All rights reserved.
        </p>
    </div>
</body>
</html>
