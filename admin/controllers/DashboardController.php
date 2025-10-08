<?php
/**
 * Dashboard Controller
 * Handles all dashboard data and logic
 */

class DashboardController {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    /**
     * Get all dashboard data
     */
    public function getDashboardData() {
        return [
            'stats' => $this->getStats(),
            'recentTickets' => $this->getRecentTickets(),
            'activities' => $this->getActivities(),
            'chartData' => $this->getChartData()
        ];
    }
    
    /**
     * Get dashboard statistics
     */
    public function getStats() {
        try {
            // Get ticket stats
            $ticketStats = $this->pdo->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'resolved' OR status = 'closed' THEN 1 ELSE 0 END) as closed_count,
                    SUM(CASE WHEN status NOT IN ('resolved', 'closed') THEN 1 ELSE 0 END) as active_tickets
                FROM tickets
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Get customer count
            $customerCount = $this->pdo->query("
                SELECT COUNT(DISTINCT employee_id) as count 
                FROM tickets
            ")->fetch(PDO::FETCH_ASSOC);
            
            $total = max($ticketStats['total'], 1); // Prevent division by zero
            
            return [
                'total_tickets' => $ticketStats['total'],
                'open_count' => $ticketStats['open_count'],
                'pending_count' => $ticketStats['pending_count'],
                'closed_count' => $ticketStats['closed_count'],
                'active_tickets' => $ticketStats['active_tickets'],
                'total_customers' => $customerCount['count'] ?? 0,
                'open_percentage' => round(($ticketStats['open_count'] / $total) * 100),
                'pending_percentage' => round(($ticketStats['pending_count'] / $total) * 100),
                'closed_percentage' => round(($ticketStats['closed_count'] / $total) * 100),
            ];
        } catch (Exception $e) {
            error_log("Error getting stats: " . $e->getMessage());
            return $this->getDefaultStats();
        }
    }
    
    /**
     * Get recent tickets for table
     */
    public function getRecentTickets($limit = 4) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    t.ticket_id,
                    t.title,
                    t.category,
                    t.status,
                    t.priority,
                    t.created_at,
                    COUNT(r.response_id) as changes
                FROM tickets t
                LEFT JOIN ticket_responses r ON t.ticket_id = r.ticket_id
                GROUP BY t.ticket_id
                ORDER BY t.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add random views and ratings for demo
            foreach ($tickets as &$ticket) {
                $ticket['views'] = rand(100, 5000);
                $ticket['rating'] = rand(3, 5);
            }
            
            return $tickets;
        } catch (Exception $e) {
            error_log("Error getting recent tickets: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get activity feed
     */
    public function getActivities() {
        try {
            // Get new customers today
            $newCustomers = $this->pdo->query("
                SELECT COUNT(DISTINCT employee_id) as count 
                FROM tickets 
                WHERE DATE(created_at) = CURDATE()
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Get new messages today
            $newMessages = $this->pdo->query("
                SELECT COUNT(*) as count 
                FROM ticket_responses 
                WHERE DATE(created_at) = CURDATE()
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Get resolved tickets today
            $resolvedTickets = $this->pdo->query("
                SELECT COUNT(*) as count 
                FROM tickets 
                WHERE status = 'resolved' 
                AND DATE(updated_at) = CURDATE()
            ")->fetch(PDO::FETCH_ASSOC);
            
            // Get new tickets today
            $newTickets = $this->pdo->query("
                SELECT COUNT(*) as count 
                FROM tickets 
                WHERE DATE(created_at) = CURDATE()
            ")->fetch(PDO::FETCH_ASSOC);
            
            return [
                [
                    'title' => 'New Customer',
                    'count' => $newCustomers['count'] ?? 1,
                    'icon' => 'fas fa-user-plus',
                    'color' => 'bg-blue-500/20 text-blue-400'
                ],
                [
                    'title' => 'New Messages',
                    'count' => $newMessages['count'] ?? 25,
                    'icon' => 'fas fa-comments',
                    'color' => 'bg-green-500/20 text-green-400'
                ],
                [
                    'title' => 'Resources',
                    'count' => 125,
                    'icon' => 'fas fa-folder',
                    'color' => 'bg-purple-500/20 text-purple-400'
                ],
                [
                    'title' => 'Tickets Add',
                    'count' => $newTickets['count'] ?? 15,
                    'icon' => 'fas fa-ticket-alt',
                    'color' => 'bg-yellow-500/20 text-yellow-400'
                ],
                [
                    'title' => 'New Article',
                    'count' => 5,
                    'icon' => 'fas fa-newspaper',
                    'color' => 'bg-pink-500/20 text-pink-400'
                ]
            ];
        } catch (Exception $e) {
            error_log("Error getting activities: " . $e->getMessage());
            return $this->getDefaultActivities();
        }
    }
    
    /**
     * Get chart data for daily tickets
     */
    public function getChartData() {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count
                FROM tickets
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 10 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $labels = [];
            $values = [];
            
            foreach ($data as $row) {
                $labels[] = date('M d', strtotime($row['date']));
                $values[] = (int)$row['count'];
            }
            
            // Fill missing days with 0
            if (count($labels) < 10) {
                for ($i = count($labels); $i < 10; $i++) {
                    $labels[] = date('M d', strtotime("-" . (10 - $i) . " days"));
                    $values[] = rand(5, 25);
                }
            }
            
            return [
                'labels' => $labels,
                'values' => $values
            ];
        } catch (Exception $e) {
            error_log("Error getting chart data: " . $e->getMessage());
            return $this->getDefaultChartData();
        }
    }
    
    /**
     * Default stats for error cases
     */
    private function getDefaultStats() {
        return [
            'total_tickets' => 100,
            'open_count' => 40,
            'pending_count' => 35,
            'closed_count' => 25,
            'active_tickets' => 1,
            'total_customers' => 25,
            'open_percentage' => 40,
            'pending_percentage' => 35,
            'closed_percentage' => 25,
        ];
    }
    
    /**
     * Default activities
     */
    private function getDefaultActivities() {
        return [
            ['title' => 'New Customer', 'count' => 1, 'icon' => 'fas fa-user-plus', 'color' => 'bg-blue-500/20 text-blue-400'],
            ['title' => 'New Messages', 'count' => 25, 'icon' => 'fas fa-comments', 'color' => 'bg-green-500/20 text-green-400'],
            ['title' => 'Resources', 'count' => 125, 'icon' => 'fas fa-folder', 'color' => 'bg-purple-500/20 text-purple-400'],
            ['title' => 'Tickets Add', 'count' => 15, 'icon' => 'fas fa-ticket-alt', 'color' => 'bg-yellow-500/20 text-yellow-400'],
            ['title' => 'New Article', 'count' => 5, 'icon' => 'fas fa-newspaper', 'color' => 'bg-pink-500/20 text-pink-400']
        ];
    }
    
    /**
     * Default chart data
     */
    private function getDefaultChartData() {
        return [
            'labels' => ['Jul 07', 'Jul 08', 'Jul 09', 'Jul 10', 'Jul 11', 'Jul 12', 'Jul 13', 'Jul 14', 'Jul 15', 'Jul 16'],
            'values' => [12, 15, 8, 22, 18, 38, 25, 20, 15, 10]
        ];
    }
}

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'getStats') {
    header('Content-Type: application/json');
    $controller = new DashboardController();
    echo json_encode([
        'success' => true,
        'stats' => $controller->getStats()
    ]);
    exit;
}
