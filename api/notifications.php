<?php
/**
 * Notifications API Endpoint
 * Handles AJAX requests for notifications
 */

// Use the same config as the main app
require_once __DIR__ . '/../config/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Please login first']);
    exit;
}

// Initialize notification model
try {
    $db = Database::getInstance()->getConnection();
    $notification = new Notification($db);
    
    // Get user ID based on user type
    $sessionUserType = $_SESSION['user_type'] ?? 'employee';
    
    // For employees, use employee_id from session; for admin/IT, use user_id
    if ($sessionUserType === 'employee') {
        $userId = $_SESSION['employee_id'] ?? $_SESSION['user_id'];
        $userType = 'employee';
    } else {
        $userId = $_SESSION['user_id'];
        $userType = 'user';
    }
    
    // Debug logging
    error_log("Notifications API - Session User Type: " . $sessionUserType);
    error_log("Notifications API - User ID: " . $userId);
    error_log("Notifications API - User Type for query: " . $userType);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
    exit;
}

// Get action from request
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_recent':
        // Get recent notifications (last 10) - pass user type
        $notifications = $notification->getRecentByUser($userId, 10, $userType);
        $unreadCount = $notification->getUnreadCount($userId, $userType);
        
        // Debug logging
        error_log("Notifications API - Found " . count($notifications) . " notifications for user_id={$userId}, type={$userType}");
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'debug' => [
                'user_id' => $userId,
                'user_type' => $userType,
                'session_user_type' => $_SESSION['user_type'] ?? 'not set',
                'count' => count($notifications)
            ]
        ]);
        break;
        
    case 'mark_read':
        // Mark single notification as read - pass user type
        $notificationId = $_POST['notification_id'] ?? 0;
        
        if ($notificationId) {
            $result = $notification->markAsRead($notificationId, $userId, $userType);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Marked as read' : 'Failed to mark as read'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
        }
        break;
        
    case 'mark_all_read':
        // Mark all notifications as read - pass user type
        $result = $notification->markAllAsRead($userId, $userType);
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'All marked as read' : 'Failed to mark all as read'
        ]);
        break;
        
    case 'delete':
        // Delete a notification - pass user type
        $notificationId = $_POST['notification_id'] ?? 0;
        
        if ($notificationId) {
            $result = $notification->delete($notificationId, $userId, $userType);
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Notification deleted' : 'Failed to delete'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
        }
        break;
        
    case 'get_count':
        // Get unread count only (for polling) - pass user type
        $count = $notification->getUnreadCount($userId, $userType);
        echo json_encode([
            'success' => true,
            'unread_count' => $count
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
