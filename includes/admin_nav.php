<?php
/**
 * Admin Navigation Component
 * Mobile-responsive sidebar navigation for admin/IT staff
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$basePath = strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '' : 'admin/';
?>

<!-- Mobile Menu Button -->
<button id="mobile-menu-button" class="lg:hidden fixed top-4 left-4 z-50 bg-teal-600 text-white p-3 rounded-none shadow-lg hover:bg-teal-700 transition">
    <i class="fas fa-bars text-xl"></i>
</button>

<!-- Mobile Overlay -->
<div id="mobile-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white text-gray-900 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 border-r border-gray-200 shadow-xl">
    <div class="flex flex-col h-full">
        <!-- Logo/Header -->
        <div class="flex items-center justify-between h-16 bg-gradient-to-r from-teal-600 to-emerald-600 px-6 border-b border-teal-700">
            <div class="flex items-center">
                <img src="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>img/ResolveIT Logo Only without Background.png" alt="ResolveIT" class="h-8 w-auto mr-2">
                <span class="text-xl font-bold"><span class="text-green-300">Resolve</span><span class="text-teal-200">IT</span></span>
            </div>
            <!-- Close button for mobile -->
            <button id="close-sidebar" class="lg:hidden text-white hover:text-green-100 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto py-6">
            <a href="<?= $basePath ?>dashboard.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'dashboard.php' ? 'text-gray-900 border-l-4 border-teal-600 font-medium' : 'text-gray-700 hover:bg-gray-50 hover:text-teal-600' ?> transition">
                <i class="fas fa-th-large w-6"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="<?= $basePath ?>tickets.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'tickets.php' || $currentPage === 'view_ticket.php' ? 'text-gray-900 border-l-4 border-teal-600 font-medium' : 'text-gray-700 hover:bg-gray-50 hover:text-teal-600' ?> transition">
                <i class="fas fa-ticket-alt w-6"></i>
                <span>Tickets</span>
            </a>
            
            <a href="<?= $basePath ?>create_ticket.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'create_ticket.php' ? 'text-gray-900 border-l-4 border-teal-600 font-medium' : 'text-gray-700 hover:bg-gray-50 hover:text-teal-600' ?> transition">
                <i class="fas fa-plus-circle w-6"></i>
                <span>Create Ticket</span>
            </a>
            
            <a href="<?= $basePath ?>customers.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'customers.php' ? 'text-gray-900 border-l-4 border-teal-600 font-medium' : 'text-gray-700 hover:bg-gray-50 hover:text-teal-600' ?> transition">
                <i class="fas fa-users w-6"></i>
                <span>Employees</span>
            </a>
            
            <a href="<?= $basePath ?>categories.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'categories.php' ? 'text-gray-900 border-l-4 border-teal-600 font-medium' : 'text-gray-700 hover:bg-gray-50 hover:text-teal-600' ?> transition">
                <i class="fas fa-folder w-6"></i>
                <span>Categories</span>
            </a>
            
            <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="<?= $basePath ?>sla_management.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'sla_management.php' ? 'text-gray-900 border-l-4 border-teal-600 font-medium' : 'text-gray-700 hover:bg-gray-50 hover:text-teal-600' ?> transition">
                <i class="fas fa-clock w-6"></i>
                <span>SLA Management</span>
            </a>
            
            <a href="<?= $basePath ?>sla_performance.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'sla_performance.php' ? 'text-gray-900 border-l-4 border-teal-600 font-medium' : 'text-gray-700 hover:bg-gray-50 hover:text-teal-600' ?> transition">
                <i class="fas fa-chart-line w-6"></i>
                <span>SLA Performance</span>
            </a>
            
            <a href="<?= $basePath ?>admin.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'admin.php' ? 'text-gray-900 border-l-4 border-teal-600 font-medium' : 'text-gray-700 hover:bg-gray-50 hover:text-teal-600' ?> transition">
                <i class="fas fa-cog w-6"></i>
                <span>Admin Settings</span>
            </a>
            <?php endif; ?>
            
            <a href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>article.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'article.php' ? 'text-gray-900 border-l-4 border-teal-600 font-medium' : 'text-gray-700 hover:bg-gray-50 hover:text-teal-600' ?> transition">
                <i class="fas fa-newspaper w-6"></i>
                <span>Articles</span>
            </a>
            
            <!-- User Info (Mobile) -->
            <div class="lg:hidden mt-6 px-6 py-3 border-t border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-teal-500 to-emerald-600 rounded-full flex items-center justify-center">
                        <span class="text-sm font-bold text-white"><?= strtoupper(substr($currentUser['full_name'], 0, 2)) ?></span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($currentUser['full_name']) ?></p>
                        <p class="text-xs text-gray-600"><?= ucfirst($currentUser['role']) ?></p>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Logout Button -->
        <div class="border-t border-gray-200">
            <a href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>logout.php" 
               class="flex items-center px-6 py-4 text-red-600 hover:bg-red-50 hover:text-red-700 transition">
                <i class="fas fa-sign-out-alt w-6"></i>
                <span>Logout</span>
            </a>
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
    
    mobileMenuButton.addEventListener('click', openSidebar);
    closeSidebar.addEventListener('click', closeSidebarFunc);
    overlay.addEventListener('click', closeSidebarFunc);
    
    // Close sidebar when clicking on a link (mobile only)
    if (window.innerWidth < 1024) {
        document.querySelectorAll('#sidebar a').forEach(link => {
            link.addEventListener('click', closeSidebarFunc);
        });
    }
});
</script>
