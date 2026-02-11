<?php
/**
 * Quick Fix for Profile Bug - Alternative Profile Controller
 * Uses username instead of id to avoid the id=0 duplicate issue
 */

class ProfileControllerFixed {
    private $auth;
    private $employeeModel;
    private $currentUser;

    public function __construct() {
        // Initialize authentication
        $this->auth = new Auth();
        $this->auth->requireLogin();

        // Initialize models
        $this->employeeModel = new Employee();

        // Get current employee by username instead of id (avoids id=0 issue)
        $username = $_SESSION['username'];
        
        if (!$username) {
            $_SESSION['error'] = "Username not found in session.";
            redirect('customer/dashboard.php');
        }
        
        // Find by username instead of id to avoid duplicate id=0 issue
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT * FROM employees WHERE username = :username LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([':username' => $username]);
        $this->currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$this->currentUser) {
            $_SESSION['error'] = "User profile not found for username: " . $username;
            redirect('customer/dashboard.php');
        }
    }
    
    /**
     * Get unread notification count for current user
     */
    private function getUnreadCount() {
        $db = Database::getInstance()->getConnection();
        // Use username instead of employee_id to avoid id=0 issue
        $sql = "SELECT COUNT(*) as count FROM notifications n 
                INNER JOIN employees e ON n.employee_id = e.id 
                WHERE e.username = :username AND n.is_read = 0";
        $stmt = $db->prepare($sql);
        $stmt->execute([':username' => $this->currentUser['username']]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
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
            'currentUser' => $this->currentUser,
            'unreadNotifications' => $this->getUnreadCount()
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
        try {
            $data = [
                'fname' => trim($_POST['fname'] ?? ''),
                'lname' => trim($_POST['lname'] ?? ''),
                'personal_email' => trim($_POST['personal_email'] ?? ''),
                'contact' => trim($_POST['contact'] ?? '')
            ];

            // Validate required fields
            if (empty($data['fname']) || empty($data['lname'])) {
                throw new Exception('First name and last name are required.');
            }

            // Update by username instead of id
            $db = Database::getInstance()->getConnection();
            $sql = "UPDATE employees SET 
                        fname = :fname, 
                        lname = :lname, 
                        personal_email = :personal_email, 
                        contact = :contact 
                    WHERE username = :username";
            
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':fname' => $data['fname'],
                ':lname' => $data['lname'],
                ':personal_email' => $data['personal_email'],
                ':contact' => $data['contact'],
                ':username' => $this->currentUser['username']
            ]);

            if ($result) {
                // Update session name
                $_SESSION['full_name'] = $data['fname'] . ' ' . $data['lname'];
                redirect('customer/profile.php?success=profile_updated');
            } else {
                throw new Exception('Failed to update profile.');
            }

        } catch (Exception $e) {
            redirect('customer/profile.php?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Change password
     */
    private function changePassword() {
        try {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validate input
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('All password fields are required.');
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match.');
            }

            if (strlen($newPassword) < 6) {
                throw new Exception('New password must be at least 6 characters long.');
            }

            // Verify current password
            if ($currentPassword !== $this->currentUser['password']) {
                throw new Exception('Current password is incorrect.');
            }

            // Update password by username
            $db = Database::getInstance()->getConnection();
            $sql = "UPDATE employees SET password = :password WHERE username = :username";
            $stmt = $db->prepare($sql);
            $result = $stmt->execute([
                ':password' => $newPassword,
                ':username' => $this->currentUser['username']
            ]);

            if ($result) {
                redirect('customer/profile.php?success=password_changed');
            } else {
                throw new Exception('Failed to update password.');
            }

        } catch (Exception $e) {
            redirect('customer/profile.php?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Upload profile picture
     */
    private function uploadProfilePicture() {
        try {
            if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded or upload error.');
            }

            $file = $_FILES['profile_picture'];
            
            // Validate file type
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Please upload JPEG, PNG, or GIF images only.');
            }

            // Validate file size (2MB max)
            if ($file['size'] > 2097152) {
                throw new Exception('File size too large. Maximum 2MB allowed.');
            }

            // Create upload directory if it doesn't exist
            $uploadDir = __DIR__ . '/../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $this->currentUser['username'] . '_' . time() . '.' . $extension;
            $uploadPath = $uploadDir . $filename;

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                // Update database by username
                $db = Database::getInstance()->getConnection();
                $sql = "UPDATE employees SET profile_picture = :filename WHERE username = :username";
                $stmt = $db->prepare($sql);
                $result = $stmt->execute([
                    ':filename' => $filename,
                    ':username' => $this->currentUser['username']
                ]);

                if ($result) {
                    // Delete old profile picture if exists
                    if ($this->currentUser['profile_picture'] && file_exists($uploadDir . $this->currentUser['profile_picture'])) {
                        unlink($uploadDir . $this->currentUser['profile_picture']);
                    }

                    redirect('customer/profile.php?success=picture_uploaded');
                } else {
                    // Clean up uploaded file if database update fails
                    unlink($uploadPath);
                    throw new Exception('Failed to update profile picture in database.');
                }
            } else {
                throw new Exception('Failed to upload file.');
            }

        } catch (Exception $e) {
            redirect('customer/profile.php?error=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Load view with data
     */
    private function loadView($view, $data = []) {
        // Set page variables
        $pageTitle = 'My Profile - IT Help Desk';
        $baseUrl = '../';

        // Extract data to make variables available in view
        extract($data);

        // Load the view
        include __DIR__ . '/../views/' . $view . '.view.php';
    }
}

// Use the fixed controller
require_once __DIR__ . '/../config/config.php';

$controller = new ProfileControllerFixed();
$controller->index();

?>