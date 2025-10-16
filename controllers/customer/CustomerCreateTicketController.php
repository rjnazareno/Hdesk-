<?php
/**
 * Customer Create Ticket Controller
 * Handles employee ticket creation
 */

class CustomerCreateTicketController {
    private $auth;
    private $ticketModel;
    private $categoryModel;
    private $activityModel;
    private $currentUser;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireLogin();
        
        // Ensure only employees can access
        if ($_SESSION['user_type'] !== 'employee') {
            redirect('admin/dashboard.php');
        }
        
        $this->ticketModel = new Ticket();
        $this->categoryModel = new Category();
        $this->activityModel = new TicketActivity();
        $this->currentUser = $this->auth->getCurrentUser();
    }
    
    /**
     * Display ticket creation form
     */
    public function index() {
        // Get categories for dropdown
        $categories = $this->categoryModel->getAll();
        
        // Pass data to view
        $currentUser = $this->currentUser;
        $error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
        unset($_SESSION['error']);
        
        // Load view
        $this->loadView('customer/create_ticket', compact('currentUser', 'categories', 'error'));
    }
    
    /**
     * Handle ticket creation
     */
    public function create() {
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
            'submitter_id' => $this->currentUser['id'],
            'submitter_type' => $_SESSION['user_type'] ?? 'employee'
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
        $ticketId = $this->ticketModel->create($ticketData);
        
        if ($ticketId) {
            // Log activity
            $this->activityModel->log([
                'ticket_id' => $ticketId,
                'user_id' => $this->currentUser['id'],
                'action_type' => 'created',
                'new_value' => 'pending',
                'comment' => 'Ticket created'
            ]);
            
            // Create notification for employee (confirmation)
            try {
                $db = Database::getInstance()->getConnection();
                $notificationModel = new Notification($db);
                
                // Notify employee that ticket was created
                $notificationModel->create([
                    'user_id' => null,
                    'employee_id' => $this->currentUser['id'],
                    'type' => 'ticket_created',
                    'title' => 'Ticket Created Successfully',
                    'message' => "Your ticket #{$ticketNumber} has been submitted and is awaiting review",
                    'ticket_id' => $ticketId,
                    'related_user_id' => null
                ]);
                
                // Notify all admins/IT staff about new ticket
                $userModel = new User();
                $adminUsers = $userModel->getAllAdmins(); // Get all IT staff and admins
                
                foreach ($adminUsers as $admin) {
                    $notificationModel->create([
                        'user_id' => $admin['id'],
                        'employee_id' => null,
                        'type' => 'ticket_created',
                        'title' => 'New Ticket Submitted',
                        'message' => "New ticket #{$ticketNumber}: " . $ticketData['title'],
                        'ticket_id' => $ticketId,
                        'related_user_id' => null
                    ]);
                }
            } catch (Exception $e) {
                // Log error but don't stop ticket creation
                error_log("Failed to create notification: " . $e->getMessage());
            }
            
            // Send notification email
            try {
                $mailer = new Mailer();
                $ticket = $this->ticketModel->findById($ticketId);
                $mailer->sendTicketCreated($ticket, $this->currentUser);
            } catch (Exception $e) {
                error_log("Failed to send email: " . $e->getMessage());
            }
            
            $_SESSION['success'] = "Ticket created successfully!";
            redirect('customer/tickets.php');
        } else {
            $_SESSION['error'] = "Failed to create ticket. Please try again.";
            redirect('customer/create_ticket.php');
        }
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
