<?php
/**
 * Notification System API
 * Handles in-app notifications (bell icon dropdown)
 */

require_once '../config/database.php';
require_once '../includes/auth.php';

header('Content-Type: application/json');

try {
    // Check authentication
    if (!$auth->isLoggedIn()) {
        throw new Exception('Authentication required');
    }
    
    $userId = $auth->getUserId();
    $userType = $auth->getUserType();
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    $db = Database::getInstance()->getConnection();
    
    switch ($action) {
        case 'get_notifications':
            // Get user's notifications
            $limit = intval($_GET['limit'] ?? 20);
            $stmt = $db->prepare("
                SELECT id, type, title, message, action_url, is_read, created_at
                FROM notifications 
                WHERE user_id = ? AND user_type = ?
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $userType, $limit]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get unread count
            $stmt = $db->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND user_type = ? AND is_read = 0
            ");
            $stmt->execute([$userId, $userType]);
            $unreadCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'unread_count' => intval($unreadCount)
            ]);
            break;
            
        case 'mark_read':
            // Mark notification as read
            $notificationId = intval($_POST['id'] ?? 0);
            $stmt = $db->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND user_id = ? AND user_type = ?
            ");
            $stmt->execute([$notificationId, $userId, $userType]);
            
            echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            break;
            
        case 'mark_all_read':
            // Mark all notifications as read for user
            $stmt = $db->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = CURRENT_TIMESTAMP 
                WHERE user_id = ? AND user_type = ? AND is_read = 0
            ");
            $stmt->execute([$userId, $userType]);
            $affectedRows = $stmt->rowCount();
            
            echo json_encode([
                'success' => true, 
                'message' => "Marked {$affectedRows} notifications as read"
            ]);
            break;
            
        case 'clear_all':
            // Clear (delete) all notifications for user
            $stmt = $db->prepare("
                DELETE FROM notifications 
                WHERE user_id = ? AND user_type = ?
            ");
            $stmt->execute([$userId, $userType]);
            $deletedRows = $stmt->rowCount();
            
            echo json_encode([
                'success' => true, 
                'message' => "Cleared {$deletedRows} notifications"
            ]);
            break;
            
        case 'add_notification':
            // Add new notification (for internal use)
            $type = $_POST['type'] ?? '';
            $title = $_POST['title'] ?? '';
            $message = $_POST['message'] ?? '';
            $actionUrl = $_POST['action_url'] ?? null;
            $targetUserId = intval($_POST['target_user_id'] ?? 0);
            $targetUserType = $_POST['target_user_type'] ?? '';
            
            if (!$type || !$title || !$message || !$targetUserId || !$targetUserType) {
                throw new Exception('Missing required fields');
            }
            
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, user_type, type, title, message, action_url)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$targetUserId, $targetUserType, $type, $title, $message, $actionUrl]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Notification created',
                'id' => $db->lastInsertId()
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>