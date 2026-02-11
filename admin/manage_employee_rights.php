<?php
/**
 * Employee Admin Rights Manager
 * Assign IT, HR, or Super Admin rights to employees
 * Super Admin only
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../models/Employee.php';

$auth = new Auth();
$auth->requireLogin();

// Only Super Admin can access (users table admin OR employee with superadmin rights)
$isSuperAdmin = ($auth->getUserRole() === 'admin') || (($_SESSION['admin_rights'] ?? null) === 'superadmin');
if (!$isSuperAdmin) {
    $_SESSION['error'] = "Access denied. Super Admin only.";
    redirect('admin/dashboard.php');
}

$employeeModel = new Employee();
$currentUser = $auth->getCurrentUser();
$currentUserId = $currentUser['id'];

// Handle AJAX update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['employee_id']) && isset($_POST['admin_rights'])) {
    header('Content-Type: application/json');
    
    $employeeId = intval($_POST['employee_id']);
    $adminRights = $_POST['admin_rights'] === '' ? null : $_POST['admin_rights'];
    
    // Get the employee to check current rights
    $employee = $employeeModel->findById($employeeId);
    
    // Prevent removing superadmin rights (protection)
    if ($employee && $employee['admin_rights_hdesk'] === 'superadmin' && $adminRights !== 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Cannot remove Super Admin rights. Super Admin accounts are protected.']);
        exit;
    }
    
    // Validate admin_rights value
    if ($adminRights !== null && !in_array($adminRights, ['it', 'hr', 'superadmin'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid admin rights value']);
        exit;
    }
    
    // Update role to 'internal' if assigning admin rights, keep as is if removing
    $updateData = ['admin_rights_hdesk' => $adminRights];
    if ($adminRights !== null) {
        $updateData['role'] = 'internal';
    }
    
    $result = $employeeModel->update($employeeId, $updateData);
    
    echo json_encode(['success' => $result, 'message' => $result ? 'Updated successfully' : 'Update failed']);
    exit;
}

// Get all employees with role='internal' (eligible for admin rights)
// Regular employees (role='employee') cannot have admin rights
$internalEmployees = $employeeModel->getByRole('internal', 'active');

// Separate admin employees from internal employees without rights
$adminEmployees = array_filter($internalEmployees, fn($e) => !empty($e['admin_rights_hdesk']));
$regularEmployees = array_filter($internalEmployees, fn($e) => empty($e['admin_rights_hdesk']));

// Stats
$stats = [
    'total' => count($internalEmployees),
    'superadmin' => count(array_filter($internalEmployees, fn($e) => $e['admin_rights_hdesk'] === 'superadmin')),
    'it' => count(array_filter($internalEmployees, fn($e) => $e['admin_rights_hdesk'] === 'it')),
    'hr' => count(array_filter($internalEmployees, fn($e) => $e['admin_rights_hdesk'] === 'hr'))
];

$pageTitle = 'Manage Employee Admin Rights';
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="lg:ml-64 p-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-user-shield text-purple-600"></i>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Employee Admin Rights</h1>
                <p class="text-sm text-gray-500">Manage admin access for internal employees (role='internal' only)</p>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-gray-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Internal Employees</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['total'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-crown text-red-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Super Admins</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['superadmin'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-laptop-code text-blue-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">IT Admins</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['it'] ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white border border-gray-200 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users-cog text-green-600"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">HR Admins</p>
                    <p class="text-2xl font-bold text-gray-900"><?= $stats['hr'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button onclick="showTab('current-admins')" id="tab-current-admins"
                        class="tab-btn border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600">
                    <i class="fas fa-user-shield mr-2"></i>Current Admins (<?= count($adminEmployees) ?>)
                </button>
                <button onclick="showTab('assign-rights')" id="tab-assign-rights"
                        class="tab-btn border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300">
                    <i class="fas fa-user-plus mr-2"></i>Assign Rights
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab: Current Admins -->
    <div id="panel-current-admins" class="tab-panel">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-user-shield mr-2 text-purple-600"></i>Employees with Admin Rights
                </h2>
                <p class="text-sm text-gray-500 mt-1">Edit or retract admin rights (Super Admin accounts are protected)</p>
            </div>
            
            <?php if (empty($adminEmployees)): ?>
            <div class="p-8 text-center text-gray-500">
                <i class="fas fa-users-slash text-4xl mb-3"></i>
                <p>No employees with admin rights yet.</p>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Current Rights</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($adminEmployees as $employee): 
                            $rights = $employee['admin_rights_hdesk'];
                            $isSuperAdminEmployee = $rights === 'superadmin';
                        ?>
                        <tr class="hover:bg-gray-50" data-employee-id="<?= $employee['id'] ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($employee['fname'] . ' ' . $employee['lname']) ?>&background=000000&color=fff" 
                                         alt="" class="w-10 h-10 rounded-full">
                                    <div>
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($employee['fname'] . ' ' . $employee['lname']) ?></p>
                                        <p class="text-sm text-gray-500">@<?= htmlspecialchars($employee['username']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($employee['company'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($employee['position'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4">
                                <span class="current-rights-badge">
                                    <?php if ($rights === 'superadmin'): ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded inline-flex items-center gap-1">
                                            <i class="fas fa-crown"></i> Super Admin
                                        </span>
                                    <?php elseif ($rights === 'it'): ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded">IT Admin</span>
                                    <?php elseif ($rights === 'hr'): ?>
                                        <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-800 rounded">HR Admin</span>
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($isSuperAdminEmployee): ?>
                                    <span class="text-xs text-gray-400 italic">
                                        <i class="fas fa-lock mr-1"></i>Protected
                                    </span>
                                <?php else: ?>
                                    <div class="flex items-center gap-2">
                                        <select class="admin-rights-select px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 bg-white"
                                                data-employee-id="<?= $employee['id'] ?>"
                                                data-current="<?= $rights ?>">
                                            <option value="it" <?= $rights === 'it' ? 'selected' : '' ?>>IT Admin</option>
                                            <option value="hr" <?= $rights === 'hr' ? 'selected' : '' ?>>HR Admin</option>
                                        </select>
                                        <button onclick="retractRights(<?= $employee['id'] ?>, '<?= htmlspecialchars($employee['fname'] . ' ' . $employee['lname']) ?>')"
                                                class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded hover:bg-red-200 transition"
                                                title="Remove admin rights">
                                            <i class="fas fa-user-minus"></i> Retract
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tab: Assign Rights -->
    <div id="panel-assign-rights" class="tab-panel hidden">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">
                            <i class="fas fa-user-plus mr-2 text-blue-600"></i>Assign Admin Rights
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">Grant admin access to internal employees (role='internal' without rights)</p>
                    </div>
                    <div class="relative">
                        <input type="text" id="employeeSearch" placeholder="Search employees..." 
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 text-sm">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                <?php if (empty($regularEmployees)): ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-trophy text-4xl mb-3 text-yellow-500"></i>
                    <p class="font-medium">All internal employees have admin rights!</p>
                    <p class="text-sm mt-1">No more internal staff to assign rights to.</p>
                    <p class="text-xs mt-2 text-gray-400">Tip: Regular employees (role='employee') cannot have admin rights.</p>
                </div>
                <?php else: ?>
                <table class="w-full">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Internal Employee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Assign Rights</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="employeeTableBody">
                        <?php foreach ($regularEmployees as $employee): ?>
                        <tr class="hover:bg-gray-50 employee-row" 
                            data-employee-id="<?= $employee['id'] ?>"
                            data-search-name="<?= strtolower($employee['fname'] . ' ' . $employee['lname']) ?>"
                            data-search-username="<?= strtolower($employee['username']) ?>"
                            data-search-dept="<?= strtolower($employee['company'] ?? '') ?>"
                            data-search-position="<?= strtolower($employee['position'] ?? '') ?>">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($employee['fname'] . ' ' . $employee['lname']) ?>&background=random&color=fff" 
                                         alt="" class="w-10 h-10 rounded-full">
                                    <div>
                                        <p class="font-medium text-gray-900"><?= htmlspecialchars($employee['fname'] . ' ' . $employee['lname']) ?></p>
                                        <p class="text-sm text-gray-500">@<?= htmlspecialchars($employee['username']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($employee['company'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($employee['position'] ?? 'N/A') ?></td>
                            <td class="px-6 py-4">
                                <select class="assign-rights-select px-3 py-1.5 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white"
                                        data-employee-id="<?= $employee['id'] ?>"
                                        data-name="<?= htmlspecialchars($employee['fname'] . ' ' . $employee['lname']) ?>">
                                    <option value="">-- Select --</option>
                                    <option value="it">IT Admin</option>
                                    <option value="hr">HR Admin</option>
                                    <option value="superadmin">Super Admin</option>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all panels
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.add('hidden'));
    // Deactivate all tabs
    document.querySelectorAll('.tab-btn').forEach(t => {
        t.classList.remove('border-blue-500', 'text-blue-600');
        t.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected panel
    document.getElementById('panel-' + tabName).classList.remove('hidden');
    // Activate selected tab
    const activeTab = document.getElementById('tab-' + tabName);
    activeTab.classList.remove('border-transparent', 'text-gray-500');
    activeTab.classList.add('border-blue-500', 'text-blue-600');
}

function retractRights(employeeId, employeeName) {
    if (!confirm(`Remove admin rights from ${employeeName}?\n\nThey will no longer have access to the admin panel.`)) {
        return;
    }
    
    updateRights(employeeId, '', `Admin rights removed from ${employeeName}`);
}

function updateRights(employeeId, newValue, successMessage) {
    fetch('manage_employee_rights.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `employee_id=${employeeId}&admin_rights=${newValue}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(successMessage || 'Rights updated successfully!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error, 'error');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Edit rights for current admins (IT/HR only, not superadmin)
    document.querySelectorAll('.admin-rights-select').forEach(select => {
        select.addEventListener('change', function() {
            const employeeId = this.dataset.employeeId;
            const newValue = this.value;
            const oldValue = this.dataset.current;
            
            if (newValue === oldValue) return;
            
            if (confirm(`Change admin rights to ${newValue.toUpperCase()}?`)) {
                updateRights(employeeId, newValue, 'Rights updated successfully!');
            } else {
                this.value = oldValue;
            }
        });
    });
    
    // Assign rights to regular employees
    document.querySelectorAll('.assign-rights-select').forEach(select => {
        select.addEventListener('change', function() {
            const employeeId = this.dataset.employeeId;
            const employeeName = this.dataset.name;
            const newValue = this.value;
            
            if (!newValue) return;
            
            if (confirm(`Grant ${newValue.toUpperCase()} rights to ${employeeName}?`)) {
                updateRights(employeeId, newValue, `${employeeName} is now ${newValue.toUpperCase()} Admin!`);
            } else {
                this.value = '';
            }
        });
    });
    
    // Search functionality
    const searchInput = document.getElementById('employeeSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            document.querySelectorAll('.employee-row').forEach(row => {
                const name = row.dataset.searchName || '';
                const username = row.dataset.searchUsername || '';
                const dept = row.dataset.searchDept || '';
                const position = row.dataset.searchPosition || '';
                
                const matches = name.includes(searchTerm) || 
                              username.includes(searchTerm) || 
                              dept.includes(searchTerm) || 
                              position.includes(searchTerm);
                
                row.style.display = matches ? '' : 'none';
            });
        });
    }
});

function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} text-white`;
    notification.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>${message}`;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
