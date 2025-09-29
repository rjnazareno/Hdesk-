<?php
/**
 * Common utility functions for the ticketing system
 */

/**
 * Format ticket priority for display
 */
function formatPriority($priority) {
    $priorities = [
        'low' => ['Low', 'text-green-600', 'bg-green-100'],
        'medium' => ['Medium', 'text-yellow-600', 'bg-yellow-100'],
        'high' => ['High', 'text-red-600', 'bg-red-100']
    ];
    
    return $priorities[$priority] ?? $priorities['low'];
}

/**
 * Format ticket status for display
 */
function formatStatus($status) {
    $statuses = [
        'open' => ['Open', 'text-yellow-600', 'bg-yellow-100'],
        'in_progress' => ['In Progress', 'text-blue-600', 'bg-blue-100'],
        'resolved' => ['Resolved', 'text-green-600', 'bg-green-100'],
        'closed' => ['Closed', 'text-gray-600', 'bg-gray-100']
    ];
    
    return $statuses[$status] ?? $statuses['open'];
}

/**
 * Get category icon
 */
function getCategoryIcon($category) {
    $icons = [
        'Hardware' => 'fas fa-desktop',
        'Software' => 'fas fa-code',
        'Network' => 'fas fa-network-wired',
        'Account' => 'fas fa-user',
        'Email' => 'fas fa-envelope',
        'Phone' => 'fas fa-phone',
        'Security' => 'fas fa-shield-alt',
        'General' => 'fas fa-question-circle'
    ];
    
    return $icons[$category] ?? $icons['General'];
}

/**
 * Calculate time ago
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    $units = [
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    ];
    
    foreach ($units as $unit => $val) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return ($numberOfUnits > 1) ? $numberOfUnits . ' ' . $val . 's ago' : '1 ' . $val . ' ago';
    }
    
    return 'just now';
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

/**
 * Truncate text with ellipsis
 */
function truncateText($text, $length = 50, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length - strlen($suffix)) . $suffix;
}

/**
 * Clean filename for storage
 */
function cleanFilename($filename) {
    // Remove any path components
    $filename = basename($filename);
    
    // Remove special characters
    $filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', $filename);
    
    // Limit length
    if (strlen($filename) > 100) {
        $info = pathinfo($filename);
        $name = substr($info['filename'], 0, 96 - strlen($info['extension']));
        $filename = $name . '.' . $info['extension'];
    }
    
    return $filename;
}

/**
 * Check if user can access ticket
 */
function canAccessTicket($ticketId, $userId, $userType, $db) {
    try {
        $stmt = $db->prepare("SELECT employee_id FROM tickets WHERE ticket_id = ?");
        $stmt->execute([$ticketId]);
        $ticket = $stmt->fetch();
        
        if (!$ticket) {
            return false;
        }
        
        // IT staff can access all tickets
        if ($userType === 'it') {
            return true;
        }
        
        // Employees can only access their own tickets
        if ($userType === 'employee') {
            return $ticket['employee_id'] == $userId;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Can access ticket error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log user action
 */
function logUserAction($action, $details = [], $userId = null, $userType = null) {
    global $auth;
    
    if (!$userId) {
        $userId = $auth->getUserId();
    }
    
    if (!$userType) {
        $userType = $auth->getUserType();
    }
    
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $userId,
        'user_type' => $userType,
        'action' => $action,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ];
    
    error_log("USER_ACTION: " . json_encode($logData));
}

/**
 * Get system statistics
 */
function getSystemStats($db, $userType = null, $userId = null) {
    try {
        $stats = [];
        
        if ($userType === 'it') {
            // IT staff stats
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total_tickets,
                    COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
                    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
                    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
                    COUNT(CASE WHEN priority = 'high' AND status IN ('open', 'in_progress') THEN 1 END) as high_priority,
                    COUNT(CASE WHEN assigned_to IS NULL AND status != 'closed' THEN 1 END) as unassigned
                FROM tickets
            ");
            $stats = $stmt->fetch();
            
            // Average resolution time (for closed tickets in last 30 days)
            $stmt = $db->query("
                SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, closed_at)) as avg_resolution_hours
                FROM tickets 
                WHERE status = 'closed' 
                AND closed_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
                AND closed_at IS NOT NULL
            ");
            $avgTime = $stmt->fetch();
            $stats['avg_resolution_hours'] = round($avgTime['avg_resolution_hours'] ?? 0, 1);
            
        } elseif ($userType === 'employee' && $userId) {
            // Employee stats
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_tickets,
                    COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
                    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
                    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets
                FROM tickets 
                WHERE employee_id = ?
            ");
            $stmt->execute([$userId]);
            $stats = $stmt->fetch();
        }
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Get system stats error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get IT staff list for assignments
 */
function getITStaffList($db) {
    try {
        $stmt = $db->prepare("SELECT staff_id, name, email FROM it_staff WHERE is_active = 1 ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get IT staff list error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get ticket categories
 */
function getTicketCategories($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM ticket_categories WHERE is_active = 1 ORDER BY sort_order");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Get ticket categories error: " . $e->getMessage());
        return [];
    }
}

/**
 * Validate ticket data
 */
function validateTicketData($data) {
    $errors = [];
    
    if (empty($data['subject']) || strlen(trim($data['subject'])) < 5) {
        $errors[] = 'Subject must be at least 5 characters long';
    }
    
    if (empty($data['description']) || strlen(trim($data['description'])) < 10) {
        $errors[] = 'Description must be at least 10 characters long';
    }
    
    if (!in_array($data['priority'] ?? '', ['low', 'medium', 'high'])) {
        $errors[] = 'Invalid priority level';
    }
    
    if (empty($data['category'])) {
        $errors[] = 'Category is required';
    }
    
    return $errors;
}

/**
 * Check maintenance mode
 */
function isMaintenanceMode() {
    return file_exists(__DIR__ . '/../maintenance.flag');
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            $ip = trim($ips[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}
?>