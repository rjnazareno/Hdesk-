<?php
/**
 * Customer Profile Controller
 * Handles employee profile viewing and updating
 */

class ProfileController {
    private $auth;
    private $employeeModel;
    private $currentUser;

    public function __construct() {
        // Initialize authentication
        $this->auth = new Auth();
        $this->auth->requireLogin();

        // Initialize models
        $this->employeeModel = new Employee();

        // Get current employee
        $userId = $_SESSION['user_id'];
        $this->currentUser = $this->employeeModel->findById($userId);
        
        if (!$this->currentUser) {
            $_SESSION['error'] = "User not found.";
            redirect('customer/dashboard.php');
        }
    }

    /**
     * Display profile page
     */
    public function index() {
        // Handle POST requests (profile updates)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleAction();
            return;
        }

        // Load the view
        $this->loadView('customer/profile', [
            'currentUser' => $this->currentUser
        ]);
    }

    /**
     * Handle profile update actions
     */
    private function handleAction() {
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'update_profile':
                $this->updateProfile();
                break;
            
            case 'change_password':
                $this->changePassword();
                break;
            
            case 'upload_picture':
                $this->uploadProfilePicture();
                break;
            
            default:
                redirect('customer/profile.php?error=invalid_action');
        }
    }

    /**
     * Update profile information
     */
    private function updateProfile() {
        $updateData = [
            'personal_email' => sanitize($_POST['personal_email'] ?? ''),
            'contact' => sanitize($_POST['contact'] ?? '')
        ];

        // Remove empty values
        $updateData = array_filter($updateData, function($value) {
            return !empty($value);
        });

        if (empty($updateData)) {
            $_SESSION['error'] = "No changes to update.";
            redirect('customer/profile.php');
            return;
        }

        if ($this->employeeModel->update($this->currentUser['id'], $updateData)) {
            $_SESSION['success'] = "Profile updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update profile. Please try again.";
        }

        redirect('customer/profile.php');
    }

    /**
     * Change password
     */
    private function changePassword() {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate current password
        if (!password_verify($currentPassword, $this->currentUser['password'])) {
            $_SESSION['error'] = "Current password is incorrect.";
            redirect('customer/profile.php#change-password');
            return;
        }

        // Validate new password
        if ($newPassword !== $confirmPassword) {
            $_SESSION['error'] = "New passwords do not match.";
            redirect('customer/profile.php#change-password');
            return;
        }

        if (strlen($newPassword) < 6) {
            $_SESSION['error'] = "Password must be at least 6 characters long.";
            redirect('customer/profile.php#change-password');
            return;
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($this->employeeModel->update($this->currentUser['id'], ['password' => $hashedPassword])) {
            $_SESSION['success'] = "Password changed successfully!";
        } else {
            $_SESSION['error'] = "Failed to change password. Please try again.";
        }

        redirect('customer/profile.php#change-password');
    }

    /**
     * Upload profile picture
     */
    private function uploadProfilePicture() {
        if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = "No file uploaded or upload error occurred.";
            redirect('customer/profile.php#profile-picture');
            return;
        }

        $uploadDir = __DIR__ . '/../../uploads/profiles/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileExtension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($fileExtension, $allowedExtensions)) {
            $_SESSION['error'] = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            redirect('customer/profile.php#profile-picture');
            return;
        }

        // Check file size (2MB max)
        if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
            $_SESSION['error'] = "File too large. Maximum size is 2MB.";
            redirect('customer/profile.php#profile-picture');
            return;
        }

        // Delete old profile picture if exists
        if (!empty($this->currentUser['profile_picture'])) {
            $oldFile = $uploadDir . $this->currentUser['profile_picture'];
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        $fileName = uniqid('profile_') . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filePath)) {
            if ($this->employeeModel->update($this->currentUser['id'], ['profile_picture' => $fileName])) {
                $_SESSION['success'] = "Profile picture updated successfully!";
            } else {
                $_SESSION['error'] = "Failed to save profile picture.";
            }
        } else {
            $_SESSION['error'] = "Failed to upload file.";
        }

        redirect('customer/profile.php#profile-picture');
    }

    /**
     * Load view file
     */
    private function loadView($view, $data = []) {
        extract($data);
        require_once __DIR__ . '/../../views/' . $view . '.view.php';
    }
}

