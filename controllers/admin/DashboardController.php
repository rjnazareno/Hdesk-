<?php
/**
 * Dashboard Controller
 * Handles all business logic for the admin dashboard
 */

class DashboardController {
    private $auth;
    private $ticketModel;
    private $userModel;
    private $employeeModel;
    private $categoryModel;
    private $activityModel;
    private $currentUser;
    private $isITStaff;

    public function __construct() {
        // Initialize authentication
        $this->auth = new Auth();
        $this->auth->requireLogin();
        $this->auth->requireITStaff();

        // Get current user
        $this->currentUser = $this->auth->getCurrentUser();
        $this->isITStaff = $this->currentUser['role'] === 'it_staff' || $this->currentUser['role'] === 'admin';

        // Redirect IT staff to their own dashboard
        if ($this->currentUser['role'] === 'it_staff') {
            header('Location: it_dashboard.php');
            exit;
        }

        // Only admins continue here
        $this->auth->requireRole('admin');

        // Initialize models
        $this->ticketModel = new Ticket();
        $this->userModel = new User();
        $this->employeeModel = new Employee();
        $this->categoryModel = new Category();
        $this->activityModel = new TicketActivity();
    }

    /**
     * Main index action - displays the dashboard
     */
    public function index() {
        // Get all required data
        $data = [
            'currentUser' => $this->currentUser,
            'isITStaff' => $this->isITStaff,
            'stats' => $this->getStatistics(),
            'userStats' => $this->userModel->getStats(),
            'employeeStats' => $this->employeeModel->getStats(),
            'categoryStats' => $this->categoryModel->getStats(),
            'recentTickets' => $this->getRecentTickets(),
            'recentActivity' => $this->getRecentActivity(),
            'dailyStats' => $this->getDailyStatistics(),
            'chartData' => $this->prepareChartData(),
            'statusBreakdown' => $this->ticketModel->getStatusBreakdown()
        ];

        // Load the view
        $this->loadView('admin/dashboard', $data);
    }

    /**
     * Get ticket statistics
     */
    private function getStatistics() {
        return $this->ticketModel->getStats($this->currentUser['id'], $this->currentUser['role']);
    }

    /**
     * Get recent tickets
     */
    private function getRecentTickets() {
        return $this->ticketModel->getAll([
            'limit' => 5,
            'submitter_id' => !$this->isITStaff ? $this->currentUser['id'] : null
        ]);
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity() {
        return $this->activityModel->getRecent(5, $this->currentUser['id'], $this->currentUser['role']);
    }

    /**
     * Get daily statistics
     */
    private function getDailyStatistics() {
        return $this->ticketModel->getDailyStats(10);
    }

    /**
     * Prepare chart data for JavaScript
     */
    private function prepareChartData() {
        $dailyStats = $this->getDailyStatistics();
        $chartLabels = [];
        $chartData = [];
        
        foreach ($dailyStats as $stat) {
            $chartLabels[] = date('M d', strtotime($stat['date']));
            $chartData[] = $stat['count'];
        }

        return [
            'labels' => $chartLabels,
            'data' => $chartData
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
