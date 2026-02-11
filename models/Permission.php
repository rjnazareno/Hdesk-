<?php
/**
 * Permission Model
 * Handles permission-related database operations for RBAC system
 */

class Permission {
    private $db;
    
    // Permission categories
    const CATEGORY_TICKETS = 'tickets';
    const CATEGORY_USERS = 'users';
    const CATEGORY_DEPARTMENTS = 'departments';
    const CATEGORY_REPORTS = 'reports';
    const CATEGORY_SETTINGS = 'settings';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all permissions
     * 
     * @param string|null $category Filter by category
     * @return array List of permissions
     */
    public function getAll($category = null) {
        $sql = "SELECT * FROM permissions";
        $params = [];
        
        if ($category !== null) {
            $sql .= " WHERE category = :category";
            $params[':category'] = $category;
        }
        
        $sql .= " ORDER BY category, name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get permissions grouped by category
     * 
     * @return array Permissions grouped by category
     */
    public function getAllGrouped() {
        $permissions = $this->getAll();
        $grouped = [];
        
        foreach ($permissions as $permission) {
            $category = $permission['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }
        
        return $grouped;
    }
    
    /**
     * Get permission by ID
     * 
     * @param int $id Permission ID
     * @return array|false Permission data or false
     */
    public function findById($id) {
        $sql = "SELECT * FROM permissions WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get permission by slug
     * 
     * @param string $slug Permission slug
     * @return array|false Permission data or false
     */
    public function findBySlug($slug) {
        $sql = "SELECT * FROM permissions WHERE slug = :slug";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all categories
     * 
     * @return array List of unique categories
     */
    public function getCategories() {
        $sql = "SELECT DISTINCT category FROM permissions ORDER BY category";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Check if a user has a specific permission
     * 
     * @param int $userId User ID
     * @param string $permissionSlug Permission slug
     * @return bool True if user has permission
     */
    public function userHasPermission($userId, $permissionSlug) {
        $sql = "SELECT COUNT(*) 
                FROM users u
                JOIN roles r ON u.role_id = r.id
                JOIN role_permissions rp ON r.id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE u.id = :user_id 
                AND p.slug = :permission_slug
                AND u.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':permission_slug' => $permissionSlug
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get all permissions for a user
     * 
     * @param int $userId User ID
     * @return array List of permission slugs
     */
    public function getUserPermissions($userId) {
        $sql = "SELECT DISTINCT p.slug 
                FROM users u
                JOIN roles r ON u.role_id = r.id
                JOIN role_permissions rp ON r.id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE u.id = :user_id 
                AND u.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get user permissions grouped by category
     * 
     * @param int $userId User ID
     * @return array Permissions grouped by category
     */
    public function getUserPermissionsGrouped($userId) {
        $sql = "SELECT DISTINCT p.* 
                FROM users u
                JOIN roles r ON u.role_id = r.id
                JOIN role_permissions rp ON r.id = rp.role_id
                JOIN permissions p ON rp.permission_id = p.id
                WHERE u.id = :user_id 
                AND u.is_active = 1
                ORDER BY p.category, p.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $grouped = [];
        foreach ($permissions as $permission) {
            $category = $permission['category'];
            if (!isset($grouped[$category])) {
                $grouped[$category] = [];
            }
            $grouped[$category][] = $permission;
        }
        
        return $grouped;
    }
    
    /**
     * Create a new permission
     * 
     * @param array $data Permission data
     * @return int|false Insert ID or false
     */
    public function create($data) {
        $sql = "INSERT INTO permissions (name, slug, description, category) 
                VALUES (:name, :slug, :description, :category)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':description' => $data['description'] ?? null,
            ':category' => $data['category'] ?? 'general'
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    /**
     * Update permission
     * 
     * @param int $id Permission ID
     * @param array $data Permission data
     * @return bool Success status
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        $allowedFields = ['name', 'description', 'category'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE permissions SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete permission
     * 
     * @param int $id Permission ID
     * @return bool Success status
     */
    public function delete($id) {
        $sql = "DELETE FROM permissions WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get roles that have a specific permission
     * 
     * @param int $permissionId Permission ID
     * @return array List of roles
     */
    public function getRolesWithPermission($permissionId) {
        $sql = "SELECT r.* 
                FROM roles r
                JOIN role_permissions rp ON r.id = rp.role_id
                WHERE rp.permission_id = :permission_id
                ORDER BY r.hierarchy_level DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':permission_id' => $permissionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
