<?php 
// Set page-specific variables
$pageTitle = 'Categories - IT Help Desk';
$baseUrl = '../';

// Include header layout
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen">
    <!-- Enhanced Top Bar -->
    <div class="bg-gradient-to-r from-white to-blue-50 shadow-sm border-b border-blue-100">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <!-- Left Section: Title & Stats -->
            <div class="flex items-center space-x-4">
                <div class="hidden lg:flex items-center justify-center w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg">
                    <i class="fas fa-folder-open text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-gray-900 to-blue-600 bg-clip-text text-transparent">
                        Ticket Categories
                    </h1>
                    <div class="flex items-center space-x-3 mt-1">
                        <p class="text-sm text-gray-600">Organize and manage ticket categories</p>
                        <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-layer-group mr-1"></i>
                            <?php echo count($categories); ?> Categories
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
                        placeholder="Search categories..." 
                        class="pl-10 pr-4 py-2 w-48 lg:w-64 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all"
                        id="quickSearch"
                    >
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                </div>

                <!-- Dark Mode Toggle -->
                <button id="darkModeToggle" class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition" title="Toggle dark mode">
                    <i id="dark-mode-icon" class="fas fa-moon"></i>
                </button>

                <!-- Quick Actions Dropdown -->
                <div class="relative" id="quickActionsDropdown">
                    <button class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition" id="quickActionsBtn">
                        <i class="fas fa-bolt text-blue-600"></i>
                        <span class="hidden lg:inline text-sm font-medium">Quick Actions</span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50" id="quickActionsMenu">
                        <div class="py-2">
                            <a href="add_category.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                <i class="fas fa-plus-circle w-5"></i>
                                <span class="ml-3">Add Category</span>
                            </a>
                            <a href="manage_categories.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                <i class="fas fa-edit w-5"></i>
                                <span class="ml-3">Manage All</span>
                            </a>
                            <div class="border-t border-gray-200 my-1"></div>
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition" onclick="viewCategoryStats(); return false;">
                                <i class="fas fa-chart-bar w-5"></i>
                                <span class="ml-3">View Statistics</span>
                            </a>
                            <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition" onclick="printCategories(); return false;">
                                <i class="fas fa-print w-5"></i>
                                <span class="ml-3">Print View</span>
                            </a>
                        </div>
                    </div>
                </div>

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
                    placeholder="Search categories..." 
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
                        <span class="ml-1 text-sm font-medium text-gray-700">Categories</span>
                    </div>
                </li>
            </ol>
        </nav>
        
        <!-- Category Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($categories as $category): ?>
            <div class="bg-white rounded-xl shadow-sm p-6 hover:shadow-md transition">
                <div class="flex items-start justify-between mb-4">
                    <div class="w-12 h-12 rounded-lg flex items-center justify-center" style="background-color: <?php echo $category['color']; ?>20;">
                        <i class="fas fa-folder text-xl" style="color: <?php echo $category['color']; ?>;"></i>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <?php echo $category['ticket_count']; ?> tickets
                    </span>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($category['name']); ?></h3>
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600">Open Tickets:</span>
                    <span class="font-semibold text-gray-900"><?php echo $category['open_tickets']; ?></span>
                </div>
            </div>
            <?php endforeach; ?>
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
        document.addEventListener('click', function() {
            if (quickActionsMenu) quickActionsMenu.classList.add('hidden');
            if (userMenu) userMenu.classList.add('hidden');
        });

        // Quick Search Functionality
        const quickSearch = document.getElementById('quickSearch');
        const mobileQuickSearch = document.getElementById('mobileQuickSearch');
        
        function handleQuickSearch(searchValue) {
            const searchTerm = searchValue.toLowerCase().trim();
            const categoryCards = document.querySelectorAll('.grid > div');
            let visibleCount = 0;
            
            categoryCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchTerm) || searchTerm === '') {
                    card.style.display = '';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Update count badge if exists
            const countBadge = document.querySelector('.bg-blue-100.text-blue-800');
            if (countBadge && searchTerm) {
                const icon = countBadge.querySelector('i');
                countBadge.innerHTML = (icon ? icon.outerHTML : '<i class="fas fa-search mr-1"></i>') + 
                                      visibleCount + ' Found';
            } else if (countBadge && !searchTerm) {
                // Reset to original count
                countBadge.innerHTML = '<i class="fas fa-layer-group mr-1"></i><?php echo count($categories); ?> Categories';
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
        window.printCategories = function() {
            window.print();
        };

        // View Statistics function
        window.viewCategoryStats = function() {
            // Calculate stats
            const categoryCards = document.querySelectorAll('.grid > div');
            let totalTickets = 0;
            let totalOpen = 0;
            
            categoryCards.forEach(card => {
                const ticketCount = card.querySelector('.bg-blue-100').textContent.match(/\d+/);
                const openCount = card.querySelector('.font-semibold.text-gray-900').textContent;
                
                if (ticketCount) totalTickets += parseInt(ticketCount[0]);
                if (openCount) totalOpen += parseInt(openCount);
            });
            
            alert(`Category Statistics:\n\nTotal Categories: <?php echo count($categories); ?>\nTotal Tickets: ${totalTickets}\nOpen Tickets: ${totalOpen}\nClosed Tickets: ${totalTickets - totalOpen}`);
        };
    });
</script>

<?php 
// Include footer layout
include __DIR__ . '/../layouts/footer.php'; 
?>
