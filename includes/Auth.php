<?php
/**
 * Authentication Handler
 * Handles user login, logout, and session management
 * Integrates with RBAC for role-based permissions
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Employee.php';

class Auth {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Process login
     */
    public function login($username, $password) {
        $result = $this->verifyLogin($username, $password);
        
        if ($result) {
            $user = $result['user'];
            $type = $result['type'];
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $type;
            $_SESSION['username'] = $user['username'] ?? '';
            $_SESSION['logged_in'] = true;
            $_SESSION['last_activity'] = time();
            
            if ($type === 'user') {
                // IT staff or admin (users table)
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['role_id'] = $user['role_id'] ?? null;
                $_SESSION['department'] = $user['department'] ?? '';
                $_SESSION['admin_rights'] = null; // Not from employees table
                
                // Load RBAC permissions for users table accounts
                $this->loadRBACPermissions($user['id']);
                
                // Record login timestamp
                $this->userModel->recordLogin($user['id']);
            } else {
                // Employee - check for admin rights
                $employeeModel = new Employee();
                $_SESSION['full_name'] = $employeeModel->getFullName($user);
                $_SESSION['email'] = $user['email'] ?? $user['personal_email'] ?? '';
                $_SESSION['role'] = $user['role'] ?? 'employee';
                $_SESSION['department'] = $user['company'] ?? '';
                $_SESSION['admin_rights'] = $user['admin_rights_hdesk'] ?? null;
                
                // Check if employee has admin rights AND internal role
                $hasAdminAccess = ($user['role'] === 'internal') && !empty($user['admin_rights_hdesk']);
                
                if ($hasAdminAccess) {
                    // Employee with admin rights - set appropriate permissions
                    $adminRights = $user['admin_rights_hdesk'];
                    
                    if ($adminRights === 'superadmin') {
                        $_SESSION['rbac_role'] = [
                            'id' => 1,
                            'slug' => 'super_admin',
                            'name' => 'Super Admin',
                            'hierarchy_level' => 100
                        ];
                        $_SESSION['role_id'] = 1;
                        $_SESSION['rbac_permissions'] = $this->getAllPermissions();
                    } elseif ($adminRights === 'it') {
                        $_SESSION['rbac_role'] = [
                            'id' => 2,
                            'slug' => 'dept_admin',
                            'name' => 'IT Admin',
                            'hierarchy_level' => 50
                        ];
                        $_SESSION['role_id'] = 2;
                        $_SESSION['rbac_permissions'] = $this->getDeptAdminPermissions();
                    } elseif ($adminRights === 'hr') {
                        $_SESSION['rbac_role'] = [
                            'id' => 2,
                            'slug' => 'dept_admin',
                            'name' => 'HR Admin',
                            'hierarchy_level' => 50
                        ];
                        $_SESSION['role_id'] = 2;
                        $_SESSION['rbac_permissions'] = $this->getDeptAdminPermissions();
                    }
                    
                    $_SESSION['is_employee_admin'] = true;
                } else {
                    // Regular employee - basic permissions
                    $_SESSION['rbac_role'] = [
                        'id' => 4,
                        'slug' => 'employee',
                        'name' => 'Employee',
                        'hierarchy_level' => 10
                    ];
                    $_SESSION['rbac_permissions'] = ['tickets.view_own', 'tickets.create', 'tickets.comment'];
                    $_SESSION['is_employee_admin'] = false;
                }
                
                $_SESSION['rbac_departments'] = [];
                $_SESSION['rbac_loaded'] = true;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get all permissions for super admin
     */
    private function getAllPermissions() {
        return [
            'tickets.view', 'tickets.view_own', 'tickets.view_department', 'tickets.create',
            'tickets.update', 'tickets.delete', 'tickets.assign', 'tickets.comment',
            'tickets.close', 'tickets.reopen', 'tickets.export',
            'users.view', 'users.create', 'users.update', 'users.delete', 'users.assign_role',
            'departments.view', 'departments.create', 'departments.update', 'departments.delete', 'departments.assign_staff',
            'reports.view', 'reports.export', 'reports.sla',
            'settings.view', 'settings.update', 'settings.sla', 'settings.categories', 'settings.notifications'
        ];
    }
    
    /**
     * Get department admin permissions
     */
    private function getDeptAdminPermissions() {
        return [
            'tickets.view', 'tickets.view_own', 'tickets.view_department', 'tickets.create',
            'tickets.update', 'tickets.assign', 'tickets.comment', 'tickets.close', 'tickets.reopen',
            'reports.view', 'reports.sla'
        ];
    }
    
    /**
     * Load RBAC permissions into session
     * 
     * @param int $userId User ID from users table
     */
    private function loadRBACPermissions($userId) {
        // Check if RBAC tables exist (graceful degradation)
        try {
            require_once __DIR__ . '/RBAC.php';
            $rbac = RBAC::getInstance();
            $rbac->loadUserPermissions($userId);
            $rbac->loadUserDepartments($userId);
        } catch (Exception $e) {
            // RBAC tables may not exist yet - use legacy role system
            error_log("RBAC loading failed (tables may not exist): " . $e->getMessage());
            $this->loadLegacyPermissions();
        }
    }
    
    /**
     * Load legacy permissions based on role string (fallback)
     */
    private function loadLegacyPermissions() {
        $role = $_SESSION['role'] ?? 'employee';
        
        $legacyPermissions = [
            'admin' => [
                'tickets.view_all', 'tickets.create', 'tickets.assign', 'tickets.reassign',
                'tickets.update_status', 'tickets.update_priority', 'tickets.delete', 
                'tickets.override', 'tickets.comment', 'tickets.view_history',
                'users.view_all', 'users.create', 'users.create_admin', 'users.edit',
                'users.deactivate', 'users.assign_role', 'users.assign_department',
                'departments.view_all', 'departments.create', 'departments.edit',
                'reports.view_all', 'reports.export', 'reports.analytics',
                'settings.system', 'settings.sla', 'settings.roles'
            ],
            'it_staff' => [
                'tickets.view_department', 'tickets.view_own', 'tickets.create',
                'tickets.update_status', 'tickets.grab', 'tickets.release',
                'tickets.comment', 'tickets.view_history'
            ],
            'employee' => [
                'tickets.view_own', 'tickets.create', 'tickets.comment'
            ]
        ];
        
        $_SESSION['rbac_permissions'] = $legacyPermissions[$role] ?? $legacyPermissions['employee'];
        $_SESSION['rbac_role'] = [
            'slug' => $role === 'admin' ? 'super_admin' : ($role === 'it_staff' ? 'it_staff' : 'employee'),
            'hierarchy_level' => $role === 'admin' ? 100 : ($role === 'it_staff' ? 30 : 10)
        ];
        $_SESSION['rbac_loaded'] = true;
    }
    
    /**
     * Verify login credentials
     * Checks both users and employees tables
     */
    private function verifyLogin($username, $password) {
        // Try users table first (IT staff/admin)
        $user = $this->userModel->findByUsername($username);
        
        if ($user && $user['is_active'] && $password === $user['password']) {
            return [
                'user' => $user,
                'type' => 'user'
            ];
        }
        
        // Try employees table
        require_once __DIR__ . '/../models/Employee.php';
        $employeeModel = new Employee();
        $employee = $employeeModel->verifyLogin($username, $password);
        
        if ($employee) {
            return [
                'user' => $employee,
                'type' => 'employee'
            ];
        }
        
        return false;
    }
    
    /**
     * Process logout
     */
    public function logout() {
        // Unset all session variables
        $_SESSION = array();
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
        
        return true;
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Check if session is valid
     */
    public function checkSession() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        // Check for session timeout (30 minutes of inactivity)
        $timeout = 1800; // 30 minutes in seconds
        
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $timeout)) {
            $this->logout();
            return false;
        }
        
        // Update last activity timestamp
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Require login
     */
    public function requireLogin() {
        if (!$this->checkSession()) {
            redirect('login.php');
        }
    }
    
    /**
     * Require specific role
     */
    public function requireRole($role) {
        $this->requireLogin();
        
        if ($_SESSION['role'] !== $role) {
            // Redirect to appropriate dashboard
            if ($_SESSION['user_type'] === 'employee') {
                redirect('customer/dashboard.php');
            } else {
                redirect('admin/dashboard.php');
            }
        }
    }
    
    /**
     * Require IT staff or admin (including internal employees with admin rights)
     * Admin access requires: role='internal' AND admin_rights_hdesk IS NOT NULL
     */
    public function requireITStaff() {
        $this->requireLogin();
        
        $role = $_SESSION['role'] ?? '';
        $adminRights = $_SESSION['admin_rights'] ?? null;
        
        // Allow: it_staff, admin users
        if (in_array($role, ['it_staff', 'admin'])) {
            return; // Allowed
        }
        
        // For internal employees, must also have admin_rights_hdesk set
        if ($role === 'internal' && !empty($adminRights)) {
            return; // Allowed - internal with admin rights
        }
        
        // Redirect unauthorized users to customer dashboard
        redirect('customer/dashboard.php');
    }
    
    /**
     * Require admin or internal employee with admin rights (for admin dashboard access)
     * Admin access requires: role='internal' AND admin_rights_hdesk IS NOT NULL
     */
    public function requireAdminOrInternal() {
        $this->requireLogin();
        
        $role = $_SESSION['role'] ?? '';
        $adminRights = $_SESSION['admin_rights'] ?? null;
        
        // Allow: admin users from users table
        if ($role === 'admin') {
            return; // Allowed
        }
        
        // For internal employees, must also have admin_rights_hdesk set
        if ($role === 'internal' && !empty($adminRights)) {
            return; // Allowed - internal with admin rights
        }
        
        // Redirect IT staff to their dashboard, others to customer dashboard
        if ($role === 'it_staff') {
            redirect('admin/it_dashboard.php');
        } else {
            redirect('customer/dashboard.php');
        }
    }
    
    /**
     * Get current user ID
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public function getUserRole() {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * Check if current user is admin
     */
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    /**
     * Require admin role (for sensitive operations)
     */
    public function requireAdmin() {
        $this->requireLogin();
        
        if (!$this->isAdmin()) {
            $_SESSION['error'] = "Access denied. Admin privileges required.";
            redirect('admin/dashboard.php');
        }
    }
    
    /**
     * Get current user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email'],
            'role' => $_SESSION['role'],
            'role_id' => $_SESSION['role_id'] ?? null,
            'department' => $_SESSION['department'],
            'admin_rights' => $_SESSION['admin_rights'] ?? null,
            'is_employee_admin' => $_SESSION['is_employee_admin'] ?? false
        ];
    }
    
    /**
     * Check if current user is an employee with admin access
     * Requires: role='internal' AND admin_rights_hdesk IS NOT NULL
     */
    public function isEmployeeAdmin() {
        // Must be logged in as employee
        if (($_SESSION['user_type'] ?? '') !== 'employee') {
            return false;
        }
        // Must have role='internal' AND admin_rights set
        $role = $_SESSION['role'] ?? '';
        $adminRights = $_SESSION['admin_rights'] ?? null;
        
        return ($role === 'internal') && !empty($adminRights);
    }
    
    /**
     * Check if current user is super admin (either users table or employee)
     */
    public function isSuperAdmin() {
        // Users table admin
        if (($_SESSION['user_type'] ?? '') === 'user' && ($_SESSION['role'] ?? '') === 'admin') {
            return true;
        }
        // Employee with superadmin rights AND internal role
        if (($_SESSION['user_type'] ?? '') === 'employee') {
            $role = $_SESSION['role'] ?? '';
            $adminRights = $_SESSION['admin_rights'] ?? null;
            return ($role === 'internal') && ($adminRights === 'superadmin');
        }
        return false;
    }
    
    /**
     * Check if user has specific admin rights (it, hr, superadmin)
     * Only valid for employees with role='internal'
     */
    public function hasAdminRights($type = null) {
        // Must be an employee with internal role
        if (($_SESSION['user_type'] ?? '') !== 'employee') {
            return false;
        }
        
        $role = $_SESSION['role'] ?? '';
        $rights = $_SESSION['admin_rights'] ?? null;
        
        // Must be internal role
        if ($role !== 'internal') {
            return false;
        }
        
        if ($type === null) {
            // Check if has any admin rights
            return !empty($rights);
        }
        
        // Check for specific type or superadmin (superadmin has all rights)
        return $rights === $type || $rights === 'superadmin';
    }
}
