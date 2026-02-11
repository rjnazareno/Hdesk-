<?php
/**
 * Ticket Model
 * Handles all ticket-related database operations
 * Supports multi-department routing and ticket bucket model
 */

class Ticket {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new ticket
     * 
     * @param array $data Ticket data including department_id
     * @return int|false Ticket ID or false on failure
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO tickets (ticket_number, title, description, category_id, department_id, priority, status, submitter_id, submitter_type, assigned_to, attachments) 
                    VALUES (:ticket_number, :title, :description, :category_id, :department_id, :priority, :status, :submitter_id, :submitter_type, :assigned_to, :attachments)";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':ticket_number' => $data['ticket_number'],
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':category_id' => $data['category_id'],
                ':department_id' => $data['department_id'] ?? null,
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
            
            // MySQL bug/config issue: lastInsertId() sometimes returns 0 or empty string
            // Always fallback to querying by ticket_number which is unique
            if (!$insertId || $insertId == 0 || $insertId === '0') {
                error_log("lastInsertId returned invalid value ($insertId), using fallback query");
                // Find the ticket by ticket_number as fallback (ticket_number is unique)
                $checkSql = "SELECT id FROM tickets WHERE ticket_number = :ticket_number ORDER BY id DESC LIMIT 1";
                $checkStmt = $this->db->prepare($checkSql);
                $checkStmt->execute([':ticket_number' => $data['ticket_number']]);
                $found = $checkStmt->fetch();
                
                if ($found) {
                    error_log("Found ticket via fallback query: ID = " . $found['id']);
                    return (int)$found['id'];
                }
                
                error_log("Fallback query also failed to find ticket");
                return false;
            }
            
