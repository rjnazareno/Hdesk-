<?php
/**
 * Application Configuration
 * Main configuration file for the IT Help Ticketing System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables from .env file
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Environment Configuration
define('ENVIRONMENT', getenv('APP_ENV') ?: 'production');
define('IS_PRODUCTION', ENVIRONMENT === 'production');

// Base URL Configuration - Auto-detect or use environment variable
$baseUrl = getenv('BASE_URL');
if (!$baseUrl) {
    // Auto-detect BASE_URL based on server configuration
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $baseUrl = $protocol . '://' . $host . rtrim($scriptDir, '/') . '/';
    
    // If we're at root level, clean up the path
    if ($scriptDir === '/' || $scriptDir === '\\') {
        $baseUrl = $protocol . '://' . $host . '/';
    }
}
define('BASE_URL', rtrim($baseUrl, '/') . '/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Application Settings
define('APP_NAME', 'HDesk');
define('APP_TAGLINE', 'Multi-Department Service Portal');
define('APP_VERSION', '2.0.0');
define('APP_TIMEZONE', 'Asia/Manila');

// Supported Departments
define('SUPPORTED_DEPARTMENTS', ['IT', 'HR']);

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xlsx', 'txt']);

// Email Configuration (PHPMailer)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'it.resourcestaff@gmail.com');
define('MAIL_PASSWORD', 'fqbr ocgu jcfh jwdy');
define('MAIL_FROM_EMAIL', 'it.resourcestaff@gmail.com');
define('MAIL_FROM_NAME', 'HDesk');
define('MAIL_ENCRYPTION', 'tls');

// Pagination
define('ITEMS_PER_PAGE', 10);

// Ticket Number Prefix
define('TICKET_PREFIX', 'TKT');

// Timezone
date_default_timezone_set(APP_TIMEZONE);

// Error Reporting - Production DISABLES display, Development ENABLES
if (IS_PRODUCTION) {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Autoload function for classes
spl_autoload_register(function ($class) {
    $directories = [
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../controllers/admin/',
        __DIR__ . '/../controllers/customer/',
        __DIR__ . '/../includes/',
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Include database configuration
require_once __DIR__ . '/database.php';

/**
 * Helper function to check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Helper function to check user role
 */
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Helper function to check if user is IT staff or admin
 */
function isITStaff() {
    return isLoggedIn() && isset($_SESSION['role']) && 
           ($_SESSION['role'] === 'it_staff' || $_SESSION['role'] === 'admin');
}

/**
 * Helper function to redirect
 */
function redirect($url) {
    header("Location: " . BASE_URL . $url);
    exit();
}

/**
 * Helper function to sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Helper function to format date
 */
function formatDate($date, $format = 'M d, Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Helper function to get status badge color
 */
function getStatusColor($status) {
    $colors = [
        'pending' => 'yellow',
        'open' => 'blue',
        'in_progress' => 'purple',
        'resolved' => 'green',
        'closed' => 'gray'
    ];
    return $colors[$status] ?? 'gray';
}

/**
 * Helper function to get priority color
 */
function getPriorityColor($priority) {
    $colors = [
        'low' => 'green',
        'medium' => 'yellow',
        'high' => 'orange',
        'urgent' => 'red'
    ];
    return $colors[$priority] ?? 'gray';
}

/**
 * Helper function to generate ticket number
 */
function generateTicketNumber() {
    return TICKET_PREFIX . '-' . date('Y') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

/**
 * Helper function to load Tailwind CSS appropriately
 * Development: Uses CDN (faster iteration)
 * Production: Uses local compiled CSS with glass morphism support
 * 
 * @return string HTML string with Tailwind CSS
 */
function getTailwindCSS() {
    // Use locally built Tailwind CSS v3
    return '<link rel="stylesheet" href="' . ASSETS_URL . 'css/tailwind.css">';
}

// Explicitly load Auth class to ensure it's always available
require_once __DIR__ . '/../includes/Auth.php';
