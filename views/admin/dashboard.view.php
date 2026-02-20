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
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
            <!-- Total Tickets -->
            <a href="tickets.php" class="block bg-white rounded-xl border border-slate-200 p-5 hover:shadow-lg hover:shadow-slate-200/50 hover:border-teal-300 transition-all cursor-pointer group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 bg-slate-100 rounded-xl flex items-center justify-center group-hover:bg-teal-100 transition">
                        <i class="fas fa-ticket-alt text-slate-600 group-hover:text-teal-600 transition"></i>
                    </div>
                    <span class="text-xs font-medium text-slate-400 uppercase">Total</span>
                </div>
                <div class="text-3xl font-bold text-slate-800"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="text-xs text-slate-500 mt-1">All tickets</div>
            </a>

            <!-- Pending -->
            <a href="tickets.php?status=pending" class="block bg-white rounded-xl border border-slate-200 p-5 hover:shadow-lg hover:shadow-amber-100 hover:border-amber-300 transition-all cursor-pointer group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 bg-amber-50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-clock text-amber-500"></i>
                    </div>
                    <span class="text-xs font-medium text-amber-500 uppercase">Pending</span>
                </div>
                <div class="text-3xl font-bold text-slate-800"><?php echo $stats['pending'] ?? 0; ?></div>
                <div class="text-xs text-amber-600 mt-1">Awaiting action</div>
            </a>

            <!-- Open -->
            <a href="tickets.php?status=open" class="block bg-white rounded-xl border border-slate-200 p-5 hover:shadow-lg hover:shadow-blue-100 hover:border-blue-300 transition-all cursor-pointer group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-folder-open text-blue-500"></i>
                    </div>
                    <span class="text-xs font-medium text-blue-500 uppercase">Open</span>
                </div>
                <div class="text-3xl font-bold text-slate-800"><?php echo $stats['open'] ?? 0; ?></div>
                <div class="text-xs text-blue-600 mt-1">Active tickets</div>
            </a>

            <!-- In Progress -->
            <a href="tickets.php?status=in_progress" class="block bg-white rounded-xl border border-slate-200 p-5 hover:shadow-lg hover:shadow-purple-100 hover:border-purple-300 transition-all cursor-pointer group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 bg-purple-50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-spinner text-purple-500"></i>
                    </div>
                    <span class="text-xs font-medium text-purple-500 uppercase">Progress</span>
                </div>
                <div class="text-3xl font-bold text-slate-800"><?php echo $stats['in_progress'] ?? 0; ?></div>
                <div class="text-xs text-purple-600 mt-1">Being worked on</div>
            </a>

            <!-- Resolved -->
            <a href="tickets.php?status=closed" class="block bg-white rounded-xl border border-slate-200 p-5 hover:shadow-lg hover:shadow-teal-100 hover:border-teal-300 transition-all cursor-pointer group">
                <div class="flex items-center justify-between mb-3">
                    <div class="w-11 h-11 bg-teal-50 rounded-xl flex items-center justify-center">
                        <i class="fas fa-check-circle text-teal-500"></i>
                    </div>
                    <span class="text-xs font-medium text-teal-500 uppercase">Resolved</span>
                </div>
                <div class="text-3xl font-bold text-slate-800"><?php echo $stats['closed'] ?? 0; ?></div>
                <div class="text-xs text-teal-600 mt-1"><?php echo $stats['total'] > 0 ? round(($stats['closed'] / $stats['total']) * 100) : 0; ?>% completed</div>
            </a>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- Ticket Trends Chart -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">Ticket Trends</h3>
                        <p class="text-sm text-slate-500">Last 10 days activity</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 bg-gradient-to-r from-teal-400 to-cyan-500 rounded-full"></span>
                        <span class="text-xs text-slate-500">Tickets</span>
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
                        ['label' => 'Pending', 'count' => $stats['pending'], 'color' => 'bg-amber-500', 'bg' => 'bg-amber-100'],
                        ['label' => 'Open', 'count' => $stats['open'], 'color' => 'bg-blue-500', 'bg' => 'bg-blue-100'],
                        ['label' => 'In Progress', 'count' => $stats['in_progress'], 'color' => 'bg-purple-500', 'bg' => 'bg-purple-100'],
                        ['label' => 'Resolved', 'count' => $stats['closed'], 'color' => 'bg-teal-500', 'bg' => 'bg-teal-100']
                    ];
                    
                    foreach ($statusData as $data):
                        $percentage = $stats['total'] > 0 ? round(($data['count'] / $stats['total']) * 100) : 0;
                    ?>
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-sm font-medium text-slate-700"><?php echo $data['label']; ?></span>
                            <span class="text-sm text-slate-500"><?php echo $data['count']; ?> (<?php echo $percentage; ?>%)</span>
                        </div>
                        <div class="w-full <?php echo $data['bg']; ?> h-2 rounded-full">
                            <div class="<?php echo $data['color']; ?> h-2 rounded-full transition-all" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Summary -->
                <div class="mt-6 pt-6 border-t border-slate-100 grid grid-cols-2 gap-4">
                    <div class="text-center p-4 bg-gradient-to-br from-slate-50 to-slate-100 rounded-xl">
                        <div class="text-2xl font-bold text-slate-800"><?php echo $stats['open'] + $stats['in_progress']; ?></div>
                        <div class="text-xs text-slate-500 uppercase">Active Tickets</div>
                    </div>
                    <div class="text-center p-4 bg-gradient-to-br from-teal-50 to-cyan-50 rounded-xl">
                        <div class="text-2xl font-bold text-teal-600"><?php echo $stats['total'] > 0 ? round(($stats['closed'] / $stats['total']) * 100) : 0; ?>%</div>
                        <div class="text-xs text-teal-600 uppercase">Resolution Rate</div>
                    </div>
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
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'open' => 'bg-blue-100 text-blue-700',
                                    'in_progress' => 'bg-purple-100 text-purple-700',
                                    'closed' => 'bg-teal-100 text-teal-700'
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
        const chartValues = <?php echo json_encode($chartData['data']); ?>;
        
        const lineCtx = document.getElementById('lineChart');
        if (lineCtx && typeof Chart !== 'undefined') {
            const ctx = lineCtx.getContext('2d');
            const gradient = ctx.createLinearGradient(0, 0, 0, 250);
            gradient.addColorStop(0, 'rgba(20, 184, 166, 0.3)');
            gradient.addColorStop(1, 'rgba(20, 184, 166, 0.01)');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Tickets',
                        data: chartValues,
                        borderColor: '#14b8a6',
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#14b8a6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            padding: 12,
                            borderColor: '#14b8a6',
                            borderWidth: 1,
                            displayColors: false,
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


