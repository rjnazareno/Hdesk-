<?php
/**
 * Admin Controller
 * Handles all business logic for admin settings and user management
 */

class AdminController {
    private $auth;
    private $userModel;
    private $employeeModel;
    private $currentUser;

    public function __construct() {
        // Initialize authentication
        $this->auth = new Auth();
        $this->auth->requireLogin();
        $this->auth->requireRole('admin'); // Only admins can access

        // Initialize models
        $this->userModel = new User();
        $this->employeeModel = new Employee();

        // Get current user
        $this->currentUser = $this->auth->getCurrentUser();
    }

    /**
     * Display admin settings page
     */
    public function index() {
        // Handle POST requests (user management actions)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAction();
            return;
        }

        // Get all IT staff/admins
        $data = [
            'currentUser' => $this->currentUser,
            'allUsers' => $this->userModel->getAll()
        ];

        // Load the view
        $this->loadView('admin/admin_settings', $data);
    }

    /**
     * Handle user management actions
     */
    private function handleAction() {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'toggle_status':
                $this->toggleUserStatus();
                break;
            
            case 'edit_user':
                $this->editUser();
                break;
            
            case 'change_password':
                $this->changePassword();
                break;
            
            default:
                redirect('admin.php?error=invalid_action');
        }
    }

    /**
     * Toggle user active status
     */
    private function toggleUserStatus() {
        $userId = (int)$_POST['user_id'];
        $user = $this->userModel->findById($userId);
        
        if ($user) {
            $newStatus = $user['is_active'] ? 0 : 1;
            $this->userModel->update($userId, ['is_active' => $newStatus]);
            redirect('admin.php?success=status_updated');
        }
        
        redirect('admin.php?error=user_not_found');
    }

    /**
     * Edit user information
     */
    private function editUser() {
        $userId = (int)$_POST['user_id'];
        $updateData = [
            'username' => sanitize($_POST['username']),
            'full_name' => sanitize($_POST['full_name']),
            'email' => sanitize($_POST['email']),
            'role' => sanitize($_POST['role']),
            'department' => sanitize($_POST['department']),
            'phone' => sanitize($_POST['phone'])
        ];
        
        if ($this->userModel->update($userId, $updateData)) {
            redirect('admin.php?success=user_updated');
        } else {
            redirect('admin.php?error=update_failed');
        }
    }

    /**
     * Change user password
     */
    private function changePassword() {
        $userId = (int)$_POST['user_id'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];
        
        // Validate passwords
        if ($newPassword !== $confirmPassword) {
            redirect('admin.php?error=password_mismatch');
            return;
        }
        
        if (strlen($newPassword) < 6) {
            redirect('admin.php?error=password_short');
            return;
        }
        
        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($this->userModel->update($userId, ['password' => $hashedPassword])) {
            redirect('admin.php?success=password_changed');
        } else {
            redirect('admin.php?error=password_change_failed');
        }
    }

    /**
     * Load view file with data
     */
    private function loadView($viewName, $data = []) {
        // Extract data to variables
        extract($data);
        
        // Include the view file
        $viewFile = __DIR__ . '/../../views/' . $viewName . '.view.php';
        
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View file not found: " . $viewFile);
        }
    }
}
