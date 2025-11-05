<?php
/**
 * Employee Password Reset/Regeneration Tool
 * Admin panel to reset employee passwords to default or send reset links
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../models/Employee.php';

// Check authentication
$auth = new Auth();
$auth->requireLogin();
$auth->requireAdmin(); // Only admins can reset passwords

$pageTitle = 'Reset Employee Passwords - IT Help Desk';
$baseUrl = '../';
$currentUser = $auth->getCurrentUser();
$employeeModel = new Employee();
$message = '';
$errorMessage = '';

// Process password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'reset_single') {
        $employeeId = (int)$_POST['employee_id'];
        $defaultPassword = 'Welcome123!';
        
        try {
            $employee = $employeeModel->findById($employeeId);
            if ($employee) {
                $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
                $employeeModel->update($employeeId, ['password' => $hashedPassword]);
                $message = "✅ Password reset for {$employee['fname']} {$employee['lname']}. Temporary password: <strong>$defaultPassword</strong>";
            } else {
                $errorMessage = "Employee not found.";
            }
        } catch (Exception $e) {
            $errorMessage = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'reset_all_broken') {
        // Reset all employees with non-bcrypt passwords
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id, username, fname, lname, password FROM employees WHERE status = 'active'");
            $stmt->execute();
            $employees = $stmt->fetchAll();
            
            $resetCount = 0;
            $defaultPassword = 'Welcome123!';
            $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
            
            foreach ($employees as $emp) {
                // Check if password is NOT bcrypt
                if (strpos($emp['password'], '$2') !== 0) {
                    $employeeModel->update($emp['id'], ['password' => $hashedPassword]);
                    $resetCount++;
                }
            }
            
            $message = "✅ Reset $resetCount employee passwords to default. Temporary password: <strong>$defaultPassword</strong>";
        } catch (Exception $e) {
            $errorMessage = "Error: " . $e->getMessage();
        }
    }
}

// Pagination settings
$itemsPerPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 20;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Get all employees
$allEmployees = $employeeModel->getAll('active');

// Analyze password formats
$bcryptEmployees = [];
$brokenEmployees = [];

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT id, username, email, fname, lname, password FROM employees WHERE status = 'active' ORDER BY fname, lname");
$stmt->execute();
$employees = $stmt->fetchAll();

foreach ($employees as $emp) {
    if (strpos($emp['password'], '$2') === 0) {
        $bcryptEmployees[] = $emp;
    } else {
        $brokenEmployees[] = $emp;
    }
}

// Calculate pagination for all employees table
$totalEmployees = count($employees);
$totalPages = ceil($totalEmployees / $itemsPerPage);
$currentPage = min($currentPage, max(1, $totalPages));
$offset = ($currentPage - 1) * $itemsPerPage;
$paginatedEmployees = array_slice($employees, $offset, $itemsPerPage);

// Set page variables for header
include_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="lg:ml-64 min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Top Bar -->
    <div class="bg-slate-800/50 border-b border-slate-700/50 backdrop-blur-sm">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <div class="flex items-center space-x-4">
                <div class="hidden lg:flex items-center justify-center w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 text-white rounded-lg">
                    <i class="fas fa-key text-lg"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-white">Reset Employee Passwords</h1>
                    <p class="text-sm text-slate-400 mt-0.5">Fix login issues by regenerating passwords</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="p-4 lg:p-8">
        <!-- Messages -->
        <?php if ($message): ?>
        <div class="mb-6 bg-green-900/20 border border-green-500/30 rounded-lg p-4 text-green-300 flex items-start space-x-3">
            <i class="fas fa-check-circle text-green-400 mt-0.5 flex-shrink-0"></i>
            <div><?php echo $message; ?></div>
        </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
        <div class="mb-6 bg-red-900/20 border border-red-500/30 rounded-lg p-4 text-red-300 flex items-start space-x-3">
            <i class="fas fa-exclamation-circle text-red-400 mt-0.5 flex-shrink-0"></i>
            <div><?php echo $errorMessage; ?></div>
        </div>
        <?php endif; ?>

        <!-- Status Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <!-- Working Passwords Card -->
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-lg p-6 backdrop-blur-sm hover:border-cyan-500/50 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-400 uppercase tracking-wide">Working Passwords</p>
                        <p class="text-4xl font-bold text-green-400 mt-2"><?php echo count($bcryptEmployees); ?></p>
                        <p class="text-xs text-slate-500 mt-2">✓ Bcrypt format (secure)</p>
                    </div>
                    <div class="text-6xl text-green-500 opacity-10">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                </div>
            </div>

            <!-- Broken Passwords Card -->
            <div class="bg-slate-800/50 border border-slate-700/50 rounded-lg p-6 backdrop-blur-sm hover:border-red-500/50 transition">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-slate-400 uppercase tracking-wide">Broken Passwords</p>
                        <p class="text-4xl font-bold text-red-400 mt-2"><?php echo count($brokenEmployees); ?></p>
                        <p class="text-xs text-slate-500 mt-2">⚠ Need reset</p>
                    </div>
                    <div class="text-6xl text-red-500 opacity-10">
                        <i class="fas fa-exclamation"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Action Alert -->
        <?php if (count($brokenEmployees) > 0): ?>
        <div class="bg-amber-900/20 border border-amber-500/30 rounded-lg p-6 mb-8 backdrop-blur-sm">
            <div class="flex items-start space-x-4">
                <div class="flex-shrink-0">
                    <i class="fas fa-bolt text-amber-400 text-2xl"></i>
                </div>
                <div class="flex-1">
                    <h2 class="text-lg font-semibold text-amber-300 mb-2">
                        <?php echo count($brokenEmployees); ?> Employee(s) Cannot Login
                    </h2>
                    <p class="text-amber-200/80 text-sm mb-4">
                        These employees have non-bcrypt passwords. Fix them instantly with one click.
                    </p>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="reset_all_broken">
                        <button type="submit" class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition flex items-center space-x-2">
                            <i class="fas fa-sync-alt"></i>
                            <span>Reset All Broken Passwords</span>
                        </button>
                    </form>
                    <p class="text-xs text-amber-300/60 mt-3">
                        Temporary password: <code class="bg-slate-900/50 px-2 py-1 rounded">Welcome123!</code>
                    </p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Employees with Broken Passwords -->
        <?php if (count($brokenEmployees) > 0): ?>
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-lg overflow-hidden mb-8 backdrop-blur-sm">
            <div class="bg-slate-900/50 px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white">
                    <i class="fas fa-user-times text-red-400 mr-2"></i>Broken Passwords
                </h2>
                <span class="text-xs bg-red-500/20 text-red-300 px-3 py-1 rounded-full border border-red-500/50">
                    <?php echo count($brokenEmployees); ?> employee<?php echo count($brokenEmployees) !== 1 ? 's' : ''; ?>
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50 border-b border-slate-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-slate-300 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brokenEmployees as $emp): ?>
                        <tr class="border-b border-slate-700/30 hover:bg-slate-700/20 transition">
                            <td class="px-6 py-4 text-sm text-slate-300"><?php echo "{$emp['fname']} {$emp['lname']}"; ?></td>
                            <td class="px-6 py-4 text-sm text-slate-400"><?php echo $emp['email']; ?></td>
                            <td class="px-6 py-4 text-sm text-slate-400"><?php echo $emp['username']; ?></td>
                            <td class="px-6 py-4 text-center">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="reset_single">
                                    <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                                    <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded transition inline-flex items-center space-x-1">
                                        <i class="fas fa-key"></i>
                                        <span>Reset</span>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- All Active Employees List -->
        <div class="bg-slate-800/50 border border-slate-700/50 rounded-lg overflow-hidden backdrop-blur-sm">
            <div class="bg-slate-900/50 px-6 py-4 border-b border-slate-700/50 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white">
                    <i class="fas fa-users text-cyan-400 mr-2"></i>All Active Employees
                </h2>
                <span class="text-xs bg-slate-700/50 text-slate-300 px-3 py-1 rounded-full border border-slate-600/50">
                    <?php echo $totalEmployees; ?> total
                </span>
            </div>

            <!-- Employees Table -->
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-slate-900/50 border-b border-slate-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-300 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginatedEmployees as $emp): ?>
                        <tr class="border-b border-slate-700/30 hover:bg-slate-700/20 transition">
                            <td class="px-6 py-4 text-sm text-slate-300"><?php echo "{$emp['fname']} {$emp['lname']}"; ?></td>
                            <td class="px-6 py-4 text-sm text-slate-400"><?php echo $emp['email']; ?></td>
                            <td class="px-6 py-4 text-sm text-slate-400"><?php echo $emp['username']; ?></td>
                            <td class="px-6 py-4 text-sm">
                                <?php if (strpos($emp['password'], '$2') === 0): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-900/30 text-green-300 border border-green-500/30">
                                        <i class="fas fa-check-circle mr-1"></i>Bcrypt ✓
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-900/30 text-red-300 border border-red-500/30">
                                        <i class="fas fa-exclamation-circle mr-1"></i>Invalid
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="bg-slate-900/50 border-t border-slate-700/50 px-6 py-4">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <!-- Info Text -->
                    <div class="text-xs text-slate-400">
                        Showing <span class="font-semibold text-slate-300"><?php echo $offset + 1; ?></span> 
                        to <span class="font-semibold text-slate-300"><?php echo min($offset + $itemsPerPage, $totalEmployees); ?></span> 
                        of <span class="font-semibold text-slate-300"><?php echo $totalEmployees; ?></span> employees
                    </div>

                    <!-- Pagination Controls -->
                    <div class="flex items-center space-x-1">
                        <!-- Previous -->
                        <?php if ($currentPage > 1): ?>
                        <a href="?page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $itemsPerPage; ?>" class="px-3 py-1.5 text-slate-300 hover:text-white hover:bg-slate-700/50 border border-slate-600 rounded-lg transition text-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php else: ?>
                        <button disabled class="px-3 py-1.5 text-slate-600 bg-slate-700/20 border border-slate-600 rounded-lg opacity-50 text-sm">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php 
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        if ($startPage > 1): ?>
                        <a href="?page=1&per_page=<?php echo $itemsPerPage; ?>" class="px-3 py-1.5 text-slate-300 hover:text-white hover:bg-slate-700/50 border border-slate-600 rounded-lg transition text-sm">1</a>
                        <?php if ($startPage > 2): ?>
                        <span class="px-2 text-slate-500">...</span>
                        <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php if ($i == $currentPage): ?>
                            <button class="px-3 py-1.5 text-white bg-cyan-600/50 border border-cyan-500 rounded-lg text-sm font-medium">
                                <?php echo $i; ?>
                            </button>
                            <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&per_page=<?php echo $itemsPerPage; ?>" class="px-3 py-1.5 text-slate-300 hover:text-white hover:bg-slate-700/50 border border-slate-600 rounded-lg transition text-sm">
                                <?php echo $i; ?>
                            </a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                        <span class="px-2 text-slate-500">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $totalPages; ?>&per_page=<?php echo $itemsPerPage; ?>" class="px-3 py-1.5 text-slate-300 hover:text-white hover:bg-slate-700/50 border border-slate-600 rounded-lg transition text-sm">
                            <?php echo $totalPages; ?>
                        </a>
                        <?php endif; ?>

                        <!-- Next -->
                        <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $itemsPerPage; ?>" class="px-3 py-1.5 text-slate-300 hover:text-white hover:bg-slate-700/50 border border-slate-600 rounded-lg transition text-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php else: ?>
                        <button disabled class="px-3 py-1.5 text-slate-600 bg-slate-700/20 border border-slate-600 rounded-lg opacity-50 text-sm">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Items Per Page -->
                    <div class="flex items-center space-x-2">
                        <label class="text-xs text-slate-400">Per page:</label>
                        <select onchange="window.location.href = '?page=1&per_page=' + this.value" class="px-2 py-1.5 text-xs bg-slate-700/50 text-slate-300 border border-slate-600 rounded-lg hover:border-cyan-500 transition focus:outline-none focus:border-cyan-500">
                            <option value="10" <?php echo $itemsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="20" <?php echo $itemsPerPage == 20 ? 'selected' : ''; ?>>20</option>
                            <option value="50" <?php echo $itemsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $itemsPerPage == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../views/layouts/footer.php'; ?>