            return (int)$insertId;
        } catch (PDOException $e) {
            error_log("Ticket creation error: " . $e->getMessage());
            error_log("Data: " . print_r($data, true));
            return false;
        }
    }
    
    /**
     * Grab a ticket from the queue (Bucket Model)
     * Assigns the ticket to the staff member who grabbed it
     * 
     * @param int $ticketId Ticket ID
     * @param int $userId User ID grabbing the ticket
     * @return bool Success status
     */
    public function grabTicket($ticketId, $userId, $assigneeType = 'user') {
        try {
            // Check if ticket is available to grab
            $checkSql = "SELECT id, status, grabbed_by FROM tickets WHERE id = :id";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([':id' => $ticketId]);
            $ticket = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) {
                return false;
            }
            
            // Can only grab pending/open tickets that aren't already grabbed
            if (!in_array($ticket['status'], ['pending', 'open']) || $ticket['grabbed_by'] !== null) {
                return false;
            }
            
            $sql = "UPDATE tickets 
                    SET grabbed_by = :user_id, 
                        grabbed_at = NOW(),
                        assigned_to = :user_id,
                        assignee_type = :assignee_type,
                        status = CASE WHEN status = 'pending' THEN 'open' ELSE status END
                    WHERE id = :id 
                    AND grabbed_by IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':id' => $ticketId,
                ':user_id' => $userId,
                ':assignee_type' => $assigneeType
            ]);
            
            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Grab ticket error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Release a grabbed ticket back to the queue
     * 
     * @param int $ticketId Ticket ID
     * @param int $userId User ID releasing the ticket (must be the one who grabbed it)
     * @return bool Success status
     */
    public function releaseTicket($ticketId, $userId) {
        try {
            $sql = "UPDATE tickets 
                    SET grabbed_by = NULL, 
                        grabbed_at = NULL,
                        assigned_to = NULL,
                        status = 'pending'
                    WHERE id = :id 
                    AND grabbed_by = :user_id
                    AND status IN ('pending', 'open')";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':id' => $ticketId,
                ':user_id' => $userId
            ]);
            
            return $result && $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            error_log("Release ticket error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get ticket queue for a department (unassigned tickets)
     * 
     * @param int $departmentId Department ID
     * @param array $filters Optional filters
     * @return array Tickets in queue
     */
    public function getQueue($departmentId, $filters = []) {
        $sql = "SELECT t.*, 
                c.name as category_name, c.color as category_color,
                d.name as department_name, d.code as department_code,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                    ELSE u1.full_name
                END as submitter_name,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN e.email
                    ELSE u1.email
                END as submitter_email,
                st.resolution_due_at,
                CASE 
                    WHEN NOW() > st.resolution_due_at THEN 'breached'
                    WHEN TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) <= 60 THEN 'at_risk'
                    ELSE 'safe'
                END as sla_status,
                TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) as minutes_remaining
                FROM tickets t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN departments d ON t.department_id = d.id
                LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
                LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
                LEFT JOIN sla_tracking st ON t.id = st.ticket_id
                WHERE t.department_id = :department_id
                AND t.status IN ('pending', 'open')
                AND t.grabbed_by IS NULL";
        
        $params = [':department_id' => $departmentId];
        
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        $sql .= " ORDER BY FIELD(t.priority, 'high', 'medium', 'low'), st.resolution_due_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get tickets grabbed by a user
     * 
     * @param int $userId User ID
     * @param array $filters Optional filters
     * @return array Grabbed tickets
     */
    public function getMyTickets($userId, $filters = []) {
        $sql = "SELECT t.*, 
                c.name as category_name, c.color as category_color,
                d.name as department_name, d.code as department_code,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                    ELSE u1.full_name
                END as submitter_name,
                st.resolution_due_at,
                CASE 
                    WHEN t.status IN ('resolved', 'closed') THEN 'completed'
                    WHEN NOW() > st.resolution_due_at THEN 'breached'
                    WHEN TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) <= 60 THEN 'at_risk'
                    ELSE 'safe'
                END as sla_status,
                TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) as minutes_remaining
                FROM tickets t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN departments d ON t.department_id = d.id
                LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
                LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
                LEFT JOIN sla_tracking st ON t.id = st.ticket_id
                WHERE t.grabbed_by = :user_id";
        
        $params = [':user_id' => $userId];
        
        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params[':status'] = $filters['status'];
        } else {
            $sql .= " AND t.status NOT IN ('closed')";
        }
        
        $sql .= " ORDER BY FIELD(t.priority, 'high', 'medium', 'low'), t.grabbed_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get ticket by ID with related data
     */
    public function findById($id) {
        $sql = "SELECT t.*, 
                c.name as category_name, c.color as category_color,
                d.name as department_name, d.code as department_code,
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
                CASE 
                    WHEN t.assignee_type = 'employee' THEN CONCAT(ea.fname, ' ', ea.lname)
                    ELSE u2.full_name
                END as assigned_name,
                CASE 
                    WHEN t.assignee_type = 'employee' THEN ea.email
                    ELSE u2.email
                END as assigned_email,
                CASE 
                    WHEN t.assignee_type = 'employee' THEN CONCAT(eg.fname, ' ', eg.lname)
                    ELSE u3.full_name
                END as grabbed_by_name
                FROM tickets t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN departments d ON t.department_id = d.id
                LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
                LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
                LEFT JOIN users u2 ON t.assigned_to = u2.id AND t.assignee_type = 'user'
                LEFT JOIN employees ea ON t.assigned_to = ea.id AND t.assignee_type = 'employee'
                LEFT JOIN users u3 ON t.grabbed_by = u3.id AND t.assignee_type = 'user'
                LEFT JOIN employees eg ON t.grabbed_by = eg.id AND t.assignee_type = 'employee'
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
                d.name as department_name, d.code as department_code,
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
                CASE 
                    WHEN t.assignee_type = 'employee' THEN CONCAT(ea.fname, ' ', ea.lname)
                    ELSE u2.full_name
                END as assigned_name,
                CASE 
                    WHEN t.assignee_type = 'employee' THEN ea.email
                    ELSE u2.email
                END as assigned_email
                FROM tickets t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN departments d ON t.department_id = d.id
                LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
                LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
                LEFT JOIN users u2 ON t.assigned_to = u2.id AND t.assignee_type = 'user'
                LEFT JOIN employees ea ON t.assigned_to = ea.id AND t.assignee_type = 'employee'
                WHERE t.ticket_number = :ticket_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ticket_number' => $ticketNumber]);
        return $stmt->fetch();
    }
    
    /**
     * Get all tickets with filters
     */
    public function getAll($filters = [], $sortBy = 'created_at', $sortDir = 'DESC', $limit = null, $offset = null) {
        $sql = "SELECT t.*, 
                c.name as category_name, c.color as category_color,
                d.name as department_name, d.code as department_code,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                    ELSE u1.full_name
                END as submitter_name,
                CASE 
                    WHEN t.assignee_type = 'employee' THEN CONCAT(ea.fname, ' ', ea.lname)
                    ELSE u2.full_name
                END as assigned_name,
                CASE 
                    WHEN t.assignee_type = 'employee' THEN CONCAT(eg.fname, ' ', eg.lname)
                    ELSE u3.full_name
                END as grabbed_by_name,
                st.response_due_at, st.resolution_due_at,
                st.response_sla_status, st.resolution_sla_status,
                CASE 
                    WHEN t.status IN ('resolved', 'closed') THEN 
                        CASE WHEN st.resolution_sla_status = 'met' THEN 'met' ELSE 'breached' END
                    WHEN NOW() > st.resolution_due_at THEN 'breached'
                    WHEN TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) <= 60 THEN 'at_risk'
                    ELSE 'safe'
                END as sla_display_status,
                TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) as minutes_remaining
                FROM tickets t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN departments d ON t.department_id = d.id
                LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
                LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
                LEFT JOIN users u2 ON t.assigned_to = u2.id AND (t.assignee_type = 'user' OR t.assignee_type IS NULL)
                LEFT JOIN employees ea ON t.assigned_to = ea.id AND t.assignee_type = 'employee'
                LEFT JOIN users u3 ON t.grabbed_by = u3.id AND (t.assignee_type = 'user' OR t.assignee_type IS NULL)
                LEFT JOIN employees eg ON t.grabbed_by = eg.id AND t.assignee_type = 'employee'
                LEFT JOIN sla_tracking st ON t.id = st.ticket_id
                WHERE 1=1";
        
        $params = [];
        
        // Handle unassigned filter (for queue view)
        if (!empty($filters['unassigned'])) {
            $sql .= " AND t.grabbed_by IS NULL";
        }
        
        if (!empty($filters['status'])) {
            // Support comma-separated status values
            if (strpos($filters['status'], ',') !== false) {
                $statuses = explode(',', $filters['status']);
                $statusPlaceholders = [];
                foreach ($statuses as $i => $status) {
                    $statusPlaceholders[] = ":status$i";
                    $params[":status$i"] = trim($status);
                }
                $sql .= " AND t.status IN (" . implode(',', $statusPlaceholders) . ")";
            } else {
                $sql .= " AND t.status = :status";
                $params[':status'] = $filters['status'];
            }
        }
        
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (!empty($filters['department_id'])) {
            $sql .= " AND t.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (!empty($filters['submitter_id'])) {
            $sql .= " AND t.submitter_id = :submitter_id";
            $params[':submitter_id'] = $filters['submitter_id'];
        }
        
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
            
            // If assignee_type is also specified, filter by it
            if (!empty($filters['assignee_type'])) {
                $sql .= " AND t.assignee_type = :assignee_type";
                $params[':assignee_type'] = $filters['assignee_type'];
            }
        }
        
        if (!empty($filters['grabbed_by'])) {
            $sql .= " AND t.grabbed_by = :grabbed_by";
            $params[':grabbed_by'] = $filters['grabbed_by'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (t.ticket_number LIKE :search1 OR t.title LIKE :search2 OR t.description LIKE :search3)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[':search1'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
        }
        
        // Date range filters
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(t.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(t.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        // SLA status filter (for pool filtering)
        if (!empty($filters['sla_status'])) {
            if ($filters['sla_status'] === 'breached') {
                $sql .= " AND NOW() > st.resolution_due_at";
            } elseif ($filters['sla_status'] === 'at_risk') {
                $sql .= " AND TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) <= 60 AND TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) > 0";
            } elseif ($filters['sla_status'] === 'safe') {
                $sql .= " AND TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) > 60";
            }
        }
        
        // Add sorting
        $allowedSortFields = ['created_at', 'priority', 'status', 'ticket_number', 'updated_at', 'department_id'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sortDir, ['ASC', 'DESC'])) {
            $sortDir = 'DESC';
        }
        $sql .= " ORDER BY t.$sortBy $sortDir";
        
        // Add pagination
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            if ($offset !== null) {
                $sql .= " OFFSET :offset";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        
        // Bind all parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Bind pagination parameters with explicit type
        if ($limit !== null) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            if ($offset !== null) {
                $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            }
        }
        
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("SQL Error in Ticket::getAll(): " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . print_r($params, true));
            throw $e;
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get total count of tickets matching filters
     */
    public function getTotalCount($filters = []) {
        $sql = "SELECT COUNT(*) as total
                FROM tickets t
                WHERE 1=1";
        
        $params = [];
        
        // Handle unassigned filter (for queue view)
        if (!empty($filters['unassigned'])) {
            $sql .= " AND t.grabbed_by IS NULL";
        }
        
        if (isset($filters['status']) && !empty($filters['status'])) {
            // Support comma-separated status values
            if (strpos($filters['status'], ',') !== false) {
                $statuses = explode(',', $filters['status']);
                $statusPlaceholders = [];
                foreach ($statuses as $i => $status) {
                    $statusPlaceholders[] = ":status$i";
                    $params[":status$i"] = trim($status);
                }
                $sql .= " AND t.status IN (" . implode(',', $statusPlaceholders) . ")";
            } else {
                $sql .= " AND t.status = :status";
                $params[':status'] = $filters['status'];
            }
        }
        
        if (isset($filters['priority']) && !empty($filters['priority'])) {
            $sql .= " AND t.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }
        
        if (isset($filters['category_id']) && !empty($filters['category_id'])) {
            $sql .= " AND t.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        if (isset($filters['department_id']) && !empty($filters['department_id'])) {
            $sql .= " AND t.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (isset($filters['submitter_id']) && !empty($filters['submitter_id'])) {
            $sql .= " AND t.submitter_id = :submitter_id";
            $params[':submitter_id'] = $filters['submitter_id'];
        }
        
        if (isset($filters['assigned_to']) && !empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
        }
        
        if (isset($filters['grabbed_by']) && !empty($filters['grabbed_by'])) {
            $sql .= " AND t.grabbed_by = :grabbed_by";
            $params[':grabbed_by'] = $filters['grabbed_by'];
        }
        
        if (isset($filters['search']) && !empty($filters['search'])) {
            $sql .= " AND (t.ticket_number LIKE :search1 OR t.title LIKE :search2 OR t.description LIKE :search3)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[':search1'] = $searchTerm;
            $params[':search2'] = $searchTerm;
            $params[':search3'] = $searchTerm;
        }
        
        // Date range filters
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(t.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(t.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return (int)$result['total'];
    }
    
    /**
     * Update ticket
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['title', 'description', 'category_id', 'priority', 'admin_priority', 'status', 'assigned_to', 'assignee_type', 'grabbed_by', 'resolution', 'attachments'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        // If assigning and grabbed_by not set, also set grabbed_by
        if (isset($data['assigned_to']) && !isset($data['grabbed_by'])) {
            $fields[] = "grabbed_by = :grabbed_by";
            $params[':grabbed_by'] = $data['assigned_to'];
            $fields[] = "grabbed_at = NOW()";
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
                SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high,
                SUM(CASE WHEN priority = 'medium' THEN 1 ELSE 0 END) as medium
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
