<?php
/**
 * Customer/Employee Navigation Component
 * HDesk - Multi-Department Support Portal
 * Mobile-responsive sidebar navigation for employees
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$basePath = strpos($_SERVER['PHP_SELF'], '/customer/') !== false ? '' : 'customer/';
$imgPath = strpos($_SERVER['PHP_SELF'], '/customer/') !== false ? '../' : '';

// Handle user display name
$displayName = '';
$initials = '';
$position = '';

if (isset($currentUser['full_name'])) {
    $displayName = $currentUser['full_name'];
    $initials = strtoupper(substr($displayName, 0, 2));
} elseif (isset($currentUser['fname']) && isset($currentUser['lname'])) {
    $displayName = $currentUser['fname'] . ' ' . $currentUser['lname'];
    $initials = strtoupper(substr($currentUser['fname'], 0, 1) . substr($currentUser['lname'], 0, 1));
} else {
    $displayName = 'User';
    $initials = 'U';
}

$position = $currentUser['position'] ?? 'Employee';
?>

<!-- Mobile Menu Button -->
<button id="mobile-menu-button" class="lg:hidden fixed top-4 left-4 z-50 bg-emerald-500 text-white p-3 rounded-xl shadow-lg hover:bg-emerald-600 transition-all duration-200">
    <i class="fas fa-bars text-lg"></i>
</button>

<!-- Mobile Overlay -->
<div id="mobile-overlay" class="lg:hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-40 hidden transition-opacity duration-300"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-100 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 shadow-xl lg:shadow-none">
    <div class="flex flex-col h-full">
        <!-- Logo/Header -->
        <div class="flex items-center justify-center h-24 px-5 border-b border-gray-200 bg-white relative">
            <span class="text-4xl font-bold">
                <span class="text-emerald-500">H</span><span class="text-slate-800">desk</span>
            </span>
            <!-- Close button for mobile -->
            <button id="close-sidebar" class="lg:hidden absolute right-4 text-gray-400 hover:text-gray-600 p-2">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto py-6 px-3">
            <div class="space-y-1">
                <a href="<?= $basePath ?>dashboard.php" 
                   class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= $currentPage === 'dashboard.php' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center mr-3 transition-all duration-200 <?= $currentPage === 'dashboard.php' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'bg-gray-100 text-gray-500 group-hover:bg-emerald-100 group-hover:text-emerald-600' ?>">
                        <i class="fas fa-home text-sm"></i>
                    </div>
                    <span class="font-medium">Home</span>
                </a>
                
                <a href="<?= $basePath ?>tickets.php" 
                   class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= ($currentPage === 'tickets.php' || $currentPage === 'view_ticket.php') ? 'bg-emerald-50 text-emerald-600' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center mr-3 transition-all duration-200 <?= ($currentPage === 'tickets.php' || $currentPage === 'view_ticket.php') ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'bg-gray-100 text-gray-500 group-hover:bg-emerald-100 group-hover:text-emerald-600' ?>">
                        <i class="fas fa-ticket-alt text-sm"></i>
                    </div>
                    <span class="font-medium">My Requests</span>
                </a>
                
                <a href="<?= $basePath ?>create_ticket.php" 
                   class="flex items-center px-4 py-3 rounded-xl transition-all duration-200 group <?= $currentPage === 'create_ticket.php' ? 'bg-emerald-50 text-emerald-600' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <div class="w-9 h-9 rounded-lg flex items-center justify-center mr-3 transition-all duration-200 <?= $currentPage === 'create_ticket.php' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'bg-gray-100 text-gray-500 group-hover:bg-emerald-100 group-hover:text-emerald-600' ?>">
                        <i class="fas fa-plus text-sm"></i>
                    </div>
                    <span class="font-medium">Create Ticket</span>
                </a>
            </div>
        </nav>
        
        <!-- User Info (Logout moved to top right) -->
        <div class="border-t border-gray-100 p-4">
            <div class="flex items-center p-3 bg-gray-50 rounded-xl">
                <?php if (!empty($currentUser['profile_picture'])): ?>
                    <img src="<?= $imgPath ?>uploads/profiles/<?= htmlspecialchars($currentUser['profile_picture']) ?>" 
                         alt="Profile" class="w-10 h-10 rounded-full object-cover">
                <?php else: ?>
                    <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                        <?= $initials ?>
                    </div>
                <?php endif; ?>
                <div class="ml-3 flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($displayName) ?></p>
                    <p class="text-xs text-gray-500 truncate"><?= htmlspecialchars($position) ?></p>
                </div>
            </div>
        </div>
    </div>
</aside>

<!-- JavaScript for Mobile Menu -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const closeSidebar = document.getElementById('close-sidebar');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('mobile-overlay');
    
    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function closeSidebarFunc() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    if (mobileMenuButton) mobileMenuButton.addEventListener('click', openSidebar);
    if (closeSidebar) closeSidebar.addEventListener('click', closeSidebarFunc);
    if (overlay) overlay.addEventListener('click', closeSidebarFunc);
    
    // Close sidebar when clicking on a link (mobile only)
    if (window.innerWidth < 1024) {
        document.querySelectorAll('#sidebar a').forEach(link => {
            link.addEventListener('click', closeSidebarFunc);
        });
    }
});
</script>
