<?php
/**
 * Clean Get Tickets API - Fixed Version
 */
// Prevent any output before JSON
ini_set('display_errors', 0);
ini_set('html_errors', 0);
error_reporting(0);
ob_start();

try {
    session_start();
    require_once '../config/database.php';
    
    // Clear buffer and set JSON header
    ob_clean();
    header('Content-Type: application/json');
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get parameters
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 10)));
    $status = $_GET['status'] ?? '';
    $category = $_GET['category'] ?? '';
    
    // Build WHERE clause
    $whereConditions = [];
    $params = [];
    
    // User-specific filters
    if ($_SESSION['user_type'] === 'employee') {
        $whereConditions[] = "t.employee_id = ?";
        $params[] = $_SESSION['user_id'];
    }
    // IT staff can see all tickets
    
    // Status filter
    if (!empty($status)) {
        $whereConditions[] = "t.status = ?";
        $params[] = $status;
    }
    
    // Category filter
    if (!empty($category)) {
        $whereConditions[] = "t.category = ?";
        $params[] = $category;
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    }
    
    // Count total tickets
    $countSql = "SELECT COUNT(*) as total FROM tickets t $whereClause";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $totalCount = $stmt->fetch()['total'];
    
    // Calculate pagination
    $totalPages = ceil($totalCount / $limit);
    $offset = ($page - 1) * $limit;
    
    // Get tickets with basic info
    $sql = "
        SELECT 
            t.ticket_id,
            t.subject,
            t.description,
            t.category,
            t.priority,
            t.status,
            t.created_at,
            t.updated_at,
            t.employee_id,
            t.assigned_to,
            e.username as employee_username,
            CONCAT(e.fname, ' ', e.lname) as employee_name,
            its.name as assigned_staff_name,
            (SELECT COUNT(*) FROM ticket_responses tr WHERE tr.ticket_id = t.ticket_id) as response_count,
            (SELECT COUNT(*) FROM ticket_attachments ta WHERE ta.ticket_id = t.ticket_id) as attachment_count
        FROM tickets t
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN it_staff its ON t.assigned_to = its.staff_id
        $whereClause
        ORDER BY 
            CASE t.priority 
                WHEN 'Critical' THEN 1 
                WHEN 'High' THEN 2 
                WHEN 'Medium' THEN 3 
                WHEN 'Low' THEN 4 
            END,
            t.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    // Add limit and offset to parameters
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
    
    // Format tickets for response
    $formattedTickets = [];
    foreach ($tickets as $ticket) {
        $formattedTickets[] = [
            'id' => $ticket['ticket_id'],
            'subject' => $ticket['subject'],
            'description' => $ticket['description'],
            'category' => $ticket['category'],
            'priority' => $ticket['priority'],
            'status' => $ticket['status'],
            'created_at' => $ticket['created_at'],
            'updated_at' => $ticket['updated_at'],
            'response_count' => intval($ticket['response_count']),
            'attachment_count' => intval($ticket['attachment_count']),
            'employee' => [
                'id' => $ticket['employee_id'],
                'username' => $ticket['employee_username'],
                'name' => $ticket['employee_name']
            ],
            'assigned_staff' => [
                'id' => $ticket['assigned_to'],
                'name' => $ticket['assigned_staff_name']
            ]
        ];
    }
    
    // Return JSON response
    $response = [
        'success' => true,
        'tickets' => $formattedTickets,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'limit' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    error_log("Get tickets API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>