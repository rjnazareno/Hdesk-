<?php
/**
 * Authentication Handler
 * Handles user login, logout, and session management
 */

require_once __DIR__ . '/../config/config.php';

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
                // IT staff or admin
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['department'] = $user['department'] ?? '';
            } else {
                // Employee
                $employeeModel = new Employee();
                $_SESSION['full_name'] = $employeeModel->getFullName($user);
                $_SESSION['email'] = $user['email'] ?? $user['personal_email'] ?? '';
                $_SESSION['role'] = 'employee';
                $_SESSION['department'] = $user['company'] ?? '';
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Verify login credentials
     * Checks both users and employees tables
     */
    private function verifyLogin($username, $password) {
        // Try users table first (IT staff/admin)
        $user = $this->userModel->findByUsername($username);
        
        if ($user && $user['is_active'] && password_verify($password, $user['password'])) {
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
            redirect('dashboard.php');
        }
    }
    
    /**
     * Require IT staff or admin
     */
    public function requireITStaff() {
        $this->requireLogin();
        
        if ($_SESSION['role'] !== 'it_staff' && $_SESSION['role'] !== 'admin') {
            redirect('dashboard.php');
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
            'department' => $_SESSION['department']
        ];
    }
}
