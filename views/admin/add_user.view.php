<?php 
// Include layout header
$pageTitle = ($selectedRole === 'admin' ? 'Add Administrator' : 'Add IT Staff') . ' - IT Help Desk';
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-white border-b border-gray-200">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-gray-900 flex items-center justify-center text-white">
                    <i class="fas fa-user-shield text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">
                        <?php echo $selectedRole === 'admin' ? 'Add Administrator' : 'Add IT Staff'; ?>
                    </h1>
                    <p class="text-sm text-gray-500 mt-0.5">Create a new system user account</p>
                </div>
            </div>
            <div class="hidden lg:flex items-center space-x-2">
                <button id="darkModeToggle" class="p-2 text-gray-500 hover:text-gray-900 transition" title="Toggle dark mode">
                    <i id="dark-mode-icon" class="fas fa-moon text-sm"></i>
                </button>
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=fff" 
                     alt="User" 
                     class="w-8 h-8 rounded-full"
                     title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
            </div>
        </div>
    </div>

    <!-- Form Content -->
    <div class="p-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-gray-900">
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
                        <span class="ml-1 text-sm font-medium text-gray-700">
                            <?php echo $selectedRole === 'admin' ? 'Add Administrator' : 'Add IT Staff'; ?>
                        </span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 mb-6">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Section -->
            <div class="lg:col-span-2 bg-white border border-gray-200 p-6">
                <div class="mb-6">
                    <div class="flex items-center space-x-2 text-sm text-gray-600 bg-blue-50 border border-blue-200 px-4 py-3">
                        <i class="fas fa-info-circle text-blue-600"></i>
                        <span>
                            <?php if ($selectedRole === 'admin'): ?>
                                Administrators have full system access and can manage all users and settings.
                            <?php else: ?>
                                IT Staff can manage tickets, view reports, and assist employees but cannot modify system settings.
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <form action="add_user.php" method="POST" enctype="multipart/form-data" class="space-y-6" id="addUserForm">
                    
                    <!-- Role Selection (Hidden but can be toggled) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-900 mb-2">
                            User Type <span class="text-red-500">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative flex items-center p-4 border-2 cursor-pointer transition <?php echo $selectedRole === 'it_staff' ? 'border-gray-900 bg-gray-50' : 'border-gray-300 hover:border-gray-400'; ?>">
                                <input 
                                    type="radio" 
                                    name="role" 
                                    value="it_staff" 
                                    <?php echo $selectedRole === 'it_staff' ? 'checked' : ''; ?>
                                    class="sr-only"
                                    onchange="updateRoleDescription()"
                                >
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gray-100 flex items-center justify-center">
                                        <i class="fas fa-headset text-gray-700"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">IT Staff</div>
                                        <div class="text-xs text-gray-500">Support role</div>
                                    </div>
                                </div>
                            </label>

                            <label class="relative flex items-center p-4 border-2 cursor-pointer transition <?php echo $selectedRole === 'admin' ? 'border-gray-900 bg-gray-50' : 'border-gray-300 hover:border-gray-400'; ?>">
                                <input 
                                    type="radio" 
                                    name="role" 
                                    value="admin" 
                                    <?php echo $selectedRole === 'admin' ? 'checked' : ''; ?>
                                    class="sr-only"
                                    onchange="updateRoleDescription()"
                                >
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-gray-100 flex items-center justify-center">
                                        <i class="fas fa-user-shield text-gray-700"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-gray-900">Administrator</div>
                                        <div class="text-xs text-gray-500">Full access</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wide">
                            Personal Information
                        </h3>
                        
                        <!-- Full Name -->
                        <div class="mb-4">
                            <label for="full_name" class="block text-sm font-medium text-gray-900 mb-2">
                                Full Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                placeholder="e.g., John Doe Smith"
                            >
                        </div>

                        <!-- Department and Phone -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="department" class="block text-sm font-medium text-gray-900 mb-2">
                                    Department
                                </label>
                                <input 
                                    type="text" 
                                    id="department" 
                                    name="department"
                                    class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                    placeholder="e.g., IT Department"
                                >
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-900 mb-2">
                                    Phone Number
                                </label>
                                <input 
                                    type="tel" 
                                    id="phone" 
                                    name="phone"
                                    class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                    placeholder="+1 234 567 8900"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Account Credentials -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-sm font-semibold text-gray-900 mb-4 uppercase tracking-wide">
                            Account Credentials
                        </h3>

                        <!-- Username -->
                        <div class="mb-4">
                            <label for="username" class="block text-sm font-medium text-gray-900 mb-2">
                                Username <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                placeholder="john.smith"
                            >
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>Used for logging into the system
                            </p>
                        </div>

                        <!-- Email and Password -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-900 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    required
                                    class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                    placeholder="john.smith@company.com"
                                >
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-900 mb-2">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        required
                                        minlength="8"
                                        class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                        placeholder="Min. 8 characters"
                                    >
                                    <button 
                                        type="button" 
                                        onclick="togglePassword('password')"
                                        class="absolute right-3 top-3 text-gray-400 hover:text-gray-600"
                                    >
                                        <i class="fas fa-eye" id="password-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="dashboard.php" class="px-6 py-3 border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button 
                            type="submit"
                            class="px-6 py-3 bg-gray-900 text-white font-semibold hover:bg-gray-800 transition"
                        >
                            <i class="fas fa-user-plus mr-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Role Description -->
                <div class="bg-white border border-gray-200 p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        <i class="fas fa-info-circle mr-2"></i>Role Permissions
                    </h3>
                    <div id="roleDescription">
                        <?php if ($selectedRole === 'admin'): ?>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-gray-900">Full System Access</strong>
                                    <p class="text-sm text-gray-600">Manage all settings and configurations</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-gray-900">User Management</strong>
                                    <p class="text-sm text-gray-600">Add, edit, and remove users</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-gray-900">Ticket Management</strong>
                                    <p class="text-sm text-gray-600">Full access to all tickets</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-gray-900">Reports & Analytics</strong>
                                    <p class="text-sm text-gray-600">View all system reports</p>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-gray-900">Ticket Management</strong>
                                    <p class="text-sm text-gray-600">View, assign, and resolve tickets</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-gray-900">Employee Support</strong>
                                    <p class="text-sm text-gray-600">Assist employees with issues</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-gray-900">Basic Reports</strong>
                                    <p class="text-sm text-gray-600">View ticket statistics</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-times-circle text-gray-400 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-gray-500">No Admin Access</strong>
                                    <p class="text-sm text-gray-500">Cannot modify system settings</p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Security Notice -->
                <div class="bg-yellow-50 border border-yellow-200 p-6">
                    <h3 class="text-sm font-semibold text-yellow-900 mb-3">
                        <i class="fas fa-shield-alt mr-2"></i>Security Best Practices
                    </h3>
                    <ul class="space-y-2 text-sm text-yellow-800">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                            <span>Use strong passwords (8+ characters)</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                            <span>Unique username for each user</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                            <span>Valid email for notifications</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle mr-2 mt-0.5"></i>
                            <span>Review access levels regularly</span>
                        </li>
                    </ul>
                </div>

                <!-- Quick Stats -->
                <div class="bg-white border border-gray-200 p-6">
                    <h3 class="text-sm font-semibold text-gray-900 mb-3">
                        <i class="fas fa-chart-bar mr-2"></i>Current Users
                    </h3>
                    <?php
                    $userStats = $this->userModel->getStats();
                    ?>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Administrators:</span>
                            <span class="font-semibold text-gray-900"><?php echo $userStats['admins'] ?? 0; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">IT Staff:</span>
                            <span class="font-semibold text-gray-900"><?php echo $userStats['it_staff'] ?? 0; ?></span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-gray-200">
                            <span class="text-gray-600">Total Active:</span>
                            <span class="font-semibold text-gray-900"><?php echo $userStats['active'] ?? 0; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const eye = document.getElementById(fieldId + '-eye');
    
    if (field.type === 'password') {
        field.type = 'text';
        eye.classList.remove('fa-eye');
        eye.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        eye.classList.remove('fa-eye-slash');
        eye.classList.add('fa-eye');
    }
}

