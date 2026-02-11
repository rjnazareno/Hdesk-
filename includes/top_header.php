<?php
/**
 * Global Top Header Component
 * Consistent header bar for all admin and customer pages
 * Matches reference design with search, actions, notifications, and user menu
 */

// Get current user info
$userType = $_SESSION['user_type'] ?? 'employee';
$userRole = $_SESSION['role'] ?? 'employee';
$isITStaff = in_array($userRole, ['it_staff', 'admin']);

// User display information
$userName = '';
$userEmail = '';
$userInitials = 'U';

if (isset($currentUser)) {
    if (isset($currentUser['full_name'])) {
        $userName = $currentUser['full_name'];
        $userEmail = $currentUser['email'] ?? '';
        $userInitials = strtoupper(substr($userName, 0, 2));
    } elseif (isset($currentUser['fname']) && isset($currentUser['lname'])) {
        $userName = $currentUser['fname'] . ' ' . $currentUser['lname'];
        $userEmail = $currentUser['email'] ?? '';
        $userInitials = strtoupper(substr($currentUser['fname'], 0, 1) . substr($currentUser['lname'], 0, 1));
    }
}

// Page-specific variables (can be set before including this file)
$headerTitle = $headerTitle ?? 'Dashboard';
$headerSubtitle = $headerSubtitle ?? 'Overview and statistics';
$headerBadge = $headerBadge ?? null;
$showSearch = $showSearch ?? true;
$showQuickActions = $showQuickActions ?? $isITStaff;
$showNotifications = $showNotifications ?? true;

// Determine base URL for links
$basePath = strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '' : 'admin/';
$logoutPath = strpos($_SERVER['PHP_SELF'], '/admin/') !== false || strpos($_SERVER['PHP_SELF'], '/customer/') !== false ? '../logout.php' : 'logout.php';
?>

<!-- Top Header Bar -->
<div class="bg-white border-b border-gray-200 sticky top-0 z-30">
    <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
        <!-- Left Section: Title & Subtitle -->
        <div class="flex items-center space-x-3">
            <div>
                <h1 class="text-xl lg:text-2xl font-semibold text-gray-900 flex items-center gap-2">
                    <?php echo htmlspecialchars($headerTitle); ?>
                    <?php if ($headerBadge): ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 text-xs font-medium border border-gray-300 text-gray-700 bg-white rounded">
                        <i class="fas fa-shield-alt mr-1"></i>
                        <?php echo htmlspecialchars($headerBadge); ?>
                    </span>
                    <?php endif; ?>
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    <?php echo htmlspecialchars($headerSubtitle); ?>
                </p>
            </div>
        </div>

        <!-- Right Section: Notifications, User -->
        <div class="flex items-center space-x-3">
            <!-- Notifications Bell -->
            <?php if ($showNotifications): ?>
            <button class="relative p-2.5 text-gray-500 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-all" title="Notifications" id="notificationBell">
                <i class="far fa-bell text-lg"></i>
                <span class="absolute top-2 right-2 w-2 h-2 bg-red-500 rounded-full" id="notificationDot"></span>
            </button>
            <?php endif; ?>

            <!-- User Menu Dropdown -->
            <div class="relative z-50" id="userMenuDropdown">
                <button class="flex items-center space-x-2 px-3 py-2 hover:bg-gray-100 transition-all rounded-lg" id="userMenuBtn">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-gray-900 text-white rounded-full flex items-center justify-center text-sm font-semibold">
                            <?php echo $userInitials; ?>
                        </div>
                        <div class="hidden lg:block text-left">
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($userName); ?></p>
                            <?php if ($userEmail): ?>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($userEmail); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class="fas fa-chevron-down text-xs text-gray-400 hidden lg:block"></i>
                </button>
                <div class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-[100]" id="userMenuContent">
                    <div class="py-2">
                        <div class="px-4 py-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($userName); ?></p>
                            <?php if ($userEmail): ?>
                            <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars($userEmail); ?></p>
                            <?php endif; ?>
                        </div>
                        <a href="<?php echo $basePath; ?>profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition">
                            <i class="fas fa-user w-5"></i>
                            <span class="ml-3">Profile</span>
                        </a>
                        <?php if ($isITStaff): ?>
                        <a href="<?php echo $basePath; ?>settings.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 hover:text-blue-600 transition">
                            <i class="fas fa-cog w-5"></i>
                            <span class="ml-3">Settings</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($_SESSION['user_type'] === 'employee' && $userRole === 'internal'): ?>
                        <a href="<?php echo strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../customer/dashboard.php' : 'customer/dashboard.php'; ?>" class="flex items-center px-4 py-2 text-sm text-emerald-600 hover:bg-emerald-50 transition">
                            <i class="fas fa-arrow-left w-5"></i>
                            <span class="ml-3">Return to Employee Page</span>
                        </a>
                        <?php endif; ?>
                        <div class="border-t border-gray-200 my-1"></div>
                        <a href="<?php echo $logoutPath; ?>" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                            <i class="fas fa-sign-out-alt w-5"></i>
                            <span class="ml-3">Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Dropdowns -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // User Menu Dropdown
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenuContent = document.getElementById('userMenuContent');
    
    if (userMenuBtn && userMenuContent) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenuContent.classList.toggle('hidden');
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (userMenuContent && !userMenuContent.contains(e.target) && e.target !== userMenuBtn) {
            userMenuContent.classList.add('hidden');
        }
    });
});
</script>
