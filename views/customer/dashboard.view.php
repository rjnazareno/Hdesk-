<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - <?php echo defined('APP_NAME') ? APP_NAME : 'ServiceDesk'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            100% { transform: scale(1.3); opacity: 0; }
        }
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .float-animation { animation: float 3s ease-in-out infinite; }
        .float-delay-1 { animation-delay: 0.5s; }
        .float-delay-2 { animation-delay: 1s; }
        .gradient-animate {
            background-size: 200% 200%;
            animation: gradient-shift 8s ease infinite;
        }
        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .stat-card:hover {
            transform: translateY(-4px);
        }
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }
        .stat-icon {
            transition: all 0.3s ease;
        }
        .ticket-row {
            transition: all 0.2s ease;
        }
        .ticket-row:hover {
            background: linear-gradient(90deg, rgba(16, 185, 129, 0.05) 0%, transparent 100%);
        }
        .quick-action:hover .action-icon {
            transform: translateX(4px);
        }
        .dropdown-menu {
            display: none;
            position: absolute;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 50;
        }
        .dropdown-toggle.active ~ .dropdown-menu {
            display: block;
        }
    </style>
</head>
<body class="bg-slate-50">
    <?php $basePath = ''; include __DIR__ . '/../../includes/customer_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <?php 
        // Set page variables for header
        $pageTitle = 'Employee Dashboard';
        $pageSubtitle = date('l, F j, Y');
        $showSearch = false;
        $basePath = ''; 
        include __DIR__ . '/../../includes/customer_header.php'; 
        ?>

        <!-- Dashboard Content -->
        <div class="p-4 sm:p-6 lg:p-8">
            
            <!-- Hero Section - Compact -->
            <div class="relative bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-600 gradient-animate rounded-xl p-5 sm:p-6 mb-6 overflow-hidden">
                <!-- Animated Background Elements -->
                <div class="absolute inset-0 overflow-hidden">
                    <div class="absolute -top-4 -right-4 w-20 h-20 bg-white/10 rounded-full blur-2xl float-animation"></div>
                    <div class="absolute top-1/2 right-1/4 w-12 h-12 bg-teal-400/20 rounded-full blur-xl float-animation float-delay-1"></div>
                    <div class="absolute -bottom-6 left-1/4 w-24 h-24 bg-emerald-400/10 rounded-full blur-2xl float-animation float-delay-2"></div>
                    <!-- Grid Pattern -->
                    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 24px 24px;"></div>
                </div>
                
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="px-2 py-0.5 bg-white/20 backdrop-blur-sm rounded-full text-emerald-50 text-xs font-medium">
                            <?php 
                            $hour = date('G');
                            if ($hour < 12) echo '🌅 Good Morning';
                            elseif ($hour < 17) echo '☀️ Good Afternoon';
                            else echo '🌙 Good Evening';
                            ?>
                        </span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold text-white mb-2">
                        Hey, <?= htmlspecialchars(explode(' ', $currentUser['full_name'])[0]) ?>! 👋
                    </h1>
                    <p class="text-white/90 text-base sm:text-lg max-w-lg">
                        <?php if ($stats['pending'] > 0): ?>
                            You have <span class="font-semibold text-white"><?= $stats['pending'] ?> pending request<?= $stats['pending'] > 1 ? 's' : '' ?></span> awaiting response.
                        <?php elseif ($stats['open'] > 0): ?>
                            Your support requests are being processed. We're on it!
                        <?php else: ?>
                            Ready to get help? Submit a request and we'll assist you right away.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <!-- Total Tickets -->
                <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200/50 shadow-sm hover:shadow-xl hover:shadow-slate-200/50">
                    <div class="flex items-start justify-between mb-4">
                        <div class="stat-icon w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                            <i class="fas fa-layer-group text-white text-lg"></i>
                        </div>
                        <span class="text-xs font-medium text-slate-400 bg-slate-100 px-2 py-1 rounded-lg">All Time</span>
                    </div>
                    <h3 class="text-3xl font-bold text-slate-800 mb-1"><?= $stats['total'] ?></h3>
                    <p class="text-sm text-slate-500">Total Requests</p>
                </div>

                <!-- Open Tickets -->
                <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200/50 shadow-sm hover:shadow-xl hover:shadow-slate-200/50">
                    <div class="flex items-start justify-between mb-4">
                        <div class="stat-icon w-12 h-12 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                            <i class="fas fa-folder-open text-white text-lg"></i>
                        </div>
                        <span class="text-xs font-medium text-emerald-600 bg-emerald-50 px-2 py-1 rounded-lg">Active</span>
                    </div>
                    <h3 class="text-3xl font-bold text-slate-800 mb-1"><?= $stats['open'] ?></h3>
                    <p class="text-sm text-slate-500">Open Tickets</p>
                </div>

                <!-- Pending Tickets -->
                <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200/50 shadow-sm hover:shadow-xl hover:shadow-slate-200/50">
                    <div class="flex items-start justify-between mb-4">
                        <div class="stat-icon w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center shadow-lg shadow-amber-500/30">
                            <i class="fas fa-hourglass-half text-white text-lg"></i>
                        </div>
                        <?php if ($stats['pending'] > 0): ?>
                        <span class="text-xs font-medium text-amber-600 bg-amber-50 px-2 py-1 rounded-lg flex items-center gap-1">
                            <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-pulse"></span>
                            Waiting
                        </span>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-3xl font-bold text-slate-800 mb-1"><?= $stats['pending'] ?></h3>
                    <p class="text-sm text-slate-500">Pending</p>
                </div>

                <!-- Resolved Tickets -->
                <div class="stat-card bg-white rounded-2xl p-5 border border-slate-200/50 shadow-sm hover:shadow-xl hover:shadow-slate-200/50">
                    <div class="flex items-start justify-between mb-4">
                        <div class="stat-icon w-12 h-12 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg shadow-violet-500/30">
                            <i class="fas fa-check-double text-white text-lg"></i>
                        </div>
                        <span class="text-xs font-medium text-violet-600 bg-violet-50 px-2 py-1 rounded-lg">Done</span>
                    </div>
                    <h3 class="text-3xl font-bold text-slate-800 mb-1"><?= $stats['closed'] ?></h3>
                    <p class="text-sm text-slate-500">Resolved</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Recent Tickets -->
                <div class="lg:col-span-3 bg-white rounded-2xl border border-slate-200/50 shadow-sm overflow-hidden">
                    <div class="p-6 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-800">My Recent Tickets</h3>
                            <p class="text-sm text-slate-500 mt-0.5">View and manage your support tickets</p>
                        </div>
                    </div>
                    
                    <?php if (empty($recentTickets)): ?>
                        <div class="p-12 text-center">
                            <div class="relative inline-block mb-6">
                                <div class="w-24 h-24 bg-gradient-to-br from-slate-100 to-slate-200 rounded-3xl flex items-center justify-center">
                                    <i class="fas fa-ticket-alt text-slate-400 text-3xl"></i>
                                </div>
                                <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                    <i class="fas fa-plus text-white"></i>
                                </div>
                            </div>
                            <h4 class="text-lg font-semibold text-slate-800 mb-2">No tickets yet</h4>
                            <p class="text-slate-500 text-sm mb-6 max-w-sm mx-auto">
                                Having an IT issue? Create your first ticket and our team will assist you right away.
                            </p>
                            <a href="create_ticket.php" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-xl font-semibold text-sm hover:shadow-lg hover:shadow-emerald-500/30 transition-all">
                                <i class="fas fa-plus-circle"></i>
                                Create Your First Ticket
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Table Header -->
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Ticket #</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Priority</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Created</th>
                                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($recentTickets as $ticket): ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="text-sm font-medium text-emerald-600 hover:text-emerald-800">
                                                <?= htmlspecialchars($ticket['ticket_number']) ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-slate-800"><?= htmlspecialchars($ticket['title']) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm text-slate-600"><?= htmlspecialchars($ticket['category_name']) ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $priorityColors = [
                                                'low' => 'bg-slate-100 text-slate-700',
                                                'medium' => 'bg-yellow-100 text-yellow-700',
                                                'high' => 'bg-red-100 text-red-700'
                                            ];
                                            $priorityColor = $priorityColors[$ticket['priority']] ?? 'bg-slate-100 text-slate-700';
                                            ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium <?= $priorityColor ?>">
                                                <?= ucfirst($ticket['priority']) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-700',
                                                'open' => 'bg-emerald-100 text-emerald-700',
                                                'in_progress' => 'bg-purple-100 text-purple-700',
                                                'resolved' => 'bg-emerald-100 text-emerald-700',
                                                'closed' => 'bg-slate-100 text-slate-600'
                                            ];
                                            $statusColor = $statusColors[$ticket['status']] ?? 'bg-slate-100 text-slate-600';
                                            ?>
                                            <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-medium <?= $statusColor ?>">
                                                <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                            <?php
                                            $createdDate = new DateTime($ticket['created_at']);
                                            $now = new DateTime();
                                            $diff = $now->diff($createdDate);
                                            
                                            if ($diff->days == 0) {
                                                echo 'Today';
                                            } elseif ($diff->days < 30) {
                                                echo $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
                                            } else {
                                                $months = floor($diff->days / 30);
                                                echo $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
                                            }
                                            ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="view_ticket.php?id=<?= $ticket['id'] ?>" class="text-emerald-600 hover:text-emerald-800">
                                                <i class="fas fa-eye"></i>
                                            </a>
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
                                   class="px-4 py-2 text-sm bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-lg hover:from-emerald-600 hover:to-teal-600 transition">
                                    Next
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Footer with count and view all link -->
                        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                            <p class="text-sm text-slate-600">Showing <?= count($recentTickets) ?> most recent tickets</p>
                            <a href="tickets.php" class="inline-flex items-center gap-1 text-sm font-medium text-slate-700 hover:text-emerald-600 transition">
                                <i class="fas fa-list"></i>
                                View All My Tickets
                                <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions & Info -->
                <div class="space-y-6">
                    <!-- Quick Actions -->
                    <div class="bg-white rounded-2xl border border-slate-200/50 shadow-sm p-5">
                        <h3 class="text-lg font-semibold text-slate-800 mb-4">Quick Actions</h3>
                        <div class="space-y-2">
                            <a href="create_ticket.php" class="quick-action flex items-center gap-3 p-3 rounded-xl hover:bg-emerald-50 transition group">
                                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center text-emerald-600 group-hover:bg-emerald-500 group-hover:text-white transition">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <span class="flex-1 font-medium text-slate-700">New Ticket</span>
                                <i class="fas fa-chevron-right text-slate-400 action-icon"></i>
                            </a>
                            <a href="tickets.php" class="quick-action flex items-center gap-3 p-3 rounded-xl hover:bg-blue-50 transition group">
                                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 group-hover:bg-blue-500 group-hover:text-white transition">
                                    <i class="fas fa-list"></i>
                                </div>
                                <span class="flex-1 font-medium text-slate-700">All Tickets</span>
                                <i class="fas fa-chevron-right text-slate-400 action-icon"></i>
                            </a>
                            <a href="notifications.php" class="quick-action flex items-center gap-3 p-3 rounded-xl hover:bg-amber-50 transition group">
                                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center text-amber-600 group-hover:bg-amber-500 group-hover:text-white transition">
                                    <i class="fas fa-bell"></i>
                                </div>
                                <span class="flex-1 font-medium text-slate-700">Notifications</span>
                                <?php if (isset($unreadNotifications) && $unreadNotifications > 0): ?>
                                <span class="px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full"><?= $unreadNotifications ?></span>
                                <?php else: ?>
                                <i class="fas fa-chevron-right text-slate-400 action-icon"></i>
                                <?php endif; ?>
                            </a>
                            <a href="profile.php" class="quick-action flex items-center gap-3 p-3 rounded-xl hover:bg-violet-50 transition group">
                                <div class="w-10 h-10 bg-violet-100 rounded-xl flex items-center justify-center text-violet-600 group-hover:bg-violet-500 group-hover:text-white transition">
                                    <i class="fas fa-user-cog"></i>
                                </div>
                                <span class="flex-1 font-medium text-slate-700">My Profile</span>
                                <i class="fas fa-chevron-right text-slate-400 action-icon"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Tips Card -->
                    <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-5">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-emerald-500 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-lightbulb text-white text-sm"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-emerald-800 mb-1">Pro Tip</h4>
                                <p class="text-sm text-emerald-700">Include screenshots or error messages when creating tickets for faster resolution.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/helpers.js"></script>
    <script>
        function toggleUserDropdown() {
            const btn = document.getElementById('userDropdownBtn');
            btn.classList.toggle('active');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userBtn = document.getElementById('userDropdownBtn');
            const dropdownMenu = userBtn.nextElementSibling;
            if (!event.target.closest('.relative')) {
                userBtn.classList.remove('active');
            }
        });
    </script>
</body>
</html>
