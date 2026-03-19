<?php
/**
 * Customer View Ticket Entry Point
 */

require_once __DIR__ . '/../config/config.php';

// Handle reply POST before controller
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_message']) && !empty(trim($_POST['reply_message']))) {
    $auth = new Auth();
    $auth->requireLogin();
    $currentUser = $auth->getCurrentUser();
    $isITStaff = $currentUser['role'] === 'it_staff' || $currentUser['role'] === 'admin' || $currentUser['role'] === 'internal';

    $ticketId = $_POST['ticket_id'] ?? ($_GET['id'] ?? 0);
    $replyMsg = sanitize(trim($_POST['reply_message']));
    $userType = ($_SESSION['user_type'] ?? 'employee');

    $replyModel = new TicketReply();
    $replyModel->create([
        'ticket_id' => $ticketId,
        'user_id' => $currentUser['id'],
        'user_type' => $userType,
        'message' => $replyMsg
    ]);

    // Auto-change status to in_progress and record first response when admin replies to pending ticket
    $ticketModel = new Ticket();
    $ticket = $ticketModel->findById($ticketId);

    if ($isITStaff && $ticket && $ticket['status'] === 'pending') {
        $ticketModel->update($ticketId, ['status' => 'in_progress']);

        $slaModel = new SLA();
        $slaModel->recordFirstResponse($ticketId);

        $activityModel = new TicketActivity();
        $activityModel->log([
            'ticket_id' => $ticketId,
            'user_id' => $currentUser['id'],
            'action_type' => 'status_change',
            'old_value' => 'pending',
            'new_value' => 'in_progress',
            'comment' => 'Status automatically changed to in_progress (admin reply)'
        ]);
    }

    // Log activity
    $activityModel = new TicketActivity();
    $activityModel->log([
        'ticket_id' => $ticketId,
        'user_id' => $currentUser['id'],
        'action_type' => 'reply',
        'comment' => 'Customer replied to ticket'
    ]);

    // Notify assigned staff
    try {
        if (!isset($ticketModel)) {
            $ticketModel = new Ticket();
        }
        if (!isset($ticket)) {
            $ticket = $ticketModel->findById($ticketId);
        }
        if ($ticket && $ticket['assigned_to']) {
            $db = Database::getInstance()->getConnection();
            $notificationModel = new Notification($db);
            $notificationModel->create([
                'user_id' => $ticket['assignee_type'] === 'user' ? $ticket['assigned_to'] : null,
                'employee_id' => $ticket['assignee_type'] === 'employee' ? $ticket['assigned_to'] : null,
                'type' => 'ticket_reply',
                'title' => 'New Customer Reply',
                'message' => "Customer replied on ticket #{$ticket['ticket_number']}",
                'ticket_id' => $ticketId,
                'related_user_id' => $currentUser['id']
            ]);
        }
    } catch (Exception $e) { error_log("Reply notification error: " . $e->getMessage()); }

    header("Location: view_ticket.php?id=" . $ticketId . "&success=reply");
    exit();
}

$controller = new CustomerViewTicketController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->update();
} else {
    $controller->index();
}
