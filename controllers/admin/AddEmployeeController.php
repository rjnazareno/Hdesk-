<?php
/**
 * Add Employee Controller
 * Handles employee creation by admin/IT staff
 */

class AddEmployeeController {
    private $auth;
    private $employeeModel;
    private $currentUser;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireITStaff();
        
        $this->employeeModel = new Employee();
        $this->currentUser = $this->auth->getCurrentUser();
    }
    
    /**
     * Show add employee form
     */
    public function index() {
        // Load view
        $this->loadView('admin/add_employee', [
            'currentUser' => $this->currentUser
        ]);
    }
    
    /**
     * Handle employee creation
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/add_employee.php');
        }
        
        // Validate required fields
        $requiredFields = ['username', 'email', 'password', 'fname', 'lname', 'position'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                $_SESSION['error'] = "Please fill in all required fields.";
                redirect('admin/add_employee.php');
            }
        }
        
        // Check if username already exists
        if ($this->employeeModel->findByUsername(sanitize($_POST['username']))) {
            $_SESSION['error'] = "Username already exists.";
            redirect('admin/add_employee.php');
        }
        
        // Check if email already exists
        if ($this->employeeModel->findByEmail(sanitize($_POST['email']))) {
            $_SESSION['error'] = "Email already exists.";
            redirect('admin/add_employee.php');
        }
        
        // Prepare employee data
        $employeeData = [
            'username' => sanitize($_POST['username']),
            'email' => sanitize($_POST['email']),
            'personal_email' => !empty($_POST['personal_email']) ? sanitize($_POST['personal_email']) : null,
            'password' => $_POST['password'], // Will be hashed in model
            'fname' => sanitize($_POST['fname']),
            'lname' => sanitize($_POST['lname']),
            'company' => !empty($_POST['company']) ? sanitize($_POST['company']) : null,
            'position' => sanitize($_POST['position']),
            'contact' => !empty($_POST['contact']) ? sanitize($_POST['contact']) : null,
            'official_sched' => !empty($_POST['official_sched']) ? sanitize($_POST['official_sched']) : null,
            'role' => sanitize($_POST['role'] ?? 'employee'),
            'status' => 'active'
        ];
        
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (in_array($fileExtension, $allowedExtensions)) {
                $fileName = uniqid('profile_') . '.' . $fileExtension;
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $filePath)) {
                    $employeeData['profile_picture'] = $fileName;
                }
            }
        }
        
        // Create employee
        $employeeId = $this->employeeModel->create($employeeData);
        
        if ($employeeId) {
            // Send welcome email (optional)
            try {
                $mailer = new Mailer();
                $employee = $this->employeeModel->findById($employeeId);
                
                // You can create a sendWelcomeEmail method in Mailer class
                // $mailer->sendWelcomeEmail($employee);
            } catch (Exception $e) {
                error_log("Failed to send welcome email: " . $e->getMessage());
            }
            
            $_SESSION['success'] = "Employee added successfully!";
            redirect('admin/customers.php');
        } else {
            $_SESSION['error'] = "Failed to add employee. Please try again.";
            redirect('admin/add_employee.php');
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
