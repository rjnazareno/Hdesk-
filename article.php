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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-gray-900 text-white">
        <div class="flex items-center justify-center h-16 bg-gray-800">
            <i class="fas fa-layer-group text-xl mr-2"></i>
            <span class="text-xl font-bold">Simply Web</span>
        </div>
        
        <nav class="mt-6">
            <a href="dashboard.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-th-large w-6"></i>
                <span>Dashboard</span>
            </a>
            <a href="tickets.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-ticket-alt w-6"></i>
                <span>Tickets</span>
            </a>
            <a href="article.php" class="flex items-center px-6 py-3 bg-gray-800 text-white">
                <i class="fas fa-newspaper w-6"></i>
                <span>Article</span>
            </a>
            <a href="logout.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition mt-8">
                <i class="fas fa-sign-out-alt w-6"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-8 py-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Knowledge Base</h1>
                    <p class="text-gray-600">Help articles and documentation</p>
                </div>
                <div class="flex items-center space-x-2">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                         alt="User" 
                         class="w-10 h-10 rounded-full">
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <div class="bg-white rounded-xl shadow-sm p-8 text-center">
                <i class="fas fa-newspaper text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Knowledge Base Coming Soon</h3>
                <p class="text-gray-600">This feature will contain helpful articles and documentation.</p>
            </div>
        </div>
    </div>
</body>
</html>
