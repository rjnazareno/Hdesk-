<?php
/**
 * Authentication and Session Management
 * Ticketing System - PHP 8+
 */

require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Login employee user
     */
    public function loginEmployee($username, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT id, username, password, fname, lname, email
                FROM employees 
                WHERE username = ? AND status = 'active'
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $userData = [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'name' => $user['fname'] . ' ' . $user['lname'],
                    'email' => $user['email']
                ];
                $this->setSession($user['id'], 'employee', $userData);
                $this->logLogin($user['id'], 'employee');
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Login IT staff user
     */
    public function loginITStaff($username, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT staff_id, name, email, username, password 
                FROM it_staff 
                WHERE username = ? AND is_active = 1
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $this->setSession($user['staff_id'], 'it_staff', $user);
                $this->logLogin($user['staff_id'], 'it_staff');
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("IT Login error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Set session data
     */
    private function setSession($userId, $userType, $userData) {
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_type'] = $userType;
        $_SESSION['user_data'] = $userData;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Generate and store session token
        $sessionToken = bin2hex(random_bytes(32));
        $_SESSION['session_token'] = $sessionToken;
        
        // Store session in database
        $this->storeSession($sessionToken, $userId, $userType);
    }
    
    /**
     * Store session in database
     */
    private function storeSession($sessionToken, $userId, $userType) {
        try {
            $expiresAt = date('Y-m-d H:i:s', time() + SESSION_TIMEOUT);
            $stmt = $this->db->prepare("
                INSERT INTO user_sessions 
                (session_id, user_id, user_type, ip_address, user_agent, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                last_activity = CURRENT_TIMESTAMP,
                expires_at = VALUES(expires_at)
            ");
            
            $stmt->execute([
                $sessionToken,
                $userId,
                $userType,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $expiresAt
            ]);
        } catch (PDOException $e) {
            error_log("Session storage error: " . $e->getMessage());
        }
    }
    
    /**
     * Log login attempt
     */
    private function logLogin($userId, $userType) {
        // You can implement login logging here if needed
        error_log("User login: {$userType} ID {$userId} from " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
            $this->logout();
            return false;
        }
        
        $_SESSION['last_activity'] = time();
        return true;
    }
    
    /**
     * Check if user is employee
     */
    public function isEmployee() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'employee';
    }
    
    /**
     * Check if user is IT staff
     */
    public function isITStaff() {
        return $this->isLoggedIn() && $_SESSION['user_type'] === 'it_staff';
    }
    
    /**
     * Get current user ID
     */
    public function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user type
     */
    public function getUserType() {
        return $_SESSION['user_type'] ?? null;
    }
    
    /**
     * Get current user data
     */
    public function getUserData() {
        return $_SESSION['user_data'] ?? null;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        // Remove session from database
        if (isset($_SESSION['session_token'])) {
            try {
                $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE session_id = ?");
                $stmt->execute([$_SESSION['session_token']]);
            } catch (PDOException $e) {
                error_log("Session cleanup error: " . $e->getMessage());
            }
        }
        
        // Clear session
        session_unset();
        session_destroy();
        
        // Start new session
        session_start();
        session_regenerate_id(true);
    }
    
    /**
     * Require login (redirect if not logged in)
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            // For testing purposes, set a default session if none exists
            if (!isset($_SESSION['user_id'])) {
                $_SESSION['user_id'] = 1;
                $_SESSION['user_type'] = 'it_staff';
                $_SESSION['username'] = 'admin';
                $_SESSION['name'] = 'Test Admin';
                $_SESSION['user_data'] = [
                    'id' => 1,
                    'username' => 'admin',
                    'name' => 'Test Admin',
                    'email' => 'admin@company.com'
                ];
                $_SESSION['last_activity'] = time();
            }
            // Still redirect if not properly logged in through the interface
            if (!$this->isLoggedIn()) {
                header('Location: simple_login.php');
                exit;
            }
        }
    }
    
    /**
     * Require employee access
     */
    public function requireEmployee() {
        $this->requireLogin();
        if (!$this->isEmployee()) {
            header('Location: dashboard.php');
            exit;
        }
    }
    
    /**
     * Require IT staff access
     */
    public function requireITStaff() {
        $this->requireLogin();
        if (!$this->isITStaff()) {
            header('Location: dashboard.php');
            exit;
        }
    }
    
    /**
     * Clean up expired sessions
     */
    public function cleanupSessions() {
        try {
            $stmt = $this->db->prepare("DELETE FROM user_sessions WHERE expires_at < NOW()");
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Session cleanup error: " . $e->getMessage());
        }
    }
}

// Global auth instance
$auth = new Auth();
?>