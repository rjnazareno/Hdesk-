<?php
require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireRole('admin'); // Only admins can access

$currentUser = $auth->getCurrentUser();
$userModel = new User();
$employeeModel = new Employee();

// Get all IT staff/admins
$allUsers = $userModel->getAll();

// Handle user management actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'toggle_status') {
        $userId = (int)$_POST['user_id'];
        $user = $userModel->findById($userId);
        
        if ($user) {
            $newStatus = $user['is_active'] ? 0 : 1;
            $userModel->update($userId, ['is_active' => $newStatus]);
            redirect('admin.php?success=status_updated');
        }
    }
    
    if ($action === 'edit_user') {
        $userId = (int)$_POST['user_id'];
        $updateData = [
            'username' => sanitize($_POST['username']),
            'full_name' => sanitize($_POST['full_name']),
            'email' => sanitize($_POST['email']),
            'role' => sanitize($_POST['role']),
            'department' => sanitize($_POST['department']),
            'phone' => sanitize($_POST['phone'])
        ];
        
        if ($userModel->update($userId, $updateData)) {
            redirect('admin.php?success=user_updated');
        } else {
            redirect('admin.php?error=update_failed');
        }
    }
    
    if ($action === 'change_password') {
        $userId = (int)$_POST['user_id'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        if ($newPassword !== $confirmPassword) {
            redirect('admin.php?error=password_mismatch');
        } elseif (strlen($newPassword) < 6) {
            redirect('admin.php?error=password_short');
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            if ($userModel->update($userId, ['password' => $hashedPassword])) {
                redirect('admin.php?success=password_changed');
            } else {
                redirect('admin.php?error=password_change_failed');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Quick Wins CSS -->
    <link rel="stylesheet" href="../assets/css/print.css">
    <link rel="stylesheet" href="../assets/css/dark-mode.css">
</head>
<body class="bg-gray-50">
    <?php include __DIR__ . '/../includes/admin_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Enhanced Top Bar -->
        <div class="bg-gradient-to-r from-white to-blue-50 shadow-sm border-b border-blue-100">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
                <!-- Left Section: Title & Stats -->
                <div class="flex items-center space-x-4">
                    <div class="hidden lg:flex items-center justify-center w-14 h-14 rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 shadow-lg">
                        <i class="fas fa-user-shield text-white text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-gray-900 to-blue-600 bg-clip-text text-transparent">
                            Admin Settings
                        </h1>
                        <div class="flex items-center space-x-3 mt-1">
                            <p class="text-sm text-gray-600">Manage system users and settings</p>
                            <span class="hidden md:inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <i class="fas fa-crown mr-1"></i>
                                Admin Access
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Right Section: Actions & User -->
                <div class="flex items-center space-x-3">
                    <!-- Search (Hidden on Mobile) -->
                    <div class="hidden md:block relative">
                        <input 
                            type="text" 
                            placeholder="Search users..." 
                            class="pl-10 pr-4 py-2 w-48 lg:w-64 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm transition-all"
                            id="quickSearch"
                        >
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                    </div>

                    <!-- Dark Mode Toggle -->
                    <button id="darkModeToggle" class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition" title="Toggle dark mode">
                        <i id="dark-mode-icon" class="fas fa-moon"></i>
                    </button>

                    <!-- Quick Actions Dropdown -->
                    <div class="relative" id="quickActionsDropdown">
                        <button class="flex items-center space-x-2 px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition" id="quickActionsBtn">
                            <i class="fas fa-bolt text-blue-600"></i>
                            <span class="hidden lg:inline text-sm font-medium">Quick Actions</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50" id="quickActionsMenu">
                            <div class="py-2">
                                <a href="add_user.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                    <i class="fas fa-user-plus w-5"></i>
                                    <span class="ml-3">Add IT Staff</span>
                                </a>
                                <a href="system_settings.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                    <i class="fas fa-cogs w-5"></i>
                                    <span class="ml-3">System Settings</span>
                                </a>
                                <div class="border-t border-gray-200 my-1"></div>
                                <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition" onclick="exportUserList(); return false;">
                                    <i class="fas fa-file-export w-5 text-green-600"></i>
                                    <span class="ml-3">Export Users</span>
                                </a>
                                <a href="#" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition" onclick="viewAuditLog(); return false;">
                                    <i class="fas fa-history w-5"></i>
                                    <span class="ml-3">Audit Log</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Bell -->
                    <button class="relative p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition" title="Notifications" id="notificationBell">
                        <i class="far fa-bell text-lg"></i>
                        <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                    </button>

                    <!-- User Avatar with Dropdown -->
                    <div class="relative" id="userMenuDropdown">
                        <button class="flex items-center space-x-2 p-1 hover:bg-gray-100 rounded-lg transition" id="userMenuBtn">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                                 alt="User" 
                                 class="w-10 h-10 rounded-full ring-2 ring-blue-200"
                                 title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                            <div class="hidden lg:block text-left">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?></div>
                                <div class="text-xs text-gray-500"><?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?></div>
                            </div>
                            <i class="fas fa-chevron-down text-xs text-gray-500 hidden lg:block"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50" id="userMenu">
                            <div class="p-4 border-b border-gray-200">
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <i class="fas fa-crown mr-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="py-2">
                                <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                    <i class="fas fa-user w-5"></i>
                                    <span class="ml-3">My Profile</span>
                                </a>
                                <a href="dashboard.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                                    <i class="fas fa-tachometer-alt w-5"></i>
                                    <span class="ml-3">Dashboard</span>
                                </a>
                                <div class="border-t border-gray-200 my-1"></div>
                                <a href="../logout.php" class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition">
                                    <i class="fas fa-sign-out-alt w-5"></i>
                                    <span class="ml-3">Logout</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Search Bar -->
            <div class="md:hidden px-4 pb-4">
                <div class="relative">
                    <input 
                        type="text" 
                        placeholder="Search users..." 
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                        id="mobileQuickSearch"
                    >
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-blue-600">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-gray-700">Admin Settings</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    <?php
                    $messages = [
                        'status_updated' => 'User status updated successfully!',
                        'user_updated' => 'User information updated successfully!',
                        'password_changed' => 'Password changed successfully!',
                    ];
                    echo $messages[$_GET['success']] ?? 'Operation completed successfully!';
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <?php
                    $errors = [
                        'update_failed' => 'Failed to update user information.',
                        'password_mismatch' => 'Passwords do not match!',
                        'password_short' => 'Password must be at least 6 characters long!',
                        'password_change_failed' => 'Failed to change password.',
                    ];
                    echo $errors[$_GET['error']] ?? 'An error occurred.';
                    ?>
                </div>
            <?php endif; ?>

            <!-- IT Staff Management -->
            <div class="bg-white rounded-xl shadow-sm mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold">IT Staff & Admin Management</h3>
                    <p class="text-gray-600 text-sm">Manage system administrators and IT staff</p>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-gray-600 text-sm border-b">
                                    <th class="pb-3">ID</th>
                                    <th class="pb-3">Username</th>
                                    <th class="pb-3">Full Name</th>
                                    <th class="pb-3">Email</th>
                                    <th class="pb-3">Role</th>
                                    <th class="pb-3">Department</th>
                                    <th class="pb-3">Status</th>
                                    <th class="pb-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                <?php foreach ($allUsers as $user): ?>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-4"><?php echo $user['id']; ?></td>
                                    <td class="py-4">
                                        <span class="font-medium"><?php echo htmlspecialchars($user['username']); ?></span>
                                    </td>
                                    <td class="py-4"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td class="py-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="py-4">
                                        <?php
                                        $roleColors = [
                                            'admin' => 'bg-purple-100 text-purple-800',
                                            'it_staff' => 'bg-blue-100 text-blue-800'
                                        ];
                                        $roleClass = $roleColors[$user['role']] ?? 'bg-gray-100 text-gray-800';
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $roleClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td class="py-4"><?php echo htmlspecialchars($user['department'] ?? '-'); ?></td>
                                    <td class="py-4">
                                        <?php if ($user['is_active']): ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                    class="text-blue-600 hover:text-blue-800" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="openPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                    class="text-green-600 hover:text-green-800" title="Change Password">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($user['id'] !== $currentUser['id']): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="text-orange-600 hover:text-orange-800" 
                                                            title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                                            onclick="return confirm('Are you sure you want to change this user\'s status?')">
                                                        <i class="fas fa-toggle-<?php echo $user['is_active'] ? 'on' : 'off'; ?>"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- System Information -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Total IT Staff</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1"><?php echo count($allUsers); ?></h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-lg">
                            <i class="fas fa-user-shield text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Active Admins</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1">
                                <?php 
                                echo count(array_filter($allUsers, function($u) {
                                    return $u['role'] === 'admin' && $u['is_active'];
                                }));
                                ?>
                            </h3>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-lg">
                            <i class="fas fa-crown text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Active IT Staff</p>
                            <h3 class="text-2xl font-bold text-gray-900 mt-1">
                                <?php 
                                echo count(array_filter($allUsers, function($u) {
                                    return $u['role'] === 'it_staff' && $u['is_active'];
                                }));
                                ?>
                            </h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-lg">
                            <i class="fas fa-users text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Edit User Information</h3>
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                        <input type="text" name="username" id="edit_username" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" name="email" id="edit_email" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                        <select name="role" id="edit_role" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                            <option value="it_staff">IT Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <input type="text" name="department" id="edit_department" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Phone</label>
                        <input type="text" name="phone" id="edit_phone" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeEditModal()" 
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Change Password</h3>
                <p class="text-sm text-gray-600 mb-4">Changing password for: <strong id="password_username"></strong></p>
                <form method="POST" id="passwordForm">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="user_id" id="password_user_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                        <input type="password" name="new_password" id="new_password" required minlength="6"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Enter new password (min 6 characters)">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                               placeholder="Confirm new password">
                    </div>
                    
                    <div id="password_error" class="hidden mb-4 text-red-600 text-sm"></div>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closePasswordModal()" 
                                class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Change Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_department').value = user.department || '';
            document.getElementById('edit_phone').value = user.phone || '';
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function openPasswordModal(userId, username) {
            document.getElementById('password_user_id').value = userId;
            document.getElementById('password_username').textContent = username;
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
            document.getElementById('password_error').classList.add('hidden');
            document.getElementById('passwordModal').classList.remove('hidden');
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').classList.add('hidden');
        }

        // Password validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const errorDiv = document.getElementById('password_error');

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                errorDiv.textContent = 'Passwords do not match!';
                errorDiv.classList.remove('hidden');
                return false;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                errorDiv.textContent = 'Password must be at least 6 characters long!';
                errorDiv.classList.remove('hidden');
                return false;
            }

            return true;
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            const editModal = document.getElementById('editModal');
            const passwordModal = document.getElementById('passwordModal');
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === passwordModal) {
                closePasswordModal();
            }
        }
    </script>
    
    <!-- Quick Wins JavaScript -->
    <script src="../assets/js/helpers.js"></script>
    <script src="../assets/js/notifications.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initTooltips();
            initDarkMode();
            updateTimeAgo();
            setInterval(updateTimeAgo, 60000);

            // Quick Actions Dropdown
            const quickActionsBtn = document.getElementById('quickActionsBtn');
            const quickActionsMenu = document.getElementById('quickActionsMenu');
            
            if (quickActionsBtn && quickActionsMenu) {
                quickActionsBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    quickActionsMenu.classList.toggle('hidden');
                    // Close user menu if open
                    const userMenu = document.getElementById('userMenu');
                    if (userMenu) userMenu.classList.add('hidden');
                });
            }

            // User Menu Dropdown
            const userMenuBtn = document.getElementById('userMenuBtn');
            const userMenu = document.getElementById('userMenu');
            
            if (userMenuBtn && userMenu) {
                userMenuBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userMenu.classList.toggle('hidden');
                    // Close quick actions if open
                    if (quickActionsMenu) quickActionsMenu.classList.add('hidden');
                });
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function() {
                if (quickActionsMenu) quickActionsMenu.classList.add('hidden');
                if (userMenu) userMenu.classList.add('hidden');
            });

            // Quick Search Functionality
            const quickSearch = document.getElementById('quickSearch');
            const mobileQuickSearch = document.getElementById('mobileQuickSearch');
            
            function handleQuickSearch(searchValue) {
                const searchTerm = searchValue.toLowerCase().trim();
                const rows = document.querySelectorAll('tbody tr');
                let visibleCount = 0;
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm) || searchTerm === '') {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Update stats if searching
                const countBadge = document.querySelector('.bg-purple-100.text-purple-800');
                if (countBadge && searchTerm) {
                    const icon = countBadge.querySelector('i');
                    countBadge.innerHTML = (icon ? icon.outerHTML : '<i class="fas fa-search mr-1"></i>') + 
                                          visibleCount + ' Found';
                } else if (countBadge && !searchTerm) {
                    // Reset to original
                    countBadge.innerHTML = '<i class="fas fa-crown mr-1"></i>Admin Access';
                }
            }
            
            if (quickSearch) {
                quickSearch.addEventListener('input', function() {
                    handleQuickSearch(this.value);
                    // Sync with mobile search
                    if (mobileQuickSearch) mobileQuickSearch.value = this.value;
                });
            }
            
            if (mobileQuickSearch) {
                mobileQuickSearch.addEventListener('input', function() {
                    handleQuickSearch(this.value);
                    // Sync with desktop search
                    if (quickSearch) quickSearch.value = this.value;
                });
            }

            // Export Users function
            window.exportUserList = function() {
                // Simple CSV export
                const users = <?php echo json_encode($allUsers); ?>;
                let csv = 'ID,Username,Full Name,Email,Role,Department,Status\n';
                
                users.forEach(user => {
                    csv += `${user.id},"${user.username}","${user.full_name}","${user.email}","${user.role}","${user.department || ''}","${user.is_active ? 'Active' : 'Inactive'}"\n`;
                });
                
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'users_' + new Date().toISOString().split('T')[0] + '.csv';
                a.click();
                window.URL.revokeObjectURL(url);
            };

            // View Audit Log function
            window.viewAuditLog = function() {
                alert('Audit Log feature coming soon!\n\nThis will show:\n- User login history\n- User modifications\n- Permission changes\n- System access logs');
            };
        });
    </script>
</body>
</html>
