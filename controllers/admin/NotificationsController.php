<?php
/**
 * Notifications Controller
 * Handles notification display and management for all user types
 */

class NotificationsController {
    private $auth;
    private $db;
    private $currentUser;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireLogin();
        
        $this->db = Database::getInstance()->getConnection();
        $this->currentUser = $this->auth->getCurrentUser();
    }
    
    /**
     * Display notifications page
     */
    public function index() {
        // Get user type
        $userType = $_SESSION['user_type'] ?? 'employee';
        $userId = $this->currentUser['id'];
        
        // Pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $itemsPerPage = 5; // Show 5 per page so pagination is visible
        $offset = ($page - 1) * $itemsPerPage;
        
        // Get paginated notifications for this user
        $notificationsData = $this->getNotifications($userId, $userType, $itemsPerPage, $offset);
        
        // Get notification stats
        $stats = $this->getNotificationStats($userId, $userType);
        
        // Pass data to view
        $currentUser = $this->currentUser;
        $notifications = $notificationsData['notifications'];
        $pagination = $notificationsData['pagination'];
        
        // Load appropriate view based on user type
        if ($userType === 'employee' && ($this->currentUser['role'] === 'it_staff' || $this->currentUser['role'] === 'admin')) {
            // IT Staff or Admin
            $this->loadView('admin/notifications', compact('currentUser', 'notifications', 'stats', 'userType', 'pagination'));
        } else {
            // Regular Employee
            $this->loadView('customer/notifications', compact('currentUser', 'notifications', 'stats', 'userType', 'pagination'));
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead() {
        $notificationId = $_POST['notification_id'] ?? 0;
        
        if ($notificationId) {
            $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = :id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $notificationId,
                ':user_id' => $this->currentUser['id']
            ]);
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }
        exit;
    }
    
    /**
     * Mark all notifications as read
     */
    public function markAllAsRead() {
        $sql = "UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = :user_id AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $this->currentUser['id']]);
        
        $_SESSION['success'] = "All notifications marked as read!";
        
        $userType = $_SESSION['user_type'] ?? 'employee';
        if ($userType === 'employee' && ($this->currentUser['role'] === 'it_staff' || $this->currentUser['role'] === 'admin')) {
            header("Location: notifications.php");
            exit();
        } else {
            header("Location: " . BASE_URL . "customer/notifications.php");
            exit();
        }
    }
    
    /**
     * Delete notification
     */
    public function delete() {
        $notificationId = $_POST['notification_id'] ?? 0;
        
        if ($notificationId) {
            $sql = "DELETE FROM notifications WHERE id = :id AND user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':id' => $notificationId,
                ':user_id' => $this->currentUser['id']
            ]);
            
            $_SESSION['success'] = "Notification deleted!";
        }
        
        $userType = $_SESSION['user_type'] ?? 'employee';
        if ($userType === 'employee' && ($this->currentUser['role'] === 'it_staff' || $this->currentUser['role'] === 'admin')) {
            header("Location: notifications.php");
            exit();
        } else {
            header("Location: " . BASE_URL . "customer/notifications.php");
            exit();
        }
    }
    
    /**
     * Get notifications for user with pagination
     */
    private function getNotifications($userId, $userType, $limit = 10, $offset = 0) {
        // Get total count
        $countSql = "SELECT COUNT(*) as total
                     FROM notifications n
                     WHERE n.user_id = :user_id";
        
        $stmt = $this->db->prepare($countSql);
        $stmt->execute([':user_id' => $userId]);
        $totalNotifications = $stmt->fetch()['total'];
        
        // Get paginated notifications
        $sql = "SELECT n.*, 
                t.ticket_number, t.title as ticket_title
                FROM notifications n
                LEFT JOIN tickets t ON n.ticket_id = t.id
                WHERE n.user_id = :user_id
                ORDER BY n.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $notifications = $stmt->fetchAll();
        
        // Calculate pagination
        $totalPages = ceil($totalNotifications / $limit);
        $currentPage = floor($offset / $limit) + 1;
        
        return [
            'notifications' => $notifications,
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalItems' => $totalNotifications,
                'itemsPerPage' => $limit,
                'hasNextPage' => $currentPage < $totalPages,
                'hasPrevPage' => $currentPage > 1
            ]
        ];
    }
    
    /**
     * Get notification statistics
     */
    private function getNotificationStats($userId, $userType) {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as `read`,
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today
                FROM notifications
                WHERE user_id = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch();
    }
    
    /**
     * Load a view file
     */
    private function loadView($viewName, $data = []) {
        extract($data);
        $viewPath = __DIR__ . '/../../views/' . $viewName . '.view.php';
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View not found: {$viewPath}");
        }
    }
}
