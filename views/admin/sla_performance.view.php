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
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <?php include __DIR__ . '/../../includes/admin_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-slate-800/50 backdrop-blur-md border-b border-slate-700/50">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white rounded-lg">
                        <i class="fas fa-chart-line text-sm"></i>
                    </div>
                    <div>
                        <h1 class="text-xl lg:text-2xl font-semibold text-white">SLA Performance</h1>
                        <p class="text-sm text-slate-400 mt-0.5">IT staff performance metrics (Last 30 days)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Overall Stats -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Tickets -->
                <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg shadow-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm mb-1">Total Tickets</p>
                            <p class="text-3xl font-bold text-white"><?php echo $overallStats['total_tickets']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-cyan-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-ticket-alt text-cyan-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Response SLA -->
                <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg shadow-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm mb-1">Response SLA</p>
                            <p class="text-3xl font-bold text-<?php echo $overallStats['response_percentage'] >= 90 ? 'green' : ($overallStats['response_percentage'] >= 70 ? 'yellow' : 'red'); ?>-400">
                                <?php echo $overallStats['response_percentage']; ?>%
                            </p>
                            <p class="text-xs text-slate-500 mt-1"><?php echo $overallStats['response_met']; ?> met / <?php echo $overallStats['response_breached']; ?> breached</p>
                        </div>
                        <div class="w-12 h-12 bg-green-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-bolt text-green-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Resolution SLA -->
                <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg shadow-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm mb-1">Resolution SLA</p>
                            <p class="text-3xl font-bold text-<?php echo $overallStats['resolution_percentage'] >= 90 ? 'green' : ($overallStats['resolution_percentage'] >= 70 ? 'yellow' : 'red'); ?>-400">
                                <?php echo $overallStats['resolution_percentage']; ?>%
                            </p>
                            <p class="text-xs text-slate-500 mt-1"><?php echo $overallStats['resolution_met']; ?> met / <?php echo $overallStats['resolution_breached']; ?> breached</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-check-circle text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Avg Response Time -->
                <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg shadow-xl p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-slate-400 text-sm mb-1">Avg Response</p>
                            <p class="text-2xl font-bold text-white">
                                <?php 
                                if ($overallStats['avg_response_minutes'] < 60) {
                                    echo round($overallStats['avg_response_minutes']) . ' min';
                                } else {
                                    $hours = floor($overallStats['avg_response_minutes'] / 60);
                                    $mins = round($overallStats['avg_response_minutes'] % 60);
                                    echo $hours . 'h ' . $mins . 'm';
                                }
                                ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-purple-500/20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-clock text-purple-400 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- IT Staff Performance Table -->
            <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg shadow-xl mb-8">
                <div class="p-6 border-b border-slate-700/50">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-users mr-2 text-cyan-400"></i>
                        IT Staff SLA Scores
                    </h2>
                    <p class="text-sm text-slate-400 mt-1">Individual performance rankings based on SLA compliance</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-700/50">
                                <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Rank</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase">IT Staff</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-slate-400 uppercase">SLA Score</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Tickets</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Response SLA</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Resolution SLA</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Avg Response</th>
                                <th class="text-center px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Avg Resolution</th>
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
                                elseif ($rank == 2) $rankDisplay = '<i class="fas fa-medal text-slate-400"></i> 2nd';
                                elseif ($rank == 3) $rankDisplay = '<i class="fas fa-medal text-amber-600"></i> 3rd';
                            ?>
                            <tr class="hover:bg-slate-700/30 transition">
                                <td class="px-6 py-4">
                                    <span class="text-white font-semibold"><?php echo $rankDisplay; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                                            <?php echo strtoupper(substr($staff['full_name'], 0, 2)); ?>
                                        </div>
                                        <div>
                                            <p class="text-white font-medium"><?php echo htmlspecialchars($staff['full_name']); ?></p>
                                            <p class="text-xs text-slate-400"><?php echo htmlspecialchars($staff['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center justify-center w-16 h-16 bg-<?php echo $scoreColor; ?>-500/20 border-2 border-<?php echo $scoreColor; ?>-500/50 rounded-full">
                                        <span class="text-2xl font-bold text-<?php echo $scoreColor; ?>-400"><?php echo $staff['sla_score']; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-white font-semibold"><?php echo $staff['total_tickets']; ?></span>
                                    <span class="block text-xs text-slate-400"><?php echo $staff['resolved_tickets']; ?> resolved</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-<?php echo $staff['response_sla_percentage'] >= 90 ? 'green' : ($staff['response_sla_percentage'] >= 70 ? 'yellow' : 'red'); ?>-400 font-semibold">
                                        <?php echo $staff['response_sla_percentage']; ?>%
                                    </span>
                                    <span class="block text-xs text-slate-400"><?php echo $staff['response_sla_met']; ?>/<?php echo $staff['response_sla_met'] + $staff['response_sla_breached']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-<?php echo $staff['resolution_sla_percentage'] >= 90 ? 'green' : ($staff['resolution_sla_percentage'] >= 70 ? 'yellow' : 'red'); ?>-400 font-semibold">
                                        <?php echo $staff['resolution_sla_percentage']; ?>%
                                    </span>
                                    <span class="block text-xs text-slate-400"><?php echo $staff['resolution_sla_met']; ?>/<?php echo $staff['resolution_sla_met'] + $staff['resolution_sla_breached']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-white text-sm"><?php echo $staff['avg_response_time']; ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-white text-sm"><?php echo $staff['avg_resolution_time']; ?></span>
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
            <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg shadow-xl">
                <div class="p-6 border-b border-slate-700/50">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-exclamation-triangle mr-2 text-red-400"></i>
                        Recent SLA Breaches (Last 7 Days)
                    </h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-slate-700/50">
                                <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Ticket</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Priority</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Assigned To</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Breach Type</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-slate-400 uppercase">Created</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            <?php foreach ($recentBreaches as $breach): ?>
                            <tr class="hover:bg-slate-700/30 transition">
                                <td class="px-6 py-4">
                                    <a href="view_ticket.php?id=<?php echo $breach['id']; ?>" class="text-cyan-400 hover:text-cyan-300 font-medium">
                                        <?php echo $breach['ticket_number']; ?>
                                    </a>
                                    <p class="text-sm text-slate-400 mt-1"><?php echo htmlspecialchars(substr($breach['title'], 0, 50)); ?></p>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-lg
                                        <?php 
                                        echo match($breach['priority']) {
                                            'urgent' => 'bg-red-500/20 text-red-400 border border-red-500/30',
                                            'high' => 'bg-orange-500/20 text-orange-400 border border-orange-500/30',
                                            'medium' => 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30',
                                            'low' => 'bg-green-500/20 text-green-400 border border-green-500/30',
                                            default => 'bg-slate-500/20 text-slate-400'
                                        };
                                        ?>">
                                        <?php echo ucfirst($breach['priority']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-white text-sm"><?php echo $breach['assigned_to_name'] ?: 'Unassigned'; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ($breach['first_response_sla_met'] == 0): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-red-500/20 text-red-400 border border-red-500/30 rounded-lg mr-1">
                                        <i class="fas fa-bolt mr-1"></i> Response
                                    </span>
                                    <?php endif; ?>
                                    <?php if ($breach['resolution_sla_met'] == 0 && $breach['resolved_at']): ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs bg-red-500/20 text-red-400 border border-red-500/30 rounded-lg">
                                        <i class="fas fa-clock mr-1"></i> Resolution
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-slate-400 text-sm"><?php echo date('M d, Y H:i', strtotime($breach['created_at'])); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Score Legend -->
            <div class="mt-8 bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg shadow-xl p-6">
                <h3 class="text-sm font-semibold text-white mb-4">
                    <i class="fas fa-info-circle mr-2 text-cyan-400"></i>
                    SLA Score Calculation
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-slate-300">
                    <div>
                        <p class="font-semibold mb-2">Score Components:</p>
                        <ul class="space-y-1 text-slate-400">
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
