<?php
require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireITStaff();

$currentUser = $auth->getCurrentUser();

// Page variables
$pageTitle = 'Settings - IT Help Desk';
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
                    <i class="fas fa-cog text-sm"></i>
                </div>
                <div>
                    <h1 class="text-xl lg:text-2xl font-semibold text-white">Settings</h1>
                    <p class="text-sm text-slate-400">Configure your preferences</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="p-4 lg:p-8">
        <div class="max-w-4xl">
            <!-- Notification Settings -->
            <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-slate-700/50">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-bell mr-2 text-cyan-500"></i>
                        Notification Preferences
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-white">Email Notifications</h3>
                            <p class="text-xs text-slate-400 mt-1">Receive email updates for ticket activities</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-cyan-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-white">Push Notifications</h3>
                            <p class="text-xs text-slate-400 mt-1">Browser notifications for new tickets and updates</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-cyan-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-white">Ticket Assignment</h3>
                            <p class="text-xs text-slate-400 mt-1">Notify when tickets are assigned to you</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-cyan-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between py-3">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-white">SLA Alerts</h3>
                            <p class="text-xs text-slate-400 mt-1">Alerts for tickets approaching SLA breach</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-cyan-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Display Settings -->
            <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-slate-700/50">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-desktop mr-2 text-cyan-500"></i>
                        Display Settings
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-white">Items Per Page</h3>
                            <p class="text-xs text-slate-400 mt-1">Number of tickets shown per page</p>
                        </div>
                        <select class="px-4 py-2 bg-slate-700/50 border border-slate-600 text-white rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between py-3 border-b border-slate-700/50">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-white">Date Format</h3>
                            <p class="text-xs text-slate-400 mt-1">How dates are displayed</p>
                        </div>
                        <select class="px-4 py-2 bg-slate-700/50 border border-slate-600 text-white rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            <option value="M d, Y" selected>Nov 18, 2025</option>
                            <option value="d/m/Y">18/11/2025</option>
                            <option value="Y-m-d">2025-11-18</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between py-3">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-white">Compact View</h3>
                            <p class="text-xs text-slate-400 mt-1">Show more tickets in less space</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer">
                            <div class="w-11 h-6 bg-slate-700 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-cyan-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-cyan-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="bg-slate-800/50 backdrop-blur-md border border-slate-700/50 rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-700/50">
                    <h2 class="text-lg font-semibold text-white flex items-center">
                        <i class="fas fa-info-circle mr-2 text-cyan-500"></i>
                        Account Information
                    </h2>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex items-center justify-between py-2 text-sm">
                        <span class="text-slate-400">Account Type</span>
                        <span class="text-white font-medium"><?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?></span>
                    </div>
                    <div class="flex items-center justify-between py-2 text-sm border-t border-slate-700/50">
                        <span class="text-slate-400">Member Since</span>
                        <span class="text-white font-medium"><?php echo date('F Y', strtotime($currentUser['created_at'])); ?></span>
                    </div>
                    <div class="flex items-center justify-between py-2 text-sm border-t border-slate-700/50">
                        <span class="text-slate-400">User ID</span>
                        <span class="text-white font-medium">#<?php echo $currentUser['id']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 mt-6 border-t border-slate-700/50">
                <a href="dashboard.php" class="px-6 py-2.5 border border-slate-600 bg-slate-700/50 text-slate-300 hover:bg-slate-700 hover:text-white transition rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
                <button type="button" class="px-6 py-2.5 bg-gradient-to-r from-cyan-500 to-blue-600 text-white hover:from-cyan-600 hover:to-blue-700 transition rounded-lg">
                    <i class="fas fa-save mr-2"></i>
                    Save Settings
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Note: These toggles are UI-only for now. Backend functionality can be added later.
console.log('Settings page loaded - toggles are UI demonstrations');
</script>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
