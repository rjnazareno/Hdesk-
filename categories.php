<?php
require_once __DIR__ . '/config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireITStaff();

$categoryModel = new Category();
$currentUser = $auth->getCurrentUser();

// Get all categories with statistics
$categories = $categoryModel->getStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - IT Help Desk</title>
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
            <a href="customers.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-users w-6"></i>
                <span>Customers</span>
            </a>
            <a href="categories.php" class="flex items-center px-6 py-3 bg-gray-800 text-white">
                <i class="fas fa-folder w-6"></i>
                <span>Categories</span>
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
                    <h1 class="text-2xl font-bold text-gray-900">Ticket Categories</h1>
                    <p class="text-gray-600">Manage ticket categories</p>
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
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($categories as $category): ?>
                <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: <?php echo $category['color']; ?>20;">
                            <i class="fas fa-folder text-xl" style="color: <?php echo $category['color']; ?>;"></i>
                        </div>
                        <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <?php echo $category['ticket_count']; ?> tickets
                        </span>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($category['name']); ?></h3>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600">Open Tickets:</span>
                        <span class="font-semibold text-gray-900"><?php echo $category['open_tickets']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
