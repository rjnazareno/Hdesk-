<?php
/**
 * Database Configuration
 * Ticketing System - PHP 8+ with MySQL
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'u816220874_ithelp');
define('DB_USER', 'u816220874_IT');  // Live server MySQL username
define('DB_PASS', 'F]n5HZgi$fK');      // Live server MySQL password
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'IThelp - IT Ticketing System');
define('APP_URL', 'https://ithelp.resourcestaffonline.com/IThelp/'); // Update with your actual domain
define('UPLOAD_DIR', __DIR__ . '/../uploads/tickets/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip']);

// Email Configuration (PHPMailer)
define('SMTP_HOST', 'smtp.gmail.com');      // Replace with your SMTP host
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@company.com');  // Replace with your email
define('SMTP_PASSWORD', 'your_email_password');     // Replace with your email password
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
date_default_timezone_set('America/New_York'); // Adjust to your timezone

// Error reporting (production settings)
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
?>