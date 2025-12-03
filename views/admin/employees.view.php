<?php 
// Set page-specific variables
$pageTitle = 'Employees - IT Help Desk';
$baseUrl = '../';

// Include header layout
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-white border-b border-gray-200  relative z-40">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4 overflow-visible">
            <!-- Left Section: Title & Stats -->
            <div class="flex items-center space-x-4">
                <div class="hidden lg:flex items-center justify-center w-10 h-10 bg-gradient-to-br from-teal-500 to-emerald-600 text-gray-900 rounded-lg">
                    <i class="fas fa-users text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">
                        Employees
                    </h1>
                    <div class="flex items-center space-x-3 mt-0.5">
                        <p class="text-sm text-gray-600">Manage registered employees</p>
                        <span class="hidden md:inline-flex items-center px-2 py-0.5 text-xs font-medium border border-gray-300 text-gray-700 bg-gray-100/30 rounded">
                            <i class="fas fa-users mr-1"></i>
                            <?php echo count($employees); ?> Total
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right Section: Actions & User -->
            <div class="flex items-center space-x-3 overflow-visible">
                <!-- Smart Search with Page Finder -->
                <div class="hidden md:block relative" id="searchContainer">
                    <form method="GET" action="" class="flex">
                        <input 
                            type="text" 
                            name="search"
                            placeholder="Search name, email..." 
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                            class="pl-10 pr-4 py-2 w-48 lg:w-64 border border-gray-300 bg-gray-50 text-gray-900 placeholder-slate-400 rounded-l-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm transition-all"
                            id="searchInput"
                        >
                        <button type="submit" class="px-4 py-2 bg-gray-50 border border-l-0 border-gray-300 text-gray-600 hover:text-teal-600 rounded-r-lg transition">
                            <i class="fas fa-search text-sm"></i>
                        </button>
                    </form>
                    <!-- Search results indicator -->
                    <?php if ($searchResults): ?>
                    <div class="absolute top-full left-0 right-0 mt-2 bg-gray-100 border border-gray-300 rounded-lg shadow-lg p-3 text-sm text-gray-700 z-50 hidden" id="searchResultsInfo">
                        <div class="flex items-center justify-between">
                            <span><i class="fas fa-check-circle text-teal-600 mr-2"></i><?php echo $pagination['totalItems']; ?> result<?php echo $pagination['totalItems'] != 1 ? 's' : ''; ?> found</span>
                            <span class="text-gray-500">Page <?php echo $pagination['currentPage']; ?> of <?php echo max(1, $pagination['totalPages']); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions Dropdown -->
                <div class="relative z-50" id="quickActionsDropdown">
                    <button class="flex items-center space-x-2 px-4 py-2 border border-gray-300 bg-gray-50 text-gray-700 hover:text-gray-900 hover:border-teal-500/50 rounded-lg transition" id="quickActionsBtn">
                        <i class="fas fa-bolt text-cyan-500"></i>
                        <span class="hidden lg:inline text-sm font-medium">Quick Actions</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-56 bg-gray-100 rounded-lg shadow-xl border border-gray-200 hidden z-[100]" id="quickActionsMenu">
                        <div class="py-2">
                            <a href="add_employee.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-teal-600 transition">
                                <i class="fas fa-user-plus w-5"></i>
                                <span class="ml-3">Add Employee</span>
                            </a>
                            <a href="export_employees.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-teal-600 transition">
                                <i class="fas fa-file-excel w-5 text-green-400"></i>
                                <span class="ml-3">Export to Excel</span>
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-teal-600 transition" onclick="printEmployees(); return false;">
                                <i class="fas fa-print w-5"></i>
                                <span class="ml-3">Print View</span>
                            </a>
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-teal-600 transition">
                                <i class="fas fa-filter w-5"></i>
                                <span class="ml-3">Filter Options</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Notifications Bell -->
                <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-lg transition" title="Notifications" id="notificationBell">
                    <i class="far fa-bell text-lg"></i>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                </button>

                <!-- User Avatar with Dropdown -->
                <div class="relative z-50" id="userMenuDropdown">
                    <button class="flex items-center space-x-2 p-1 hover:bg-gray-50 transition" id="userMenuBtn">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=06b6d4" 
                             alt="User" 
                             class="w-10 h-10 rounded-full"
                             title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                        <div class="hidden lg:block text-left">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?></div>
                            <div class="text-xs text-gray-600"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>
                        <i class="fas fa-chevron-down text-xs text-gray-600 hidden lg:block"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-64 bg-gray-100 border border-gray-200 rounded-lg shadow-xl hidden z-[100]" id="userMenu">
                        <div class="p-4 border-b border-gray-200">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                            <div class="text-sm text-gray-600"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>
                        <div class="py-2">
                            <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-teal-600 transition">
                                <i class="fas fa-user w-5"></i>
                                <span class="ml-3">My Profile</span>
                            </a>
                            <a href="settings.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-teal-600 transition">
                                <i class="fas fa-cog w-5"></i>
                                <span class="ml-3">Settings</span>
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="../logout.php" class="flex items-center px-4 py-2 text-sm text-red-400 hover:bg-gray-50 transition">
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
                    placeholder="Search employees..." 
                    class="w-full pl-10 pr-4 py-2 border border-gray-300 bg-gray-50 text-gray-900 placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm"
                    id="mobileQuickSearch"
                >
                <i class="fas fa-search absolute left-3 top-3 text-gray-600 text-sm"></i>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="p-8">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 bg-emerald-900/20 border border-emerald-500/30 rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="fas fa-check-circle text-emerald-400 text-lg"></i>
                <p class="text-emerald-300 font-medium"><?php echo htmlspecialchars($_SESSION['success']); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 bg-red-900/20 border border-red-500/30 rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="fas fa-exclamation-circle text-red-400 text-lg"></i>
                <p class="text-red-300 font-medium"><?php echo htmlspecialchars($_SESSION['error']); ?></p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-300">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php unset($_SESSION['error']); endif; ?>
        
        <!-- Search Results Feedback Banner -->
        <?php if ($searchResults && !empty($searchQuery)): ?>
        <div class="mb-6 bg-teal-50 border border-teal-500/30 rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="fas fa-search text-teal-600 text-lg"></i>
                <div>
                    <p class="text-teal-500 font-medium">
                        Found <span class="font-bold text-teal-600"><?php echo $pagination['totalItems']; ?></span> result<?php echo $pagination['totalItems'] != 1 ? 's' : ''; ?> for "<span class="font-bold text-teal-600"><?php echo htmlspecialchars($searchQuery); ?></span>"
                    </p>
                    <p class="text-teal-600/70 text-sm mt-1">
                        Currently on page <span class="font-bold"><?php echo $pagination['currentPage']; ?></span> of <span class="font-bold"><?php echo max(1, $pagination['totalPages']); ?></span>
                    </p>
                </div>
            </div>
            <a href="customers.php<?php echo !empty($sortBy) ? '?sort_by=' . $sortBy . '&sort_order=' . $sortOrder : ''; ?>" class="px-4 py-2 bg-gray-50 hover:bg-slate-600 text-gray-700 hover:text-teal-600 rounded-lg transition text-sm font-medium whitespace-nowrap ml-4">
                <i class="fas fa-times mr-2"></i>Clear Search
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Breadcrumb -->
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-teal-600">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-700">Employees</span>
                    </div>
                </li>
            </ol>
        </nav>
        
        <!-- Employees Table -->
        <div class="bg-white border border-gray-200  overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                <a href="<?php echo $sortUrl('fname'); ?>&page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>" class="hover:text-teal-600 flex items-center group">
                                    Name
                                    <span class="ml-1 opacity-0 group-hover:opacity-100 transition">
                                        <?php if ($sortBy === 'fname'): ?>
                                            <?php echo $sortOrder === 'ASC' ? '▲' : '▼'; ?>
                                        <?php else: ?>
                                            ⇅
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                <a href="<?php echo $sortUrl('email'); ?>&page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>" class="hover:text-teal-600 flex items-center group">
                                    Email
                                    <span class="ml-1 opacity-0 group-hover:opacity-100 transition">
                                        <?php if ($sortBy === 'email'): ?>
                                            <?php echo $sortOrder === 'ASC' ? '▲' : '▼'; ?>
                                        <?php else: ?>
                                            ⇅
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                <a href="<?php echo $sortUrl('company'); ?>&page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>" class="hover:text-teal-600 flex items-center group">
                                    Company
                                    <span class="ml-1 opacity-0 group-hover:opacity-100 transition">
                                        <?php if ($sortBy === 'company'): ?>
                                            <?php echo $sortOrder === 'ASC' ? '▲' : '▼'; ?>
                                        <?php else: ?>
                                            ⇅
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                <a href="<?php echo $sortUrl('status'); ?>&page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>" class="hover:text-teal-600 flex items-center group">
                                    Status
                                    <span class="ml-1 opacity-0 group-hover:opacity-100 transition">
                                        <?php if ($sortBy === 'status'): ?>
                                            <?php echo $sortOrder === 'ASC' ? '▲' : '▼'; ?>
                                        <?php else: ?>
                                            ⇅
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                <a href="<?php echo $sortUrl('created_at'); ?>&page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>" class="hover:text-teal-600 flex items-center group">
                                    Joined
                                    <span class="ml-1 opacity-0 group-hover:opacity-100 transition">
                                        <?php if ($sortBy === 'created_at'): ?>
                                            <?php echo $sortOrder === 'ASC' ? '▲' : '▼'; ?>
                                        <?php else: ?>
                                            ⇅
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php foreach ($employees as $employee): ?>
                        <tr class="hover:bg-gray-100/30 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php 
                                    $employeeModel = new Employee();
                                    $fullName = $employeeModel->getFullName($employee);
                                    ?>
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($fullName); ?>&background=000000&color=06b6d4" 
                                         alt="<?php echo htmlspecialchars($fullName); ?>" 
                                         class="w-10 h-10 rounded-full mr-3">
                                    <div>
                                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($fullName); ?></div>
                                        <div class="text-sm text-gray-600">@<?php echo htmlspecialchars($employee['username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($employee['email']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($employee['company'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($employee['contact'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4">
                                <?php if ($employee['status'] === 'active'): ?>
                                <span class="px-3 py-1 text-xs font-medium border border-emerald-600/50 bg-emerald-600/20 text-emerald-400">Active</span>
                                <?php else: ?>
                                <span class="px-3 py-1 text-xs font-medium border border-gray-300 bg-gray-100/30 text-gray-700"><?php echo ucfirst($employee['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="time-ago" data-timestamp="<?php echo $employee['created_at']; ?>">
                                    <?php echo formatDate($employee['created_at'], 'M d, Y'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="edit_employee.php?id=<?php echo $employee['id']; ?>" 
                                       class="inline-flex items-center px-3 py-1.5 bg-gray-50 border border-gray-300 text-gray-700 hover:bg-gray-100 hover:text-teal-600 hover:border-teal-500/50 transition rounded-lg text-sm"
                                       title="Edit employee">
                                        <i class="fas fa-edit mr-1.5"></i>
                                        Edit
                                    </a>
                                    <button onclick="confirmDelete(<?php echo $employee['id']; ?>, '<?php echo htmlspecialchars($fullName, ENT_QUOTES); ?>')"
                                            class="inline-flex items-center px-3 py-1.5 bg-red-900/20 border border-red-600/50 text-red-400 hover:bg-red-900/30 hover:border-red-500 transition rounded-lg text-sm"
                                            title="Delete employee">
                                        <i class="fas fa-trash mr-1.5"></i>
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if (isset($pagination) && $pagination['totalPages'] > 1): ?>
            <div class="bg-white border-t border-gray-200 px-6 py-4">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <!-- Info Text -->
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-medium text-gray-900"><?php echo $pagination['offset'] + 1; ?></span> 
                        to <span class="font-medium text-gray-900"><?php echo min($pagination['offset'] + $pagination['itemsPerPage'], $pagination['totalItems']); ?></span> 
                        of <span class="font-medium text-gray-900"><?php echo $pagination['totalItems']; ?></span> employees
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div class="flex items-center space-x-1">
                        <!-- Previous Button -->
                        <?php if ($pagination['hasPrevious']): ?>
                        <a href="?page=<?php echo $pagination['previousPage']; ?>&per_page=<?php echo $pagination['itemsPerPage']; ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 border border-gray-300 rounded-lg transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php else: ?>
                        <button disabled class="px-3 py-2 text-sm font-medium text-gray-500 bg-gray-100/20 border border-gray-300 rounded-lg opacity-50">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php 
                        $pages = $pagination['pages'];
                        if ($pages[0] > 1): 
                        ?>
                        <a href="?page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 border border-gray-300 rounded-lg transition">1</a>
                        <?php if ($pages[0] > 2): ?>
                        <span class="px-2 text-gray-600">...</span>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php foreach ($pages as $page): ?>
                        <?php if ($page == $pagination['currentPage']): ?>
                        <button class="px-3 py-2 text-sm font-medium text-gray-900 bg-teal-600/50 border border-teal-600 rounded-lg">
                            <?php echo $page; ?>
                        </button>
                        <?php else: ?>
                        <a href="?page=<?php echo $page; ?>&per_page=<?php echo $pagination['itemsPerPage']; ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 border border-gray-300 rounded-lg transition">
                            <?php echo $page; ?>
                        </a>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php 
                        if ($pages[count($pages)-1] < $pagination['totalPages']): 
                        ?>
                        <?php if ($pages[count($pages)-1] < $pagination['totalPages'] - 1): ?>
                        <span class="px-2 text-gray-600">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $pagination['totalPages']; ?>&per_page=<?php echo $pagination['itemsPerPage']; ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 border border-gray-300 rounded-lg transition">
                            <?php echo $pagination['totalPages']; ?>
                        </a>
                        <?php endif; ?>
                        
                        <!-- Next Button -->
                        <?php if ($pagination['hasNext']): ?>
                        <a href="?page=<?php echo $pagination['nextPage']; ?>&per_page=<?php echo $pagination['itemsPerPage']; ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 border border-gray-300 rounded-lg transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php else: ?>
                        <button disabled class="px-3 py-2 text-sm font-medium text-gray-500 bg-gray-100/20 border border-gray-300 rounded-lg opacity-50">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Items Per Page -->
                    <div class="flex items-center space-x-2">
                        <label class="text-sm text-gray-600">Per page:</label>
                        <select onchange="window.location.href = '?page=1&per_page=' + this.value + '&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>'" 
                                class="px-3 py-2 text-sm font-medium bg-gray-50 text-gray-700 border border-gray-300 rounded-lg hover:border-teal-500 transition">
                            <option value="10" <?php echo $pagination['itemsPerPage'] == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $pagination['itemsPerPage'] == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $pagination['itemsPerPage'] == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $pagination['itemsPerPage'] == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
            let visibleCount = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm) || searchTerm === '') {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Update count badge if exists
            const countBadge = document.querySelector('.bg-blue-100.text-blue-800');
            if (countBadge && searchTerm) {
                const icon = countBadge.querySelector('i');
                countBadge.innerHTML = (icon ? icon.outerHTML : '<i class="fas fa-search mr-1"></i>') + 
                                      visibleCount + ' Found';
            }
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
        window.printEmployees = function() {
            window.print();
        };

        // Delete confirmation function
        window.confirmDelete = function(id, name) {
            if (confirm(`Are you sure you want to delete ${name}?\n\nThis action cannot be undone. The employee will only be deleted if they have no associated tickets.`)) {
                // Create and submit form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'customers.php?action=delete';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'id';
                input.value = id;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        };
    });
</script>

<?php 
// Include footer layout
include __DIR__ . '/../layouts/footer.php'; 
?>

