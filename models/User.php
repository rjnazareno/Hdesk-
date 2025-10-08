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
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $stmt->execute([
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => $hashedPassword,
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
    public function findById($id) {
        $sql = "SELECT * FROM users WHERE id = :id AND is_active = 1";
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
        
        if ($user && password_verify($password, $user['password'])) {
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
     * Update user
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['email', 'full_name', 'role', 'department', 'phone', 'is_active'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                $params[":$field"] = $data[$field];
            }
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
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
}
