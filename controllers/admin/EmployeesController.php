<?php
/**
 * Employees Controller (Customers)
 * Handles all business logic for employee management
 */

class EmployeesController {
    private $auth;
    private $employeeModel;
    private $currentUser;

    public function __construct() {
        // Initialize authentication
        $this->auth = new Auth();
        $this->auth->requireLogin();
        $this->auth->requireITStaff();

        // Initialize models
        $this->employeeModel = new Employee();

        // Get current user
        $this->currentUser = $this->auth->getCurrentUser();
    }

    /**
     * Display all employees
     */
    public function index() {
        // Get all employees
        $data = [
            'currentUser' => $this->currentUser,
            'employees' => $this->employeeModel->getAll()
        ];

        // Load the view
        $this->loadView('admin/employees', $data);
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
