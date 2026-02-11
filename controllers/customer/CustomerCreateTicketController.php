<?php
/**
 * Customer Create Ticket Controller
 * Handles employee ticket creation with department routing
 */

class CustomerCreateTicketController {
    private $auth;
    private $ticketModel;
    private $categoryModel;
    private $departmentModel;
    private $activityModel;
    private $slaModel;
    private $priorityMapModel;
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
        $this->departmentModel = new Department();
        $this->activityModel = new TicketActivity();
        $this->slaModel = new SLA();
        $this->priorityMapModel = new CategoryPriorityMap();
        $this->currentUser = $this->auth->getCurrentUser();
    }
    
    /**
     * Get unread notification count for current user
     */
    private function getUnreadCount() {
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT COUNT(*) as count FROM notifications WHERE employee_id = :employee_id AND is_read = 0";
        $stmt = $db->prepare($sql);
        $stmt->execute([':employee_id' => $this->currentUser['id']]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    /**
     * Display ticket creation form with department selection
     */
    public function index() {
        // Get departments for selection
        $departments = $this->departmentModel->getAll();
        
        // Get all categories (will be filtered by JS based on department)
        $categories = $this->categoryModel->getAll();
        
        // Pass data to view
        $currentUser = $this->currentUser;
        $error = isset($_SESSION['error']) ? $_SESSION['error'] : null;
        unset($_SESSION['error']);
        $unreadNotifications = $this->getUnreadCount();
        
        // Get category priority map for auto-priority assignment
        $priorityMap = [];
        if ($this->priorityMapModel->tableExists()) {
            $priorityMap = $this->priorityMapModel->getAllAsLookup();
        }
        
        // SLA targets for display
        $slaTargets = [
            'high' => CategoryPriorityMap::getSLATargets('high'),
            'medium' => CategoryPriorityMap::getSLATargets('medium'),
            'low' => CategoryPriorityMap::getSLATargets('low')
        ];
        
        // Use new multi-step view
        $this->loadView('customer/create_ticket_v2', compact(
            'currentUser', 'departments', 'categories', 'error', 'unreadNotifications',
            'priorityMap', 'slaTargets'
        ));
    }
    
    /**
     * Handle ticket creation with department routing
     */
    public function create() {
        // Generate unique ticket number
        do {
            $ticketNumber = generateTicketNumber();
            $existingTicket = $this->ticketModel->findByTicketNumber($ticketNumber);
        } while ($existingTicket);
        
        // Determine priority: use category-mapped priority, fallback to submitted
        $categoryId = (int)$_POST['category_id'];
        $submittedPriority = sanitize($_POST['priority']);
        $mappedPriority = null;
        
        if ($this->priorityMapModel->tableExists()) {
            $mappedPriority = $this->priorityMapModel->getDefaultPriority($categoryId);
        }
        
        // For customers, always use the mapped priority (no override option)
        $finalPriority = $mappedPriority ?? $submittedPriority;
        
        // Prepare ticket data WITHOUT department (Super Admin will assign)
        $ticketData = [
            'ticket_number' => $ticketNumber,
            'title' => sanitize($_POST['title']),
            'description' => sanitize($_POST['description']),
            'category_id' => $categoryId,
            'department_id' => null, // Super Admin assigns department
            'priority' => $finalPriority,
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
        try {
            $ticketId = $this->ticketModel->create($ticketData);
        } catch (Exception $e) {
            error_log("Customer ticket creation exception: " . $e->getMessage());
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            redirect('customer/create_ticket.php');
            return;
        }
        
        if ($ticketId) {
            // Create SLA tracking for this ticket
            $this->slaModel->createTracking($ticketId, $ticketData['priority']);
            
            // Log activity
            $this->activityModel->log([
                'ticket_id' => $ticketId,
                'user_id' => $this->currentUser['id'],
                'action_type' => 'created',
                'new_value' => 'pending',
                'comment' => 'Ticket created'
            ]);
            
            // Create notifications
            $this->createNotifications($ticketId, $ticketNumber, $ticketData);
            
            // Send email notification
            $this->sendEmailNotification($ticketId, $ticketData);
            
            // Send FCM push notification
            $this->sendPushNotification($ticketId, $ticketNumber, $ticketData);
            
            $_SESSION['success'] = "Your request has been submitted successfully! Ticket #" . $ticketNumber;
            redirect('customer/tickets.php');
        } else {
            $_SESSION['error'] = "Failed to create ticket. Please try again.";
            redirect('customer/create_ticket.php');
        }
    }
    
    /**
     * Create notifications for ticket submission
     */
    private function createNotifications($ticketId, $ticketNumber, $ticketData) {
        try {
            $db = Database::getInstance()->getConnection();
            $notificationModel = new Notification($db);
            
            // Notify employee that ticket was created
            $notificationModel->create([
                'user_id' => null,
                'employee_id' => $this->currentUser['id'],
                'type' => 'ticket_created',
                'title' => 'Request Submitted Successfully',
                'message' => "Your request #{$ticketNumber} has been submitted and is awaiting routing by admin",
                'ticket_id' => $ticketId,
                'related_user_id' => null
            ]);
            
            // Notify Super Admins about new unrouted ticket
            $superAdminSql = "SELECT id FROM users WHERE role_id = 1 AND role = 'admin'";
            $stmt = $db->prepare($superAdminSql);
            $stmt->execute();
            $superAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($superAdmins as $admin) {
                $notificationModel->create([
                    'user_id' => $admin['id'],
                    'employee_id' => null,
                    'type' => 'ticket_created',
                    'title' => "New Ticket Pending Routing",
                    'message' => "New ticket #{$ticketNumber}: " . $ticketData['title'] . " - Needs department assignment",
                    'ticket_id' => $ticketId,
                    'related_user_id' => null
                ]);
            }
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
        }
    }
    
    /**
     * Send email notification for new ticket
     */
    private function sendEmailNotification($ticketId, $ticketData) {
        try {
            if (class_exists('Mailer')) {
                $mailer = new Mailer();
                $ticket = $this->ticketModel->findById($ticketId);
                $mailer->sendTicketCreated($ticket, $this->currentUser);
            }
        } catch (Exception $e) {
            error_log("Failed to send email: " . $e->getMessage());
        }
    }
    
    /**
     * Send FCM push notification
     */
    private function sendPushNotification($ticketId, $ticketNumber, $ticketData) {
        try {
            require_once __DIR__ . '/../../includes/FCMNotification.php';
            $fcm = new FCMNotification();
            $category = $this->categoryModel->findById($ticketData['category_id']);
            
            $fcm->notifyTicketCreated(
                $ticketId,
                $ticketNumber,
                $this->currentUser['fname'] . ' ' . $this->currentUser['lname'],
                $category['name'] ?? 'General'
            );
        } catch (Exception $e) {
            error_log("Failed to send FCM notification: " . $e->getMessage());
        }
    }
    
    /**
     * Load a view file
     */
    private function loadView($viewName, $data = []) {
        extract($data);
        
        $viewPath = __DIR__ . '/../../views/' . $viewName . '.view.php';
        
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View not found: {$viewPath}");
        }
    }
}
