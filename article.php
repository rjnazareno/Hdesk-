<?php
require_once __DIR__ . '/config/config.php';

$auth = new Auth();
$auth->requireLogin();

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles - IT Help Desk</title>
    
    <!-- Tailwind CSS: Uses CDN in development, local file in production -->
    <?php echo getTailwindCSS(); ?>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <?php 
    // Determine which navigation to include based on user type
    if ($_SESSION['user_type'] === 'employee') {
        include __DIR__ . '/includes/customer_nav.php';
    } else {
        include __DIR__ . '/includes/admin_nav.php';
    }
    ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-gradient-to-r from-slate-800/80 to-slate-800/80 backdrop-blur-sm border-b border-slate-700/50">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-white">Knowledge Base</h1>
                    <p class="text-sm lg:text-base text-slate-400">Help articles and documentation</p>
                </div>
                <div class="hidden lg:flex items-center space-x-2">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=06b6d4" 
                         alt="User" 
                         class="w-10 h-10 rounded-full">
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <div class="bg-slate-800/50 border border-slate-700/50 backdrop-blur-md rounded-lg p-8 text-center">
                <i class="fas fa-newspaper text-6xl text-slate-700 mb-4"></i>
                <h3 class="text-xl font-semibold text-white mb-2">Knowledge Base Coming Soon</h3>
                <p class="text-slate-400">This feature will contain helpful articles and documentation.</p>
            </div>
        </div>
    </div>
</body>
</html>
