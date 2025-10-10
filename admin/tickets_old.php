<?php
require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireITStaff();

$currentUser = $auth->getCurrentUser();
$isITStaff = $currentUser['role'] === 'it_staff' || $currentUser['role'] === 'admin';

$ticketModel = new Ticket();
$categoryModel = new Category();

// Get filter parameters
$filters = [
    'status' => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'category_id' => $_GET['category_id'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// If employee, only show their tickets
if (!$isITStaff) {
    $filters['submitter_id'] = $currentUser['id'];
}

// Get tickets
$tickets = $ticketModel->getAll($filters);
$categories = $categoryModel->getAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Quick Wins CSS -->
    <link rel="stylesheet" href="../assets/css/print.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Enhanced Top Bar -->
        <div class="bg-gradient-to-r from-white to-blue-50 shadow-sm border-b border-blue-100">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
                <!-- Left Section: Title & Stats -->
                <div class="flex items-center space-x-4">
                    <div class="hidden lg:flex items-center justify-center w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg">
                        <i class="fas fa-ticket-alt text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-gray-900 to-blue-600 bg-clip-text text-transparent">
                            Support Tickets
                        </h1>
                        <div class="flex items-center space-x-3 mt-1">
                            <p class="text-sm text-gray-600">Manage and track all support requests</p>
                            <?php if ($isITStaff): ?>
                            <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
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
                    <a href="create_ticket.php" class="flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition shadow-md hover:shadow-lg" title="Create a new support ticket">
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
                        <button class="flex items-center space-x-2 p-1 hover:bg-gray-100 rounded-lg transition" id="userMenuBtn">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                                 alt="User" 
                                 class="w-10 h-10 rounded-full ring-2 ring-blue-200"
                                 title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                            <div class="hidden lg:block text-left">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                            </div>
                            <i class="fas fa-chevron-down text-xs text-gray-500 hidden lg:block"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50" id="userMenu">
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
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <form method="GET" action="tickets.php" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                        <input 
                            type="text" 
                            name="search" 
                            value="<?php echo htmlspecialchars($filters['search']); ?>"
                            placeholder="Ticket number, title..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Status</option>
                            <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="open" <?php echo $filters['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                            <option value="in_progress" <?php echo $filters['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                            <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                            <option value="closed" <?php echo $filters['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                        <select name="priority" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Priority</option>
                            <option value="low" <?php echo $filters['priority'] === 'low' ? 'selected' : ''; ?>>Low</option>
                            <option value="medium" <?php echo $filters['priority'] === 'medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="high" <?php echo $filters['priority'] === 'high' ? 'selected' : ''; ?>>High</option>
                            <option value="urgent" <?php echo $filters['priority'] === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                        <select name="category_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" <?php echo $filters['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="md:col-span-4 flex justify-end space-x-2">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-filter mr-2"></i>Apply Filters
                        </button>
                        <a href="tickets.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-times mr-2"></i>Clear
                        </a>
                    </div>
                </form>
            </div>

            <!-- Tickets Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <?php if ($isITStaff): ?>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitter</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                <?php endif; ?>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($tickets)): ?>
                            <tr>
                                <td colspan="<?php echo $isITStaff ? '8' : '6'; ?>" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-3 text-gray-400"></i>
                                    <p>No tickets found</p>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($tickets as $ticket): ?>
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($ticket['title']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-3 py-1 rounded-full text-xs font-medium" style="background-color: <?php echo $ticket['category_color']; ?>20; color: <?php echo $ticket['category_color']; ?>">
                                            <?php echo htmlspecialchars($ticket['category_name']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $priorityColors = [
                                            'low' => 'bg-green-100 text-green-800',
                                            'medium' => 'bg-yellow-100 text-yellow-800',
                                            'high' => 'bg-orange-100 text-orange-800',
                                            'urgent' => 'bg-red-100 text-red-800'
                                        ];
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $priorityColors[$ticket['priority']]; ?>">
                                            <?php echo strtoupper($ticket['priority']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'open' => 'bg-blue-100 text-blue-800',
                                            'in_progress' => 'bg-purple-100 text-purple-800',
                                            'resolved' => 'bg-green-100 text-green-800',
                                            'closed' => 'bg-gray-100 text-gray-800'
                                        ];
                                        ?>
                                        <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusColors[$ticket['status']]; ?>">
                                            <?php echo str_replace('_', ' ', strtoupper($ticket['status'])); ?>
                                        </span>
                                    </td>
                                    <?php if ($isITStaff): ?>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($ticket['submitter_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo $ticket['assigned_name'] ? htmlspecialchars($ticket['assigned_name']) : '<span class="text-gray-400">Unassigned</span>'; ?>
                                    </td>
                                    <?php endif; ?>
                    <td class="px-6 py-4 text-sm text-gray-500">
                        <span class="time-ago" data-timestamp="<?php echo $ticket['created_at']; ?>">
                            <?php echo formatDate($ticket['created_at'], 'M d, Y'); ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <a href="view_ticket.php?id=<?php echo $ticket['id']; ?>" class="text-blue-600 hover:text-blue-800" title="View ticket details">
                            <i class="fas fa-eye mr-1"></i>View
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
    
    <!-- Quick Wins JavaScript -->
    <script src="../assets/js/helpers.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initTooltips();
            initDarkMode();
            updateTimeAgo();
            setInterval(updateTimeAgo, 60000);

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
            document.addEventListener('click', function() {
                if (quickActionsMenu) quickActionsMenu.classList.add('hidden');
                if (userMenu) userMenu.classList.add('hidden');
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
</body>
</html>