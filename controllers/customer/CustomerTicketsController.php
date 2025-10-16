<?php
/**
 * Customer Tickets Controller  
 * Handles employee ticket listing and filtering
 */

class CustomerTicketsController {
    private $auth;
    private $ticketModel;
    private $categoryModel;
    private $currentUser;
    private $isITStaff;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireLogin();
        
        // Ensure only employees can access
        if ($_SESSION['user_type'] !== 'employee') {
            redirect('admin/dashboard.php');
        }
        
        $this->ticketModel = new Ticket();
        $this->categoryModel = new Category();
        $this->currentUser = $this->auth->getCurrentUser();
        $this->isITStaff = $this->currentUser['role'] === 'it_staff' || $this->currentUser['role'] === 'admin';
    }
    
    /**
     * Display ticket listing
     */
    public function index() {
        // Get filter parameters
        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        // If employee, only show their tickets
        if (!$this->isITStaff) {
            $filters['submitter_id'] = $this->currentUser['id'];
        }
        
        // Get tickets and categories
        $tickets = $this->ticketModel->getAll($filters);
        $categories = $this->categoryModel->getAll();
        
        // Pass data to view
        $currentUser = $this->currentUser;
        
        // Load view
        $this->loadView('customer/tickets', compact('currentUser', 'tickets', 'categories', 'filters'));
    }
    
    /**
     * Load a view file
     */
    private function loadView($viewName, $data = []) {
        // Extract data array to variables
        extract($data);
        
        // Include the view file
        $viewPath = __DIR__ . '/../../views/' . $viewName . '.view.php';
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View not found: {$viewPath}");
        }
    }
}
