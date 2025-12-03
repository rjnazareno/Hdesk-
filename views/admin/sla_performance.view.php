<?php
$pageTitle = 'SLA Performance - IT Help Desk';
$baseUrl = '../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../includes/admin_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-white  border-b border-gray-200">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
                <div class="flex items-center space-x-4">
                    <div>
                        <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">SLA Performance</h1>
                        <p class="text-sm text-gray-600 mt-0.5">IT staff performance metrics (Last 30 days)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Overall Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Tickets -->
                <div class="bg-white  border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Total Tickets</p>
                            <p class="text-3xl font-bold text-gray-900"><?php echo $overallStats['total_tickets']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-ticket-alt text-gray-700 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Response SLA -->
                <div class="bg-white  border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Response SLA</p>
                            <p class="text-3xl font-bold text-<?php echo $overallStats['response_percentage'] >= 90 ? 'green' : ($overallStats['response_percentage'] >= 70 ? 'yellow' : 'red'); ?>-400">
                                <?php echo $overallStats['response_percentage']; ?>%
                            </p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $overallStats['response_met']; ?> met / <?php echo $overallStats['response_breached']; ?> breached</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-bolt text-gray-700 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Resolution SLA -->
                <div class="bg-white  border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Resolution SLA</p>
                            <p class="text-3xl font-bold text-<?php echo $overallStats['resolution_percentage'] >= 90 ? 'green' : ($overallStats['resolution_percentage'] >= 70 ? 'yellow' : 'red'); ?>-400">
                                <?php echo $overallStats['resolution_percentage']; ?>%
                            </p>
                            <p class="text-xs text-gray-500 mt-1"><?php echo $overallStats['resolution_met']; ?> met / <?php echo $overallStats['resolution_breached']; ?> breached</p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-check-circle text-gray-700 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Avg Response Time -->
                <div class="bg-white  border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm mb-1">Avg Response</p>
                            <p class="text-2xl font-bold text-gray-900">
                                <?php 
                                if ($overallStats['avg_response_minutes'] !== null && $overallStats['avg_response_minutes'] < 60) {
                                    echo round($overallStats['avg_response_minutes']) . ' min';
                                } elseif ($overallStats['avg_response_minutes'] !== null) {
                                    $hours = floor($overallStats['avg_response_minutes'] / 60);
                                    $mins = round($overallStats['avg_response_minutes'] % 60);
                                    echo $hours . 'h ' . $mins . 'm';
                                } else {
                                    echo '0 min';
                                }
                                ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-gray-100 flex items-center justify-center">
                            <i class="fas fa-clock text-gray-700 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IT Staff Performance Table -->
            <div class="bg-white  border border-gray-200 mb-8">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-users mr-2 text-teal-600"></i>
                        IT Staff SLA Scores
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Individual performance rankings based on SLA compliance</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Rank</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">IT Staff</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase">SLA Score</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Tickets</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Response SLA</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Resolution SLA</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Avg Response</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Avg Resolution</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            <?php 
                            $rank = 1;
                            foreach ($staffPerformance as $staff): 
                                // Determine score color
                                $scoreColor = 'red';
                                if ($staff['sla_score'] >= 90) $scoreColor = 'green';
                                elseif ($staff['sla_score'] >= 70) $scoreColor = 'yellow';
                                elseif ($staff['sla_score'] >= 50) $scoreColor = 'orange';
                                
                                // Determine rank medal
                                $rankDisplay = $rank;
                                if ($rank == 1) $rankDisplay = '<i class="fas fa-trophy text-yellow-400"></i> 1st';
                                elseif ($rank == 2) $rankDisplay = '<i class="fas fa-medal text-gray-600"></i> 2nd';
                                elseif ($rank == 3) $rankDisplay = '<i class="fas fa-medal text-amber-600"></i> 3rd';
                            ?>
                            <tr class="hover:bg-gray-100/30 transition">
                                <td class="px-6 py-4">
                                    <span class="text-gray-900 font-semibold"><?php echo $rankDisplay; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-full flex items-center justify-center text-gray-900 font-bold text-sm">
                                            <?php echo strtoupper(substr($staff['full_name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($staff['full_name']); ?></p>
                                            <p class="text-xs text-gray-600"><?php echo htmlspecialchars($staff['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 bg-<?php echo $scoreColor; ?>-500/20 border-2 border-<?php echo $scoreColor; ?>-500/50 rounded-full">
                                        <span class="text-2xl font-bold text-<?php echo $scoreColor; ?>-400"><?php echo $staff['sla_score']; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-gray-900 font-semibold"><?php echo $staff['total_tickets']; ?></span>
                                    <span class="block text-xs text-gray-600"><?php echo $staff['resolved_tickets']; ?> resolved</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-<?php echo $staff['response_sla_percentage'] >= 90 ? 'green' : ($staff['response_sla_percentage'] >= 70 ? 'yellow' : 'red'); ?>-400 font-semibold">
                                        <?php echo $staff['response_sla_percentage']; ?>%
                                    </span>
                                    <span class="block text-xs text-gray-600"><?php echo $staff['response_sla_met']; ?>/<?php echo $staff['response_sla_met'] + $staff['response_sla_breached']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-<?php echo $staff['resolution_sla_percentage'] >= 90 ? 'green' : ($staff['resolution_sla_percentage'] >= 70 ? 'yellow' : 'red'); ?>-400 font-semibold">
                                        <?php echo $staff['resolution_sla_percentage']; ?>%
                                    </span>
                                    <span class="block text-xs text-gray-600"><?php echo $staff['resolution_sla_met']; ?>/<?php echo $staff['resolution_sla_met'] + $staff['resolution_sla_breached']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-gray-900 text-sm"><?php echo $staff['avg_response_time']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-gray-900 text-sm"><?php echo $staff['avg_resolution_time']; ?></span>
                                </td>
                            </tr>
                            <?php 
                            $rank++;
                            endforeach; 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent SLA Breaches -->
            <?php if (count($recentBreaches) > 0): ?>
            <div class="bg-white  border border-gray-200 shadow-sm">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2 text-red-400"></i>
                        Recent SLA Breaches (Last 7 Days)
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Ticket</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Priority</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Assigned To</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Breach Type</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-600 uppercase">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            <?php foreach ($recentBreaches as $breach): ?>
                            <tr class="hover:bg-gray-100/30 transition">
                                <td class="px-6 py-4">
                                    <a href="view_ticket.php?id=<?php echo $breach['id']; ?>" class="text-teal-600 hover:text-teal-600 font-medium">
                                        <?php echo $breach['ticket_number']; ?>
                                    </a>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($breach['title'], 0, 50)); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold
                                        <?php 
                                        echo match($breach['priority']) {
                                            'urgent' => 'bg-red-500/20 text-red-400 border border-red-500/30',
                                            'high' => 'bg-orange-500/20 text-orange-400 border border-orange-500/30',
                                            'medium' => 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30',
                                            'low' => 'bg-green-500/20 text-green-400 border border-green-500/30',
                                            default => 'bg-gray-100 text-gray-600 border border-gray-300'
                                        };
                                        ?>">
                                        <?php echo ucfirst($breach['priority']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-900 text-sm"><?php echo $breach['assigned_to_name'] ?: 'Unassigned'; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($breach['response_sla_status'] == 'breached'): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-red-500/20 text-red-400 border border-red-500/30 mr-1">
                                        <i class="fas fa-bolt mr-1"></i> Response
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($breach['resolution_sla_status'] === 'breached'): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-red-500/20 text-red-400 border border-red-500/30">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Resolution
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-gray-600 text-sm"><?php echo date('M d, Y H:i', strtotime($breach['created_at'])); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Score Legend -->
            <div class="mt-8 bg-white  border border-gray-200 p-6">
                <h3 class="text-sm font-semibold text-gray-900 mb-4">
                    <i class="fas fa-info-circle mr-2 text-teal-600"></i>
                    SLA Score Calculation
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                    <div>
                        <p class="font-semibold mb-2">Score Components:</p>
                        <ul class="space-y-1 text-gray-600">
                            <li>• Response SLA: 0-50 points</li>
                            <li>• Resolution SLA: 0-50 points</li>
                            <li>• <strong>Total Score: 0-100</strong></li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold mb-2">Performance Ratings:</p>
                        <ul class="space-y-1">
                            <li><span class="text-green-400">●</span> Excellent: 90-100</li>
                            <li><span class="text-yellow-400">●</span> Good: 70-89</li>
                            <li><span class="text-orange-400">●</span> Fair: 50-69</li>
                            <li><span class="text-red-400">●</span> Needs Improvement: 0-49</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/helpers.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initTooltips();
            initDarkMode();
        });
    </script>
</body>
</html>
