<?php 
// Set page-specific variables
$pageTitle = 'Tickets - IT Help Desk';
$baseUrl = '../';

// Include header layout
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-white border-b border-gray-200">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <!-- Left Section: Title & Stats -->
            <div class="flex items-center space-x-4">
                <div class="hidden lg:flex items-center justify-center w-10 h-10 bg-gray-900 text-white">
                    <i class="fas fa-ticket-alt text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">
                        Support Tickets
                    </h1>
                    <div class="flex items-center space-x-3 mt-0.5">
                        <p class="text-sm text-gray-500">Manage and track all support requests</p>
                        <?php if ($isITStaff): ?>
                        <span class="hidden md:inline-flex items-center px-2 py-0.5 text-xs font-medium border border-gray-300 text-gray-700">
                            <i class="fas fa-shield-alt mr-1"></i>
                            <?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Right Section: Actions & User -->
            <div class="flex items-center space-x-3">
                <!-- Search (Hidden on Mobile) -->
                <div class="hidden md:block relative">
                    <input 
                        type="text" 
                        placeholder="Quick search..." 
                        class="pl-10 pr-4 py-2 w-48 lg:w-64 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all"
                        id="quickSearch"
                    >
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                </div>

                <!-- Dark Mode Toggle -->
                <button id="darkModeToggle" class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition" title="Toggle dark mode">
                    <i id="dark-mode-icon" class="fas fa-moon"></i>
                </button>

                <!-- Action Buttons -->
                <?php if (!$isITStaff): ?>
                <a href="create_ticket.php" class="flex items-center px-4 py-2 bg-gray-900 text-white hover:bg-gray-800 transition" title="Create a new support ticket">
                    <i class="fas fa-plus mr-2"></i>
                    <span class="hidden sm:inline">New Ticket</span>
                </a>
                <?php endif; ?>
                
                <?php if ($isITStaff): ?>
                <!-- Quick Actions Dropdown -->
                <div class="relative" id="quickActionsDropdown">
                    <button class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition" id="quickActionsBtn">
                        <i class="fas fa-bolt text-blue-600"></i>
                        <span class="hidden lg:inline text-sm font-medium">Quick Actions</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50" id="quickActionsMenu">
                        <div class="py-2">
                            <a href="create_ticket.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                <i class="fas fa-plus-circle w-5"></i>
                                <span class="ml-3">Create Ticket</span>
                            </a>
                            <a href="export_tickets.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                <i class="fas fa-file-excel w-5 text-green-600"></i>
                                <span class="ml-3">Export to Excel</span>
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition" onclick="printTickets(); return false;">
                                <i class="fas fa-print w-5"></i>
                                <span class="ml-3">Print View</span>
                            </a>
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                <i class="fas fa-cog w-5"></i>
                                <span class="ml-3">Settings</span>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Notifications Bell -->
                <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition" title="Notifications" id="notificationBell">
                    <i class="far fa-bell text-lg"></i>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                </button>

                <!-- User Avatar with Dropdown -->
                <div class="relative" id="userMenuDropdown">
                    <button class="flex items-center space-x-2 p-1 hover:bg-gray-100 transition" id="userMenuBtn">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=fff" 
                             alt="User" 
                             class="w-10 h-10 rounded-full"
                             title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                        <div class="hidden lg:block text-left">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>
                        <i class="fas fa-chevron-down text-xs text-gray-500 hidden lg:block"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-64 bg-white border border-gray-200 hidden z-50" id="userMenu">
                        <div class="p-4 border-b border-gray-200">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>
                        <div class="py-2">
                            <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                <i class="fas fa-user w-5"></i>
                                <span class="ml-3">My Profile</span>
                            </a>
                            <a href="settings.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                <i class="fas fa-cog w-5"></i>
                                <span class="ml-3">Settings</span>
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="../logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                                <i class="fas fa-sign-out-alt w-5"></i>
                                <span class="ml-3">Logout</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Search Bar -->
        <div class="md:hidden px-4 pb-4">
            <div class="relative">
                <input 
                    type="text" 
                    placeholder="Search tickets..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                    id="mobileQuickSearch"
                >
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="p-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-blue-600">
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
                        <span class="ml-1 text-sm font-medium text-gray-700">Tickets</span>
                    </div>
                </li>
            </ol>
        </nav>
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

        <!-- Filters -->
        <div class="bg-white border border-gray-200 p-6 mb-6">
            <div class="flex items-center space-x-3 mb-6">
                <div class="w-8 h-8 bg-gray-100 flex items-center justify-center text-gray-700">
                    <i class="fas fa-filter text-sm"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Filter Tickets</h3>
                    <p class="text-sm text-gray-500">Refine your search results</p>
                </div>
            </div>
            <form method="GET" action="tickets.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Search
                    </label>
                    <input 
                        type="text" 
                        name="search" 
                        value="<?php echo htmlspecialchars($filters['search']); ?>"
                        placeholder="Ticket number, title..."
                        class="w-full px-4 py-2 border border-gray-300 focus:outline-none focus:border-gray-400 transition"
                    >
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Status
                    </label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 focus:outline-none focus:border-gray-400 transition">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="open" <?php echo $filters['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Priority
                    </label>
                    <select name="priority" class="w-full px-4 py-2 border border-gray-300 focus:outline-none focus:border-gray-400 transition">
                        <option value="">All Priority</option>
                        <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo $filters['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Category
                    </label>
                    <select name="category_id" class="w-full px-4 py-2 border border-gray-300 focus:outline-none focus:border-gray-400 transition">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $filters['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-4 flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="submit" class="inline-flex items-center px-6 py-2 bg-gray-900 text-white hover:bg-gray-800 transition font-medium">
                        <i class="fas fa-filter mr-2"></i>Apply Filters
                    </button>
                    <a href="tickets.php" class="inline-flex items-center px-6 py-2 border border-gray-300 text-gray-700 hover:border-gray-400 transition font-medium">
                        <i class="fas fa-times mr-2"></i>Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Tickets Table -->
        <div class="bg-white border border-gray-200 overflow-hidden">
            <!-- Table Header -->
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 bg-gray-100 flex items-center justify-center text-gray-700">
                        <i class="fas fa-ticket-alt text-sm"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">All Tickets</h3>
                        <p class="text-sm text-gray-500">
                            <?php echo count($tickets); ?> ticket<?php echo count($tickets) !== 1 ? 's' : ''; ?> found
                        </p>
                    </div>
                </div>
                <div class="hidden md:flex items-center space-x-2 px-3 py-1.5 border border-gray-300 text-xs text-gray-600">
                    <i class="fas fa-clock"></i>
                    <span>Live View</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                Ticket
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                Category
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                Priority
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                Status
                            </th>
                            <?php if ($isITStaff): ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                Submitter
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                Assigned To
                            </th>
                            <?php endif; ?>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                Created
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php if (empty($tickets)): ?>
                        <tr>
                            <td colspan="<?php echo $isITStaff ? '8' : '6'; ?>" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center">
                                        <i class="fas fa-inbox text-4xl text-gray-400"></i>
                                    </div>
                                    <p class="text-gray-500 font-medium">No tickets found</p>
                                    <p class="text-sm text-gray-400">Try adjusting your filters or create a new ticket</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($tickets as $ticket): ?>
                            <tr class="hover:bg-gray-50 transition cursor-pointer" onclick="window.location.href='view_ticket.php?id=<?php echo $ticket['id']; ?>'">
                                <td class="px-6 py-4 border-b border-gray-200">
                                    <div class="flex items-center space-x-3">
                                        <!-- Ticket Number Badge -->
                                        <span class="inline-flex items-center px-3 py-1 text-xs font-semibold bg-gray-900 text-white">
                                            <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                        </span>
                                        <!-- Title -->
                                        <div class="flex-1 min-w-0">
                                            <div class="font-medium text-gray-900 truncate">
                                                <?php echo htmlspecialchars($ticket['title']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 border-b border-gray-200">
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-medium border" style="background-color: <?php echo $ticket['category_color']; ?>20; color: <?php echo $ticket['category_color']; ?>; border-color: <?php echo $ticket['category_color']; ?>40;">
                                        <?php echo htmlspecialchars($ticket['category_name']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 border-b border-gray-200">
                                    <?php
                                    $priorityConfig = [
                                        'low' => ['bg' => 'bg-green-600', 'icon' => 'fa-minus'],
                                        'medium' => ['bg' => 'bg-yellow-600', 'icon' => 'fa-arrow-up'],
                                        'high' => ['bg' => 'bg-orange-600', 'icon' => 'fa-arrow-up'],
                                        'urgent' => ['bg' => 'bg-red-600', 'icon' => 'fa-exclamation-triangle']
                                    ];
                                    $config = $priorityConfig[$ticket['priority']];
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-white <?php echo $config['bg']; ?>">
                                        <i class="fas <?php echo $config['icon']; ?> mr-1.5"></i>
                                        <?php echo strtoupper($ticket['priority']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 border-b border-gray-200">
                                    <?php
                                    $statusConfig = [
                                        'pending' => ['bg' => 'bg-yellow-600', 'icon' => 'fa-clock'],
                                        'open' => ['bg' => 'bg-blue-600', 'icon' => 'fa-folder-open'],
                                        'in_progress' => ['bg' => 'bg-purple-600', 'icon' => 'fa-spinner'],
                                        'resolved' => ['bg' => 'bg-green-600', 'icon' => 'fa-check-circle'],
                                        'closed' => ['bg' => 'bg-gray-600', 'icon' => 'fa-times-circle']
                                    ];
                                    $config = $statusConfig[$ticket['status']];
                                    ?>
                                    <span class="inline-flex items-center px-3 py-1 text-xs font-semibold text-white <?php echo $config['bg']; ?>">
                                        <i class="fas <?php echo $config['icon']; ?> mr-1.5"></i>
                                        <?php echo str_replace('_', ' ', strtoupper($ticket['status'])); ?>
                                    </span>
                                </td>
                                <?php if ($isITStaff): ?>
                                <td class="px-6 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center text-white text-xs font-semibold">
                                            <?php echo strtoupper(substr($ticket['submitter_name'], 0, 1)); ?>
                                        </div>
                                        <span class="font-medium"><?php echo htmlspecialchars($ticket['submitter_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 border-b border-gray-200">
                                    <?php if ($ticket['assigned_name']): ?>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-8 h-8 rounded-full bg-gray-900 flex items-center justify-center text-white text-xs font-semibold">
                                            <?php echo strtoupper(substr($ticket['assigned_name'], 0, 1)); ?>
                                        </div>
                                        <span class="font-medium"><?php echo htmlspecialchars($ticket['assigned_name']); ?></span>
                                    </div>
                                    <?php else: ?>
                                    <div class="flex items-center space-x-2 text-gray-400">
                                        <div class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center">
                                            <i class="fas fa-user-slash text-xs"></i>
                                        </div>
                                        <span class="italic">Unassigned</span>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td class="px-6 py-4 text-sm text-gray-600 border-b border-gray-200">
                                    <div class="flex flex-col">
                                        <span class="font-medium time-ago" data-timestamp="<?php echo $ticket['created_at']; ?>">
                                            <?php echo formatDate($ticket['created_at'], 'M d, Y'); ?>
                                        </span>
                                        <span class="text-xs text-gray-400">
                                            <?php echo formatDate($ticket['created_at'], 'h:i A'); ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm border-b border-gray-200">
                                    <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" 
                                       class="inline-flex items-center px-4 py-2 bg-gray-900 text-white hover:bg-gray-800 transition" 
                                       onclick="event.stopPropagation()">
                                        <i class="fas fa-eye mr-2"></i>
                                        <span class="font-medium">View</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer with Stats -->
            <?php if (!empty($tickets)): ?>
            <div class="bg-gray-50 border-t border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between text-sm">
                    <div class="flex items-center space-x-6">
                        <div class="flex items-center space-x-2 px-3 py-1.5 border border-gray-300">
                            <i class="fas fa-ticket-alt text-gray-700"></i>
                            <span class="text-gray-700 font-medium">
                                <strong class="text-gray-900"><?php echo count($tickets); ?></strong> ticket<?php echo count($tickets) !== 1 ? 's' : ''; ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-2 text-gray-500">
                        <i class="fas fa-info-circle"></i>
                        <span class="text-xs">Click on any row to view details</span>
                    </div>
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
