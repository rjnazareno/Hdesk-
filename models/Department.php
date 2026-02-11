<?php
/**
 * Department Model
 * Handles all department-related database operations
 * Supports multi-department service desk routing
 */

class Department {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all active departments
     * 
     * @return array List of active departments
     */
    public function getAll() {
        $sql = "SELECT * FROM departments WHERE is_active = 1 ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get department by ID
     * 
     * @param int $id Department ID
     * @return array|false Department data or false
     */
    public function findById($id) {
        $sql = "SELECT * FROM departments WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get department by code (IT, HR, etc.)
     * 
     * @param string $code Department code
     * @return array|false Department data or false
     */
    public function findByCode($code) {
        $sql = "SELECT * FROM departments WHERE code = :code AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':code' => strtoupper($code)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new department
     * 
     * @param array $data Department data
     * @return int|false Insert ID or false
     */
    public function create($data) {
        $sql = "INSERT INTO departments (name, code, description, icon, color) 
                VALUES (:name, :code, :description, :icon, :color)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':name' => $data['name'],
            ':code' => strtoupper($data['code']),
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? 'building',
            ':color' => $data['color'] ?? '#3B82F6'
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    /**
     * Update department
     * 
     * @param int $id Department ID
     * @param array $data Department data
     * @return bool Success status
     */
    public function update($id, $data) {
        $sql = "UPDATE departments 
                SET name = :name, code = :code, description = :description, 
                    icon = :icon, color = :color
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':name' => $data['name'],
            ':code' => strtoupper($data['code']),
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? 'building',
            ':color' => $data['color'] ?? '#3B82F6'
        ]);
    }
    
    /**
     * Soft delete department
     * 
     * @param int $id Department ID
     * @return bool Success status
     */
    public function delete($id) {
        $sql = "UPDATE departments SET is_active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get department statistics
     * 
     * @return array Department stats with ticket counts
     */
    public function getStats() {
        $sql = "SELECT 
                d.id,
                d.name,
                d.code,
                d.color,
                d.icon,
                COUNT(t.id) as total_tickets,
                SUM(CASE WHEN t.status IN ('pending', 'open', 'in_progress') THEN 1 ELSE 0 END) as active_tickets,
                SUM(CASE WHEN t.status = 'pending' AND t.grabbed_by IS NULL THEN 1 ELSE 0 END) as queue_count
                FROM departments d
                LEFT JOIN tickets t ON d.id = t.department_id
                WHERE d.is_active = 1
                GROUP BY d.id, d.name, d.code, d.color, d.icon
                ORDER BY d.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get ticket queue for a department
     * Tickets that are pending/open and not grabbed by anyone
     * 
     * @param int $departmentId Department ID
     * @param array $filters Optional filters
     * @return array Tickets in queue
     */
    public function getTicketQueue($departmentId, $filters = []) {
        $sql = "SELECT 
                t.id, t.ticket_number, t.title, t.description, t.priority, t.status, 
                t.created_at, t.updated_at,
                c.name as category_name, c.color as category_color,
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
                LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
                LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
                LEFT JOIN sla_tracking st ON t.id = st.ticket_id
                WHERE t.department_id = :department_id
                AND t.status IN ('pending', 'open')
                AND t.grabbed_by IS NULL";
        
        $params = [':department_id' => $departmentId];
        
        // Apply filters
        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }
        
        if (!empty($filters['category_id'])) {
            $sql .= " AND t.category_id = :category_id";
            $params[':category_id'] = $filters['category_id'];
        }
        
        // Order by priority urgency and SLA risk
        $sql .= " ORDER BY 
                  FIELD(t.priority, 'high', 'medium', 'low'),
                  st.resolution_due_at ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get tickets grabbed by a specific user in a department
     * 
     * @param int $departmentId Department ID
     * @param int $userId User ID who grabbed tickets
     * @return array Grabbed tickets
     */
    public function getGrabbedTickets($departmentId, $userId) {
        $sql = "SELECT 
                t.id, t.ticket_number, t.title, t.description, t.priority, t.status, 
                t.created_at, t.updated_at, t.grabbed_at,
                c.name as category_name, c.color as category_color,
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
                LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
                LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
                LEFT JOIN sla_tracking st ON t.id = st.ticket_id
                WHERE t.department_id = :department_id
                AND t.grabbed_by = :user_id
                AND t.status NOT IN ('closed')
                ORDER BY 
                  FIELD(t.priority, 'high', 'medium', 'low'),
                  t.grabbed_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id' => $departmentId,
            ':user_id' => $userId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get staff members assigned to a department
     * 
     * @param int $departmentId Department ID
     * @return array Staff members
     */
    public function getStaff($departmentId) {
        $sql = "SELECT id, username, email, full_name, role, profile_picture
                FROM users 
                WHERE department_id = :department_id 
                AND is_active = 1
                AND role IN ('it_staff', 'admin', 'hr_staff')
                ORDER BY full_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get categories for a department
     * 
     * @param int $departmentId Department ID
     * @param bool $includeSubCategories Whether to include sub-categories
     * @return array Categories
     */
    public function getCategories($departmentId, $includeSubCategories = true) {
        $sql = "SELECT c.*, 
                pc.name as parent_name
                FROM categories c
                LEFT JOIN categories pc ON c.parent_id = pc.id
                WHERE c.department_id = :department_id
                AND c.is_active = 1";
        
        if (!$includeSubCategories) {
            $sql .= " AND c.parent_id IS NULL";
        }
        
        $sql .= " ORDER BY c.sort_order ASC, c.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get hierarchical categories for a department (grouped by parent)
     * 
     * @param int $departmentId Department ID
     * @return array Hierarchical categories
     */
    public function getCategoriesHierarchy($departmentId) {
        $categories = $this->getCategories($departmentId, true);
        
        $hierarchy = [];
        $children = [];
        
        // First pass: separate parents and children
        foreach ($categories as $category) {
            if ($category['parent_id'] === null) {
                $hierarchy[$category['id']] = $category;
                $hierarchy[$category['id']]['children'] = [];
            } else {
                $children[] = $category;
            }
        }
        
        // Second pass: assign children to parents
        foreach ($children as $child) {
            if (isset($hierarchy[$child['parent_id']])) {
                $hierarchy[$child['parent_id']]['children'][] = $child;
            }
        }
        
        return array_values($hierarchy);
    }
}
