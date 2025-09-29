<?php
/**
 * Live Deployment Configuration Template
 * IT Ticketing System - Production Environment
 * 
 * Instructions:
 * 1. Copy this file to config/config.php
 * 2. Update all settings below for your production environment
 * 3. Ensure proper file permissions (644 for files, 755 for directories)
 */

// =====================================================
// DATABASE CONFIGURATION - PRODUCTION
// =====================================================

// Database Configuration - UPDATE THESE FOR LIVE SERVER
define('DB_HOST', 'localhost');                    // Your database host
define('DB_NAME', 'it_ticketing_live');            // Your database name
define('DB_USER', 'your_db_username');             // Your database username
define('DB_PASS', 'your_secure_db_password');      // Your database password
define('DB_CHARSET', 'utf8mb4');

// =====================================================
// APPLICATION CONFIGURATION
// =====================================================

// Application Configuration - UPDATE FOR YOUR DOMAIN
define('APP_NAME', 'IT Support Ticketing System');
define('APP_URL', 'https://yourdomain.com/ticketing/');   // Update with your domain
define('APP_VERSION', '1.0.0');
define('APP_ENVIRONMENT', 'production');  // production, staging, development

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../uploads/tickets/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', [
    'jpg', 'jpeg', 'png', 'gif', 'pdf', 
    'doc', 'docx', 'txt', 'zip', 'rar'
]);

// =====================================================
// EMAIL CONFIGURATION - PRODUCTION
// =====================================================

// SMTP Configuration - UPDATE WITH YOUR EMAIL PROVIDER
define('SMTP_HOST', 'mail.yourdomain.com');          // Your SMTP host
define('SMTP_PORT', 587);                            // 587 for TLS, 465 for SSL
define('SMTP_USERNAME', 'noreply@yourdomain.com');   // Your SMTP username
define('SMTP_PASSWORD', 'your_email_password');      // Your SMTP password
define('SMTP_ENCRYPTION', 'tls');                    // tls or ssl
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com'); // From email address
define('SMTP_FROM_NAME', 'IT Support System');       // From name

// IT Staff notification emails (comma separated)
define('IT_NOTIFICATION_EMAILS', 'it-support@yourdomain.com,admin@yourdomain.com');

// =====================================================
// SECURITY CONFIGURATION
// =====================================================

// Security Configuration
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 8 * 3600); // 8 hours in seconds
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 15 * 60); // 15 minutes in seconds

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);     // Set to 1 for HTTPS only
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// =====================================================
// SYSTEM SETTINGS
// =====================================================

// Pagination
define('TICKETS_PER_PAGE', 10);
define('RESPONSES_PER_PAGE', 20);

// Timezone - UPDATE FOR YOUR LOCATION
date_default_timezone_set('America/New_York'); // Adjust to your timezone

// =====================================================
// ERROR HANDLING & LOGGING
// =====================================================

// Error reporting - PRODUCTION SETTINGS
error_reporting(E_ERROR | E_WARNING | E_PARSE);  // Only show critical errors
ini_set('display_errors', 0);                     // Don't display errors to users
ini_set('log_errors', 1);                        // Log errors to file

// Custom error log file
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// =====================================================
// PERFORMANCE SETTINGS
// =====================================================

// Cache Settings
define('ENABLE_CACHING', true);
define('CACHE_DURATION', 300); // 5 minutes

// Database Connection Pool
define('DB_PERSISTENT', false); // Set to true for persistent connections

// =====================================================
// FEATURE FLAGS
// =====================================================

// Feature Configuration
define('ENABLE_FILE_UPLOADS', true);
define('ENABLE_EMAIL_NOTIFICATIONS', true);
define('ENABLE_ACTIVITY_LOGGING', true);
define('ENABLE_AUTO_ASSIGNMENT', false);
define('MAINTENANCE_MODE', false);

// =====================================================
// API CONFIGURATION (if needed)
// =====================================================

// API Settings
define('API_ENABLED', false);
define('API_RATE_LIMIT', 100);        // Requests per hour
define('API_SECRET_KEY', 'your-secret-api-key-here');

// =====================================================
// BACKUP CONFIGURATION
// =====================================================

// Backup Settings
define('BACKUP_ENABLED', true);
define('BACKUP_FREQUENCY', 'daily');   // daily, weekly, monthly
define('BACKUP_RETENTION_DAYS', 30);
define('BACKUP_PATH', __DIR__ . '/../backups/');

// =====================================================
// COMPANY INFORMATION
// =====================================================

// Company Settings - UPDATE WITH YOUR INFORMATION
define('COMPANY_NAME', 'Your Company Name');
define('COMPANY_ADDRESS', 'Your Company Address');
define('COMPANY_PHONE', '+1 (555) 123-4567');
define('COMPANY_EMAIL', 'contact@yourdomain.com');
define('COMPANY_WEBSITE', 'https://yourdomain.com');

// =====================================================
// DEPLOYMENT CHECKLIST
// =====================================================

/*
DEPLOYMENT CHECKLIST:
□ Update database credentials (DB_HOST, DB_NAME, DB_USER, DB_PASS)
□ Update application URL (APP_URL)
□ Configure SMTP settings for email notifications
□ Set proper timezone (date_default_timezone_set)
□ Create uploads directory with proper permissions (755)
□ Create logs directory with proper permissions (755)
□ Run the database schema script (live_database_schema.sql)
□ Test login functionality with employee and IT staff accounts
□ Test ticket creation and response functionality
□ Verify file upload functionality
□ Test email notifications
□ Set up SSL certificate for HTTPS
□ Configure web server (Apache/Nginx) virtual host
□ Set up regular database backups
□ Configure monitoring and alerting
□ Update DNS records if needed
□ Test all functionality in production environment

SECURITY CHECKLIST:
□ Use strong database passwords
□ Enable HTTPS/SSL
□ Set proper file and directory permissions
□ Configure firewall rules
□ Enable security headers in web server
□ Disable directory listing
□ Remove default/test accounts
□ Enable error logging but disable error display
□ Configure rate limiting
□ Set up intrusion detection if needed
*/

// =====================================================
// AUTO-CREATE REQUIRED DIRECTORIES
// =====================================================

// Create required directories if they don't exist
$requiredDirs = [
    __DIR__ . '/../uploads/tickets/',
    __DIR__ . '/../logs/',
    __DIR__ . '/../backups/',
    __DIR__ . '/../cache/'
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        // Create .htaccess for security
        if (strpos($dir, 'uploads') !== false || strpos($dir, 'logs') !== false) {
            file_put_contents($dir . '.htaccess', "deny from all\n");
        }
    }
}

?>