<?php
/**
 * IT Staff Dashboard Controller
 * Handles IT staff personal dashboard with assigned tickets and performance metrics
 */

class ITStaffController {
    private $auth;
    private $ticketModel;
    private $slaModel;
    private $currentUser;

    public function __construct() {
        // Initialize authentication
        $this->auth = new Auth();
        $this->auth->requireLogin();
        $this->auth->requireRole('it_staff'); // Only IT staff (not admin)

        // Initialize models
        $this->ticketModel = new Ticket();
        $this->slaModel = new SLA();

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
            'myChartData' => $this->prepareMyChartData(),
            'atRiskTickets' => $this->getAtRiskTickets(),
            'breachedTickets' => $this->getBreachedTickets(),
            'mySLACompliance' => $this->getMySLACompliance()
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
     * Get tickets at risk of breaching SLA (assigned to this IT staff)
     */
    private function getAtRiskTickets() {
        $userId = $this->currentUser['id'];
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT t.id, t.ticket_number, t.title, t.priority, t.status,
                st.resolution_due_at,
                TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) as minutes_remaining
                FROM tickets t
                JOIN sla_tracking st ON t.id = st.ticket_id
                WHERE t.assigned_to = :user_id
                AND t.status NOT IN ('closed', 'resolved')
                AND st.is_paused = 0
                AND st.resolved_at IS NULL
                AND TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) <= 60
                AND TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) > 0
                ORDER BY st.resolution_due_at ASC
                LIMIT 5";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get breached tickets (assigned to this IT staff)
     */
    private function getBreachedTickets() {
        $userId = $this->currentUser['id'];
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT t.id, t.ticket_number, t.title, t.priority, t.status,
                st.resolution_due_at,
                TIMESTAMPDIFF(MINUTE, st.resolution_due_at, NOW()) as minutes_overdue
                FROM tickets t
                JOIN sla_tracking st ON t.id = st.ticket_id
                WHERE t.assigned_to = :user_id
                AND t.status NOT IN ('closed', 'resolved')
                AND st.is_paused = 0
                AND st.resolved_at IS NULL
                AND NOW() > st.resolution_due_at
                ORDER BY st.resolution_due_at ASC
                LIMIT 5";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get SLA compliance stats for this IT staff
     */
    private function getMySLACompliance() {
        $userId = $this->currentUser['id'];
        $db = Database::getInstance()->getConnection();
        
        $sql = "SELECT 
                COUNT(*) as total_tickets,
                SUM(CASE WHEN st.response_sla_status = 'met' THEN 1 ELSE 0 END) as response_met,
                SUM(CASE WHEN st.response_sla_status = 'breached' THEN 1 ELSE 0 END) as response_breached,
                SUM(CASE WHEN st.resolution_sla_status = 'met' THEN 1 ELSE 0 END) as resolution_met,
                SUM(CASE WHEN st.resolution_sla_status = 'breached' THEN 1 ELSE 0 END) as resolution_breached,
                ROUND((SUM(CASE WHEN st.response_sla_status = 'met' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as response_compliance_rate,
                ROUND((SUM(CASE WHEN st.resolution_sla_status = 'met' THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as resolution_compliance_rate
                FROM tickets t
                JOIN sla_tracking st ON t.id = st.ticket_id
                WHERE t.assigned_to = :user_id
                AND st.resolved_at IS NOT NULL";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch();
        
        // Handle case where no tickets resolved yet
        if ($result['total_tickets'] == 0) {
            $result['response_compliance_rate'] = 0;
            $result['resolution_compliance_rate'] = 0;
        }
        
        return $result;
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
