<?php
/**
 * SLA Actions Handler (Admin Only)
 * Handles pause/resume actions for SLA timers
 */

require_once __DIR__ . '/../config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireAdmin(); // Only admins can pause/resume SLA

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin/tickets.php');
}

$action = $_POST['action'] ?? '';
$ticketId = (int)($_POST['ticket_id'] ?? 0);

if (!$ticketId) {
    $_SESSION['error'] = "Invalid ticket ID";
    redirect('admin/tickets.php');
}

$slaModel = new SLA();
$ticketModel = new Ticket();
$ticket = $ticketModel->findById($ticketId);

if (!$ticket) {
    $_SESSION['error'] = "Ticket not found";
    redirect('admin/tickets.php');
}

switch ($action) {
    case 'pause':
        $reason = sanitize($_POST['reason'] ?? 'Waiting for customer');
        
        if (empty($reason)) {
            $_SESSION['error'] = "Please provide a reason for pausing SLA";
            redirect('admin/view_ticket.php?id=' . $ticketId);
        }
        
        $result = $slaModel->pauseSLA($ticketId, $reason);
        
        if ($result) {
            // Log activity
            $activityModel = new TicketActivity();
            $currentUser = $auth->getCurrentUser();
            $activityModel->log([
                'ticket_id' => $ticketId,
                'user_id' => $currentUser['id'],
                'action_type' => 'sla_paused',
                'comment' => 'SLA timer paused: ' . $reason
            ]);
            
            $_SESSION['success'] = "SLA timer paused successfully";
        } else {
            $_SESSION['error'] = "Failed to pause SLA timer";
        }
        break;
        
    case 'resume':
        $result = $slaModel->resumeSLA($ticketId);
        
        if ($result) {
            // Log activity
            $activityModel = new TicketActivity();
            $currentUser = $auth->getCurrentUser();
            $activityModel->log([
                'ticket_id' => $ticketId,
                'user_id' => $currentUser['id'],
                'action_type' => 'sla_resumed',
                'comment' => 'SLA timer resumed'
            ]);
            
            $_SESSION['success'] = "SLA timer resumed successfully";
        } else {
            $_SESSION['error'] = "Failed to resume SLA timer";
        }
        break;
        
    default:
        $_SESSION['error'] = "Invalid action";
        break;
}

redirect('admin/view_ticket.php?id=' . $ticketId);
