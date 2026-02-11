<?php
/**
 * Login Controller
 * Handles authentication requests
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Auth.php';

class LoginController {
    
    /**
     * Process login request
     */
    public function login() {
        // Check if already logged in
        if (isLoggedIn()) {
            $this->redirectToAppropriateArea();
            return;
        }

        // Check if form is submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = sanitize($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                redirect('login.php?error=empty');
            }
            
            $auth = new Auth();
            
            if ($auth->login($username, $password)) {
                $this->redirectToAppropriateArea();
            } else {
                redirect('login.php?error=invalid');
            }
        } else {
            redirect('login.php');
        }
    }
    
    /**
     * Redirect user to appropriate dashboard based on their role/rights
     */
    private function redirectToAppropriateArea() {
        if (!isset($_SESSION['user_type'])) {
            redirect('login.php?error=session');
            return;
        }
        
        if ($_SESSION['user_type'] === 'user') {
            // Users table accounts always go to admin
            redirect('admin/dashboard.php');
            return;
        }
        
        // Employee - check if they have admin rights
        if ($_SESSION['user_type'] === 'employee') {
            $hasAdminAccess = ($_SESSION['role'] === 'internal') && !empty($_SESSION['admin_rights']);
            
            if ($hasAdminAccess) {
                // Employee with admin rights - go to admin dashboard
                redirect('admin/dashboard.php');
            } else {
                // Regular employee - go to customer dashboard
                redirect('customer/dashboard.php');
            }
        }
    }
    
    /**
     * Process logout request
     */
    public function logout() {
        $auth = new Auth();
        $auth->logout();
        redirect('login.php?success=logout');
    }
}
