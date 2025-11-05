<?php 
// Include layout header
$pageTitle = 'Add Employee - IT Help Desk';
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Top Bar -->
    <div class="bg-slate-800/50 border-b border-slate-700/50 backdrop-blur-md">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white rounded-lg">
                    <i class="fas fa-user-plus text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-white">Add New Employee</h1>
                    <p class="text-sm text-slate-400 mt-0.5">Register a new employee to the system</p>
                </div>
            </div>
            <div class="hidden lg:flex items-center space-x-2">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=06b6d4" 
                     alt="User" 
                     class="w-8 h-8 rounded-full"
                     title="<?php echo htmlspecialchars($currentUser['full_name']); ?>">
            </div>
        </div>
    </div>

    <!-- Quick Instructions Banner -->
    <div class="bg-gradient-to-r from-cyan-600/20 to-blue-600/20 border-b border-cyan-600/30 backdrop-blur-sm">
        <div class="px-4 lg:px-8 py-3 flex items-start space-x-3">
            <i class="fas fa-lightbulb text-cyan-400 mt-1 flex-shrink-0"></i>
            <div class="text-sm text-slate-200">
                <strong>Quick Tip:</strong> Fields marked with <span class="text-red-400">*</span> are required. Username and email must be unique. The employee will receive login credentials via email.
            </div>
        </div>
    </div>

    <!-- Form Content -->
    <div class="p-4 lg:p-8">
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
                        <a href="customers.php" class="ml-1 text-sm font-medium text-slate-400 hover:text-cyan-400">Employees</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-slate-300">Add Employee</span>
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
            <!-- Main Form (2 columns) -->
            <div class="lg:col-span-2">
                <div class="bg-slate-800/50 border border-slate-700/50 backdrop-blur-md p-6 space-y-6">
                    <form action="add_employee.php" method="POST" enctype="multipart/form-data" class="space-y-6" id="addEmployeeForm">
                
                <!-- Personal Information Section -->
                <div>
                    <h2 class="text-lg font-semibold text-white mb-4 pb-2 border-b border-slate-700/50">
                        <i class="fas fa-user mr-2"></i>Personal Information
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- First Name -->
                        <div>
                            <label for="fname" class="block text-sm font-medium text-white mb-2">
                                First Name <span class="text-red-400">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="fname" 
                                name="fname" 
                                required
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                                placeholder="John"
                            >
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="lname" class="block text-sm font-medium text-white mb-2">
                                Last Name <span class="text-red-400">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="lname" 
                                name="lname" 
                                required
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                                placeholder="Doe"
                            >
                        </div>

                        <!-- Position -->
                        <div>
                            <label for="position" class="block text-sm font-medium text-white mb-2">
                                Position <span class="text-red-400">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="position" 
                                name="position" 
                                required
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                                placeholder="e.g., Software Developer, Accountant"
                            >
                        </div>

                        <!-- Company/Department -->
                        <div>
                            <label for="company" class="block text-sm font-medium text-white mb-2">
                                Company/Department
                            </label>
                            <input 
                                type="text" 
                                id="company" 
                                name="company"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                                placeholder="e.g., IT Department"
                            >
                        </div>

                        <!-- Contact Number -->
                        <div>
                            <label for="contact" class="block text-sm font-medium text-white mb-2">
                                Contact Number
                            </label>
                            <input 
                                type="tel" 
                                id="contact" 
                                name="contact"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                                placeholder="+1 234 567 8900"
                            >
                        </div>

                        <!-- Official Schedule -->
                        <div>
                            <label for="official_sched" class="block text-sm font-medium text-white mb-2">
                                Official Schedule
                            </label>
                            <input 
                                type="text" 
                                id="official_sched" 
                                name="official_sched"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                                placeholder="e.g., Mon-Fri 9:00 AM - 5:00 PM"
                            >
                        </div>
                    </div>
                </div>

                <!-- Account Information Section -->
                <div>
                    <h2 class="text-lg font-semibold text-white mb-4 pb-2 border-b border-slate-700/50">
                        <i class="fas fa-user-lock mr-2"></i>Account Information
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Username -->
                        <div>
                            <label for="username" class="block text-sm font-medium text-white mb-2">
                                Username <span class="text-red-400">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                required
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                                placeholder="john.doe"
                            >
                            <p class="text-sm text-slate-400 mt-1">
                                <i class="fas fa-info-circle mr-1"></i><strong>Must be unique</strong> - Used for logging into the system
                            </p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-medium text-white mb-2">
                                Password <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <input 
                                    type="password" 
                                    id="password" 
                                    name="password" 
                                    required
                                    minlength="6"
                                    class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                                    placeholder="Min. 6 characters"
                                >
                                <button 
                                    type="button" 
                                    onclick="togglePassword('password')"
                                    class="absolute right-3 top-3 text-slate-400 hover:text-cyan-400"
                                >
                                    <i class="fas fa-eye" id="password-eye"></i>
                                </button>
                            </div>
                            <p class="text-sm text-slate-400 mt-1">
                                <i class="fas fa-lock mr-1"></i>Strong password recommended
                            </p>
                        </div>

                        <!-- Official Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-white mb-2">
                                Official Email <span class="text-red-400">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                                placeholder="john.doe@company.com"
                            >
                            <p class="text-sm text-slate-400 mt-1">
                                <i class="fas fa-envelope mr-1"></i>Used for notifications and password resets
                            </p>
                        </div>

                        <!-- Personal Email -->
                        <div>
                            <label for="personal_email" class="block text-sm font-medium text-white mb-2">
                                Personal Email
                            </label>
                            <input 
                                type="email" 
                                id="personal_email" 
                                name="personal_email"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                                placeholder="john.doe@gmail.com"
                            >
                        </div>

                        <!-- Role -->
                        <div>
                            <label for="role" class="block text-sm font-medium text-white mb-2">
                                Role <span class="text-red-400">*</span>
                            </label>
                            <select 
                                id="role" 
                                name="role" 
                                required
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                            >
                                <option value="employee" selected>Employee</option>
                                <option value="it_staff">IT Staff</option>
                                <option value="admin">Administrator</option>
                            </select>
                            <p class="text-sm text-slate-400 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>Choose based on system access needs
                            </p>
                        </div>

                        <!-- Profile Picture -->
                        <div>
                            <label for="profile_picture" class="block text-sm font-medium text-white mb-2">
                                Profile Picture
                            </label>
                            <input 
                                type="file" 
                                id="profile_picture" 
                                name="profile_picture"
                                accept="image/*"
                                class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg transition-all focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500"
                            >
                            <p class="text-sm text-slate-400 mt-1">
                                <i class="fas fa-image mr-1"></i>JPG, PNG, GIF (Max 2MB)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-slate-700/50">
                    <a href="customers.php" class="px-6 py-3 border border-slate-600 text-slate-300 hover:bg-slate-900/50 transition rounded-lg">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button 
                        type="submit"
                        class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold hover:from-cyan-600 hover:to-blue-700 transition rounded-lg"
                    >
                        <i class="fas fa-user-plus mr-2"></i>Add Employee
                    </button>
                </div>
                    </form>
                </div>
            </div>

            <!-- Sidebar Instructions (1 column) -->
            <div class="lg:col-span-1 space-y-4">
                <!-- Role Guide -->
                <div class="bg-slate-800/50 border border-slate-700/50 backdrop-blur-md p-4 rounded-lg">
                    <h3 class="text-sm font-semibold text-white mb-3 flex items-center">
                        <i class="fas fa-shield-alt text-cyan-400 mr-2"></i>Role Guide
                    </h3>
                    <div class="space-y-3 text-xs text-slate-300">
                        <div class="border-l-2 border-cyan-500 pl-2">
                            <strong class="text-white">Employee</strong>
                            <p>Can submit tickets only, cannot access admin</p>
                        </div>
                        <div class="border-l-2 border-blue-500 pl-2">
                            <strong class="text-white">IT Staff</strong>
                            <p>Manage tickets, view reports, assist employees</p>
                        </div>
                        <div class="border-l-2 border-purple-500 pl-2">
                            <strong class="text-white">Administrator</strong>
                            <p>Full system access, manage all users & settings</p>
                        </div>
                    </div>
                </div>

                <!-- Best Practices -->
                <div class="bg-slate-800/50 border border-slate-700/50 backdrop-blur-md p-4 rounded-lg">
                    <h3 class="text-sm font-semibold text-white mb-3 flex items-center">
                        <i class="fas fa-check-circle text-green-400 mr-2"></i>Best Practices
                    </h3>
                    <ul class="space-y-2 text-xs text-slate-300">
                        <li class="flex items-start">
                            <i class="fas fa-arrow-right text-cyan-400 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Use first.last format for usernames</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-arrow-right text-cyan-400 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Use official company email</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-arrow-right text-cyan-400 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Strong password recommended</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-arrow-right text-cyan-400 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Complete all required fields</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-arrow-right text-cyan-400 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Upload professional profile picture</span>
                        </li>
                    </ul>
                </div>

                <!-- Important Notes -->
                <div class="bg-amber-500/10 border border-amber-600/30 backdrop-blur-sm p-4 rounded-lg">
                    <h3 class="text-sm font-semibold text-amber-300 mb-3 flex items-center">
                        <i class="fas fa-exclamation-triangle text-amber-400 mr-2"></i>Important
                    </h3>
                    <ul class="space-y-2 text-xs text-white">
                        <li class="flex items-start">
                            <i class="fas fa-check text-amber-400 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Login credentials will be emailed to the employee</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-amber-400 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Email addresses must be unique</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check text-amber-400 mr-2 mt-0.5 flex-shrink-0"></i>
                            <span>Changes can be edited later in the system</span>
                        </li>
                    </ul>
                </div>

                <!-- Quick Tips -->
                <div class="bg-gradient-to-br from-cyan-600/20 to-blue-600/20 border border-cyan-600/30 backdrop-blur-sm p-4 rounded-lg">
                    <h3 class="text-sm font-semibold text-cyan-300 mb-3 flex items-center">
                        <i class="fas fa-lightbulb mr-2"></i>Quick Tips
                    </h3>
                    <ul class="space-y-2 text-xs text-white">
                        <li>• Auto-generates username from first & last names</li>
                        <li>• Tab or click fields to see helpful hints</li>
                        <li>• Required fields show a red asterisk</li>
                        <li>• Click eye icon to see password</li>
                    </ul>
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

