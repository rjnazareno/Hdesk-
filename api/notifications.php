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
            
            // Add user photos to notifications
            foreach ($notifications as &$notification) {
                $notification['user_photo'] = getUserPhotoForNotification($notification, $db);
            }
            unset($notification); // Break reference
            
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

/**
 * Get user photo for notification based on notification type and content
 */
function getUserPhotoForNotification($notification, $db) {
    // Default avatar for different notification types
    $defaultAvatars = [
        'ticket_reply' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#3B82F6"/><text x="50" y="55" text-anchor="middle" fill="white" font-size="40" font-family="Arial">💬</text></svg>'),
        'ticket_status' => 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#10B981"/><text x="50" y="55" text-anchor="middle" fill="white" font-size="40" font-family="Arial">📋</text></svg>'),
        'new_ticket' => 'data:image/svg+xml;base64=' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#F59E0B"/><text x="50" y="55" text-anchor="middle" fill="white" font-size="40" font-family="Arial">🎫</text></svg>'),
        'assignment' => 'data:image/svg+xml;base64=' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#8B5CF6"/><text x="50" y="55" text-anchor="middle" fill="white" font-size="40" font-family="Arial">👤</text></svg>'),
        'default' => 'data:image/svg+xml;base64=' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" fill="#6B7280"/><text x="50" y="55" text-anchor="middle" fill="white" font-size="40" font-family="Arial">🔔</text></svg>')
    ];
    
    // Try to extract ticket ID from action_url to get sender info
    if ($notification['action_url'] && preg_match('/ticket\.php\?id=(\d+)/', $notification['action_url'], $matches)) {
        $ticketId = intval($matches[1]);
        
        try {
            // Get the latest response from this ticket to determine sender
            $stmt = $db->prepare("
                SELECT tr.user_type, tr.user_id, 
                       CASE 
                           WHEN tr.user_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                           WHEN tr.user_type = 'it_staff' THEN its.name
                       END as sender_name
                FROM ticket_responses tr
                LEFT JOIN employees e ON tr.user_type = 'employee' AND tr.user_id = e.id
                LEFT JOIN it_staff its ON tr.user_type = 'it_staff' AND tr.user_id = its.staff_id
                WHERE tr.ticket_id = ?
                ORDER BY tr.created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$ticketId]);
            $latestResponse = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($latestResponse && $latestResponse['sender_name']) {
                // Generate a colored avatar based on sender name
                $colors = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6', '#F97316'];
                $colorIndex = strlen($latestResponse['sender_name']) % count($colors);
                $color = $colors[$colorIndex];
                $initials = strtoupper(substr($latestResponse['sender_name'], 0, 1));
                
                return 'data:image/svg+xml;base64,' . base64_encode(
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">' .
                    '<rect width="100" height="100" fill="' . $color . '" rx="50"/>' .
                    '<text x="50" y="65" text-anchor="middle" fill="white" font-size="45" font-family="Arial, sans-serif" font-weight="bold">' . $initials . '</text>' .
                    '</svg>'
                );
            }
        } catch (Exception $e) {
            // Fallback to default avatar on error
        }
    }
    
    // Return appropriate default avatar based on notification type
    return $defaultAvatars[$notification['type']] ?? $defaultAvatars['default'];
}
?>