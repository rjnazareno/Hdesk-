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
        // Get filter parameters
        $filters = $this->getFilters();

        // If employee, only show their tickets
        if (!$this->isITStaff) {
            $filters['submitter_id'] = $this->currentUser['id'];
        }

        // Get pagination parameters
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $perPage = isset($_GET['per_page']) ? max(5, min(100, (int)$_GET['per_page'])) : 10;
        $filters['limit'] = $perPage;
        $filters['offset'] = ($page - 1) * $perPage;

        // Get sort parameters
        $filters['sort_by'] = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
        $filters['sort_order'] = isset($_GET['sort_order']) && $_GET['sort_order'] === 'asc' ? 'asc' : 'desc';

        // Get total count for pagination
        $totalTickets = $this->ticketModel->getCount($filters);
        $totalPages = ceil($totalTickets / $perPage);

        // Get tickets and categories
        $data = [
            'currentUser' => $this->currentUser,
            'isITStaff' => $this->isITStaff,
            'tickets' => $this->ticketModel->getAll($filters),
            'categories' => $this->categoryModel->getAll(),
            'filters' => $filters,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_tickets' => $totalTickets,
                'total_pages' => $totalPages
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
