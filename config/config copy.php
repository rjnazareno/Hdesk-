<?php
/**
 * Database Configuration
 * Ticketing System - PHP 8+ with MySQL
 */

// Database Configuration
define('DB_HOST', '153.92.15.63');
define('DB_NAME', 'u816220874_ithelp');
define('DB_USER', 'u816220874_IT');  // Default XAMPP MySQL username
define('DB_PASS', 'F]n5HZgi$fK');      // Default XAMPP MySQL password (empty)
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'IThelp - IT Ticketing System');
define('APP_URL', 'http://localhost/IThelp/');
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

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>