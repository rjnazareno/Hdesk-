<?php
/**
 * Admin Navigation Component
 * ServiceDesk - Multi-Department Support Portal
 * Modern sidebar navigation with emerald/teal theme
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$basePath = strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '' : 'admin/';
$imgPath = strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? '../' : '';

// Determine user access levels
$adminRights = $_SESSION['admin_rights'] ?? null;
$userRole = $_SESSION['role'] ?? '';
$userType = $_SESSION['user_type'] ?? '';

// Super Admin: users table admin OR employee with internal role + superadmin rights
$isSuperAdmin = ($userType === 'user' && $userRole === 'admin') || 
                ($userType === 'employee' && $userRole === 'internal' && $adminRights === 'superadmin');

// Department Admin: employee with internal role + it/hr rights
$isDeptAdmin = ($userType === 'employee' && $userRole === 'internal' && in_array($adminRights, ['it', 'hr']));

// Has any admin access: users table admin/it_staff OR employee with internal role + admin_rights
$hasAdminAccess = $isSuperAdmin || $isDeptAdmin || 
                  ($userType === 'user' && in_array($userRole, ['admin', 'it_staff'])) ||
                  ($userType === 'employee' && $userRole === 'internal' && !empty($adminRights));

// Get pool counts for badge
$poolCount = 0;
$myTicketsCount = 0;
try {
    $navDb = Database::getInstance()->getConnection();
    // Count ungrabbed tickets in pool
    $poolStmt = $navDb->prepare('SELECT COUNT(*) as count FROM tickets WHERE grabbed_by IS NULL AND status IN ("pending", "open")');
    $poolStmt->execute();
    $poolCount = $poolStmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Count my tickets (pending + open) - check both user and employee assignees
    if (isset($currentUser['id'])) {
        // Determine expected assignee_type based on user_type in session
        $expectedAssigneeType = ($_SESSION['user_type'] ?? 'user') === 'employee' ? 'employee' : 'user';
        
        // Count tickets assigned to this user/employee with matching assignee_type
        $myStmt = $navDb->prepare('SELECT COUNT(*) as count FROM tickets WHERE assigned_to = ? AND assignee_type = ? AND status IN ("pending", "open")');
        $myStmt->execute([$currentUser['id'], $expectedAssigneeType]);
        $myTicketsCount = $myStmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
} catch (Exception $e) {
    // Silently fail
}
?>

<!-- Mobile Menu Button -->
<button id="mobile-menu-button" class="lg:hidden fixed top-4 left-4 z-50 bg-gradient-to-r from-emerald-500 to-teal-500 text-white p-3 rounded-xl shadow-lg hover:from-emerald-600 hover:to-teal-600 transition">
    <i class="fas fa-bars text-lg"></i>
</button>

<!-- Mobile Overlay -->
<div id="mobile-overlay" class="lg:hidden fixed inset-0 bg-black/50 z-40 hidden transition-opacity duration-300"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 w-64 bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out z-40 shadow-2xl">
    <div class="flex flex-col h-full">
        <!-- Logo/Header -->
        <div class="flex items-center justify-center h-20 px-5 border-b border-slate-700/50">
            <a href="<?= $basePath ?>dashboard.php" class="flex flex-col items-center gap-1">
                <div class="text-center">
                    <span class="text-3xl font-bold text-emerald-400">H</span><span class="text-3xl font-bold text-white">desk</span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Admin</p>
            </a>
            <!-- Close button for mobile -->
            <button id="close-sidebar" class="lg:hidden absolute right-4 w-8 h-8 flex items-center justify-center text-slate-400 hover:text-white hover:bg-slate-700 rounded-lg transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Navigation Links -->
        <nav class="flex-1 overflow-y-auto py-4 px-3">
            <p class="px-3 mb-2 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Main Menu</p>
            
            <a href="<?= $basePath ?>dashboard.php" 
               class="flex items-center gap-3 px-3 py-2.5 mb-1 rounded-xl <?= $currentPage === 'dashboard.php' ? 'bg-emerald-500/20 text-white border border-emerald-500/40' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition group">
                <div class="w-8 h-8 rounded-lg <?= $currentPage === 'dashboard.php' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-700 text-slate-400 group-hover:bg-slate-600 group-hover:text-emerald-400' ?> flex items-center justify-center transition">
                    <i class="fas fa-th-large text-sm"></i>
                </div>
                <span class="font-medium text-sm">Dashboard</span>
            </a>
            
            <p class="px-3 mt-4 mb-2 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Ticket Pool</p>
            
            <a href="<?= $basePath ?>tickets.php?view=pool" 
               class="flex items-center gap-3 px-3 py-2.5 mb-1 rounded-xl <?= ($currentPage === 'tickets.php' && in_array($_GET['view'] ?? '', ['pool', 'queue'])) ? 'bg-emerald-500/20 text-white border border-emerald-500/40' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition group">
                <div class="w-8 h-8 rounded-lg <?= ($currentPage === 'tickets.php' && in_array($_GET['view'] ?? '', ['pool', 'queue'])) ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-700 text-slate-400 group-hover:bg-slate-600 group-hover:text-emerald-400' ?> flex items-center justify-center transition">
                    <i class="fas fa-inbox text-sm"></i>
                </div>
                <span class="font-medium text-sm">Ticket Pool</span>
                <?php if ($poolCount > 0): ?>
                <span class="ml-auto inline-flex items-center justify-center px-2 h-5 bg-red-500 text-white text-xs font-bold rounded-full">
                    <?= $poolCount > 99 ? '99+' : $poolCount ?>
                </span>
                <?php endif; ?>
            </a>
            
            <a href="<?= $basePath ?>tickets.php?view=my_tickets" 
               class="flex items-center gap-3 px-3 py-2.5 mb-1 rounded-xl <?= ($currentPage === 'tickets.php' && ($_GET['view'] ?? '') === 'my_tickets') ? 'bg-emerald-500/20 text-white border border-emerald-500/40' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition group">
                <div class="w-8 h-8 rounded-lg <?= ($currentPage === 'tickets.php' && ($_GET['view'] ?? '') === 'my_tickets') ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-700 text-slate-400 group-hover:bg-slate-600 group-hover:text-emerald-400' ?> flex items-center justify-center transition">
                    <i class="fas fa-user-check text-sm"></i>
                </div>
                <span class="font-medium text-sm">My Tickets</span>
                <?php if ($myTicketsCount > 0): ?>
                <span class="ml-auto inline-flex items-center justify-center min-w-[24px] px-2 py-0.5 bg-green-500 text-white text-xs font-bold rounded-full">
                    <?= $myTicketsCount > 99 ? '99+' : $myTicketsCount ?>
                </span>
                <?php endif; ?>
            </a>
            
            <a href="<?= $basePath ?>create_ticket.php" 
               class="flex items-center gap-3 px-3 py-2.5 mb-1 rounded-xl <?= $currentPage === 'create_ticket.php' ? 'bg-emerald-500/20 text-white border border-emerald-500/40' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition group">
                <div class="w-8 h-8 rounded-lg <?= $currentPage === 'create_ticket.php' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-700 text-slate-400 group-hover:bg-slate-600 group-hover:text-emerald-400' ?> flex items-center justify-center transition">
                    <i class="fas fa-plus text-sm"></i>
                </div>
                <span class="font-medium text-sm">Create Ticket</span>
            </a>
            
            <p class="px-3 mt-4 mb-2 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Management</p>
            
            <a href="<?= $basePath ?>customers.php" 
               class="flex items-center gap-3 px-3 py-2.5 mb-1 rounded-xl <?= $currentPage === 'customers.php' || $currentPage === 'edit_employee.php' ? 'bg-emerald-500/20 text-white border border-emerald-500/40' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition group">
                <div class="w-8 h-8 rounded-lg <?= $currentPage === 'customers.php' || $currentPage === 'edit_employee.php' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-700 text-slate-400 group-hover:bg-slate-600 group-hover:text-emerald-400' ?> flex items-center justify-center transition">
                    <i class="fas fa-users text-sm"></i>
                </div>
                <span class="font-medium text-sm">Employees</span>
            </a>
            
            <a href="<?= $basePath ?>categories.php" 
               class="flex items-center gap-3 px-3 py-2.5 mb-1 rounded-xl <?= $currentPage === 'categories.php' ? 'bg-emerald-500/20 text-white border border-emerald-500/40' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition group">
                <div class="w-8 h-8 rounded-lg <?= $currentPage === 'categories.php' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-700 text-slate-400 group-hover:bg-slate-600 group-hover:text-emerald-400' ?> flex items-center justify-center transition">
                    <i class="fas fa-folder text-sm"></i>
                </div>
                <span class="font-medium text-sm">Categories</span>
            </a>
            
            <?php if ($hasAdminAccess): ?>
            <p class="px-3 mt-4 mb-2 text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Administration</p>
            
            <?php if ($isSuperAdmin): ?>
            <a href="<?= $basePath ?>manage_employee_rights.php" 
               class="flex items-center gap-3 px-3 py-2.5 mb-1 rounded-xl <?= $currentPage === 'manage_employee_rights.php' ? 'bg-emerald-500/20 text-white border border-emerald-500/40' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition group">
                <div class="w-8 h-8 rounded-lg <?= $currentPage === 'manage_employee_rights.php' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-700 text-slate-400 group-hover:bg-slate-600 group-hover:text-emerald-400' ?> flex items-center justify-center transition">
                    <i class="fas fa-user-shield text-sm"></i>
                </div>
                <span class="font-medium text-sm">Admin Rights</span>
            </a>
            <?php endif; ?>
            
            <a href="<?= $basePath ?>sla_management.php" 
               class="flex items-center gap-3 px-3 py-2.5 mb-1 rounded-xl <?= $currentPage === 'sla_management.php' ? 'bg-emerald-500/20 text-white border border-emerald-500/40' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition group">
                <div class="w-8 h-8 rounded-lg <?= $currentPage === 'sla_management.php' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-700 text-slate-400 group-hover:bg-slate-600 group-hover:text-emerald-400' ?> flex items-center justify-center transition">
                    <i class="fas fa-clock text-sm"></i>
                </div>
                <span class="font-medium text-sm">SLA Management</span>
            </a>
            
            <a href="<?= $basePath ?>sla_performance.php" 
               class="flex items-center gap-3 px-3 py-2.5 mb-1 rounded-xl <?= $currentPage === 'sla_performance.php' ? 'bg-emerald-500/20 text-white border border-emerald-500/40' : 'text-slate-300 hover:bg-slate-700/50 hover:text-white' ?> transition group">
                <div class="w-8 h-8 rounded-lg <?= $currentPage === 'sla_performance.php' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-slate-700 text-slate-400 group-hover:bg-slate-600 group-hover:text-emerald-400' ?> flex items-center justify-center transition">
                    <i class="fas fa-chart-line text-sm"></i>
                </div>
                <span class="font-medium text-sm">SLA Performance</span>
            </a>

            <?php endif; ?>
        </nav>
        
        <!-- User Profile Only (No Logout) -->
        <div class="border-t border-slate-700/50 p-3">
            <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-800/50">
                <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-500 rounded-xl flex items-center justify-center shadow-lg shadow-emerald-500/20">
                    <span class="text-sm font-bold text-white"><?= strtoupper(substr($currentUser['full_name'], 0, 2)) ?></span>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate"><?= htmlspecialchars($currentUser['full_name']) ?></p>
                    <p class="text-xs text-slate-400">
                        <?php 
                        // Show role badge based on admin rights
                        if ($adminRights === 'superadmin') {
                            echo '<span class="text-red-400">Super Admin</span>';
                        } elseif ($adminRights === 'it') {
                            echo '<span class="text-blue-400">IT Admin</span>';
                        } elseif ($adminRights === 'hr') {
                            echo '<span class="text-green-400">HR Admin</span>';
                        } else {
                            echo ucfirst(str_replace('_', ' ', $currentUser['role'] ?? 'User'));
                        }
                        ?>
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
