<?php
require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireITStaff();

$ticketModel = new Ticket();
$userModel = new User();
$employeeModel = new Employee();
$categoryModel = new Category();
$activityModel = new TicketActivity();

$currentUser = $auth->getCurrentUser();
$isITStaff = $currentUser['role'] === 'it_staff' || $currentUser['role'] === 'admin';

// Get statistics
$stats = $ticketModel->getStats($currentUser['id'], $currentUser['role']);
$userStats = $userModel->getStats();
$employeeStats = $employeeModel->getStats();
$categoryStats = $categoryModel->getStats();

// Get recent tickets
$recentTickets = $ticketModel->getAll([
    'limit' => 5,
    'submitter_id' => !$isITStaff ? $currentUser['id'] : null
]);

// Get recent activity
$recentActivity = $activityModel->getRecent(5, $currentUser['id'], $currentUser['role']);

// Get daily stats for chart (last 10 days)
$dailyStats = $ticketModel->getDailyStats(10);

// Prepare chart data
$chartLabels = [];
$chartData = [];
foreach ($dailyStats as $stat) {
    $chartLabels[] = date('M d', strtotime($stat['date']));
    $chartData[] = $stat['count'];
}

// Get status breakdown
$statusBreakdown = $ticketModel->getStatusBreakdown();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    
    <!-- Quick Wins CSS -->
    <link rel="stylesheet" href="../assets/css/print.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-gradient-to-r from-white to-blue-50 shadow-sm border-b border-blue-100">
            <div class="flex items-center justify-between px-4 lg:px-8 py-6 pt-20 lg:pt-6">
                <div class="flex items-center space-x-4">
                    <div class="hidden lg:block">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white text-2xl font-bold shadow-lg ring-4 ring-blue-100">
                            <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-center space-x-2 mb-1">
                            <h1 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-gray-900 to-blue-600 bg-clip-text text-transparent">
                                <span id="greetingText">Good Morning</span>, <?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?>! 👋
                            </h1>
                        </div>
                        <div class="flex items-center space-x-4 text-sm text-gray-600">
                            <span class="flex items-center">
                                <i class="far fa-clock mr-1.5 text-blue-500"></i>
                                <span id="lastLoginDisplay">Last login: Loading...</span>
                            </span>
                            <span class="hidden md:flex items-center">
                                <i class="fas fa-shield-alt mr-1.5 text-blue-500"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?>
                            </span>
                            <span class="hidden md:flex items-center">
                                <i class="far fa-calendar mr-1.5 text-blue-500"></i>
                                <span id="currentDate"></span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="hidden lg:flex items-center space-x-4">
                    <div class="relative">
                        <input 
                            id="dashboardSearch"
                            type="text" 
                            placeholder="Search tickets..." 
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            onkeyup="searchDashboard(this.value)"
                        >
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <button id="darkModeToggle" class="p-2 text-gray-600 hover:text-gray-900" title="Toggle dark mode">
                        <i id="dark-mode-icon" class="fas fa-moon"></i>
                    </button>
                    <button class="p-2 text-gray-600 hover:text-gray-900" title="Filters">
                        <i class="fas fa-sliders"></i>
                    </button>
                    <button class="p-2 text-gray-600 hover:text-gray-900 relative" title="Notifications">
                        <i class="far fa-bell"></i>
                        <span class="absolute top-0 right-0 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <div class="flex items-center space-x-2">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                             alt="User" 
                             class="w-10 h-10 rounded-full"
                             title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="p-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <span class="inline-flex items-center text-sm font-medium text-gray-600">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                            Dashboard
                        </span>
                    </li>
                </ol>
            </nav>
            
            <!-- Analytics Overview -->
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 mb-6 text-white">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-2xl font-bold">Ticket Analytics</h2>
                        <p class="text-blue-100 text-sm mt-1">Real-time overview of your helpdesk performance</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-chart-line text-3xl opacity-50"></i>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <!-- Total Tickets -->
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 hover:bg-white/20 transition-all duration-200 cursor-pointer border border-white/20"
                         data-stat-filter="all" 
                         onclick="filterByStatus('all')"
                         title="Click to show all tickets">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-blue-100">Total Tickets</span>
                            <i class="fas fa-ticket-alt text-sm opacity-75"></i>
                        </div>
                        <div class="text-3xl font-bold"><?php echo $stats['total'] ?? 0; ?></div>
                        <div class="text-xs text-blue-100 mt-1">All time</div>
                    </div>
                    
                    <!-- Pending -->
                    <div class="bg-yellow-500/20 backdrop-blur-sm rounded-lg p-4 hover:bg-yellow-500/30 transition-all duration-200 cursor-pointer border border-yellow-400/30"
                         data-stat-filter="pending" 
                         onclick="filterByStatus('pending')"
                         title="Click to filter Pending tickets">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-yellow-100">Pending</span>
                            <i class="fas fa-clock text-sm text-yellow-300"></i>
                        </div>
                        <div class="text-3xl font-bold"><?php echo $stats['pending'] ?? 0; ?></div>
                        <div class="text-xs text-yellow-100 mt-1">Awaiting response</div>
                    </div>
                    
                    <!-- Open -->
                    <div class="bg-blue-400/20 backdrop-blur-sm rounded-lg p-4 hover:bg-blue-400/30 transition-all duration-200 cursor-pointer border border-blue-300/30"
                         data-stat-filter="open" 
                         onclick="filterByStatus('open')"
                         title="Click to filter Open tickets">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-blue-100">Open</span>
                            <i class="fas fa-folder-open text-sm text-blue-300"></i>
                        </div>
                        <div class="text-3xl font-bold"><?php echo $stats['open'] ?? 0; ?></div>
                        <div class="text-xs text-blue-100 mt-1">Active tickets</div>
                    </div>
                    
                    <!-- In Progress -->
                    <div class="bg-purple-500/20 backdrop-blur-sm rounded-lg p-4 hover:bg-purple-500/30 transition-all duration-200 cursor-pointer border border-purple-400/30"
                         data-stat-filter="in_progress" 
                         onclick="filterByStatus('in_progress')"
                         title="Click to filter In Progress tickets">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-purple-100">In Progress</span>
                            <i class="fas fa-spinner text-sm text-purple-300"></i>
                        </div>
                        <div class="text-3xl font-bold"><?php echo $stats['in_progress'] ?? 0; ?></div>
                        <div class="text-xs text-purple-100 mt-1">Being worked on</div>
                    </div>
                    
                    <!-- Resolved/Closed -->
                    <div class="bg-green-500/20 backdrop-blur-sm rounded-lg p-4 hover:bg-green-500/30 transition-all duration-200 cursor-pointer border border-green-400/30"
                         data-stat-filter="closed" 
                         onclick="filterByStatus('closed')"
                         title="Click to filter Closed tickets">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-green-100">Resolved</span>
                            <i class="fas fa-check-circle text-sm text-green-300"></i>
                        </div>
                        <div class="text-3xl font-bold"><?php echo $stats['closed'] ?? 0; ?></div>
                        <div class="text-xs text-green-100 mt-1">
                            <?php 
                            $resolveRate = $stats['total'] > 0 ? round(($stats['closed'] / $stats['total']) * 100) : 0;
                            echo $resolveRate . '% completion rate';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Recent Activity Timeline -->
                <div class="lg:col-span-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl p-8 shadow-lg">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-xl font-bold">Recent Activity</h3>
                            <p class="text-blue-100 text-sm mt-1">Ticket trends overview</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <select id="activityPeriod" class="bg-white text-blue-700 font-semibold rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-300 cursor-pointer shadow-lg">
                                <option value="daily">Daily</option>
                                <option value="weekly" selected>Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <div class="bg-white/20 backdrop-blur-sm rounded-lg px-3 py-2">
                                <i class="fas fa-chart-line text-lg"></i>
                            </div>
                        </div>
                    </div>
                    
                    <?php 
                    // Get last 7 days for mini chart
                    $last7Days = array_slice($dailyStats, -7);
                    $maxCount = !empty($last7Days) ? max(array_column($last7Days, 'count')) : 1;
                    $todayCount = !empty($last7Days) ? end($last7Days)['count'] : 0;
                    $yesterdayCount = count($last7Days) > 1 ? $last7Days[count($last7Days) - 2]['count'] : 0;
                    $changePercent = $yesterdayCount > 0 ? round((($todayCount - $yesterdayCount) / $yesterdayCount) * 100) : 0;
                    ?>
                    
                    <!-- Today's Stats -->
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-5 mb-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="text-blue-200 text-sm mb-2">Today's Tickets</div>
                                <div class="text-5xl font-bold mb-3"><?php echo $todayCount; ?></div>
                                <div class="flex items-center text-sm">
                                    <?php if ($changePercent >= 0): ?>
                                        <i class="fas fa-arrow-up text-green-300 mr-2"></i>
                                        <span class="text-green-300 font-medium"><?php echo abs($changePercent); ?>% from yesterday</span>
                                    <?php else: ?>
                                        <i class="fas fa-arrow-down text-red-300 mr-2"></i>
                                        <span class="text-red-300 font-medium"><?php echo abs($changePercent); ?>% from yesterday</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="text-blue-200">
                                <i class="fas fa-ticket-alt text-4xl opacity-50"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Yesterday's Comparison -->
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 mb-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-blue-200 text-xs mb-1">Yesterday</div>
                                <div class="text-2xl font-bold"><?php echo $yesterdayCount; ?></div>
                            </div>
                            <div class="text-right">
                                <div class="text-blue-200 text-xs mb-1">This Week Total</div>
                                <div class="text-2xl font-bold"><?php echo array_sum(array_column($last7Days, 'count')); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mini Bar Chart -->
                    <div id="chartContainer" class="space-y-2">
                        <div class="flex items-center justify-between mb-4">
                            <div class="text-sm text-blue-200 font-medium">Last 7 Days Overview</div>
                            <div class="text-xs text-blue-300">
                                Avg: <?php echo round(array_sum(array_column($last7Days, 'count')) / count($last7Days), 1); ?>/day
                            </div>
                        </div>
                        <div class="flex items-end justify-between gap-2 h-32">
                            <?php foreach ($last7Days as $day): 
                                $height = $maxCount > 0 ? ($day['count'] / $maxCount) * 100 : 0;
                                $isToday = $day === end($last7Days);
                                $isYesterday = $day === $last7Days[count($last7Days) - 2];
                            ?>
                            <div class="flex-1 flex flex-col items-center group cursor-pointer">
                                <div class="w-full <?php echo $isYesterday ? 'bg-blue-400/40' : 'bg-white/20'; ?> rounded-t hover:bg-white/40 transition-all relative" 
                                     style="height: <?php echo max($height, 5); ?>%;"
                                     title="<?php echo date('M d', strtotime($day['date'])); ?>: <?php echo $day['count']; ?> tickets">
                                    <?php if ($isToday): ?>
                                    <div class="absolute -top-7 left-1/2 transform -translate-x-1/2 bg-white text-blue-700 px-3 py-1 rounded-md text-sm font-bold whitespace-nowrap shadow-lg">
                                        <?php echo $day['count']; ?>
                                    </div>
                                    <?php elseif ($isYesterday): ?>
                                    <div class="absolute -top-7 left-1/2 transform -translate-x-1/2 bg-blue-400 text-white px-2 py-0.5 rounded text-xs font-semibold whitespace-nowrap">
                                        <?php echo $day['count']; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="text-xs text-blue-300 mt-2 opacity-75 group-hover:opacity-100 font-medium <?php echo $isYesterday ? 'text-blue-200' : ''; ?>">
                                    <?php echo date('D', strtotime($day['date'])); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Priority Distribution -->
                <div class="lg:col-span-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold mb-1">Priority Distribution</h3>
                    <p class="text-blue-100 text-sm mb-6">Tickets breakdown by priority level</p>
                    <div class="space-y-4">
                        <?php 
                        // Simulate priority data (you can get this from database later)
                        $priorityData = [
                            'urgent' => ['count' => 2, 'color' => '#EF4444', 'bg' => 'bg-red-500', 'icon' => 'fa-fire', 'label' => 'Urgent'],
                            'high' => ['count' => 1, 'color' => '#F59E0B', 'bg' => 'bg-orange-500', 'icon' => 'fa-exclamation-triangle', 'label' => 'High'],
                            'medium' => ['count' => 1, 'color' => '#06B6D4', 'bg' => 'bg-cyan-500', 'icon' => 'fa-info-circle', 'label' => 'Medium'],
                            'low' => ['count' => 1, 'color' => '#10B981', 'bg' => 'bg-green-500', 'icon' => 'fa-check-circle', 'label' => 'Low']
                        ];
                        
                        $totalPriority = array_sum(array_column($priorityData, 'count'));
                        $totalPriority = $totalPriority > 0 ? $totalPriority : 1;
                        
                        foreach ($priorityData as $priority => $data):
                            $percentage = round(($data['count'] / $totalPriority) * 100);
                        ?>
                        <div class="group cursor-pointer hover:bg-white/20 p-2 rounded-lg transition-all">
                            <div class="flex justify-between items-center mb-2">
                                <span class="flex items-center">
                                    <i class="fas <?php echo $data['icon']; ?> mr-2" style="color: <?php echo $data['color']; ?>"></i>
                                    <span class="text-sm"><?php echo $data['label']; ?></span>
                                    <span class="ml-2 text-blue-200 text-xs">(<?php echo $data['count']; ?>)</span>
                                </span>
                                <span class="font-semibold"><?php echo $percentage; ?>%</span>
                            </div>
                            <div class="w-full bg-white/20 backdrop-blur-sm rounded-full h-2 overflow-hidden">
                                <div class="<?php echo $data['bg']; ?> h-2 rounded-full transition-all duration-500 group-hover:scale-105" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 pt-4 border-t border-white/20">
                        <div class="flex justify-between items-center">
                            <div class="text-sm text-blue-200">Total Active</div>
                            <div class="text-xl font-bold"><?php echo $stats['open'] + $stats['in_progress']; ?></div>
                        </div>
                        <div class="mt-2 text-xs text-blue-100">
                            <i class="fas fa-info-circle mr-1"></i>
                            <?php 
                            $urgentCount = 2; // This should come from database
                            echo $urgentCount > 0 ? "$urgentCount urgent tickets need immediate attention" : "No urgent tickets at the moment";
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Activity Summary -->
                <div class="lg:col-span-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl p-6 shadow-lg">
                    <h3 class="text-lg font-semibold mb-1">Activity</h3>
                    <p class="text-blue-100 text-sm mb-6">Tickets employee activity</p>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-white/10 backdrop-blur-sm rounded-lg">
                            <div>
                                <div class="text-2xl font-bold"><?php echo $stats['open'] + $stats['in_progress']; ?></div>
                                <div class="text-blue-200 text-sm">Active Tickets</div>
                            </div>
                            <div class="text-green-300">
                                <i class="fas fa-arrow-up mr-1"></i>
                            </div>
                        </div>
                        <div class="border-t border-white/20 pt-4">
                            <div class="text-xl font-semibold mb-1">Employees</div>
                            <div class="text-blue-100 text-sm mb-3">Number of registered employees</div>
                            <div class="flex justify-between items-center">
                                <div class="text-3xl font-bold"><?php echo $employeeStats['total']; ?></div>
                                <span class="text-sm text-blue-200"><?php echo $userStats['total'] + $employeeStats['total']; ?> total users</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Articles -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold">Recent Tickets</h3>
                        <p class="text-gray-600 text-sm">Latest ticket submissions - Click headers to sort</p>
                    </div>
                    <div class="p-6">
                        <table id="dashboardTable" class="w-full">
                            <thead>
                                <tr class="text-left text-gray-600 text-sm">
                                    <th class="pb-4 cursor-pointer hover:text-blue-600 select-none" onclick="sortTable(0)">
                                        Title <i class="fas fa-sort text-xs ml-1"></i>
                                    </th>
                                    <th class="pb-4 cursor-pointer hover:text-blue-600 select-none" onclick="sortTable(1)">
                                        Status <i class="fas fa-sort text-xs ml-1"></i>
                                    </th>
                                    <th class="pb-4 cursor-pointer hover:text-blue-600 select-none" onclick="sortTable(2)">
                                        Priority <i class="fas fa-sort text-xs ml-1"></i>
                                    </th>
                                    <th class="pb-4 cursor-pointer hover:text-blue-600 select-none" onclick="sortTable(3)">
                                        Date <i class="fas fa-sort text-xs ml-1"></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="text-sm" id="dashboardTableBody">
                                <?php foreach ($recentTickets as $ticket): 
                                    $statusColors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'open' => 'bg-blue-100 text-blue-800',
                                        'in_progress' => 'bg-purple-100 text-purple-800',
                                        'closed' => 'bg-green-100 text-green-800'
                                    ];
                                    $priorityColors = [
                                        'urgent' => 'text-red-600',
                                        'high' => 'text-orange-600',
                                        'medium' => 'text-cyan-600',
                                        'low' => 'text-green-600'
                                    ];
                                ?>
                                <tr class="border-t border-gray-100 hover:bg-gray-50 transition searchable-row" 
                                    data-ticket-row 
                                    data-ticket-id="<?php echo $ticket['id']; ?>"
                                    data-ticket-title="<?php echo htmlspecialchars($ticket['title']); ?>"
                                    data-ticket-status="<?php echo $ticket['status']; ?>"
                                    data-ticket-priority="<?php echo $ticket['priority']; ?>"
                                    data-ticket-date="<?php echo $ticket['created_at']; ?>">
                                    <td class="py-4">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($ticket['title']); ?></div>
                                        <div class="text-gray-500 text-xs"><?php echo htmlspecialchars($ticket['category_name']); ?></div>
                                    </td>
                                    <td class="py-4">
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="py-4">
                                        <span class="font-medium <?php echo $priorityColors[$ticket['priority']] ?? 'text-gray-600'; ?>">
                                            <?php echo ucfirst($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-gray-600">
                                        <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Last Updates -->
                <div class="lg:col-span-1 bg-white rounded-xl shadow-sm">
                    <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-semibold">Last Updates</h3>
                        <select class="text-sm border border-gray-300 rounded px-2 py-1">
                            <option>Today</option>
                            <option>This Week</option>
                            <option>This Month</option>
                        </select>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-user-plus text-blue-500"></i>
                                    <div>
                                        <div class="font-medium text-sm">New Employee</div>
                                    </div>
                                </div>
                                <span class="text-gray-900 font-semibold"><?php echo $employeeStats['total']; ?></span>
                            </div>
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-envelope text-blue-500"></i>
                                    <div>
                                        <div class="font-medium text-sm">New Messages</div>
                                    </div>
                                </div>
                                <span class="text-gray-900 font-semibold"><?php echo count($recentActivity); ?></span>
                            </div>
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-database text-blue-500"></i>
                                    <div>
                                        <div class="font-medium text-sm">Resources</div>
                                    </div>
                                </div>
                                <span class="text-gray-900 font-semibold"><?php echo count($categoryStats); ?></span>
                            </div>
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-ticket-alt text-blue-500"></i>
                                    <div>
                                        <div class="font-medium text-sm">Tickets Add</div>
                                    </div>
                                </div>
                                <span class="text-gray-900 font-semibold"><?php echo $stats['pending'] + $stats['open']; ?></span>
                            </div>
                            <div class="flex items-center justify-between p-3 hover:bg-gray-50 rounded-lg transition">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-newspaper text-blue-500"></i>
                                    <div>
                                        <div class="font-medium text-sm">New Article</div>
                                    </div>
                                </div>
                                <span class="text-gray-900 font-semibold">5</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Wins JavaScript -->
    <script src="../assets/js/helpers.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script src="../assets/js/filters.js"></script>
    
    <script>
        // Filter by status using stat boxes
        function filterByStatus(status) {
            // Update active stat box styling
            document.querySelectorAll('[data-stat-filter]').forEach(box => {
                box.classList.remove('border-blue-500', 'ring-2', 'ring-blue-200');
            });
            
            const activeBox = document.querySelector(`[data-stat-filter="${status}"]`);
            if (activeBox && status !== 'all') {
                activeBox.classList.add('border-blue-500', 'ring-2', 'ring-blue-200');
            }
            
            // Update the filter dropdown if it exists
            const statusFilter = document.getElementById('filter-status');
            if (statusFilter) {
                statusFilter.value = status;
            }
            
            // Trigger filter application
            if (window.TicketFilters && window.TicketFilters.setStatus) {
                window.TicketFilters.setStatus(status);
            }
        }
        
        // Handle activity period filtering
        function handleActivityPeriodChange(period) {
            // This will be connected to backend API in future
            // For now, show a notification
            console.log('Activity period changed to:', period);
            
            // You can add AJAX call here to fetch data for selected period
            // Example:
            // fetch(`api/get-activity-stats.php?period=${period}`)
            //     .then(response => response.json())
            //     .then(data => updateChart(data));
        }
        
        // Dashboard Search Function
        function searchDashboard(query) {
            const searchTerm = query.toLowerCase().trim();
            const rows = document.querySelectorAll('.searchable-row');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show message if no results
            const tableBody = document.getElementById('dashboardTableBody');
            const noResultsRow = document.getElementById('noResultsRow');
            
            if (visibleCount === 0 && searchTerm !== '') {
                if (!noResultsRow) {
                    const tr = document.createElement('tr');
                    tr.id = 'noResultsRow';
                    tr.innerHTML = '<td colspan="4" class="py-8 text-center text-gray-500"><i class="fas fa-search mr-2"></i>No tickets found matching "' + query + '"</td>';
                    tableBody.appendChild(tr);
                }
            } else if (noResultsRow) {
                noResultsRow.remove();
            }
        }
        
        // Table Sorting Function
        let sortDirection = {};
        function sortTable(columnIndex) {
            const table = document.getElementById('dashboardTable');
            const tbody = document.getElementById('dashboardTableBody');
            const rows = Array.from(tbody.querySelectorAll('tr:not(#noResultsRow)'));
            
            // Initialize sort direction for this column
            if (!sortDirection[columnIndex]) {
                sortDirection[columnIndex] = 'asc';
            }
            
            // Toggle direction
            sortDirection[columnIndex] = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
            const direction = sortDirection[columnIndex];
            
            // Sort rows
            rows.sort((a, b) => {
                const aValue = a.cells[columnIndex].textContent.trim();
                const bValue = b.cells[columnIndex].textContent.trim();
                
                // Handle dates (column 3)
                if (columnIndex === 3) {
                    const aDate = new Date(aValue);
                    const bDate = new Date(bValue);
                    return direction === 'asc' ? aDate - bDate : bDate - aDate;
                }
                
                // Handle text/numbers
                if (!isNaN(aValue) && !isNaN(bValue)) {
                    return direction === 'asc' ? aValue - bValue : bValue - aValue;
                }
                
                return direction === 'asc' 
                    ? aValue.localeCompare(bValue) 
                    : bValue.localeCompare(aValue);
            });
            
            // Re-append sorted rows
            rows.forEach(row => tbody.appendChild(row));
            
            // Update sort icons
            const headers = table.querySelectorAll('th i');
            headers.forEach((icon, index) => {
                if (index === columnIndex) {
                    icon.className = `fas fa-sort-${direction === 'asc' ? 'up' : 'down'} text-xs ml-1 text-blue-600`;
                } else {
                    icon.className = 'fas fa-sort text-xs ml-1';
                }
            });
        }
        
        // Dynamic greeting based on time
        function updateGreeting() {
            const hour = new Date().getHours();
            const greetingText = document.getElementById('greetingText');
            
            if (hour >= 5 && hour < 12) {
                greetingText.textContent = 'Good Morning';
            } else if (hour >= 12 && hour < 17) {
                greetingText.textContent = 'Good Afternoon';
            } else if (hour >= 17 && hour < 22) {
                greetingText.textContent = 'Good Evening';
            } else {
                greetingText.textContent = 'Welcome Back';
            }
        }
        
        // Update current date display
        function updateCurrentDate() {
            const dateElement = document.getElementById('currentDate');
            if (dateElement) {
                const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' };
                dateElement.textContent = new Date().toLocaleDateString('en-US', options);
            }
        }
        
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Activity period selector
            const periodSelect = document.getElementById('activityPeriod');
            if (periodSelect) {
                periodSelect.addEventListener('change', function() {
                    handleActivityPeriodChange(this.value);
                });
            }
            // Initialize Quick Wins features
            initTooltips();
            initDarkMode();
            
            // Update greeting based on time
            updateGreeting();
            
            // Update current date
            updateCurrentDate();
            
            // Update last login display (using current time as demo)
            updateLastLogin('<?php echo date('Y-m-d H:i:s'); ?>');
            
            // Initialize time-ago elements
            updateTimeAgo();
            
            // Update time-ago every minute
            setInterval(updateTimeAgo, 60000);
            
            // Update greeting every minute in case time period changes
            setInterval(updateGreeting, 60000);
            
            // Daily Chart
            const dailyCtx = document.getElementById('dailyChart');
            if (dailyCtx) {
                new Chart(dailyCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($chartLabels); ?>,
                        datasets: [{
                            label: 'Tickets',
                            data: <?php echo json_encode($chartData); ?>,
                            backgroundColor: function(context) {
                                const index = context.dataIndex;
                                return index === <?php echo count($chartData) - 1; ?> ? '#3B82F6' : '#4B5563';
                            },
                            borderRadius: 4,
                            barThickness: 30
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#9CA3AF'
                        },
                        grid: {
                            color: '#374151',
                            drawBorder: false
                        }
                    },
                    x: {
                        ticks: {
                            color: '#9CA3AF'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
                });
            }
        });
    </script>
</body>
</html>
