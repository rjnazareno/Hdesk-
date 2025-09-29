<?php
/**
 * Acknowledge Ticket API Endpoint
 * For employees to acknowledge resolved tickets
 */
require_once '../includes/auth.php';
require_once '../includes/security.php';
require_once '../config/database.php';

// Handle both GET (direct link from email) and POST requests
$ticketId = 0;
$fromEmail = false;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $ticketId = intval($_GET['id'] ?? 0);
    $token = $_GET['token'] ?? '';
    $fromEmail = !empty($token);
    
    // For email links, we need to validate the token instead of requiring login
    if ($fromEmail) {
        // Simple token validation - in production, use more secure tokens
        $expectedToken = md5($ticketId . 'acknowledge_token_salt');
        if ($token !== $expectedToken) {
            http_response_code(403);
            if (isset($_GET['format']) && $_GET['format'] === 'json') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid token']);
            } else {
                echo '<h1>Invalid Token</h1><p>The acknowledgment link is invalid or expired.</p>';
            }
            exit;
        }
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in for POST requests
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Validate CSRF token for POST requests
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid security token']);
        exit;
    }
    
    $ticketId = intval($_POST['ticket_id'] ?? 0);
} else {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    if ($ticketId <= 0) {
        throw new Exception('Invalid ticket ID');
    }
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get ticket details
    $stmt = $db->prepare("
        SELECT t.*, e.username as employee_username, e.email as employee_email
        FROM tickets t
        LEFT JOIN employees e ON t.employee_id = e.id
        WHERE t.ticket_id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        throw new Exception('Ticket not found');
    }
    
    // For logged-in users, check if they own the ticket
    if (!$fromEmail && $auth->isLoggedIn()) {
        if ($auth->isEmployee() && $ticket['employee_id'] != $auth->getUserId()) {
            throw new Exception('Access denied');
        }
    }
    
    // Check if ticket is in resolved status
    if ($ticket['status'] !== 'resolved') {
        throw new Exception('Ticket is not in resolved status');
    }
    
    // Check if already acknowledged
    if ($ticket['acknowledged']) {
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['format'])) {
            echo '<h1>Already Acknowledged</h1><p>This ticket has already been acknowledged.</p>';
            exit;
        } else {
            throw new Exception('Ticket has already been acknowledged');
        }
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Update ticket status to closed and set acknowledged
    $stmt = $db->prepare("
        UPDATE tickets 
        SET status = 'closed', acknowledged = 1, closed_at = CURRENT_TIMESTAMP
        WHERE ticket_id = ?
    ");
    $stmt->execute([$ticketId]);
    
    // Add acknowledgment response
    $acknowledgeMessage = "Ticket acknowledged and closed by employee";
    $stmt = $db->prepare("
        INSERT INTO ticket_responses (ticket_id, responder_id, responder_type, message)
        VALUES (?, ?, 'employee', ?)
    ");
    $stmt->execute([$ticketId, $ticket['employee_id'], $acknowledgeMessage]);
    
    // Commit transaction
    $db->commit();
    
    // Log acknowledgment
    error_log("Ticket {$ticketId} acknowledged by employee {$ticket['employee_id']}");
    
    // Handle response based on request type
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['format'])) {
        // HTML response for email links
        echo '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Ticket Acknowledged</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-50 flex items-center justify-center min-h-screen">
            <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
                <div class="text-green-600 text-6xl mb-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Ticket Acknowledged</h1>
                <p class="text-gray-600 mb-6">
                    Thank you! Ticket #' . $ticketId . ' has been acknowledged and closed.
                </p>
                <p class="text-sm text-gray-500">
                    Subject: ' . htmlspecialchars($ticket['subject']) . '
                </p>
            </div>
        </body>
        </html>';
    } else {
        // JSON response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Ticket acknowledged successfully',
            'ticket_id' => $ticketId
        ]);
    }

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("Acknowledge ticket error: " . $e->getMessage());
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['format'])) {
        // HTML error response
        echo '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Error</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-gray-50 flex items-center justify-center min-h-screen">
            <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
                <div class="text-red-600 text-6xl mb-4">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-4">Error</h1>
                <p class="text-gray-600">
                    ' . htmlspecialchars($e->getMessage()) . '
                </p>
            </div>
        </body>
        </html>';
    } else {
        // JSON error response
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>