<?php
/**
 * SLA Performance Controller
 * Displays SLA scores and performance metrics for IT staff
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
     * Display SLA performance dashboard
     */
    public function index() {
        // Get IT staff performance metrics
        $staffPerformance = $this->getStaffPerformance();
        
        // Get overall statistics
        $overallStats = $this->getOverallStats();
        
        // Get recent SLA breaches
        $recentBreaches = $this->getRecentBreaches();
        
        // Pass data to view
        $currentUser = $this->currentUser;
        
        $this->loadView('admin/sla_performance', compact(
            'currentUser',
            'staffPerformance',
            'overallStats',
            'recentBreaches'
        ));
    }
    
    /**
     * Get performance metrics for each IT staff member
     */
    private function getStaffPerformance() {
        $sql = "SELECT 
                    u.id,
                    u.full_name,
                    u.email,
                    COUNT(DISTINCT t.id) as total_tickets,
                    COUNT(DISTINCT CASE WHEN t.status IN ('resolved', 'closed') THEN t.id END) as resolved_tickets,
                    COUNT(DISTINCT CASE WHEN sla.first_response_sla_met = 1 THEN t.id END) as response_sla_met,
                    COUNT(DISTINCT CASE WHEN sla.first_response_sla_met = 0 AND sla.first_response_at IS NOT NULL THEN t.id END) as response_sla_breached,
                    COUNT(DISTINCT CASE WHEN sla.resolution_sla_met = 1 THEN t.id END) as resolution_sla_met,
                    COUNT(DISTINCT CASE WHEN sla.resolution_sla_met = 0 AND sla.resolved_at IS NOT NULL THEN t.id END) as resolution_sla_breached,
                    AVG(CASE 
                        WHEN sla.first_response_at IS NOT NULL AND t.created_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, t.created_at, sla.first_response_at) 
                    END) as avg_response_time_minutes,
                    AVG(CASE 
                        WHEN sla.resolved_at IS NOT NULL AND t.created_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(HOUR, t.created_at, sla.resolved_at) 
                    END) as avg_resolution_time_hours
                FROM users u
                LEFT JOIN tickets t ON u.id = t.assigned_to
                LEFT JOIN sla_tracking sla ON t.id = sla.ticket_id
                WHERE u.role IN ('it_staff', 'admin')
                AND (t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) OR t.id IS NULL)
                GROUP BY u.id, u.full_name, u.email
                ORDER BY 
                    CASE 
                        WHEN COUNT(DISTINCT t.id) > 0 
                        THEN (COUNT(DISTINCT CASE WHEN sla.first_response_sla_met = 1 THEN t.id END) + 
                              COUNT(DISTINCT CASE WHEN sla.resolution_sla_met = 1 THEN t.id END)) / 
                             (COUNT(DISTINCT t.id) * 2)
                        ELSE 0 
                    END DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate SLA scores for each staff member
        foreach ($results as &$staff) {
            $totalTickets = $staff['total_tickets'];
            
            if ($totalTickets > 0) {
                // Response SLA Score (0-50 points)
                $responseMet = $staff['response_sla_met'];
                $responseBreached = $staff['response_sla_breached'];
                $responseTotal = $responseMet + $responseBreached;
                $responseScore = $responseTotal > 0 ? ($responseMet / $responseTotal) * 50 : 0;
                
                // Resolution SLA Score (0-50 points)
                $resolutionMet = $staff['resolution_sla_met'];
                $resolutionBreached = $staff['resolution_sla_breached'];
                $resolutionTotal = $resolutionMet + $resolutionBreached;
                $resolutionScore = $resolutionTotal > 0 ? ($resolutionMet / $resolutionTotal) * 50 : 0;
                
                // Overall SLA Score (0-100)
                $staff['sla_score'] = round($responseScore + $resolutionScore, 1);
                $staff['response_sla_percentage'] = $responseTotal > 0 ? round(($responseMet / $responseTotal) * 100, 1) : 0;
                $staff['resolution_sla_percentage'] = $resolutionTotal > 0 ? round(($resolutionMet / $resolutionTotal) * 100, 1) : 0;
            } else {
                $staff['sla_score'] = 0;
                $staff['response_sla_percentage'] = 0;
                $staff['resolution_sla_percentage'] = 0;
            }
            
            // Format times
            $staff['avg_response_time'] = $staff['avg_response_time_minutes'] 
                ? $this->formatMinutes($staff['avg_response_time_minutes']) 
                : 'N/A';
            $staff['avg_resolution_time'] = $staff['avg_resolution_time_hours'] 
                ? $this->formatHours($staff['avg_resolution_time_hours']) 
                : 'N/A';
        }
        
        return $results;
    }
    
    /**
     * Get overall SLA statistics
     */
    private function getOverallStats() {
        $sql = "SELECT 
                    COUNT(DISTINCT t.id) as total_tickets,
                    COUNT(DISTINCT CASE WHEN sla.first_response_sla_met = 1 THEN t.id END) as response_met,
                    COUNT(DISTINCT CASE WHEN sla.first_response_sla_met = 0 AND sla.first_response_at IS NOT NULL THEN t.id END) as response_breached,
                    COUNT(DISTINCT CASE WHEN sla.resolution_sla_met = 1 THEN t.id END) as resolution_met,
                    COUNT(DISTINCT CASE WHEN sla.resolution_sla_met = 0 AND sla.resolved_at IS NOT NULL THEN t.id END) as resolution_breached,
                    AVG(CASE 
                        WHEN sla.first_response_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, t.created_at, sla.first_response_at) 
                    END) as avg_response_minutes,
                    AVG(CASE 
                        WHEN sla.resolved_at IS NOT NULL 
                        THEN TIMESTAMPDIFF(HOUR, t.created_at, sla.resolved_at) 
                    END) as avg_resolution_hours
                FROM tickets t
                LEFT JOIN sla_tracking sla ON t.id = sla.ticket_id
                WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate percentages
        $responseTotal = $stats['response_met'] + $stats['response_breached'];
        $resolutionTotal = $stats['resolution_met'] + $stats['resolution_breached'];
        
        $stats['response_percentage'] = $responseTotal > 0 
            ? round(($stats['response_met'] / $responseTotal) * 100, 1) 
            : 0;
        $stats['resolution_percentage'] = $resolutionTotal > 0 
            ? round(($stats['resolution_met'] / $resolutionTotal) * 100, 1) 
            : 0;
        
        return $stats;
    }
    
    /**
     * Get recent SLA breaches
     */
    private function getRecentBreaches() {
        $sql = "SELECT 
                    t.id,
                    t.ticket_number,
                    t.title,
                    t.priority,
                    t.status,
                    t.created_at,
                    u.full_name as assigned_to_name,
                    sla.first_response_sla_met,
                    sla.resolution_sla_met,
                    sla.first_response_at,
                    sla.resolved_at
                FROM tickets t
                JOIN sla_tracking sla ON t.id = sla.ticket_id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE (sla.first_response_sla_met = 0 OR sla.resolution_sla_met = 0)
                AND t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                ORDER BY t.created_at DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Format minutes to readable string
     */
    private function formatMinutes($minutes) {
        if ($minutes < 60) {
            return round($minutes) . ' min';
        }
        $hours = floor($minutes / 60);
        $mins = round($minutes % 60);
        return $hours . 'h ' . $mins . 'm';
    }
    
    /**
     * Format hours to readable string
     */
    private function formatHours($hours) {
        if ($hours < 24) {
            return round($hours, 1) . ' hours';
        }
        $days = floor($hours / 24);
        $hrs = round($hours % 24);
        return $days . 'd ' . $hrs . 'h';
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
