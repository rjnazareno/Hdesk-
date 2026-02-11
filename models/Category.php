<?php
/**
 * Category Model
 * Handles all category-related database operations
 * Supports department-based categorization with parent/child hierarchy
 */

class Category {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all active categories
     * 
     * @param int|null $departmentId Filter by department (optional)
     * @return array List of categories
     */
    public function getAll($departmentId = null) {
        $sql = "SELECT c.*, d.name as department_name, d.code as department_code,
                pc.name as parent_name
                FROM categories c
                LEFT JOIN departments d ON c.department_id = d.id
                LEFT JOIN categories pc ON c.parent_id = pc.id
                WHERE c.is_active = 1";
        
        $params = [];
        
        if ($departmentId !== null) {
            $sql .= " AND c.department_id = :department_id";
            $params[':department_id'] = $departmentId;
        }
        
        $sql .= " ORDER BY c.sort_order ASC, c.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get top-level categories (no parent) for a department
     * 
     * @param int $departmentId Department ID
     * @return array Parent categories only
     */
    public function getParentCategories($departmentId) {
        $sql = "SELECT * FROM categories 
                WHERE department_id = :department_id 
                AND parent_id IS NULL 
                AND is_active = 1
                ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':department_id' => $departmentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get sub-categories for a parent category
     * 
     * @param int $parentId Parent category ID
     * @return array Sub-categories
     */
    public function getSubCategories($parentId) {
        $sql = "SELECT * FROM categories 
                WHERE parent_id = :parent_id 
                AND is_active = 1
                ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':parent_id' => $parentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get categories by department with hierarchy
     * 
     * @param int $departmentId Department ID
     * @return array Hierarchical categories
     */
    public function getByDepartmentHierarchy($departmentId) {
        $categories = $this->getAll($departmentId);
        
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
    
    /**
     * Get category by ID
     * 
     * @param int $id Category ID
     * @return array|false Category data
     */
    public function findById($id) {
        $sql = "SELECT c.*, d.name as department_name, d.code as department_code
                FROM categories c
                LEFT JOIN departments d ON c.department_id = d.id
                WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new category
     * 
     * @param array $data Category data
     * @return int|false Insert ID
     */
    public function create($data) {
        $sql = "INSERT INTO categories (department_id, parent_id, name, description, icon, color, sort_order, requires_fields) 
                VALUES (:department_id, :parent_id, :name, :description, :icon, :color, :sort_order, :requires_fields)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':department_id' => $data['department_id'] ?? null,
            ':parent_id' => $data['parent_id'] ?? null,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? 'folder',
            ':color' => $data['color'] ?? '#3B82F6',
            ':sort_order' => $data['sort_order'] ?? 0,
            ':requires_fields' => isset($data['requires_fields']) ? json_encode($data['requires_fields']) : null
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    /**
     * Update category
     * 
     * @param int $id Category ID
     * @param array $data Category data
     * @return bool Success status
     */
    public function update($id, $data) {
        $sql = "UPDATE categories 
                SET department_id = :department_id, parent_id = :parent_id, 
                    name = :name, description = :description, 
                    icon = :icon, color = :color, sort_order = :sort_order,
                    requires_fields = :requires_fields
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':department_id' => $data['department_id'] ?? null,
            ':parent_id' => $data['parent_id'] ?? null,
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':icon' => $data['icon'] ?? 'folder',
            ':color' => $data['color'] ?? '#3B82F6',
            ':sort_order' => $data['sort_order'] ?? 0,
            ':requires_fields' => isset($data['requires_fields']) ? json_encode($data['requires_fields']) : null
        ]);
    }
    
    /**
     * Delete category (soft delete)
     * 
     * @param int $id Category ID
     * @return bool Success status
     */
    public function delete($id) {
        $sql = "UPDATE categories SET is_active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get category statistics
     * 
     * @param int|null $departmentId Filter by department
     * @return array Category stats
     */
    public function getStats($departmentId = null) {
        $sql = "SELECT 
                c.id,
                c.name,
                c.color,
                c.icon,
                c.department_id,
                d.name as department_name,
                COUNT(t.id) as ticket_count,
                SUM(CASE WHEN t.status IN ('open', 'in_progress', 'pending') THEN 1 ELSE 0 END) as active_tickets,
                SUM(CASE WHEN t.status IN ('open', 'in_progress', 'pending') THEN 1 ELSE 0 END) as open_tickets,
                SUM(CASE WHEN t.status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as closed_tickets
                FROM categories c
                LEFT JOIN departments d ON c.department_id = d.id
                LEFT JOIN tickets t ON c.id = t.category_id
                WHERE c.is_active = 1";
        
        $params = [];
        
        if ($departmentId !== null) {
            $sql .= " AND c.department_id = :department_id";
            $params[':department_id'] = $departmentId;
        }
        
        $sql .= " GROUP BY c.id, c.name, c.color, c.icon, c.department_id, d.name
                  ORDER BY ticket_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get categories with ticket counts per department
     * 
     * @return array Category stats grouped by department
     */
    public function getStatsByDepartment() {
        $sql = "SELECT 
                d.id as department_id,
                d.name as department_name,
                d.code as department_code,
                c.id as category_id,
                c.name as category_name,
                c.color,
                c.icon,
                COUNT(t.id) as ticket_count
                FROM departments d
                LEFT JOIN categories c ON d.id = c.department_id AND c.is_active = 1
                LEFT JOIN tickets t ON c.id = t.category_id
                WHERE d.is_active = 1
                GROUP BY d.id, d.name, d.code, c.id, c.name, c.color, c.icon
                ORDER BY d.name, c.sort_order, c.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group by department
        $grouped = [];
        foreach ($results as $row) {
            $deptId = $row['department_id'];
            if (!isset($grouped[$deptId])) {
                $grouped[$deptId] = [
                    'department_id' => $deptId,
                    'department_name' => $row['department_name'],
                    'department_code' => $row['department_code'],
                    'categories' => []
                ];
            }
            if ($row['category_id']) {
                $grouped[$deptId]['categories'][] = [
                    'id' => $row['category_id'],
                    'name' => $row['category_name'],
                    'color' => $row['color'],
                    'icon' => $row['icon'],
                    'ticket_count' => $row['ticket_count']
                ];
            }
        }
        
        return array_values($grouped);
    }
}
