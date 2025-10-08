<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-xl mb-4">
                    <i class="fas fa-layer-group text-white text-2xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">ResolveIT</h1>
                <p class="text-gray-600 mt-2">IT Help Desk System</p>
            </div>

            <!-- Error Message -->
            <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
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
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                You have been logged out successfully.
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="login_process.php" method="POST" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2"></i>Username or Email
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Enter your username or email"
                    >
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>Password
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Enter your password"
                    >
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-700">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-blue-600 hover:text-blue-700">Forgot password?</a>
                </div>

                <button 
                    type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg font-semibold hover:bg-blue-700 transition duration-200 shadow-lg hover:shadow-xl"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i>Sign In
                </button>
            </form>

            <!-- Demo Credentials -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-sm text-gray-600 text-center mb-3 font-semibold">Demo Credentials:</p>
                <div class="space-y-2 text-xs text-gray-600">
                    <div class="flex justify-between bg-gray-50 px-3 py-2 rounded">
                        <span><strong>Admin:</strong> admin / admin123</span>
                    </div>
                    <div class="flex justify-between bg-gray-50 px-3 py-2 rounded">
                        <span><strong>IT Staff:</strong> mahfuzul / admin123</span>
                    </div>
                    <div class="flex justify-between bg-gray-50 px-3 py-2 rounded">
                        <span><strong>Employee:</strong> john.doe / admin123</span>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center text-gray-600 text-sm mt-6">
            &copy; <?php echo date('Y'); ?> ResolveIT. All rights reserved.
        </p>
    </div>
</body>
</html>
