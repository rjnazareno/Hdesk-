<?php 
// Include layout header
$pageTitle = 'Add Employee - IT Help Desk';
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
                    <i class="fas fa-user-plus text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">Add New Employee</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Register a new employee to the system</p>
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
                        <a href="customers.php" class="ml-1 text-sm font-medium text-gray-600 hover:text-gray-900">Employees</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-700">Add Employee</span>
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

        <div class="bg-white border border-gray-200 p-6 max-w-5xl">
            <form action="add_employee.php" method="POST" enctype="multipart/form-data" class="space-y-6" id="addEmployeeForm">
                
                <!-- Personal Information Section -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">
                        <i class="fas fa-user mr-2"></i>Personal Information
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- First Name -->
                        <div>
                            <label for="fname" class="block text-sm font-medium text-gray-900 mb-2">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="fname" 
                                name="fname" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                placeholder="John"
                            >
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="lname" class="block text-sm font-medium text-gray-900 mb-2">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="lname" 
                                name="lname" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                placeholder="Doe"
                            >
                        </div>

                        <!-- Position -->
                        <div>
                            <label for="position" class="block text-sm font-medium text-gray-900 mb-2">
                                Position <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="position" 
                                name="position" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                placeholder="e.g., Software Developer, Accountant"
                            >
                        </div>

                        <!-- Company/Department -->
                        <div>
                            <label for="company" class="block text-sm font-medium text-gray-900 mb-2">
                                Company/Department
                            </label>
                            <input 
                                type="text" 
                                id="company" 
                                name="company"
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                placeholder="e.g., IT Department"
                            >
                        </div>

                        <!-- Contact Number -->
                        <div>
                            <label for="contact" class="block text-sm font-medium text-gray-900 mb-2">
                                Contact Number
                            </label>
                            <input 
                                type="tel" 
                                id="contact" 
                                name="contact"
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                placeholder="+1 234 567 8900"
                            >
                        </div>

                        <!-- Official Schedule -->
                        <div>
                            <label for="official_sched" class="block text-sm font-medium text-gray-900 mb-2">
                                Official Schedule
                            </label>
                            <input 
                                type="text" 
                                id="official_sched" 
                                name="official_sched"
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                placeholder="e.g., Mon-Fri 9:00 AM - 5:00 PM"
                            >
                        </div>
                    </div>
                </div>

                <!-- Account Information Section -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-200">
                        <i class="fas fa-user-lock mr-2"></i>Account Information
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Username -->
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-900 mb-2">
                                Username <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="username" 
                                name="username" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                placeholder="john.doe"
                            >
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>Used for logging into the system
                            </p>
                        </div>

                        <!-- Password -->
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
                                    minlength="6"
                                    class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                    placeholder="Min. 6 characters"
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

                        <!-- Official Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-900 mb-2">
                                Official Email <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                placeholder="john.doe@company.com"
                            >
                        </div>

                        <!-- Personal Email -->
                        <div>
                            <label for="personal_email" class="block text-sm font-medium text-gray-900 mb-2">
                                Personal Email
                            </label>
                            <input 
                                type="email" 
                                id="personal_email" 
                                name="personal_email"
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                                placeholder="john.doe@gmail.com"
                            >
                        </div>

                        <!-- Role -->
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-900 mb-2">
                                Role <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="role" 
                                name="role" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                            >
                                <option value="employee" selected>Employee</option>
                                <option value="it_staff">IT Staff</option>
                                <option value="admin">Admin</option>
                            </select>
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="fas fa-info-circle mr-1"></i>Determines access level
                            </p>
                        </div>

                        <!-- Profile Picture -->
                        <div>
                            <label for="profile_picture" class="block text-sm font-medium text-gray-900 mb-2">
                                Profile Picture
                            </label>
                            <input 
                                type="file" 
                                id="profile_picture" 
                                name="profile_picture"
                                accept="image/*"
                                class="w-full px-4 py-3 border border-gray-300 focus:outline-none focus:border-gray-400"
                            >
                            <p class="text-sm text-gray-500 mt-1">
                                <i class="fas fa-image mr-1"></i>JPG, PNG, GIF (Max 2MB)
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Buttons -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="customers.php" class="px-6 py-3 border border-gray-300 text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </a>
                    <button 
                        type="submit"
                        class="px-6 py-3 bg-gray-900 text-white font-semibold hover:bg-gray-800 transition"
                    >
                        <i class="fas fa-user-plus mr-2"></i>Add Employee
                    </button>
                </div>
            </form>
        </div>

        <!-- Help Section -->
        <div class="mt-6 bg-gray-50 border border-gray-200 p-6 max-w-5xl">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">
                <i class="fas fa-info-circle mr-2"></i>Important Notes
            </h3>
            <ul class="space-y-2 text-sm text-gray-600">
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-gray-900 mr-2 mt-0.5"></i>
                    <span><strong>Username</strong> must be unique and will be used for login</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-gray-900 mr-2 mt-0.5"></i>
                    <span><strong>Password</strong> should be at least 6 characters long and secure</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-gray-900 mr-2 mt-0.5"></i>
                    <span><strong>Role</strong> determines what the employee can access (Employee = submit tickets only)</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-gray-900 mr-2 mt-0.5"></i>
                    <span><strong>Official Email</strong> is used for system notifications and password resets</span>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-gray-900 mr-2 mt-0.5"></i>
                    <span>The employee will be notified via email with their login credentials</span>
                </li>
            </ul>
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

// Form validation
document.getElementById('addEmployeeForm').addEventListener('submit', function(e) {
    const username = document.getElementById('username').value;
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    const fname = document.getElementById('fname').value;
    const lname = document.getElementById('lname').value;
    const position = document.getElementById('position').value;
    
    // Check required fields
    if (!username || !email || !password || !fname || !lname || !position) {
        e.preventDefault();
        alert('Please fill in all required fields marked with *');
        return false;
    }
    
    // Password length validation
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long');
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
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Adding Employee...';
    submitBtn.disabled = true;
});

// File upload validation
document.getElementById('profile_picture').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        // Check file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
            alert('File size must be less than 2MB');
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

// Auto-generate username from name
document.getElementById('fname').addEventListener('blur', generateUsername);
document.getElementById('lname').addEventListener('blur', generateUsername);

function generateUsername() {
    const fname = document.getElementById('fname').value.trim().toLowerCase();
    const lname = document.getElementById('lname').value.trim().toLowerCase();
    const usernameField = document.getElementById('username');
    
    if (fname && lname && !usernameField.value) {
        usernameField.value = fname + '.' + lname;
    }
}
</script>

<?php 
// Include layout footer
include __DIR__ . '/../layouts/footer.php'; 
?>
