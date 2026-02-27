<?php 
// Include layout header
$pageTitle = 'Dashboard - ' . APP_NAME;
$includeChartJs = true;
$includeFirebase = true;
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-slate-50">
    <?php
    // Set header variables
    $greeting = '';
    $hour = date('G');
    if ($hour < 12) $greeting = 'Good Morning';
    elseif ($hour < 18) $greeting = 'Good Afternoon';
    else $greeting = 'Good Evening';
    
    $firstName = explode(' ', $currentUser['full_name'])[0];
    $headerTitle = $greeting . ', ' . htmlspecialchars($firstName);
    $headerSubtitle = ucfirst(str_replace('_', ' ', $currentUser['role'])) . ' · ' . date('l, F j, Y');
    $showQuickActions = true;
    
    include __DIR__ . '/../../includes/top_header.php';
    ?>

    <!-- Dashboard Content -->
    <div class="p-6 lg:p-8 max-w-7xl mx-auto">
        
        <!-- Stats Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">

            <!-- All Tickets -->
            <a href="tickets.php" class="block bg-white rounded-xl border border-slate-200 p-5 hover:shadow-lg hover:shadow-slate-200/50 hover:border-slate-400 transition-all cursor-pointer group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 bg-slate-100 rounded-xl flex items-center justify-center group-hover:bg-slate-200 transition">
                        <i class="fas fa-ticket-alt text-slate-600"></i>
                    </div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wide">All</span>
                </div>
                <div class="text-3xl font-bold text-slate-800"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="text-xs text-slate-500 mt-1">All tickets</div>
            </a>

            <!-- New Tickets -->
            <a href="tickets.php?view=pool" class="block bg-white rounded-xl border border-slate-200 p-5 hover:shadow-lg hover:shadow-cyan-100 hover:border-cyan-300 transition-all cursor-pointer group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 bg-cyan-50 rounded-xl flex items-center justify-center group-hover:bg-cyan-100 transition">
                        <i class="fas fa-inbox text-cyan-500"></i>
                    </div>
                    <span class="text-xs font-semibold text-cyan-500 uppercase tracking-wide">New</span>
                </div>
                <div class="text-3xl font-bold text-slate-800"><?php echo $stats['new_tickets'] ?? 0; ?></div>
                <div class="text-xs text-cyan-600 mt-1">New tickets</div>
            </a>

            <!-- Open Tickets (with sub-breakdown) -->
            <div onclick="window.location.href='tickets.php?assigned=assigned'" class="block bg-white rounded-xl border border-slate-200 p-5 hover:shadow-lg hover:shadow-blue-100 hover:border-blue-300 transition-all cursor-pointer group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center group-hover:bg-blue-100 transition">
                        <i class="fas fa-folder-open text-blue-500"></i>
                    </div>
                    <span class="text-xs font-semibold text-blue-500 uppercase tracking-wide">Open</span>
                </div>
                <div class="text-3xl font-bold text-slate-800"><?php echo $stats['open_tickets'] ?? 0; ?></div>
                <div class="flex items-center gap-2 mt-2">
                    <a href="tickets.php?status=in_progress" onclick="event.stopPropagation();"
                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-purple-100 text-purple-700 text-xs font-medium hover:bg-purple-200 transition">
                        <i class="fas fa-spinner fa-spin text-[9px]"></i>
                        <?php echo $stats['in_progress'] ?? 0; ?> In Progress
                    </a>
                    <a href="tickets.php?status=pending" onclick="event.stopPropagation();"
                       class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-amber-100 text-amber-700 text-xs font-medium hover:bg-amber-200 transition">
                        <i class="fas fa-clock text-[9px]"></i>
                        <?php echo $stats['pending'] ?? 0; ?> Pending
                    </a>
                </div>
            </div>

            <!-- Closed Tickets -->
            <a href="tickets.php?status=closed" class="block bg-white rounded-xl border border-slate-200 p-5 hover:shadow-lg hover:shadow-gray-100 hover:border-gray-300 transition-all cursor-pointer group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 bg-gray-100 rounded-xl flex items-center justify-center group-hover:bg-gray-200 transition">
                        <i class="fas fa-check-double text-gray-500"></i>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Closed</span>
                </div>
                <div class="text-3xl font-bold text-slate-800"><?php echo $stats['closed_tickets'] ?? 0; ?></div>
                <div class="text-xs text-gray-500 mt-1"><?php echo $stats['total'] > 0 ? round(($stats['closed_tickets'] / $stats['total']) * 100) : 0; ?>% completion rate</div>
            </a>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Ticket Trends Chart -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Ticket Trends</h3>
                        <p class="text-sm text-slate-500">New vs Closed tickets (last 10 days)</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs text-slate-500">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-cyan-500"></span>
                            <span>New</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-gray-600"></span>
                            <span>Closed</span>
                        </div>
                    </div>
                </div>
                <div class="h-64">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>

            <!-- Status Distribution -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Status Distribution</h3>
                        <p class="text-sm text-slate-500">Current ticket breakdown</p>
                    </div>
                </div>
                <div class="space-y-4">
                    <?php
                    $statusData = [
                        ['label' => 'New Tickets', 'count' => $stats['new_tickets'], 'color' => 'bg-cyan-500', 'bg' => 'bg-cyan-100', 'href' => 'tickets.php?assigned=unassigned'],
                        ['label' => 'Open — In Progress', 'count' => $stats['in_progress'], 'color' => 'bg-purple-500', 'bg' => 'bg-purple-100', 'href' => 'tickets.php?status=in_progress'],
                        ['label' => 'Open — Pending', 'count' => $stats['pending'], 'color' => 'bg-amber-500', 'bg' => 'bg-amber-100', 'href' => 'tickets.php?status=pending'],
                        ['label' => 'Closed', 'count' => $stats['closed_tickets'], 'color' => 'bg-gray-500', 'bg' => 'bg-gray-100', 'href' => 'tickets.php?status=closed'],
                    ];
                    
                    foreach ($statusData as $data):
                        $percentage = $stats['total'] > 0 ? round(($data['count'] / $stats['total']) * 100) : 0;
                    ?>
                    <a href="<?php echo $data['href']; ?>" class="block group">
                        <div class="flex justify-between items-center mb-1.5">
                            <span class="text-sm font-medium text-slate-700 group-hover:text-slate-900 transition"><?php echo $data['label']; ?></span>
                            <span class="text-sm text-slate-500"><?php echo $data['count']; ?> (<?php echo $percentage; ?>%)</span>
                        </div>
                        <div class="w-full <?php echo $data['bg']; ?> h-2 rounded-full">
                            <div class="<?php echo $data['color']; ?> h-2 rounded-full transition-all" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                
                <!-- Summary -->
                <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-slate-50 rounded-xl">
                        <div class="text-2xl font-bold text-slate-800"><?php echo $stats['open_tickets'] ?? 0; ?></div>
                        <div class="text-xs text-slate-500 uppercase">Open Tickets</div>
                    </div>
                    <div class="text-center p-4 bg-gray-50 rounded-xl">
                        <div class="text-2xl font-bold text-gray-600"><?php echo $stats['total'] > 0 ? round(($stats['closed_tickets'] / $stats['total']) * 100) : 0; ?>%</div>
                        <div class="text-xs text-gray-500 uppercase">Completion Rate</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly SLA Report -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm mb-8">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">SLA Report</h3>
                    <p class="text-sm text-slate-500"><?php echo htmlspecialchars($monthlySlaReport['month_label'] ?? date('F Y')); ?> summary for workload and capacity planning</p>
                </div>
                <a href="sla_performance.php" class="text-sm text-teal-600 hover:text-teal-700 font-medium">View Details →</a>
            </div>

            <?php
            $formatMinutes = function ($minutes) {
                $minutes = (int)round((float)$minutes);
                if ($minutes <= 0) {
                    return '0m';
                }

                $days = intdiv($minutes, 1440);
                $remainingAfterDays = $minutes % 1440;
                $hours = intdiv($remainingAfterDays, 60);
                $mins = $remainingAfterDays % 60;

                if ($days > 0) {
                    return $days . 'd ' . $hours . 'h';
                }

                if ($hours > 0) {
                    return $hours . 'h ' . $mins . 'm';
                }

                return $mins . 'm';
            };

            $slaResponsePercent = (float)($monthlySlaReport['response_sla_met_percent'] ?? 0);
            ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">
                <div class="p-4 bg-slate-50 rounded-xl border border-slate-100">
                    <div class="text-xs text-slate-500 uppercase tracking-wide">Entered</div>
                    <div class="text-2xl font-bold text-slate-800 mt-1"><?php echo (int)($monthlySlaReport['total_entered'] ?? 0); ?></div>
                    <div class="text-xs text-slate-500 mt-1">Tickets created this month</div>
                </div>

                <div class="p-4 bg-teal-50 rounded-xl border border-teal-100">
                    <div class="text-xs text-teal-700 uppercase tracking-wide">Resolved</div>
                    <div class="text-2xl font-bold text-teal-700 mt-1"><?php echo (int)($monthlySlaReport['total_resolved'] ?? 0); ?></div>
                    <div class="text-xs text-teal-600 mt-1">Tickets closed this month</div>
                </div>

                <div class="p-4 bg-amber-50 rounded-xl border border-amber-100">
                    <div class="text-xs text-amber-700 uppercase tracking-wide">Pending</div>
                    <div class="text-2xl font-bold text-amber-700 mt-1"><?php echo (int)($monthlySlaReport['total_pending'] ?? 0); ?></div>
                    <div class="text-xs text-amber-600 mt-1">Open backlog from monthly intake</div>
                </div>

                <div class="p-4 bg-cyan-50 rounded-xl border border-cyan-100">
                    <div class="text-xs text-cyan-700 uppercase tracking-wide">Response SLA Met</div>
                    <div class="text-2xl font-bold text-cyan-700 mt-1"><?php echo rtrim(rtrim(number_format($slaResponsePercent, 2), '0'), '.'); ?>%</div>
                    <div class="text-xs text-cyan-600 mt-1">First response compliance</div>
                </div>

                <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                    <div class="text-xs text-gray-600 uppercase tracking-wide">Speed</div>
                    <div class="text-sm font-semibold text-gray-700 mt-2">First response: <span class="font-bold"><?php echo $formatMinutes($monthlySlaReport['avg_first_response_minutes'] ?? 0); ?></span></div>
                    <div class="text-sm font-semibold text-gray-700 mt-1">Time to close: <span class="font-bold"><?php echo $formatMinutes($monthlySlaReport['avg_close_minutes'] ?? 0); ?></span></div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 gap-6">
            <!-- Recent Tickets Table -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Recent Tickets</h3>
                        <p class="text-sm text-slate-500"><?php echo $recentTicketsPagination['totalItems']; ?> total tickets</p>
                    </div>
                    <a href="tickets.php" class="text-sm text-teal-600 hover:text-teal-700 font-medium">View All →</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full" id="dashboardTable">
                        <thead class="bg-slate-50 border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Ticket</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Priority</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Date</th>
                            </tr>
                        </thead>
                        <tbody id="dashboardTableBody" class="divide-y divide-slate-100">
                            <?php foreach ($recentTickets as $ticket): 
                                $statusColors = [
                                    'pending'     => 'bg-amber-100 text-amber-700',
                                    'in_progress' => 'bg-purple-100 text-purple-700',
                                    'closed'      => 'bg-gray-100 text-gray-700',
                                ];
                                $priorityColors = [
                                    'high' => 'bg-red-100 text-red-700',
                                    'medium' => 'bg-slate-100 text-slate-700',
                                    'low' => 'bg-green-100 text-green-700'
                                ];
                            ?>
                            <tr class="hover:bg-slate-50 cursor-pointer transition searchable-row" 
                                data-ticket-row 
                                data-ticket-id="<?php echo $ticket['id']; ?>"
                                data-ticket-title="<?php echo htmlspecialchars($ticket['title']); ?>"
                                data-ticket-status="<?php echo $ticket['status']; ?>"
                                data-ticket-priority="<?php echo $ticket['priority']; ?>"
                                onclick="window.location.href='view_ticket.php?id=<?php echo $ticket['id']; ?>'">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-9 h-9 bg-gradient-to-br from-teal-400 to-cyan-500 rounded-lg text-white flex items-center justify-center text-xs font-bold mr-3 shadow-sm">
                                            <?php echo $ticket['id']; ?>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($ticket['title']); ?></div>
                                            <div class="text-xs text-slate-500"><?php echo htmlspecialchars($ticket['category_name']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-lg <?php echo $statusColors[$ticket['status']] ?? 'bg-slate-100 text-slate-700'; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex px-2.5 py-1 text-xs font-medium rounded-lg <?php echo $priorityColors[$ticket['priority']] ?? 'bg-slate-100 text-slate-700'; ?>">
                                        <?php echo ucfirst($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">
                                    <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($recentTicketsPagination['totalPages'] > 1): ?>
                <div class="px-6 py-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-sm text-slate-500">
                        Page <?php echo $recentTicketsPagination['currentPage']; ?> of <?php echo $recentTicketsPagination['totalPages']; ?>
                    </div>
                    <div class="flex items-center space-x-2">
                        <?php if ($recentTicketsPagination['hasPrevPage']): ?>
                        <a href="?page=<?php echo $recentTicketsPagination['currentPage'] - 1; ?>" 
                           class="px-4 py-2 text-sm border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-lg transition">
                            Previous
                        </a>
                        <?php endif; ?>
                        
                        <?php if ($recentTicketsPagination['hasNextPage']): ?>
                        <a href="?page=<?php echo $recentTicketsPagination['currentPage'] + 1; ?>" 
                           class="px-4 py-2 text-sm bg-gradient-to-r from-teal-500 to-cyan-500 text-white rounded-lg hover:from-teal-600 hover:to-cyan-600 transition">
                            Next
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard JavaScript -->
<script>
    // Set greeting based on time
    document.addEventListener('DOMContentLoaded', function() {
        const hour = new Date().getHours();
        const greeting = hour < 12 ? 'Good Morning' : hour < 18 ? 'Good Afternoon' : 'Good Evening';
        document.getElementById('greetingText').textContent = greeting;
    });
    
    // Search tickets
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
    
    // Initialize Line Chart
    document.addEventListener('DOMContentLoaded', function() {
        const chartLabels = <?php echo json_encode($chartData['labels']); ?>;
        const newTicketValues = <?php echo json_encode($chartData['newData']); ?>;
        const closedTicketValues = <?php echo json_encode($chartData['closedData']); ?>;
        
        const lineCtx = document.getElementById('lineChart');
        if (lineCtx && typeof Chart !== 'undefined') {
            const ctx = lineCtx.getContext('2d');
            const newGradient = ctx.createLinearGradient(0, 0, 0, 250);
            newGradient.addColorStop(0, 'rgba(6, 182, 212, 0.22)');
            newGradient.addColorStop(1, 'rgba(6, 182, 212, 0.02)');

            const closedGradient = ctx.createLinearGradient(0, 0, 0, 250);
            closedGradient.addColorStop(0, 'rgba(71, 85, 105, 0.20)');
            closedGradient.addColorStop(1, 'rgba(71, 85, 105, 0.02)');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [
                        {
                            label: 'New Tickets',
                            data: newTicketValues,
                            borderColor: '#06b6d4',
                            backgroundColor: newGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4,
                            pointBackgroundColor: '#06b6d4',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6
                        },
                        {
                            label: 'Closed Tickets',
                            data: closedTicketValues,
                            borderColor: '#475569',
                            backgroundColor: closedGradient,
                            borderWidth: 3,
                            fill: true,
                            tension: 0.35,
                            pointRadius: 4,
                            pointBackgroundColor: '#475569',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 8,
                                color: '#475569'
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            padding: 12,
                            borderColor: '#06b6d4',
                            borderWidth: 1,
                            displayColors: true,
                            cornerRadius: 8
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { color: '#64748b', font: { size: 11 } },
                            grid: { color: '#f1f5f9' },
                            border: { display: false }
                        },
                        x: {
                            ticks: { color: '#64748b', font: { size: 11 } },
                            grid: { display: false },
                            border: { display: false }
                        }
                    }
                }
            });
        }
    });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>


