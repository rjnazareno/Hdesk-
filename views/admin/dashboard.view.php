<?php 
// Include layout header
$pageTitle = 'Dashboard - IT Help Desk';
$includeChartJs = true;
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

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
                <div class="space-y-2 mb-6">
                    <?php 
                    $barColors = ['#ef4444', '#f59e0b', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#8b5cf6'];
                    $colorIndex = 0;
                    foreach ($last7Days as $day): 
                        $percentage = $maxCount > 0 ? ($day['count'] / $maxCount) * 100 : 0;
                        $barColor = $barColors[$colorIndex % count($barColors)];
                        $colorIndex++;
                    ?>
                    <div>
                        <div class="flex justify-between text-xs text-blue-200 mb-1">
                            <span><?php echo date('D', strtotime($day['date'])); ?></span>
                            <span><?php echo $day['count']; ?></span>
                        </div>
                        <div class="w-full bg-white/10 rounded-full h-2">
                            <div class="rounded-full h-2 transition-all" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $barColor; ?>"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Weekly Summary -->
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                    <div class="flex justify-between items-center mb-3">
                        <div class="text-sm font-medium text-white">Average Daily Tickets</div>
                        <div class="text-2xl font-bold text-white"><?php echo round(array_sum(array_column($last7Days, 'count')) / count($last7Days), 1); ?></div>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center text-white/80">
                            <i class="fas fa-calendar-week mr-2 text-white/60"></i>
                            <span>Last 7 days trend</span>
                        </div>
                        <div class="flex items-center">
                            <?php 
                            $weekTrend = $changePercent >= 0 ? 'up' : 'down';
                            $trendColor = $changePercent >= 0 ? 'text-green-300' : 'text-red-300';
                            ?>
                            <i class="fas fa-arrow-<?php echo $weekTrend; ?> mr-1 <?php echo $trendColor; ?>"></i>
                            <span class="<?php echo $trendColor; ?> font-semibold"><?php echo abs($changePercent); ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Chart -->
            <div class="lg:col-span-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold mb-1">Daily Ticket Volume</h3>
                <p class="text-blue-100 text-sm mb-6">Last 10 days ticket trends</p>
                <div class="h-64 mb-6">
                    <canvas id="dailyChart"></canvas>
                </div>
                
                <!-- Category Trends -->
                <div class="space-y-2 mb-6">
                    <?php
                    // Get top categories with their counts
                    $topCategories = array_slice($categoryStats, 0, 4);
                    $maxCategoryCount = !empty($topCategories) ? max(array_column($topCategories, 'ticket_count')) : 1;
                    $categoryColors = [
                        ['bg' => '#ef4444', 'label' => 'Hardware'],
                        ['bg' => '#f59e0b', 'label' => 'Software'],
                        ['bg' => '#22c55e', 'label' => 'Network'],
                        ['bg' => '#8b5cf6', 'label' => 'Other']
                    ];
                    
                    foreach ($topCategories as $index => $category):
                        $percentage = $maxCategoryCount > 0 ? ($category['ticket_count'] / $maxCategoryCount) * 100 : 0;
                        $color = $categoryColors[$index]['bg'] ?? '#60a5fa';
                    ?>
                    <div>
                        <div class="flex justify-between text-xs text-blue-200 mb-1">
                            <span class="truncate max-w-[150px]"><?php echo htmlspecialchars($category['name']); ?></span>
                            <span class="ml-2"><?php echo $category['ticket_count']; ?></span>
                        </div>
                        <div class="w-full bg-white/10 rounded-full h-1.5">
                            <div class="rounded-full h-1.5 transition-all" style="width: <?php echo $percentage; ?>%; background-color: <?php echo $color; ?>"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Volume Summary -->
                <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-white/60 text-xs mb-1">Peak Day</div>
                            <div class="text-lg font-bold text-white"><?php echo !empty($dailyStats) ? max(array_column($dailyStats, 'count')) : 0; ?></div>
                        </div>
                        <div class="text-center border-l border-r border-white/20">
                            <div class="text-white/60 text-xs mb-1">Average</div>
                            <div class="text-lg font-bold text-white"><?php echo !empty($dailyStats) ? round(array_sum(array_column($dailyStats, 'count')) / count($dailyStats), 1) : 0; ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-white/60 text-xs mb-1">Total (10d)</div>
                            <div class="text-lg font-bold text-white"><?php echo !empty($dailyStats) ? array_sum(array_column($dailyStats, 'count')) : 0; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="lg:col-span-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold mb-1">Status Distribution</h3>
                <p class="text-blue-100 text-sm mb-6">Tickets breakdown by status</p>
                <div class="space-y-3">
                    <?php
                    $statusData = [
                        ['label' => 'Pending', 'count' => $stats['pending'], 'icon' => 'fa-clock', 'color' => '#fbbf24', 'bg' => 'bg-yellow-400', 'iconBg' => 'bg-yellow-500/30'],
                        ['label' => 'Open', 'count' => $stats['open'], 'icon' => 'fa-folder-open', 'color' => '#60a5fa', 'bg' => 'bg-blue-400', 'iconBg' => 'bg-blue-400/30'],
                        ['label' => 'In Progress', 'count' => $stats['in_progress'], 'icon' => 'fa-spinner', 'color' => '#c084fc', 'bg' => 'bg-purple-400', 'iconBg' => 'bg-purple-400/30'],
                        ['label' => 'Closed', 'count' => $stats['closed'], 'icon' => 'fa-check-circle', 'color' => '#4ade80', 'bg' => 'bg-green-400', 'iconBg' => 'bg-green-400/30']
                    ];
                    
                    foreach ($statusData as $data):
                        $percentage = $stats['total'] > 0 ? round(($data['count'] / $stats['total']) * 100) : 0;
                    ?>
                    <div class="group cursor-pointer hover:bg-white/10 bg-white/5 backdrop-blur-sm p-3 rounded-lg transition-all border border-white/10">
                        <div class="flex justify-between items-center mb-2">
                            <span class="flex items-center">
                                <div class="<?php echo $data['iconBg']; ?> backdrop-blur-sm rounded-lg p-2 mr-3">
                                    <i class="fas <?php echo $data['icon']; ?>" style="color: <?php echo $data['color']; ?>"></i>
                                </div>
                                <div>
                                    <span class="text-sm font-medium text-white"><?php echo $data['label']; ?></span>
                                    <span class="ml-2 text-white/60 text-xs">(<?php echo $data['count']; ?>)</span>
                                </div>
                            </span>
                            <span class="font-bold text-lg text-white"><?php echo $percentage; ?>%</span>
                        </div>
                        <div class="w-full bg-white/20 backdrop-blur-sm rounded-full h-2.5 overflow-hidden shadow-inner">
                            <div class="<?php echo $data['bg']; ?> h-2.5 rounded-full transition-all duration-500 group-hover:scale-105 shadow-lg" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-6 bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20">
                    <div class="flex justify-between items-center mb-3">
                        <div class="text-sm font-medium text-white">Total Active Tickets</div>
                        <div class="text-2xl font-bold text-white"><?php echo $stats['open'] + $stats['in_progress']; ?></div>
                    </div>
                    <div class="flex items-center text-xs text-white/80 bg-white/10 rounded px-3 py-2">
                        <i class="fas fa-info-circle mr-2 text-white/60"></i>
                        <span>
                        <?php 
                        $urgentCount = 2; // This should come from database
                        echo $urgentCount > 0 ? "$urgentCount urgent tickets need immediate attention" : "No urgent tickets at the moment";
                        ?>
                        </span>
                    </div>
                </div>
            </div>

        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Recent Tickets -->
            <div class="lg:col-span-2 bg-gradient-to-br from-white to-blue-50 rounded-xl shadow-lg border border-blue-100">
                <div class="p-6 border-b border-blue-100 bg-gradient-to-r from-blue-500 to-blue-600 rounded-t-xl">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white flex items-center">
                                <i class="fas fa-ticket-alt mr-2"></i>
                                Recent Tickets
                            </h3>
                            <p class="text-blue-100 text-sm mt-1">Latest ticket submissions - Click headers to sort</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="bg-white/20 backdrop-blur-sm text-white text-xs font-semibold px-3 py-1 rounded-full">
                                <?php echo count($recentTickets); ?> tickets
                            </span>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table id="dashboardTable" class="w-full">
                            <thead>
                                <tr class="text-left text-gray-700 text-xs uppercase tracking-wider border-b-2 border-blue-200">
                                    <th class="pb-4 cursor-pointer hover:text-blue-600 select-none transition-colors group" onclick="sortTable(0)">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-alt mr-2 text-blue-500"></i>
                                            <span>Title</span>
                                            <i class="fas fa-sort text-xs ml-2 opacity-50 group-hover:opacity-100"></i>
                                        </div>
                                    </th>
                                    <th class="pb-4 cursor-pointer hover:text-blue-600 select-none transition-colors group" onclick="sortTable(1)">
                                        <div class="flex items-center">
                                            <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                                            <span>Status</span>
                                            <i class="fas fa-sort text-xs ml-2 opacity-50 group-hover:opacity-100"></i>
                                        </div>
                                    </th>
                                    <th class="pb-4 cursor-pointer hover:text-blue-600 select-none transition-colors group" onclick="sortTable(2)">
                                        <div class="flex items-center">
                                            <i class="fas fa-flag mr-2 text-blue-500"></i>
                                            <span>Priority</span>
                                            <i class="fas fa-sort text-xs ml-2 opacity-50 group-hover:opacity-100"></i>
                                        </div>
                                    </th>
                                    <th class="pb-4 cursor-pointer hover:text-blue-600 select-none transition-colors group" onclick="sortTable(3)">
                                        <div class="flex items-center">
                                            <i class="fas fa-calendar mr-2 text-blue-500"></i>
                                            <span>Date</span>
                                            <i class="fas fa-sort text-xs ml-2 opacity-50 group-hover:opacity-100"></i>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="text-sm" id="dashboardTableBody">
                                <?php foreach ($recentTickets as $ticket): 
                                    $statusColors = [
                                        'pending' => 'bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800 border-yellow-300',
                                        'open' => 'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 border-blue-300',
                                        'in_progress' => 'bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 border-purple-300',
                                        'closed' => 'bg-gradient-to-r from-green-100 to-green-200 text-green-800 border-green-300'
                                    ];
                                    $statusIcons = [
                                        'pending' => 'fa-clock',
                                        'open' => 'fa-folder-open',
                                        'in_progress' => 'fa-spinner fa-spin',
                                        'closed' => 'fa-check-circle'
                                    ];
                                    $priorityColors = [
                                        'urgent' => 'text-red-600 bg-red-50 px-2 py-1 rounded-lg font-bold',
                                        'high' => 'text-orange-600 bg-orange-50 px-2 py-1 rounded-lg font-bold',
                                        'medium' => 'text-cyan-600 bg-cyan-50 px-2 py-1 rounded-lg font-semibold',
                                        'low' => 'text-green-600 bg-green-50 px-2 py-1 rounded-lg font-medium'
                                    ];
                                    $priorityIcons = [
                                        'urgent' => 'fa-exclamation-triangle',
                                        'high' => 'fa-arrow-up',
                                        'medium' => 'fa-minus',
                                        'low' => 'fa-arrow-down'
                                    ];
                                ?>
                                <tr class="border-b border-blue-50 hover:bg-blue-50/50 transition-all duration-200 cursor-pointer group searchable-row" 
                                    data-ticket-row 
                                    data-ticket-id="<?php echo $ticket['id']; ?>"
                                    data-ticket-title="<?php echo htmlspecialchars($ticket['title']); ?>"
                                    data-ticket-status="<?php echo $ticket['status']; ?>"
                                    data-ticket-priority="<?php echo $ticket['priority']; ?>"
                                    data-ticket-date="<?php echo $ticket['created_at']; ?>"
                                    onclick="window.location.href='view_ticket.php?id=<?php echo $ticket['id']; ?>'"
                                    title="Click to view ticket details">
                                    <td class="py-4 pr-4">
                                        <div class="flex items-start">
                                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm mr-3 group-hover:scale-110 transition-transform">
                                                #<?php echo $ticket['id']; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors truncate">
                                                    <?php echo htmlspecialchars($ticket['title']); ?>
                                                </div>
                                                <div class="flex items-center text-gray-500 text-xs mt-1">
                                                    <i class="fas fa-folder text-blue-400 mr-1"></i>
                                                    <?php echo htmlspecialchars($ticket['category_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 pr-4">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold border <?php echo $statusColors[$ticket['status']] ?? 'bg-gray-100 text-gray-800 border-gray-300'; ?> shadow-sm">
                                            <i class="fas <?php echo $statusIcons[$ticket['status']] ?? 'fa-circle'; ?> mr-1.5"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 pr-4">
                                        <span class="inline-flex items-center <?php echo $priorityColors[$ticket['priority']] ?? 'text-gray-600 bg-gray-50 px-2 py-1 rounded-lg'; ?>">
                                            <i class="fas <?php echo $priorityIcons[$ticket['priority']] ?? 'fa-circle'; ?> mr-1.5 text-xs"></i>
                                            <?php echo ucfirst($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-gray-600">
                                        <div class="flex items-center">
                                            <i class="far fa-calendar-alt mr-2 text-blue-400"></i>
                                            <span class="font-medium"><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></span>
                                        </div>
                                        <div class="text-xs text-gray-400 mt-1">
                                            <?php echo date('h:i A', strtotime($ticket['created_at'])); ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- View All Button -->
                    <div class="mt-6 pt-4 border-t border-blue-100 text-center">
                        <a href="tickets.php" class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white font-semibold rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-200 shadow-md hover:shadow-lg">
                            <i class="fas fa-list mr-2"></i>
                            View All Tickets
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Last Updates -->
            <div class="lg:col-span-1 bg-gradient-to-br from-white to-purple-50 rounded-xl shadow-lg border border-purple-100">
                <div class="p-6 border-b border-purple-100 bg-gradient-to-r from-purple-500 to-purple-600 rounded-t-xl">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold text-white flex items-center">
                                <i class="fas fa-bell mr-2"></i>
                                Activity Feed
                            </h3>
                            <p class="text-purple-100 text-xs mt-1">Latest system updates</p>
                        </div>
                        <select class="text-xs bg-white/20 backdrop-blur-sm text-white font-semibold border border-white/30 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-white/50 cursor-pointer">
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-3">
                        <!-- New Employee -->
                        <div class="group p-4 hover:bg-purple-50/50 rounded-xl transition-all duration-200 border border-transparent hover:border-purple-200 cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i class="fas fa-user-plus text-white"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm text-gray-900">New Employees</div>
                                        <div class="text-xs text-gray-500">Total registered</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-2xl font-bold text-gray-900"><?php echo $employeeStats['total']; ?></span>
                                    <i class="fas fa-chevron-right ml-2 text-gray-400 group-hover:text-blue-500 transition-colors"></i>
                                </div>
                            </div>
                        </div>

                        <!-- New Messages -->
                        <div class="group p-4 hover:bg-purple-50/50 rounded-xl transition-all duration-200 border border-transparent hover:border-purple-200 cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i class="fas fa-envelope text-white"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm text-gray-900">New Messages</div>
                                        <div class="text-xs text-gray-500">Recent activities</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-2xl font-bold text-gray-900"><?php echo count($recentActivity); ?></span>
                                    <i class="fas fa-chevron-right ml-2 text-gray-400 group-hover:text-green-500 transition-colors"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Resources -->
                        <div class="group p-4 hover:bg-purple-50/50 rounded-xl transition-all duration-200 border border-transparent hover:border-purple-200 cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-purple-500 to-purple-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i class="fas fa-database text-white"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm text-gray-900">Categories</div>
                                        <div class="text-xs text-gray-500">Available resources</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-2xl font-bold text-gray-900"><?php echo count($categoryStats); ?></span>
                                    <i class="fas fa-chevron-right ml-2 text-gray-400 group-hover:text-purple-500 transition-colors"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Active Tickets -->
                        <div class="group p-4 hover:bg-purple-50/50 rounded-xl transition-all duration-200 border border-transparent hover:border-purple-200 cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i class="fas fa-ticket-alt text-white"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm text-gray-900">Active Tickets</div>
                                        <div class="text-xs text-gray-500">Pending & Open</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-2xl font-bold text-gray-900"><?php echo $stats['pending'] + $stats['open']; ?></span>
                                    <i class="fas fa-chevron-right ml-2 text-gray-400 group-hover:text-orange-500 transition-colors"></i>
                                </div>
                            </div>
                        </div>

                        <!-- New Articles -->
                        <div class="group p-4 hover:bg-purple-50/50 rounded-xl transition-all duration-200 border border-transparent hover:border-purple-200 cursor-pointer">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-pink-500 to-pink-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <i class="fas fa-newspaper text-white"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-sm text-gray-900">Knowledge Base</div>
                                        <div class="text-xs text-gray-500">New articles</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-2xl font-bold text-gray-900">5</span>
                                    <i class="fas fa-chevron-right ml-2 text-gray-400 group-hover:text-pink-500 transition-colors"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-6 pt-4 border-t border-purple-100">
                        <a href="#" class="flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-purple-500 to-purple-600 text-white font-semibold rounded-lg hover:from-purple-600 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg">
                            <i class="fas fa-chart-bar mr-2"></i>
                            View Full Report
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard-specific JavaScript -->
<script src="../assets/js/filters.js"></script>
<script>
    // Last login timestamp from PHP
    const lastLogin = '<?php echo date('Y-m-d H:i:s'); ?>';
    
    // Filter by status using stat boxes
    function filterByStatus(status) {
        const rows = document.querySelectorAll('[data-ticket-row]');
        const statBoxes = document.querySelectorAll('[data-stat-filter]');
        
        // Update active stat box styling
        statBoxes.forEach(box => {
            box.classList.remove('ring-2', 'ring-white');
        });
        document.querySelector(`[data-stat-filter="${status}"]`)?.classList.add('ring-2', 'ring-white');
        
        // Filter rows
        rows.forEach(row => {
            if (status === 'all') {
                row.style.display = '';
            } else {
                const rowStatus = row.getAttribute('data-ticket-status');
                row.style.display = rowStatus === status ? '' : 'none';
            }
        });
    }
    
    // Search dashboard tickets
    function searchDashboard(query) {
        const rows = document.querySelectorAll('[data-ticket-row]');
        const searchTerm = query.toLowerCase();
        
        rows.forEach(row => {
            const title = row.getAttribute('data-ticket-title').toLowerCase();
            const status = row.getAttribute('data-ticket-status').toLowerCase();
            const priority = row.getAttribute('data-ticket-priority').toLowerCase();
            
            const matches = title.includes(searchTerm) || 
                          status.includes(searchTerm) || 
                          priority.includes(searchTerm);
            
            row.style.display = matches ? '' : 'none';
        });
    }
    
    // Table sorting
    let sortDirection = {};
    function sortTable(columnIndex) {
        const table = document.getElementById('dashboardTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        
        // Toggle sort direction
        sortDirection[columnIndex] = sortDirection[columnIndex] === 'asc' ? 'desc' : 'asc';
        const direction = sortDirection[columnIndex];
        
        rows.sort((a, b) => {
            const aText = a.cells[columnIndex].textContent.trim();
            const bText = b.cells[columnIndex].textContent.trim();
            
            if (direction === 'asc') {
                return aText.localeCompare(bText);
            } else {
                return bText.localeCompare(aText);
            }
        });
        
        rows.forEach(row => tbody.appendChild(row));
    }
    
    // Activity period change handler
    function handleActivityPeriodChange(period) {
        console.log('Changed to period:', period);
        // TODO: Fetch and update data based on period
    }
    
    // Initialize dashboard
    document.addEventListener('DOMContentLoaded', function() {
        // Daily Chart
        const dailyCtx = document.getElementById('dailyChart');
        if (dailyCtx) {
            const gradient = dailyCtx.getContext('2d').createLinearGradient(0, 0, 0, 400);
            
            new Chart(dailyCtx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($chartData['labels']); ?>,
                    datasets: [{
                        label: 'Tickets',
                        data: <?php echo json_encode($chartData['data']); ?>,
                        backgroundColor: [
                            '#ef4444', // Red
                            '#f59e0b', // Orange
                            '#eab308', // Yellow
                            '#22c55e', // Green
                            '#06b6d4', // Cyan
                            '#60a5fa', // Light Blue
                            '#8b5cf6', // Purple
                            '#ec4899', // Pink
                            '#f97316', // Deep Orange
                            '#10b981'  // Emerald
                        ],
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 35,
                        hoverBackgroundColor: [
                            '#dc2626', // Darker Red
                            '#ea580c', // Darker Orange
                            '#ca8a04', // Darker Yellow
                            '#16a34a', // Darker Green
                            '#0891b2', // Darker Cyan
                            '#3b82f6', // Darker Blue
                            '#7c3aed', // Darker Purple
                            '#db2777', // Darker Pink
                            '#ea580c', // Darker Deep Orange
                            '#059669'  // Darker Emerald
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1000,
                        easing: 'easeInOutQuart',
                        delay: (context) => {
                            let delay = 0;
                            if (context.type === 'data' && context.mode === 'default') {
                                delay = context.dataIndex * 100;
                            }
                            return delay;
                        }
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(255, 255, 255, 0.95)',
                            titleColor: '#1f2937',
                            bodyColor: '#4b5563',
                            borderColor: 'rgba(59, 130, 246, 0.3)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: true,
                            boxPadding: 6,
                            usePointStyle: true,
                            callbacks: {
                                title: function(context) {
                                    return context[0].label;
                                },
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    label += context.parsed.y;
                                    if (context.parsed.y === 1) {
                                        label += ' ticket';
                                    } else {
                                        label += ' tickets';
                                    }
                                    return label;
                                },
                                afterLabel: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed.y / total) * 100).toFixed(1);
                                    return percentage + '% of total';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.9)',
                                font: {
                                    size: 12,
                                    weight: '500'
                                },
                                padding: 8,
                                stepSize: 1
                            },
                            grid: {
                                color: 'rgba(255, 255, 255, 0.15)',
                                drawBorder: false,
                                lineWidth: 1
                            },
                            border: {
                                display: false
                            }
                        },
                        x: {
                            ticks: {
                                color: 'rgba(255, 255, 255, 0.9)',
                                font: {
                                    size: 11,
                                    weight: '500'
                                },
                                padding: 8
                            },
                            grid: {
                                display: false
                            },
                            border: {
                                display: false
                            }
                        }
                    },
                    onHover: (event, activeElements) => {
                        event.native.target.style.cursor = activeElements.length > 0 ? 'pointer' : 'default';
                    }
                }
            });
        }
    });
</script>

<?php 
// Include layout footer
include __DIR__ . '/../layouts/footer.php'; 
?>
