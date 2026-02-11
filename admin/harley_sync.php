<?php
/**
 * Harley Sync Admin Page
 * Manage employee synchronization from Harley HRIS
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/HarleySyncService.php';

$auth = new Auth();
$auth->requireRole('admin');

$currentUser = $auth->getCurrentUser();
$syncService = new HarleySyncService();

$message = '';
$messageType = '';
$syncResult = null;
$connectionTest = null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'test_connection':
            $connectionTest = $syncService->testConnection();
            $messageType = $connectionTest['success'] ? 'success' : 'error';
            $message = $connectionTest['message'];
            if ($connectionTest['success']) {
                $message .= " ({$connectionTest['employee_count']} employees found)";
            }
            break;
            
        case 'full_sync':
            $syncResult = $syncService->fullSync();
            $messageType = $syncResult['success'] ? 'success' : 'error';
            if ($syncResult['success']) {
                $message = "Full sync completed: {$syncResult['stats']['created']} created, {$syncResult['stats']['updated']} updated, {$syncResult['stats']['errors']} errors";
            } else {
                $message = "Sync failed: " . ($syncResult['error'] ?? 'Unknown error');
            }
            break;
            
        case 'incremental_sync':
            $since = $_POST['since'] ?? date('Y-m-d H:i:s', strtotime('-24 hours'));
            $syncResult = $syncService->incrementalSync($since);
            $messageType = $syncResult['success'] ? 'success' : 'error';
            if ($syncResult['success']) {
                $message = "Incremental sync completed: {$syncResult['total']} employees processed";
            } else {
                $message = "Sync failed: " . ($syncResult['error'] ?? 'Unknown error');
            }
            break;
    }
}

$pageTitle = 'Harley HRIS Sync - ' . APP_NAME;
$baseUrl = '../';
include __DIR__ . '/../views/layouts/header.php';
?>

<div class="lg:ml-64 min-h-screen bg-gray-50">
    
    <!-- Top Bar -->
    <div class="bg-white border-b border-gray-200">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-sync-alt text-purple-600"></i>
                    </div>
                    <div>
                        <h1 class="text-xl lg:text-2xl font-semibold text-gray-900">Harley HRIS Sync</h1>
                        <p class="text-sm text-gray-500">Synchronize employees from Harley HRIS system</p>
                    </div>
                </div>
                <a href="employees.php" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Employees
                </a>
            </div>
        </div>
    </div>
    
    <div class="p-6">
        <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?>">
            <div class="flex items-center">
                <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mr-3"></i>
                <span><?php echo htmlspecialchars($message); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            
            <!-- Connection Status Card -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-database text-blue-500 mr-2"></i>
                    Connection Status
                </h2>
                
                <div class="space-y-3 mb-6">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Database Host:</span>
                        <span class="font-mono text-gray-700"><?php echo HARLEY_DB_HOST; ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Database Name:</span>
                        <span class="font-mono text-gray-700"><?php echo HARLEY_DB_NAME; ?></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Sync Status:</span>
                        <span class="px-2 py-1 rounded text-xs font-medium <?php echo HARLEY_SYNC_ENABLED ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                            <?php echo HARLEY_SYNC_ENABLED ? 'Enabled' : 'Disabled'; ?>
                        </span>
                    </div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="action" value="test_connection">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plug mr-2"></i>
                        Test Connection
                    </button>
                </form>
            </div>
            
            <!-- Sync Actions Card -->
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-sync text-green-500 mr-2"></i>
                    Sync Actions
                </h2>
                
                <div class="space-y-4">
                    <!-- Full Sync -->
                    <form method="POST">
                        <input type="hidden" name="action" value="full_sync">
                        <button type="submit" class="w-full px-4 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-left">
                            <div class="flex items-center justify-between">
                                <div>
                                    <i class="fas fa-cloud-download-alt mr-2"></i>
                                    <span class="font-medium">Full Sync</span>
                                </div>
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <p class="text-xs text-green-100 mt-1 ml-6">Sync all employees from Harley</p>
                        </button>
                    </form>
                    
                    <!-- Incremental Sync -->
                    <form method="POST" class="space-y-2">
                        <input type="hidden" name="action" value="incremental_sync">
                        <div class="flex space-x-2">
                            <input type="datetime-local" name="since" 
                                   value="<?php echo date('Y-m-d\TH:i', strtotime('-24 hours')); ?>"
                                   class="flex-1 px-3 py-2 border border-gray-200 rounded-lg text-sm">
                            <button type="submit" class="px-4 py-2 bg-amber-500 text-white rounded-lg hover:bg-amber-600 transition">
                                <i class="fas fa-clock mr-1"></i>
                                Incremental Sync
                            </button>
                        </div>
                        <p class="text-xs text-gray-500">Only sync employees modified since selected date</p>
                    </form>
                </div>
            </div>
            
        </div>
        
        <!-- Sync Log -->
        <?php if ($syncResult && isset($syncResult['log']) && !empty($syncResult['log'])): ?>
        <div class="mt-6 bg-white rounded-xl border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                <i class="fas fa-list text-gray-500 mr-2"></i>
                Sync Log
            </h2>
            
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-gray-600">Status</th>
                            <th class="px-4 py-2 text-left text-gray-600">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($syncResult['log'] as $log): ?>
                        <tr>
                            <td class="px-4 py-2">
                                <?php
                                $statusColors = [
                                    'created' => 'bg-green-100 text-green-700',
                                    'updated' => 'bg-blue-100 text-blue-700',
                                    'unchanged' => 'bg-gray-100 text-gray-600',
                                    'error' => 'bg-red-100 text-red-700',
                                ];
                                $color = $statusColors[$log['status']] ?? 'bg-gray-100 text-gray-600';
                                ?>
                                <span class="px-2 py-1 rounded text-xs font-medium <?php echo $color; ?>">
                                    <?php echo ucfirst($log['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-700"><?php echo htmlspecialchars($log['message']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Setup Instructions -->
        <div class="mt-6 bg-amber-50 border border-amber-200 rounded-xl p-6">
            <h3 class="text-lg font-semibold text-amber-800 mb-3">
                <i class="fas fa-info-circle mr-2"></i>
                Setup Instructions
            </h3>
            <ol class="list-decimal list-inside space-y-2 text-sm text-amber-700">
                <li>Edit <code class="bg-amber-100 px-1 rounded">config/harley_config.php</code> with your Harley database credentials</li>
                <li>Ensure your Hostinger MySQL allows remote connections (whitelist this server's IP)</li>
                <li>Test the connection using the button above</li>
                <li>Run a full sync to import all employees</li>
                <li>Set up a cron job for automatic syncing (optional)</li>
            </ol>
            
            <div class="mt-4 p-3 bg-amber-100 rounded-lg">
                <p class="text-xs font-mono text-amber-800">
                    <strong>Cron job example (every 5 minutes):</strong><br>
                    */5 * * * * php /path/to/IThelp/cron/sync_harley.php
                </p>
            </div>
        </div>
        
    </div>
</div>

<?php include __DIR__ . '/../views/layouts/footer.php'; ?>
