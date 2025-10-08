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
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div class="fixed inset-y-0 left-0 w-64 bg-gray-900 text-white">
        <div class="flex items-center justify-center h-16 bg-gray-800">
            <i class="fas fa-layer-group text-xl mr-2"></i>
            <span class="text-xl font-bold">ResolveIT</span>
        </div>
        
        <nav class="mt-6">
            <a href="dashboard.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-th-large w-6"></i>
                <span>Dashboard</span>
            </a>
            <a href="tickets.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-ticket-alt w-6"></i>
                <span>Tickets</span>
            </a>
            <a href="customers.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-users w-6"></i>
                <span>Employees</span>
            </a>
            <a href="categories.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-folder w-6"></i>
                <span>Categories</span>
            </a>
            <a href="admin.php" class="flex items-center px-6 py-3 bg-gray-800 text-white">
                <i class="fas fa-cog w-6"></i>
                <span>Admin Settings</span>
            </a>
            <a href="../article.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition">
                <i class="fas fa-newspaper w-6"></i>
                <span>Article</span>
            </a>
            <a href="../logout.php" class="flex items-center px-6 py-3 text-gray-300 hover:bg-gray-800 hover:text-white transition mt-8">
                <i class="fas fa-sign-out-alt w-6"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-white shadow-sm">
            <div class="flex items-center justify-between px-8 py-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Admin Settings</h1>
                    <p class="text-gray-600">Manage system users and settings</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=2563eb&color=fff" 
                             alt="User" 
                             class="w-10 h-10 rounded-full">
                        <div>
                            <div class="text-sm font-medium"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($currentUser['role']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
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
</body>
</html>
