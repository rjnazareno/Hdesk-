<?php
/**
 * User Model
 * Handles all user-related database operations
 */

class User {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new user
     */
    public function create($data) {
        $sql = "INSERT INTO users (username, email, password, full_name, role, department, phone) 
                VALUES (:username, :email, :password, :full_name, :role, :department, :phone)";
        
        $stmt = $this->db->prepare($sql);
        
        $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':full_name' => $data['full_name'],
            ':role' => $data['role'] ?? 'employee',
            ':department' => $data['department'] ?? null,
            ':phone' => $data['phone'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Find user by ID
     */
    public function findById($id, $activeOnly = false) {
        $sql = "SELECT * FROM users WHERE id = :id";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = :username AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }
    
    /**
     * Verify user login
     */
    public function verifyLogin($username, $password) {
        $user = $this->findByUsername($username);
        
        if (!$user) {
            $user = $this->findByEmail($username);
        }
        
        if ($user && $password === $user['password']) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Get all users (IT staff/admin only)
     */
    public function getAll($role = null) {
        $sql = "SELECT id, username, email, full_name, role, department, phone, is_active, created_at 
                FROM users WHERE 1=1";
        
        if ($role) {
            $sql .= " AND role = :role";
        }
        
        $sql .= " ORDER BY full_name ASC";
        
        $stmt = $this->db->prepare($sql);
        
        if ($role) {
            $stmt->execute([':role' => $role]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get IT staff members
     */
    public function getITStaff() {
        $sql = "SELECT id, username, email, full_name, department, phone 
                FROM users 
                WHERE (role = 'it_staff' OR role = 'admin') AND is_active = 1
                ORDER BY full_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get all admins and IT staff (alias for getITStaff)
     */
    public function getAllAdmins() {
        return $this->getITStaff();
    }
    
    /**
     * Update user
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['username', 'email', 'full_name', 'role', 'department', 'phone', 'is_active', 'password'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete user (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE users SET is_active = 0 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Deactivate user with audit trail
     * 
     * @param int $id User ID to deactivate
     * @param int $deactivatedBy User ID who performed deactivation
     * @return bool Success status
     */
    public function deactivate($id, $deactivatedBy) {
        $sql = "UPDATE users 
                SET is_active = 0, 
                    deactivated_at = NOW(), 
                    deactivated_by = :deactivated_by 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':deactivated_by' => $deactivatedBy
        ]);
    }
    
    /**
     * Reactivate user
     * 
     * @param int $id User ID to reactivate
     * @return bool Success status
     */
    public function reactivate($id) {
        $sql = "UPDATE users 
                SET is_active = 1, 
                    deactivated_at = NULL, 
                    deactivated_by = NULL 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get user statistics (IT staff/admin only)
     */
    public function getStats() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN role = 'it_staff' THEN 1 ELSE 0 END) as it_staff,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active
                FROM users";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    // =====================================================
    // RBAC-ENHANCED METHODS
    // =====================================================
    
    /**
     * Get user with role details
     * 
     * @param int $id User ID
     * @return array|false User with role data
     */
    public function findByIdWithRole($id) {
        $sql = "SELECT u.*, 
                r.name as role_name, r.slug as role_slug, r.hierarchy_level,
                GROUP_CONCAT(DISTINCT d.id) as department_ids,
                GROUP_CONCAT(DISTINCT d.name ORDER BY ud.is_primary DESC) as department_names
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN user_departments ud ON u.id = ud.user_id
                LEFT JOIN departments d ON ud.department_id = d.id
                WHERE u.id = :id
                GROUP BY u.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all users with role and department info
     * 
     * @param array $filters Filter options (role_id, department_id, is_active)
     * @return array List of users
     */
    public function getAllWithRoles($filters = []) {
        $sql = "SELECT u.id, u.username, u.email, u.full_name, u.phone, 
                u.is_active, u.created_at, u.last_login_at,
                r.name as role_name, r.slug as role_slug, r.hierarchy_level,
                GROUP_CONCAT(DISTINCT d.name ORDER BY ud.is_primary DESC SEPARATOR ', ') as departments
                FROM users u
                LEFT JOIN roles r ON u.role_id = r.id
                LEFT JOIN user_departments ud ON u.id = ud.user_id
                LEFT JOIN departments d ON ud.department_id = d.id
                WHERE 1=1";
        
        $params = [];
        
        if (isset($filters['role_id'])) {
            $sql .= " AND u.role_id = :role_id";
            $params[':role_id'] = $filters['role_id'];
        }
        
        if (isset($filters['role_slug'])) {
            $sql .= " AND r.slug = :role_slug";
            $params[':role_slug'] = $filters['role_slug'];
        }
        
        if (isset($filters['department_id'])) {
            $sql .= " AND ud.department_id = :department_id";
            $params[':department_id'] = $filters['department_id'];
        }
        
        if (isset($filters['is_active'])) {
            $sql .= " AND u.is_active = :is_active";
            $params[':is_active'] = $filters['is_active'];
        }
        
        if (isset($filters['hierarchy_max'])) {
            $sql .= " AND r.hierarchy_level < :hierarchy_max";
            $params[':hierarchy_max'] = $filters['hierarchy_max'];
        }
        
        $sql .= " GROUP BY u.id ORDER BY r.hierarchy_level DESC, u.full_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get staff members for a department (for ticket assignment)
     * 
     * @param int $departmentId Department ID
     * @param bool $includeAdmins Include department admins
     * @return array List of staff
     */
    public function getDepartmentStaff($departmentId, $includeAdmins = true) {
        $sql = "SELECT u.id, u.username, u.full_name, u.email,
                r.name as role_name, r.slug as role_slug
                FROM users u
                JOIN roles r ON u.role_id = r.id
                JOIN user_departments ud ON u.id = ud.user_id
                WHERE ud.department_id = :department_id
                AND u.is_active = 1
                AND r.hierarchy_level >= :min_level
                ORDER BY r.hierarchy_level DESC, u.full_name ASC";
        
        $minLevel = $includeAdmins ? 30 : 30; // IT Staff level minimum
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':department_id' => $departmentId,
            ':min_level' => $minLevel
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get department admins
     * 
     * @param int|null $departmentId Filter by department (null = all dept admins)
     * @return array List of department admins
     */
    public function getDepartmentAdmins($departmentId = null) {
        $sql = "SELECT u.id, u.username, u.full_name, u.email,
                GROUP_CONCAT(DISTINCT d.name SEPARATOR ', ') as departments
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN user_departments ud ON u.id = ud.user_id
                LEFT JOIN departments d ON ud.department_id = d.id
                WHERE r.slug = 'dept_admin'
                AND u.is_active = 1";
        
        $params = [];
        
        if ($departmentId !== null) {
            $sql .= " AND ud.department_id = :department_id";
            $params[':department_id'] = $departmentId;
        }
        
        $sql .= " GROUP BY u.id ORDER BY u.full_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Assign user to role
     * 
     * @param int $userId User ID
     * @param int $roleId Role ID
     * @return bool Success status
     */
    public function assignRole($userId, $roleId) {
        $sql = "UPDATE users SET role_id = :role_id WHERE id = :user_id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':role_id' => $roleId
        ]);
    }
    
    /**
     * Assign user to department
     * 
     * @param int $userId User ID
     * @param int $departmentId Department ID
     * @param bool $isPrimary Is this the primary department
     * @param int|null $assignedBy User who made the assignment
     * @return bool Success status
     */
    public function assignDepartment($userId, $departmentId, $isPrimary = true, $assignedBy = null) {
        $sql = "INSERT INTO user_departments (user_id, department_id, is_primary, assigned_by)
                VALUES (:user_id, :department_id, :is_primary, :assigned_by)
                ON DUPLICATE KEY UPDATE 
                    is_primary = :is_primary,
                    assigned_by = :assigned_by,
                    assigned_at = NOW()";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':department_id' => $departmentId,
            ':is_primary' => $isPrimary ? 1 : 0,
            ':assigned_by' => $assignedBy
        ]);
    }
    
    /**
     * Remove user from department
     * 
     * @param int $userId User ID
     * @param int $departmentId Department ID
     * @return bool Success status
     */
    public function removeDepartment($userId, $departmentId) {
        $sql = "DELETE FROM user_departments 
                WHERE user_id = :user_id AND department_id = :department_id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':user_id' => $userId,
            ':department_id' => $departmentId
        ]);
    }
    
    /**
     * Sync user departments (replace all)
     * 
     * @param int $userId User ID
     * @param array $departmentIds Array of department IDs
     * @param int|null $assignedBy User who made the assignment
     * @return bool Success status
     */
    public function syncDepartments($userId, $departmentIds, $assignedBy = null) {
        try {
            $this->db->beginTransaction();
            
            // Remove all existing department assignments
            $deleteSql = "DELETE FROM user_departments WHERE user_id = :user_id";
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute([':user_id' => $userId]);
            
            // Add new department assignments
            if (!empty($departmentIds)) {
                $insertSql = "INSERT INTO user_departments (user_id, department_id, is_primary, assigned_by) 
                              VALUES (:user_id, :department_id, :is_primary, :assigned_by)";
                $insertStmt = $this->db->prepare($insertSql);
                
                $isFirst = true;
                foreach ($departmentIds as $departmentId) {
                    $insertStmt->execute([
                        ':user_id' => $userId,
                        ':department_id' => $departmentId,
                        ':is_primary' => $isFirst ? 1 : 0,
                        ':assigned_by' => $assignedBy
                    ]);
                    $isFirst = false;
                }
            }
            
            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            error_log("User syncDepartments error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user's departments
     * 
     * @param int $userId User ID
     * @return array List of departments
     */
    public function getUserDepartments($userId) {
        $sql = "SELECT d.*, ud.is_primary
                FROM departments d
                JOIN user_departments ud ON d.id = ud.department_id
                WHERE ud.user_id = :user_id
                ORDER BY ud.is_primary DESC, d.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Record login timestamp
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function recordLogin($userId) {
        $sql = "UPDATE users SET last_login_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $userId]);
    }
    
    /**
     * Create user with RBAC (role_id instead of role string)
     * 
     * @param array $data User data including role_id
     * @param int|null $createdBy User ID who created this user
     * @return int|false Insert ID or false
     */
    public function createWithRole($data, $createdBy = null) {
        $sql = "INSERT INTO users (username, email, password, full_name, role, role_id, department, phone, created_by) 
                VALUES (:username, :email, :password, :full_name, :role, :role_id, :department, :phone, :created_by)";
        
        $stmt = $this->db->prepare($sql);
        
        // Map role_id to legacy role string for backward compatibility
        $legacyRole = 'employee';
        if (isset($data['role_id'])) {
            $roleMap = [
                1 => 'admin',      // super_admin maps to admin
                2 => 'it_staff',   // dept_admin maps to it_staff
                3 => 'it_staff',   // it_staff
                4 => 'employee'    // employee
            ];
            $legacyRole = $roleMap[$data['role_id']] ?? 'employee';
        }
        
        $result = $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':full_name' => $data['full_name'],
            ':role' => $data['role'] ?? $legacyRole,
            ':role_id' => $data['role_id'] ?? null,
            ':department' => $data['department'] ?? null,
            ':phone' => $data['phone'] ?? null,
            ':created_by' => $createdBy
        ]);
        
        return $result ? $this->db->lastInsertId() : false;
    }
}
