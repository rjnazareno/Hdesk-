<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/print.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <?php include __DIR__ . '/../../includes/admin_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
        <!-- Top Bar -->
        <div class="bg-slate-800/50 border-b border-slate-700/50 backdrop-blur-md">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white rounded-lg">
                        <i class="fas fa-bell text-sm"></i>
                    </div>
                    <div>
                        <h1 class="text-xl lg:text-2xl font-semibold text-white">Notifications</h1>
                        <p class="text-sm text-slate-400 mt-0.5">Stay updated with your ticket activities</p>
                    </div>
                </div>
                <div class="hidden lg:flex items-center space-x-2">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=06b6d4" 
                         alt="User" 
                         class="w-8 h-8 rounded-full"
                         title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-slate-400 hover:text-white">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-slate-300">Notifications</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-500/20 border border-green-500/30 text-green-400 px-4 py-3 rounded-lg mb-6 backdrop-blur-sm">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Notifications List -->
                <div class="lg:col-span-2">
                    <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg shadow-xl">
                        <div class="p-6 border-b border-slate-700/50 flex justify-between items-center">
                            <div>
                                <h2 class="text-lg font-semibold text-white">All Notifications</h2>
                                <p class="text-sm text-slate-400"><?php echo $stats['unread']; ?> unread of <?php echo $stats['total']; ?> total</p>
                            </div>
                            <?php if ($stats['unread'] > 0): ?>
                            <form method="POST" action="notifications.php">
                                <input type="hidden" name="action" value="mark_all_read">
                                <button type="submit" class="px-4 py-2 text-sm bg-gradient-to-r from-cyan-500 to-blue-600 text-white hover:from-cyan-600 hover:to-blue-700 transition rounded-lg shadow-md">
                                    <i class="fas fa-check-double mr-2"></i>Mark All Read
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>

                        <div class="divide-y divide-slate-700/50">
                            <?php if (empty($notifications)): ?>
                                <div class="text-center py-12">
                                    <div class="w-20 h-20 bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-bell-slash text-slate-400 text-4xl"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-white mb-2">No Notifications</h3>
                                    <p class="text-slate-400">You're all caught up! No new notifications.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                <div class="p-6 hover:bg-slate-700/30 transition <?php echo $notification['is_read'] ? 'opacity-60' : 'bg-cyan-500/5'; ?>">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <?php
                                                $iconMap = [
                                                    'ticket_created' => ['icon' => 'plus-circle', 'color' => 'text-cyan-400'],
                                                    'ticket_assigned' => ['icon' => 'user-plus', 'color' => 'text-purple-400'],
                                                    'status_changed' => ['icon' => 'exchange-alt', 'color' => 'text-orange-400'],
                                                    'comment_added' => ['icon' => 'comment', 'color' => 'text-green-400'],
                                                    'ticket_closed' => ['icon' => 'check-circle', 'color' => 'text-slate-400'],
                                                ];
                                                $type = $notification['type'] ?? 'ticket_created';
                                                $iconData = $iconMap[$type] ?? $iconMap['ticket_created'];
                                                ?>
                                                <i class="fas fa-<?php echo $iconData['icon']; ?> <?php echo $iconData['color']; ?>"></i>
                                                <?php if (!$notification['is_read']): ?>
                                                <span class="w-2 h-2 bg-cyan-400 rounded-full animate-pulse"></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <p class="text-sm text-white font-medium"><?php echo htmlspecialchars($notification['title']); ?></p>
                                                <p class="text-sm text-slate-400 mt-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            </div>

                                            <?php if ($notification['ticket_number']): ?>
                                            <a href="view_ticket.php?id=<?php echo $notification['ticket_id']; ?>" 
                                               class="inline-flex items-center text-xs text-cyan-400 hover:text-cyan-300 transition">
                                                <i class="fas fa-ticket-alt mr-1"></i>
                                                View Ticket #<?php echo htmlspecialchars($notification['ticket_number']); ?>
                                            </a>
                                            <?php endif; ?>

                                            <div class="mt-2 text-xs text-slate-400">
                                                <i class="far fa-clock mr-1"></i>
                                                <?php echo date('M d, Y g:i A', strtotime($notification['created_at'])); ?>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-2 ml-4">
                                            <?php if (!$notification['is_read']): ?>
                                            <form method="POST" action="notifications.php" class="inline">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="p-2 text-slate-400 hover:text-cyan-400 transition rounded-lg hover:bg-slate-700/50" title="Mark as read">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" action="notifications.php" class="inline" onsubmit="return confirm('Delete this notification?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="p-2 text-slate-400 hover:text-red-400 transition rounded-lg hover:bg-slate-700/50" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if (isset($pagination)): ?>
                        <div class="px-6 py-4 border-t border-slate-700/50">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-slate-400">
                                    <?php if ($pagination['totalPages'] > 1): ?>
                                    Page <?php echo $pagination['currentPage']; ?> of <?php echo $pagination['totalPages']; ?>
                                    <span class="mx-2">•</span>
                                    <?php endif; ?>
                                    Showing <?php echo count($notifications); ?> of <?php echo $pagination['totalItems']; ?> notifications
                                </div>
                                <?php if ($pagination['totalPages'] > 1): ?>
                                <div class="flex items-center space-x-2">
                                    <?php if ($pagination['hasPrevPage']): ?>
                                    <a href="?page=<?php echo $pagination['currentPage'] - 1; ?>" 
                                       class="px-3 py-1.5 text-sm border border-slate-600 bg-slate-700/50 text-slate-300 hover:bg-slate-600/50 hover:text-cyan-400 rounded-lg transition">
                                        <i class="fas fa-chevron-left mr-1"></i> Prev
                                    </a>
                                    <?php else: ?>
                                    <span class="px-3 py-1.5 text-sm border border-slate-700 bg-slate-800/50 text-slate-600 rounded-lg cursor-not-allowed">
                                        <i class="fas fa-chevron-left mr-1"></i> Prev
                                    </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($pagination['hasNextPage']): ?>
                                    <a href="?page=<?php echo $pagination['currentPage'] + 1; ?>" 
                                       class="px-3 py-1.5 text-sm border border-slate-600 bg-slate-700/50 text-slate-300 hover:bg-slate-600/50 hover:text-cyan-400 rounded-lg transition">
                                        Next <i class="fas fa-chevron-right ml-1"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="px-3 py-1.5 text-sm border border-slate-700 bg-slate-800/50 text-slate-600 rounded-lg cursor-not-allowed">
                                        Next <i class="fas fa-chevron-right ml-1"></i>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Stats Card -->
                    <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg shadow-xl p-6">
                        <h3 class="text-sm font-semibold text-white mb-4">
                            <i class="fas fa-chart-pie mr-2 text-cyan-400"></i>Notification Stats
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-3 bg-slate-700/30 rounded-lg">
                                <span class="text-sm text-slate-400">Total</span>
                                <span class="text-lg font-bold text-white"><?php echo $stats['total']; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-cyan-500/10 rounded-lg border border-cyan-500/20">
                                <span class="text-sm text-slate-400">Unread</span>
                                <span class="text-lg font-bold text-cyan-400"><?php echo $stats['unread']; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-green-500/10 rounded-lg border border-green-500/20">
                                <span class="text-sm text-slate-400">Read</span>
                                <span class="text-lg font-bold text-green-400"><?php echo $stats['read']; ?></span>
                            </div>
                            <div class="flex justify-between items-center p-3 bg-orange-500/10 rounded-lg border border-orange-500/20 mt-3">
                                <span class="text-sm font-semibold text-white">Today</span>
                                <span class="text-lg font-bold text-orange-400"><?php echo $stats['today']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Types -->
                    <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg shadow-xl p-6">
                        <h3 class="text-sm font-semibold text-white mb-4">
                            <i class="fas fa-bell mr-2 text-cyan-400"></i>Notification Types
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center space-x-3 p-2 hover:bg-slate-700/30 rounded-lg transition">
                                <i class="fas fa-plus-circle text-cyan-400"></i>
                                <span class="text-slate-300">New Tickets</span>
                            </div>
                            <div class="flex items-center space-x-3 p-2 hover:bg-slate-700/30 rounded-lg transition">
                                <i class="fas fa-user-plus text-purple-400"></i>
                                <span class="text-slate-300">Assignments</span>
                            </div>
                            <div class="flex items-center space-x-3 p-2 hover:bg-slate-700/30 rounded-lg transition">
                                <i class="fas fa-exchange-alt text-orange-400"></i>
                                <span class="text-slate-300">Status Changes</span>
                            </div>
                            <div class="flex items-center space-x-3 p-2 hover:bg-slate-700/30 rounded-lg transition">
                                <i class="fas fa-comment text-green-400"></i>
                                <span class="text-slate-300">New Comments</span>
                            </div>
                            <div class="flex items-center space-x-3 p-2 hover:bg-slate-700/30 rounded-lg transition">
                                <i class="fas fa-check-circle text-slate-400"></i>
                                <span class="text-slate-300">Ticket Closed</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg shadow-xl p-6">
                        <h3 class="text-sm font-semibold text-white mb-4">
                            <i class="fas fa-bolt mr-2 text-cyan-400"></i>Quick Actions
                        </h3>
                        <div class="space-y-2">
                            <a href="tickets.php" class="block px-4 py-2.5 text-sm bg-slate-700/50 border border-slate-600/50 text-slate-300 hover:bg-slate-600/50 hover:text-cyan-400 hover:border-cyan-500/50 transition rounded-lg text-center">
                                <i class="fas fa-ticket-alt mr-2"></i>View All Tickets
                            </a>
                            <a href="dashboard.php" class="block px-4 py-2.5 text-sm bg-slate-700/50 border border-slate-600/50 text-slate-300 hover:bg-slate-600/50 hover:text-cyan-400 hover:border-cyan-500/50 transition rounded-lg text-center">
                                <i class="fas fa-tachometer-alt mr-2"></i>Go to Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="bg-cyan-500/10 backdrop-blur-sm border border-cyan-500/30 rounded-lg shadow-xl p-6">
                        <h3 class="text-sm font-semibold text-cyan-400 mb-3">
                            <i class="fas fa-info-circle mr-2"></i>About Notifications
                        </h3>
                        <ul class="space-y-2 text-xs text-slate-300">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0 text-cyan-400"></i>
                                <span>Real-time updates on tickets</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0 text-cyan-400"></i>
                                <span>Email notifications sent too</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0 text-cyan-400"></i>
                                <span>Kept for 30 days</span>
                            </li>
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

