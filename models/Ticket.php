<?php
/**
 * Ticket Model
 * Handles all ticket-related database operations
 */

class Ticket {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new ticket
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO tickets (ticket_number, title, description, category_id, priority, status, submitter_id, submitter_type, assigned_to, attachments) 
                    VALUES (:ticket_number, :title, :description, :category_id, :priority, :status, :submitter_id, :submitter_type, :assigned_to, :attachments)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':ticket_number' => $data['ticket_number'],
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':category_id' => $data['category_id'],
                ':priority' => $data['priority'] ?? 'medium',
                ':status' => $data['status'] ?? 'pending',
                ':submitter_id' => $data['submitter_id'],
                ':submitter_type' => $data['submitter_type'] ?? 'employee',
                ':assigned_to' => $data['assigned_to'] ?? null,
                ':attachments' => $data['attachments'] ?? null
            ]);
            
            if (!$result) {
                error_log("Ticket creation failed - execute returned false");
                error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            
            $insertId = $this->db->lastInsertId();
            error_log("Ticket insert successful - lastInsertId: " . $insertId);
            error_log("Row count affected: " . $stmt->rowCount());
            
            if (!$insertId || $insertId == 0) {
                error_log("WARNING: lastInsertId returned 0 or false!");
                error_log("Checking if ticket was actually inserted...");
                
                // Try to find the ticket by ticket_number as fallback
                $checkSql = "SELECT id FROM tickets WHERE ticket_number = :ticket_number ORDER BY id DESC LIMIT 1";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([':ticket_number' => $data['ticket_number']]);
                $found = $checkStmt->fetch();
                
                if ($found) {
                    error_log("Found ticket by ticket_number! ID: " . $found['id']);
                    return $found['id'];
                }
            }
            
            return $insertId;
        } catch (PDOException $e) {
            error_log("Ticket creation error: " . $e->getMessage());
            error_log("Data: " . print_r($data, true));
            error_log("SQL State: " . $e->getCode());
            return false;
        }
    }
    
    /**
     * Get ticket by ID with related data
     */
    public function findById($id) {
        $sql = "SELECT t.*, 
                c.name as category_name, c.color as category_color,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                    ELSE u1.full_name
                END as submitter_name,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN e.email
                    ELSE u1.email
                END as submitter_email,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN e.position
                    ELSE u1.department
                END as submitter_department,
                u2.full_name as assigned_name, u2.email as assigned_email
                FROM tickets t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
                LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
                LEFT JOIN users u2 ON t.assigned_to = u2.id
                WHERE t.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Get ticket by ticket number
     */
    public function findByTicketNumber($ticketNumber) {
        $sql = "SELECT t.*, 
                c.name as category_name, c.color as category_color,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                    ELSE u1.full_name
                END as submitter_name,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN e.email
                    ELSE u1.email
                END as submitter_email,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN e.position
                    ELSE u1.department
                END as submitter_department,
                u2.full_name as assigned_name, u2.email as assigned_email
                FROM tickets t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
                LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
                LEFT JOIN users u2 ON t.assigned_to = u2.id
                WHERE t.ticket_number = :ticket_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ticket_number' => $ticketNumber]);
        return $stmt->fetch();
    }
    
    /**
     * Get all tickets with filters
     */
    public function getAll($filters = []) {
        $sql = "SELECT t.*, 
                c.name as category_name, c.color as category_color,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                    ELSE u1.full_name
                END as submitter_name,
                u2.full_name as assigned_name,
                st.response_due_at, st.resolution_due_at,
                st.response_sla_status, st.resolution_sla_status,
                st.is_paused,
                CASE 
                    WHEN st.is_paused = 1 THEN 'paused'
                    WHEN t.status IN ('resolved', 'closed') THEN 
                        CASE WHEN st.resolution_sla_status = 'met' THEN 'met' ELSE 'breached' END
                    WHEN NOW() > st.resolution_due_at THEN 'breached'
                    WHEN TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) <= 60 THEN 'at_risk'
                    ELSE 'safe'
                END as sla_display_status,
                TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) as minutes_remaining
                FROM tickets t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
                LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
                LEFT JOIN users u2 ON t.assigned_to = u2.id
                LEFT JOIN sla_tracking st ON t.id = st.ticket_id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['priority']) && !empty($filters['priority'])) {
            $sql .= " AND t.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }
        
        if (isset($filters['category_id']) && !empty($filters['category_id'])) {
            $sql .= " AND t.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (isset($filters['submitter_id']) && !empty($filters['submitter_id'])) {
            $sql .= " AND t.submitter_id = :submitter_id";
            $params[':submitter_id'] = $filters['submitter_id'];
        }
        
        if (isset($filters['assigned_to']) && !empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $sql .= " AND (t.ticket_number LIKE :search OR t.title LIKE :search OR t.description LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        if (isset($filters['limit'])) {
            $sql .= " LIMIT :limit";
            if (isset($filters['offset'])) {
                $sql .= " OFFSET :offset";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        
        // Bind limit and offset separately as integers
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if (isset($filters['limit'])) {
            $stmt->bindValue(':limit', (int)$filters['limit'], PDO::PARAM_INT);
            if (isset($filters['offset'])) {
                $stmt->bindValue(':offset', (int)$filters['offset'], PDO::PARAM_INT);
            }
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Update ticket
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['title', 'description', 'category_id', 'priority', 'status', 'assigned_to', 'resolution', 'attachments'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        // Set resolved_at timestamp when status changes to resolved
        if (isset($data['status']) && $data['status'] === 'resolved') {
            $fields[] = "resolved_at = NOW()";
        }
        
        // Set closed_at timestamp when status changes to closed
        if (isset($data['status']) && $data['status'] === 'closed') {
            $fields[] = "closed_at = NOW()";
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE tickets SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete ticket
     */
    public function delete($id) {
        $sql = "DELETE FROM tickets WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get ticket statistics
     */
    public function getStats($userId = null, $userRole = null) {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent,
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high
                FROM tickets WHERE 1=1";
        
        $params = [];
        
        if ($userId && $userRole === 'employee') {
            $sql .= " AND submitter_id = :user_id";
            $params[':user_id'] = $userId;
        } elseif ($userId && ($userRole === 'it_staff' || $userRole === 'admin')) {
            // IT staff can see all tickets, so no filter needed
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get daily ticket counts for chart
     */
    public function getDailyStats($days = 7) {
        $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as count
                FROM tickets
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':days' => $days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get tickets by status for chart
     */
    public function getStatusBreakdown() {
        $sql = "SELECT 
                status,
                COUNT(*) as count,
                ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM tickets)), 2) as percentage
                FROM tickets
                GROUP BY status
                ORDER BY count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Count total tickets with filters
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM tickets WHERE 1=1";
        $params = [];
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (isset($filters['submitter_id']) && !empty($filters['submitter_id'])) {
            $sql .= " AND submitter_id = :submitter_id";
            $params[':submitter_id'] = $filters['submitter_id'];
        }
        
        if (isset($filters['assigned_to']) && !empty($filters['assigned_to'])) {
            $sql .= " AND assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Get today's ticket statistics
     */
    public function getTodayStats() {
        $sql = "SELECT 
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'closed' AND DATE(updated_at) = CURDATE() THEN 1 ELSE 0 END) as closed,
                SUM(CASE WHEN status IN ('open', 'pending', 'in_progress') THEN 1 ELSE 0 END) as open
                FROM tickets";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
}
