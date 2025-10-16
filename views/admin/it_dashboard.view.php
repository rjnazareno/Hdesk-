<?php 
// Set page-specific variables
$pageTitle = 'My Dashboard - IT Help Desk';
$baseUrl = '../';

// Include header layout
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gray-50">
    <!-- Minimal Top Bar -->
    <div class="bg-white border-b border-gray-200">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <!-- Left Section: Personal Greeting -->
            <div class="flex items-center space-x-4">
                <div class="hidden lg:flex items-center justify-center w-10 h-10 rounded-full bg-gray-900">
                    <i class="fas fa-user text-white text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">
                        Welcome back, <?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?>
                    </h1>
                    <div class="flex items-center space-x-3 mt-0.5">
                        <p class="text-sm text-gray-500">Your personal performance dashboard</p>
                        <span class="hidden md:inline-flex items-center px-2 py-0.5 text-xs font-medium text-gray-700 border border-gray-300 rounded">
                            IT Staff
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right Section: User Menu -->
            <div class="flex items-center space-x-2">
                <!-- Dark Mode Toggle -->
                <button id="darkModeToggle" class="p-2 text-gray-500 hover:text-gray-900 transition">
                    <i id="dark-mode-icon" class="fas fa-moon text-sm"></i>
                </button>

                <!-- Notifications -->
                <button class="relative p-2 text-gray-500 hover:text-gray-900 transition">
                    <i class="far fa-bell text-sm"></i>
                    <span class="absolute top-1 right-1 w-1.5 h-1.5 bg-red-500 rounded-full"></span>
                </button>

                <!-- User Avatar -->
                <div class="relative" id="userMenuDropdown">
                    <button class="flex items-center space-x-2 p-1 hover:bg-gray-50 rounded transition" id="userMenuBtn">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=fff" 
                             alt="User" 
                             class="w-8 h-8 rounded-full">
                        <div class="hidden lg:block text-left">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?></div>
                            <div class="text-xs text-gray-500">IT Staff</div>
                        </div>
                        <i class="fas fa-chevron-down text-xs text-gray-400 hidden lg:block"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-56 bg-white rounded border border-gray-200 shadow-lg hidden z-50" id="userMenu">
                        <div class="p-3 border-b border-gray-200">
                            <div class="font-medium text-sm text-gray-900"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>
                        <div class="py-1">
                            <a href="profile.php" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">
                                <i class="fas fa-user w-4 text-xs"></i>
                                <span class="ml-2">My Profile</span>
                            </a>
                            <a href="tickets.php" class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 transition">
                                <i class="fas fa-ticket-alt w-4 text-xs"></i>
                                <span class="ml-2">All Tickets</span>
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="../logout.php" class="flex items-center px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition">
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
            <div class="bg-white border border-gray-200 p-5 hover:border-gray-300 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Assigned to Me</p>
                        <h3 class="text-2xl font-semibold text-gray-900"><?php echo $myStats['total_assigned'] ?? 0; ?></h3>
                    </div>
                    <div class="w-10 h-10 flex items-center justify-center bg-gray-100">
                        <i class="fas fa-tasks text-gray-700"></i>
                    </div>
                </div>
            </div>

            <!-- Open Tickets -->
            <div class="bg-white border border-gray-200 p-5 hover:border-gray-300 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Open Tickets</p>
                        <h3 class="text-2xl font-semibold text-gray-900"><?php echo $myStats['open_tickets'] ?? 0; ?></h3>
                    </div>
                    <div class="w-10 h-10 flex items-center justify-center bg-gray-100">
                        <i class="fas fa-folder-open text-gray-700"></i>
                    </div>
                </div>
            </div>

            <!-- In Progress -->
            <div class="bg-white border border-gray-200 p-5 hover:border-gray-300 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">In Progress</p>
                        <h3 class="text-2xl font-semibold text-gray-900"><?php echo $myStats['in_progress'] ?? 0; ?></h3>
                    </div>
                    <div class="w-10 h-10 flex items-center justify-center bg-gray-100">
                        <i class="fas fa-spinner text-gray-700"></i>
                    </div>
                </div>
            </div>

            <!-- Urgent Pending -->
            <div class="bg-white border border-gray-200 p-5 hover:border-gray-300 transition-colors">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Urgent Pending</p>
                        <h3 class="text-2xl font-semibold text-red-600"><?php echo $myStats['urgent_pending'] ?? 0; ?></h3>
                    </div>
                    <div class="w-10 h-10 flex items-center justify-center bg-red-50">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
            <!-- Response Time -->
            <div class="bg-white border border-gray-200 p-6">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 flex items-center justify-center bg-gray-100">
                        <i class="fas fa-clock text-gray-700"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Avg Response Time</h3>
                        <p class="text-xs text-gray-500">First response speed</p>
                    </div>
                </div>
                <div class="text-center py-4">
                    <h2 class="text-4xl font-semibold text-gray-900">
                        <?php echo round($myPerformance['avg_response_time'] ?? 0, 1); ?>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">hours</p>
                </div>
            </div>

            <!-- Resolution Time -->
            <div class="bg-white border border-gray-200 p-6">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 flex items-center justify-center bg-gray-100">
                        <i class="fas fa-check-circle text-gray-700"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Avg Resolution Time</h3>
                        <p class="text-xs text-gray-500">Time to resolve</p>
                    </div>
                </div>
                <div class="text-center py-4">
                    <h2 class="text-4xl font-semibold text-gray-900">
                        <?php echo round($myPerformance['avg_resolution_time'] ?? 0, 1); ?>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">hours</p>
                </div>
            </div>

            <!-- Resolved Tickets -->
            <div class="bg-white border border-gray-200 p-6">
                <div class="flex items-center space-x-3 mb-6">
                    <div class="w-10 h-10 flex items-center justify-center bg-gray-100">
                        <i class="fas fa-trophy text-gray-700"></i>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Tickets Resolved</h3>
                        <p class="text-xs text-gray-500">Your achievements</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="border border-gray-200 p-3">
                        <p class="text-xl font-semibold text-gray-900"><?php echo $myPerformance['resolved_today'] ?? 0; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Today</p>
                    </div>
                    <div class="border border-gray-200 p-3">
                        <p class="text-xl font-semibold text-gray-900"><?php echo $myPerformance['resolved_this_week'] ?? 0; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Week</p>
                    </div>
                    <div class="border border-gray-200 p-3">
                        <p class="text-xl font-semibold text-gray-900"><?php echo $myPerformance['resolved_this_month'] ?? 0; ?></p>
                        <p class="text-xs text-gray-500 mt-1">Month</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Assigned Tickets -->
        <div class="bg-white border border-gray-200 mb-8">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">My Assigned Tickets</h3>
                    <p class="text-sm text-gray-500 mt-0.5">
                        <?php echo count($myTickets); ?> ticket<?php echo count($myTickets) !== 1 ? 's' : ''; ?> assigned to you
                    </p>
                </div>
                <a href="tickets.php" class="hidden md:flex items-center space-x-1 px-3 py-1.5 text-sm text-gray-700 border border-gray-300 hover:border-gray-400 transition">
                    <span>View All</span>
                    <i class="fas fa-arrow-right text-xs"></i>
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                Ticket
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                Category
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                Priority
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                Status
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                Created
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($myTickets)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-2">
                                    <div class="w-12 h-12 flex items-center justify-center bg-gray-100">
                                        <i class="fas fa-clipboard-check text-2xl text-gray-400"></i>
                                    </div>
                                    <p class="text-gray-600 font-medium">No tickets assigned</p>
                                    <p class="text-sm text-gray-500">You're all caught up!</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($myTickets as $ticket): ?>
                            <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="window.location.href='view_ticket.php?id=<?php echo $ticket['id']; ?>'">
                                <td class="px-6 py-4">
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center px-2 py-1 text-xs font-mono font-medium text-gray-900 bg-gray-100">
                                            <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-gray-900 truncate">
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
                                        'low' => ['color' => 'text-green-700', 'bg' => 'bg-green-50', 'border' => 'border-green-200'],
                                        'medium' => ['color' => 'text-yellow-700', 'bg' => 'bg-yellow-50', 'border' => 'border-yellow-200'],
                                        'high' => ['color' => 'text-orange-700', 'bg' => 'bg-orange-50', 'border' => 'border-orange-200'],
                                        'urgent' => ['color' => 'text-red-700', 'bg' => 'bg-red-50', 'border' => 'border-red-200']
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
                                        'pending' => ['color' => 'text-yellow-700', 'bg' => 'bg-yellow-50', 'border' => 'border-yellow-200'],
                                        'open' => ['color' => 'text-blue-700', 'bg' => 'bg-blue-50', 'border' => 'border-blue-200'],
                                        'in_progress' => ['color' => 'text-purple-700', 'bg' => 'bg-purple-50', 'border' => 'border-purple-200'],
                                        'resolved' => ['color' => 'text-green-700', 'bg' => 'bg-green-50', 'border' => 'border-green-200'],
                                        'closed' => ['color' => 'text-gray-700', 'bg' => 'bg-gray-50', 'border' => 'border-gray-200']
                                    ];
                                    $config = $statusConfig[$ticket['status']];
                                    ?>
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium border <?php echo $config['color'] . ' ' . $config['bg'] . ' ' . $config['border']; ?>">
                                        <?php echo str_replace('_', ' ', ucfirst($ticket['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <div class="flex flex-col">
                                        <span class="font-medium">
                                            <?php echo date('M d, Y', strtotime($ticket['created_at'])); ?>
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            <?php echo date('h:i A', strtotime($ticket['created_at'])); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                       class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 border border-gray-300 hover:border-gray-400 transition" 
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
