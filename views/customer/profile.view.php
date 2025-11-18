<?php
/**
 * Customer Profile View
 * Employee profile management page
 */

$pageTitle = 'My Profile - ResolveIT';
$basePath = '../';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen">

    <!-- Customer Navigation -->
    <?php include __DIR__ . '/../../includes/customer_nav.php'; ?>

    <!-- Main Content -->
    <div class="lg:ml-64 min-h-screen">
        <!-- Top Bar -->
        <div class="bg-slate-800/50 backdrop-blur-md border-b border-slate-700/50 sticky top-0 z-30">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div>
                        <h1 class="text-xl lg:text-2xl font-semibold text-white">My Profile</h1>
                        <p class="text-sm text-slate-400 mt-0.5">Manage your account settings</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="mb-6 bg-green-900/20 border border-green-600/50 text-green-400 px-6 py-4 rounded-lg flex items-center">
                    <i class="fas fa-check-circle mr-3 text-xl"></i>
                    <span><?= htmlspecialchars($_SESSION['success']) ?></span>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="mb-6 bg-red-900/20 border border-red-600/50 text-red-400 px-6 py-4 rounded-lg flex items-center">
                    <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>
                    <span><?= htmlspecialchars($_SESSION['error']) ?></span>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column: Profile Card -->
                <div class="lg:col-span-1">
                    <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg p-6">
                        <div class="text-center">
                            <!-- Profile Picture -->
                            <div class="relative inline-block" id="profile-picture">
                                <?php if (!empty($currentUser['profile_picture'])): ?>
                                    <img src="../uploads/profiles/<?= htmlspecialchars($currentUser['profile_picture']) ?>" 
                                         alt="Profile Picture" 
                                         class="w-32 h-32 rounded-full mx-auto border-4 border-cyan-500/50 object-cover">
                                <?php else: ?>
                                    <div class="w-32 h-32 bg-gradient-to-r from-cyan-500 to-blue-600 rounded-full mx-auto border-4 border-cyan-500/50 flex items-center justify-center">
                                        <span class="text-4xl font-bold text-white">
                                            <?= strtoupper(substr($currentUser['fname'], 0, 1) . substr($currentUser['lname'], 0, 1)) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <h2 class="mt-4 text-xl font-semibold text-white">
                                <?= htmlspecialchars($currentUser['fname'] . ' ' . $currentUser['lname']) ?>
                            </h2>
                            <p class="text-sm text-slate-400"><?= htmlspecialchars($currentUser['position'] ?? 'Employee') ?></p>
                            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars($currentUser['company'] ?? '') ?></p>

                            <!-- Upload Picture Button -->
                            <form method="POST" action="profile.php" enctype="multipart/form-data" id="uploadPictureForm" class="mt-4">
                                <input type="hidden" name="action" value="upload_picture">
                                <input type="file" id="profile_picture_input" name="profile_picture" accept="image/jpeg,image/png,image/gif" class="hidden">
                                <button type="button" onclick="document.getElementById('profile_picture_input').click()" 
                                        class="w-full px-4 py-2 bg-gradient-to-r from-cyan-500 to-blue-600 text-white rounded-lg hover:from-cyan-600 hover:to-blue-700 transition">
                                    <i class="fas fa-camera mr-2"></i>Change Picture
                                </button>
                            </form>

                            <p class="text-xs text-slate-500 mt-2">JPG, PNG, GIF (Max 2MB)</p>
                        </div>

                        <!-- Profile Stats -->
                        <div class="mt-6 pt-6 border-t border-slate-700/50 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Username:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($currentUser['username']) ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Employee ID:</span>
                                <span class="text-white font-medium"><?= htmlspecialchars($currentUser['employee_id'] ?? 'N/A') ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-400">Member Since:</span>
                                <span class="text-white font-medium"><?= date('M Y', strtotime($currentUser['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Settings Forms -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Profile Information -->
                    <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                            <i class="fas fa-user-edit mr-2 text-cyan-400"></i>
                            Profile Information
                        </h3>

                        <form method="POST" action="profile.php">
                            <input type="hidden" name="action" value="update_profile">

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Official Email (Read-only) -->
                                <div>
                                    <label class="block text-sm font-medium text-white mb-2">
                                        Official Email
                                    </label>
                                    <input type="email" value="<?= htmlspecialchars($currentUser['email']) ?>" disabled
                                           class="w-full px-4 py-3 bg-slate-700/30 border border-slate-600 text-slate-400 rounded-lg cursor-not-allowed">
                                    <p class="text-xs text-slate-500 mt-1">Contact admin to change</p>
                                </div>

                                <!-- Personal Email -->
                                <div>
                                    <label for="personal_email" class="block text-sm font-medium text-white mb-2">
                                        Personal Email
                                    </label>
                                    <input type="email" id="personal_email" name="personal_email" 
                                           value="<?= htmlspecialchars($currentUser['personal_email'] ?? '') ?>"
                                           class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500 transition-all">
                                </div>

                                <!-- Contact Number -->
                                <div>
                                    <label for="contact" class="block text-sm font-medium text-white mb-2">
                                        Contact Number
                                    </label>
                                    <input type="text" id="contact" name="contact" 
                                           value="<?= htmlspecialchars($currentUser['contact'] ?? '') ?>"
                                           class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500 transition-all"
                                           placeholder="09123456789">
                                </div>

                                <!-- Position (Read-only) -->
                                <div>
                                    <label class="block text-sm font-medium text-white mb-2">
                                        Position
                                    </label>
                                    <input type="text" value="<?= htmlspecialchars($currentUser['position'] ?? 'N/A') ?>" disabled
                                           class="w-full px-4 py-3 bg-slate-700/30 border border-slate-600 text-slate-400 rounded-lg cursor-not-allowed">
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" 
                                        class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold rounded-lg hover:from-cyan-600 hover:to-blue-700 transition">
                                    <i class="fas fa-save mr-2"></i>Update Profile
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg p-6" id="change-password">
                        <h3 class="text-lg font-semibold text-white mb-4 flex items-center">
                            <i class="fas fa-lock mr-2 text-cyan-400"></i>
                            Change Password
                        </h3>

                        <form method="POST" action="profile.php">
                            <input type="hidden" name="action" value="change_password">

                            <div class="space-y-4">
                                <!-- Current Password -->
                                <div>
                                    <label for="current_password" class="block text-sm font-medium text-white mb-2">
                                        Current Password <span class="text-red-400">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="password" id="current_password" name="current_password" required
                                               class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500 transition-all"
                                               placeholder="Enter current password">
                                        <button type="button" onclick="togglePasswordVisibility('current_password')"
                                                class="absolute right-3 top-3 text-slate-400 hover:text-cyan-400">
                                            <i class="fas fa-eye" id="current_password-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- New Password -->
                                <div>
                                    <label for="new_password" class="block text-sm font-medium text-white mb-2">
                                        New Password <span class="text-red-400">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="password" id="new_password" name="new_password" required minlength="6"
                                               class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500 transition-all"
                                               placeholder="Min. 6 characters">
                                        <button type="button" onclick="togglePasswordVisibility('new_password')"
                                                class="absolute right-3 top-3 text-slate-400 hover:text-cyan-400">
                                            <i class="fas fa-eye" id="new_password-eye"></i>
                                        </button>
                                    </div>
                                    <!-- Password Strength Meter -->
                                    <div id="password-strength" class="mt-2 hidden">
                                        <div class="flex items-center justify-between text-xs mb-1">
                                            <span class="text-slate-400">Strength:</span>
                                            <span id="strength-text" class="font-medium"></span>
                                        </div>
                                        <div class="w-full h-1.5 bg-slate-700 rounded-full overflow-hidden">
                                            <div id="strength-bar" class="h-full transition-all duration-300 rounded-full"></div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Confirm Password -->
                                <div>
                                    <label for="confirm_password" class="block text-sm font-medium text-white mb-2">
                                        Confirm New Password <span class="text-red-400">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                               class="w-full px-4 py-3 bg-slate-700/50 border border-slate-600 text-white placeholder-slate-500 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500/50 focus:border-cyan-500 hover:border-slate-500 transition-all"
                                               placeholder="Re-enter new password">
                                        <button type="button" onclick="togglePasswordVisibility('confirm_password')"
                                                class="absolute right-3 top-3 text-slate-400 hover:text-cyan-400">
                                            <i class="fas fa-eye" id="confirm_password-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6 flex justify-end">
                                <button type="submit" 
                                        class="px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold rounded-lg hover:from-cyan-600 hover:to-blue-700 transition">
                                    <i class="fas fa-key mr-2"></i>Change Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Toggle password visibility
    function togglePasswordVisibility(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '-eye');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Password strength meter
    document.getElementById('new_password').addEventListener('input', function(e) {
        const password = e.target.value;
        const strengthMeter = document.getElementById('password-strength');
        const strengthBar = document.getElementById('strength-bar');
        const strengthText = document.getElementById('strength-text');
        
        if (!password) {
            strengthMeter.classList.add('hidden');
            return;
        }
        
        strengthMeter.classList.remove('hidden');
        
        let strength = 0;
        if (password.length >= 6) strength += 20;
        if (password.length >= 8) strength += 10;
        if (password.length >= 12) strength += 10;
        if (/[a-z]/.test(password)) strength += 15;
        if (/[A-Z]/.test(password)) strength += 15;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[^a-zA-Z0-9]/.test(password)) strength += 15;
        
        if (strength <= 30) {
            strengthBar.className = 'h-full transition-all duration-300 rounded-full bg-red-500';
            strengthBar.style.width = strength + '%';
            strengthText.textContent = 'Weak';
            strengthText.className = 'font-medium text-red-400';
        } else if (strength <= 60) {
            strengthBar.className = 'h-full transition-all duration-300 rounded-full bg-yellow-500';
            strengthBar.style.width = strength + '%';
            strengthText.textContent = 'Fair';
            strengthText.className = 'font-medium text-yellow-400';
        } else if (strength <= 80) {
            strengthBar.className = 'h-full transition-all duration-300 rounded-full bg-blue-500';
            strengthBar.style.width = strength + '%';
            strengthText.textContent = 'Good';
            strengthText.className = 'font-medium text-blue-400';
        } else {
            strengthBar.className = 'h-full transition-all duration-300 rounded-full bg-green-500';
            strengthBar.style.width = '100%';
            strengthText.textContent = 'Strong';
            strengthText.className = 'font-medium text-green-400';
        }
    });

    // Auto-submit picture upload
    document.getElementById('profile_picture_input').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            // Validate file size
            if (this.files[0].size > 2 * 1024 * 1024) {
                alert('File too large. Maximum size is 2MB.');
                this.value = '';
                return;
            }
            
            // Validate file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!allowedTypes.includes(this.files[0].type)) {
                alert('Invalid file type. Only JPG, PNG, and GIF are allowed.');
                this.value = '';
                return;
            }
            
            document.getElementById('uploadPictureForm').submit();
        }
    });
    </script>
</body>
</html>
