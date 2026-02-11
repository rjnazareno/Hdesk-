<?php
$pageTitle = 'SLA Performance - ' . APP_NAME;
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php';

// Helper function for score color
function getScoreColor($score) {
    if ($score >= 90) return 'green';
    if ($score >= 70) return 'yellow';
    if ($score >= 50) return 'orange';
    return 'red';
}
?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <?php
        $headerTitle = 'SLA Performance';
        $headerSubtitle = 'Track your SLA metrics and generate team reports';
        $showQuickActions = true;
        $showSearch = false;
        include __DIR__ . '/../../includes/top_header.php';
        ?>

        <!-- Content -->
        <div class="p-8">

            <!-- Tab Navigation -->
            <div class="flex border-b border-gray-200 mb-8">
                <a href="sla_performance.php?tab=my_performance" 
                   class="px-6 py-3 text-sm font-medium border-b-2 -mb-px transition <?php echo $activeTab === 'my_performance' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    <i class="fas fa-user mr-2"></i>My SLA Performance
                </a>
                <a href="sla_performance.php?tab=report" 
                   class="px-6 py-3 text-sm font-medium border-b-2 -mb-px transition <?php echo $activeTab === 'report' ? 'border-gray-900 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?>">
                    <i class="fas fa-chart-bar mr-2"></i>Generate SLA Report
                </a>
            </div>

            <?php if ($activeTab === 'my_performance'): ?>
            <!-- ==================== TAB 1: MY SLA PERFORMANCE ==================== -->
            
            <!-- My Score Summary -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- SLA Score -->
                <div class="bg-white border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">My SLA Score</p>
                            <p class="text-3xl font-bold text-<?php echo getScoreColor($myPerformance['sla_score']); ?>-500">
                                <?php echo $myPerformance['sla_score']; ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">out of 100</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-900 flex items-center justify-center">
                            <i class="fas fa-trophy text-white text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Tickets -->
                <div class="bg-white border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">My Tickets</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $myPerformance['total_tickets']; ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $myPerformance['resolved_tickets']; ?> resolved, <?php echo $myPerformance['open_tickets']; ?> open</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-ticket-alt text-gray-700 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Response SLA -->
                <div class="bg-white border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Response SLA</p>
                            <p class="text-3xl font-bold text-<?php echo $myPerformance['response_percentage'] >= 90 ? 'green' : ($myPerformance['response_percentage'] >= 70 ? 'yellow' : 'red'); ?>-500">
                                <?php echo $myPerformance['response_percentage']; ?>%
                            </p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $myPerformance['response_met']; ?> met / <?php echo $myPerformance['response_breached']; ?> breached</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-bolt text-gray-700 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Resolution SLA -->
                <div class="bg-white border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Resolution SLA</p>
                            <p class="text-3xl font-bold text-<?php echo $myPerformance['resolution_percentage'] >= 90 ? 'green' : ($myPerformance['resolution_percentage'] >= 70 ? 'yellow' : 'red'); ?>-500">
                                <?php echo $myPerformance['resolution_percentage']; ?>%
                            </p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $myPerformance['resolution_met']; ?> met / <?php echo $myPerformance['resolution_breached']; ?> breached</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-check-circle text-gray-700 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Times & Priority Breakdown -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Average Times -->
                <div class="bg-white border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">
                        <i class="fas fa-clock mr-2 text-gray-500"></i>Average Times
                    </h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Avg Response Time</span>
                            <span class="text-sm font-semibold text-gray-900"><?php echo $myPerformance['avg_response_formatted']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Avg Resolution Time</span>
                            <span class="text-sm font-semibold text-gray-900"><?php echo $myPerformance['avg_resolution_formatted']; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Priority Breakdown -->
                <div class="bg-white border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">
                        <i class="fas fa-layer-group mr-2 text-gray-500"></i>Tickets by Priority
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center">
                            <span class="inline-flex items-center">
                                <span class="w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                                <span class="text-sm text-gray-600">High</span>
                            </span>
                            <span class="text-sm font-semibold text-gray-900"><?php echo $myPerformance['high_tickets']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="inline-flex items-center">
                                <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2"></span>
                                <span class="text-sm text-gray-600">Medium</span>
                            </span>
                            <span class="text-sm font-semibold text-gray-900"><?php echo $myPerformance['medium_tickets']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="inline-flex items-center">
                                <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                                <span class="text-sm text-gray-600">Low</span>
                            </span>
                            <span class="text-sm font-semibold text-gray-900"><?php echo $myPerformance['low_tickets']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Recent Tickets -->
            <div class="bg-white border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-list mr-2 text-gray-500"></i>
                        My Recent Tickets (Last 20)
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Ticket</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Title</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Priority</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Status</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Response</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Resolution</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Response Time</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Resolution Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($myRecentTickets)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2 block text-gray-300"></i>
                                    No tickets assigned to you yet.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($myRecentTickets as $ticket): ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="text-blue-600 hover:underline font-medium text-sm">
                                        <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-900 text-sm"><?php echo htmlspecialchars(substr($ticket['title'], 0, 40)); ?><?php echo strlen($ticket['title']) > 40 ? '...' : ''; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 text-xs font-semibold <?php 
                                        echo match($ticket['priority']) {
                                            'high' => 'bg-red-100 text-red-700',
                                            'medium' => 'bg-yellow-100 text-yellow-700',
                                            'low' => 'bg-green-100 text-green-700',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                    ?>">
                                        <?php echo ucfirst($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-2 py-1 text-xs font-semibold <?php 
                                        echo match($ticket['status']) {
                                            'pending' => 'bg-yellow-600 text-white',
                                            'open' => 'bg-blue-600 text-white',
                                            'in_progress' => 'bg-purple-600 text-white',
                                            'resolved' => 'bg-green-600 text-white',
                                            'closed' => 'bg-gray-600 text-white',
                                            default => 'bg-gray-100 text-gray-600'
                                        };
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($ticket['response_sla_status']): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium <?php echo $ticket['response_sla_status'] === 'met' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <i class="fas fa-<?php echo $ticket['response_sla_status'] === 'met' ? 'check' : 'times'; ?> mr-1"></i>
                                        <?php echo ucfirst($ticket['response_sla_status']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-gray-400 text-xs">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($ticket['resolution_sla_status']): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium <?php echo $ticket['resolution_sla_status'] === 'met' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                                        <i class="fas fa-<?php echo $ticket['resolution_sla_status'] === 'met' ? 'check' : 'times'; ?> mr-1"></i>
                                        <?php echo ucfirst($ticket['resolution_sla_status']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="text-gray-400 text-xs">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-gray-900 text-sm"><?php echo $ticket['response_time_formatted']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-gray-900 text-sm"><?php echo $ticket['resolution_time_formatted']; ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php else: ?>
            <!-- ==================== TAB 2: GENERATE SLA REPORT ==================== -->
            
            <!-- Date Filter -->
            <div class="bg-white border border-gray-200 p-6 mb-8">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">
                    <i class="fas fa-filter mr-2 text-gray-500"></i>Filter by Date Range
                </h3>
                <form method="GET" action="export_sla_report.php" class="flex flex-wrap items-end gap-4">
                    <div>
                        <label class="block text-xs text-gray-500 uppercase mb-1">Start Date</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" 
                               class="px-4 py-2 border border-gray-300 text-sm text-gray-900 focus:outline-none focus:border-gray-900">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 uppercase mb-1">End Date</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" 
                               class="px-4 py-2 border border-gray-300 text-sm text-gray-900 focus:outline-none focus:border-gray-900">
                    </div>
                    <button type="submit" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 transition">
                        <i class="fas fa-file-excel mr-2"></i>Generate Report
                    </button>
                </form>
            </div>

            <?php if ($reportGenerated): ?>
            <!-- Overall Stats for Period -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Total Tickets</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $reportOverallStats['total_tickets']; ?></p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $reportOverallStats['resolved_tickets']; ?> resolved</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-ticket-alt text-gray-700 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Response SLA</p>
                            <p class="text-3xl font-bold text-<?php echo $reportOverallStats['response_percentage'] >= 90 ? 'green' : ($reportOverallStats['response_percentage'] >= 70 ? 'yellow' : 'red'); ?>-500">
                                <?php echo $reportOverallStats['response_percentage']; ?>%
                            </p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $reportOverallStats['response_met']; ?> met / <?php echo $reportOverallStats['response_breached']; ?> breached</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-bolt text-gray-700 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Resolution SLA</p>
                            <p class="text-3xl font-bold text-<?php echo $reportOverallStats['resolution_percentage'] >= 90 ? 'green' : ($reportOverallStats['resolution_percentage'] >= 70 ? 'yellow' : 'red'); ?>-500">
                                <?php echo $reportOverallStats['resolution_percentage']; ?>%
                            </p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $reportOverallStats['resolution_met']; ?> met / <?php echo $reportOverallStats['resolution_breached']; ?> breached</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-check-circle text-gray-700 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm mb-1">Avg Response</p>
                            <p class="text-2xl font-bold text-gray-900"><?php echo $reportOverallStats['avg_response_formatted']; ?></p>
                            <p class="text-xs text-gray-500 mt-1">Avg Resolution: <?php echo $reportOverallStats['avg_resolution_formatted']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-clock text-gray-700 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Staff Performance Table -->
            <div class="bg-white border border-gray-200">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-users mr-2 text-gray-500"></i>
                        Admin Staff SLA Rankings
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Performance for <?php echo date('M d, Y', strtotime($dateFrom)); ?> — <?php echo date('M d, Y', strtotime($dateTo)); ?>
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Rank</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Staff</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">SLA Score</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Tickets</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Response SLA</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Resolution SLA</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Avg Response</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-500 uppercase">Avg Resolution</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($staffReport)): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-search text-3xl mb-2 block text-gray-300"></i>
                                    No tickets found in the selected date range.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php 
                            $rank = 1;
                            foreach ($staffReport as $staff): 
                                $scoreColor = getScoreColor($staff['sla_score']);
                                
                                $rankDisplay = $rank;
                                if ($rank == 1) $rankDisplay = '<i class="fas fa-trophy text-yellow-500"></i> 1st';
                                elseif ($rank == 2) $rankDisplay = '<i class="fas fa-medal text-gray-400"></i> 2nd';
                                elseif ($rank == 3) $rankDisplay = '<i class="fas fa-medal text-amber-600"></i> 3rd';
                            ?>
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <span class="text-gray-900 font-semibold"><?php echo $rankDisplay; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gray-900 flex items-center justify-center text-white font-bold text-sm">
                                            <?php echo strtoupper(substr($staff['full_name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($staff['full_name']); ?></p>
                                            <p class="text-xs">
                                                <?php 
                                                $rights = $staff['admin_rights_hdesk'] ?? '';
                                                if ($rights === 'superadmin') {
                                                    echo '<span class="px-2 py-0.5 bg-red-100 text-red-700 text-xs">Super Admin</span>';
                                                } elseif ($rights === 'it') {
                                                    echo '<span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs">IT Admin</span>';
                                                } elseif ($rights === 'hr') {
                                                    echo '<span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs">HR Admin</span>';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center justify-center w-14 h-14 border-2 border-<?php echo $scoreColor; ?>-500 rounded-full">
                                        <span class="text-xl font-bold text-<?php echo $scoreColor; ?>-500"><?php echo $staff['sla_score']; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-gray-900 font-semibold"><?php echo $staff['total_tickets']; ?></span>
                                    <span class="block text-xs text-gray-500"><?php echo $staff['resolved_tickets']; ?> resolved</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-<?php echo $staff['response_percentage'] >= 90 ? 'green' : ($staff['response_percentage'] >= 70 ? 'yellow' : 'red'); ?>-500 font-semibold">
                                        <?php echo $staff['response_percentage']; ?>%
                                    </span>
                                    <span class="block text-xs text-gray-500"><?php echo $staff['response_met']; ?>/<?php echo $staff['response_met'] + $staff['response_breached']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-<?php echo $staff['resolution_percentage'] >= 90 ? 'green' : ($staff['resolution_percentage'] >= 70 ? 'yellow' : 'red'); ?>-500 font-semibold">
                                        <?php echo $staff['resolution_percentage']; ?>%
                                    </span>
                                    <span class="block text-xs text-gray-500"><?php echo $staff['resolution_met']; ?>/<?php echo $staff['resolution_met'] + $staff['resolution_breached']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-gray-900 text-sm"><?php echo $staff['avg_response_formatted']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-gray-900 text-sm"><?php echo $staff['avg_resolution_formatted']; ?></span>
                                </td>
                            </tr>
                            <?php 
                            $rank++;
                            endforeach; 
                            ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php endif; ?>

            <!-- Score Legend (shown on both tabs) -->
            <div class="mt-8 bg-white border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">
                    <i class="fas fa-info-circle mr-2 text-gray-500"></i>
                    SLA Score Calculation
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="font-semibold text-gray-900 mb-2">Score Components:</p>
                        <ul class="space-y-1 text-gray-600">
                            <li>Response SLA: 0-50 points</li>
                            <li>Resolution SLA: 0-50 points</li>
                            <li><strong class="text-gray-900">Total Score: 0-100</strong></li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 mb-2">Performance Ratings:</p>
                        <ul class="space-y-1 text-gray-600">
                            <li><span class="text-green-500">&#9679;</span> Excellent: 90-100</li>
                            <li><span class="text-yellow-500">&#9679;</span> Good: 70-89</li>
                            <li><span class="text-orange-500">&#9679;</span> Fair: 50-69</li>
                            <li><span class="text-red-500">&#9679;</span> Needs Improvement: 0-49</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
