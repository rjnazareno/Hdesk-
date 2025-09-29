<?php
/**
 * Security Functions
 * CSRF Protection, XSS Prevention, File Upload Validation
 */

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify CSRF token (alias for validateCSRFToken)
 */
function verifyCSRFToken($token) {
    return validateCSRFToken($token);
}

/**
 * Sanitize output to prevent XSS
 */
function escape($string) {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return trim(strip_tags($input));
}

/**
 * Validate file upload
 */
function validateFileUpload($file) {
    $errors = [];
    
    // Check if file was uploaded
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errors[] = 'File size exceeds maximum allowed size.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errors[] = 'File was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errors[] = 'No file was uploaded.';
                break;
            default:
                $errors[] = 'File upload failed.';
        }
        return $errors;
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'File size exceeds ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB limit.';
    }
    
    // Check file type
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExtension, ALLOWED_FILE_TYPES)) {
        $errors[] = 'File type not allowed. Allowed types: ' . implode(', ', ALLOWED_FILE_TYPES);
    }
    
    // Check MIME type for additional security
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimeTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif',
        'application/pdf', 
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/plain',
        'application/zip', 'application/x-zip-compressed'
    ];
    
    if (!in_array($mimeType, $allowedMimeTypes)) {
        $errors[] = 'Invalid file type detected.';
    }
    
    return $errors;
}

/**
 * Generate secure filename
 */
function generateSecureFilename($originalName) {
    $extension = pathinfo($originalName, PATHINFO_EXTENSION);
    return bin2hex(random_bytes(16)) . '.' . $extension;
}

/**
 * Rate limiting (simple implementation)
 */
function checkRateLimit($action, $limit = 10, $window = 3600) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $key = 'rate_limit_' . $action;
    $now = time();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }
    
    // Clean old entries
    $_SESSION[$key] = array_filter($_SESSION[$key], function($timestamp) use ($now, $window) {
        return ($now - $timestamp) < $window;
    });
    
    // Check if limit exceeded
    if (count($_SESSION[$key]) >= $limit) {
        return false;
    }
    
    // Add current timestamp
    $_SESSION[$key][] = $now;
    return true;
}

/**
 * Log security events
 */
function logSecurityEvent($event, $details = []) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'event' => $event,
        'details' => $details
    ];
    
    error_log("SECURITY: " . json_encode($logData));
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Generate strong password
 */
function generatePassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle($chars), 0, $length);
}

/**
 * Get current user display name safely
 */
function getCurrentUserName() {
    if (!isset($_SESSION['user_data'])) {
        return 'User';
    }
    
    return $_SESSION['user_data']['name'] ?? $_SESSION['user_data']['username'] ?? 'User';
}

/**
 * Check if user is logged in and redirect if not
 */
function requireLogin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        header('Location: simple_login.php');
        exit;
    }
}

/**
 * Check if current user is IT staff
 */
function isITStaff() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'it_staff';
}
?>