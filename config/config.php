<?php
/**
 * Application Configuration
 * Main configuration file for the IT Help Ticketing System
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL Configuration
define('BASE_URL', 'http://localhost/IThelp/');
define('ASSETS_URL', BASE_URL . 'assets/');

// Application Settings
define('APP_NAME', 'ResolveIT');
define('APP_VERSION', '1.0.0');
define('APP_TIMEZONE', 'UTC');

// File Upload Settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xlsx', 'txt']);

// Email Configuration (PHPMailer)
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_FROM_EMAIL', 'noreply@company.com');
define('MAIL_FROM_NAME', 'IT Help Desk');
define('MAIL_ENCRYPTION', 'tls');

// Pagination
define('ITEMS_PER_PAGE', 10);

// Ticket Number Prefix
define('TICKET_PREFIX', 'TKT');

// Timezone
date_default_timezone_set(APP_TIMEZONE);

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
