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
<body class="<?php echo $_SESSION['user_type'] === 'employee' ? 'bg-gray-50' : 'bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900'; ?>">
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
        <div class="<?php echo $_SESSION['user_type'] === 'employee' ? 'bg-white border-b border-gray-200' : 'bg-gradient-to-r from-slate-800/80 to-slate-800/80 backdrop-blur-sm border-b border-slate-700/50'; ?>">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold <?php echo $_SESSION['user_type'] === 'employee' ? 'text-gray-900' : 'text-white'; ?>">Knowledge Base</h1>
                    <p class="text-sm lg:text-base <?php echo $_SESSION['user_type'] === 'employee' ? 'text-gray-500' : 'text-slate-400'; ?>">Help articles and documentation</p>
                </div>
                <div class="hidden lg:flex items-center space-x-2">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=<?php echo $_SESSION['user_type'] === 'employee' ? '2563eb' : '000000'; ?>&color=<?php echo $_SESSION['user_type'] === 'employee' ? 'ffffff' : '06b6d4'; ?>" 
                         alt="User" 
                         class="w-10 h-10 rounded-full">
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <div class="<?php echo $_SESSION['user_type'] === 'employee' ? 'bg-white border border-gray-200' : 'bg-slate-800/50 border border-slate-700/50 backdrop-blur-md'; ?> rounded-lg p-8 text-center">
                <i class="fas fa-newspaper text-6xl <?php echo $_SESSION['user_type'] === 'employee' ? 'text-gray-300' : 'text-slate-700'; ?> mb-4"></i>
                <h3 class="text-xl font-semibold <?php echo $_SESSION['user_type'] === 'employee' ? 'text-gray-900' : 'text-white'; ?> mb-2">Knowledge Base Coming Soon</h3>
                <p class="<?php echo $_SESSION['user_type'] === 'employee' ? 'text-gray-500' : 'text-slate-400'; ?>">This feature will contain helpful articles and documentation.</p>
            </div>
        </div>
    </div>
</body>
</html>