// Auto-generate username from name
function generateUsername() {
    const fname = document.getElementById('fname').value.trim().toLowerCase();
    const lname = document.getElementById('lname').value.trim().toLowerCase();
    const usernameField = document.getElementById('username');
    
    if (fname && lname && !usernameField.value) {
        usernameField.value = fname + '.' + lname;
    }
}

// Add blur event listeners for username generation
document.getElementById('fname').addEventListener('blur', generateUsername);
document.getElementById('lname').addEventListener('blur', generateUsername);

// Form validation with better error messages
document.getElementById('addEmployeeForm').addEventListener('submit', function(e) {
    const errors = [];
    
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const fname = document.getElementById('fname').value.trim();
    const lname = document.getElementById('lname').value.trim();
    const position = document.getElementById('position').value.trim();
    
    // Validate required fields
    if (!fname) errors.push('First name is required');
    if (!lname) errors.push('Last name is required');
    if (!username) errors.push('Username is required');
    if (!email) errors.push('Official email is required');
    if (!password) errors.push('Password is required');
    if (!position) errors.push('Position is required');
    
    // Validate password length
    if (password && password.length < 6) {
        errors.push('Password must be at least 6 characters long');
    }
    
    // Validate email format
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email && !emailRegex.test(email)) {
        errors.push('Please enter a valid email address');
    }
    
    // Validate username format
    if (username && !/^[a-zA-Z0-9._-]+$/.test(username)) {
        errors.push('Username can only contain letters, numbers, dots, hyphens, and underscores');
    }
    
    if (errors.length > 0) {
        e.preventDefault();
        alert('Please fix the following errors:\n\n• ' + errors.join('\n• '));
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding Employee...';
    submitBtn.disabled = true;
});

// File upload validation
document.getElementById('profile_picture').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Check file size (2MB max)
        const maxSize = 2 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('File size must be less than 2MB. Your file is ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
            this.value = '';
            return;
        }
        
        // Check file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Please upload only image files (JPG, PNG, GIF)');
            this.value = '';
            return;
        }
    }
});

// Show helpful tooltip on field focus
document.querySelectorAll('input[type="email"], input[type="password"], input[type="text"], select').forEach(field => {
    field.addEventListener('focus', function() {
        const parent = this.closest('div');
        const hint = parent.querySelector('.text-slate-400');
        if (hint) {
            hint.style.opacity = '1';
        }
    });
});
</script>

<?php 
// Include layout footer
include __DIR__ . '/../layouts/footer.php'; 
?>

