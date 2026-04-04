<?php 
// Set page-specific variables
$pageTitle = 'Employees - ' . APP_NAME;
$baseUrl = '../';

// Include header layout
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-slate-50">
    <?php
    // Set header variables for this page
    $headerTitle = 'Employees';
    $headerSubtitle = 'Manage registered employees · ' . count($employees) . ' Total';
    $showQuickActions = true;
    $showSearch = false; // Using custom search form below
    
    include __DIR__ . '/../../includes/top_header.php';
    ?>
    
    <!-- Custom Search Bar (below header) -->
    <div class="bg-white border-b border-gray-200 px-4 lg:px-8 py-3">
        <div class="flex items-center justify-between">
            <div class="flex-1 max-w-md relative">
                <form method="GET" action="" class="flex">
                    <div class="relative flex-1">
                        <input 
                            type="text" 
                            name="search"
                            placeholder="Search name, email..." 
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                            class="pl-10 pr-4 py-2 w-full border border-gray-300 bg-white text-gray-900 placeholder-gray-400 rounded-l-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all"
                            id="searchInput"
                        >
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-white border border-l-0 border-gray-300 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-r-lg transition">
                        <i class="fas fa-arrow-right text-sm"></i>
                    </button>
                </form>
            </div>
            
            <!-- Sync from Harley Button -->
            <button 
                onclick="openSyncModal()"
                class="ml-4 inline-flex items-center px-4 py-2 bg-emerald-600 text-white hover:bg-emerald-700 rounded-lg transition text-sm font-medium"
                title="Sync employees from Harley system"
            >
                <i class="fas fa-sync-alt mr-2"></i>
                Sync from Harley
            </button>
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
        <div class="mb-6 bg-emerald-50 border border-emerald-500/30 rounded-lg p-4 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <i class="fas fa-search text-emerald-600 text-lg"></i>
                <div>
                    <p class="text-teal-500 font-medium">
                        Found <span class="font-bold text-emerald-600"><?php echo $pagination['totalItems']; ?></span> result<?php echo $pagination['totalItems'] != 1 ? 's' : ''; ?> for "<span class="font-bold text-emerald-600"><?php echo htmlspecialchars($searchQuery); ?></span>"
                    </p>
                    <p class="text-emerald-600/70 text-sm mt-1">
                        Currently on page <span class="font-bold"><?php echo $pagination['currentPage']; ?></span> of <span class="font-bold"><?php echo max(1, $pagination['totalPages']); ?></span>
                    </p>
                </div>
            </div>
            <a href="customers.php<?php echo !empty($sortBy) ? '?sort_by=' . $sortBy . '&sort_order=' . $sortOrder : ''; ?>" class="px-4 py-2 bg-slate-50 hover:bg-slate-600 text-gray-700 hover:text-emerald-600 rounded-lg transition text-sm font-medium whitespace-nowrap ml-4">
                <i class="fas fa-times mr-2"></i>Clear Search
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Breadcrumb -->
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-emerald-600">
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
        <div class="bg-white border border-slate-200  overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-white border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wide">
                                <a href="<?php echo $sortUrl('fname'); ?>&page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>" class="hover:text-emerald-600 flex items-center group">
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
                                <a href="<?php echo $sortUrl('email'); ?>&page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>" class="hover:text-emerald-600 flex items-center group">
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
                                <a href="<?php echo $sortUrl('company'); ?>&page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>" class="hover:text-emerald-600 flex items-center group">
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
                                <a href="<?php echo $sortUrl('status'); ?>&page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>" class="hover:text-emerald-600 flex items-center group">
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
                                <a href="<?php echo $sortUrl('created_at'); ?>&page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>" class="hover:text-emerald-600 flex items-center group">
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
                        <tr class="hover:bg-slate-100/30 transition">
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
                                        <div class="font-medium text-slate-800"><?php echo htmlspecialchars($fullName); ?></div>
                                        <div class="text-sm text-slate-500">@<?php echo htmlspecialchars($employee['username']); ?></div>
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
                                <span class="px-3 py-1 text-xs font-medium border border-gray-300 bg-slate-100/30 text-gray-700"><?php echo ucfirst($employee['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">
                                <span class="time-ago" data-timestamp="<?php echo $employee['created_at']; ?>">
                                    <?php echo formatDate($employee['created_at'], 'M d, Y'); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <?php if (!empty($employee['employee_id'])): ?>
                                    <button onclick="syncEmployee('<?php echo htmlspecialchars($employee['employee_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($fullName, ENT_QUOTES); ?>')"
                                            class="inline-flex items-center px-3 py-1.5 bg-blue-50 border border-blue-300 text-blue-600 hover:bg-blue-100 hover:border-blue-400 transition rounded-lg text-sm"
                                            title="Sync data from Harley">
                                        <i class="fas fa-sync-alt mr-1.5"></i>
                                        Sync
                                    </button>
                                    <?php endif; ?>
                                    <a href="edit_employee.php?id=<?php echo $employee['id']; ?>" 
                                       class="inline-flex items-center px-3 py-1.5 bg-slate-50 border border-gray-300 text-gray-700 hover:bg-slate-100 hover:text-emerald-600 hover:border-emerald-500/50 transition rounded-lg text-sm"
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
            <div class="bg-white border-t border-slate-200 px-6 py-4">
                <div class="flex flex-col md:flex-row items-center justify-between gap-4">
                    <!-- Info Text -->
                    <div class="text-sm text-slate-500">
                        Showing <span class="font-medium text-slate-800"><?php echo $pagination['offset'] + 1; ?></span> 
                        to <span class="font-medium text-slate-800"><?php echo min($pagination['offset'] + $pagination['itemsPerPage'], $pagination['totalItems']); ?></span> 
                        of <span class="font-medium text-slate-800"><?php echo $pagination['totalItems']; ?></span> employees
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div class="flex items-center space-x-1">
                        <!-- Previous Button -->
                        <?php if ($pagination['hasPrevious']): ?>
                        <a href="?page=<?php echo $pagination['previousPage']; ?>&per_page=<?php echo $pagination['itemsPerPage']; ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-slate-800 hover:bg-slate-50 border border-gray-300 rounded-lg transition">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php else: ?>
                        <button disabled class="px-3 py-2 text-sm font-medium text-gray-500 bg-slate-100/20 border border-gray-300 rounded-lg opacity-50">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php 
                        $pages = $pagination['pages'];
                        if ($pages[0] > 1): 
                        ?>
                        <a href="?page=1&per_page=<?php echo $pagination['itemsPerPage']; ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-slate-800 hover:bg-slate-50 border border-gray-300 rounded-lg transition">1</a>
                        <?php if ($pages[0] > 2): ?>
                        <span class="px-2 text-slate-500">...</span>
                        <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php foreach ($pages as $page): ?>
                        <?php if ($page == $pagination['currentPage']): ?>
                        <button class="px-3 py-2 text-sm font-medium text-slate-800 bg-emerald-600/50 border border-emerald-600 rounded-lg">
                            <?php echo $page; ?>
                        </button>
                        <?php else: ?>
                        <a href="?page=<?php echo $page; ?>&per_page=<?php echo $pagination['itemsPerPage']; ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-slate-800 hover:bg-slate-50 border border-gray-300 rounded-lg transition">
                            <?php echo $page; ?>
                        </a>
                        <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php 
                        if ($pages[count($pages)-1] < $pagination['totalPages']): 
                        ?>
                        <?php if ($pages[count($pages)-1] < $pagination['totalPages'] - 1): ?>
                        <span class="px-2 text-slate-500">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $pagination['totalPages']; ?>&per_page=<?php echo $pagination['itemsPerPage']; ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-slate-800 hover:bg-slate-50 border border-gray-300 rounded-lg transition">
                            <?php echo $pagination['totalPages']; ?>
                        </a>
                        <?php endif; ?>
                        
                        <!-- Next Button -->
                        <?php if ($pagination['hasNext']): ?>
                        <a href="?page=<?php echo $pagination['nextPage']; ?>&per_page=<?php echo $pagination['itemsPerPage']; ?>&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>" 
                           class="px-3 py-2 text-sm font-medium text-gray-700 hover:text-slate-800 hover:bg-slate-50 border border-gray-300 rounded-lg transition">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php else: ?>
                        <button disabled class="px-3 py-2 text-sm font-medium text-gray-500 bg-slate-100/20 border border-gray-300 rounded-lg opacity-50">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Items Per Page -->
                    <div class="flex items-center space-x-2">
                        <label class="text-sm text-slate-500">Per page:</label>
                        <select onchange="window.location.href = '?page=1&per_page=' + this.value + '&sort_by=<?php echo $sortBy; ?>&sort_order=<?php echo $sortOrder; ?><?php if (!empty($searchQuery)): ?>&search=<?php echo urlencode($searchQuery); ?><?php endif; ?>'" 
                                class="px-3 py-2 text-sm font-medium bg-slate-50 text-gray-700 border border-gray-300 rounded-lg hover:border-emerald-500 transition">
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

<!-- Sync from Harley Modal -->
<div id="syncModal" class="fixed inset-0 z-50 hidden">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeSyncModal()"></div>
    
    <!-- Modal Content -->
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col" onclick="event.stopPropagation()">
            <!-- Modal Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Sync Employees from Harley</h3>
                    <p class="text-sm text-gray-500 mt-1">Import new employees or update existing ones</p>
                </div>
                <button onclick="closeSyncModal()" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <!-- Tabs -->
            <div class="border-b border-gray-200 px-6">
                <nav class="flex space-x-4" aria-label="Tabs">
                    <button onclick="switchSyncTab('new')" id="tabNew" 
                            class="sync-tab px-4 py-3 text-sm font-medium border-b-2 border-emerald-500 text-emerald-600">
                        <i class="fas fa-user-plus mr-2"></i>New Employees
                    </button>
                    <button onclick="switchSyncTab('updates')" id="tabUpdates"
                            class="sync-tab px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700">
                        <i class="fas fa-sync mr-2"></i>Check Updates
                        <span id="updatesCountBadge" class="hidden ml-1 px-2 py-0.5 text-xs bg-orange-100 text-orange-600 rounded-full">0</span>
                    </button>
                </nav>
            </div>
            
            <!-- Modal Body -->
            <div class="flex-1 overflow-auto p-6" id="syncModalBody">
                <!-- Loading State -->
                <div id="syncLoading" class="flex flex-col items-center justify-center py-12">
                    <div class="w-12 h-12 border-4 border-emerald-200 border-t-emerald-600 rounded-full animate-spin mb-4"></div>
                    <p class="text-gray-600">Fetching employees from Harley...</p>
                </div>
                
                <!-- Error State -->
                <div id="syncError" class="hidden">
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                        <i class="fas fa-exclamation-circle text-red-500 text-3xl mb-3"></i>
                        <p class="text-red-700 font-medium" id="syncErrorMessage">Failed to fetch employees</p>
                        <button onclick="fetchNewEmployees()" class="mt-3 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                            <i class="fas fa-redo mr-2"></i>Try Again
                        </button>
                    </div>
                </div>
                
                <!-- Tab: New Employees -->
                <div id="tabContentNew">
                    <!-- Results State -->
                    <div id="syncResults" class="hidden">
                        <!-- Summary Stats -->
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <p class="text-2xl font-bold text-gray-900" id="statHarleyTotal">0</p>
                                <p class="text-sm text-gray-500">Total in Harley</p>
                            </div>
                            <div class="bg-blue-50 rounded-lg p-4 text-center">
                                <p class="text-2xl font-bold text-blue-600" id="statAlreadySynced">0</p>
                                <p class="text-sm text-gray-500">Already Synced</p>
                            </div>
                            <div class="bg-emerald-50 rounded-lg p-4 text-center">
                                <p class="text-2xl font-bold text-emerald-600" id="statNewCount">0</p>
                                <p class="text-sm text-gray-500">New Employees</p>
                            </div>
                        </div>
                        
                        <!-- No New Employees Message -->
                        <div id="noNewEmployees" class="hidden text-center py-8">
                            <i class="fas fa-check-circle text-emerald-500 text-5xl mb-4"></i>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">All Synced!</h4>
                            <p class="text-gray-500">All employees from Harley are already in the system.</p>
                        </div>
                        
                        <!-- New Employees Table -->
                        <div id="newEmployeesTable" class="hidden">
                            <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <input type="checkbox" id="selectAllEmployees" class="w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500" onchange="toggleSelectAll(this.checked)">
                                <label for="selectAllEmployees" class="text-sm font-medium text-gray-700">Select All (<span id="selectedCount">0</span> selected)</label>
                            </div>
                            <button onclick="fetchNewEmployees()" class="px-3 py-1.5 text-sm text-gray-600 hover:text-emerald-600 hover:bg-gray-100 rounded transition">
                                <i class="fas fa-sync-alt mr-1"></i>Refresh
                            </button>
                        </div>
                        
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <div class="max-h-[400px] overflow-y-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 sticky top-0">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12"></th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                                        </tr>
                                    </thead>
                                    <tbody id="newEmployeesBody" class="divide-y divide-gray-200">
                                        <!-- Populated via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Import Results State -->
                <div id="importResults" class="hidden">
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle text-emerald-500 text-5xl mb-4"></i>
                        <h4 class="text-lg font-semibold text-gray-900 mb-2">Import Complete!</h4>
                    </div>
                    
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-emerald-50 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-emerald-600" id="importedCount">0</p>
                            <p class="text-sm text-gray-500">Imported</p>
                        </div>
                        <div class="bg-yellow-50 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-yellow-600" id="skippedCount">0</p>
                            <p class="text-sm text-gray-500">Skipped</p>
                        </div>
                        <div class="bg-red-50 rounded-lg p-4 text-center">
                            <p class="text-2xl font-bold text-red-600" id="failedCount">0</p>
                            <p class="text-sm text-gray-500">Failed</p>
                        </div>
                    </div>
                    
                    <!-- Import Details -->
                    <div id="importDetails" class="space-y-3 max-h-[300px] overflow-y-auto">
                        <!-- Populated via JavaScript -->
                    </div>
                </div>
                </div><!-- End tabContentNew -->
                
                <!-- Tab: Updates for Existing Employees -->
                <div id="tabContentUpdates" class="hidden">
                    <!-- Updates Loading -->
                    <div id="updatesLoading" class="flex flex-col items-center justify-center py-12">
                        <div class="w-12 h-12 border-4 border-blue-200 border-t-blue-600 rounded-full animate-spin mb-4"></div>
                        <p class="text-gray-600">Checking for updates...</p>
                    </div>
                    
                    <!-- Updates Results -->
                    <div id="updatesResults" class="hidden">
                        <!-- Stats -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div class="bg-blue-50 rounded-lg p-4 text-center">
                                <p class="text-2xl font-bold text-blue-600" id="statExistingCount">0</p>
                                <p class="text-sm text-gray-500">Synced Employees</p>
                            </div>
                            <div class="bg-orange-50 rounded-lg p-4 text-center">
                                <p class="text-2xl font-bold text-orange-600" id="statWithChanges">0</p>
                                <p class="text-sm text-gray-500">With Changes</p>
                            </div>
                        </div>
                        
                        <!-- No Updates Message -->
                        <div id="noUpdates" class="hidden text-center py-8">
                            <i class="fas fa-check-circle text-blue-500 text-5xl mb-4"></i>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">All Up to Date!</h4>
                            <p class="text-gray-500">All existing employees match their Harley data.</p>
                        </div>
                        
                        <!-- Updates Table -->
                        <div id="updatesTable" class="hidden">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-3">
                                    <input type="checkbox" id="selectAllUpdates" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" onchange="toggleSelectAllUpdates(this.checked)">
                                    <label for="selectAllUpdates" class="text-sm font-medium text-gray-700">Select All (<span id="selectedUpdatesCount">0</span> selected)</label>
                                </div>
                                <button onclick="fetchUpdates()" class="px-3 py-1.5 text-sm text-gray-600 hover:text-blue-600 hover:bg-gray-100 rounded transition">
                                    <i class="fas fa-sync-alt mr-1"></i>Refresh
                                </button>
                            </div>
                            
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <div class="max-h-[400px] overflow-y-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50 sticky top-0">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12"></th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Changes</th>
                                            </tr>
                                        </thead>
                                        <tbody id="updatesBody" class="divide-y divide-gray-200">
                                            <!-- Populated via JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bulk Update Results -->
                    <div id="bulkUpdateResults" class="hidden">
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle text-blue-500 text-5xl mb-4"></i>
                            <h4 class="text-lg font-semibold text-gray-900 mb-2">Update Complete!</h4>
                        </div>
                        
                        <div class="grid grid-cols-3 gap-4 mb-6">
                            <div class="bg-blue-50 rounded-lg p-4 text-center">
                                <p class="text-2xl font-bold text-blue-600" id="bulkUpdatedCount">0</p>
                                <p class="text-sm text-gray-500">Updated</p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4 text-center">
                                <p class="text-2xl font-bold text-gray-600" id="bulkSkippedCount">0</p>
                                <p class="text-sm text-gray-500">No Changes</p>
                            </div>
                            <div class="bg-red-50 rounded-lg p-4 text-center">
                                <p class="text-2xl font-bold text-red-600" id="bulkFailedCount">0</p>
                                <p class="text-sm text-gray-500">Failed</p>
                            </div>
                        </div>
                        
                        <div id="bulkUpdateDetails" class="space-y-3 max-h-[300px] overflow-y-auto">
                            <!-- Populated via JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50">
                <p class="text-sm text-gray-500" id="syncTimestamp"></p>
                <div class="flex items-center space-x-3">
                    <button onclick="closeSyncModal()" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition text-sm font-medium">
                        Close
                    </button>
                    <button onclick="importSelectedEmployees()" id="importBtn" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed hidden">
                        <i class="fas fa-download mr-2"></i>Import Selected (<span id="importBtnCount">0</span>)
                    </button>
                    <button onclick="bulkUpdateSelected()" id="updateBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium disabled:opacity-50 disabled:cursor-not-allowed hidden">
                        <i class="fas fa-sync mr-2"></i>Update Selected (<span id="updateBtnCount">0</span>)
                    </button>
                    <button onclick="location.reload()" id="refreshPageBtn" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-medium hidden">
                        <i class="fas fa-check mr-2"></i>Done - Refresh Page
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sync Modal JavaScript -->
<script>
let newEmployeesData = [];
let selectedEmployeeIds = new Set();
let updatesData = [];
let selectedUpdateIds = new Set();
let currentTab = 'new';

function switchSyncTab(tab) {
    currentTab = tab;
    
    // Update tab buttons
    document.querySelectorAll('.sync-tab').forEach(btn => {
        btn.classList.remove('border-emerald-500', 'text-emerald-600', 'border-blue-500', 'text-blue-600');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    const activeTab = document.getElementById(tab === 'new' ? 'tabNew' : 'tabUpdates');
    activeTab.classList.remove('border-transparent', 'text-gray-500');
    activeTab.classList.add(tab === 'new' ? 'border-emerald-500' : 'border-blue-500', 
                           tab === 'new' ? 'text-emerald-600' : 'text-blue-600');
    
    // Show/hide content
    document.getElementById('tabContentNew').classList.toggle('hidden', tab !== 'new');
    document.getElementById('tabContentUpdates').classList.toggle('hidden', tab !== 'updates');
    
    // Show/hide footer buttons
    document.getElementById('importBtn').classList.toggle('hidden', tab !== 'new' || newEmployeesData.length === 0);
    document.getElementById('updateBtn').classList.toggle('hidden', tab !== 'updates' || updatesData.length === 0);
    
    // Fetch data for updates tab if switching to it
    if (tab === 'updates' && updatesData.length === 0) {
        fetchUpdates();
    }
}

function openSyncModal() {
    document.getElementById('syncModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    currentTab = 'new';
    switchSyncTab('new');
    fetchNewEmployees();
}

function closeSyncModal() {
    document.getElementById('syncModal').classList.add('hidden');
    document.body.style.overflow = '';
}

async function fetchNewEmployees() {
    // Show loading, hide others
    document.getElementById('syncLoading').classList.remove('hidden');
    document.getElementById('syncError').classList.add('hidden');
    document.getElementById('syncResults').classList.add('hidden');
    document.getElementById('importResults').classList.add('hidden');
    document.getElementById('importBtn').classList.add('hidden');
    document.getElementById('refreshPageBtn').classList.add('hidden');
    
    try {
        const response = await fetch('../api/sync_employees.php?action=preview');
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Failed to fetch employees');
        }
        
        // Update stats
        document.getElementById('statHarleyTotal').textContent = data.harley_total || 0;
        document.getElementById('statAlreadySynced').textContent = data.already_synced || 0;
        document.getElementById('statNewCount').textContent = data.new_count || 0;
        document.getElementById('syncTimestamp').textContent = 'Last checked: ' + data.timestamp;
        
        // Store data
        newEmployeesData = data.new_employees || [];
        selectedEmployeeIds.clear();
        
        // Show results
        document.getElementById('syncLoading').classList.add('hidden');
        document.getElementById('syncResults').classList.remove('hidden');
        
        if (newEmployeesData.length === 0) {
            document.getElementById('noNewEmployees').classList.remove('hidden');
            document.getElementById('newEmployeesTable').classList.add('hidden');
        } else {
            document.getElementById('noNewEmployees').classList.add('hidden');
            document.getElementById('newEmployeesTable').classList.remove('hidden');
            document.getElementById('importBtn').classList.remove('hidden');
            renderEmployeesTable();
        }
        
    } catch (error) {
        document.getElementById('syncLoading').classList.add('hidden');
        document.getElementById('syncError').classList.remove('hidden');
        document.getElementById('syncErrorMessage').textContent = error.message;
    }
}

function renderEmployeesTable() {
    const tbody = document.getElementById('newEmployeesBody');
    tbody.innerHTML = '';
    
    newEmployeesData.forEach(emp => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50';
        tr.innerHTML = `
            <td class="px-4 py-3">
                <input type="checkbox" 
                       class="employee-checkbox w-4 h-4 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500"
                       value="${emp.harley_id}"
                       onchange="toggleEmployee(${emp.harley_id}, this.checked)"
                       ${selectedEmployeeIds.has(emp.harley_id) ? 'checked' : ''}>
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center">
                    <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(emp.full_name)}&background=000000&color=06b6d4" 
                         alt="${escapeHtml(emp.full_name)}" 
                         class="w-8 h-8 rounded-full mr-3">
                    <div>
                        <div class="font-medium text-gray-900">${escapeHtml(emp.full_name)}</div>
                        <div class="text-xs text-gray-500">@${escapeHtml(emp.username || '')}</div>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-600">${escapeHtml(emp.email || 'N/A')}</td>
            <td class="px-4 py-3 text-sm text-gray-600">${escapeHtml(emp.company || 'N/A')}</td>
            <td class="px-4 py-3 text-sm text-gray-600">${escapeHtml(emp.position || 'N/A')}</td>
        `;
        tbody.appendChild(tr);
    });
    
    updateSelectedCount();
}

function toggleEmployee(harleyId, checked) {
    if (checked) {
        selectedEmployeeIds.add(harleyId);
    } else {
        selectedEmployeeIds.delete(harleyId);
    }
    updateSelectedCount();
}

function toggleSelectAll(checked) {
    selectedEmployeeIds.clear();
    
    if (checked) {
        newEmployeesData.forEach(emp => selectedEmployeeIds.add(emp.harley_id));
    }
    
    document.querySelectorAll('.employee-checkbox').forEach(cb => {
        cb.checked = checked;
    });
    
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = selectedEmployeeIds.size;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('importBtnCount').textContent = count;
    
    const importBtn = document.getElementById('importBtn');
    importBtn.disabled = count === 0;
    
    // Update select all checkbox state
    const selectAll = document.getElementById('selectAllEmployees');
    if (newEmployeesData.length > 0) {
        selectAll.checked = count === newEmployeesData.length;
        selectAll.indeterminate = count > 0 && count < newEmployeesData.length;
    }
}

async function importSelectedEmployees() {
    if (selectedEmployeeIds.size === 0) {
        alert('Please select at least one employee to import');
        return;
    }
    
    const importBtn = document.getElementById('importBtn');
    importBtn.disabled = true;
    importBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Importing...';
    
    try {
        const response = await fetch('../api/sync_employees.php?action=import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                employee_ids: Array.from(selectedEmployeeIds)
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Import failed');
        }
        
        // Show import results
        document.getElementById('syncResults').classList.add('hidden');
        document.getElementById('importResults').classList.remove('hidden');
        document.getElementById('importBtn').classList.add('hidden');
        document.getElementById('refreshPageBtn').classList.remove('hidden');
        
        // Update counts
        document.getElementById('importedCount').textContent = data.summary.imported || 0;
        document.getElementById('skippedCount').textContent = data.summary.skipped || 0;
        document.getElementById('failedCount').textContent = data.summary.failed || 0;
        
        // Show details
        const detailsDiv = document.getElementById('importDetails');
        detailsDiv.innerHTML = '';
        
        if (data.imported && data.imported.length > 0) {
            data.imported.forEach(emp => {
                detailsDiv.innerHTML += `
                    <div class="flex items-center p-3 bg-emerald-50 rounded-lg">
                        <i class="fas fa-check-circle text-emerald-500 mr-3"></i>
                        <div>
                            <span class="font-medium text-gray-900">${escapeHtml(emp.name)}</span>
                            <span class="text-gray-500 text-sm ml-2">${escapeHtml(emp.email)}</span>
                        </div>
                    </div>
                `;
            });
        }
        
        if (data.skipped && data.skipped.length > 0) {
            data.skipped.forEach(emp => {
                detailsDiv.innerHTML += `
                    <div class="flex items-center p-3 bg-yellow-50 rounded-lg">
                        <i class="fas fa-minus-circle text-yellow-500 mr-3"></i>
                        <div>
                            <span class="font-medium text-gray-900">${escapeHtml(emp.name)}</span>
                            <span class="text-gray-500 text-sm ml-2">- ${escapeHtml(emp.reason)}</span>
                        </div>
                    </div>
                `;
            });
        }
        
        if (data.failed && data.failed.length > 0) {
            data.failed.forEach(emp => {
                detailsDiv.innerHTML += `
                    <div class="flex items-center p-3 bg-red-50 rounded-lg">
                        <i class="fas fa-times-circle text-red-500 mr-3"></i>
                        <div>
                            <span class="font-medium text-gray-900">${escapeHtml(emp.name)}</span>
                            <span class="text-red-500 text-sm ml-2">- ${escapeHtml(emp.error)}</span>
                        </div>
                    </div>
                `;
            });
        }
        
    } catch (error) {
        alert('Import failed: ' + error.message);
        importBtn.disabled = false;
        importBtn.innerHTML = '<i class="fas fa-download mr-2"></i>Import Selected (<span id="importBtnCount">' + selectedEmployeeIds.size + '</span>)';
    }
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Single employee sync function
async function syncEmployee(employeeId, employeeName) {
    if (!confirm(`Sync data for ${employeeName} from Harley?\n\nThis will update their information (name, email, password, position, etc.) with the latest data from Harley.`)) {
        return;
    }
    
    // Find and disable the sync button
    const buttons = document.querySelectorAll(`button[onclick*="syncEmployee('${employeeId}'"]`);
    buttons.forEach(btn => {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1.5"></i>Syncing...';
    });
    
    try {
        const response = await fetch('../api/sync_employees.php?action=sync_one', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ employee_id: employeeId })
        });
        
        const data = await response.json();
        
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Sync failed');
        }
        
        // Show success message
        if (data.changes_count > 0) {
            // Build changes summary
            let changesText = data.changes.map(c => `• ${c.field}: "${c.old}" → "${c.new}"`).join('\n');
            alert(`✅ ${employeeName} synced successfully!\n\n${data.changes_count} field(s) updated:\n${changesText}\n\nRefreshing page...`);
            location.reload();
        } else {
            alert(`✅ ${employeeName} is already up to date.\n\nNo changes detected from Harley.`);
            // Re-enable button
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-sync-alt mr-1.5"></i>Sync';
            });
        }
        
    } catch (error) {
        alert(`❌ Sync failed for ${employeeName}:\n\n${error.message}`);
        // Re-enable button
        buttons.forEach(btn => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync-alt mr-1.5"></i>Sync';
        });
    }
}

