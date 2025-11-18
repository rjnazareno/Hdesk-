<?php
/**
 * SLA Management Controller (Admin Only)
 * Allows admins to configure SLA policies
 */

class SLAManagementController {
    private $auth;
    private $slaModel;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireLogin();
        
        // ADMIN ONLY - IT Staff cannot access this
        if (!$this->auth->isAdmin()) {
            $_SESSION['error'] = "Access denied. Admin privileges required.";
            redirect('admin/dashboard.php');
        }
        
        $this->slaModel = new SLA();
    }
    
    /**
     * Display SLA management page
     */
    public function index() {
        $currentUser = $this->auth->getCurrentUser();
        
        // Get all SLA policies
        $policies = $this->slaModel->getAllPolicies();
        
        // Get SLA statistics
        $stats = $this->slaModel->getStatistics();
        
        // Get at-risk tickets
        $atRiskTickets = $this->slaModel->getAtRiskTickets(60); // 60 minutes threshold
        
        // Pagination for breached tickets
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $itemsPerPage = 10;
        $offset = ($page - 1) * $itemsPerPage;
        
        // Get all breached tickets for count
        $allBreachedTickets = $this->slaModel->getBreachedTickets();
        $totalBreached = count($allBreachedTickets);
        $totalPages = ceil($totalBreached / $itemsPerPage);
        
        // Get paginated breached tickets
        $breachedTickets = array_slice($allBreachedTickets, $offset, $itemsPerPage);
        
        $this->loadView('admin/sla_management', compact(
            'currentUser',
            'policies',
            'stats',
            'atRiskTickets',
            'breachedTickets',
            'page',
            'totalPages',
            'totalBreached',
            'itemsPerPage'
        ));
    }
    
    /**
     * Update SLA policy
     */
    public function updatePolicy() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/sla_management.php');
        }
        
        $policyId = (int)$_POST['policy_id'];
        $responseTime = (int)$_POST['response_time'];
        $resolutionTime = (int)$_POST['resolution_time'];
        $isBusinessHours = isset($_POST['is_business_hours']) ? 1 : 0;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        // Validation
        if ($responseTime <= 0 || $resolutionTime <= 0) {
            $_SESSION['error'] = "Response and resolution times must be greater than 0";
            redirect('admin/sla_management.php');
        }
        
        if ($responseTime >= $resolutionTime) {
            $_SESSION['error'] = "Response time must be less than resolution time";
            redirect('admin/sla_management.php');
        }
        
        $policyData = [
            'response_time' => $responseTime,
            'resolution_time' => $resolutionTime,
            'is_business_hours' => $isBusinessHours,
            'is_active' => $isActive
        ];
        
        $result = $this->slaModel->updatePolicy($policyId, $policyData);
        
        if ($result) {
            $_SESSION['success'] = "SLA policy updated successfully";
        } else {
            $_SESSION['error'] = "Failed to update SLA policy";
        }
        
        redirect('admin/sla_management.php');
    }
    
    /**
     * Load view file
     */
    private function loadView($view, $data = []) {
        extract($data);
        require_once __DIR__ . '/../../views/' . $view . '.view.php';
    }
}
