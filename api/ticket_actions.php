<?php
/**
 * Ticket Actions API
 * Handles grab, release, and other ticket actions
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$auth = new Auth();

// Require login
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only IT staff/admin can use these actions
if (!isITStaff()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$ticketModel = new Ticket();
$activityModel = new TicketActivity();
$currentUser = $auth->getCurrentUser();

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$ticketId = (int)($_POST['ticket_id'] ?? $_GET['ticket_id'] ?? 0);

if (!$ticketId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
    exit;
}

switch ($action) {
    case 'grab':
        handleGrabTicket($ticketModel, $activityModel, $ticketId, $currentUser);
        break;
        
    case 'release':
        handleReleaseTicket($ticketModel, $activityModel, $ticketId, $currentUser);
        break;
        
    case 'get_queue':
        handleGetQueue($ticketModel, $currentUser);
        break;
        
    case 'get_my_tickets':
        handleGetMyTickets($ticketModel, $currentUser);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}

/**
 * Handle grab ticket action
 */
function handleGrabTicket($ticketModel, $activityModel, $ticketId, $currentUser) {
    // Get ticket details first
    $ticket = $ticketModel->findById($ticketId);
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        return;
    }
    
    // Check if ticket is available
    if ($ticket['grabbed_by'] !== null) {
        echo json_encode([
            'success' => false, 
            'error' => 'Ticket already grabbed by ' . ($ticket['grabbed_by_name'] ?? 'another staff member')
        ]);
        return;
    }
    
    if (!in_array($ticket['status'], ['pending', 'open'])) {
        echo json_encode([
            'success' => false, 
            'error' => 'Only pending or open tickets can be grabbed'
        ]);
        return;
    }
    
    // Determine assignee type based on user type
    $assigneeType = ($_SESSION['user_type'] ?? 'user') === 'employee' ? 'employee' : 'user';
    
    // Attempt to grab
    $result = $ticketModel->grabTicket($ticketId, $currentUser['id'], $assigneeType);
    
    if ($result) {
        // Log activity
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'grabbed',
            'new_value' => $currentUser['full_name'],
            'comment' => 'Ticket grabbed from queue'
        ]);
        
        // Create notification for submitter
        try {
            $db = Database::getInstance()->getConnection();
            $notificationModel = new Notification($db);
            
            if ($ticket['submitter_type'] === 'employee') {
                $notificationModel->create([
                    'user_id' => null,
                    'employee_id' => $ticket['submitter_id'],
                    'type' => 'ticket_assigned',
                    'title' => 'Your Request is Being Handled',
                    'message' => "{$currentUser['full_name']} is now working on your request #{$ticket['ticket_number']}",
                    'ticket_id' => $ticketId,
                    'related_user_id' => $currentUser['id']
                ]);
            }
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Ticket grabbed successfully',
            'ticket_number' => $ticket['ticket_number']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Failed to grab ticket. It may have been grabbed by someone else.'
        ]);
    }
}

/**
 * Handle release ticket action
 */
function handleReleaseTicket($ticketModel, $activityModel, $ticketId, $currentUser) {
    // Get ticket details first
    $ticket = $ticketModel->findById($ticketId);
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        return;
    }
    
    // Check if current user grabbed this ticket
    if ($ticket['grabbed_by'] != $currentUser['id']) {
        echo json_encode([
            'success' => false, 
            'error' => 'You can only release tickets you have grabbed'
        ]);
        return;
    }
    
    // Cannot release tickets that are in progress or beyond
    if (!in_array($ticket['status'], ['pending', 'open'])) {
        echo json_encode([
            'success' => false, 
            'error' => 'Cannot release tickets that are already in progress'
        ]);
        return;
    }
    
    // Attempt to release
    $result = $ticketModel->releaseTicket($ticketId, $currentUser['id']);
    
    if ($result) {
        // Log activity
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'released',
            'old_value' => $currentUser['full_name'],
            'new_value' => 'Unassigned',
            'comment' => 'Ticket released back to queue'
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Ticket released back to queue',
            'ticket_number' => $ticket['ticket_number']
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'error' => 'Failed to release ticket'
        ]);
    }
}

/**
 * Get ticket queue for user's department
 */
function handleGetQueue($ticketModel, $currentUser) {
    $departmentId = $currentUser['department_id'] ?? null;
    
    if (!$departmentId) {
        // If no department assigned, get all tickets
        $tickets = $ticketModel->getAll([
            'status' => 'pending'
        ], 'created_at', 'ASC');
    } else {
        $tickets = $ticketModel->getQueue($departmentId);
    }
    
    echo json_encode([
        'success' => true,
        'tickets' => $tickets,
        'count' => count($tickets)
    ]);
}

/**
 * Get tickets grabbed by current user
 */
function handleGetMyTickets($ticketModel, $currentUser) {
    $tickets = $ticketModel->getMyTickets($currentUser['id']);
    
    echo json_encode([
        'success' => true,
        'tickets' => $tickets,
        'count' => count($tickets)
    ]);
}