// === UPDATE TAB FUNCTIONS ===

async function fetchUpdates() {
    // Show loading
    document.getElementById('updatesLoading').classList.remove('hidden');
    document.getElementById('updatesResults').classList.add('hidden');
    document.getElementById('bulkUpdateResults').classList.add('hidden');
    document.getElementById('updateBtn').classList.add('hidden');
    
    try {
        const response = await fetch('../api/sync_employees.php?action=preview_updates');
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Failed to check for updates');
        }
        
        // Update stats
        document.getElementById('statExistingCount').textContent = data.existing_count || 0;
        document.getElementById('statWithChanges').textContent = data.with_changes || 0;
        document.getElementById('syncTimestamp').textContent = 'Last checked: ' + data.timestamp;
        
        // Update badge on tab
        const badge = document.getElementById('updatesCountBadge');
        if (data.with_changes > 0) {
            badge.textContent = data.with_changes;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
        
        // Store data
        updatesData = data.employees || [];
        selectedUpdateIds.clear();
        
        // Show results
        document.getElementById('updatesLoading').classList.add('hidden');
        document.getElementById('updatesResults').classList.remove('hidden');
        
        if (updatesData.length === 0) {
            document.getElementById('noUpdates').classList.remove('hidden');
            document.getElementById('updatesTable').classList.add('hidden');
        } else {
            document.getElementById('noUpdates').classList.add('hidden');
            document.getElementById('updatesTable').classList.remove('hidden');
            document.getElementById('updateBtn').classList.remove('hidden');
            renderUpdatesTable();
        }
        
    } catch (error) {
        document.getElementById('updatesLoading').classList.add('hidden');
        alert('Failed to check for updates: ' + error.message);
    }
}

