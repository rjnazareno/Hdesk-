<?php
/**
 * SLA Performance Controller
 * Tab 1: My SLA Performance - personal metrics for logged-in user
 * Tab 2: Generate SLA Report - date-filtered report for all admin staff
 */

class SLAPerformanceController {
    private $auth;
    private $db;
    private $currentUser;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireLogin();
        $this->auth->requireITStaff();
        
        $this->db = Database::getInstance()->getConnection();
        $this->currentUser = $this->auth->getCurrentUser();
    }
    
    /**
     * Display SLA performance with tabs
     */
    public function index() {
        $activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'my_performance';
        $currentUser = $this->currentUser;
        
        // Data for Tab 1: My SLA Performance (always loaded)
        $myPerformance = $this->getMyPerformance();
        $myRecentTickets = $this->getMyRecentTickets();
        
        // Data for Tab 2: Generate Report
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
        $staffReport = [];
        $reportOverallStats = [];
        $reportGenerated = false;
        
        if ($activeTab === 'report') {
            $staffReport = $this->getStaffPerformanceByDateRange($dateFrom, $dateTo);
            $reportOverallStats = $this->getOverallStatsByDateRange($dateFrom, $dateTo);
            $reportGenerated = true;
        }
        
        $this->loadView('admin/sla_performance', compact(
            'currentUser',
            'activeTab',
            'myPerformance',
            'myRecentTickets',
            'dateFrom',
            'dateTo',
            'staffReport',
            'reportOverallStats',
            'reportGenerated'
        ));
    }
    
    /**
     * Get the logged-in user's own SLA performance
     */
    private function getMyPerformance() {
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT 
                    COUNT(DISTINCT t.id) as total_tickets,
                    COUNT(DISTINCT CASE WHEN t.status IN ('resolved', 'closed') THEN t.id END) as resolved_tickets,
                    COUNT(DISTINCT CASE WHEN t.status NOT IN ('resolved', 'closed') THEN t.id END) as open_tickets,
                    COUNT(DISTINCT CASE WHEN sla.response_sla_status = 'met' THEN t.id END) as response_met,
                    COUNT(DISTINCT CASE WHEN sla.response_sla_status = 'breached' THEN t.id END) as response_breached,
                    COUNT(DISTINCT CASE WHEN sla.resolution_sla_status = 'met' THEN t.id END) as resolution_met,
                    COUNT(DISTINCT CASE WHEN sla.resolution_sla_status = 'breached' THEN t.id END) as resolution_breached,
                    AVG(CASE 
                        WHEN sla.first_response_at IS NOT NULL AND t.created_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, t.created_at, sla.first_response_at) 
                    END) as avg_response_minutes,
                    AVG(CASE 
                        WHEN sla.resolved_at IS NOT NULL AND t.created_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, t.created_at, sla.resolved_at) 
                    END) as avg_resolution_minutes,
                    COUNT(DISTINCT CASE WHEN t.priority = 'high' THEN t.id END) as high_tickets,
                    COUNT(DISTINCT CASE WHEN t.priority = 'medium' THEN t.id END) as medium_tickets,
                    COUNT(DISTINCT CASE WHEN t.priority = 'low' THEN t.id END) as low_tickets
                FROM tickets t
                LEFT JOIN sla_tracking sla ON t.id = sla.ticket_id
                WHERE t.assigned_to = :user_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate percentages and score
        $responseTotal = $stats['response_met'] + $stats['response_breached'];
        $resolutionTotal = $stats['resolution_met'] + $stats['resolution_breached'];
        
        $stats['response_percentage'] = $responseTotal > 0 
            ? round(($stats['response_met'] / $responseTotal) * 100, 1) : 0;
        $stats['resolution_percentage'] = $resolutionTotal > 0 
            ? round(($stats['resolution_met'] / $resolutionTotal) * 100, 1) : 0;
        
        // SLA Score: 50 pts response + 50 pts resolution
        $responseScore = $responseTotal > 0 ? ($stats['response_met'] / $responseTotal) * 50 : 0;
        $resolutionScore = $resolutionTotal > 0 ? ($stats['resolution_met'] / $resolutionTotal) * 50 : 0;
        $stats['sla_score'] = round($responseScore + $resolutionScore, 1);
        
        // Format average times
        $stats['avg_response_formatted'] = $stats['avg_response_minutes'] !== null 
            ? $this->formatMinutes($stats['avg_response_minutes']) : 'N/A';
        $stats['avg_resolution_formatted'] = $stats['avg_resolution_minutes'] !== null 
            ? $this->formatMinutes($stats['avg_resolution_minutes']) : 'N/A';
        
        return $stats;
    }
    
    /**
     * Get recent tickets assigned to the current user with SLA info
     */
    private function getMyRecentTickets() {
        $userId = $_SESSION['user_id'];
        
        $sql = "SELECT 
                    t.id, t.ticket_number, t.title, t.priority, t.status, t.created_at,
                    sla.response_sla_status, sla.resolution_sla_status,
                    sla.response_due_at, sla.resolution_due_at,
                    CASE 
                        WHEN sla.first_response_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, t.created_at, sla.first_response_at)
                        ELSE NULL 
                    END as response_time_mins,
                    CASE 
                        WHEN sla.resolved_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, t.created_at, sla.resolved_at)
                        ELSE NULL 
                    END as resolution_time_mins
                FROM tickets t
                LEFT JOIN sla_tracking sla ON t.id = sla.ticket_id
                WHERE t.assigned_to = :user_id
                ORDER BY t.created_at DESC
                LIMIT 20";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($tickets as &$ticket) {
            $ticket['response_time_formatted'] = $ticket['response_time_mins'] !== null 
                ? $this->formatMinutes($ticket['response_time_mins']) : '-';
            $ticket['resolution_time_formatted'] = $ticket['resolution_time_mins'] !== null 
                ? $this->formatMinutes($ticket['resolution_time_mins']) : '-';
        }
        
        return $tickets;
    }
    
    /**
     * Get all staff SLA performance filtered by date range
     */
    private function getStaffPerformanceByDateRange($dateFrom, $dateTo) {
        $dateToEnd = $dateTo . ' 23:59:59';
        
        $sql = "SELECT 
                    e.id,
                    CONCAT(e.fname, ' ', e.lname) as full_name,
                    e.email,
                    e.admin_rights_hdesk,
                    COUNT(DISTINCT t.id) as total_tickets,
                    COUNT(DISTINCT CASE WHEN t.status IN ('resolved', 'closed') THEN t.id END) as resolved_tickets,
                    COUNT(DISTINCT CASE WHEN sla.response_sla_status = 'met' THEN t.id END) as response_met,
                    COUNT(DISTINCT CASE WHEN sla.response_sla_status = 'breached' THEN t.id END) as response_breached,
                    COUNT(DISTINCT CASE WHEN sla.resolution_sla_status = 'met' THEN t.id END) as resolution_met,
                    COUNT(DISTINCT CASE WHEN sla.resolution_sla_status = 'breached' THEN t.id END) as resolution_breached,
                    AVG(CASE 
                        WHEN sla.first_response_at IS NOT NULL AND t.created_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, t.created_at, sla.first_response_at) 
                    END) as avg_response_minutes,
                    AVG(CASE 
                        WHEN sla.resolved_at IS NOT NULL AND t.created_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, t.created_at, sla.resolved_at) 
                    END) as avg_resolution_minutes
                FROM employees e
                LEFT JOIN tickets t ON e.id = t.assigned_to 
                    AND t.created_at >= :date_from 
                    AND t.created_at <= :date_to_end
                LEFT JOIN sla_tracking sla ON t.id = sla.ticket_id
                WHERE e.role = 'internal' 
                AND e.admin_rights_hdesk IS NOT NULL
                AND e.status = 'active'
                GROUP BY e.id, e.fname, e.lname, e.email, e.admin_rights_hdesk
                HAVING total_tickets > 0
                ORDER BY 
                    CASE 
                        WHEN COUNT(DISTINCT t.id) > 0 
                        THEN (COUNT(DISTINCT CASE WHEN sla.response_sla_status = 'met' THEN t.id END) + 
                              COUNT(DISTINCT CASE WHEN sla.resolution_sla_status = 'met' THEN t.id END)) / 
                             (COUNT(DISTINCT t.id) * 2)
                        ELSE 0 
                    END DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':date_from' => $dateFrom,
            ':date_to_end' => $dateToEnd
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate scores
        foreach ($results as &$staff) {
            $responseTotal = $staff['response_met'] + $staff['response_breached'];
            $resolutionTotal = $staff['resolution_met'] + $staff['resolution_breached'];
            
            $responseScore = $responseTotal > 0 ? ($staff['response_met'] / $responseTotal) * 50 : 0;
            $resolutionScore = $resolutionTotal > 0 ? ($staff['resolution_met'] / $resolutionTotal) * 50 : 0;
            
            $staff['sla_score'] = round($responseScore + $resolutionScore, 1);
            $staff['response_percentage'] = $responseTotal > 0 ? round(($staff['response_met'] / $responseTotal) * 100, 1) : 0;
            $staff['resolution_percentage'] = $resolutionTotal > 0 ? round(($staff['resolution_met'] / $resolutionTotal) * 100, 1) : 0;
            
            $staff['avg_response_formatted'] = $staff['avg_response_minutes'] !== null 
                ? $this->formatMinutes($staff['avg_response_minutes']) : 'N/A';
            $staff['avg_resolution_formatted'] = $staff['avg_resolution_minutes'] !== null 
                ? $this->formatMinutes($staff['avg_resolution_minutes']) : 'N/A';
        }
        
        return $results;
    }
    
    /**
     * Get overall stats filtered by date range
     */
    private function getOverallStatsByDateRange($dateFrom, $dateTo) {
        $dateToEnd = $dateTo . ' 23:59:59';
        
        $sql = "SELECT 
                    COUNT(DISTINCT t.id) as total_tickets,
                    COUNT(DISTINCT CASE WHEN t.status IN ('resolved', 'closed') THEN t.id END) as resolved_tickets,
                    COUNT(DISTINCT CASE WHEN sla.response_sla_status = 'met' THEN t.id END) as response_met,
                    COUNT(DISTINCT CASE WHEN sla.response_sla_status = 'breached' THEN t.id END) as response_breached,
                    COUNT(DISTINCT CASE WHEN sla.resolution_sla_status = 'met' THEN t.id END) as resolution_met,
                    COUNT(DISTINCT CASE WHEN sla.resolution_sla_status = 'breached' THEN t.id END) as resolution_breached,
                    AVG(CASE 
                        WHEN sla.first_response_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, t.created_at, sla.first_response_at) 
                    END) as avg_response_minutes,
                    AVG(CASE 
                        WHEN sla.resolved_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, t.created_at, sla.resolved_at) 
                    END) as avg_resolution_minutes
                FROM tickets t
                LEFT JOIN sla_tracking sla ON t.id = sla.ticket_id
                WHERE t.created_at >= :date_from
                AND t.created_at <= :date_to_end";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':date_from' => $dateFrom,
            ':date_to_end' => $dateToEnd
        ]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $responseTotal = $stats['response_met'] + $stats['response_breached'];
        $resolutionTotal = $stats['resolution_met'] + $stats['resolution_breached'];
        
        $stats['response_percentage'] = $responseTotal > 0 
            ? round(($stats['response_met'] / $responseTotal) * 100, 1) : 0;
        $stats['resolution_percentage'] = $resolutionTotal > 0 
            ? round(($stats['resolution_met'] / $resolutionTotal) * 100, 1) : 0;
            
        $stats['avg_response_formatted'] = $stats['avg_response_minutes'] !== null 
            ? $this->formatMinutes($stats['avg_response_minutes']) : 'N/A';
        $stats['avg_resolution_formatted'] = $stats['avg_resolution_minutes'] !== null 
            ? $this->formatMinutes($stats['avg_resolution_minutes']) : 'N/A';
        
        return $stats;
    }
    
    /**
     * Format minutes to readable string
     */
    private function formatMinutes($minutes) {
        if ($minutes === null) return 'N/A';
        $minutes = abs($minutes);
        if ($minutes < 60) {
            return round($minutes) . ' min';
        }
        if ($minutes < 1440) {
            $hours = floor($minutes / 60);
            $mins = round($minutes % 60);
            return $hours . 'h ' . $mins . 'm';
        }
        $days = floor($minutes / 1440);
        $hours = round(($minutes % 1440) / 60);
        return $days . 'd ' . $hours . 'h';
    }
    
    /**
     * Load view file
     */
    private function loadView($viewName, $data = []) {
        extract($data);
        $viewPath = __DIR__ . '/../../views/' . $viewName . '.view.php';
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View not found: {$viewPath}");
        }
    }
}
