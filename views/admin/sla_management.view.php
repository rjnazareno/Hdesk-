<?php 
$pageTitle = 'SLA Management - ' . APP_NAME;
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-slate-50">
    <?php
    // Set header variables for this page
    $headerTitle = 'SLA Management';
    $headerSubtitle = 'Configure and monitor Service Level Agreements';
    $showQuickActions = true;
    $showSearch = false;
    
    include __DIR__ . '/../../includes/top_header.php';
    ?>

    <!-- Content -->
    <div class="p-4 lg:p-8">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-4 py-3">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <?php if ($stats && $stats['total_tickets'] > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Response Compliance -->
            <div class="bg-white border border-slate-200 p-6 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center justify-between mb-3 relative z-10">
                    <div class="w-12 h-12 bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                        <i class="fas fa-reply text-lg"></i>
                    </div>
                    <span class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['response_compliance_rate'], 1); ?>%</span>
                </div>
                <h3 class="text-sm font-medium text-gray-700">Response SLA</h3>
                <p class="text-xs text-slate-500 mt-1">
                    <?php echo $stats['response_met']; ?> met of <?php echo $stats['total_tickets']; ?> tickets
                </p>
            </div>

            <!-- Resolution Compliance -->
            <div class="bg-white border border-slate-200 p-6 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center justify-between mb-3 relative z-10">
                    <div class="w-12 h-12 bg-blue-500/20 flex items-center justify-center text-blue-400">
                        <i class="fas fa-check-circle text-lg"></i>
                    </div>
                    <span class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['resolution_compliance_rate'], 1); ?>%</span>
                </div>
                <h3 class="text-sm font-medium text-gray-700">Resolution SLA</h3>
                <p class="text-xs text-slate-500 mt-1">
                    <?php echo $stats['resolution_met']; ?> met of <?php echo $stats['total_tickets']; ?> tickets
                </p>
            </div>

            <!-- Avg Response Time -->
            <div class="bg-white border border-slate-200 p-6 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center justify-between mb-3 relative z-10">
                    <div class="w-12 h-12 bg-yellow-500/20 flex items-center justify-center text-yellow-400">
                        <i class="fas fa-stopwatch text-lg"></i>
                    </div>
                    <span class="text-3xl font-bold text-slate-800">
                        <?php echo number_format($stats['avg_response_time'] / 60, 1); ?>h
                    </span>
                </div>
                <h3 class="text-sm font-medium text-gray-700">Avg Response Time</h3>
                <p class="text-xs text-slate-500 mt-1">
                    <?php echo number_format($stats['avg_response_time']); ?> minutes average
                </p>
            </div>

            <!-- Avg Resolution Time -->
            <div class="bg-white border border-slate-200 p-6 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center justify-between mb-3 relative z-10">
                    <div class="w-12 h-12 bg-purple-500/20 flex items-center justify-center text-purple-400">
                        <i class="fas fa-hourglass-end text-lg"></i>
                    </div>
                    <span class="text-3xl font-bold text-slate-800">
                        <?php echo number_format($stats['avg_resolution_time'] / 60, 1); ?>h
                    </span>
                </div>
                <h3 class="text-sm font-medium text-gray-700">Avg Resolution Time</h3>
                <p class="text-xs text-slate-500 mt-1">
                    <?php echo number_format($stats['avg_resolution_time']); ?> minutes average
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- At-Risk Tickets -->
            <div class="bg-white border border-slate-200 p-6 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center space-x-3 mb-4 relative z-10">
                    <div class="w-8 h-8 bg-yellow-500/20 flex items-center justify-center text-yellow-400">
                        <i class="fas fa-exclamation-triangle text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-slate-800">At-Risk Tickets</h3>
                        <p class="text-sm text-slate-500">Nearing SLA breach (<1 hour remaining)</p>
                    </div>
                </div>
                
                <?php if (empty($atRiskTickets)): ?>
                <div class="text-center py-8 text-slate-500">
                    <i class="fas fa-check-circle text-4xl mb-2 text-emerald-500"></i>
                    <p>No tickets at risk</p>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($atRiskTickets as $ticket): ?>
                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="block p-3 border border-yellow-500/30 bg-yellow-500/10 hover:bg-yellow-500/20 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-medium text-slate-800"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                                <p class="text-sm text-slate-500 truncate"><?php echo htmlspecialchars($ticket['title']); ?></p>
                            </div>
                            <span class="text-xs font-semibold text-yellow-400">
                                <?php echo floor($ticket['minutes_remaining'] / 60); ?>h <?php echo $ticket['minutes_remaining'] % 60; ?>m left
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Breached Tickets -->
            <div class="bg-white border border-slate-200 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-red-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center justify-between p-6 pb-4 relative z-10">
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-red-500/20 flex items-center justify-center text-red-400">
                            <i class="fas fa-times-circle text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800">Breached Tickets</h3>
                            <p class="text-sm text-slate-500">Missed SLA deadline (<?php echo $totalBreached; ?> total)</p>
                        </div>
                    </div>
                    <?php if ($totalPages > 1): ?>
                    <div class="text-xs text-slate-500">
                        Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="px-6 pb-6">
                    <?php if (empty($breachedTickets) && $totalBreached == 0): ?>
                    <div class="text-center py-8 text-slate-500">
                        <i class="fas fa-check-circle text-4xl mb-2 text-emerald-500"></i>
                        <p>No breached tickets</p>
                    </div>
                    <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($breachedTickets as $ticket): ?>
                        <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="block p-3 border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 transition">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-medium text-slate-800"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                                    <p class="text-sm text-slate-500 truncate"><?php echo htmlspecialchars($ticket['title']); ?></p>
                                </div>
                                <span class="text-xs font-semibold text-red-400">
                                    <?php echo floor($ticket['minutes_overdue'] / 60); ?>h <?php echo $ticket['minutes_overdue'] % 60; ?>m overdue
                                </span>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <?php if ($totalPages > 1): ?>
                    <div class="mt-6 pt-4 border-t border-slate-200">
                        <div class="flex items-center justify-between">
                            <!-- Previous Button -->
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" 
                               class="px-3 py-2 bg-slate-50 border border-gray-300 text-gray-700 hover:bg-slate-100 hover:text-slate-800 transition rounded-lg text-sm">
                                <i class="fas fa-chevron-left mr-1"></i>
                                Previous
                            </a>
                            <?php else: ?>
                            <span class="px-3 py-2 bg-white border border-slate-200 text-gray-500 rounded-lg cursor-not-allowed text-sm">
                                <i class="fas fa-chevron-left mr-1"></i>
                                Previous
                            </span>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <div class="flex items-center space-x-1">
                                <?php
                                $maxPagesToShow = 5;
                                $halfPages = floor($maxPagesToShow / 2);
                                $startPage = max(1, $page - $halfPages);
                                $endPage = min($totalPages, $page + $halfPages);
                                
                                // Adjust if at start or end
                                if ($page <= $halfPages) {
                                    $endPage = min($maxPagesToShow, $totalPages);
                                }
                                if ($page > $totalPages - $halfPages) {
                                    $startPage = max(1, $totalPages - $maxPagesToShow + 1);
                                }

                                // First page
                                if ($startPage > 1) {
                                    echo '<a href="?page=1" class="px-3 py-2 border border-gray-300 bg-slate-50 text-gray-700 hover:bg-slate-100 hover:text-slate-800 transition rounded-lg text-sm">1</a>';
                                    if ($startPage > 2) echo '<span class="px-2 text-gray-500">...</span>';
                                }

                                // Page numbers
                                for ($i = $startPage; $i <= $endPage; $i++):
                                    if ($i == $page):
                                ?>
                                <span class="px-3 py-2 bg-gradient-to-r from-emerald-400 to-emerald-600 text-slate-800 font-semibold rounded-lg text-sm">
                                    <?php echo $i; ?>
                                </span>
                                <?php else: ?>
                                <a href="?page=<?php echo $i; ?>" 
                                   class="px-3 py-2 border border-gray-300 bg-slate-50 text-gray-700 hover:bg-slate-100 hover:text-slate-800 transition rounded-lg text-sm">
                                    <?php echo $i; ?>
                                </a>
                                <?php 
                                    endif;
                                endfor;

                                // Last page
                                if ($endPage < $totalPages) {
                                    if ($endPage < $totalPages - 1) echo '<span class="px-2 text-gray-500">...</span>';
                                    echo '<a href="?page=' . $totalPages . '" class="px-3 py-2 border border-gray-300 bg-slate-50 text-gray-700 hover:bg-slate-100 hover:text-slate-800 transition rounded-lg text-sm">' . $totalPages . '</a>';
                                }
                                ?>
                            </div>

                            <!-- Next Button -->
                            <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" 
                               class="px-3 py-2 bg-slate-50 border border-gray-300 text-gray-700 hover:bg-slate-100 hover:text-slate-800 transition rounded-lg text-sm">
                                Next
                                <i class="fas fa-chevron-right ml-1"></i>
                            </a>
                            <?php else: ?>
                            <span class="px-3 py-2 bg-white border border-slate-200 text-gray-500 rounded-lg cursor-not-allowed text-sm">
                                Next
                                <i class="fas fa-chevron-right ml-1"></i>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SLA Policies Configuration -->
        <div class="bg-white border border-slate-200 p-6">
            <div class="flex items-center space-x-3 mb-6">
                <div class="w-8 h-8 bg-slate-100 flex items-center justify-center text-gray-700">
                    <i class="fas fa-cog text-sm"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">SLA Policies</h3>
                    <p class="text-sm text-slate-500">Configure response and resolution times by priority</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-gray-300">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Response Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Resolution Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Mode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php foreach ($policies as $policy): ?>
                        <tr id="policy-row-<?php echo $policy['id']; ?>" class="hover:bg-slate-100/30">
                            <td class="px-6 py-4 border-b border-slate-200">
                                <?php
                                $priorityBadges = [
                                    'high' => 'bg-red-600',
                                    'medium' => 'bg-yellow-600',
                                    'low' => 'bg-emerald-600'
                                ];
                                ?>
                                <span class="px-3 py-1 text-xs font-semibold text-slate-800 <?php echo $priorityBadges[$policy['priority']]; ?>">
                                    <?php echo strtoupper($policy['priority']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-200 text-sm text-slate-800">
                                <span class="font-medium"><?php echo floor($policy['response_time'] / 60); ?>h <?php echo $policy['response_time'] % 60; ?>m</span>
                                <span class="text-slate-500">(<?php echo $policy['response_time']; ?> min)</span>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-200 text-sm text-slate-800">
                                <span class="font-medium"><?php echo floor($policy['resolution_time'] / 60); ?>h <?php echo $policy['resolution_time'] % 60; ?>m</span>
                                <span class="text-slate-500">(<?php echo $policy['resolution_time']; ?> min)</span>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-200 text-sm text-gray-700">
                                <?php echo $policy['is_business_hours'] ? 'Business Hours' : '24/7'; ?>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-200">
                                <?php if ($policy['is_active']): ?>
                                <span class="px-2 py-1 bg-emerald-600 text-slate-800 text-xs font-semibold">
                                    <i class="fas fa-check mr-1"></i>Active
                                </span>
                                <?php else: ?>
                                <span class="px-2 py-1 bg-slate-600 text-slate-800 text-xs font-semibold">
                                    <i class="fas fa-times mr-1"></i>Inactive
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-200">
                                <button onclick="editPolicy(<?php echo htmlspecialchars(json_encode($policy)); ?>)" 
                                        class="px-3 py-1 bg-gradient-to-r from-emerald-400 to-emerald-600 text-slate-800 hover:from-teal-700 hover:to-emerald-700 transition text-sm rounded">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Category → Priority Mapping Reference -->
        <?php if (!empty($priorityMappings)): ?>
        <div class="bg-white border border-slate-200 p-6 mt-8">
            <div class="flex items-center space-x-3 mb-6">
                <div class="w-8 h-8 bg-blue-50 flex items-center justify-center text-blue-600">
                    <i class="fas fa-sitemap text-sm"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-slate-800">Category → Priority Mapping</h3>
                    <p class="text-sm text-slate-500">Auto-assigned priority based on issue type (from SLA Guide)</p>
                </div>
            </div>

            <!-- SLA Targets Summary -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center mb-2">
                        <span class="text-lg mr-2">🔴</span>
                        <span class="font-semibold text-red-700">HIGH</span>
                    </div>
                    <p class="text-sm text-red-600">Response: <strong>30 minutes</strong></p>
                    <p class="text-sm text-red-600">Resolution: <strong>1 business day</strong></p>
                </div>
                <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <div class="flex items-center mb-2">
                        <span class="text-lg mr-2">🟡</span>
                        <span class="font-semibold text-yellow-700">MEDIUM</span>
                    </div>
                    <p class="text-sm text-yellow-600">Response: <strong>4 hours</strong></p>
                    <p class="text-sm text-yellow-600">Resolution: <strong>2–3 days</strong></p>
                </div>
                <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center mb-2">
                        <span class="text-lg mr-2">🟢</span>
                        <span class="font-semibold text-green-700">LOW</span>
                    </div>
                    <p class="text-sm text-green-600">Response: <strong>1 day</strong></p>
                    <p class="text-sm text-green-600">Resolution: <strong>3–5 days</strong></p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-50 border-b border-gray-300">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Department</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Parent Category</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Issue Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Auto Priority</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Response Target</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase">Resolution Target</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php 
                        $lastDept = '';
                        $lastParent = '';
                        foreach ($priorityMappings as $mapping): 
                            $deptName = $mapping['department_name'] ?? 'Unknown';
                            $parentName = $mapping['parent_category_name'] ?? '-';
                            $isSubcategory = !empty($mapping['parent_id']);
                            $target = CategoryPriorityMap::getSLATargets($mapping['default_priority']);
                            
                            $priorityBadges = [
                                'high' => 'bg-red-600 text-white',
                                'medium' => 'bg-yellow-500 text-white',
                                'low' => 'bg-green-600 text-white'
                            ];
                        ?>
                        <tr class="hover:bg-slate-50 <?php echo $isSubcategory ? '' : 'bg-slate-100 font-medium'; ?>">
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <?php if ($deptName !== $lastDept): ?>
                                    <?php echo htmlspecialchars($deptName); ?>
                                    <?php $lastDept = $deptName; ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                <?php if ($parentName !== $lastParent): ?>
                                    <?php echo htmlspecialchars($parentName); ?>
                                    <?php $lastParent = $parentName; ?>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-800 <?php echo $isSubcategory ? 'pl-8' : 'font-semibold'; ?>">
                                <?php if ($isSubcategory): ?>
                                    <i class="fas fa-angle-right text-gray-400 mr-1"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($mapping['category_name']); ?>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold <?php echo $priorityBadges[$mapping['default_priority']]; ?> rounded">
                                    <?php echo strtoupper($mapping['default_priority']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?php echo $target['response']; ?></td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?php echo $target['resolution']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-xs text-blue-700">
                    <i class="fas fa-info-circle mr-1"></i>
                    <strong>Note:</strong> Priority is automatically set when employees/admins select a category during ticket creation. 
                    Admins can override the auto-assigned priority using the override checkbox.
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Edit Policy Modal -->
<div id="editPolicyModal" class="hidden fixed inset-0 bg-black/60  z-50 flex items-center justify-center p-4">
    <div class="bg-slate-100 border border-slate-200 max-w-lg w-full p-6 rounded-lg">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-slate-800">Edit SLA Policy</h3>
            <button onclick="closeEditModal()" class="text-slate-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="sla_management.php" class="space-y-4">
            <input type="hidden" name="action" value="update_policy">
            <input type="hidden" name="policy_id" id="edit_policy_id">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                <input type="text" id="edit_priority" readonly 
                       class="w-full px-4 py-2 border border-gray-300 bg-slate-50 text-gray-700 font-semibold uppercase">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Response Time (minutes)</label>
                    <input type="number" name="response_time" id="edit_response_time" required min="1"
                           class="w-full px-4 py-2 border border-gray-300 bg-slate-50 text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-cyan-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Resolution Time (minutes)</label>
                    <input type="number" name="resolution_time" id="edit_resolution_time" required min="1"
                           class="w-full px-4 py-2 border border-gray-300 bg-slate-50 text-slate-800 placeholder-slate-400 focus:ring-2 focus:ring-cyan-500">
                </div>
            </div>

            <div>
                <label class="flex items-center space-x-3">
                    <input type="checkbox" name="is_business_hours" id="edit_is_business_hours" value="1"
                           class="w-4 h-4 text-cyan-500 border-gray-300 focus:ring-cyan-500">
                    <span class="text-sm font-medium text-gray-700">Calculate using business hours only (Mon-Fri, 8AM-5PM)</span>
                </label>
            </div>

            <div>
                <label class="flex items-center space-x-3">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1"
                           class="w-4 h-4 text-cyan-500 border-gray-300 focus:ring-cyan-500">
                    <span class="text-sm font-medium text-gray-700">Policy is active</span>
                </label>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-2 border border-gray-300 text-gray-700 hover:bg-slate-50 hover:text-slate-800 transition rounded-lg">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-gradient-to-r from-emerald-400 to-emerald-600 text-slate-800 hover:from-teal-700 hover:to-emerald-700 transition rounded-lg">
                    <i class="fas fa-save mr-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editPolicy(policy) {
    document.getElementById('edit_policy_id').value = policy.id;
    document.getElementById('edit_priority').value = policy.priority.toUpperCase();
    document.getElementById('edit_response_time').value = policy.response_time;
    document.getElementById('edit_resolution_time').value = policy.resolution_time;
    document.getElementById('edit_is_business_hours').checked = policy.is_business_hours == 1;
    document.getElementById('edit_is_active').checked = policy.is_active == 1;
    document.getElementById('editPolicyModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editPolicyModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('editPolicyModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>


