<?php
/**
 * IT Staff Dashboard Controller
 * Handles IT staff personal dashboard with assigned tickets and performance metrics
 */

class ITStaffController {
    private $auth;
    private $ticketModel;
    private $currentUser;

    public function __construct() {
        // Initialize authentication
        $this->auth = new Auth();
        $this->auth->requireLogin();
        $this->auth->requireRole('it_staff'); // Only IT staff (not admin)

        // Initialize models
        $this->ticketModel = new Ticket();

        // Get current user
        $this->currentUser = $this->auth->getCurrentUser();
    }

    /**
     * Main index action - displays the IT staff dashboard
     */
    public function index() {
        // Get all required data for IT staff
        $data = [
            'currentUser' => $this->currentUser,
            'isITStaff' => true,
            'myStats' => $this->getMyStatistics(),
            'myTickets' => $this->getMyTickets(),
            'myPerformance' => $this->getMyPerformance(),
            'myWorkload' => $this->getMyWorkload(),
            'myChartData' => $this->prepareMyChartData()
        ];

        // Load the IT staff dashboard view
        $this->loadView('admin/it_dashboard', $data);
    }

    /**
     * Get IT staff personal statistics
     */
    private function getMyStatistics() {
        $userId = $this->currentUser['id'];
        $db = Database::getInstance()->getConnection();
        
        // Get tickets assigned to this IT staff
        $sql = "SELECT 
                COUNT(*) as total_assigned,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_tickets,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_today,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_tickets,
                SUM(CASE WHEN priority = 'urgent' AND status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as urgent_pending
                FROM tickets 
                WHERE assigned_to = :user_id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Get tickets assigned to this IT staff
     */
    private function getMyTickets() {
        return $this->ticketModel->getAll([
            'assigned_to' => $this->currentUser['id'],
            'limit' => 10,
            'order_by' => 'priority DESC, created_at DESC'
        ]);
    }

    /**
     * Get performance metrics for this IT staff
     */
    private function getMyPerformance() {
        $userId = $this->currentUser['id'];
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT 
                AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_response_time,
                AVG(CASE WHEN status = 'resolved' THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) END) as avg_resolution_time,
                COUNT(CASE WHEN status = 'resolved' AND DATE(updated_at) = CURDATE() THEN 1 END) as resolved_today,
                COUNT(CASE WHEN status = 'resolved' AND WEEK(updated_at) = WEEK(CURDATE()) THEN 1 END) as resolved_this_week,
                COUNT(CASE WHEN status = 'resolved' AND MONTH(updated_at) = MONTH(CURDATE()) THEN 1 END) as resolved_this_month
                FROM tickets 
                WHERE assigned_to = :user_id";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Get workload distribution
     */
    private function getMyWorkload() {
        $userId = $this->currentUser['id'];
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT 
                priority,
                COUNT(*) as count,
                SUM(CASE WHEN status IN ('open', 'in_progress') THEN 1 ELSE 0 END) as pending
                FROM tickets 
                WHERE assigned_to = :user_id
                GROUP BY priority
                ORDER BY FIELD(priority, 'urgent', 'high', 'medium', 'low')";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Prepare chart data for last 7 days
     */
    private function prepareMyChartData() {
        $userId = $this->currentUser['id'];
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(*) as assigned,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved
                FROM tickets 
                WHERE assigned_to = :user_id 
                AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $stats = $stmt->fetchAll();
        
        $labels = [];
        $assignedData = [];
        $resolvedData = [];
        
        foreach ($stats as $stat) {
            $labels[] = date('M d', strtotime($stat['date']));
            $assignedData[] = $stat['assigned'];
            $resolvedData[] = $stat['resolved'];
        }

        return [
            'labels' => $labels,
            'assigned' => $assignedData,
            'resolved' => $resolvedData
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
