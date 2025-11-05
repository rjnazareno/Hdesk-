<?php 
// Set page-specific variables
$pageTitle = 'My Dashboard - IT Help Desk';
$baseUrl = '../';

// Include header layout
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Minimal Top Bar -->
    <div class="bg-slate-800/50 border-b border-slate-700/50 backdrop-blur-md">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <!-- Left Section: Personal Greeting -->
            <div class="flex items-center space-x-4">
                <div class="hidden lg:flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-cyan-500 to-blue-600">
                    <i class="fas fa-user text-white text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-white">
                        Welcome back, <?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?>
                    </h1>
                    <div class="flex items-center space-x-3 mt-0.5">
                        <p class="text-sm text-slate-400">Your personal performance dashboard</p>
                        <span class="hidden md:inline-flex items-center px-2 py-0.5 text-xs font-medium text-cyan-400 border border-cyan-500/30 rounded bg-cyan-500/10">
                            IT Staff
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right Section: User Menu -->
            <div class="flex items-center space-x-2">
                <!-- Notifications -->
                <button class="relative p-2 text-slate-400 hover:text-cyan-400 transition">
                    <i class="far fa-bell text-sm"></i>
                    <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                </button>

                <!-- User Avatar -->
                <div class="relative" id="userMenuDropdown">
                    <button class="flex items-center space-x-2 p-1 hover:bg-slate-700/30 rounded transition" id="userMenuBtn">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=fff" 
                             alt="User" 
                             class="w-8 h-8 rounded-full">
                        <div class="hidden lg:block text-left">
                            <div class="text-sm font-medium text-white"><?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?></div>
                            <div class="text-xs text-slate-400">IT Staff</div>
                        </div>
                        <i class="fas fa-chevron-down text-xs text-slate-400 hidden lg:block"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-56 bg-slate-800 rounded border border-slate-700/50 shadow-lg hidden z-50" id="userMenu">
                        <div class="p-3 border-b border-slate-700/50">
                            <div class="font-medium text-sm text-white"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                            <div class="text-xs text-slate-400"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>
                        <div class="py-1">
                            <a href="profile.php" class="flex items-center px-3 py-2 text-sm text-slate-300 hover:bg-slate-700/30 transition">
                                <i class="fas fa-user w-4 text-xs"></i>
                                <span class="ml-2">My Profile</span>
                            </a>
                            <a href="tickets.php" class="flex items-center px-3 py-2 text-sm text-slate-300 hover:bg-slate-700/30 transition">
                                <i class="fas fa-ticket-alt w-4 text-xs"></i>
                                <span class="ml-2">All Tickets</span>
                            </a>
                            <div class="border-t border-slate-700/50 my-1"></div>
                            <a href="../logout.php" class="flex items-center px-3 py-2 text-sm text-red-400 hover:bg-red-500/10 transition">
                                <i class="fas fa-sign-out-alt w-4 text-xs"></i>
                                <span class="ml-2">Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="p-8">
        <!-- Performance Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <!-- Total Assigned -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-5 hover:border-cyan-500/50 transition-colors rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs text-slate-400 uppercase tracking-wide mb-2">Assigned to Me</p>
                        <h3 class="text-2xl font-semibold text-white"><?php echo $myStats['total_assigned'] ?? 0; ?></h3>
                    </div>
                    <div class="w-10 h-10 flex items-center justify-center bg-slate-700/50 rounded">
                        <i class="fas fa-tasks text-cyan-400"></i>
                    </div>
                </div>
            </div>

            <!-- Open Tickets -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-5 hover:border-cyan-500/50 transition-colors rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs text-slate-400 uppercase tracking-wide mb-2">Open Tickets</p>
                        <h3 class="text-2xl font-semibold text-white"><?php echo $myStats['open_tickets'] ?? 0; ?></h3>
                    </div>
                    <div class="w-10 h-10 flex items-center justify-center bg-slate-700/50 rounded">
                        <i class="fas fa-folder-open text-cyan-400"></i>
                    </div>
                </div>
            </div>

            <!-- In Progress -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-5 hover:border-cyan-500/50 transition-colors rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs text-slate-400 uppercase tracking-wide mb-2">In Progress</p>
                        <h3 class="text-2xl font-semibold text-white"><?php echo $myStats['in_progress'] ?? 0; ?></h3>
                    </div>
                    <div class="w-10 h-10 flex items-center justify-center bg-slate-700/50 rounded">
                        <i class="fas fa-spinner text-cyan-500"></i>
                    </div>
                </div>
            </div>

            <!-- Urgent Pending -->
            <div class="bg-gradient-to-br from-red-500/10 to-red-500/5 border border-red-500/30 p-5 hover:border-red-500/50 transition-colors rounded-lg">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs text-red-400 uppercase tracking-wide mb-2">Urgent Pending</p>
                        <h3 class="text-2xl font-semibold text-red-400"><?php echo $myStats['urgent_pending'] ?? 0; ?></h3>
                    </div>
                    <div class="w-10 h-10 flex items-center justify-center bg-red-500/20 rounded">
                        <i class="fas fa-exclamation-triangle text-red-400"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            <!-- Response Time -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-6 rounded-lg">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 flex items-center justify-center bg-slate-700/50 rounded">
                        <i class="fas fa-clock text-cyan-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Avg Response Time</h3>
                        <p class="text-xs text-slate-400">First response speed</p>
                    </div>
                </div>
                <div class="text-center py-4">
                    <h2 class="text-4xl font-semibold text-white">
                        <?php echo round($myPerformance['avg_response_time'] ?? 0, 1); ?>
                    </h2>
                    <p class="text-sm text-slate-400 mt-1">hours</p>
                </div>
            </div>

            <!-- Resolution Time -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-6 rounded-lg">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 flex items-center justify-center bg-slate-700/50 rounded">
                        <i class="fas fa-check-circle text-cyan-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Avg Resolution Time</h3>
                        <p class="text-xs text-slate-400">Time to resolve</p>
                    </div>
                </div>
                <div class="text-center py-4">
                    <h2 class="text-4xl font-semibold text-white">
                        <?php echo round($myPerformance['avg_resolution_time'] ?? 0, 1); ?>
                    </h2>
                    <p class="text-sm text-slate-400 mt-1">hours</p>
                </div>
            </div>

            <!-- Resolved Tickets -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-6 rounded-lg">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 flex items-center justify-center bg-slate-700/50 rounded">
                        <i class="fas fa-trophy text-cyan-400"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Tickets Resolved</h3>
                        <p class="text-xs text-slate-400">Your achievements</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="border border-slate-600/50 bg-slate-700/30 p-3 rounded-lg">
                        <p class="text-xl font-semibold text-white"><?php echo $myPerformance['resolved_today'] ?? 0; ?></p>
                        <p class="text-xs text-slate-400 mt-1">Today</p>
                    </div>
                    <div class="border border-slate-600/50 bg-slate-700/30 p-3 rounded-lg">
                        <p class="text-xl font-semibold text-white"><?php echo $myPerformance['resolved_this_week'] ?? 0; ?></p>
                        <p class="text-xs text-slate-400 mt-1">Week</p>
                    </div>
                    <div class="border border-slate-600/50 bg-slate-700/30 p-3 rounded-lg">
                        <p class="text-xl font-semibold text-white"><?php echo $myPerformance['resolved_this_month'] ?? 0; ?></p>
                        <p class="text-xs text-slate-400 mt-1">Month</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- SLA Widgets Row -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <!-- SLA Compliance Card -->
            <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 p-6 rounded-lg">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 flex items-center justify-center bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded">
                        <i class="fas fa-chart-line text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">My SLA Compliance</h3>
                        <p class="text-xs text-slate-400">Performance metrics</p>
                    </div>
                </div>
                
                <?php if ($mySLACompliance['total_tickets'] > 0): ?>
                <div class="space-y-4">
                    <!-- Response SLA -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-slate-300">Response SLA</span>
                            <span class="text-sm font-semibold <?php echo $mySLACompliance['response_compliance_rate'] >= 85 ? 'text-green-400' : 'text-red-400'; ?>">
                                <?php echo $mySLACompliance['response_compliance_rate']; ?>%
                            </span>
                        </div>
                        <div class="w-full bg-slate-700 h-2 rounded-full">
                            <div class="<?php echo $mySLACompliance['response_compliance_rate'] >= 85 ? 'bg-green-500' : 'bg-red-500'; ?> h-2 transition-all rounded-full" 
                                 style="width: <?php echo $mySLACompliance['response_compliance_rate']; ?>%"></div>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            <?php echo $mySLACompliance['response_met']; ?> met of <?php echo $mySLACompliance['total_tickets']; ?> tickets
                        </p>
                    </div>
                    
                    <!-- Resolution SLA -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-medium text-slate-300">Resolution SLA</span>
                            <span class="text-sm font-semibold <?php echo $mySLACompliance['resolution_compliance_rate'] >= 85 ? 'text-green-400' : 'text-red-400'; ?>">
                                <?php echo $mySLACompliance['resolution_compliance_rate']; ?>%
                            </span>
                        </div>
                        <div class="w-full bg-slate-700 h-2 rounded-full">
                            <div class="<?php echo $mySLACompliance['resolution_compliance_rate'] >= 85 ? 'bg-green-500' : 'bg-red-500'; ?> h-2 transition-all rounded-full" 
                                 style="width: <?php echo $mySLACompliance['resolution_compliance_rate']; ?>%"></div>
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            <?php echo $mySLACompliance['resolution_met']; ?> met of <?php echo $mySLACompliance['total_tickets']; ?> tickets
                        </p>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-chart-line text-3xl text-slate-600 mb-2"></i>
                    <p class="text-sm text-slate-400">No resolved tickets yet</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- At-Risk Tickets -->
            <div class="bg-gradient-to-br from-yellow-500/10 to-yellow-500/5 border border-yellow-500/30 p-6 rounded-lg">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 flex items-center justify-center bg-yellow-600 text-white rounded">
                        <i class="fas fa-exclamation-circle text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">At-Risk Tickets</h3>
                        <p class="text-xs text-yellow-400">&lt;1 hour remaining</p>
                    </div>
                </div>

                <?php if (!empty($atRiskTickets)): ?>
                <div class="space-y-3">
                    <?php foreach ($atRiskTickets as $ticket): 
                        $hours = floor($ticket['minutes_remaining'] / 60);
                        $mins = $ticket['minutes_remaining'] % 60;
                    ?>
                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" 
                       class="block p-3 bg-yellow-500/10 border border-yellow-500/30 hover:border-yellow-500/50 transition rounded-lg">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-yellow-300">
                                <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                            </span>
                            <span class="text-xs font-semibold text-yellow-400">
                                <?php if ($hours > 0) echo $hours . 'h '; echo $mins; ?>m left
                            </span>
                        </div>
                        <p class="text-sm text-white truncate">
                            <?php echo htmlspecialchars($ticket['title']); ?>
                        </p>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-check-circle text-3xl text-green-500 mb-2"></i>
                    <p class="text-sm text-slate-300">No tickets at risk</p>
                    <p class="text-xs text-slate-400 mt-1">Great job!</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Breached Tickets -->
            <div class="bg-gradient-to-br from-red-500/10 to-red-500/5 border border-red-500/30 p-6 rounded-lg">
                <div class="flex items-center space-x-3 mb-4">
                    <div class="w-10 h-10 flex items-center justify-center bg-red-600 text-white rounded">
                        <i class="fas fa-exclamation-triangle text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white">Breached Tickets</h3>
                        <p class="text-xs text-red-400">Overdue SLA</p>
                    </div>
                </div>

                <?php if (!empty($breachedTickets)): ?>
                <div class="space-y-3">
                    <?php foreach ($breachedTickets as $ticket): 
                        $hours = floor($ticket['minutes_overdue'] / 60);
                        $mins = $ticket['minutes_overdue'] % 60;
                    ?>
                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" 
                       class="block p-3 bg-red-500/10 border border-red-500/30 hover:border-red-500/50 transition rounded-lg">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-red-300">
                                <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                            </span>
                            <span class="text-xs font-semibold text-red-400">
                                <?php if ($hours > 0) echo $hours . 'h '; echo $mins; ?>m ago
                            </span>
                        </div>
                        <p class="text-sm text-white truncate">
                            <?php echo htmlspecialchars($ticket['title']); ?>
                        </p>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-check-circle text-3xl text-green-500 mb-2"></i>
                    <p class="text-sm text-slate-300">No breached tickets</p>
                    <p class="text-xs text-slate-400 mt-1">Excellent work!</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Assigned Tickets -->
        <div class="bg-gradient-to-br from-slate-800 to-slate-800/50 border border-slate-700/50 mb-8 rounded-lg">
            <div class="px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-white">My Assigned Tickets</h3>
                    <p class="text-sm text-slate-400 mt-0.5">
                        <?php echo count($myTickets); ?> ticket<?php echo count($myTickets) !== 1 ? 's' : ''; ?> assigned to you
                    </p>
                </div>
                <a href="tickets.php" class="hidden md:flex items-center space-x-1 px-3 py-1.5 text-sm text-slate-300 border border-slate-600/50 hover:border-cyan-500/50 hover:text-cyan-400 transition rounded">
                    <span>View All</span>
                    <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-700/30 border-b border-slate-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wide">
                                Ticket
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wide">
                                Category
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wide">
                                Priority
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wide">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wide">
                                Created
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wide">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-gradient-to-br from-slate-800 to-slate-800/50 divide-y divide-slate-700/50">
                        <?php if (empty($myTickets)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="w-12 h-12 flex items-center justify-center bg-slate-700/50 rounded">
                                        <i class="fas fa-clipboard-check text-2xl text-slate-600"></i>
                                    </div>
                                    <p class="text-slate-300 font-medium">No tickets assigned</p>
                                    <p class="text-sm text-slate-400">You're all caught up!</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($myTickets as $ticket): ?>
                            <tr class="hover:bg-slate-700/30 transition-colors cursor-pointer border-b border-slate-700/50" onclick="window.location.href='view_ticket.php?id=<?php echo $ticket['id']; ?>'">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-mono font-medium text-white bg-slate-700/50 rounded">
                                            <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-white truncate">
                                                <?php echo htmlspecialchars($ticket['title']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium border" style="color: <?php echo $ticket['category_color']; ?>; border-color: <?php echo $ticket['category_color']; ?>;">
                                        <?php echo htmlspecialchars($ticket['category_name']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $priorityConfig = [
                                        'low' => ['color' => 'text-green-400', 'bg' => 'bg-green-500/20', 'border' => 'border-green-500/30'],
                                        'medium' => ['color' => 'text-yellow-400', 'bg' => 'bg-yellow-500/20', 'border' => 'border-yellow-500/30'],
                                        'high' => ['color' => 'text-orange-400', 'bg' => 'bg-orange-500/20', 'border' => 'border-orange-500/30'],
                                        'urgent' => ['color' => 'text-red-400', 'bg' => 'bg-red-500/20', 'border' => 'border-red-500/30']
                                    ];
                                    $config = $priorityConfig[$ticket['priority']];
                                    ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium border <?php echo $config['color'] . ' ' . $config['bg'] . ' ' . $config['border']; ?>">
                                        <?php echo ucfirst($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php
                                    $statusConfig = [
                                        'pending' => ['color' => 'text-yellow-400', 'bg' => 'bg-yellow-500/20', 'border' => 'border-yellow-500/30'],
                                        'open' => ['color' => 'text-blue-400', 'bg' => 'bg-blue-500/20', 'border' => 'border-blue-500/30'],
                                        'in_progress' => ['color' => 'text-purple-400', 'bg' => 'bg-purple-500/20', 'border' => 'border-purple-500/30'],
                                        'resolved' => ['color' => 'text-green-400', 'bg' => 'bg-green-500/20', 'border' => 'border-green-500/30'],
                                        'closed' => ['color' => 'text-slate-400', 'bg' => 'bg-slate-500/20', 'border' => 'border-slate-500/30']
                                    ];
                                    $config = $statusConfig[$ticket['status']];
                                    ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium border <?php echo $config['color'] . ' ' . $config['bg'] . ' ' . $config['border']; ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($ticket['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-400">
                                    <div class="flex flex-col">
                                        <span class="font-medium text-white">
                                            <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                        </span>
                                        <span class="text-xs text-slate-500">
                                            <?php echo date('h:i A', strtotime($ticket['created_at'])); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-slate-300 border border-slate-600/50 hover:border-cyan-500/50 hover:text-cyan-400 transition rounded" 
                                       onclick="event.stopPropagation()">
                                        <span>View</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // User Menu Dropdown
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenu');
    
    if (userMenuBtn && userMenu) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('hidden');
        });
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        // Check if click is outside user menu dropdown
        const userMenuDropdown = document.getElementById('userMenuDropdown');
        if (userMenu && userMenuDropdown && !userMenuDropdown.contains(e.target)) {
            userMenu.classList.add('hidden');
        }
    });
});
</script>

<?php 
// Include footer layout
include __DIR__ . '/../layouts/footer.php'; 
?>

