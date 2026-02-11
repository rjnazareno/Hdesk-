<?php 
/**
 * Customer Notifications View
 * Notifications page with emerald green theme
 */

$pageTitle = 'Notifications - ' . (defined('APP_NAME') ? APP_NAME : 'ServiceHub');
$basePath = '../';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Customer Navigation -->
    <?php include __DIR__ . '/../../includes/customer_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-white border-b border-gray-100 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500 flex items-center justify-center">
                            <i class="fas fa-bell text-white"></i>
                        </div>
                        <div>
                            <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">Notifications</h1>
                            <p class="text-sm text-gray-500">Your latest ticket updates</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Success Message -->
            <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl flex items-center">
                <i class="fas fa-check-circle mr-3"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Notifications List -->
                <div class="lg:col-span-2">
                    <div class="bg-white border border-gray-100 rounded-xl overflow-hidden">
                        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">All Notifications</h2>
                                <p class="text-sm text-gray-500"><?php echo $stats['unread']; ?> unread of <?php echo $stats['total']; ?> total</p>
                            </div>
                            <?php if ($stats['unread'] > 0): ?>
                            <form method="POST" action="notifications.php">
                                <input type="hidden" name="action" value="mark_all_read">
                                <button type="submit" class="px-4 py-2 text-sm bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 transition">
                                    <i class="fas fa-check-double mr-2"></i>Mark All Read
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>

                        <div class="divide-y divide-gray-100">
                            <?php if (empty($notifications)): ?>
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fas fa-bell-slash text-gray-400 text-2xl"></i>
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No Notifications</h3>
                                    <p class="text-gray-500">You're all caught up! No new notifications.</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $notification): ?>
                                <div class="p-5 hover:bg-gray-50 transition <?php echo $notification['is_read'] ? '' : 'bg-emerald-50/50'; ?>">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center space-x-2 mb-2">
                                                <?php
                                                $iconMap = [
                                                    'ticket_created' => ['icon' => 'plus-circle', 'bg' => 'bg-blue-100', 'color' => 'text-blue-600'],
                                                    'status_changed' => ['icon' => 'exchange-alt', 'bg' => 'bg-orange-100', 'color' => 'text-orange-600'],
                                                    'comment_added' => ['icon' => 'comment', 'bg' => 'bg-emerald-100', 'color' => 'text-emerald-600'],
                                                    'ticket_closed' => ['icon' => 'check-circle', 'bg' => 'bg-gray-100', 'color' => 'text-gray-600'],
                                                ];
                                                $type = $notification['type'] ?? 'ticket_created';
                                                $iconData = $iconMap[$type] ?? $iconMap['ticket_created'];
                                                ?>
                                                <div class="w-8 h-8 rounded-lg <?php echo $iconData['bg']; ?> flex items-center justify-center">
                                                    <i class="fas fa-<?php echo $iconData['icon']; ?> <?php echo $iconData['color']; ?> text-sm"></i>
                                                </div>
                                                <?php if (!$notification['is_read']): ?>
                                                <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="mb-2 ml-10">
                                                <p class="text-sm text-gray-900 font-medium"><?php echo htmlspecialchars($notification['title']); ?></p>
                                                <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($notification['message']); ?></p>
                                            </div>
                                            <?php if ($notification['ticket_number']): ?>
                                            <a href="view_ticket.php?id=<?php echo $notification['ticket_id']; ?>" 
                                               class="inline-flex items-center text-xs text-emerald-600 hover:text-emerald-700 ml-10">
                                                <i class="fas fa-ticket-alt mr-1"></i>
                                                View Ticket #<?php echo htmlspecialchars($notification['ticket_number']); ?>
                                            </a>
                                            <?php endif; ?>
                                            <div class="mt-2 text-xs text-gray-400 ml-10">
                                                <i class="far fa-clock mr-1"></i>
                                                <?php echo date('M d, Y g:i A', strtotime($notification['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-1 ml-4">
                                            <?php if (!$notification['is_read']): ?>
                                            <form method="POST" action="notifications.php" class="inline">
                                                <input type="hidden" name="action" value="mark_read">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="p-2 text-gray-400 hover:text-emerald-600 rounded-lg hover:bg-emerald-50 transition" title="Mark as read">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                            <form method="POST" action="notifications.php" class="inline" onsubmit="return confirm('Delete this notification?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition" title="Delete">
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
                        <?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
                        <div class="bg-gray-50 border-t border-gray-100 px-6 py-4">
                            <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                                <div class="text-sm text-gray-500">
                                    Showing <span class="font-medium text-gray-900"><?php echo (($pagination['currentPage'] - 1) * $pagination['itemsPerPage']) + 1; ?></span> 
                                    to <span class="font-medium text-gray-900"><?php echo min($pagination['currentPage'] * $pagination['itemsPerPage'], $pagination['totalItems']); ?></span> 
                                    of <span class="font-medium text-gray-900"><?php echo $pagination['totalItems']; ?></span> notifications
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <?php if ($pagination['hasPrevPage']): ?>
                                    <a href="?page=<?php echo $pagination['currentPage'] - 1; ?>" 
                                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                        <i class="fas fa-chevron-left mr-1"></i> Previous
                                    </a>
                                    <?php else: ?>
                                    <button disabled class="px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg opacity-50 cursor-not-allowed">
                                        <i class="fas fa-chevron-left mr-1"></i> Previous
                                    </button>
                                    <?php endif; ?>
                                    
                                    <div class="flex items-center space-x-1">
                                        <?php 
                                        $startPage = max(1, $pagination['currentPage'] - 2);
                                        $endPage = min($pagination['totalPages'], $pagination['currentPage'] + 2);
                                        
                                        for ($i = $startPage; $i <= $endPage; $i++): 
                                        ?>
                                            <?php if ($i == $pagination['currentPage']): ?>
                                            <button class="px-3 py-2 text-sm font-medium text-white bg-emerald-500 rounded-lg">
                                                <?php echo $i; ?>
                                            </button>
                                            <?php else: ?>
                                            <a href="?page=<?php echo $i; ?>" 
                                               class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                                <?php echo $i; ?>
                                            </a>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </div>
                                    
                                    <?php if ($pagination['hasNextPage']): ?>
                                    <a href="?page=<?php echo $pagination['currentPage'] + 1; ?>" 
                                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                                        Next <i class="fas fa-chevron-right ml-1"></i>
                                    </a>
                                    <?php else: ?>
                                    <button disabled class="px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-200 rounded-lg opacity-50 cursor-not-allowed">
                                        Next <i class="fas fa-chevron-right ml-1"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Stats Card -->
                    <div class="bg-white border border-gray-100 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-chart-pie mr-2 text-emerald-500"></i>Notification Stats
                        </h3>
                        <div class="space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Total</span>
                                <span class="text-lg font-bold text-gray-900"><?php echo $stats['total']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Unread</span>
                                <span class="text-lg font-bold text-emerald-600"><?php echo $stats['unread']; ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-500">Read</span>
                                <span class="text-lg font-bold text-gray-400"><?php echo $stats['read']; ?></span>
                            </div>
                            <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                                <span class="text-sm font-semibold text-gray-900">Today</span>
                                <span class="text-lg font-bold text-orange-500"><?php echo $stats['today']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Types -->
                    <div class="bg-white border border-gray-100 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-bell mr-2 text-emerald-500"></i>Notification Types
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <i class="fas fa-plus-circle text-blue-600 text-sm"></i>
                                </div>
                                <span class="text-gray-600">New Tickets</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-orange-100 flex items-center justify-center">
                                    <i class="fas fa-exchange-alt text-orange-600 text-sm"></i>
                                </div>
                                <span class="text-gray-600">Status Changes</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <i class="fas fa-comment text-emerald-600 text-sm"></i>
                                </div>
                                <span class="text-gray-600">New Comments</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                    <i class="fas fa-check-circle text-gray-600 text-sm"></i>
                                </div>
                                <span class="text-gray-600">Ticket Closed</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white border border-gray-100 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-bolt mr-2 text-emerald-500"></i>Quick Actions
                        </h3>
                        <div class="space-y-2">
                            <a href="tickets.php" class="flex items-center justify-center gap-2 px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-100 transition">
                                <i class="fas fa-ticket-alt"></i>View My Tickets
                            </a>
                            <a href="dashboard.php" class="flex items-center justify-center gap-2 px-4 py-2.5 text-sm bg-gray-50 border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-100 transition">
                                <i class="fas fa-tachometer-alt"></i>Go to Dashboard
                            </a>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-6">
                        <h3 class="text-sm font-semibold text-emerald-900 mb-3 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>About Notifications
                        </h3>
                        <ul class="space-y-2 text-xs text-emerald-700">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                <span>Ticket updates in real-time</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                <span>Email alerts for important changes</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 mt-0.5 flex-shrink-0"></i>
                                <span>Notifications kept for 30 days</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
