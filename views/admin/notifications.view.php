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
<body class="bg-gray-50">
    <?php include __DIR__ . '/../../includes/admin_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen bg-gray-50">
        <!-- Top Bar -->
        <div class="bg-white border-b border-gray-200">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-gray-900 flex items-center justify-center text-white">
                        <i class="fas fa-bell text-sm"></i>
                    </div>
                    <div>
                        <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">Notifications</h1>
                        <p class="text-sm text-gray-500 mt-0.5">Stay updated with your ticket activities</p>
                    </div>
                </div>
                <div class="hidden lg:flex items-center space-x-2">
                    <button id="darkModeToggle" class="p-2 text-gray-500 hover:text-gray-900 transition" title="Toggle dark mode">
                        <i id="dark-mode-icon" class="fas fa-moon text-sm"></i>
                    </button>
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=fff" 
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
                        <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-700">Notifications</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 mb-6">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Notifications List -->
                <div class="lg:col-span-2">
                    <div class="bg-white border border-gray-200">
                        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">All Notifications</h2>
                                <p class="text-sm text-gray-500"><?php echo $stats['unread']; ?> unread of <?php echo $stats['total']; ?> total</p>
                            </div>
                            <?php if ($stats['unread'] > 0): ?>
                            <form method="POST" action="notifications.php">
                                <input type="hidden" name="action" value="mark_all_read">
                                <button type="submit" class="px-4 py-2 text-sm bg-gray-900 text-white hover:bg-gray-800 transition">
                                    <i class="fas fa-check-double mr-2"></i>Mark All Read
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>

                        <div class="divide-y divide-gray-200">
                            <?php if (empty($notifications)): ?>
                                <div class="text-center py-12">
                                    <i class="fas fa-bell-slash text-gray-400 text-4xl mb-3"></i>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No Notifications</h3>
                                    <p class="text-gray-600">You're all caught up! No new notifications.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                <div class="p-6 hover:bg-gray-50 transition <?php echo $notification['is_read'] ? 'opacity-60' : 'bg-blue-50'; ?>">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <?php
                                                $iconMap = [
                                                    'ticket_created' => ['icon' => 'plus-circle', 'color' => 'text-blue-600'],
                                                    'ticket_assigned' => ['icon' => 'user-plus', 'color' => 'text-purple-600'],
                                                    'status_changed' => ['icon' => 'exchange-alt', 'color' => 'text-orange-600'],
                                                    'comment_added' => ['icon' => 'comment', 'color' => 'text-green-600'],
                                                    'ticket_closed' => ['icon' => 'check-circle', 'color' => 'text-gray-600'],
                                                ];
                                                $type = $notification['type'] ?? 'ticket_created';
                                                $iconData = $iconMap[$type] ?? $iconMap['ticket_created'];
                                                ?>
                                                <i class="fas fa-<?php echo $iconData['icon']; ?> <?php echo $iconData['color']; ?>"></i>
                                                <?php if (!$notification['is_read']): ?>
                                                <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($notification['title']); ?></p>
                                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            </div>

                                            <?php if ($notification['ticket_number']): ?>
                                            <a href="view_ticket.php?id=<?php echo $notification['ticket_id']; ?>" 
                                               class="inline-flex items-center text-xs text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-ticket-alt mr-1"></i>
                                                View Ticket #<?php echo htmlspecialchars($notification['ticket_number']); ?>
                                            </a>
                                            <?php endif; ?>

                                            <div class="mt-2 text-xs text-gray-500">
                                                <i class="far fa-clock mr-1"></i>
                                                <?php echo date('M d, Y g:i A', strtotime($notification['created_at'])); ?>
                                            </div>
                                        </div>

                                        <div class="flex items-center space-x-2 ml-4">
                                            <?php if (!$notification['is_read']): ?>
                                            <form method="POST" action="notifications.php" class="inline">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="p-2 text-gray-400 hover:text-gray-600" title="Mark as read">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            
                                            <form method="POST" action="notifications.php" class="inline" onsubmit="return confirm('Delete this notification?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Stats Card -->
                    <div class="bg-white border border-gray-200 p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">
                            <i class="fas fa-chart-pie mr-2"></i>Notification Stats
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Total</span>
                                <span class="text-lg font-bold text-gray-900"><?php echo $stats['total']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Unread</span>
                                <span class="text-lg font-bold text-blue-600"><?php echo $stats['unread']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Read</span>
                                <span class="text-lg font-bold text-green-600"><?php echo $stats['read']; ?></span>
                            </div>
                            <div class="flex justify-between items-center pt-3 border-t border-gray-200">
                                <span class="text-sm font-semibold text-gray-900">Today</span>
                                <span class="text-lg font-bold text-orange-600"><?php echo $stats['today']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Types -->
                    <div class="bg-white border border-gray-200 p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">
                            <i class="fas fa-bell mr-2"></i>Notification Types
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-plus-circle text-blue-600"></i>
                                <span class="text-gray-600">New Tickets</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-user-plus text-purple-600"></i>
                                <span class="text-gray-600">Assignments</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-exchange-alt text-orange-600"></i>
                                <span class="text-gray-600">Status Changes</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-comment text-green-600"></i>
                                <span class="text-gray-600">New Comments</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-check-circle text-gray-600"></i>
                                <span class="text-gray-600">Ticket Closed</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-gray-50 border border-gray-200 p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4">
                            <i class="fas fa-bolt mr-2"></i>Quick Actions
                        </h3>
                        <div class="space-y-2">
                            <a href="tickets.php" class="block px-4 py-2 text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition text-center">
                                <i class="fas fa-ticket-alt mr-2"></i>View All Tickets
                            </a>
                            <a href="dashboard.php" class="block px-4 py-2 text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 transition text-center">
                                <i class="fas fa-tachometer-alt mr-2"></i>Go to Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="bg-blue-50 border border-blue-200 p-6">
                        <h3 class="text-sm font-semibold text-blue-900 mb-3">
                            <i class="fas fa-info-circle mr-2"></i>About Notifications
                        </h3>
                        <ul class="space-y-2 text-xs text-blue-800">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                <span>Real-time updates on tickets</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                <span>Email notifications sent too</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
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