// Update role description
function updateRoleDescription() {
    const role = document.querySelector('input[name="role"]:checked').value;
    const description = document.getElementById('roleDescription');
    
    if (role === 'admin') {
        description.innerHTML = `
            <div class="space-y-3">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-gray-900">Full System Access</strong>
                        <p class="text-sm text-gray-600">Manage all settings and configurations</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-gray-900">User Management</strong>
                        <p class="text-sm text-gray-600">Add, edit, and remove users</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-gray-900">Ticket Management</strong>
                        <p class="text-sm text-gray-600">Full access to all tickets</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-gray-900">Reports & Analytics</strong>
                        <p class="text-sm text-gray-600">View all system reports</p>
                    </div>
                </div>
            </div>
        `;
    } else {
        description.innerHTML = `
            <div class="space-y-3">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-gray-900">Ticket Management</strong>
                        <p class="text-sm text-gray-600">View, assign, and resolve tickets</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-gray-900">Employee Support</strong>
                        <p class="text-sm text-gray-600">Assist employees with issues</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-gray-900">Basic Reports</strong>
                        <p class="text-sm text-gray-600">View ticket statistics</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-times-circle text-gray-400 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-gray-500">No Admin Access</strong>
                        <p class="text-sm text-gray-500">Cannot modify system settings</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Update radio button styling
    document.querySelectorAll('input[name="role"]').forEach(input => {
        const label = input.closest('label');
        if (input.checked) {
            label.classList.add('border-gray-900', 'bg-gray-50');
            label.classList.remove('border-gray-300');
        } else {
            label.classList.remove('border-gray-900', 'bg-gray-50');
            label.classList.add('border-gray-300');
        }
    });
}

// Form validation
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const fullName = document.getElementById('full_name').value.trim();
    
    // Check required fields
    if (!username || !email || !password || !fullName) {
        e.preventDefault();
        alert('Please fill in all required fields marked with *');
        return false;
    }
    
    // Password length validation
    if (password.length < 8) {
        e.preventDefault();
        alert('Password must be at least 8 characters long');
        return false;
    }
    
    // Email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        e.preventDefault();
        alert('Please enter a valid email address');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating User...';
    submitBtn.disabled = true;
});
</script>

<?php 
// Include layout footer
include __DIR__ . '/../layouts/footer.php'; 
?>
