<?php 
// Set page-specific variables
$pageTitle = 'Employees - IT Help Desk';
$baseUrl = '../';

// Include header layout
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Top Bar -->
    <div class="bg-slate-800/50 border-b border-slate-700/50 backdrop-blur-md">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <!-- Left Section: Title & Stats -->
            <div class="flex items-center space-x-4">
                <div class="hidden lg:flex items-center justify-center w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded-lg">
                    <i class="fas fa-users text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-white">
                        Employees
                    </h1>
                    <div class="flex items-center space-x-3 mt-0.5">
                        <p class="text-sm text-slate-400">Manage registered employees</p>
                        <span class="hidden md:inline-flex items-center px-2 py-0.5 text-xs font-medium border border-slate-600 text-slate-300 bg-slate-700/30 rounded">
                            <i class="fas fa-users mr-1"></i>
                            <?php echo count($employees); ?> Total
                        </span>
                    </div>
                </div>
            </div>

            <!-- Right Section: Actions & User -->
            <div class="flex items-center space-x-3">
                <!-- Search (Hidden on Mobile) -->
                <div class="hidden md:block relative">
                    <input 
                        type="text" 
                        placeholder="Search employees..." 
                        class="pl-10 pr-4 py-2 w-48 lg:w-64 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm transition-all"
                        id="quickSearch"
                    >
                    <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-sm"></i>
                </div>

                <!-- Quick Actions Dropdown -->
                <div class="relative" id="quickActionsDropdown">
                    <button class="flex items-center space-x-2 px-4 py-2 border border-slate-600 bg-slate-700/50 text-slate-300 hover:text-white hover:border-cyan-500/50 rounded-lg transition" id="quickActionsBtn">
                        <i class="fas fa-bolt text-cyan-500"></i>
                        <span class="hidden lg:inline text-sm font-medium">Quick Actions</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-56 bg-slate-800 rounded-lg shadow-xl border border-slate-700/50 hidden z-50" id="quickActionsMenu">
                        <div class="py-2">
                            <a href="add_employee.php" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition">
                                <i class="fas fa-user-plus w-5"></i>
                                <span class="ml-3">Add Employee</span>
                            </a>
                            <a href="export_employees.php" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition">
                                <i class="fas fa-file-excel w-5 text-green-400"></i>
                                <span class="ml-3">Export to Excel</span>
                            </a>
                            <div class="border-t border-slate-700/50 my-1"></div>
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition" onclick="printEmployees(); return false;">
                                <i class="fas fa-print w-5"></i>
                                <span class="ml-3">Print View</span>
                            </a>
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition">
                                <i class="fas fa-filter w-5"></i>
                                <span class="ml-3">Filter Options</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Notifications Bell -->
                <button class="relative p-2 text-slate-400 hover:text-white hover:bg-slate-700/50 rounded-lg transition" title="Notifications" id="notificationBell">
                    <i class="far fa-bell text-lg"></i>
                    <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                </button>

                <!-- User Avatar with Dropdown -->
                <div class="relative" id="userMenuDropdown">
                    <button class="flex items-center space-x-2 p-1 hover:bg-slate-700/50 transition" id="userMenuBtn">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=06b6d4" 
                             alt="User" 
                             class="w-10 h-10 rounded-full"
                             title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                        <div class="hidden lg:block text-left">
                            <div class="text-sm font-medium text-white"><?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?></div>
                            <div class="text-xs text-slate-400"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>
                        <i class="fas fa-chevron-down text-xs text-slate-400 hidden lg:block"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-64 bg-slate-800 border border-slate-700/50 hidden z-50" id="userMenu">
                        <div class="p-4 border-b border-slate-700/50">
                            <div class="font-medium text-white"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                            <div class="text-sm text-slate-400"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                        </div>
                        <div class="py-2">
                            <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition">
                                <i class="fas fa-user w-5"></i>
                                <span class="ml-3">My Profile</span>
                            </a>
                            <a href="settings.php" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition">
                                <i class="fas fa-cog w-5"></i>
                                <span class="ml-3">Settings</span>
                            </a>
                            <div class="border-t border-slate-700/50 my-1"></div>
                            <a href="../logout.php" class="flex items-center px-4 py-2 text-sm text-red-400 hover:bg-slate-700/50 transition">
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
                    class="w-full pl-10 pr-4 py-2 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm"
                    id="mobileQuickSearch"
                >
                <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-sm"></i>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="p-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-slate-400 hover:text-cyan-400">
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
                        <span class="ml-1 text-sm font-medium text-slate-300">Employees</span>
                    </div>
                </li>
            </ol>
        </nav>
        
        <!-- Employees Table -->
        <div class="bg-slate-800/50 border border-slate-700/50 backdrop-blur-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50 border-b border-slate-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wide">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wide">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wide">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wide">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wide">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wide">Joined</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-700/50">
                        <?php foreach ($employees as $employee): ?>
                        <tr class="hover:bg-slate-700/30 transition">
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
                                        <div class="font-medium text-white"><?php echo htmlspecialchars($fullName); ?></div>
                                        <div class="text-sm text-slate-400">@<?php echo htmlspecialchars($employee['username']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-300"><?php echo htmlspecialchars($employee['email']); ?></td>
                            <td class="px-6 py-4 text-sm text-slate-300"><?php echo htmlspecialchars($employee['company'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4 text-sm text-slate-300"><?php echo htmlspecialchars($employee['contact'] ?? 'N/A'); ?></td>
                            <td class="px-6 py-4">
                                <?php if ($employee['status'] === 'active'): ?>
                                <span class="px-3 py-1 text-xs font-medium border border-emerald-600/50 bg-emerald-600/20 text-emerald-400">Active</span>
                                <?php else: ?>
                                <span class="px-3 py-1 text-xs font-medium border border-slate-600 bg-slate-700/30 text-slate-300"><?php echo ucfirst($employee['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-400">
                                <span class="time-ago" data-timestamp="<?php echo $employee['created_at']; ?>">
                                    <?php echo formatDate($employee['created_at'], 'M d, Y'); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
    });
</script>

<?php 
// Include footer layout
include __DIR__ . '/../layouts/footer.php'; 
?>

