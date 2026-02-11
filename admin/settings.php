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
<div class="lg:ml-64 min-h-screen bg-gray-50">
    <?php
    // Set header variables for this page
    $headerTitle = 'Settings';
    $headerSubtitle = 'Configure your preferences';
    $showQuickActions = false;
    $showSearch = false;
    
    include __DIR__ . '/../includes/top_header.php';
    ?>

    <!-- Content -->
    <div class="p-4 lg:p-8">
        <div class="max-w-4xl">
            <!-- Notification Settings -->
            <div class="bg-white border border-gray-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-white">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-bell mr-2 text-gray-700"></i>
                        Notification Preferences
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Email Notifications</h3>
                            <p class="text-xs text-gray-500 mt-1">Receive email updates for ticket activities</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Push Notifications</h3>
                            <p class="text-xs text-gray-500 mt-1">Browser notifications for new tickets and updates</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Ticket Assignment</h3>
                            <p class="text-xs text-gray-500 mt-1">Notify when tickets are assigned to you</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between py-3">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">SLA Alerts</h3>
                            <p class="text-xs text-gray-500 mt-1">Alerts for tickets approaching SLA breach</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer" checked>
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Display Settings -->
            <div class="bg-white border border-gray-200 overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200 bg-white">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-desktop mr-2 text-gray-700"></i>
                        Display Settings
                    </h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Items Per Page</h3>
                            <p class="text-xs text-gray-500 mt-1">Number of tickets shown per page</p>
                        </div>
                        <select class="px-4 py-2 bg-white border border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between py-3 border-b border-gray-200">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Date Format</h3>
                            <p class="text-xs text-gray-500 mt-1">How dates are displayed</p>
                        </div>
                        <select class="px-4 py-2 bg-white border border-gray-300 text-gray-900 focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                            <option value="M d, Y" selected>Nov 18, 2025</option>
                            <option value="d/m/Y">18/11/2025</option>
                            <option value="Y-m-d">2025-11-18</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between py-3">
                        <div class="flex-1">
                            <h3 class="text-sm font-medium text-gray-900">Compact View</h3>
                            <p class="text-xs text-gray-500 mt-1">Show more tickets in less space</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-gray-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-gray-900"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Account Information -->
            <div class="bg-white border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-white">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-info-circle mr-2 text-gray-700"></i>
                        Account Information
                    </h2>
                </div>
                <div class="p-6 space-y-3">
                    <div class="flex items-center justify-between py-2 text-sm">
                        <span class="text-gray-600">Account Type</span>
                        <span class="text-gray-900 font-medium"><?php echo ucfirst(str_replace('_', ' ', $currentUser['role'])); ?></span>
                    </div>
                    <div class="flex items-center justify-between py-2 text-sm border-t border-gray-200">
                        <span class="text-gray-600">Member Since</span>
                        <span class="text-gray-900 font-medium"><?php echo date('F Y', strtotime($currentUser['created_at'])); ?></span>
                    </div>
                    <div class="flex items-center justify-between py-2 text-sm border-t border-gray-200">
                        <span class="text-gray-600">User ID</span>
                        <span class="text-gray-900 font-medium">#<?php echo $currentUser['id']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 mt-6 border-t border-gray-200">
                <a href="dashboard.php" class="px-6 py-2.5 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 transition">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
                <button type="button" class="px-6 py-2.5 bg-gray-900 text-white hover:bg-gray-800 transition">
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
