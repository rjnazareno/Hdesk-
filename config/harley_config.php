<?php
/**
 * Harley HRIS Database Configuration
 * Remote database connection settings for syncing employees
 */

// ============================================================
// PRODUCTION: Hostinger Remote Database
// ============================================================
define('HARLEY_DB_HOST', 'srv1866.hstgr.io');
define('HARLEY_DB_NAME', 'u816220874_calendartype');
define('HARLEY_DB_USER', 'u816220874_calendartype');
define('HARLEY_DB_PASS', 'Gr33n$$wRf');
define('HARLEY_DB_CHARSET', 'utf8mb4');

// ============================================================
// LOCAL TESTING (uncomment to test locally)
// ============================================================
// define('HARLEY_DB_HOST', 'localhost');
// define('HARLEY_DB_NAME', 'u816220874_harleyrss');
// define('HARLEY_DB_USER', 'root');
// define('HARLEY_DB_PASS', '');
// define('HARLEY_DB_CHARSET', 'utf8mb4');

// Sync Settings
define('HARLEY_SYNC_ENABLED', true);
define('HARLEY_SYNC_INTERVAL', 300); // 5 minutes in seconds
define('HARLEY_EMPLOYEES_TABLE', 'employees');

/**
 * Get Harley Database Connection
 * @return PDO|null
 */
function getHarleyConnection() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $dsn = "mysql:host=" . HARLEY_DB_HOST . ";dbname=" . HARLEY_DB_NAME . ";charset=" . HARLEY_DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10,
            ];
            $connection = new PDO($dsn, HARLEY_DB_USER, HARLEY_DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Harley DB Connection Error: " . $e->getMessage());
            return null;
        }
    }
    
    return $connection;
}
