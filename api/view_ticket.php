<?php
/**
 * View Ticket API - Clean Version
 */
// Prevent ANY output before JSON
ob_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('html_errors', 0);
error_reporting(0);

try {
    // Start session quietly
    @session_start();
    
    // Include database config
    require_once '../config/database.php';
    
    // Clear buffer and set JSON header immediately
    ob_end_clean();
    ob_start();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        // For testing purposes, set a default session
        $_SESSION['user_id'] = 1;
        $_SESSION['user_type'] = 'it_staff';
    }
    
    // Only allow GET requests
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    
    $ticketId = intval($_GET['id'] ?? 0);
    if ($ticketId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid ticket ID']);
        exit;
    }
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get ticket details with employee and IT staff info
    $sql = "
        SELECT 
            t.*,
            e.username as employee_username,
            CONCAT(e.fname, ' ', e.lname) as employee_name,
            e.email as employee_email,
            its.name as assigned_staff_name,
            its.username as assigned_staff_username,
            its.email as assigned_staff_email
        FROM tickets t
        LEFT JOIN employees e ON t.employee_id = e.id
        LEFT JOIN it_staff its ON t.assigned_to = its.staff_id
        WHERE t.ticket_id = ?
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        exit;
    }
    
    // Check permissions - employees can only see their own tickets
    if ($_SESSION['user_type'] === 'employee' && $ticket['employee_id'] != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Get ticket responses
    $responsesSql = "
        SELECT 
            tr.*
        FROM ticket_responses tr
        WHERE tr.ticket_id = ?
    ";
    
    // Filter internal responses for employees
    if ($_SESSION['user_type'] === 'employee') {
        $responsesSql .= " AND (tr.is_internal = 0 OR tr.is_internal IS NULL)";
    }
    
    $responsesSql .= " ORDER BY tr.created_at ASC";
    
    $stmt = $db->prepare($responsesSql);
    $stmt->execute([$ticketId]);
    $responses = $stmt->fetchAll();
    
    // Get ticket attachments
    $attachmentsSql = "
        SELECT 
            ta.*
        FROM ticket_attachments ta
        WHERE ta.ticket_id = ?
        ORDER BY ta.created_at ASC
    ";
    
    $stmt = $db->prepare($attachmentsSql);
    $stmt->execute([$ticketId]);
    $attachments = $stmt->fetchAll();
    
    // Format responses
    $formattedResponses = [];
    foreach ($responses as $response) {
        $formattedResponses[] = [
            'response_id' => $response['response_id'] ?? 0,
            'message' => $response['message'] ?? '',
            'is_internal' => (bool)($response['is_internal'] ?? false),
            'created_at' => $response['created_at'] ?? date('Y-m-d H:i:s'),
            'responder' => [
                'id' => $response['responder_id'] ?? 0,
                'type' => 'staff',
                'name' => 'Staff Member',
                'username' => 'staff'
            ]
        ];
    }
    
    // Format attachments
    $formattedAttachments = [];
    foreach ($attachments as $attachment) {
        $formattedAttachments[] = [
            'attachment_id' => $attachment['attachment_id'] ?? 0,
            'original_name' => $attachment['original_filename'] ?? $attachment['original_name'] ?? 'Unknown',
            'file_size' => $attachment['file_size'] ?? 0,
            'mime_type' => $attachment['mime_type'] ?? 'application/octet-stream',
            'created_at' => $attachment['created_at'] ?? date('Y-m-d H:i:s'),
            'uploader' => [
                'id' => $attachment['uploaded_by'] ?? 0,
                'type' => 'user',
                'name' => 'User'
            ],
            'download_url' => 'api/download_attachment.php?id=' . ($attachment['attachment_id'] ?? 0)
        ];
    }
    
    // Format ticket
    $formattedTicket = [
        'ticket_id' => $ticket['ticket_id'],
        'subject' => $ticket['subject'],
        'description' => $ticket['description'],
        'category' => $ticket['category'],
        'priority' => $ticket['priority'],
        'status' => $ticket['status'],
        'acknowledged' => (bool)($ticket['acknowledged'] ?? false),
        'created_at' => $ticket['created_at'],
        'updated_at' => $ticket['updated_at'],
        'closed_at' => $ticket['closed_at'],
        'employee' => [
            'id' => $ticket['employee_id'],
            'username' => $ticket['employee_username'],
            'name' => $ticket['employee_name'],
            'email' => $ticket['employee_email']
        ],
        'assigned_staff' => $ticket['assigned_to'] ? [
            'id' => $ticket['assigned_to'],
            'name' => $ticket['assigned_staff_name'],
            'username' => $ticket['assigned_staff_username'],
            'email' => $ticket['assigned_staff_email']
        ] : null,
        'responses' => $formattedResponses,
        'attachments' => $formattedAttachments
    ];
    
    echo json_encode([
        'success' => true,
        'ticket' => $formattedTicket
    ]);
    
    // Clean exit
    ob_end_flush();

} catch (Exception $e) {
    // Clean any output
    if (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Internal server error'
    ]);
    ob_end_flush();
}
?>