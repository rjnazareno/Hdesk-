<?php
/**
 * Remote Database Test Configuration
 * Use this config to test direct connection to remote database
 * Ticketing System - PHP 8+ with MySQL
 */

// Database Configuration - Remote Host
define('DB_HOST', '153.92.15.63');  // Direct remote database host
define('DB_NAME', 'u816220874_ithelp');
define('DB_USER', 'u816220874_IT');
define('DB_PASS', 'F]n5HZgi$fK');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'IThelp - IT Ticketing System (Remote Test)');
define('APP_URL', 'https://ithelp.resourcestaffonline.com/IThelp/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/tickets/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip']);

// Email Configuration (PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@company.com');
define('SMTP_PASSWORD', 'your_email_password');
define('SMTP_FROM_EMAIL', 'noreply@company.com');
define('SMTP_FROM_NAME', 'IT Support System');

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 8 * 3600); // 8 hours in seconds

// IT Staff notification emails (comma separated)
define('IT_NOTIFICATION_EMAILS', 'it-support@company.com,admin@company.com');

// Pagination
define('TICKETS_PER_PAGE', 10);

// Timezone
date_default_timezone_set('America/New_York');

// Error reporting (test mode - show errors)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
?>