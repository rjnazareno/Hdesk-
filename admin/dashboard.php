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
        <div class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
                <div>
                    <h1 class="text-xl lg:text-2xl font-bold text-gray-900">Welcome Back</h1>
                    <p class="text-sm lg:text-base text-gray-600">
                        Hello <?php echo htmlspecialchars($currentUser['full_name']); ?>, Good Morning!
                        <span class="ml-2" id="lastLoginDisplay"></span>
                    </p>
                </div>
                <div class="hidden lg:flex items-center space-x-4">
                    <div class="relative">
                        <input 
                            type="text" 
                            placeholder="Search Dashboard" 
                            class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <button id="darkModeToggle" class="p-2 text-gray-600 hover:text-gray-900" title="Toggle dark mode">
                        <i id="dark-mode-icon" class="fas fa-moon"></i>
                    </button>
                    <button class="p-2 text-gray-600 hover:text-gray-900" title="Filters">
                        <i class="fas fa-sliders-h"></i>
                    </button>
                    <button class="p-2 text-gray-600 hover:text-gray-900" title="Bookmarks">
                        <i class="far fa-bookmark"></i>
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
            
            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="text-sm text-gray-600">Total</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo $stats['total'] ?? 0; ?></div>
                </div>
                <div class="bg-yellow-50 rounded-lg shadow-sm p-4">
                    <div class="text-sm text-yellow-600">Pending</div>
                    <div class="text-2xl font-bold text-yellow-900"><?php echo $stats['pending'] ?? 0; ?></div>
                </div>
                <div class="bg-blue-50 rounded-lg shadow-sm p-4">
                    <div class="text-sm text-blue-600">Open</div>
                    <div class="text-2xl font-bold text-blue-900"><?php echo $stats['open'] ?? 0; ?></div>
                </div>
                <div class="bg-purple-50 rounded-lg shadow-sm p-4">
                    <div class="text-sm text-purple-600">In Progress</div>
                    <div class="text-2xl font-bold text-purple-900"><?php echo $stats['in_progress'] ?? 0; ?></div>
                </div>
                <div class="bg-gray-50 rounded-lg shadow-sm p-4">
                    <div class="text-sm text-gray-600">Closed</div>
                    <div class="text-2xl font-bold text-gray-900"><?php echo $stats['closed'] ?? 0; ?></div>
                </div>
            </div>
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
                <!-- Daily Tickets Chart -->
                <div class="lg:col-span-1 bg-gray-800 text-white rounded-xl p-6">
                    <h3 class="text-lg font-semibold mb-1">Daily Tickets</h3>
                    <p class="text-gray-400 text-sm mb-6">Check on each column for more details</p>
                    <div style="height: 250px; position: relative;">
                        <canvas id="dailyChart"></canvas>
                    </div>
                </div>

                <!-- Tickets by Status -->
                <div class="lg:col-span-1 bg-gray-800 text-white rounded-xl p-6">
                    <h3 class="text-lg font-semibold mb-1">Tickets by Status</h3>
                    <p class="text-gray-400 text-sm mb-6">Open vs Pending vs Closed tickets</p>
                    <div class="space-y-4">
                        <?php 
                        $statusColors = [
                            'pending' => ['bg' => 'bg-yellow-500', 'text' => 'Pending'],
                            'open' => ['bg' => 'bg-blue-500', 'text' => 'Open'],
                            'closed' => ['bg' => 'bg-blue-400', 'text' => 'Closed']
                        ];
                        
                        // Calculate total for these three statuses only
                        $totalForChart = ($stats['pending'] ?? 0) + ($stats['open'] ?? 0) + ($stats['closed'] ?? 0);
                        $totalForChart = $totalForChart > 0 ? $totalForChart : 1;
                        
                        foreach (['pending', 'open', 'closed'] as $status):
                            $count = $stats[$status] ?? 0;
                            $percentage = round(($count / $totalForChart) * 100);
                            $color = $statusColors[$status];
                        ?>
                        <div>
                            <div class="flex justify-between mb-2">
                                <span><i class="fas fa-circle mr-2" style="color: <?php echo $status === 'pending' ? '#EAB308' : ($status === 'open' ? '#3B82F6' : '#60A5FA'); ?>"></i><?php echo $color['text']; ?> (<?php echo $count; ?>)</span>
                                <span><?php echo $percentage; ?>%</span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-2">
                                <div class="<?php echo $color['bg']; ?> h-2 rounded-full transition-all duration-500" style="width: <?php echo $percentage; ?>%"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="mt-6 pt-4 border-t border-gray-700">
                        <div class="flex justify-between items-center">
                            <div class="flex space-x-2">
                                <span class="flex items-center"><i class="fas fa-circle text-yellow-500 text-xs mr-1"></i>Pending</span>
                                <span class="flex items-center"><i class="fas fa-circle text-blue-500 text-xs mr-1"></i>Open</span>
                                <span class="flex items-center"><i class="fas fa-circle text-blue-400 text-xs mr-1"></i>Closed</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Activity Summary -->
                <div class="lg:col-span-1 bg-gray-800 text-white rounded-xl p-6">
                    <h3 class="text-lg font-semibold mb-1">Activity</h3>
                    <p class="text-gray-400 text-sm mb-6">Tickets employee activity</p>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-3 bg-gray-700 rounded-lg">
                            <div>
                                <div class="text-2xl font-bold"><?php echo $stats['open'] + $stats['in_progress']; ?></div>
                                <div class="text-gray-400 text-sm">Active Tickets</div>
                            </div>
                            <div class="text-green-400">
                                <i class="fas fa-arrow-up mr-1"></i>
                            </div>
                        </div>
                        <div class="border-t border-gray-700 pt-4">
                            <div class="text-xl font-semibold mb-1">Employees</div>
                            <div class="text-gray-400 text-sm mb-3">Number of registered employees</div>
                            <div class="flex justify-between items-center">
                                <div class="text-3xl font-bold"><?php echo $employeeStats['total']; ?></div>
                                <span class="text-sm text-gray-400"><?php echo $userStats['total'] + $employeeStats['total']; ?> total users</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Recent Articles -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold">Recent Article</h3>
                        <p class="text-gray-600 text-sm">Article writing that is more details here</p>
                    </div>
                    <div class="p-6">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-gray-600 text-sm">
                                    <th class="pb-4">Title</th>
                                    <th class="pb-4">Views</th>
                                    <th class="pb-4">Changes</th>
                                    <th class="pb-4">Ratings</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <?php foreach ($recentTickets as $ticket): ?>
                                <tr class="border-t border-gray-100">
                                    <td class="py-4">
                                        <div class="font-medium"><?php echo htmlspecialchars($ticket['title']); ?></div>
                                        <div class="text-gray-500 text-xs"><?php echo htmlspecialchars($ticket['category_name']); ?></div>
                                    </td>
                                    <td class="py-4"><?php echo $ticket['id'] * 123; ?></td>
                                    <td class="py-4"><?php echo $ticket['id'] % 3; ?></td>
                                    <td class="py-4">
                                        <?php 
                                        $rating = 4;
                                        for ($i = 0; $i < 5; $i++): 
                                        ?>
                                        <i class="fas fa-star <?php echo $i < $rating ? 'text-yellow-400' : 'text-gray-300'; ?>"></i>
                                        <?php endfor; ?>
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
    
    <script>
        // Wait for DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Quick Wins features
            initTooltips();
            initDarkMode();
            
            // Update last login display (using current time as demo)
            updateLastLogin('<?php echo date('Y-m-d H:i:s'); ?>');
            
            // Initialize time-ago elements
            updateTimeAgo();
            
            // Update time-ago every minute
            setInterval(updateTimeAgo, 60000);
            
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
