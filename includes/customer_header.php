<!-- Top Bar Header - Global for all customer pages -->
<div class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
        <!-- Left Section: Page Title (set by each page) -->
        <div>
            <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">
                <?php echo htmlspecialchars($pageTitle ?? 'Dashboard'); ?>
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                <?php echo htmlspecialchars($pageSubtitle ?? date('l, F j, Y')); ?>
            </p>
        </div>
        
        <!-- Right Section: Notifications, User -->
        <div class="flex items-center space-x-3">
            <!-- Notification Bell -->
            <a href="<?= $basePath ?>notifications.php" class="relative p-2.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all" title="Notifications">
                <i class="fas fa-bell text-lg"></i>
                <?php if (isset($unreadNotifications) && $unreadNotifications > 0): ?>
                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center w-5 h-5 bg-red-500 text-white text-xs font-bold rounded-full">
                    <?= $unreadNotifications > 99 ? '99+' : $unreadNotifications ?>
                </span>
                <?php endif; ?>
            </a>
            
            <!-- User Menu Dropdown -->
            <div class="relative z-50" id="customerUserDropdown">
                <button class="flex items-center space-x-2 px-3 py-2 hover:bg-gray-100 transition-all rounded-lg" id="customerUserBtn">
                    <div class="flex items-center space-x-2">
                        <?php if (!empty($currentUser['profile_picture'])): ?>
                            <img src="<?= $basePath ?>../uploads/profiles/<?= htmlspecialchars($currentUser['profile_picture']) ?>" 
                                 class="w-8 h-8 rounded-full object-cover" alt="Profile">
                        <?php else: ?>
                            <div class="w-8 h-8 bg-gray-900 text-white rounded-full flex items-center justify-center text-sm font-semibold">
                                <?= strtoupper(substr($currentUser['full_name'], 0, 2)) ?>
                            </div>
                        <?php endif; ?>
                        <div class="hidden lg:block text-left">
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($currentUser['full_name']) ?></p>
                            <?php if (isset($currentUser['email'])): ?>
                            <p class="text-xs text-gray-500"><?= htmlspecialchars($currentUser['email']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-gray-400 hidden lg:block"></i>
                </button>
                <div class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-[100]" id="customerUserMenu">
                    <div class="py-2">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($currentUser['full_name']) ?></p>
                            <?php if (isset($currentUser['email'])): ?>
                            <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($currentUser['email']) ?></p>
                            <?php endif; ?>
                        </div>
                        <a href="<?= $basePath ?>profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition">
                            <i class="fas fa-user w-5"></i>
                            <span class="ml-3">Profile</span>
                        </a>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'internal'): ?>
                        <a href="<?= $basePath ?>../admin/dashboard.php" class="flex items-center px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 transition">
                            <i class="fas fa-user-shield w-5"></i>
                            <span class="ml-3">Switch to Admin</span>
                        </a>
                        <?php endif; ?>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a href="<?= $basePath ?>../logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                            <i class="fas fa-sign-out-alt w-5"></i>
                            <span class="ml-3">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for User Dropdown -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const customerUserBtn = document.getElementById('customerUserBtn');
    const customerUserMenu = document.getElementById('customerUserMenu');
    
    if (customerUserBtn && customerUserMenu) {
        customerUserBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            customerUserMenu.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!customerUserMenu.contains(e.target) && e.target !== customerUserBtn) {
                customerUserMenu.classList.add('hidden');
            }
        });
    }
});
</script>
