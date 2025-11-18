<?php
/**
 * Customer View Ticket Controller
 * Handles viewing individual ticket details and updates
 */

class CustomerViewTicketController {
    private $auth;
    private $ticketModel;
    private $userModel;
    private $activityModel;
    private $currentUser;
    private $isITStaff;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireLogin();
        
        // Ensure only employees can access
        if ($_SESSION['user_type'] !== 'employee') {
            header("Location: " . BASE_URL . "admin/dashboard.php");
            exit();
        }
        
        $this->ticketModel = new Ticket();
        $this->userModel = new User();
        $this->activityModel = new TicketActivity();
        $this->currentUser = $this->auth->getCurrentUser();
        $this->isITStaff = $this->currentUser['role'] === 'it_staff' || $this->currentUser['role'] === 'admin';
    }
    
    /**
     * Display ticket details
     */
    public function index() {
        $ticketId = $_GET['id'] ?? 0;
        $ticket = $this->ticketModel->findById($ticketId);
        
        if (!$ticket) {
            $_SESSION['error'] = "Ticket not found.";
            header("Location: tickets.php");
            exit();
        }
        
        // Check permission - employees can only view their own tickets
        if (!$this->isITStaff && $ticket['submitter_id'] != $this->currentUser['id']) {
            $_SESSION['error'] = "You don't have permission to view this ticket.";
            header("Location: tickets.php");
            exit();
        }
        
        // Get activity log
        $activities = $this->activityModel->getByTicketId($ticketId);
        
        // Get IT staff for assignment (if IT staff viewing)
        $itStaff = [];
        if ($this->isITStaff) {
            $itStaff = $this->userModel->getITStaff();
        }
        
        // Pass data to view
        $currentUser = $this->currentUser;
        $isITStaff = $this->isITStaff;
        
        // Load view
        $this->loadView('customer/view_ticket', compact('currentUser', 'isITStaff', 'ticket', 'activities', 'itStaff'));
    }
    
    /**
     * Handle ticket updates (IT staff only)
     */
    public function update() {
        if (!$this->isITStaff) {
            $_SESSION['error'] = "You don't have permission to update tickets.";
            header("Location: tickets.php");
            exit();
        }
        
        $ticketId = $_POST['ticket_id'] ?? 0;
        $ticket = $this->ticketModel->findById($ticketId);
        
        if (!$ticket) {
            $_SESSION['error'] = "Ticket not found.";
            header("Location: tickets.php");
            exit();
        }
        
        $updateData = [];
        $oldTicket = $ticket;
        
        // Handle status change
        if (isset($_POST['status']) && $_POST['status'] !== $ticket['status']) {
            $updateData['status'] = sanitize($_POST['status']);
            
            // Log activity
            $this->activityModel->log([
                'ticket_id' => $ticketId,
                'user_id' => $this->currentUser['id'],
                'action_type' => 'status_change',
                'old_value' => $oldTicket['status'],
                'new_value' => $updateData['status'],
                'comment' => 'Status changed from ' . $oldTicket['status'] . ' to ' . $updateData['status']
            ]);
            
            // Send notification
            try {
                $mailer = new Mailer();
                $submitter = $this->userModel->findById($ticket['submitter_id']);
                $updatedTicket = array_merge($ticket, $updateData);
                $mailer->sendTicketStatusUpdate($updatedTicket, $submitter, $oldTicket['status'], $updateData['status']);
            } catch (Exception $e) {
                error_log("Failed to send email: " . $e->getMessage());
            }
        }
        
        // Handle assignment change
        if (isset($_POST['assigned_to']) && $_POST['assigned_to'] !== $ticket['assigned_to']) {
            $updateData['assigned_to'] = $_POST['assigned_to'] ? (int)$_POST['assigned_to'] : null;
            
            if ($updateData['assigned_to']) {
                $assignee = $this->userModel->findById($updateData['assigned_to']);
                
                // Log activity
                $this->activityModel->log([
                    'ticket_id' => $ticketId,
                    'user_id' => $this->currentUser['id'],
                    'action_type' => 'assigned',
                    'new_value' => $assignee['full_name'],
                    'comment' => 'Ticket assigned to ' . $assignee['full_name']
                ]);
                
                // Send notification
                try {
                    $mailer = new Mailer();
                    $updatedTicket = array_merge($ticket, $updateData);
                    $mailer->sendTicketAssigned($updatedTicket, $assignee);
                } catch (Exception $e) {
                    error_log("Failed to send email: " . $e->getMessage());
                }
            }
        }
        
        // Handle resolution
        if (isset($_POST['resolution']) && !empty($_POST['resolution'])) {
            $updateData['resolution'] = sanitize($_POST['resolution']);
            
            // Log activity
            $this->activityModel->log([
                'ticket_id' => $ticketId,
                'user_id' => $this->currentUser['id'],
                'action_type' => 'resolved',
                'comment' => 'Resolution added'
            ]);
        }
        
        // Update ticket
        if (!empty($updateData)) {
            $updateData['updated_at'] = date('Y-m-d H:i:s');
            $this->ticketModel->update($ticketId, $updateData);
            $_SESSION['success'] = "Ticket updated successfully!";
        }
        
        header("Location: view_ticket.php?id=" . $ticketId);
        exit();
    }
    
    /**
     * Load a view file
     */
    private function loadView($viewName, $data = []) {
        // Extract data array to variables
        extract($data);
        
        // Include the view file
        $viewPath = __DIR__ . '/../../views/' . $viewName . '.view.php';
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View not found: {$viewPath}");
        }
    }
}
