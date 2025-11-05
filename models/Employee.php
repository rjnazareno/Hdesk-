<?php
/**
 * Employee Model
 * Handles all employee-related database operations
 */

class Employee {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new employee
     */
    public function create($data) {
        $sql = "INSERT INTO employees (employee_id, username, email, personal_email, password, fname, lname, company, position, contact, official_sched, role, status, profile_picture) 
                VALUES (:employee_id, :username, :email, :personal_email, :password, :fname, :lname, :company, :position, :contact, :official_sched, :role, :status, :profile_picture)";
        
        $stmt = $this->db->prepare($sql);
        
        // Hash password if provided, otherwise use the already hashed password
        $password = isset($data['password']) ? 
                    (password_get_info($data['password'])['algo'] === null ? password_hash($data['password'], PASSWORD_DEFAULT) : $data['password']) 
                    : password_hash('Welcome123!', PASSWORD_DEFAULT);
        
        $stmt->execute([
            ':employee_id' => $data['employee_id'] ?? null,
            ':username' => $data['username'] ?? null,
            ':email' => $data['email'] ?? null,
            ':personal_email' => $data['personal_email'] ?? null,
            ':password' => $password,
            ':fname' => $data['fname'] ?? null,
            ':lname' => $data['lname'] ?? null,
            ':company' => $data['company'] ?? null,
            ':position' => $data['position'] ?? null,
            ':contact' => $data['contact'] ?? $data['phone'] ?? null,
            ':official_sched' => $data['official_sched'] ?? null,
            ':role' => $data['role'] ?? 'employee',
            ':status' => $data['status'] ?? 'active',
            ':profile_picture' => $data['profile_picture'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Find employee by ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM employees WHERE id = :id AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
    
    /**
     * Find employee by username
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM employees WHERE username = :username AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':username' => $username]);
        return $stmt->fetch();
    }
    
    /**
     * Find employee by email
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM employees WHERE (email = :email OR personal_email = :personal_email) AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':email' => $email,
            ':personal_email' => $email
        ]);
        return $stmt->fetch();
    }
    
    /**
     * Find employee by employee_id (external ID from other system)
     */
    public function findByEmployeeId($employeeId) {
        $sql = "SELECT * FROM employees WHERE employee_id = :employee_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':employee_id' => $employeeId]);
        return $stmt->fetch();
    }
    
    /**
     * Verify employee login
     */
    public function verifyLogin($username, $password) {
        $employee = $this->findByUsername($username);
        
        if (!$employee) {
            $employee = $this->findByEmail($username);
        }
        
        if ($employee && password_verify($password, $employee['password'])) {
            return $employee;
        }
        
        return false;
    }
    
    /**
     * Get all employees
     */
    public function getAll($status = null) {
        $sql = "SELECT id, username, email, personal_email, fname, lname, company, position, contact, role, status, profile_picture, created_at 
                FROM employees WHERE 1=1";
        
        if ($status) {
            $sql .= " AND status = :status";
        }
        
        $sql .= " ORDER BY fname ASC, lname ASC";
        
        $stmt = $this->db->prepare($sql);
        
        if ($status) {
            $stmt->execute([':status' => $status]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Update employee
     */
    public function update($id, $data) {
        $fields = [];
        $params = [':id' => $id];
        
        $allowedFields = ['employee_id', 'username', 'email', 'personal_email', 'fname', 'lname', 'company', 'position', 'contact', 'official_sched', 'role', 'status', 'profile_picture'];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $fields[] = "$field = :$field";
                // Map 'phone' to 'contact' if needed
                $params[":$field"] = ($field === 'contact' && isset($data['phone'])) ? $data['phone'] : $data[$field];
            }
        }
        
        // Handle phone mapping for contact
        if (isset($data['phone']) && !isset($data['contact'])) {
            $fields[] = "contact = :contact";
            $params[':contact'] = $data['phone'];
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = "password = :password";
            // Check if already hashed
            $params[':password'] = password_get_info($data['password'])['algo'] === null ? 
                                   password_hash($data['password'], PASSWORD_DEFAULT) : 
                                   $data['password'];
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE employees SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete employee (soft delete - set status to terminated)
     */
    public function delete($id) {
        $sql = "UPDATE employees SET status = 'terminated' WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
    
    /**
     * Get employee statistics
     */
    public function getStats() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN status = 'terminated' THEN 1 ELSE 0 END) as `terminated`,
                SUM(CASE WHEN role = 'employee' THEN 1 ELSE 0 END) as employees,
                SUM(CASE WHEN role = 'manager' THEN 1 ELSE 0 END) as managers,
                SUM(CASE WHEN role = 'supervisor' THEN 1 ELSE 0 END) as supervisors
                FROM employees";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }
    
    /**
     * Get full name of employee
     */
    public function getFullName($employee) {
        if (is_array($employee)) {
            return trim(($employee['fname'] ?? '') . ' ' . ($employee['lname'] ?? ''));
        }
        return '';
    }
}
