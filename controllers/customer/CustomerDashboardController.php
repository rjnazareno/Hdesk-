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
     * Display employee dashboard
     */
    public function index() {
        // Pagination for recent tickets
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $itemsPerPage = 5;
        $offset = ($page - 1) * $itemsPerPage;
        
        // Get statistics for this employee only
        $stats = $this->ticketModel->getStats($this->currentUser['id'], 'employee');
        
        // Get paginated recent tickets for this employee
        $recentTicketsData = $this->getRecentTickets($itemsPerPage, $offset);
        
        // Get recent activity for this employee
        $recentActivity = $this->activityModel->getRecent(5, $this->currentUser['id'], 'employee');
        
        // Get categories
        $categories = $this->categoryModel->getAll();
        
        // Pass data to view
        $currentUser = $this->currentUser;
        $recentTickets = $recentTicketsData['tickets'];
        $recentTicketsPagination = $recentTicketsData['pagination'];
        $unreadNotifications = $this->getUnreadCount();
        
        // Load view
        $this->loadView('customer/dashboard', compact(
            'currentUser',
            'stats',
            'recentTickets',
            'recentTicketsPagination',
            'recentActivity',
            'categories',
            'unreadNotifications'
        ));
    }
    
    /**
     * Get recent tickets with pagination
     */
    private function getRecentTickets($limit = 5, $offset = 0) {
        $userId = $this->currentUser['id'];
        
        // Get tickets for pagination
        $tickets = $this->ticketModel->getAll([
            'submitter_id' => $userId
        ], 'created_at', 'DESC', $limit, $offset);
        
        // Get total count for pagination
        $db = Database::getInstance()->getConnection();
        $countSql = "SELECT COUNT(*) FROM tickets WHERE submitter_id = :user_id AND submitter_type = 'employee'";
        $stmt = $db->prepare($countSql);
        $stmt->execute([':user_id' => $userId]);
        $totalTickets = $stmt->fetchColumn();
        
        // Calculate pagination
        $totalPages = ceil($totalTickets / $limit);
        $currentPage = floor($offset / $limit) + 1;
        
        return [
            'tickets' => $tickets,
            'pagination' => [
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'totalItems' => $totalTickets,
                'itemsPerPage' => $limit,
                'hasNextPage' => $currentPage < $totalPages,
                'hasPrevPage' => $currentPage > 1
            ]
        ];
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
