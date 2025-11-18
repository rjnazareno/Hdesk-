<?php
require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireITStaff();

$currentUser = $auth->getCurrentUser();
$userModel = new User();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'full_name' => sanitize($_POST['full_name'] ?? ''),
        'email' => sanitize($_POST['email'] ?? '')
    ];
    
    // Only update password if provided
    if (!empty($_POST['password'])) {
        $data['password'] = $_POST['password'];
    }
    
    // Validate
    $errors = [];
    if (empty($data['full_name'])) $errors[] = 'Full name is required';
    if (empty($data['email'])) $errors[] = 'Email is required';
    
    // Check if email is taken by another user
    $existingUser = $userModel->findByEmail($data['email']);
    if ($existingUser && $existingUser['id'] != $currentUser['id']) {
        $errors[] = 'Email is already in use';
    }
    
    if (empty($errors)) {
        if ($userModel->update($currentUser['id'], $data)) {
            $_SESSION['success'] = 'Profile updated successfully';
            // Refresh current user data
            $_SESSION['user'] = $userModel->findById($currentUser['id']);
            header('Location: profile.php');
            exit;
        } else {
            $_SESSION['error'] = 'Failed to update profile';
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

// Get fresh user data
$currentUser = $auth->getCurrentUser();

// Page variables
$pageTitle = 'My Profile - IT Help Desk';
$baseUrl = '../';

include __DIR__ . '/../views/layouts/header.php';
?>

<!-- Main Content -->
<div class="lg:ml-64 min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900">
    <!-- Top Bar -->
    <div class="bg-slate-800/50 border-b border-slate-700/50 backdrop-blur-md">
        <div class="flex items-center justify-between px-4 lg:px-8 py-4 pt-20 lg:pt-4">
            <div class="flex items-center space-x-4">
                <div class="hidden lg:flex items-center justify-center w-10 h-10 bg-gradient-to-br from-cyan-500 to-blue-600 text-white rounded-lg">
                    <i class="fas fa-user text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-white">My Profile</h1>
                    <p class="text-sm text-slate-400">Manage your account settings</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="p-4 lg:p-8">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-6 p-4 bg-red-500/20 border border-red-500/50 text-red-300 rounded-lg flex items-start">
            <i class="fas fa-exclamation-circle mt-0.5 mr-3"></i>
            <div><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-6 p-4 bg-green-500/20 border border-green-500/50 text-green-300 rounded-lg flex items-start">
            <i class="fas fa-check-circle mt-0.5 mr-3"></i>
            <div><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        </div>
        <?php endif; ?>

        <div class="max-w-3xl">
            <form method="POST" action="" class="space-y-6">
                <!-- Profile Info Card -->
                <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-700/50">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <i class="fas fa-id-card mr-2 text-cyan-500"></i>
                            Profile Information
                        </h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <!-- Avatar -->
                        <div class="flex items-center space-x-4">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($currentUser['full_name']); ?>&background=000000&color=06b6d4&size=128" 
                                 alt="Avatar" 
                                 class="w-20 h-20 rounded-full">
                            <div>
                                <h3 class="text-lg font-semibold text-white"><?php echo htmlspecialchars($currentUser['full_name']); ?></h3>
                                <p class="text-sm text-slate-400"><?php echo htmlspecialchars($currentUser['email']); ?></p>
                                <span class="inline-flex items-center px-2 py-0.5 mt-1 text-xs font-medium border border-slate-600 text-slate-300 bg-slate-700/30 rounded">
                                    <?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Full Name -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Full Name <span class="text-red-400">*</span>
                            </label>
                            <input type="text" 
                                   name="full_name" 
                                   value="<?php echo htmlspecialchars($currentUser['full_name'] ?? ''); ?>"
                                   required
                                   class="w-full px-4 py-2 bg-slate-700/50 border border-slate-600 text-white rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Email Address <span class="text-red-400">*</span>
                            </label>
                            <input type="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($currentUser['email'] ?? ''); ?>"
                                   required
                                   class="w-full px-4 py-2 bg-slate-700/50 border border-slate-600 text-white rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                        </div>

                        <!-- Username (Read-only) -->
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                Username
                            </label>
                            <input type="text" 
                                   value="<?php echo htmlspecialchars($currentUser['username'] ?? ''); ?>"
                                   readonly
                                   class="w-full px-4 py-2 bg-slate-700/30 border border-slate-700 text-slate-400 rounded-lg cursor-not-allowed">
                            <p class="mt-1 text-xs text-slate-500">
                                <i class="fas fa-info-circle mr-1"></i>
                                Username cannot be changed
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Security Card -->
                <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-700/50">
                        <h2 class="text-lg font-semibold text-white flex items-center">
                            <i class="fas fa-lock mr-2 text-cyan-500"></i>
                            Change Password
                        </h2>
                    </div>
                    <div class="p-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">
                                New Password
                            </label>
                            <input type="password" 
                                   name="password" 
                                   placeholder="Leave blank to keep current password"
                                   autocomplete="new-password"
                                   class="w-full px-4 py-2 bg-slate-700/50 border border-slate-600 text-white rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            <p class="mt-2 text-xs text-slate-400">
                                <i class="fas fa-info-circle mr-1"></i>
                                Only fill this if you want to change your password
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-6 border-t border-slate-700/50">
                    <a href="dashboard.php" class="px-6 py-2.5 border border-slate-600 bg-slate-700/50 text-slate-300 hover:bg-slate-700 hover:text-white transition rounded-lg">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Dashboard
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 text-white hover:from-cyan-600 hover:to-blue-700 transition rounded-lg">
                        <i class="fas fa-save mr-2"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
