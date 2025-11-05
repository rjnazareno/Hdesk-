<?php
$pageTitle = 'Admin Settings - IT Help Desk';
$baseUrl = '../';
?>
<?php include __DIR__ . '/../layouts/header.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
        <!-- Top Bar -->
        <div class="bg-slate-800/50 border-b border-slate-700/50 backdrop-blur-md">
            <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
                <!-- Left Section: Title & Stats -->
                <div class="flex items-center space-x-4">
                    <div class="hidden lg:flex items-center justify-center w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded-lg">
                        <i class="fas fa-user-shield text-sm"></i>
                    </div>
                    <div>
                        <h1 class="text-xl lg:text-2xl font-semibold text-white">
                            Admin Settings
                        </h1>
                        <div class="flex items-center space-x-3 mt-0.5">
                            <p class="text-sm text-slate-400">Manage system users and settings</p>
                            <span class="hidden md:inline-flex items-center px-2 py-0.5 text-xs font-medium border border-slate-600 text-slate-300 bg-slate-700/30 rounded">
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
                            class="pl-10 pr-4 py-2 w-48 lg:w-64 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm transition-all"
                            id="quickSearch"
                        >
                        <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-sm"></i>
                    </div>

                    <!-- Quick Actions Dropdown -->
                    <div class="relative" id="quickActionsDropdown">
                        <button class="flex items-center space-x-2 px-4 py-2 border border-slate-600 bg-slate-700/50 text-slate-300 hover:text-white hover:border-cyan-500/50 rounded-lg transition" id="quickActionsBtn">
                            <i class="fas fa-bolt text-cyan-500"></i>
                            <span class="hidden lg:inline text-sm font-medium">Quick Actions</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-56 bg-slate-800 rounded-lg shadow-xl border border-slate-700/50 hidden z-50" id="quickActionsMenu">
                            <div class="py-2">
                                <a href="#" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition" onclick="openAddUserModal('it_staff'); return false;">
                                    <i class="fas fa-user-plus w-5 text-cyan-500"></i>
                                    <span class="ml-3">Add IT Staff</span>
                                </a>
                                <a href="#" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition" onclick="openAddUserModal('user'); return false;">
                                    <i class="fas fa-user w-5 text-emerald-500"></i>
                                    <span class="ml-3">Add User</span>
                                </a>
                                <div class="border-t border-slate-700/50 my-1"></div>
                                <a href="#" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition" onclick="exportUserList(); return false;">
                                    <i class="fas fa-file-export w-5 text-emerald-500"></i>
                                    <span class="ml-3">Export Users</span>
                                </a>
                                <a href="#" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition" onclick="viewAuditLog(); return false;">
                                    <i class="fas fa-history w-5 text-purple-500"></i>
                                    <span class="ml-3">Audit Log</span>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications Bell -->
                    <div class="relative" id="notificationDropdown">
                        <button class="relative p-2 text-slate-400 hover:text-white hover:bg-slate-700/50 rounded-lg transition" title="Notifications" id="notificationBell">
                            <i class="far fa-bell text-lg"></i>
                            <span class="absolute top-1 right-1 w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                        </button>
                        <div class="absolute right-0 mt-2 w-80 bg-slate-800 rounded-lg shadow-xl border border-slate-700/50 hidden z-50" id="notificationMenu">
                            <div class="p-4 border-b border-slate-700/50 flex items-center justify-between">
                                <h3 class="font-semibold text-white">Notifications</h3>
                                <span class="text-xs text-slate-400">3 new</span>
                            </div>
                            <div class="max-h-96 overflow-y-auto">
                                <!-- Notification Items -->
                                <a href="#" class="block px-4 py-3 hover:bg-slate-700/50 border-b border-slate-700/50 transition">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-full bg-blue-500/20 flex items-center justify-center">
                                                <i class="fas fa-ticket-alt text-blue-400"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-white">New ticket submitted</p>
                                            <p class="text-xs text-slate-400 mt-1">John Doe submitted a new hardware issue</p>
                                            <p class="text-xs text-slate-500 mt-1">5 minutes ago</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span class="w-2 h-2 bg-blue-400 rounded-full block"></span>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="block px-4 py-3 hover:bg-slate-700/50 border-b border-slate-700/50 transition">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-full bg-emerald-500/20 flex items-center justify-center">
                                                <i class="fas fa-user-plus text-emerald-400"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-white">New user registered</p>
                                            <p class="text-xs text-slate-400 mt-1">Jane Smith registered as a new employee</p>
                                            <p class="text-xs text-slate-500 mt-1">2 hours ago</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span class="w-2 h-2 bg-emerald-400 rounded-full block"></span>
                                        </div>
                                    </div>
                                </a>
                                <a href="#" class="block px-4 py-3 hover:bg-slate-700/50 transition">
                                    <div class="flex items-start space-x-3">
                                        <div class="flex-shrink-0">
                                            <div class="w-10 h-10 rounded-full bg-yellow-500/20 flex items-center justify-center">
                                                <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                            </div>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-white">High priority ticket</p>
                                            <p class="text-xs text-slate-400 mt-1">Network outage reported in Building A</p>
                                            <p class="text-xs text-slate-500 mt-1">4 hours ago</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span class="w-2 h-2 bg-yellow-400 rounded-full block"></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                            <div class="p-3 border-t border-slate-700/50 text-center">
                                <a href="#" class="text-sm text-cyan-400 hover:text-cyan-300 font-medium">View all notifications</a>
                            </div>
                        </div>
                    </div>

                    <!-- User Avatar with Dropdown -->
                    <div class="relative" id="userMenuDropdown">
                        <button class="flex items-center space-x-2 p-1 hover:bg-slate-700/50 transition" id="userMenuBtn">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=fff" 
                                 alt="User" 
                                 class="w-10 h-10 rounded-full"
                                 title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
                            <div class="hidden lg:block text-left">
                                <div class="text-sm font-medium text-white"><?php echo htmlspecialchars(explode(' ', $currentUser['full_name'])[0]); ?></div>
                                <div class="text-xs text-slate-400"><?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?></div>
                            </div>
                            <i class="fas fa-chevron-down text-xs text-slate-400 hidden lg:block"></i>
                        </button>
                        <div class="absolute right-0 mt-2 w-64 bg-slate-800 border border-slate-700/50 hidden z-50" id="userMenu">
                            <div class="p-4 border-b border-slate-700/50">
                                <div class="font-medium text-white"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                                <div class="text-sm text-slate-400"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium border border-slate-600 text-slate-300 bg-slate-700/30">
                                        <i class="fas fa-crown mr-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="py-2">
                                <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition">
                                    <i class="fas fa-user w-5"></i>
                                    <span class="ml-3">My Profile</span>
                                </a>
                                <a href="dashboard.php" class="flex items-center px-4 py-2 text-sm text-slate-300 hover:bg-slate-700/50 hover:text-cyan-400 transition">
                                    <i class="fas fa-tachometer-alt w-5"></i>
                                    <span class="ml-3">Dashboard</span>
                                </a>
                                <div class="border-t border-slate-700/50 my-1"></div>
                                <a href="../logout.php" class="flex items-center px-4 py-2 text-sm text-red-400 hover:bg-red-500/10 transition">
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
                        class="w-full pl-10 pr-4 py-2 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent text-sm"
                        id="mobileQuickSearch"
                    >
                    <i class="fas fa-search absolute left-3 top-3 text-slate-400 text-sm"></i>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Breadcrumb -->
            <nav class="flex mb-4" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-slate-400 hover:text-cyan-400">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                            </svg>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="ml-1 text-sm font-medium text-slate-300">Admin Settings</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <?php if (isset($_GET['success'])): ?>
                <div class="mb-6 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-4 py-3 rounded">
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
                <div class="mb-6 bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded">
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
            <div class="bg-slate-800/50 rounded-lg border border-slate-700/50 mb-6 overflow-hidden">
                <div class="p-6 border-b border-slate-700/50">
                    <h3 class="text-lg font-semibold text-white">IT Staff & Admin Management</h3>
                    <p class="text-slate-400 text-sm">Manage system administrators and IT staff</p>
                </div>
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="text-left text-slate-400 text-sm border-b border-slate-700/50">
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
                                <tr class="border-b border-slate-700/50 hover:bg-slate-700/30">
                                    <td class="py-4 text-white"><?php echo $user['id']; ?></td>
                                    <td class="py-4">
                                        <span class="font-medium text-white"><?php echo htmlspecialchars($user['username']); ?></span>
                                    </td>
                                    <td class="py-4 text-slate-300"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td class="py-4 text-slate-400"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="py-4">
                                        <?php
                                        $roleColors = [
                                            'admin' => 'bg-purple-600 text-white',
                                            'it_staff' => 'bg-blue-600 text-white'
                                        ];
                                        $roleClass = $roleColors[$user['role']] ?? 'bg-slate-700 text-slate-200';
                                        ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $roleClass; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                        </span>
                                    </td>
                                    <td class="py-4 text-slate-300"><?php echo htmlspecialchars($user['department'] ?? '-'); ?></td>
                                    <td class="py-4">
                                        <?php if ($user['is_active']): ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-emerald-600 text-white">Active</span>
                                        <?php else: ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-600 text-white">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-4">
                                        <div class="flex items-center space-x-2">
                                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)" 
                                                    class="text-cyan-400 hover:text-cyan-300" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="openPasswordModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                    class="text-emerald-400 hover:text-emerald-300" title="Change Password">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($user['id'] !== $currentUser['id']): ?>
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="action" value="toggle_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="text-yellow-400 hover:text-yellow-300" 
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
                <div class="bg-slate-800/50 rounded-lg border border-slate-700/50 p-6 overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-blue-500/10 to-transparent pointer-events-none"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <div>
                            <p class="text-slate-400 text-sm">Total IT Staff</p>
                            <h3 class="text-2xl font-bold text-white mt-1"><?php echo count($allUsers); ?></h3>
                        </div>
                        <div class="bg-blue-500/20 p-3 rounded-lg">
                            <i class="fas fa-user-shield text-blue-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800/50 rounded-lg border border-slate-700/50 p-6 overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-purple-500/10 to-transparent pointer-events-none"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <div>
                            <p class="text-slate-400 text-sm">Active Admins</p>
                            <h3 class="text-2xl font-bold text-white mt-1">
                                <?php 
                                echo count(array_filter($allUsers, function($u) {
                                    return $u['role'] === 'admin' && $u['is_active'];
                                }));
                                ?>
                            </h3>
                        </div>
                        <div class="bg-purple-500/20 p-3 rounded-lg">
                            <i class="fas fa-crown text-purple-400 text-xl"></i>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-800/50 rounded-lg border border-slate-700/50 p-6 overflow-hidden relative">
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-transparent pointer-events-none"></div>
                    <div class="flex items-center justify-between relative z-10">
                        <div>
                            <p class="text-slate-400 text-sm">Active IT Staff</p>
                            <h3 class="text-2xl font-bold text-white mt-1">
                                <?php 
                                echo count(array_filter($allUsers, function($u) {
                                    return $u['role'] === 'it_staff' && $u['is_active'];
                                }));
                                ?>
                            </h3>
                        </div>
                        <div class="bg-emerald-500/20 p-3 rounded-lg">
                            <i class="fas fa-users text-emerald-400 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-slate-800 border-slate-700/50">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-white mb-4">Edit User Information</h3>
                <form method="POST" id="editForm">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Username</label>
                        <input type="text" name="username" id="edit_username" 
                               class="w-full px-3 py-2 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Full Name</label>
                        <input type="text" name="full_name" id="edit_full_name" 
                               class="w-full px-3 py-2 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                        <input type="email" name="email" id="edit_email" 
                               class="w-full px-3 py-2 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Role</label>
                        <select name="role" id="edit_role" 
                                class="w-full px-3 py-2 border border-slate-600 bg-slate-700/50 text-white rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            <option value="it_staff">IT Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Department</label>
                        <input type="text" name="department" id="edit_department" 
                               class="w-full px-3 py-2 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Phone</label>
                        <input type="text" name="phone" id="edit_phone" 
                               class="w-full px-3 py-2 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeEditModal()" 
                                class="px-4 py-2 bg-slate-700/50 text-slate-300 border border-slate-600 rounded-lg hover:bg-slate-700 hover:text-white transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-lg hover:from-cyan-600 hover:to-blue-700 transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="hidden fixed inset-0 bg-black/60 backdrop-blur-sm overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-slate-800 border-slate-700/50">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-white mb-4">Change Password</h3>
                <p class="text-sm text-slate-400 mb-4">Changing password for: <strong id="password_username" class="text-white"></strong></p>
                <form method="POST" id="passwordForm">
                    <input type="hidden" name="action" value="change_password">
                    <input type="hidden" name="user_id" id="password_user_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300 mb-2">New Password</label>
                        <input type="password" name="new_password" id="new_password" required minlength="6"
                               class="w-full px-3 py-2 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                               placeholder="Enter new password (min 6 characters)">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-300 mb-2">Confirm Password</label>
                        <input type="password" name="confirm_password" id="confirm_password" required minlength="6"
                               class="w-full px-3 py-2 border border-slate-600 bg-slate-700/50 text-white placeholder-slate-400 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                               placeholder="Confirm new password">
                    </div>
                    
                    <div id="password_error" class="hidden mb-4 text-red-400 text-sm"></div>
                    
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closePasswordModal()" 
                                class="px-4 py-2 bg-slate-700/50 text-slate-300 border border-slate-600 rounded-lg hover:bg-slate-700 hover:text-white transition">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
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

        // Open Add User Modal with pre-selected role
        function openAddUserModal(roleType) {
            // For now, show alert (you can replace this with actual modal later)
            if (roleType === 'it_staff') {
                if (confirm('Add new IT Staff member?\n\nThis will open a form to create a new IT Staff account.')) {
                    // You can redirect to a dedicated add user page or open a modal
                    window.location.href = '../admin/add_user.php?role=it_staff';
                }
            } else if (roleType === 'user') {
                if (confirm('Add new User?\n\nThis will open a form to create a new User account.')) {
                    window.location.href = '../admin/add_user.php?role=user';
                }
            }
        }

        // Export user list
        function exportUserList() {
            if (confirm('Export user list to CSV?')) {
                // Create form data
                const users = [];
                document.querySelectorAll('tbody tr').forEach(row => {
                    const cells = row.querySelectorAll('td');
                    if (cells.length > 0) {
                        users.push({
                            name: cells[0]?.textContent.trim(),
                            email: cells[1]?.textContent.trim(),
                            role: cells[2]?.textContent.trim(),
                            status: cells[3]?.textContent.trim()
                        });
                    }
                });
                
                // Create CSV content
                let csv = 'Name,Email,Role,Status\n';
                users.forEach(user => {
                    csv += `"${user.name}","${user.email}","${user.role}","${user.status}"\n`;
                });
                
                // Download CSV
                const blob = new Blob([csv], { type: 'text/csv' });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'users_' + new Date().toISOString().split('T')[0] + '.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            }
        }

        // View audit log
        function viewAuditLog() {
            alert('Audit Log Feature\n\nThis will show:\n• User login history\n• User modifications\n• System changes\n• Security events\n\n(Feature coming soon)');
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
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Quick Actions Dropdown
            const quickActionsBtn = document.getElementById('quickActionsBtn');
            const quickActionsMenu = document.getElementById('quickActionsMenu');
            
            if (quickActionsBtn && quickActionsMenu) {
                quickActionsBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    quickActionsMenu.classList.toggle('hidden');
                    // Close other menus
                    const userMenu = document.getElementById('userMenu');
                    const notificationMenu = document.getElementById('notificationMenu');
                    if (userMenu) userMenu.classList.add('hidden');
                    if (notificationMenu) notificationMenu.classList.add('hidden');
                });
            }

            // Notifications Dropdown
            const notificationBell = document.getElementById('notificationBell');
            const notificationMenu = document.getElementById('notificationMenu');
            
            if (notificationBell && notificationMenu) {
                notificationBell.addEventListener('click', function(e) {
                    e.stopPropagation();
                    notificationMenu.classList.toggle('hidden');
                    // Close other menus
                    const userMenu = document.getElementById('userMenu');
                    if (quickActionsMenu) quickActionsMenu.classList.add('hidden');
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
                    // Close other menus
                    if (quickActionsMenu) quickActionsMenu.classList.add('hidden');
                    if (notificationMenu) notificationMenu.classList.add('hidden');
                });
            }

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                // Check if click is outside quick actions dropdown
                const quickActionsDropdown = document.getElementById('quickActionsDropdown');
                if (quickActionsMenu && quickActionsDropdown && !quickActionsDropdown.contains(e.target)) {
                    quickActionsMenu.classList.add('hidden');
                }
                
                // Check if click is outside user menu dropdown
                const userMenuDropdown = document.getElementById('userMenuDropdown');
                if (userMenu && userMenuDropdown && !userMenuDropdown.contains(e.target)) {
                    userMenu.classList.add('hidden');
                }
                
                // Check if click is outside notification menu
                const notificationBell = document.getElementById('notificationBell');
                if (notificationMenu && notificationBell && !notificationBell.contains(e.target) && !notificationMenu.contains(e.target)) {
                    notificationMenu.classList.add('hidden');
                }
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

<?php include __DIR__ . '/../layouts/footer.php'; ?>

