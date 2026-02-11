<?php 
// Set page-specific variables
$currentView = $filters['view'] ?? '';
// Support both pool and queue (legacy)
if ($currentView === 'queue') $currentView = 'pool';
$pageTitle = ($currentView === 'my_tickets' ? 'My Tickets' : ($currentView === 'pool' ? 'Ticket Pool' : 'All Tickets')) . ' - ' . APP_NAME;
$includeFirebase = true; // Enable Firebase notifications
$baseUrl = '../';

// Determine header content based on view
$headerConfig = [
    'my_tickets' => [
        'title' => 'My Tickets',
        'subtitle' => 'Tickets assigned to you',
        'icon' => 'fa-user-check'
    ],
    'pool' => [
        'title' => 'Ticket Pool',
        'subtitle' => 'Unassigned tickets available to grab',
        'icon' => 'fa-inbox'
    ],
    '' => [
        'title' => 'All Tickets',
        'subtitle' => 'Manage and track all support requests',
        'icon' => 'fa-ticket-alt'
    ]
];
$header = $headerConfig[$currentView] ?? $headerConfig[''];

// Include header layout
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gray-50">
    <?php
    // Set header variables for this page
    $headerTitle = $header['title'];
    $headerSubtitle = $header['subtitle'];
    $headerBadge = $isITStaff ? ucfirst(str_replace('_', ' ', $currentUser['role'])) : null;
    $showQuickActions = $isITStaff;
    
    include __DIR__ . '/../../includes/top_header.php';
    ?>

    <!-- Content -->
    <div class="p-8">
        <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            <?php 
                if ($_GET['success'] === 'created') {
                    echo 'Ticket created successfully!';
                } elseif ($_GET['success'] === 'updated') {
                    echo 'Ticket updated successfully!';
                }
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
            ?>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm mb-6">
            <form method="GET" action="tickets.php" class="p-4">
                <!-- Preserve view parameter -->
                <?php if (!empty($currentView)): ?>
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($currentView); ?>">
                <?php endif; ?>
                <div class="flex flex-wrap items-end justify-end gap-4">
                    <!-- Search -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($filters['search']); ?>"
                            placeholder="Ticket number, title..."
                            class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-white text-gray-800 placeholder-gray-400 focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm"
                        >
                    </div>
                    
                    <!-- Status -->
                    <div class="w-40">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-white text-gray-800 focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="open" <?php echo $filters['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    
                    <!-- Priority -->
                    <div class="w-36">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Priority</label>
                        <select name="priority" class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-white text-gray-800 focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm">
                            <option value="">All Priority</option>
                            <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>
                    
                    <!-- Category -->
                    <div class="w-44">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Category</label>
                        <select name="category_id" class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-white text-gray-800 focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $filters['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Department -->
                    <div class="w-44">
                        <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide mb-1.5">Department</label>
                        <select name="department_id" class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-white text-gray-800 focus:ring-2 focus:ring-gray-900 focus:border-transparent text-sm">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $department): ?>
                            <option value="<?php echo $department['id']; ?>" <?php echo $filters['department_id'] == $department['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($department['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white hover:bg-gray-800 transition font-medium rounded-lg text-sm">
                            <i class="fas fa-search mr-2 text-xs"></i>Search
                        </button>
                        <a href="tickets.php<?php echo !empty($currentView) ? '?view=' . htmlspecialchars($currentView) : ''; ?>" class="inline-flex items-center px-4 py-2 bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition font-medium rounded-lg text-sm">
                            <i class="fas fa-redo mr-2 text-xs"></i>Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tickets Table -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <!-- Table Header -->
            <div class="px-6 py-5 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-gray-900 flex items-center justify-center text-white rounded-lg">
                        <i class="fas <?php echo $header['icon']; ?>"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $header['title']; ?></h3>
                        <p class="text-sm text-gray-500">
                            <span class="font-medium text-gray-700"><?php echo $pagination['total_items']; ?></span> ticket<?php echo $pagination['total_items'] !== 1 ? 's' : ''; ?> found
                        </p>
                    </div>
                </div>
                <div class="hidden md:flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 rounded-lg text-xs text-gray-500">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span>Live View</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <?php
                            // Helper function to generate sort URL
                            function getSortUrl($field, $currentSort, $currentDir) {
                                $newDir = ($currentSort === $field && $currentDir === 'ASC') ? 'DESC' : 'ASC';
                                $url = '?sort_by=' . $field . '&sort_dir=' . $newDir;
                                if (isset($_GET['view'])) $url .= '&view=' . urlencode($_GET['view']);
                                if (isset($_GET['page'])) $url .= '&page=' . $_GET['page'];
                                if (isset($_GET['status'])) $url .= '&status=' . urlencode($_GET['status']);
                                if (isset($_GET['priority'])) $url .= '&priority=' . urlencode($_GET['priority']);
                                if (isset($_GET['category_id'])) $url .= '&category_id=' . urlencode($_GET['category_id']);
                                if (isset($_GET['search'])) $url .= '&search=' . urlencode($_GET['search']);
                                return $url;
                            }
                            
                            // Helper function to generate sort icon
                            function getSortIcon($field, $currentSort, $currentDir) {
                                if ($currentSort !== $field) {
                                    return '<i class="fas fa-sort text-gray-400 ml-1.5"></i>';
                                }
                                return $currentDir === 'ASC' 
                                    ? '<i class="fas fa-sort-up text-gray-900 ml-1.5"></i>' 
                                    : '<i class="fas fa-sort-down text-gray-900 ml-1.5"></i>';
                            }
                            ?>
                            
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <a href="<?php echo getSortUrl('ticket_number', $sorting['sort_by'], $sorting['sort_dir']); ?>" 
                                   class="flex items-center hover:text-gray-900 transition">
                                    Ticket
                                    <?php echo getSortIcon('ticket_number', $sorting['sort_by'], $sorting['sort_dir']); ?>
                                </a>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Category
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <a href="<?php echo getSortUrl('priority', $sorting['sort_by'], $sorting['sort_dir']); ?>" 
                                   class="flex items-center hover:text-gray-900 transition">
                                    Priority
                                    <?php echo getSortIcon('priority', $sorting['sort_by'], $sorting['sort_dir']); ?>
                                </a>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <a href="<?php echo getSortUrl('status', $sorting['sort_by'], $sorting['sort_dir']); ?>" 
                                   class="flex items-center hover:text-gray-900 transition">
                                    Status
                                    <?php echo getSortIcon('status', $sorting['sort_by'], $sorting['sort_dir']); ?>
                                </a>
                            </th>
                            <?php if ($currentView === 'pool' || $currentView === 'my_tickets'): ?>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <?php echo $currentView === 'my_tickets' ? 'Transfer To' : 'Assignee'; ?>
                            </th>
                            <?php elseif ($isITStaff): ?>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <span title="Resolution SLA Timer">SLA</span>
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Submitter
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Assigned
                            </th>
                            <?php endif; ?>
                            <?php if ($currentView !== 'pool'): ?>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <a href="<?php echo getSortUrl('created_at', $sorting['sort_by'], $sorting['sort_dir']); ?>" 
                                   class="flex items-center hover:text-gray-900 transition">
                                    Created
                                    <?php echo getSortIcon('created_at', $sorting['sort_by'], $sorting['sort_dir']); ?>
                                </a>
                            </th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="<?php echo $currentView === 'pool' ? '5' : ($isITStaff ? '9' : '6'); ?>" class="px-6 py-20 text-center">
                                <div class="flex flex-col items-center justify-center gap-4">
                                    <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center">
                                        <i class="fas fa-inbox text-3xl text-gray-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-gray-900 font-semibold text-lg">No tickets found</p>
                                        <p class="text-sm text-gray-500 mt-1">Try adjusting your filters or create a new ticket</p>
                                    </div>
                                    <a href="create_ticket.php" class="mt-2 inline-flex items-center px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition text-sm font-medium">
                                        <i class="fas fa-plus mr-2"></i>Create Ticket
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr class="hover:bg-gray-50 transition-colors cursor-pointer group" onclick="window.location.href='view_ticket.php?id=<?php echo $ticket['id']; ?>'">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <!-- Ticket Number Badge -->
                                        <span class="inline-flex items-center px-2.5 py-1.5 text-xs font-mono font-semibold bg-gray-100 text-gray-700 rounded">
                                            <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                        </span>
                                        <!-- Title (hidden in pool view) -->
                                        <?php if ($currentView !== 'pool'): ?>
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-gray-900 truncate group-hover:text-gray-700">
                                                <?php echo htmlspecialchars($ticket['title']); ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <span class="inline-flex items-center px-3 py-1.5 <?php echo $currentView === 'pool' ? 'text-sm' : 'text-xs'; ?> font-medium rounded-full" style="background-color: <?php echo $ticket['category_color']; ?>15; color: <?php echo $ticket['category_color']; ?>;">
                                            <?php echo htmlspecialchars($ticket['category_name']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <?php
                                        $priorityConfig = [
                                            'low' => ['bg' => 'bg-green-100 text-green-700', 'icon' => 'fa-minus'],
                                            'medium' => ['bg' => 'bg-yellow-100 text-yellow-700', 'icon' => 'fa-minus'],
                                            'high' => ['bg' => 'bg-red-100 text-red-700', 'icon' => 'fa-arrow-up']
                                        ];
                                        $config = $priorityConfig[$ticket['priority']] ?? $priorityConfig['medium'];
                                        ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold rounded-full <?php echo $config['bg']; ?>">
                                            <i class="fas <?php echo $config['icon']; ?> text-[10px]"></i>
                                            <?php echo ucfirst($ticket['priority']); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <?php
                                        $statusConfig = [
                                            'pending' => ['bg' => 'bg-amber-100 text-amber-700', 'icon' => 'fa-clock'],
                                            'open' => ['bg' => 'bg-blue-100 text-blue-700', 'icon' => 'fa-folder-open'],
                                            'in_progress' => ['bg' => 'bg-purple-100 text-purple-700', 'icon' => 'fa-spinner'],
                                            'resolved' => ['bg' => 'bg-green-100 text-green-700', 'icon' => 'fa-check-circle'],
                                            'closed' => ['bg' => 'bg-gray-100 text-gray-600', 'icon' => 'fa-lock']
                                        ];
                                        $config = $statusConfig[$ticket['status']];
                                        ?>
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs font-semibold rounded-full <?php echo $config['bg']; ?>">
                                            <i class="fas <?php echo $config['icon']; ?> text-[10px]"></i>
                                            <?php echo ucwords(str_replace('_', ' ', $ticket['status'])); ?>
                                        </span>
                                    </div>
                                </td>
                                <?php if ($currentView === 'pool' || $currentView === 'my_tickets'): ?>
                                <!-- Assignee/Transfer Dropdown -->
                                <td class="px-6 py-4">
                                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>?action=assign&from=<?php echo $currentView; ?>" onclick="event.stopPropagation();" class="assign-form">
                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                                        <select name="assigned_to" onchange="this.form.submit()" 
                                                class="w-full text-xs px-2.5 py-1.5 border border-gray-200 rounded-lg bg-white text-gray-700 focus:ring-2 focus:ring-gray-900 focus:border-transparent cursor-pointer min-w-[140px] max-w-[160px]">
                                            <option value="" class="text-gray-400">-- <?php echo $currentView === 'my_tickets' ? 'Transfer to' : 'Unassigned'; ?> --</option>
                                            <?php if (!empty($employeeAdmins)): ?>
                                                <?php foreach ($employeeAdmins as $admin): ?>
                                                <?php 
                                                    $adminName = htmlspecialchars($admin['full_name'] ?? ($admin['fname'] . ' ' . $admin['lname']));
                                                    $isCurrentAssignee = (isset($ticket['assigned_to']) && $ticket['assigned_to'] == $admin['id']);
                                                ?>
                                                <option value="emp_<?php echo $admin['id']; ?>" <?php echo $isCurrentAssignee ? 'selected disabled' : ''; ?>><?php echo $adminName; ?><?php echo $isCurrentAssignee ? ' (current)' : ''; ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </form>
                                </td>
                                <?php elseif ($isITStaff): ?>
                                <!-- SLA Status Badge -->
                                <td class="px-6 py-4">
                                    <?php
                                    $slaStatus = $ticket['sla_display_status'] ?? 'safe';
                                    $minutesRemaining = $ticket['minutes_remaining'] ?? 0;
                                    $isPaused = $ticket['is_paused'] ?? 0;
                                    
                                    // Calculate display text
                                    if ($isPaused) {
                                        $slaText = 'Paused';
                                        $slaBg = 'bg-gray-500';
                                        $slaIcon = 'fa-pause';
                                    } elseif ($slaStatus === 'breached') {
                                        $hoursOver = abs(floor($minutesRemaining / 60));
                                        $minsOver = abs($minutesRemaining % 60);
                                        $slaText = "BREACHED";
                                        if ($hoursOver > 0) $slaText .= " {$hoursOver}h";
                                        if ($minsOver > 0 || $hoursOver === 0) $slaText .= " {$minsOver}m";
                                        $slaText .= " ago";
                                        $slaBg = 'bg-red-600';
                                        $slaIcon = 'fa-exclamation-triangle';
                                    } elseif ($slaStatus === 'at_risk') {
                                        $hours = floor($minutesRemaining / 60);
                                        $mins = $minutesRemaining % 60;
                                        $slaText = '';
                                        if ($hours > 0) $slaText .= "{$hours}h ";
                                        $slaText .= "{$mins}m remaining";
                                        $slaBg = 'bg-yellow-600';
                                        $slaIcon = 'fa-clock';
                                    } elseif ($slaStatus === 'met') {
                                        $slaText = 'Met';
                                        $slaBg = 'bg-green-600';
                                        $slaIcon = 'fa-check';
                                    } else {
                                        $hours = floor($minutesRemaining / 60);
                                        $mins = $minutesRemaining % 60;
                                        $slaText = '';
                                        if ($hours > 0) $slaText .= "{$hours}h ";
                                        $slaText .= "{$mins}m";
                                        $slaBg = 'bg-green-600';
                                        $slaIcon = 'fa-check-circle';
                                    }
                                    ?>
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full <?php echo $slaBg; ?> text-white" title="Resolution deadline">
                                        <i class="fas <?php echo $slaIcon; ?> text-[10px]"></i>
                                        <?php echo $slaText; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-gray-600 text-xs font-semibold">
                                            <?php echo strtoupper(substr($ticket['submitter_name'], 0, 1)); ?>
                                        </div>
                                        <span class="font-medium text-gray-900 truncate max-w-[120px]"><?php echo htmlspecialchars($ticket['submitter_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <?php if ($ticket['assigned_name']): ?>
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 text-xs font-semibold">
                                            <?php echo strtoupper(substr($ticket['assigned_name'], 0, 1)); ?>
                                        </div>
                                        <span class="font-medium text-gray-900 truncate max-w-[120px]"><?php echo htmlspecialchars($ticket['assigned_name']); ?></span>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-gray-400 text-xs italic">Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <?php if ($currentView !== 'pool'): ?>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex flex-col">
                                        <span class="font-medium time-ago" data-timestamp="<?php echo $ticket['created_at']; ?>">
                                            <?php echo formatDate($ticket['created_at'], 'M d, Y'); ?>
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            <?php echo formatDate($ticket['created_at'], 'h:i A'); ?>
                                        </span>
                                    </div>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer with Stats -->
            <?php if (!empty($tickets)): ?>
            <div class="bg-gray-50 border-t border-gray-200 px-6 py-4">
                <div class="flex flex-col lg:flex-row items-center justify-between gap-4">
                    <!-- Results Info -->
                    <div class="flex items-center gap-2 text-sm text-gray-600">
                        <span>
                            Showing <strong class="text-gray-900"><?php 
                                $startItem = (($pagination['current_page'] - 1) * $pagination['items_per_page']) + 1;
                                $endItem = min($pagination['current_page'] * $pagination['items_per_page'], $pagination['total_items']);
                                echo $startItem . '-' . $endItem;
                            ?></strong> of <strong class="text-gray-900"><?php echo $pagination['total_items']; ?></strong> tickets
                        </span>
                    </div>

                    <!-- Pagination Controls -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                    <?php $viewParam = isset($_GET['view']) ? '&view=' . urlencode($_GET['view']) : ''; ?>
                    <div class="flex items-center gap-1">
                        <!-- Previous Button -->
                        <?php if ($pagination['current_page'] > 1): ?>
                        <a href="?page=<?php echo $pagination['current_page'] - 1; ?><?php echo $viewParam; ?>&sort_by=<?php echo $sorting['sort_by']; ?>&sort_dir=<?php echo $sorting['sort_dir']; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['priority']) ? '&priority=' . urlencode($_GET['priority']) : ''; ?>" 
                           class="px-3 py-2 bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition rounded-lg">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </a>
                        <?php else: ?>
                        <span class="px-3 py-2 bg-gray-50 border border-gray-100 text-gray-300 rounded-lg cursor-not-allowed">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </span>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <div class="hidden sm:flex items-center gap-1">
                            <?php
                            $maxPagesToShow = 5;
                            $halfPages = floor($maxPagesToShow / 2);
                            $startPage = max(1, $pagination['current_page'] - $halfPages);
                            $endPage = min($pagination['total_pages'], $pagination['current_page'] + $halfPages);
                            
                            // Adjust if at start or end
                            if ($pagination['current_page'] <= $halfPages) {
                                $endPage = min($maxPagesToShow, $pagination['total_pages']);
                            }
                            if ($pagination['current_page'] > $pagination['total_pages'] - $halfPages) {
                                $startPage = max(1, $pagination['total_pages'] - $maxPagesToShow + 1);
                            }

                            // First page
                            if ($startPage > 1) {
                                echo '<a href="?page=1' . $viewParam . '&sort_by=' . $sorting['sort_by'] . '&sort_dir=' . $sorting['sort_dir'];
                                if (isset($_GET['status'])) echo '&status=' . urlencode($_GET['status']);
                                if (isset($_GET['priority'])) echo '&priority=' . urlencode($_GET['priority']);
                                echo '" class="px-3 py-2 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition rounded-lg text-sm">1</a>';
                                if ($startPage > 2) echo '<span class="px-2 text-gray-400">...</span>';
                            }

                            // Page numbers
                            for ($i = $startPage; $i <= $endPage; $i++):
                                if ($i == $pagination['current_page']):
                            ?>
                            <span class="px-3 py-2 bg-gray-900 text-white font-medium rounded-lg text-sm">
                                <?php echo $i; ?>
                            </span>
                            <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $viewParam; ?>&sort_by=<?php echo $sorting['sort_by']; ?>&sort_dir=<?php echo $sorting['sort_dir']; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['priority']) ? '&priority=' . urlencode($_GET['priority']) : ''; ?>" 
                               class="px-3 py-2 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition rounded-lg text-sm">
                                <?php echo $i; ?>
                            </a>
                            <?php 
                                endif;
                            endfor;

                            // Last page
                            if ($endPage < $pagination['total_pages']) {
                                if ($endPage < $pagination['total_pages'] - 1) echo '<span class="px-2 text-gray-400">...</span>';
                                echo '<a href="?page=' . $pagination['total_pages'] . $viewParam . '&sort_by=' . $sorting['sort_by'] . '&sort_dir=' . $sorting['sort_dir'];
                                if (isset($_GET['status'])) echo '&status=' . urlencode($_GET['status']);
                                if (isset($_GET['priority'])) echo '&priority=' . urlencode($_GET['priority']);
                                echo '" class="px-3 py-2 bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 hover:border-gray-300 transition rounded-lg text-sm">' . $pagination['total_pages'] . '</a>';
                            }
                            ?>
                        </div>

                        <!-- Mobile: Current Page Display -->
                        <div class="sm:hidden px-3 py-2 border border-gray-200 bg-white text-gray-600 rounded-lg text-sm">
                            <span class="text-gray-900 font-medium"><?php echo $pagination['current_page']; ?></span> / <?php echo $pagination['total_pages']; ?>
                        </div>

                        <!-- Next Button -->
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                        <a href="?page=<?php echo $pagination['current_page'] + 1; ?><?php echo $viewParam; ?>&sort_by=<?php echo $sorting['sort_by']; ?>&sort_dir=<?php echo $sorting['sort_dir']; ?><?php echo isset($_GET['status']) ? '&status=' . urlencode($_GET['status']) : ''; ?><?php echo isset($_GET['priority']) ? '&priority=' . urlencode($_GET['priority']) : ''; ?>" 
                           class="px-3 py-2 bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition rounded-lg">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </a>
                        <?php else: ?>
                        <span class="px-3 py-2 bg-gray-50 border border-gray-100 text-gray-300 rounded-lg cursor-not-allowed">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Page-specific JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Quick Actions Dropdown
        const quickActionsBtn = document.getElementById('quickActionsBtn');
        const quickActionsMenu = document.getElementById('quickActionsMenu');
        
        if (quickActionsBtn && quickActionsMenu) {
            quickActionsBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                quickActionsMenu.classList.toggle('hidden');
                // Close user menu if open
                const userMenu = document.getElementById('userMenu');
                if (userMenu) userMenu.classList.add('hidden');
            });
        }

        // User Menu Dropdown
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenu = document.getElementById('userMenu');
        
        if (userMenuBtn && userMenu) {
            userMenuBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenu.classList.toggle('hidden');
                // Close quick actions if open
                if (quickActionsMenu) quickActionsMenu.classList.add('hidden');
            });
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            // Check if click is outside quick actions dropdown
            const quickActionsDropdown = document.getElementById('quickActionsDropdown');
            if (quickActionsMenu && quickActionsDropdown && !quickActionsDropdown.contains(e.target)) {
                quickActionsMenu.classList.add('hidden');
            }
            
            // Check if click is outside user menu dropdown
            const userMenuDropdown = document.getElementById('userMenuDropdown');
            if (userMenu && userMenuDropdown && !userMenuDropdown.contains(e.target)) {
                userMenu.classList.add('hidden');
            }
        });

        // Quick Search Functionality
        const quickSearch = document.getElementById('quickSearch');
        const mobileQuickSearch = document.getElementById('mobileQuickSearch');
        
        function handleQuickSearch(searchValue) {
            const searchTerm = searchValue.toLowerCase().trim();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm) || searchTerm === '') {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        if (quickSearch) {
            quickSearch.addEventListener('input', function() {
                handleQuickSearch(this.value);
                // Sync with mobile search
                if (mobileQuickSearch) mobileQuickSearch.value = this.value;
            });
        }
        
        if (mobileQuickSearch) {
            mobileQuickSearch.addEventListener('input', function() {
                handleQuickSearch(this.value);
                // Sync with desktop search
                if (quickSearch) quickSearch.value = this.value;
            });
        }

        // Print function
        window.printTickets = function() {
            window.print();
        };
    });
</script>

<?php 
// Include footer layout
include __DIR__ . '/../layouts/footer.php'; 
?>


