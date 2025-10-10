<?php
/**
 * Tickets Controller
 * Handles all business logic for ticket management
 */

class TicketsController {
    private $auth;
    private $ticketModel;
    private $categoryModel;
    private $currentUser;
    private $isITStaff;

    public function __construct() {
        // Initialize authentication
        $this->auth = new Auth();
        $this->auth->requireLogin();
        $this->auth->requireITStaff();

        // Initialize models
        $this->ticketModel = new Ticket();
        $this->categoryModel = new Category();

        // Get current user
        $this->currentUser = $this->auth->getCurrentUser();
        $this->isITStaff = $this->currentUser['role'] === 'it_staff' || $this->currentUser['role'] === 'admin';
    }

    /**
     * Display all tickets with filters
     */
    public function index() {
        // Get filter parameters
        $filters = $this->getFilters();

        // If employee, only show their tickets
        if (!$this->isITStaff) {
            $filters['submitter_id'] = $this->currentUser['id'];
        }

        // Get tickets and categories
        $data = [
            'currentUser' => $this->currentUser,
            'isITStaff' => $this->isITStaff,
            'tickets' => $this->ticketModel->getAll($filters),
            'categories' => $this->categoryModel->getAll(),
            'filters' => $filters
        ];

        // Load the view
        $this->loadView('admin/tickets', $data);
    }

    /**
     * Get filter parameters from request
     */
    private function getFilters() {
        return [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
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
