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
     * Display all employees with pagination and sorting
     */
    public function index() {
        // Sorting parameters
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
        $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
        
        // Validate sort parameters for security
        $allowedSort = ['fname', 'lname', 'email', 'company', 'status', 'created_at'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }
        
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        // Pagination settings
        $itemsPerPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        
        // Get all employees
        $allEmployees = $this->employeeModel->getAll();
        
        // Sort employees
        usort($allEmployees, function($a, $b) use ($sortBy, $sortOrder) {
            $aVal = $a[$sortBy] ?? '';
            $bVal = $b[$sortBy] ?? '';
            
            // Handle numeric comparisons
            if ($sortBy === 'created_at') {
                $aVal = strtotime($aVal);
                $bVal = strtotime($bVal);
                $comparison = $aVal - $bVal;
            } else {
                // String comparison
                $comparison = strcasecmp($aVal, $bVal);
            }
            
            return $sortOrder === 'ASC' ? $comparison : -$comparison;
        });
        
        $totalEmployees = count($allEmployees);
        $totalPages = ceil($totalEmployees / $itemsPerPage);
        
        // Ensure current page is valid
        $currentPage = min($currentPage, max(1, $totalPages));
        
        // Calculate offset
        $offset = ($currentPage - 1) * $itemsPerPage;
        
        // Get employees for current page
        $employees = array_slice($allEmployees, $offset, $itemsPerPage);
        
        // Build sort URL helper function
        $sortUrl = function($field) use ($sortBy, $sortOrder) {
            $newOrder = ($sortBy === $field && $sortOrder === 'ASC') ? 'DESC' : 'ASC';
            return '?sort_by=' . $field . '&sort_order=' . $newOrder;
        };
        
        $data = [
            'currentUser' => $this->currentUser,
            'employees' => $employees,
            'sortBy' => $sortBy,
            'sortOrder' => $sortOrder,
            'sortUrl' => $sortUrl,
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalItems' => $totalEmployees,
                'itemsPerPage' => $itemsPerPage,
                'offset' => $offset,
                'hasPrevious' => $currentPage > 1,
                'hasNext' => $currentPage < $totalPages,
                'previousPage' => $currentPage - 1,
                'nextPage' => $currentPage + 1,
                'pages' => range(max(1, $currentPage - 2), min($totalPages, $currentPage + 2))
            ]
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
