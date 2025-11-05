<?php 
$pageTitle = 'SLA Management - IT Help Desk';
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Top Bar -->
    <div class="bg-slate-800/50 border-b border-slate-700/50 backdrop-blur-md">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <div class="flex items-center space-x-4">
                <div class="hidden lg:flex items-center justify-center w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded-lg">
                    <i class="fas fa-clock text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-white">
                        SLA Management
                    </h1>
                    <p class="text-sm text-slate-400 mt-0.5">Configure and monitor Service Level Agreements</p>
                </div>
            </div>
        </div>
    </div>

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
            <div class="bg-slate-800/50 border border-slate-700/50 p-6 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center justify-between mb-3 relative z-10">
                    <div class="w-12 h-12 bg-emerald-500/20 flex items-center justify-center text-emerald-400">
                        <i class="fas fa-reply text-lg"></i>
                    </div>
                    <span class="text-3xl font-bold text-white"><?php echo number_format($stats['response_compliance_rate'], 1); ?>%</span>
                </div>
                <h3 class="text-sm font-medium text-slate-300">Response SLA</h3>
                <p class="text-xs text-slate-400 mt-1">
                    <?php echo $stats['response_met']; ?> met of <?php echo $stats['total_tickets']; ?> tickets
                </p>
            </div>

            <!-- Resolution Compliance -->
            <div class="bg-slate-800/50 border border-slate-700/50 p-6 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center justify-between mb-3 relative z-10">
                    <div class="w-12 h-12 bg-blue-500/20 flex items-center justify-center text-blue-400">
                        <i class="fas fa-check-circle text-lg"></i>
                    </div>
                    <span class="text-3xl font-bold text-white"><?php echo number_format($stats['resolution_compliance_rate'], 1); ?>%</span>
                </div>
                <h3 class="text-sm font-medium text-slate-300">Resolution SLA</h3>
                <p class="text-xs text-slate-400 mt-1">
                    <?php echo $stats['resolution_met']; ?> met of <?php echo $stats['total_tickets']; ?> tickets
                </p>
            </div>

            <!-- Avg Response Time -->
            <div class="bg-slate-800/50 border border-slate-700/50 p-6 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center justify-between mb-3 relative z-10">
                    <div class="w-12 h-12 bg-yellow-500/20 flex items-center justify-center text-yellow-400">
                        <i class="fas fa-stopwatch text-lg"></i>
                    </div>
                    <span class="text-3xl font-bold text-white">
                        <?php echo number_format($stats['avg_response_time'] / 60, 1); ?>h
                    </span>
                </div>
                <h3 class="text-sm font-medium text-slate-300">Avg Response Time</h3>
                <p class="text-xs text-slate-400 mt-1">
                    <?php echo number_format($stats['avg_response_time']); ?> minutes average
                </p>
            </div>

            <!-- Avg Resolution Time -->
            <div class="bg-slate-800/50 border border-slate-700/50 p-6 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center justify-between mb-3 relative z-10">
                    <div class="w-12 h-12 bg-purple-500/20 flex items-center justify-center text-purple-400">
                        <i class="fas fa-hourglass-end text-lg"></i>
                    </div>
                    <span class="text-3xl font-bold text-white">
                        <?php echo number_format($stats['avg_resolution_time'] / 60, 1); ?>h
                    </span>
                </div>
                <h3 class="text-sm font-medium text-slate-300">Avg Resolution Time</h3>
                <p class="text-xs text-slate-400 mt-1">
                    <?php echo number_format($stats['avg_resolution_time']); ?> minutes average
                </p>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- At-Risk Tickets -->
            <div class="bg-slate-800/50 border border-slate-700/50 p-6 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-yellow-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center space-x-3 mb-4 relative z-10">
                    <div class="w-8 h-8 bg-yellow-500/20 flex items-center justify-center text-yellow-400">
                        <i class="fas fa-exclamation-triangle text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">At-Risk Tickets</h3>
                        <p class="text-sm text-slate-400">Nearing SLA breach (<1 hour remaining)</p>
                    </div>
                </div>
                
                <?php if (empty($atRiskTickets)): ?>
                <div class="text-center py-8 text-slate-400">
                    <i class="fas fa-check-circle text-4xl mb-2 text-emerald-500"></i>
                    <p>No tickets at risk</p>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($atRiskTickets as $ticket): ?>
                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="block p-3 border border-yellow-500/30 bg-yellow-500/10 hover:bg-yellow-500/20 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-medium text-white"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                                <p class="text-sm text-slate-400 truncate"><?php echo htmlspecialchars($ticket['title']); ?></p>
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
            <div class="bg-slate-800/50 border border-slate-700/50 p-6 overflow-hidden relative">
                <div class="absolute inset-0 bg-gradient-to-br from-red-500/10 to-transparent pointer-events-none"></div>
                <div class="flex items-center space-x-3 mb-4 relative z-10">
                    <div class="w-8 h-8 bg-red-500/20 flex items-center justify-center text-red-400">
                        <i class="fas fa-times-circle text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">Breached Tickets</h3>
                        <p class="text-sm text-slate-400">Missed SLA deadline</p>
                    </div>
                </div>
                
                <?php if (empty($breachedTickets)): ?>
                <div class="text-center py-8 text-slate-400">
                    <i class="fas fa-check-circle text-4xl mb-2 text-emerald-500"></i>
                    <p>No breached tickets</p>
                </div>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($breachedTickets as $ticket): ?>
                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="block p-3 border border-red-500/30 bg-red-500/10 hover:bg-red-500/20 transition">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-medium text-white"><?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                                <p class="text-sm text-slate-400 truncate"><?php echo htmlspecialchars($ticket['title']); ?></p>
                            </div>
                            <span class="text-xs font-semibold text-red-400">
                                <?php echo floor($ticket['minutes_overdue'] / 60); ?>h <?php echo $ticket['minutes_overdue'] % 60; ?>m overdue
                            </span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- SLA Policies Configuration -->
        <div class="bg-slate-800/50 border border-slate-700/50 p-6">
            <div class="flex items-center space-x-3 mb-6">
                <div class="w-8 h-8 bg-slate-700 flex items-center justify-center text-slate-300">
                    <i class="fas fa-cog text-sm"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-white">SLA Policies</h3>
                    <p class="text-sm text-slate-400">Configure response and resolution times by priority</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-700/50 border-b border-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase">Priority</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase">Response Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase">Resolution Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase">Mode</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php foreach ($policies as $policy): ?>
                        <tr id="policy-row-<?php echo $policy['id']; ?>" class="hover:bg-slate-700/30">
                            <td class="px-6 py-4 border-b border-slate-700/50">
                                <?php
                                $priorityBadges = [
                                    'urgent' => 'bg-red-600',
                                    'high' => 'bg-orange-600',
                                    'medium' => 'bg-yellow-600',
                                    'low' => 'bg-emerald-600'
                                ];
                                ?>
                                <span class="px-3 py-1 text-xs font-semibold text-white <?php echo $priorityBadges[$policy['priority']]; ?>">
                                    <?php echo strtoupper($policy['priority']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-700/50 text-sm text-white">
                                <span class="font-medium"><?php echo floor($policy['response_time'] / 60); ?>h <?php echo $policy['response_time'] % 60; ?>m</span>
                                <span class="text-slate-400">(<?php echo $policy['response_time']; ?> min)</span>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-700/50 text-sm text-white">
                                <span class="font-medium"><?php echo floor($policy['resolution_time'] / 60); ?>h <?php echo $policy['resolution_time'] % 60; ?>m</span>
                                <span class="text-slate-400">(<?php echo $policy['resolution_time']; ?> min)</span>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-700/50 text-sm text-slate-300">
                                <?php echo $policy['is_business_hours'] ? 'Business Hours' : '24/7'; ?>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-700/50">
                                <?php if ($policy['is_active']): ?>
                                <span class="px-2 py-1 bg-emerald-600 text-white text-xs font-semibold">
                                    <i class="fas fa-check mr-1"></i>Active
                                </span>
                                <?php else: ?>
                                <span class="px-2 py-1 bg-slate-600 text-white text-xs font-semibold">
                                    <i class="fas fa-times mr-1"></i>Inactive
                                </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 border-b border-slate-700/50">
                                <button onclick="editPolicy(<?php echo htmlspecialchars(json_encode($policy)); ?>)" 
                                        class="px-3 py-1 bg-gradient-to-r from-cyan-500 to-blue-600 text-white hover:from-cyan-600 hover:to-blue-700 transition text-sm rounded">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Policy Modal -->
<div id="editPolicyModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-slate-800 border border-slate-700/50 max-w-lg w-full p-6 rounded-lg">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-white">Edit SLA Policy</h3>
            <button onclick="closeEditModal()" class="text-slate-400 hover:text-slate-300">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form method="POST" action="sla_management.php" class="space-y-4">
            <input type="hidden" name="action" value="update_policy">
            <input type="hidden" name="policy_id" id="edit_policy_id">

            <div>
                <label class="block text-sm font-medium text-slate-300 mb-2">Priority</label>
                <input type="text" id="edit_priority" readonly 
                       class="w-full px-4 py-2 border border-slate-600 bg-slate-700/50 text-slate-300 font-semibold uppercase">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Response Time (minutes)</label>
                    <input type="number" name="response_time" id="edit_response_time" required min="1"
                           class="w-full px-4 py-2 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 focus:ring-2 focus:ring-cyan-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-300 mb-2">Resolution Time (minutes)</label>
                    <input type="number" name="resolution_time" id="edit_resolution_time" required min="1"
                           class="w-full px-4 py-2 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 focus:ring-2 focus:ring-cyan-500">
                </div>
            </div>

            <div>
                <label class="flex items-center space-x-3">
                    <input type="checkbox" name="is_business_hours" id="edit_is_business_hours" value="1"
                           class="w-4 h-4 text-cyan-500 border-slate-600 focus:ring-cyan-500">
                    <span class="text-sm font-medium text-slate-300">Calculate using business hours only (Mon-Fri, 8AM-5PM)</span>
                </label>
            </div>

            <div>
                <label class="flex items-center space-x-3">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1"
                           class="w-4 h-4 text-cyan-500 border-slate-600 focus:ring-cyan-500">
                    <span class="text-sm font-medium text-slate-300">Policy is active</span>
                </label>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-slate-700/50">
                <button type="button" onclick="closeEditModal()" 
                        class="px-6 py-2 border border-slate-600 text-slate-300 hover:bg-slate-700/50 hover:text-white transition rounded-lg">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-6 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white hover:from-cyan-600 hover:to-blue-700 transition rounded-lg">
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

