<?php
/**
 * Customer Dashboard Controller
 * Handles employee dashboard logic
 */

class CustomerDashboardController {
    private $auth;
    private $ticketModel;
    private $categoryModel;
    private $activityModel;
    private $currentUser;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireLogin();
        
        // Ensure only employees can access
        if ($_SESSION['user_type'] !== 'employee') {
            redirect('admin/dashboard.php');
        }
        
        $this->ticketModel = new Ticket();
        $this->categoryModel = new Category();
        $this->activityModel = new TicketActivity();
        $this->currentUser = $this->auth->getCurrentUser();
    }
    
    /**
     * Display employee dashboard
     */
    public function index() {
        // Get statistics for this employee only
        $stats = $this->ticketModel->getStats($this->currentUser['id'], 'employee');
        
        // Get recent tickets for this employee
        $recentTickets = $this->ticketModel->getAll([
            'limit' => 10,
            'submitter_id' => $this->currentUser['id']
        ]);
        
        // Get recent activity for this employee
        $recentActivity = $this->activityModel->getRecent(5, $this->currentUser['id'], 'employee');
        
        // Get categories
        $categories = $this->categoryModel->getAll();
        
        // Pass data to view
        $currentUser = $this->currentUser;
        
        // Load view
        $this->loadView('customer/dashboard', compact(
            'currentUser',
            'stats',
            'recentTickets',
            'recentActivity',
            'categories'
        ));
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
