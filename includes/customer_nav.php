<?php
/**
 * Customer/Employee Navigation Component
 * Mobile-responsive sidebar navigation for employees
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$basePath = strpos($_SERVER['PHP_SELF'], '/customer/') !== false ? '' : 'customer/';
?>

<!-- Mobile Menu Button -->
<button id="mobile-menu-button" class="lg:hidden fixed top-4 left-4 z-50 bg-blue-600 text-white p-3 rounded-lg shadow-lg hover:bg-blue-700 transition">
    <i class="fas fa-bars text-xl"></i>
</button>

<!-- Mobile Overlay -->
<div id="mobile-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-white border-r border-gray-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40">
    <div class="flex flex-col h-full">
        <!-- Logo/Header -->
        <div class="flex items-center justify-between h-16 bg-blue-600 text-white px-6">
            <div class="flex items-center">
                <img src="<?= strpos($_SERVER['PHP_SELF'], '/customer/') !== false ? '../' : '' ?>img/ResolveIT Logo Only without Background.png" alt="ResolveIT" class="h-8 w-auto mr-2 filter brightness-0 invert">
                <span class="text-xl font-bold">ResolveIT</span>
            </div>
            <!-- Close button for mobile -->
            <button id="close-sidebar" class="lg:hidden text-white hover:text-gray-200">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- User Info -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center">
                    <span class="text-lg font-bold text-white"><?= strtoupper(substr($currentUser['full_name'], 0, 2)) ?></span>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-gray-900"><?= htmlspecialchars($currentUser['full_name']) ?></p>
                    <p class="text-xs text-gray-500">Employee Portal</p>
                </div>
            </div>
        </div>
        
        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto py-4">
            <a href="<?= $basePath ?>dashboard.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'dashboard.php' ? 'bg-blue-50 text-blue-600 border-r-4 border-blue-600' : 'text-gray-700 hover:bg-gray-50' ?> transition">
                <i class="fas fa-th-large w-6"></i>
                <span class="font-medium">Dashboard</span>
            </a>
            
            <a href="<?= $basePath ?>tickets.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'tickets.php' || $currentPage === 'view_ticket.php' ? 'bg-blue-50 text-blue-600 border-r-4 border-blue-600' : 'text-gray-700 hover:bg-gray-50' ?> transition">
                <i class="fas fa-ticket-alt w-6"></i>
                <span class="font-medium">My Tickets</span>
            </a>
            
            <a href="<?= $basePath ?>create_ticket.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'create_ticket.php' ? 'bg-blue-50 text-blue-600 border-r-4 border-blue-600' : 'text-gray-700 hover:bg-gray-50' ?> transition">
                <i class="fas fa-plus-circle w-6"></i>
                <span class="font-medium">Create Ticket</span>
            </a>
            
            <a href="<?= strpos($_SERVER['PHP_SELF'], '/customer/') !== false ? '../' : '' ?>article.php" 
               class="flex items-center px-6 py-3 <?= $currentPage === 'article.php' ? 'bg-blue-50 text-blue-600 border-r-4 border-blue-600' : 'text-gray-700 hover:bg-gray-50' ?> transition">
                <i class="fas fa-newspaper w-6"></i>
                <span class="font-medium">Knowledge Base</span>
            </a>
            
            <div class="mt-6 px-6">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Quick Actions</p>
                <a href="<?= $basePath ?>create_ticket.php" 
                   class="flex items-center justify-center w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>
                    <span class="font-medium">New Ticket</span>
                </a>
            </div>
        </nav>
        
        <!-- Logout Button -->
        <div class="border-t border-gray-200">
            <a href="<?= strpos($_SERVER['PHP_SELF'], '/customer/') !== false ? '../' : '' ?>logout.php" 
               class="flex items-center px-6 py-4 text-gray-700 hover:bg-gray-50 transition">
                <i class="fas fa-sign-out-alt w-6 text-red-500"></i>
                <span class="font-medium">Logout</span>
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
