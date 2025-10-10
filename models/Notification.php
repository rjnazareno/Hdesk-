<?php
/**
 * Notification Model
 * Handles all notification-related database operations
 */

class Notification {
    private $conn;
    private $table = 'notifications';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Create a new notification
     * Supports both user_id (IT staff/admin) and employee_id (employees)
     */
    public function create($data) {
        // Determine which ID field to use
        $hasUserId = isset($data['user_id']) && $data['user_id'] !== null;
        $hasEmployeeId = isset($data['employee_id']) && $data['employee_id'] !== null;
        
        if (!$hasUserId && !$hasEmployeeId) {
            throw new Exception('Either user_id or employee_id must be provided');
        }
        
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, employee_id, type, title, message, ticket_id, related_user_id) 
                  VALUES (:user_id, :employee_id, :type, :title, :message, :ticket_id, :related_user_id)";
        
        $stmt = $this->conn->prepare($query);
        
        $userId = $hasUserId ? $data['user_id'] : null;
        $employeeId = $hasEmployeeId ? $data['employee_id'] : null;
        
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':employee_id', $employeeId);
        $stmt->bindParam(':type', $data['type']);
        $stmt->bindParam(':title', $data['title']);
        $stmt->bindParam(':message', $data['message']);
        $stmt->bindParam(':ticket_id', $data['ticket_id']);
        $stmt->bindParam(':related_user_id', $data['related_user_id']);
        
        return $stmt->execute();
    }
    
    /**
     * Get recent notifications for a user or employee (limit 10)
     * @param int $id The user_id or employee_id
     * @param int $limit Number of notifications to return
     * @param string $userType Either 'user' (admin/IT staff) or 'employee'
     */
    public function getRecentByUser($id, $limit = 10, $userType = 'user') {
        $whereClause = $userType === 'employee' ? 'n.employee_id = :id' : 'n.user_id = :id';
        
        $query = "SELECT n.*, 
                         t.title as ticket_title,
                         u.full_name as related_user_name
                  FROM " . $this->table . " n
                  LEFT JOIN tickets t ON n.ticket_id = t.id
                  LEFT JOIN users u ON n.related_user_id = u.id
                  WHERE " . $whereClause . "
                  ORDER BY n.created_at DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get unread count for a user or employee
     * @param int $id The user_id or employee_id
     * @param string $userType Either 'user' (admin/IT staff) or 'employee'
     */
    public function getUnreadCount($id, $userType = 'user') {
        $whereClause = $userType === 'employee' ? 'employee_id = :id' : 'user_id = :id';
        
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table . " 
                  WHERE " . $whereClause . " AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Mark notification as read
     * @param int $notificationId The notification ID
     * @param int $id The user_id or employee_id
     * @param string $userType Either 'user' or 'employee'
     */
    public function markAsRead($notificationId, $id, $userType = 'user') {
        $whereClause = $userType === 'employee' 
            ? 'id = :id AND employee_id = :user_id' 
            : 'id = :id AND user_id = :user_id';
        
        $query = "UPDATE " . $this->table . " 
                  SET is_read = 1 
                  WHERE " . $whereClause;
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $notificationId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Mark all notifications as read for a user or employee
     * @param int $id The user_id or employee_id
     * @param string $userType Either 'user' or 'employee'
     */
    public function markAllAsRead($id, $userType = 'user') {
        $whereClause = $userType === 'employee' ? 'employee_id = :user_id' : 'user_id = :user_id';
        
        $query = "UPDATE " . $this->table . " 
                  SET is_read = 1 
                  WHERE " . $whereClause . " AND is_read = 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Delete a notification
     * @param int $notificationId The notification ID
     * @param int $id The user_id or employee_id
     * @param string $userType Either 'user' or 'employee'
     */
    public function delete($notificationId, $id, $userType = 'user') {
        $whereClause = $userType === 'employee' 
            ? 'id = :id AND employee_id = :user_id' 
            : 'id = :id AND user_id = :user_id';
        
        $query = "DELETE FROM " . $this->table . " 
                  WHERE " . $whereClause;
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $notificationId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $id, PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    /**
     * Get all notifications for a user (with pagination)
     */
    public function getAllByUser($userId, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $query = "SELECT n.*, 
                         t.title as ticket_title,
                         u.full_name as related_user_name
                  FROM " . $this->table . " n
                  LEFT JOIN tickets t ON n.ticket_id = t.id
                  LEFT JOIN users u ON n.related_user_id = u.id
                  WHERE n.user_id = :user_id
                  ORDER BY n.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get total count for pagination
     */
    public function getTotalCount($userId) {
        $query = "SELECT COUNT(*) as count 
                  FROM " . $this->table . " 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] ?? 0;
    }
    
    /**
     * Helper function to create notification when ticket is assigned
     */
    public static function notifyTicketAssigned($db, $ticketId, $assignedToUserId, $assignedByUserId) {
        $notification = new self($db);
        
        return $notification->create([
            'user_id' => $assignedToUserId,
            'type' => 'ticket_assigned',
            'title' => 'New Ticket Assigned',
            'message' => 'A new ticket has been assigned to you',
            'ticket_id' => $ticketId,
            'related_user_id' => $assignedByUserId
        ]);
    }
    
    /**
     * Helper function to create notification when ticket is updated
     */
    public static function notifyTicketUpdated($db, $ticketId, $userId, $updatedByUserId) {
        $notification = new self($db);
        
        return $notification->create([
            'user_id' => $userId,
            'type' => 'ticket_updated',
            'title' => 'Ticket Updated',
            'message' => 'Your ticket has been updated',
            'ticket_id' => $ticketId,
            'related_user_id' => $updatedByUserId
        ]);
    }
    
    /**
     * Helper function to create notification when comment is added
     */
    public static function notifyCommentAdded($db, $ticketId, $userId, $commentByUserId) {
        $notification = new self($db);
        
        return $notification->create([
            'user_id' => $userId,
            'type' => 'comment_added',
            'title' => 'New Comment',
            'message' => 'A new comment was added to your ticket',
            'ticket_id' => $ticketId,
            'related_user_id' => $commentByUserId
        ]);
    }
    
    /**
     * Helper function to create notification when ticket status changes
     */
    public static function notifyStatusChanged($db, $ticketId, $userId, $newStatus, $changedByUserId) {
        $notification = new self($db);
        
        $statusLabels = [
            'open' => 'Open',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'closed' => 'Closed'
        ];
        
        $statusLabel = $statusLabels[$newStatus] ?? ucfirst($newStatus);
        
        return $notification->create([
            'user_id' => $userId,
            'type' => 'status_changed',
            'title' => 'Status Changed',
            'message' => "Ticket status changed to: {$statusLabel}",
            'ticket_id' => $ticketId,
            'related_user_id' => $changedByUserId
        ]);
    }
    
    /**
     * Get notification icon based on type
     */
    public static function getIcon($type) {
        $icons = [
            'ticket_assigned' => 'fa-user-plus',
            'ticket_updated' => 'fa-edit',
            'ticket_resolved' => 'fa-check-circle',
            'ticket_created' => 'fa-plus-circle',
            'comment_added' => 'fa-comment',
            'status_changed' => 'fa-exchange-alt',
            'priority_changed' => 'fa-exclamation-circle'
        ];
        
        return $icons[$type] ?? 'fa-bell';
    }
    
    /**
     * Get notification color based on type
     */
    public static function getColor($type) {
        $colors = [
            'ticket_assigned' => 'blue',
            'ticket_updated' => 'yellow',
            'ticket_resolved' => 'green',
            'ticket_created' => 'purple',
            'comment_added' => 'indigo',
            'status_changed' => 'orange',
            'priority_changed' => 'red'
        ];
        
        return $colors[$type] ?? 'gray';
    }
}
