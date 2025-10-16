<?php
/**
 * Add User Controller
 * Handles creation of IT Staff and Admin users
 */

class AddUserController {
    private $auth;
    private $userModel;
    private $currentUser;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireLogin();
        
        // Only admins can add IT staff/admin users
        if ($this->auth->getCurrentUser()['role'] !== 'admin') {
            $_SESSION['error'] = "Only administrators can add IT staff or admin users.";
            redirect('admin/dashboard.php');
        }
        
        $this->userModel = new User();
        $this->currentUser = $this->auth->getCurrentUser();
    }
    
    /**
     * Show add user form
     */
    public function index() {
        // Get role from query parameter (default to it_staff)
        $role = isset($_GET['role']) ? sanitize($_GET['role']) : 'it_staff';
        
        // Validate role
        if (!in_array($role, ['it_staff', 'admin'])) {
            $role = 'it_staff';
        }
        
        // Load view
        $this->loadView('admin/add_user', [
            'currentUser' => $this->currentUser,
            'selectedRole' => $role
        ]);
    }
    
    /**
     * Handle user creation
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/add_user.php');
        }
        
        // Validate required fields
        $requiredFields = ['username', 'email', 'password', 'full_name', 'role'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['error'] = "Please fill in all required fields.";
                redirect('admin/add_user.php');
            }
        }
        
        // Validate role
        if (!in_array($_POST['role'], ['it_staff', 'admin'])) {
            $_SESSION['error'] = "Invalid role selected.";
            redirect('admin/add_user.php');
        }
        
        // Check if username already exists
        if ($this->userModel->findByUsername(sanitize($_POST['username']))) {
            $_SESSION['error'] = "Username already exists.";
            redirect('admin/add_user.php?role=' . $_POST['role']);
        }
        
        // Check if email already exists
        if ($this->userModel->findByEmail(sanitize($_POST['email']))) {
            $_SESSION['error'] = "Email already exists.";
            redirect('admin/add_user.php?role=' . $_POST['role']);
        }
        
        // Prepare user data
        $userData = [
            'username' => sanitize($_POST['username']),
            'email' => sanitize($_POST['email']),
            'password' => $_POST['password'], // Will be hashed in model
            'full_name' => sanitize($_POST['full_name']),
            'role' => sanitize($_POST['role']),
            'department' => !empty($_POST['department']) ? sanitize($_POST['department']) : null,
            'phone' => !empty($_POST['phone']) ? sanitize($_POST['phone']) : null
        ];
        
        // Create user
        $userId = $this->userModel->create($userData);
        
        if ($userId) {
            // Send welcome email (optional)
            try {
                $mailer = new Mailer();
                $user = $this->userModel->findById($userId);
                // $mailer->sendWelcomeEmail($user);
            } catch (Exception $e) {
                error_log("Failed to send welcome email: " . $e->getMessage());
            }
            
            $roleLabel = $userData['role'] === 'admin' ? 'Administrator' : 'IT Staff';
            $_SESSION['success'] = "{$roleLabel} '{$userData['full_name']}' added successfully!";
            redirect('admin/dashboard.php');
        } else {
            $_SESSION['error'] = "Failed to add user. Please try again.";
            redirect('admin/add_user.php?role=' . $_POST['role']);
        }
    }
    
    /**
     * Load view file
     */
    private function loadView($view, $data = []) {
        extract($data);
        require_once __DIR__ . '/../../views/' . $view . '.view.php';
    }
}
