<?php 
// Set page-specific variables
$pageTitle = 'Edit Employee - ' . APP_NAME;
$baseUrl = '../';

// Include header layout
include __DIR__ . '/../layouts/header.php'; 
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-slate-50">
    <!-- Top Bar -->
    <div class="bg-white border-b border-slate-200 ">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <!-- Left Section: Title -->
            <div class="flex items-center space-x-4">
                <a href="customers.php" class="lg:hidden text-slate-500 hover:text-slate-800">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div class="hidden lg:flex items-center justify-center w-10 h-10 bg-gradient-to-br from-emerald-400 to-emerald-600 text-slate-800 rounded-lg">
                    <i class="fas fa-user-edit text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-slate-800">
                        Edit Employee
                    </h1>
                    <p class="text-sm text-slate-500">Update employee information</p>
                </div>
            </div>

            <!-- Right Section: Actions -->
            <div class="flex items-center space-x-3">
                <a href="customers.php" class="hidden lg:flex items-center px-4 py-2 border border-gray-300 bg-slate-50 text-gray-700 hover:bg-slate-100 hover:text-slate-800 transition rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Employees
                </a>
            </div>
        </div>
    </div>

    <!-- Form Content -->
    <div class="p-4 lg:p-8">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 text-red-300 rounded-lg flex items-start">
            <i class="fas fa-exclamation-circle mt-0.5 mr-3"></i>
            <div>
                <?php 
                echo $_SESSION['error']; 
                unset($_SESSION['error']);
                ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 text-green-300 rounded-lg flex items-start">
            <i class="fas fa-check-circle mt-0.5 mr-3"></i>
            <div>
                <?php 
                echo $_SESSION['success']; 
                unset($_SESSION['success']);
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <div class="max-w-4xl">
            <form method="POST" action="" class="space-y-6">
                <!-- Employee Info Card -->
                <div class="bg-white  border border-slate-200 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-lg font-semibold text-slate-800 flex items-center">
                            <i class="fas fa-id-card mr-2 text-cyan-500"></i>
                            Personal Information
                        </h2>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- First Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                First Name <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   name="fname" 
                                   value="<?php echo htmlspecialchars($employee['fname'] ?? ''); ?>"
                                   required
                                   class="w-full px-4 py-2 bg-slate-50 border border-gray-300 text-slate-800 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Last Name <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   name="lname" 
                                   value="<?php echo htmlspecialchars($employee['lname'] ?? ''); ?>"
                                   required
                                   class="w-full px-4 py-2 bg-slate-50 border border-gray-300 text-slate-800 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        </div>

                        <!-- Username -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Username <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   name="username" 
                                   value="<?php echo htmlspecialchars($employee['username'] ?? ''); ?>"
                                   required
                                   class="w-full px-4 py-2 bg-slate-50 border border-gray-300 text-slate-800 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Company Email <span class="text-red-400">*</span>
                            </label>
                            <input type="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($employee['email'] ?? ''); ?>"
                                   required
                                   class="w-full px-4 py-2 bg-slate-50 border border-gray-300 text-slate-800 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        </div>

                        <!-- Personal Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Personal Email
                            </label>
                            <input type="email" 
                                   name="personal_email" 
                                   value="<?php echo htmlspecialchars($employee['personal_email'] ?? ''); ?>"
                                   class="w-full px-4 py-2 bg-slate-50 border border-gray-300 text-slate-800 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        </div>

                        <!-- Contact -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Contact Number
                            </label>
                            <input type="text" 
                                   name="contact" 
                                   value="<?php echo htmlspecialchars($employee['contact'] ?? ''); ?>"
                                   class="w-full px-4 py-2 bg-slate-50 border border-gray-300 text-slate-800 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        </div>
                    </div>
                </div>

                <!-- Work Info Card -->
                <div class="bg-white  border border-slate-200 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-lg font-semibold text-slate-800 flex items-center">
                            <i class="fas fa-briefcase mr-2 text-cyan-500"></i>
                            Work Information
                        </h2>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Company -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Company
                            </label>
                            <input type="text" 
                                   name="company" 
                                   value="<?php echo htmlspecialchars($employee['company'] ?? ''); ?>"
                                   class="w-full px-4 py-2 bg-slate-50 border border-gray-300 text-slate-800 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        </div>

                        <!-- Position -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Position
                            </label>
                            <input type="text" 
                                   name="position" 
                                   value="<?php echo htmlspecialchars($employee['position'] ?? ''); ?>"
                                   class="w-full px-4 py-2 bg-slate-50 border border-gray-300 text-slate-800 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        </div>

                        <!-- Status -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Status <span class="text-red-400">*</span>
                            </label>
                            <select name="status" 
                                    required
                                    class="w-full px-4 py-2 bg-slate-50 border border-gray-300 text-slate-800 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                                <option value="active" <?php echo ($employee['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($employee['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="terminated" <?php echo ($employee['status'] ?? '') === 'terminated' ? 'selected' : ''; ?>>Terminated</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Security Card -->
                <div class="bg-white  border border-slate-200 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200">
                        <h2 class="text-lg font-semibold text-slate-800 flex items-center">
                            <i class="fas fa-lock mr-2 text-cyan-500"></i>
                            Security
                        </h2>
                    </div>
                    <div class="p-6">
                        <!-- Password -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                New Password
                            </label>
                            <input type="password" 
                                   name="password" 
                                   placeholder="Leave blank to keep current password"
                                   autocomplete="new-password"
                                   onfocus="this.removeAttribute('readonly');"
                                   readonly
                                   class="w-full px-4 py-2 bg-slate-50 border border-gray-300 text-slate-800 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            <p class="mt-2 text-xs text-slate-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Only fill this if you want to change the password
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-6 border-t border-slate-200">
                    <a href="customers.php" class="px-6 py-2.5 border border-gray-300 bg-slate-50 text-gray-700 hover:bg-slate-100 hover:text-slate-800 transition rounded-lg">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-emerald-400 to-emerald-600 text-slate-800 hover:from-teal-700 hover:to-emerald-700 transition rounded-lg">
                        <i class="fas fa-save mr-2"></i>
                        Update Employee
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Prevent inspect element and right-click on the entire page
document.addEventListener('contextmenu', function(e) {
    e.preventDefault();
    return false;
});

// Prevent F12 and Ctrl+Shift+I (DevTools shortcuts)
document.addEventListener('keydown', function(e) {
    // F12
    if (e.keyCode === 123) {
        e.preventDefault();
        return false;
    }
    // Ctrl+Shift+I
    if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
        e.preventDefault();
        return false;
    }
    // Ctrl+Shift+C
    if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
        e.preventDefault();
        return false;
    }
    // Ctrl+Shift+J
    if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
        e.preventDefault();
        return false;
    }
    // Ctrl+U (View Source)
    if (e.ctrlKey && e.keyCode === 85) {
        e.preventDefault();
        return false;
    }
});

// Clear password field on page load to prevent autofill inspection
window.addEventListener('load', function() {
    const passwordField = document.querySelector('input[name="password"]');
    if (passwordField) {
        passwordField.value = '';
    }
});
</script>

<?php 
// Include footer layout
include __DIR__ . '/../layouts/footer.php'; 
?>

