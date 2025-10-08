<?php
/**
 * Login Controller
 * Handles authentication requests
 */

require_once __DIR__ . '/../config/config.php';

class LoginController {
    
    /**
     * Process login request
     */
    public function login() {
        // Check if already logged in
        if (isLoggedIn()) {
            redirect('dashboard.php');
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
                // Check user type and redirect accordingly
                if (isset($_SESSION['user_type'])) {
                    if ($_SESSION['user_type'] === 'employee') {
                        redirect('customer/dashboard.php');
                    } else {
                        redirect('admin/dashboard.php');
                    }
                } else {
                    redirect('dashboard.php');
                }
            } else {
                redirect('login.php?error=invalid');
            }
        } else {
            redirect('login.php');
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
