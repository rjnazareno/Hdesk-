<?php
/**
 * Admin Navigation Component
 * Mobile-responsive sidebar navigation for admin/IT staff
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$basePath = strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '' : 'admin/';
?>

<!-- Mobile Menu Button -->
<button id="mobile-menu-button" class="lg:hidden fixed top-4 left-4 z-50 bg-slate-900 text-white p-3 rounded-lg shadow-lg hover:bg-slate-800 transition">
    <i class="fas fa-bars text-xl"></i>
</button>

<!-- Mobile Overlay -->
<div id="mobile-overlay" class="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden transition-opacity duration-300"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-slate-900 to-slate-800 text-white transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 border-r border-slate-700/50">
    <div class="flex flex-col h-full">
        <!-- Logo/Header -->
        <div class="flex items-center justify-between h-16 bg-slate-800/50 px-6 border-b border-slate-700/50">
            <div class="flex items-center">
                <img src="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>img/ResolveIT Logo Only without Background.png" alt="ResolveIT" class="h-8 w-auto mr-2">
                <span class="text-xl font-bold"><span class="text-slate-300">Resolve</span><span class="text-cyan-400">IT</span></span>
            </div>
            <!-- Close button for mobile -->
            <button id="close-sidebar" class="lg:hidden text-slate-400 hover:text-white transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto py-6">
            <a href="<?= $basePath ?>dashboard.php" 
               class="flex items-center px-6 py-3 mx-3 rounded-lg <?= $currentPage === 'dashboard.php' ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition">
                <i class="fas fa-th-large w-6"></i>
                <span>Dashboard</span>
            </a>
            
            <a href="<?= $basePath ?>tickets.php" 
               class="flex items-center px-6 py-3 mx-3 rounded-lg <?= $currentPage === 'tickets.php' || $currentPage === 'view_ticket.php' ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition">
                <i class="fas fa-ticket-alt w-6"></i>
                <span>Tickets</span>
            </a>
            
            <a href="<?= $basePath ?>create_ticket.php" 
               class="flex items-center px-6 py-3 mx-3 rounded-lg <?= $currentPage === 'create_ticket.php' ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition">
                <i class="fas fa-plus-circle w-6"></i>
                <span>Create Ticket</span>
            </a>
            
            <a href="<?= $basePath ?>customers.php" 
               class="flex items-center px-6 py-3 mx-3 rounded-lg <?= $currentPage === 'customers.php' ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition">
                <i class="fas fa-users w-6"></i>
                <span>Employees</span>
            </a>
            
            <a href="<?= $basePath ?>categories.php" 
               class="flex items-center px-6 py-3 mx-3 rounded-lg <?= $currentPage === 'categories.php' ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition">
                <i class="fas fa-folder w-6"></i>
                <span>Categories</span>
            </a>
            
            <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="<?= $basePath ?>sla_management.php" 
               class="flex items-center px-6 py-3 mx-3 rounded-lg <?= $currentPage === 'sla_management.php' ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition">
                <i class="fas fa-clock w-6"></i>
                <span>SLA Management</span>
            </a>
            
            <a href="<?= $basePath ?>sla_performance.php" 
               class="flex items-center px-6 py-3 mx-3 rounded-lg <?= $currentPage === 'sla_performance.php' ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition">
                <i class="fas fa-chart-line w-6"></i>
                <span>SLA Performance</span>
            </a>
            
            <a href="<?= $basePath ?>reset_employee_passwords.php" 
               class="flex items-center px-6 py-3 mx-3 rounded-lg <?= $currentPage === 'reset_employee_passwords.php' ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition">
                <i class="fas fa-key w-6"></i>
                <span>Reset Passwords</span>
            </a>
            
            <a href="<?= $basePath ?>admin.php" 
               class="flex items-center px-6 py-3 mx-3 rounded-lg <?= $currentPage === 'admin.php' ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition">
                <i class="fas fa-cog w-6"></i>
                <span>Admin Settings</span>
            </a>
            <?php endif; ?>
            
            <a href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>article.php" 
               class="flex items-center px-6 py-3 mx-3 rounded-lg <?= $currentPage === 'article.php' ? 'bg-gradient-to-r from-cyan-500/20 to-blue-500/20 text-cyan-400 border border-cyan-500/30' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition">
                <i class="fas fa-newspaper w-6"></i>
                <span>Articles</span>
            </a>
            
            <!-- User Info (Mobile) -->
            <div class="lg:hidden mt-6 px-6 py-3 border-t border-slate-700/50">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 rounded-full flex items-center justify-center">
                        <span class="text-sm font-bold"><?= strtoupper(substr($currentUser['full_name'], 0, 2)) ?></span>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-white"><?= htmlspecialchars($currentUser['full_name']) ?></p>
                        <p class="text-xs text-slate-400"><?= ucfirst($currentUser['role']) ?></p>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Logout Button -->
        <div class="border-t border-slate-700/50">
            <a href="<?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '' ?>logout.php" 
               class="flex items-center px-6 py-4 text-slate-300 hover:bg-slate-700/50 hover:text-white transition">
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