function renderUpdatesTable() {
    const tbody = document.getElementById('updatesBody');
    tbody.innerHTML = '';
    
    updatesData.forEach(emp => {
        const changesHtml = emp.changes.map(c => 
            `<span class="inline-block px-2 py-1 text-xs bg-orange-100 text-orange-700 rounded mr-1 mb-1">
                ${escapeHtml(c.field)}: "${escapeHtml(c.hdesk)}" → "${escapeHtml(c.harley)}"
            </span>`
        ).join('');
        
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50';
        tr.innerHTML = `
            <td class="px-4 py-3">
                <input type="checkbox" 
                       class="update-checkbox w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                       value="${emp.employee_id}"
                       onchange="toggleUpdate('${emp.employee_id}', this.checked)"
                       ${selectedUpdateIds.has(emp.employee_id) ? 'checked' : ''}>
            </td>
            <td class="px-4 py-3">
                <div class="flex items-center">
                    <img src="https://ui-avatars.com/api/?name=${encodeURIComponent(emp.full_name)}&background=000000&color=3b82f6" 
                         alt="${escapeHtml(emp.full_name)}" 
                         class="w-8 h-8 rounded-full mr-3">
                    <div>
                        <div class="font-medium text-gray-900">${escapeHtml(emp.full_name)}</div>
                        <div class="text-xs text-gray-500">${escapeHtml(emp.email || '')}</div>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3">
                <div class="flex flex-wrap">${changesHtml}</div>
            </td>
        `;
        tbody.appendChild(tr);
    });
    
    updateSelectedUpdatesCount();
}

