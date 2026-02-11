<?php
/**
 * Customer Tickets Controller  
 * Handles employee ticket listing and filtering
 */

class CustomerTicketsController {
    private $auth;
    private $ticketModel;
    private $categoryModel;
    private $departmentModel;
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
        $this->departmentModel = new Department();
        $this->currentUser = $this->auth->getCurrentUser();
        $this->isITStaff = $this->currentUser['role'] === 'it_staff' || $this->currentUser['role'] === 'admin';
    }
    
    /**
     * Get unread notification count for current user
     */
    private function getUnreadCount() {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE employee_id = :employee_id AND is_read = 0";
        $stmt = $db->prepare($sql);
        $stmt->execute([':employee_id' => $this->currentUser['id']]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    /**
     * Display ticket listing
     */
    public function index() {
        // Handle delete action if POST request
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
            $this->deleteTicket();
            return;
        }
        
        // Get filter parameters
        $filters = [
            'status' => $_GET['status'] ?? '',
            'priority' => $_GET['priority'] ?? '',
            'category_id' => $_GET['category_id'] ?? '',
            'department_id' => $_GET['department_id'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        // If employee, only show their tickets
        if (!$this->isITStaff) {
            $filters['submitter_id'] = $this->currentUser['id'];
        }
        
        // Get tickets, categories, and departments
        $tickets = $this->ticketModel->getAll($filters);
        $categories = $this->categoryModel->getAll();
        $departments = $this->departmentModel->getAll();
        
        // Pass data to view
        $currentUser = $this->currentUser;
        $unreadNotifications = $this->getUnreadCount();
        
        // Load view
        $this->loadView('customer/tickets', compact('currentUser', 'tickets', 'categories', 'departments', 'filters', 'unreadNotifications'));
    }
    
    /**
     * Delete a ticket
     */
    private function deleteTicket() {
        $ticketId = (int)$_POST['ticket_id'];
        
        // Get ticket to verify ownership
        $ticket = $this->ticketModel->findById($ticketId);
        
        if (!$ticket) {
            $_SESSION['error'] = 'Ticket not found.';
            redirect('customer/tickets.php');
            return;
        }
        
        // Only allow employees to delete their own tickets
        if ($ticket['submitter_type'] !== 'employee' || $ticket['submitter_id'] != $this->currentUser['id']) {
            $_SESSION['error'] = 'You do not have permission to delete this ticket.';
            redirect('customer/tickets.php');
            return;
        }
        
        // Delete the ticket
        if ($this->ticketModel->delete($ticketId)) {
            $_SESSION['success'] = 'Ticket deleted successfully.';
        } else {
            $_SESSION['error'] = 'Failed to delete ticket.';
        }
        
        redirect('customer/tickets.php');
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
