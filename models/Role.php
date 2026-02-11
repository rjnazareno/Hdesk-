<?php
/**
 * Role Model
 * Handles role-related database operations for RBAC system
 */

class Role {
    private $db;
    
    // Role hierarchy levels (higher = more permissions)
    const LEVEL_SUPER_ADMIN = 100;
    const LEVEL_DEPT_ADMIN = 50;
    const LEVEL_IT_STAFF = 30;
    const LEVEL_EMPLOYEE = 10;
    
    // Role slugs
    const SUPER_ADMIN = 'super_admin';
    const DEPT_ADMIN = 'dept_admin';
    const IT_STAFF = 'it_staff';
    const EMPLOYEE = 'employee';
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Get all roles
     * 
     * @param bool $activeOnly Only return active roles
     * @return array List of roles
     */
    public function getAll($activeOnly = true) {
        $sql = "SELECT * FROM roles";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY hierarchy_level DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get role by ID
     * 
     * @param int $id Role ID
     * @return array|false Role data or false
     */
    public function findById($id) {
        $sql = "SELECT * FROM roles WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get role by slug
     * 
     * @param string $slug Role slug (super_admin, dept_admin, etc.)
     * @return array|false Role data or false
     */
    public function findBySlug($slug) {
        $sql = "SELECT * FROM roles WHERE slug = :slug AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':slug' => $slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get roles available for assignment by a given role
     * Users can only assign roles at or below their hierarchy level
     * 
     * @param int $assignerHierarchyLevel The hierarchy level of the user assigning roles
     * @return array Roles that can be assigned
     */
    public function getAssignableRoles($assignerHierarchyLevel) {
        $sql = "SELECT * FROM roles 
                WHERE hierarchy_level < :level 
                AND is_active = 1
                ORDER BY hierarchy_level DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':level' => $assignerHierarchyLevel]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all permissions for a role
     * 
     * @param int $roleId Role ID
     * @return array List of permissions
     */
    public function getPermissions($roleId) {
        $sql = "SELECT p.* 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = :role_id
                ORDER BY p.category, p.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get permission slugs for a role (for quick checks)
     * 
     * @param int $roleId Role ID
     * @return array List of permission slugs
     */
    public function getPermissionSlugs($roleId) {
        $sql = "SELECT p.slug 
                FROM permissions p
                JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = :role_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role_id' => $roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Check if role has specific permission
     * 
     * @param int $roleId Role ID
     * @param string $permissionSlug Permission slug
     * @return bool True if role has permission
     */
    public function hasPermission($roleId, $permissionSlug) {
        $sql = "SELECT COUNT(*) 
                FROM role_permissions rp
                JOIN permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = :role_id AND p.slug = :slug";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':role_id' => $roleId,
            ':slug' => $permissionSlug
        ]);
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Create a new role
     * 
     * @param array $data Role data
     * @return int|false Insert ID or false
     */
    public function create($data) {
        $sql = "INSERT INTO roles (name, slug, description, hierarchy_level, is_system_role) 
                VALUES (:name, :slug, :description, :hierarchy_level, :is_system_role)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $this->generateSlug($data['name']),
            ':description' => $data['description'] ?? null,
            ':hierarchy_level' => $data['hierarchy_level'] ?? 20,
            ':is_system_role' => $data['is_system_role'] ?? 0
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    /**
     * Update role
     * 
     * @param int $id Role ID
     * @param array $data Role data
     * @return bool Success status
     */
    public function update($id, $data) {
        // Don't allow updating system roles hierarchy
        $role = $this->findById($id);
        if ($role && $role['is_system_role']) {
            unset($data['hierarchy_level']);
        }
        
        $fields = [];
        $params = [':id' => $id];
        $allowedFields = ['name', 'description', 'hierarchy_level', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE roles SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Assign permission to role
     * 
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool Success status
     */
    public function assignPermission($roleId, $permissionId) {
        $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id) 
                VALUES (:role_id, :permission_id)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':role_id' => $roleId,
            ':permission_id' => $permissionId
        ]);
    }
    
    /**
     * Remove permission from role
     * 
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool Success status
     */
    public function removePermission($roleId, $permissionId) {
        $sql = "DELETE FROM role_permissions 
                WHERE role_id = :role_id AND permission_id = :permission_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':role_id' => $roleId,
            ':permission_id' => $permissionId
        ]);
    }
    
    /**
     * Sync permissions for a role (replace all)
     * 
     * @param int $roleId Role ID
     * @param array $permissionIds Array of permission IDs
     * @return bool Success status
     */
    public function syncPermissions($roleId, $permissionIds) {
        try {
            $this->db->beginTransaction();
            
            // Remove all existing permissions
            $deleteSql = "DELETE FROM role_permissions WHERE role_id = :role_id";
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute([':role_id' => $roleId]);
            
            // Add new permissions
            if (!empty($permissionIds)) {
                $insertSql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
                $insertStmt = $this->db->prepare($insertSql);
                
                foreach ($permissionIds as $permissionId) {
                    $insertStmt->execute([
                        ':role_id' => $roleId,
                        ':permission_id' => $permissionId
                    ]);
                }
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("Role syncPermissions error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get users count by role
     * 
     * @return array Role ID => user count
     */
    public function getUserCountByRole() {
        $sql = "SELECT r.id, r.name, r.slug, COUNT(u.id) as user_count
                FROM roles r
                LEFT JOIN users u ON r.id = u.role_id AND u.is_active = 1
                GROUP BY r.id
                ORDER BY r.hierarchy_level DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate URL-friendly slug from name
     * 
     * @param string $name Role name
     * @return string Slug
     */
    private function generateSlug($name) {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '_', $slug);
        $slug = preg_replace('/_+/', '_', $slug);
        return $slug;
    }
    
    /**
     * Check if role can be deleted
     * 
     * @param int $roleId Role ID
     * @return array ['can_delete' => bool, 'reason' => string]
     */
    public function canDelete($roleId) {
        $role = $this->findById($roleId);
        
        if (!$role) {
            return ['can_delete' => false, 'reason' => 'Role not found'];
        }
        
        if ($role['is_system_role']) {
            return ['can_delete' => false, 'reason' => 'System roles cannot be deleted'];
        }
        
        // Check if any users have this role
        $sql = "SELECT COUNT(*) FROM users WHERE role_id = :role_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':role_id' => $roleId]);
        $userCount = $stmt->fetchColumn();
        
        if ($userCount > 0) {
            return ['can_delete' => false, 'reason' => "Role is assigned to $userCount user(s)"];
        }
        
        return ['can_delete' => true, 'reason' => ''];
    }
}