function toggleUpdate(employeeId, checked) {
    if (checked) {
        selectedUpdateIds.add(employeeId);
    } else {
        selectedUpdateIds.delete(employeeId);
    }
    updateSelectedUpdatesCount();
}

function toggleSelectAllUpdates(checked) {
    selectedUpdateIds.clear();
    
    if (checked) {
        updatesData.forEach(emp => selectedUpdateIds.add(emp.employee_id));
    }
    
    document.querySelectorAll('.update-checkbox').forEach(cb => {
        cb.checked = checked;
    });
    
    updateSelectedUpdatesCount();
}

function updateSelectedUpdatesCount() {
    const count = selectedUpdateIds.size;
    document.getElementById('selectedUpdatesCount').textContent = count;
    document.getElementById('updateBtnCount').textContent = count;
    
    const updateBtn = document.getElementById('updateBtn');
    updateBtn.disabled = count === 0;
    
    // Update select all checkbox state
    const selectAll = document.getElementById('selectAllUpdates');
    if (updatesData.length > 0) {
        selectAll.checked = count === updatesData.length;
        selectAll.indeterminate = count > 0 && count < updatesData.length;
    }
}

async function bulkUpdateSelected() {
    if (selectedUpdateIds.size === 0) {
        alert('Please select at least one employee to update');
        return;
    }
    
    const updateBtn = document.getElementById('updateBtn');
    updateBtn.disabled = true;
    updateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Updating...';
    
    try {
        const response = await fetch('../api/sync_employees.php?action=bulk_update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                employee_ids: Array.from(selectedUpdateIds)
            })
        });
        
        const data = await response.json();
        
        if (!response.ok) {
            throw new Error(data.error || 'Bulk update failed');
        }
        
        // Show results
        document.getElementById('updatesResults').classList.add('hidden');
        document.getElementById('bulkUpdateResults').classList.remove('hidden');
        document.getElementById('updateBtn').classList.add('hidden');
        document.getElementById('refreshPageBtn').classList.remove('hidden');
        
        // Update counts
        document.getElementById('bulkUpdatedCount').textContent = data.summary.updated || 0;
        document.getElementById('bulkSkippedCount').textContent = data.summary.skipped || 0;
        document.getElementById('bulkFailedCount').textContent = data.summary.failed || 0;
        
        // Show details
        const detailsDiv = document.getElementById('bulkUpdateDetails');
        detailsDiv.innerHTML = '';
        
        if (data.updated && data.updated.length > 0) {
            data.updated.forEach(emp => {
                detailsDiv.innerHTML += `
                    <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                        <i class="fas fa-check-circle text-blue-500 mr-3"></i>
                        <div>
                            <span class="font-medium text-gray-900">${escapeHtml(emp.name)}</span>
                            <span class="text-gray-500 text-sm ml-2">${emp.changes_count} field(s) updated</span>
                        </div>
                    </div>
                `;
            });
        }
        
        if (data.skipped && data.skipped.length > 0) {
            data.skipped.forEach(emp => {
                detailsDiv.innerHTML += `
                    <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                        <i class="fas fa-minus-circle text-gray-400 mr-3"></i>
                        <div>
                            <span class="font-medium text-gray-900">${escapeHtml(emp.name)}</span>
                            <span class="text-gray-500 text-sm ml-2">- ${escapeHtml(emp.reason)}</span>
                        </div>
                    </div>
                `;
            });
        }
        
        if (data.failed && data.failed.length > 0) {
            data.failed.forEach(emp => {
                detailsDiv.innerHTML += `
                    <div class="flex items-center p-3 bg-red-50 rounded-lg">
                        <i class="fas fa-times-circle text-red-500 mr-3"></i>
                        <div>
                            <span class="font-medium text-gray-900">Employee ID: ${escapeHtml(emp.employee_id)}</span>
                            <span class="text-red-500 text-sm ml-2">- ${escapeHtml(emp.error)}</span>
                        </div>
                    </div>
                `;
            });
        }
        
    } catch (error) {
        alert('Bulk update failed: ' + error.message);
        updateBtn.disabled = false;
        updateBtn.innerHTML = '<i class="fas fa-sync mr-2"></i>Update Selected (<span id="updateBtnCount">' + selectedUpdateIds.size + '</span>)';
    }
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && !document.getElementById('syncModal').classList.contains('hidden')) {
        closeSyncModal();
    }
});
</script>

<?php 
// Include footer layout
include __DIR__ . '/../layouts/footer.php'; 
?>