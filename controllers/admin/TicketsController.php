<?php
/**
 * Tickets Controller
 * Handles all business logic for ticket management
 */

class TicketsController {
    private $auth;
    private $ticketModel;
    private $categoryModel;
    private $slaModel;
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
        $this->slaModel = new SLA();

        // Get current user
        $this->currentUser = $this->auth->getCurrentUser();
        $this->isITStaff = $this->currentUser['role'] === 'it_staff' || $this->currentUser['role'] === 'admin';
    }

    /**
     * Display all tickets with filters
     */
    public function index() {
        // Pagination
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $itemsPerPage = 20;
        $offset = ($page - 1) * $itemsPerPage;
        
        // Sorting
        $sortBy = $_GET['sort_by'] ?? 'created_at';
        $sortDir = $_GET['sort_dir'] ?? 'DESC';
        
        // Validate sort fields
        $allowedSort = ['created_at', 'priority', 'status', 'ticket_number'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'created_at';
        }
        if (!in_array($sortDir, ['ASC', 'DESC'])) {
            $sortDir = 'DESC';
        }
        
        // Get filters
        $filters = $this->getFilters();
        
        // If employee, only show their tickets
        if (!$this->isITStaff) {
            $filters['submitter_id'] = $this->currentUser['id'];
        }
        
        // Get tickets with pagination and sorting
        $tickets = $this->ticketModel->getAll($filters, $sortBy, $sortDir, $itemsPerPage, $offset);
        
        // Get total count for pagination
        $totalTickets = $this->ticketModel->getTotalCount($filters);
        $totalPages = ceil($totalTickets / $itemsPerPage);
        
        // Prepare data for view
        $data = [
            'currentUser' => $this->currentUser,
            'isITStaff' => $this->isITStaff,
            'tickets' => $tickets,
            'categories' => $this->categoryModel->getAll(),
            'filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'items_per_page' => $itemsPerPage,
                'total_items' => $totalTickets
            ],
            'sorting' => [
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir
            ]
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
