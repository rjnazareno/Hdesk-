<?php
/**
 * Cron Job: Sync Employees from Harley HRIS
 * 
 * Run this script periodically to keep employees in sync.
 * Windows Task Scheduler: Run every 5 minutes
 * Linux Cron: Use crontab -e and add the script path
 */

// Set working directory
chdir(dirname(__DIR__));

// Load dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/harley_config.php';
require_once __DIR__ . '/../includes/HarleySyncService.php';

// Check if sync is enabled
if (!HARLEY_SYNC_ENABLED) {
    echo "[" . date('Y-m-d H:i:s') . "] Harley sync is disabled. Exiting.\n";
    exit(0);
}

echo "[" . date('Y-m-d H:i:s') . "] Starting Harley employee sync...\n";

try {
    $syncService = new HarleySyncService();
    
    // Test connection first
    $connectionTest = $syncService->testConnection();
    
    if (!$connectionTest['success']) {
        echo "[ERROR] Cannot connect to Harley: {$connectionTest['message']}\n";
        exit(1);
    }
    
    echo "[INFO] Connected to Harley. {$connectionTest['employee_count']} employees found.\n";
    
    // Determine sync mode based on last sync time
    $lastSyncFile = __DIR__ . '/../storage/last_harley_sync.txt';
    $lastSync = null;
    
    if (file_exists($lastSyncFile)) {
        $lastSync = file_get_contents($lastSyncFile);
        echo "[INFO] Last sync: {$lastSync}\n";
        
        // Incremental sync if synced within last 24 hours
        $result = $syncService->incrementalSync($lastSync);
    } else {
        echo "[INFO] No previous sync found. Running full sync...\n";
        $result = $syncService->fullSync();
    }
    
    // Save sync time
    if (!is_dir(dirname($lastSyncFile))) {
        mkdir(dirname($lastSyncFile), 0755, true);
    }
    file_put_contents($lastSyncFile, date('Y-m-d H:i:s'));
    
    // Output results
    if ($result['success']) {
        echo "[SUCCESS] Sync completed!\n";
        echo "  - Total processed: {$result['total']}\n";
        if (isset($result['stats'])) {
            echo "  - Created: {$result['stats']['created']}\n";
            echo "  - Updated: {$result['stats']['updated']}\n";
            echo "  - Unchanged: {$result['stats']['unchanged']}\n";
            echo "  - Errors: {$result['stats']['errors']}\n";
        }
    } else {
        echo "[ERROR] Sync failed: {$result['error']}\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "[ERROR] Exception: {$e->getMessage()}\n";
    exit(1);
}

echo "[" . date('Y-m-d H:i:s') . "] Sync complete.\n";
exit(0);
