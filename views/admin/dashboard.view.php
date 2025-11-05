<?php 
// Include layout header
$pageTitle = 'Dashboard - IT Help Desk';
$includeChartJs = true;
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Top Bar -->
    <div class="bg-slate-800/50 border-b border-slate-700/50 backdrop-blur-md">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <div class="flex items-center space-x-4">
                <div class="hidden lg:block">
                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white text-sm font-semibold">
                        <?php echo strtoupper(substr($currentUser['full_name'], 0, 1)); ?>
                    </div>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-white">
                        <span id="greetingText">Good Morning</span>, <?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?>
                    </h1>
                    <div class="flex items-center space-x-3 text-sm text-slate-400 mt-0.5">
                        <span class="flex items-center">
                            <span id="lastLoginDisplay">Last login: Loading...</span>
                        </span>
                        <span class="hidden md:flex items-center">
                            <?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?>
                        </span>
                        <span class="hidden md:flex items-center">
                            <span id="currentDate"></span>
                        </span>
                    </div>
                </div>
            </div>
            <div class="hidden lg:flex items-center space-x-2">
                <div class="relative">
                    <input 
                        id="dashboardSearch"
                        type="text" 
                        placeholder="Search tickets..." 
                        class="pl-8 pr-3 py-1.5 text-sm border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 focus:outline-none focus:border-cyan-500 transition"
                        onkeyup="searchDashboard(this.value)"
                    >
                    <i class="fas fa-search absolute left-2.5 top-2.5 text-slate-400 text-xs"></i>
                </div>
                <button class="p-2 text-slate-400 hover:text-cyan-400 transition" title="Filters">
                    <i class="fas fa-sliders text-sm"></i>
                </button>
                <button class="p-2 text-slate-400 hover:text-cyan-400 relative transition" title="Notifications">
                    <i class="far fa-bell text-sm"></i>
                    <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                </button>
                <div class="flex items-center space-x-2">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=0f172a&color=06b6d4" 
                         alt="User" 
                         class="w-8 h-8 rounded-full border border-cyan-500/30"
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
                    <span class="inline-flex items-center text-sm font-medium text-cyan-400">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        Dashboard
                    </span>
                </li>
            </ol>
        </nav>
        
        <!-- Analytics Overview -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-6 mb-6 rounded-xl backdrop-blur">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-lg font-semibold text-white">Ticket Analytics</h2>
                    <p class="text-sm text-slate-400 mt-0.5">Real-time overview of your helpdesk performance</p>
                </div>
            </div>
            
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                <!-- Total Tickets -->
                <div class="bg-gradient-to-br from-slate-700/50 to-slate-700/20 border border-slate-600/50 p-4 hover:border-cyan-500/50 transition-colors cursor-pointer rounded-lg"
                     data-stat-filter="all" 
                     onclick="filterByStatus('all')"
                     title="Click to show all tickets">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-slate-400 uppercase tracking-wide">Total</span>
                    </div>
                    <div class="text-2xl font-semibold text-white"><?php echo $stats['total'] ?? 0; ?></div>
                    <div class="text-xs text-slate-400 mt-1">All tickets</div>
                </div>
                
                <!-- Pending -->
                <div class="border border-yellow-500/30 bg-gradient-to-br from-yellow-500/10 to-yellow-500/5 p-4 hover:border-yellow-500/50 transition-colors cursor-pointer rounded-lg"
                     data-stat-filter="pending" 
                     onclick="filterByStatus('pending')"
                     title="Click to filter Pending tickets">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-yellow-400 uppercase tracking-wide">Pending</span>
                    </div>
                    <div class="text-2xl font-semibold text-white"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="text-xs text-yellow-400 mt-1">Awaiting</div>
                </div>
                
                <!-- Open -->
                <div class="border border-blue-500/30 bg-gradient-to-br from-blue-500/10 to-blue-500/5 p-4 hover:border-blue-500/50 transition-colors cursor-pointer rounded-lg"
                     data-stat-filter="open" 
                     onclick="filterByStatus('open')"
                     title="Click to filter Open tickets">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-blue-400 uppercase tracking-wide">Open</span>
                    </div>
                    <div class="text-2xl font-semibold text-white"><?php echo $stats['open'] ?? 0; ?></div>
                    <div class="text-xs text-blue-400 mt-1">Active</div>
                </div>
                
                <!-- In Progress -->
                <div class="border border-purple-500/30 bg-gradient-to-br from-purple-500/10 to-purple-500/5 p-4 hover:border-purple-500/50 transition-colors cursor-pointer rounded-lg"
                     data-stat-filter="in_progress" 
                     onclick="filterByStatus('in_progress')"
                     title="Click to filter In Progress tickets">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-purple-400 uppercase tracking-wide">In Progress</span>
                    </div>
                    <div class="text-2xl font-semibold text-white"><?php echo $stats['in_progress'] ?? 0; ?></div>
                    <div class="text-xs text-purple-400 mt-1">Working</div>
                </div>
                
                <!-- Resolved/Closed -->
                <div class="border border-green-500/30 bg-gradient-to-br from-green-500/10 to-green-500/5 p-4 hover:border-green-500/50 transition-colors cursor-pointer rounded-lg"
                     data-stat-filter="closed" 
                     onclick="filterByStatus('closed')"
                     title="Click to filter Closed tickets">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-green-400 uppercase tracking-wide">Resolved</span>
                    </div>
                    <div class="text-2xl font-semibold text-white"><?php echo $stats['closed'] ?? 0; ?></div>
                    <div class="text-xs text-green-400 mt-1">
                        <?php 
                        $resolveRate = $stats['total'] > 0 ? round(($stats['closed'] / $stats['total']) * 100) : 0;
                        echo $resolveRate . '%';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
            <!-- Recent Activity Timeline -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-6 rounded-lg">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-white">Recent Activity</h3>
                        <p class="text-sm text-slate-400 mt-0.5">Ticket trends overview</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <select id="activityPeriod" class="text-sm border border-slate-600 bg-slate-700/50 text-white px-3 py-1.5 focus:outline-none focus:border-cyan-500 rounded">
                            <option value="daily">Daily</option>
                            <option value="weekly" selected>Weekly</option>
                            <option value="monthly">Monthly</option>
                            <option value="yearly">Yearly</option>
                        </select>
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
                <div class="border border-slate-600/50 bg-slate-700/30 p-5 mb-4 rounded-lg">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="text-sm text-slate-400 mb-2">Today's Tickets</div>
                            <div class="text-4xl font-semibold text-white mb-3"><?php echo $todayCount; ?></div>
                            <div class="flex items-center text-sm">
                                <?php if ($changePercent >= 0): ?>
                                    <i class="fas fa-arrow-up text-green-400 mr-1.5 text-xs"></i>
                                    <span class="text-green-400 font-medium"><?php echo abs($changePercent); ?>% from yesterday</span>
                                <?php else: ?>
                                    <i class="fas fa-arrow-down text-red-400 mr-1.5 text-xs"></i>
                                    <span class="text-red-400 font-medium"><?php echo abs($changePercent); ?>% from yesterday</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Yesterday's Comparison -->
                <div class="border border-slate-600/50 bg-slate-700/30 p-4 mb-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-xs text-slate-400 mb-1">Yesterday</div>
                            <div class="text-xl font-semibold text-white"><?php echo $yesterdayCount; ?></div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-slate-400 mb-1">This Week Total</div>
                            <div class="text-xl font-semibold text-white"><?php echo array_sum(array_column($last7Days, 'count')); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Mini Bar Chart -->
                <div class="space-y-2 mb-6">
                    <?php 
                    foreach ($last7Days as $day): 
                        $percentage = $maxCount > 0 ? ($day['count'] / $maxCount) * 100 : 0;
                    ?>
                    <div>
                        <div class="flex justify-between text-xs text-slate-400 mb-1">
                            <span><?php echo date('D', strtotime($day['date'])); ?></span>
                            <span class="font-medium"><?php echo $day['count']; ?></span>
                        </div>
                        <div class="w-full bg-slate-700 h-1.5 rounded-full">
                            <div class="bg-cyan-500 h-1.5 transition-all rounded-full" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Weekly Summary -->
                <div class="border border-slate-600/50 bg-slate-700/30 p-4 rounded-lg">
                    <div class="flex justify-between items-center mb-3">
                        <div class="text-sm font-medium text-white">Average Daily Tickets</div>
                        <div class="text-xl font-semibold text-white"><?php echo !empty($last7Days) ? round(array_sum(array_column($last7Days, 'count')) / count($last7Days), 1) : 0; ?></div>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center text-slate-400">
                            <span>Last 7 days trend</span>
                        </div>
                        <div class="flex items-center">
                            <?php 
                            $weekTrend = $changePercent >= 0 ? 'up' : 'down';
                            $trendColor = $changePercent >= 0 ? 'text-green-400' : 'text-red-400';
                            ?>
                            <i class="fas fa-arrow-<?php echo $weekTrend; ?> mr-1 text-xs <?php echo $trendColor; ?>"></i>
                            <span class="<?php echo $trendColor; ?> font-semibold"><?php echo abs($changePercent); ?>%</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daily Chart - Line Chart -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-white mb-1">Ticket Trends (Line)</h3>
                <p class="text-sm text-slate-400 mb-3">Last 10 days ticket trends</p>
                <div class="h-96 mb-2">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>

            <!-- Daily Chart - Bar Chart -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-white mb-1">Daily Ticket Volume</h3>
                <p class="text-sm text-slate-400 mb-3">Last 10 days ticket trends</p>
                <div class="h-96 mb-2">
                    <canvas id="barChart"></canvas>
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
                    ?>
                    <div>
                        <div class="flex justify-between text-xs text-slate-400 mb-1">
                            <span class="truncate max-w-[150px]"><?php echo htmlspecialchars($category['name']); ?></span>
                            <span class="ml-2 font-medium"><?php echo $category['ticket_count']; ?></span>
                        </div>
                        <div class="w-full bg-slate-700 h-1.5 rounded-full">
                            <div class="bg-cyan-500 h-1.5 transition-all rounded-full" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Volume Summary -->
                <div class="border border-slate-600/50 bg-slate-700/30 p-4 rounded-lg">
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center">
                            <div class="text-slate-400 text-xs mb-1">Peak Day</div>
                            <div class="text-lg font-semibold text-white"><?php echo !empty($dailyStats) ? max(array_column($dailyStats, 'count')) : 0; ?></div>
                        </div>
                        <div class="text-center border-l border-r border-slate-600/50">
                            <div class="text-slate-400 text-xs mb-1">Average</div>
                            <div class="text-lg font-semibold text-white"><?php echo !empty($dailyStats) ? round(array_sum(array_column($dailyStats, 'count')) / count($dailyStats), 1) : 0; ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-slate-400 text-xs mb-1">Total (10d)</div>
                            <div class="text-lg font-semibold text-white"><?php echo !empty($dailyStats) ? array_sum(array_column($dailyStats, 'count')) : 0; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="lg:col-span-1 bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-white mb-1">Status Distribution</h3>
                <p class="text-sm text-slate-400 mb-8">Tickets breakdown by status</p>
                <div class="space-y-4">
                    <?php
                    $statusData = [
                        ['label' => 'Pending', 'count' => $stats['pending']],
                        ['label' => 'Open', 'count' => $stats['open']],
                        ['label' => 'In Progress', 'count' => $stats['in_progress']],
                        ['label' => 'Closed', 'count' => $stats['closed']]
                    ];
                    
                    foreach ($statusData as $data):
                        $percentage = $stats['total'] > 0 ? round(($data['count'] / $stats['total']) * 100) : 0;
                    ?>
                    <div class="border border-slate-600/50 bg-slate-700/30 p-4 hover:border-cyan-500/50 transition-colors rounded-lg">
                        <div class="flex justify-between items-center mb-3">
                            <span class="flex items-center">
                                <div>
                                    <span class="text-sm font-medium text-white"><?php echo $data['label']; ?></span>
                                    <span class="ml-2 text-slate-400 text-xs">(<?php echo $data['count']; ?>)</span>
                                </div>
                            </span>
                            <span class="font-semibold text-sm text-white"><?php echo $percentage; ?>%</span>
                        </div>
                        <div class="w-full bg-slate-700 h-3 rounded-full">
                            <div class="bg-cyan-500 h-3 transition-all rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-8 border border-slate-600/50 bg-slate-700/30 p-4 rounded-lg">
                    <div class="flex justify-between items-center mb-3">
                        <div class="text-sm font-medium text-white">Total Active Tickets</div>
                        <div class="text-2xl font-semibold text-white"><?php echo $stats['open'] + $stats['in_progress']; ?></div>
                    </div>
                    <div class="flex items-center text-xs text-slate-400 bg-slate-700/50 px-3 py-2 rounded">
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

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Recent Tickets -->
            <div class="lg:col-span-2 bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 rounded-lg">
                <div class="px-6 py-4 border-b border-slate-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-white">Recent Tickets</h3>
                            <p class="text-sm text-slate-400 mt-0.5">Latest ticket submissions</p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="text-xs text-slate-400 px-2 py-1 border border-slate-600/50 rounded bg-slate-700/30">
                                <?php echo count($recentTickets); ?> tickets
                            </span>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table id="dashboardTable" class="w-full">
                            <thead>
                                <tr class="text-left text-slate-400 text-xs uppercase tracking-wide border-b border-slate-700/50">
                                    <th class="pb-3 cursor-pointer hover:text-cyan-400 select-none transition-colors" onclick="sortTable(0)">
                                        <div class="flex items-center">
                                            <span>Title</span>
                                            <i class="fas fa-sort text-xs ml-1.5 opacity-50"></i>
                                        </div>
                                    </th>
                                    <th class="pb-3 cursor-pointer hover:text-cyan-400 select-none transition-colors" onclick="sortTable(1)">
                                        <div class="flex items-center">
                                            <span>Status</span>
                                            <i class="fas fa-sort text-xs ml-2 opacity-50 group-hover:opacity-100"></i>
                                        </div>
                                    </th>
                                    <th class="pb-4 cursor-pointer hover:text-cyan-400 select-none transition-colors group" onclick="sortTable(2)">
                                        <div class="flex items-center">
                                            <i class="fas fa-flag mr-2 text-cyan-500"></i>
                                            <span>Priority</span>
                                            <i class="fas fa-sort text-xs ml-2 opacity-50 group-hover:opacity-100"></i>
                                        </div>
                                    </th>
                                    <th class="pb-4 cursor-pointer hover:text-cyan-400 select-none transition-colors group" onclick="sortTable(3)">
                                        <div class="flex items-center">
                                            <i class="fas fa-calendar mr-2 text-cyan-500"></i>
                                            <span>Date</span>
                                            <i class="fas fa-sort text-xs ml-2 opacity-50 group-hover:opacity-100"></i>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="text-sm" id="dashboardTableBody">
                                <?php foreach ($recentTickets as $ticket): 
                                    $statusColors = [
                                        'pending' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
                                        'open' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
                                        'in_progress' => 'bg-purple-500/20 text-purple-400 border-purple-500/30',
                                        'closed' => 'bg-green-500/20 text-green-400 border-green-500/30'
                                    ];
                                    $statusIcons = [
                                        'pending' => 'fa-clock',
                                        'open' => 'fa-folder-open',
                                        'in_progress' => 'fa-spinner fa-spin',
                                        'closed' => 'fa-check-circle'
                                    ];
                                    $priorityColors = [
                                        'urgent' => 'text-red-400 bg-red-500/20 px-2 py-1 rounded-lg font-bold',
                                        'high' => 'text-orange-400 bg-orange-500/20 px-2 py-1 rounded-lg font-bold',
                                        'medium' => 'text-cyan-400 bg-cyan-500/20 px-2 py-1 rounded-lg font-semibold',
                                        'low' => 'text-green-400 bg-green-500/20 px-2 py-1 rounded-lg font-medium'
                                    ];
                                    $priorityIcons = [
                                        'urgent' => 'fa-exclamation-triangle',
                                        'high' => 'fa-arrow-up',
                                        'medium' => 'fa-minus',
                                        'low' => 'fa-arrow-down'
                                    ];
                                ?>
                                <tr class="border-b border-slate-700/30 hover:bg-slate-700/30 transition-all duration-200 cursor-pointer group searchable-row" 
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
                                            <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white font-bold text-sm mr-3 group-hover:scale-110 transition-transform">
                                                #<?php echo $ticket['id']; ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="font-semibold text-white group-hover:text-cyan-400 transition-colors truncate">
                                                    <?php echo htmlspecialchars($ticket['title']); ?>
                                                </div>
                                                <div class="flex items-center text-slate-400 text-xs mt-1">
                                                    <i class="fas fa-folder text-cyan-400 mr-1"></i>
                                                    <?php echo htmlspecialchars($ticket['category_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 pr-4">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold border <?php echo $statusColors[$ticket['status']] ?? 'bg-slate-700/50 text-slate-300 border-slate-600/50'; ?>">
                                            <i class="fas <?php echo $statusIcons[$ticket['status']] ?? 'fa-circle'; ?> mr-1.5"></i>
                                            <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 pr-4">
                                        <span class="inline-flex items-center <?php echo $priorityColors[$ticket['priority']] ?? 'text-slate-400 bg-slate-700/30 px-2 py-1 rounded-lg'; ?>">
                                            <i class="fas <?php echo $priorityIcons[$ticket['priority']] ?? 'fa-circle'; ?> mr-1.5 text-xs"></i>
                                            <?php echo ucfirst($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-slate-400">
                                        <div class="flex items-center">
                                            <i class="far fa-calendar-alt mr-2 text-cyan-400"></i>
                                            <span class="font-medium"><?php echo date('M d, Y', strtotime($ticket['created_at'])); ?></span>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-1">
                                            <?php echo date('h:i A', strtotime($ticket['created_at'])); ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- View All Button -->
                    <div class="mt-6 pt-4 border-t border-slate-700/50 text-center">
                        <a href="tickets.php" class="inline-flex items-center px-6 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold rounded-lg hover:from-cyan-600 hover:to-blue-700 transition-all duration-200 shadow-md hover:shadow-lg">
                            <i class="fas fa-list mr-2"></i>
                            View All Tickets
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Last Updates -->
            <div class="lg:col-span-1 bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 rounded-lg">
                <div class="px-6 py-4 border-b border-slate-700/50">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-white">Activity Feed</h3>
                            <p class="text-sm text-slate-400 mt-0.5">Latest system updates</p>
                        </div>
                        <select class="text-xs border border-slate-600 bg-slate-700/50 text-white px-3 py-1.5 focus:outline-none focus:border-cyan-500 rounded">
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month">This Month</option>
                        </select>
                    </div>
                </div>
                <div class="p-6">
                    <div class="space-y-2">
                        <!-- New Employee -->
                        <div class="p-3 hover:bg-slate-700/30 transition-colors border border-slate-600/50 cursor-pointer rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-slate-700/50 flex items-center justify-center rounded">
                                        <i class="fas fa-user-plus text-cyan-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-white">New Employees</div>
                                        <div class="text-xs text-slate-400">Total registered</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-xl font-semibold text-white"><?php echo $employeeStats['total']; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- New Messages -->
                        <div class="p-3 hover:bg-slate-700/30 transition-colors border border-slate-600/50 cursor-pointer rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-slate-700/50 flex items-center justify-center rounded">
                                        <i class="fas fa-envelope text-cyan-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-white">New Messages</div>
                                        <div class="text-xs text-slate-400">Recent activities</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-xl font-semibold text-white"><?php echo count($recentActivity); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Resources -->
                        <div class="p-3 hover:bg-slate-700/30 transition-colors border border-slate-600/50 cursor-pointer rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-slate-700/50 flex items-center justify-center rounded">
                                        <i class="fas fa-database text-cyan-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-white">Categories</div>
                                        <div class="text-xs text-slate-400">Available resources</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-xl font-semibold text-white"><?php echo count($categoryStats); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Active Tickets -->
                        <div class="p-3 hover:bg-slate-700/30 transition-colors border border-slate-600/50 cursor-pointer rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-slate-700/50 flex items-center justify-center rounded">
                                        <i class="fas fa-ticket-alt text-cyan-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-white">Active Tickets</div>
                                        <div class="text-xs text-slate-400">Pending & Open</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-xl font-semibold text-white"><?php echo $stats['pending'] + $stats['open']; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- New Articles -->
                        <div class="p-3 hover:bg-slate-700/30 transition-colors border border-slate-600/50 cursor-pointer rounded-lg">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-slate-700/50 flex items-center justify-center rounded">
                                        <i class="fas fa-newspaper text-cyan-400 text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="font-medium text-sm text-white">Knowledge Base</div>
                                        <div class="text-xs text-slate-400">New articles</div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <span class="text-xl font-semibold text-white">5</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="mt-6 pt-4 border-t border-slate-700/50">
                        <a href="#" class="flex items-center justify-center px-4 py-2 text-sm font-medium text-slate-300 border border-slate-600/50 hover:border-cyan-500/50 hover:text-cyan-400 transition rounded">
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
    
    // Initialize dashboard charts
    document.addEventListener('DOMContentLoaded', function() {
        const chartLabels = <?php echo json_encode($chartData['labels']); ?>;
        const chartValues = <?php echo json_encode($chartData['data']); ?>;
        
        console.log('Chart Data Available:', chartLabels, chartValues);
        
        // LINE CHART
        initLineChart(chartLabels, chartValues);
        
        // BAR CHART
        initBarChart(chartLabels, chartValues);
    });
    
    // Line Chart Initialization
    function initLineChart(labels, data) {
        const lineCtx = document.getElementById('lineChart');
        if (!lineCtx || typeof Chart === 'undefined') {
            console.warn('Line chart element or Chart.js not available');
            return;
        }
        
        try {
            const ctx = lineCtx.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(6, 182, 212, 0.4)');
            gradient.addColorStop(0.5, 'rgba(6, 182, 212, 0.2)');
            gradient.addColorStop(1, 'rgba(6, 182, 212, 0.01)');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Tickets',
                        data: data,
                        borderColor: '#06b6d4',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointBackgroundColor: '#06b6d4',
                        pointBorderColor: '#0f172a',
                        pointBorderWidth: 2,
                        pointHoverBackgroundColor: '#0891b2',
                        pointHoverBorderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 800,
                        easing: 'easeInOutQuart'
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            borderColor: 'rgba(6, 182, 212, 0.5)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 12, weight: '500' },
                                padding: 8
                            },
                            grid: { color: '#334155', drawBorder: false },
                            border: { display: false }
                        },
                        x: {
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 11, weight: '500' },
                                padding: 8
                            },
                            grid: { display: false },
                            border: { display: false }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Line chart error:', error);
        }
    }
    
    // Bar Chart Initialization
    function initBarChart(labels, data) {
        const barCtx = document.getElementById('barChart');
        if (!barCtx || typeof Chart === 'undefined') {
            console.warn('Bar chart element or Chart.js not available');
            return;
        }
        
        try {
            const ctx = barCtx.getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Tickets',
                        data: data,
                        backgroundColor: 'rgba(226, 232, 240, 0.8)',
                        borderColor: 'rgba(226, 232, 240, 1)',
                        borderWidth: 0,
                        borderRadius: 4,
                        borderSkipped: false,
                        barThickness: 'flex',
                        maxBarThickness: 40,
                        hoverBackgroundColor: 'rgba(226, 232, 240, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 800,
                        easing: 'easeInOutQuart'
                    },
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            enabled: true,
                            backgroundColor: 'rgba(15, 23, 42, 0.95)',
                            titleColor: '#f1f5f9',
                            bodyColor: '#cbd5e1',
                            borderColor: 'rgba(226, 232, 240, 0.3)',
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 12, weight: '500' },
                                padding: 8
                            },
                            grid: { color: '#334155', drawBorder: false },
                            border: { display: false }
                        },
                        x: {
                            ticks: {
                                color: '#94a3b8',
                                font: { size: 11, weight: '500' },
                                padding: 8
                            },
                            grid: { display: false },
                            border: { display: false }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Bar chart error:', error);
        }
    }
</script>

<?php 
// Include layout footer
include __DIR__ . '/../layouts/footer.php'; 
?>

