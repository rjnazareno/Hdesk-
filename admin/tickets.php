<?php
require_once __DIR__ . '/../config/config.php';

// Handle ticket assignment from pool view
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action']) && $_GET['action'] === 'assign') {
    require_once __DIR__ . '/../includes/Auth.php';
    require_once __DIR__ . '/../models/Ticket.php';
    require_once __DIR__ . '/../models/TicketActivity.php';
    
    $auth = new Auth();
    $auth->requireLogin();
    $auth->requireITStaff();
    
    $currentUser = $auth->getCurrentUser();
    $ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
    $assignedTo = isset($_POST['assigned_to']) ? $_POST['assigned_to'] : '';
    
    if ($ticketId && $assignedTo) {
        // Parse assigned_to: "user_123" or "emp_456"
        $parts = explode('_', $assignedTo);
        if (count($parts) === 2) {
            $type = $parts[0] === 'user' ? 'user' : 'employee';
            $id = (int)$parts[1];
            
            $ticketModel = new Ticket();
            $activityModel = new TicketActivity();
            
            // Update ticket assignment
            $ticketModel->update($ticketId, [
                'assigned_to' => $id,
                'assignee_type' => $type,
                'status' => 'open',
                'grabbed_by' => $id
            ]);
            
            // Log activity
            $activityModel->log([
                'ticket_id' => $ticketId,
                'user_id' => $currentUser['id'],
                'action_type' => 'assigned',
                'new_value' => $id,
                'comment' => 'Ticket assigned from pool by ' . $currentUser['full_name']
            ]);
        }
    }
    
    $fromView = isset($_GET['from']) ? $_GET['from'] : 'pool';
    header('Location: tickets.php?view=' . urlencode($fromView));
    exit;
}

require_once __DIR__ . '/../controllers/admin/TicketsController.php';

$controller = new TicketsController();
$controller->index();