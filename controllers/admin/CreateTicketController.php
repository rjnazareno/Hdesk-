<?php
/**
 * Create Ticket Controller (Admin Side)
 * Allows admins to create tickets on behalf of employees
 */

class CreateTicketController {
    private $auth;
    private $ticketModel;
    private $categoryModel;
    private $employeeModel;
    private $activityModel;
    private $slaModel;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireITStaff();
        
        $this->ticketModel = new Ticket();
        $this->categoryModel = new Category();
        $this->employeeModel = new Employee();
        $this->activityModel = new TicketActivity();
        $this->slaModel = new SLA();
    }
    
    /**
     * Show create ticket form
     */
    public function index() {
        $currentUser = $this->auth->getCurrentUser();
        $categories = $this->categoryModel->getAll();
        $employees = $this->employeeModel->getAll('active');
        
        // Load view
        $this->loadView('admin/create_ticket', compact('currentUser', 'categories', 'employees'));
    }
    
    /**
     * Handle ticket creation
     */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('admin/create_ticket.php');
        }
        
        $currentUser = $this->auth->getCurrentUser();
        
        // Validate required fields
        if (empty($_POST['submitter_id']) || $_POST['submitter_id'] === '0') {
            error_log("Ticket creation failed: No employee selected");
            $_SESSION['error'] = "Please select an employee";
            redirect('admin/create_ticket.php');
        }
        
        if (empty($_POST['category_id']) || $_POST['category_id'] === '0') {
            error_log("Ticket creation failed: No category selected");
            $_SESSION['error'] = "Please select a category";
            redirect('admin/create_ticket.php');
        }
        
        if (empty($_POST['title'])) {
            error_log("Ticket creation failed: No title provided");
            $_SESSION['error'] = "Please enter a ticket title";
            redirect('admin/create_ticket.php');
        }
        
        if (empty($_POST['description'])) {
            error_log("Ticket creation failed: No description provided");
            $_SESSION['error'] = "Please enter a ticket description";
            redirect('admin/create_ticket.php');
        }
        
        // Generate unique ticket number
        do {
            $ticketNumber = generateTicketNumber();
            $existingTicket = $this->ticketModel->findByTicketNumber($ticketNumber);
        } while ($existingTicket);
        
        // Prepare ticket data
        $ticketData = [
            'ticket_number' => $ticketNumber,
            'title' => sanitize($_POST['title']),
            'description' => sanitize($_POST['description']),
            'category_id' => (int)$_POST['category_id'],
            'priority' => sanitize($_POST['priority']),
            'status' => 'pending',
            'submitter_id' => (int)$_POST['submitter_id'],
            'submitter_type' => 'employee',
            'assigned_to' => isset($_POST['assigned_to']) && !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null
        ];
        
        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = UPLOAD_DIR;
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileName = time() . '_' . basename($_FILES['attachment']['name']);
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $filePath)) {
                $ticketData['attachments'] = $fileName;
            }
        }
        
        // Create ticket
        try {
            error_log("Attempting to create ticket with data: " . print_r($ticketData, true));
            $ticketId = $this->ticketModel->create($ticketData);
            error_log("Ticket creation result - ID: " . ($ticketId ? $ticketId : 'FALSE'));
        } catch (Exception $e) {
            error_log("Ticket creation exception: " . $e->getMessage());
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            redirect('admin/create_ticket.php');
        }
        
        if ($ticketId) {
            // Create SLA tracking for this ticket
            $this->slaModel->createTracking($ticketId, $ticketData['priority']);
            
            // Log activity
            $this->activityModel->log([
                'ticket_id' => $ticketId,
                'user_id' => $currentUser['id'],
                'action_type' => 'created',
                'new_value' => 'pending',
                'comment' => 'Ticket created by admin on behalf of employee'
            ]);
            
            // Create notifications
            try {
                $db = Database::getInstance()->getConnection();
                $notificationModel = new Notification($db);
                
                // 1. Notify the employee that their ticket was created
                $notificationModel->create([
                    'user_id' => null,
                    'employee_id' => $ticketData['submitter_id'],
                    'type' => 'ticket_created',
                    'title' => 'Ticket Created for You',
                    'message' => "A support ticket #{$ticketNumber} has been created on your behalf by admin",
                    'ticket_id' => $ticketId,
                    'related_user_id' => $currentUser['id']
                ]);
                
                // 2. If ticket is assigned to an IT staff, notify them
                if (!empty($ticketData['assigned_to'])) {
                    $notificationModel->create([
                        'user_id' => $ticketData['assigned_to'],
                        'employee_id' => null,
                        'type' => 'ticket_assigned',
                        'title' => 'New Ticket Assigned to You',
                        'message' => "You have been assigned to ticket #{$ticketNumber}: " . $ticketData['title'],
                        'ticket_id' => $ticketId,
                        'related_user_id' => $currentUser['id']
                    ]);
                }
                
                // 3. Notify all other admins/IT staff about the new ticket
                $userModel = new User();
                $adminUsers = $userModel->getAllAdmins();
                
                foreach ($adminUsers as $admin) {
                    // Skip the current user and the assigned user
                    if ($admin['id'] != $currentUser['id'] && $admin['id'] != $ticketData['assigned_to']) {
                        $notificationModel->create([
                            'user_id' => $admin['id'],
                            'employee_id' => null,
                            'type' => 'ticket_created',
                            'title' => 'New Ticket Created',
                            'message' => "Admin created ticket #{$ticketNumber} for employee",
                            'ticket_id' => $ticketId,
                            'related_user_id' => $currentUser['id']
                        ]);
                    }
                }
            } catch (Exception $e) {
                error_log("Failed to create notifications: " . $e->getMessage());
            }
            
            // Send notification email to employee
            try {
                $mailer = new Mailer();
                $ticket = $this->ticketModel->findById($ticketId);
                $employee = $this->employeeModel->findById($ticketData['submitter_id']);
                
                if ($employee) {
                    $mailer->sendTicketCreated($ticket, [
                        'email' => $employee['email'] ?? $employee['personal_email'],
                        'full_name' => trim($employee['fname'] . ' ' . $employee['lname'])
                    ]);
                }
            } catch (Exception $e) {
                error_log("Failed to send email: " . $e->getMessage());
            }
            
            redirect('admin/tickets.php?success=created');
        } else {
            $_SESSION['error'] = "Failed to create ticket. Please try again.";
            redirect('admin/create_ticket.php');
        }
    }
    
    /**
     * Load view file
     */
    private function loadView($view, $data = []) {
        extract($data);
        require_once __DIR__ . '/../../views/' . $view . '.view.php';
    }
}
