<?php
/**
 * Ticket Workflow API
 * Handles ticket assignment, status changes, and routing
 * All actions are protected by RBAC permissions
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/RBAC.php';
require_once __DIR__ . '/../../controllers/admin/TicketWorkflowController.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON response header
header('Content-Type: application/json');

// Check authentication
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? null;

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Action required']);
    exit;
}

try {
    $workflow = new TicketWorkflowController();
    $result = ['success' => false, 'error' => 'Unknown action'];
    
    switch ($action) {
        
        // Assign ticket to department (Super Admin only)
        case 'assign_department':
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $departmentId = intval($_POST['department_id'] ?? 0);
            $notes = sanitize($_POST['notes'] ?? '');
            
            if (!$ticketId || !$departmentId) {
                $result = ['success' => false, 'error' => 'Ticket ID and Department ID required'];
                break;
            }
            
            $result = $workflow->assignToDepartment($ticketId, $departmentId, $notes);
            break;
        
        // Assign ticket to staff member
        case 'assign_staff':
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $staffId = intval($_POST['staff_id'] ?? 0);
            $notes = sanitize($_POST['notes'] ?? '');
            
            if (!$ticketId || !$staffId) {
                $result = ['success' => false, 'error' => 'Ticket ID and Staff ID required'];
                break;
            }
            
            $result = $workflow->assignToStaff($ticketId, $staffId, $notes);
            break;
        
        // Reassign ticket
        case 'reassign':
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $data = [
                'department_id' => isset($_POST['department_id']) ? intval($_POST['department_id']) : null,
                'staff_id' => isset($_POST['staff_id']) ? intval($_POST['staff_id']) : null,
                'notes' => sanitize($_POST['notes'] ?? '')
            ];
            
            if (!$ticketId) {
                $result = ['success' => false, 'error' => 'Ticket ID required'];
                break;
            }
            
            $result = $workflow->reassign($ticketId, $data);
            break;
        
        // Super Admin override
        case 'override':
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $reason = sanitize($_POST['reason'] ?? '');
            
            if (!$ticketId) {
                $result = ['success' => false, 'error' => 'Ticket ID required'];
                break;
            }
            
            $result = $workflow->override($ticketId, $reason);
            break;
        
        // Update ticket status
        case 'update_status':
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $status = sanitize($_POST['status'] ?? '');
            $resolution = sanitize($_POST['resolution'] ?? '');
            
            if (!$ticketId || !$status) {
                $result = ['success' => false, 'error' => 'Ticket ID and status required'];
                break;
            }
            
            $result = $workflow->updateStatus($ticketId, $status, $resolution);
            break;
        
        // Update ticket priority
        case 'update_priority':
            $ticketId = intval($_POST['ticket_id'] ?? 0);
            $priority = sanitize($_POST['priority'] ?? '');
            
            if (!$ticketId || !$priority) {
                $result = ['success' => false, 'error' => 'Ticket ID and priority required'];
                break;
            }
            
            $result = $workflow->updatePriority($ticketId, $priority);
            break;
        
        // Get pending routing tickets (Super Admin)
        case 'pending_routing':
            $tickets = $workflow->getPendingRoutingTickets();
            $result = ['success' => true, 'tickets' => $tickets, 'count' => count($tickets)];
            break;
        
        // Get accessible tickets based on role
        case 'get_tickets':
            $filters = [];
            if (isset($_GET['status'])) $filters['status'] = sanitize($_GET['status']);
            if (isset($_GET['priority'])) $filters['priority'] = sanitize($_GET['priority']);
            if (isset($_GET['department_id'])) $filters['department_id'] = intval($_GET['department_id']);
            
            $tickets = $workflow->getAccessibleTickets($filters);
            $result = ['success' => true, 'tickets' => $tickets];
            break;
        
        // Get staff for assignment dropdown
        case 'get_department_staff':
            $departmentId = intval($_GET['department_id'] ?? 0);
            
            if (!$departmentId) {
                $result = ['success' => false, 'error' => 'Department ID required'];
                break;
            }
            
            // Check department access
            $rbac = RBAC::getInstance();
            if (!$rbac->canAccessDepartment($departmentId)) {
                $result = ['success' => false, 'error' => 'Department access denied'];
                break;
            }
            
            require_once __DIR__ . '/../../models/User.php';
            $userModel = new User();
            $staff = $userModel->getDepartmentStaff($departmentId);
            $result = ['success' => true, 'staff' => $staff];
            break;
        
        default:
            $result = ['success' => false, 'error' => 'Unknown action: ' . $action];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Ticket workflow API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'An error occurred processing your request'
    ]);
}
