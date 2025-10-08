<?php
require_once '../config/database.php';
require_once '../includes/security.php';
require_once 'controllers/DashboardController.php';

// Start session and require admin login
session_start();
requireLogin();

// Only IT staff can access admin panel
if (!isITStaff()) {
    header('Location: ../dashboard.php');
    exit;
}

$controller = new DashboardController();
$dashboardData = $controller->getDashboardData();

// Extract data
$stats = $dashboardData['stats'];
$recentTickets = $dashboardData['recentTickets'];
$activities = $dashboardData['activities'];
$chartData = $dashboardData['chartData'];
$userName = $_SESSION['user_data']['name'] ?? $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - IT Help Desk</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    
    <!-- Custom Admin CSS -->
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="bg-gray-900 text-gray-100">
    
    <div class="flex h-screen overflow-hidden">
        
        <!-- Sidebar -->
        <aside id="sidebar" class="sidebar bg-gray-950 w-64 flex-shrink-0 transition-all duration-300">
            <div class="p-6">
                <!-- Logo -->
                <div class="flex items-center space-x-3 mb-8">
                    <div class="bg-gradient-to-br from-blue-500 to-purple-600 w-10 h-10 rounded-lg flex items-center justify-center">
                        <i class="fas fa-layer-group text-white text-xl"></i>
                    </div>
                    <span class="text-xl font-bold text-white">Simply Web</span>
                </div>
                
                <!-- Navigation -->
                <nav class="space-y-2">
                    <a href="index.php" class="nav-item active">
                        <i class="fas fa-th-large"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="tickets.php" class="nav-item">
                        <i class="fas fa-ticket-alt"></i>
                        <span>Tickets</span>
                    </a>
                    <a href="customers.php" class="nav-item">
                        <i class="fas fa-users"></i>
                        <span>Customers</span>
                    </a>
                    <a href="categories.php" class="nav-item">
                        <i class="fas fa-folder"></i>
                        <span>Categories</span>
                    </a>
                    <a href="settings.php" class="nav-item">
                        <i class="fas fa-cog"></i>
                        <span>Admin</span>
                    </a>
                    <a href="articles.php" class="nav-item">
                        <i class="fas fa-newspaper"></i>
                        <span>Article</span>
                        <span class="badge">6</span>
                    </a>
                </nav>
                
                <!-- Logout -->
                <div class="absolute bottom-6 left-6 right-6">
                    <a href="../logout.php" class="nav-item text-red-400 hover:bg-red-900/20">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            
            <!-- Header -->
            <header class="bg-gray-800/50 backdrop-blur-sm border-b border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-white">Welcome Back</h1>
                        <p class="text-gray-400 text-sm">Hello <?= htmlspecialchars($userName) ?>, Good Morning!</p>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <!-- Search -->
                        <div class="relative">
                            <input type="text" placeholder="Search Dashboard" 
                                   class="bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-2 pl-10 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 w-64">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        
                        <!-- Filter -->
                        <button class="bg-gray-700/50 hover:bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 transition-colors">
                            <i class="fas fa-filter text-gray-300"></i>
                        </button>
                        
                        <!-- Notifications -->
                        <button class="bg-gray-700/50 hover:bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 transition-colors relative">
                            <i class="fas fa-bell text-gray-300"></i>
                            <span class="absolute -top-1 -right-1 bg-blue-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">3</span>
                        </button>
                        
                        <!-- Messages -->
                        <button class="bg-gray-700/50 hover:bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 transition-colors">
                            <i class="fas fa-comment-alt text-gray-300"></i>
                        </button>
                        
                        <!-- Profile -->
                        <div class="flex items-center space-x-3 bg-gray-700/50 rounded-lg px-3 py-2 cursor-pointer hover:bg-gray-700 transition-colors">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=4F46E5&color=fff" 
                                 alt="Profile" class="w-8 h-8 rounded-full">
                            <span class="text-sm font-medium text-gray-200 hidden sm:block"><?= htmlspecialchars(explode(' ', $userName)[0]) ?></span>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left Column (2/3) -->
                    <div class="lg:col-span-2 space-y-6">
                        
                        <!-- Daily Tickets Chart -->
                        <div class="card">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Daily Tickets</h3>
                                    <p class="text-sm text-gray-400">Check each column for more details</p>
                                </div>
                            </div>
                            <canvas id="dailyTicketsChart" height="80"></canvas>
                        </div>
                        
                        <!-- Tickets by Status -->
                        <div class="card">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Tickets by Status</h3>
                                    <p class="text-sm text-gray-400">Open vs Pending vs Closed tickets</p>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <!-- Pending -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                        <span class="text-gray-300">Pending</span>
                                    </div>
                                    <span class="text-white font-semibold"><?= $stats['pending_percentage'] ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill bg-yellow-500" style="width: <?= $stats['pending_percentage'] ?>%"></div>
                                </div>
                                
                                <!-- Open -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                        <span class="text-gray-300">Open</span>
                                    </div>
                                    <span class="text-white font-semibold"><?= $stats['open_percentage'] ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill bg-blue-500" style="width: <?= $stats['open_percentage'] ?>%"></div>
                                </div>
                                
                                <!-- Closed -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                        <span class="text-gray-300">Closed</span>
                                    </div>
                                    <span class="text-white font-semibold"><?= $stats['closed_percentage'] ?>%</span>
                                </div>
                                <div class="progress-bar">
                                    <div class="progress-fill bg-purple-500" style="width: <?= $stats['closed_percentage'] ?>%"></div>
                                </div>
                            </div>
                            
                            <div class="flex items-center space-x-6 mt-6 pt-6 border-t border-gray-700">
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                    <span class="text-sm text-gray-400">Pending</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                                    <span class="text-sm text-gray-400">Open</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                                    <span class="text-sm text-gray-400">Closed</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Articles -->
                        <div class="card">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-white">Recent Article</h3>
                                    <p class="text-sm text-gray-400">Article writing that is more details here</p>
                                </div>
                            </div>
                            
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead>
                                        <tr class="text-left text-gray-400 text-sm border-b border-gray-700">
                                            <th class="pb-3 font-medium"><input type="checkbox" class="rounded"></th>
                                            <th class="pb-3 font-medium">Title</th>
                                            <th class="pb-3 font-medium">Views</th>
                                            <th class="pb-3 font-medium">Changes</th>
                                            <th class="pb-3 font-medium">Ratings</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-300">
                                        <?php foreach ($recentTickets as $ticket): ?>
                                        <tr class="border-b border-gray-800 hover:bg-gray-800/50 transition-colors">
                                            <td class="py-4"><input type="checkbox" class="rounded"></td>
                                            <td class="py-4">
                                                <div>
                                                    <div class="font-medium text-white"><?= htmlspecialchars($ticket['title']) ?></div>
                                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($ticket['category']) ?></div>
                                                </div>
                                            </td>
                                            <td class="py-4"><?= number_format($ticket['views'] ?? rand(100, 5000)) ?></td>
                                            <td class="py-4"><?= $ticket['changes'] ?? rand(0, 5) ?></td>
                                            <td class="py-4">
                                                <div class="flex text-yellow-400">
                                                    <?php for($i = 0; $i < 5; $i++): ?>
                                                        <i class="fas fa-star <?= $i < ($ticket['rating'] ?? 4) ? '' : 'text-gray-600' ?>"></i>
                                                    <?php endfor; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Right Column (1/3) -->
                    <div class="space-y-6">
                        
                        <!-- Activity Stats -->
                        <div class="card">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-white">Activity</h3>
                                <p class="text-sm text-gray-400">Tickets customer activity</p>
                            </div>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 bg-gray-800/50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                                            <i class="fas fa-chart-line text-green-400"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-400">Active</div>
                                            <div class="text-xs text-gray-500">Competitive Tickets</div>
                                        </div>
                                    </div>
                                    <div class="text-xl font-bold text-white"><?= $stats['active_tickets'] ?></div>
                                </div>
                                
                                <div class="flex items-center justify-between p-4 bg-gray-800/50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                            <i class="fas fa-users text-blue-400"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-400">Customers</div>
                                            <div class="text-xs text-gray-500">Number of registered customers</div>
                                        </div>
                                    </div>
                                    <div class="text-xl font-bold text-white"><?= $stats['total_customers'] ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Last Updates -->
                        <div class="card">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="text-lg font-semibold text-white">Last Updates</h3>
                                <select class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-1 text-sm text-gray-300 focus:outline-none focus:border-blue-500">
                                    <option>Today</option>
                                    <option>This Week</option>
                                    <option>This Month</option>
                                </select>
                            </div>
                            
                            <div class="space-y-4">
                                <?php foreach ($activities as $activity): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-8 h-8 rounded-lg <?= $activity['color'] ?> flex items-center justify-center">
                                            <i class="<?= $activity['icon'] ?> text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm text-gray-300"><?= $activity['title'] ?></div>
                                        </div>
                                    </div>
                                    <div class="text-sm font-semibold text-white"><?= $activity['count'] ?></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                    </div>
                    
                </div>
            </main>
            
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="assets/js/admin.js"></script>
    <script>
        // Chart Data from PHP
        const chartData = <?= json_encode($chartData) ?>;
    </script>
    
</body>
</html>
