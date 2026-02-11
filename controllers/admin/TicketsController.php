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
    private $departmentModel;
    private $userModel;
    private $employeeModel;
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
        $this->departmentModel = new Department();
        $this->userModel = new User();
        $this->employeeModel = new Employee();

        // Get current user
        $this->currentUser = $this->auth->getCurrentUser();
        
        // Check if user has IT staff access (includes users table admin/it_staff OR employees with internal role + admin_rights)
        $adminRights = $_SESSION['admin_rights'] ?? null;
        $this->isITStaff = in_array($this->currentUser['role'], ['it_staff', 'admin']) || 
                          ($this->currentUser['role'] === 'internal' && !empty($adminRights));
    }

    /**
     * Display all tickets with filters
     */
    public function index() {
        // Handle delete action if POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
            $this->deleteTicket();
            return;
        }
        
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
        
        // Get all staff who can be assigned (IT staff + employees with admin rights)
        $itStaff = $this->userModel->getITStaff();
        $employeeAdmins = $this->employeeModel->getAdminEmployees();
        
        // Prepare data for view
        $data = [
            'currentUser' => $this->currentUser,
            'isITStaff' => $this->isITStaff,
            'tickets' => $tickets,
            'categories' => $this->categoryModel->getAll(),
            'departments' => $this->departmentModel->getAll(),
            'itStaff' => $itStaff,
            'employeeAdmins' => $employeeAdmins,
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
        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
            'search' => $_GET['search'] ?? '',
            'view' => $_GET['view'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'sla_status' => $_GET['sla_status'] ?? ''
        ];
        
        // Handle special views
        if ($filters['view'] === 'my_tickets') {
            $filters['assigned_to'] = $this->currentUser['id'];
            // Set assignee_type based on user_type to ensure correct matching
            $filters['assignee_type'] = ($_SESSION['user_type'] ?? 'user') === 'employee' ? 'employee' : 'user';
        } elseif ($filters['view'] === 'pool') {
            $filters['unassigned'] = true;
            $filters['status'] = $filters['status'] ?: 'pending,open'; // Default to pending/open for pool
        } elseif ($filters['view'] === 'queue') {
            // Legacy support - redirect queue to pool
            $filters['unassigned'] = true;
            $filters['status'] = $filters['status'] ?: 'pending,open';
        }
        
        return $filters;
    }

    /**
     * Delete a ticket
     */
    private function deleteTicket() {
        $ticketId = (int)$_POST['ticket_id'];
        
        // Get ticket to verify ownership/permissions
        $ticket = $this->ticketModel->findById($ticketId);
        
        if (!$ticket) {
            $_SESSION['error'] = 'Ticket not found.';
            redirect('admin/tickets.php');
            return;
        }
        
        // Only IT staff/admin or the ticket submitter can delete
        $canDelete = $this->isITStaff || 
                     ($ticket['submitter_type'] === 'employee' && $ticket['submitter_id'] == $this->currentUser['id']);
        
        if (!$canDelete) {
            $_SESSION['error'] = 'You do not have permission to delete this ticket.';
            redirect('admin/tickets.php');
            return;
        }
        
        // Delete the ticket
        if ($this->ticketModel->delete($ticketId)) {
            $_SESSION['success'] = 'Ticket deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete ticket.';
        }
        
        redirect('admin/tickets.php');
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
