<?php
/**
 * TicketActivity Model
 * Handles all ticket activity logging operations
 */

class TicketActivity {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Log ticket activity
     */
    public function log($data) {
        $sql = "INSERT INTO ticket_activity (ticket_id, user_id, user_type, action_type, old_value, new_value, comment) 
                VALUES (:ticket_id, :user_id, :user_type, :action_type, :old_value, :new_value, :comment)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':ticket_id' => $data['ticket_id'],
            ':user_id' => $data['user_id'],
            ':user_type' => $data['user_type'] ?? $_SESSION['user_type'] ?? 'employee',
            ':action_type' => $data['action_type'],
            ':old_value' => $data['old_value'] ?? null,
            ':new_value' => $data['new_value'] ?? null,
            ':comment' => $data['comment'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Get activity by ticket ID
     */
    public function getByTicketId($ticketId) {
        $sql = "SELECT ta.*, 
                COALESCE(u.full_name, CONCAT(e.fname, ' ', e.lname)) as user_name,
                COALESCE(u.role, 'employee') as user_role
                FROM ticket_activity ta
                LEFT JOIN users u ON ta.user_id = u.id AND ta.user_type = 'user'
                LEFT JOIN employees e ON ta.user_id = e.id AND ta.user_type = 'employee'
                WHERE ta.ticket_id = :ticket_id
                ORDER BY ta.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ticket_id' => $ticketId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get recent activity across all tickets
     */
    public function getRecent($limit = 10, $userId = null, $userRole = null) {
        $sql = "SELECT ta.*, 
                t.ticket_number, t.title as ticket_title,
                COALESCE(u.full_name, CONCAT(e.fname, ' ', e.lname)) as user_name,
                COALESCE(u.role, 'employee') as user_role
                FROM ticket_activity ta
                LEFT JOIN tickets t ON ta.ticket_id = t.id
                LEFT JOIN users u ON ta.user_id = u.id AND ta.user_type = 'user'
                LEFT JOIN employees e ON ta.user_id = e.id AND ta.user_type = 'employee'
                WHERE 1=1";
        
        $params = [':limit' => $limit];
        
        // If employee, only show activity for their tickets
        if ($userId && $userRole === 'employee') {
            $sql .= " AND t.submitter_id = :user_id";
            $params[':user_id'] = $userId;
        }
        
        $sql .= " ORDER BY ta.created_at DESC LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            if ($key === ':limit') {
                $stmt->bindValue($key, (int)$value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, $value);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get activity statistics
     */
    public function getStats($days = 7) {
        $sql = "SELECT 
                COUNT(*) as total_activities,
                COUNT(DISTINCT ticket_id) as tickets_affected,
                COUNT(DISTINCT user_id) as users_involved
                FROM ticket_activity
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $days]);
        return $stmt->fetch();
    }
    
    /**
     * Delete activity by ticket ID
     */
    public function deleteByTicketId($ticketId) {
        $sql = "DELETE FROM ticket_activity WHERE ticket_id = :ticket_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':ticket_id' => $ticketId]);
    }
}
