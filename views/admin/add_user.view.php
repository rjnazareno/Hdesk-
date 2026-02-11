<?php 
// Include layout header
$pageTitle = ($selectedRole === 'admin' ? 'Add Administrator' : 'Add IT Staff') . ' - ' . (defined('APP_NAME') ? APP_NAME : 'ServiceHub');
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-slate-50">
    <?php
    // Set header variables for this page
    $headerTitle = $selectedRole === 'admin' ? 'Add Administrator' : 'Add IT Staff';
    $headerSubtitle = 'Create a new system user account';
    $showQuickActions = false;
    $showSearch = false;
    
    include __DIR__ . '/../../includes/top_header.php';
    ?>

    <!-- Quick Instructions Banner -->
    <div class="bg-blue-50 border-b border-blue-200">
        <div class="px-4 lg:px-8 py-3 flex items-start space-x-3">
            <i class="fas fa-lightbulb text-blue-600 mt-1 flex-shrink-0"></i>
            <div class="text-sm text-gray-700">
                <strong>Quick Tip:</strong> Fields marked with <span class="text-red-600">*</span> are required. Username and email must be unique. Choose the appropriate role based on system access needs.
            </div>
        </div>
    </div>

    <!-- Form Content -->
    <div class="p-4 lg:p-8">
        <!-- Breadcrumb -->
        <nav class="flex mb-4" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="dashboard.php" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-emerald-600">
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
                        <span class="ml-1 text-sm font-medium text-gray-700">
                            <?php echo $selectedRole === 'admin' ? 'Add Administrator' : 'Add IT Staff'; ?>
                        </span>
                    </div>
                </li>
            </ol>
        </nav>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="bg-red-900/20 border border-red-600/50 text-red-400 px-4 py-3 mb-6 rounded-lg flex items-start space-x-3">
            <i class="fas fa-exclamation-circle mt-0.5 flex-shrink-0"></i>
            <div>
                <strong>Error:</strong> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="bg-emerald-900/20 border border-emerald-600/50 text-emerald-400 px-4 py-3 mb-6 rounded-lg flex items-start space-x-3">
            <i class="fas fa-check-circle mt-0.5 flex-shrink-0"></i>
            <div>
                <strong>Success!</strong> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Section -->
            <div class="lg:col-span-2 bg-white  border border-slate-200 p-6">
                <div class="mb-6">
                    <div class="flex items-center space-x-2 text-sm text-slate-500 bg-blue-50 border border-blue-200 px-4 py-3">
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
                        <label class="block text-sm font-medium text-slate-800 mb-2">
                            User Type <span class="text-red-400">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative flex items-center p-4 border-2 cursor-pointer transition <?php echo $selectedRole === 'it_staff' ? 'border-gray-900 bg-white' : 'border-gray-300 hover:border-gray-300'; ?>">
                                <input 
                                    type="radio" 
                                    name="role" 
                                    value="it_staff" 
                                    <?php echo $selectedRole === 'it_staff' ? 'checked' : ''; ?>
                                    class="sr-only"
                                    onchange="updateRoleDescription()"
                                >
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-slate-50 flex items-center justify-center">
                                        <i class="fas fa-headset text-gray-700"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-800">IT Staff</div>
                                        <div class="text-xs text-slate-500">Support role</div>
                                    </div>
                                </div>
                            </label>

                            <label class="relative flex items-center p-4 border-2 cursor-pointer transition <?php echo $selectedRole === 'admin' ? 'border-gray-900 bg-white' : 'border-gray-300 hover:border-gray-300'; ?>">
                                <input 
                                    type="radio" 
                                    name="role" 
                                    value="admin" 
                                    <?php echo $selectedRole === 'admin' ? 'checked' : ''; ?>
                                    class="sr-only"
                                    onchange="updateRoleDescription()"
                                >
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-slate-50 flex items-center justify-center">
                                        <i class="fas fa-user-shield text-gray-700"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-slate-800">Administrator</div>
                                        <div class="text-xs text-slate-500">Full access</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="border-t border-slate-200 pt-6">
                        <h3 class="text-sm font-semibold text-slate-800 mb-4 uppercase tracking-wide">
                            Personal Information
                        </h3>
                        
                        <!-- Full Name -->
                        <div class="mb-4">
                            <label for="full_name" class="block text-sm font-medium text-slate-800 mb-2">
                                Full Name <span class="text-red-400">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="full_name" 
                                name="full_name" 
                                required
                                class="w-full px-4 py-3 bg-slate-50 border border-gray-300 text-slate-800 placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-emerald-500 hover:border-slate-500"
                                placeholder="e.g., John Doe Smith"
                            >
                        </div>

                        <!-- Department and Phone -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="department" class="block text-sm font-medium text-slate-800 mb-2">
                                    Department
                                </label>
                                <input 
                                    type="text" 
                                    id="department" 
                                    name="department"
                                    class="w-full px-4 py-3 bg-slate-50 border border-gray-300 text-slate-800 placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-emerald-500 hover:border-slate-500"
                                    placeholder="e.g., IT Department"
                                >
                            </div>

                            <div>
                                <label for="phone" class="block text-sm font-medium text-slate-800 mb-2">
                                    Phone Number
                                </label>
                                <input 
                                    type="tel" 
                                    id="phone" 
                                    name="phone"
                                    class="w-full px-4 py-3 bg-slate-50 border border-gray-300 text-slate-800 placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-emerald-500 hover:border-slate-500"
                                    placeholder="+1 234 567 8900"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Account Credentials -->
                    <div class="border-t border-slate-200 pt-6">
                        <h3 class="text-sm font-semibold text-slate-800 mb-4 uppercase tracking-wide">
                            Account Credentials
                        </h3>

                        <!-- Username -->
                        <div class="mb-4">
                            <label for="username" class="block text-sm font-medium text-slate-800 mb-2">
                                Username <span class="text-red-400">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                required
                                class="w-full px-4 py-3 bg-slate-50 border border-gray-300 text-slate-800 placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-emerald-500 hover:border-slate-500"
                                placeholder="john.smith"
                            >
                            <p class="text-sm text-slate-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i><strong>Must be unique</strong> - Used for logging into the system
                            </p>
                        </div>

                        <!-- Email and Password -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="block text-sm font-medium text-slate-800 mb-2">
                                    Email Address <span class="text-red-400">*</span>
                                </label>
                                <input 
                                    type="email" 
                                    id="email" 
                                    name="email" 
                                    required
                                    class="w-full px-4 py-3 bg-slate-50 border border-gray-300 text-slate-800 placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-emerald-500 hover:border-slate-500"
                                    placeholder="john.smith@company.com"
                                >
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-slate-800 mb-2">
                                    Password <span class="text-red-400">*</span>
                                </label>
                                <div class="relative">
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        required
                                        minlength="8"
                                        class="w-full px-4 py-3 bg-slate-50 border border-gray-300 text-slate-800 placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-emerald-500 hover:border-slate-500"
                                        placeholder="Min. 8 characters"
                                    >
                                    <button 
                                        type="button" 
                                        onclick="togglePassword('password')"
                                        class="absolute right-3 top-3 text-slate-500 hover:text-slate-500"
                                    >
                                        <i class="fas fa-eye" id="password-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-slate-200">
                        <a href="dashboard.php" class="px-6 py-3 border border-gray-300 text-gray-700 hover:bg-white transition">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <button 
                            type="submit"
                            class="px-6 py-3 bg-gradient-to-r from-emerald-400 to-emerald-600 text-slate-800 font-semibold hover:from-teal-700 hover:to-emerald-700 transition rounded-lg"
                        >
                            <i class="fas fa-user-plus mr-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Role Description -->
                <div class="bg-white  border border-slate-200 p-6">
                    <h3 class="text-lg font-semibold text-slate-800 mb-4">
                        <i class="fas fa-info-circle mr-2"></i>Role Permissions
                    </h3>
                    <div id="roleDescription">
                        <?php if ($selectedRole === 'admin'): ?>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-slate-800">Full System Access</strong>
                                    <p class="text-sm text-slate-500">Manage all settings and configurations</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-slate-800">User Management</strong>
                                    <p class="text-sm text-slate-500">Add, edit, and remove users</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-slate-800">Ticket Management</strong>
                                    <p class="text-sm text-slate-500">Full access to all tickets</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-slate-800">Reports & Analytics</strong>
                                    <p class="text-sm text-slate-500">View all system reports</p>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="space-y-3">
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-slate-800">Ticket Management</strong>
                                    <p class="text-sm text-slate-500">View, assign, and resolve tickets</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-slate-800">Employee Support</strong>
                                    <p class="text-sm text-slate-500">Assist employees with issues</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-slate-800">Basic Reports</strong>
                                    <p class="text-sm text-slate-500">View ticket statistics</p>
                                </div>
                            </div>
                            <div class="flex items-start">
                                <i class="fas fa-times-circle text-slate-500 mr-2 mt-1"></i>
                                <div>
                                    <strong class="text-slate-500">No Admin Access</strong>
                                    <p class="text-sm text-slate-500">Cannot modify system settings</p>
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
                <div class="bg-white  border border-slate-200 p-6">
                    <h3 class="text-sm font-semibold text-slate-800 mb-3">
                        <i class="fas fa-chart-bar mr-2"></i>Current Users
                    </h3>
                    <?php
                    $userStats = $this->userModel->getStats();
                    ?>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Administrators:</span>
                            <span class="font-semibold text-slate-800"><?php echo $userStats['admins'] ?? 0; ?></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">IT Staff:</span>
                            <span class="font-semibold text-slate-800"><?php echo $userStats['it_staff'] ?? 0; ?></span>
                        </div>
                        <div class="flex justify-between pt-2 border-t border-slate-200">
                            <span class="text-slate-500">Total Active:</span>
                            <span class="font-semibold text-slate-800"><?php echo $userStats['active'] ?? 0; ?></span>
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
                        <strong class="text-slate-800">Full System Access</strong>
                        <p class="text-sm text-slate-500">Manage all settings and configurations</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-slate-800">User Management</strong>
                        <p class="text-sm text-slate-500">Add, edit, and remove users</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-slate-800">Ticket Management</strong>
                        <p class="text-sm text-slate-500">Full access to all tickets</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-slate-800">Reports & Analytics</strong>
                        <p class="text-sm text-slate-500">View all system reports</p>
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
                        <strong class="text-slate-800">Ticket Management</strong>
                        <p class="text-sm text-slate-500">View, assign, and resolve tickets</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-slate-800">Employee Support</strong>
                        <p class="text-sm text-slate-500">Assist employees with issues</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-blue-600 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-slate-800">Basic Reports</strong>
                        <p class="text-sm text-slate-500">View ticket statistics</p>
                    </div>
                </div>
                <div class="flex items-start">
                    <i class="fas fa-times-circle text-slate-500 mr-2 mt-1"></i>
                    <div>
                        <strong class="text-slate-500">No Admin Access</strong>
                        <p class="text-sm text-slate-500">Cannot modify system settings</p>
                    </div>
                </div>
            </div>
        `;
    }
    
    // Update radio button styling
    document.querySelectorAll('input[name="role"]').forEach(input => {
        const label = input.closest('label');
        if (input.checked) {
            label.classList.add('border-gray-900', 'bg-white');
            label.classList.remove('border-gray-300');
        } else {
            label.classList.remove('border-gray-900', 'bg-white');
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


