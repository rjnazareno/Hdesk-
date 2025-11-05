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

// Set page variables for header
include_once __DIR__ . '/../views/layouts/header.php';
?>

<div class="lg:ml-64 min-h-screen bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-white border-b border-gray-200">
        <div class="flex items-center justify-between px-8 py-4 pt-20 lg:pt-4">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Reset Employee Passwords</h1>
                <p class="text-sm text-gray-500 mt-1">Fix employee login issues by regenerating passwords</p>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="p-8">
        <!-- Messages -->
        <?php if ($message): ?>
        <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 text-green-800">
            <i class="fas fa-check-circle mr-2"></i><?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4 text-red-800">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $errorMessage; ?>
        </div>
        <?php endif; ?>

        <!-- Status Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Working Passwords (bcrypt)</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo count($bcryptEmployees); ?></p>
                    </div>
                    <i class="fas fa-check-circle text-green-600 text-4xl opacity-20"></i>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Broken Passwords (need reset)</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo count($brokenEmployees); ?></p>
                    </div>
                    <i class="fas fa-exclamation-circle text-red-600 text-4xl opacity-20"></i>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <?php if (count($brokenEmployees) > 0): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8">
            <h2 class="text-lg font-semibold text-yellow-900 mb-4">
                <i class="fas fa-warning mr-2"></i><?php echo count($brokenEmployees); ?> Employee(s) Cannot Login
            </h2>
            <p class="text-yellow-800 mb-6">
                These employees have passwords in non-bcrypt format. They cannot login because PHP's <code>password_verify()</code> only works with bcrypt.
            </p>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="reset_all_broken">
                <button type="submit" class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                    <i class="fas fa-sync-alt mr-2"></i>Reset All Broken Passwords
                </button>
            </form>
            <p class="text-sm text-yellow-700 mt-4">
                Temporary password: <strong>Welcome123!</strong> (employees should change on first login)
            </p>
        </div>
        <?php endif; ?>

        <!-- Employees with Broken Passwords -->
        <?php if (count($brokenEmployees) > 0): ?>
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden mb-8">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Employees with Broken Passwords</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($brokenEmployees as $emp): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo "{$emp['fname']} {$emp['lname']}"; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo $emp['email']; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo $emp['username']; ?></td>
                            <td class="px-6 py-4 text-sm">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="reset_single">
                                    <input type="hidden" name="employee_id" value="<?php echo $emp['id']; ?>">
                                    <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 transition text-xs">
                                        Reset
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
        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">All Active Employees (<?php echo count($employees); ?>)</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Password Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($employees as $emp): ?>
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm text-gray-900"><?php echo "{$emp['fname']} {$emp['lname']}"; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo $emp['email']; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo $emp['username']; ?></td>
                            <td class="px-6 py-4 text-sm">
                                <?php if (strpos($emp['password'], '$2') === 0): ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        <i class="fas fa-check-circle mr-1"></i>Bcrypt ✓
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <i class="fas fa-exclamation-circle mr-1"></i>Invalid Hash
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/../views/layouts/footer.php'; ?>
