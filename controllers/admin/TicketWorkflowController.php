<?php
/**
 * Ticket Workflow Controller
 * Handles ticket assignment, escalation, and routing based on RBAC hierarchy
 * 
 * Workflow:
 * 1. Employee submits ticket → Goes to Super Admin view
 * 2. Super Admin assigns to Department Admin (HR/IT)
 * 3. Department Admin processes and resolves
 * 4. Super Admin can monitor and override at any point
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/RBAC.php';
require_once __DIR__ . '/../../models/Ticket.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Department.php';
require_once __DIR__ . '/../../models/Notification.php';

class TicketWorkflowController {
    private $auth;
    private $rbac;
    private $ticketModel;
    private $userModel;
    private $departmentModel;
    private $notificationModel;
    private $currentUserId;
    
    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireLogin();
        
        $this->rbac = RBAC::getInstance();
        $this->ticketModel = new Ticket();
        $this->userModel = new User();
        $this->departmentModel = new Department();
        $this->notificationModel = new Notification();
        
        $this->currentUserId = $_SESSION['user_id'];
    }
    
    /**
     * Assign ticket to department
     * Only Super Admin can assign tickets to departments
     * 
     * @param int $ticketId Ticket ID
     * @param int $departmentId Target department ID
     * @param string|null $notes Assignment notes
     * @return array Result with success status and message
     */
    public function assignToDepartment($ticketId, $departmentId, $notes = null) {
        // Check permission - only Super Admin can assign to departments
        if (!$this->rbac->isSuperAdmin()) {
            return [
                'success' => false,
                'message' => 'Only Super Admin can assign tickets to departments'
            ];
        }
        
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found'];
        }
        
        $department = $this->departmentModel->findById($departmentId);
        if (!$department) {
            return ['success' => false, 'message' => 'Department not found'];
        }
        
        // Update ticket
        $result = $this->ticketModel->update($ticketId, [
            'department_id' => $departmentId,
            'status' => 'open'
        ]);
        
        if ($result) {
            // Log assignment
            $this->logAssignment($ticketId, [
                'assigned_to_dept_id' => $departmentId,
                'assigned_from_dept_id' => $ticket['department_id'],
                'assigned_by' => $this->currentUserId,
                'assignment_type' => $ticket['department_id'] ? 'reassign' : 'initial',
                'notes' => $notes
            ]);
            
            // Notify department admins
            $deptAdmins = $this->userModel->getDepartmentAdmins($departmentId);
            foreach ($deptAdmins as $admin) {
                $this->notificationModel->create([
                    'user_id' => $admin['id'],
                    'type' => 'ticket_assigned_to_dept',
                    'title' => 'New Ticket Assigned to Your Department',
                    'message' => "Ticket #{$ticket['ticket_number']} has been assigned to {$department['name']}",
                    'link' => "admin/view_ticket.php?id=$ticketId"
                ]);
            }
            
            // Log activity
            $this->logTicketActivity($ticketId, 'assigned_to_department', null, $department['name'], 
                "Ticket assigned to {$department['name']} department");
            
            return [
                'success' => true,
                'message' => "Ticket assigned to {$department['name']} department"
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to assign ticket'];
    }
    
    /**
     * Assign ticket to staff member
     * Super Admin can assign to anyone
     * Department Admin can only assign within their department
     * 
     * @param int $ticketId Ticket ID
     * @param int $staffId Target staff member ID
     * @param string|null $notes Assignment notes
     * @return array Result with success status and message
     */
    public function assignToStaff($ticketId, $staffId, $notes = null) {
        // Check permissions
        if (!$this->rbac->can('tickets.assign')) {
            return [
                'success' => false,
                'message' => 'You do not have permission to assign tickets'
            ];
        }
        
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found'];
        }
        
        $staffMember = $this->userModel->findByIdWithRole($staffId);
        if (!$staffMember) {
            return ['success' => false, 'message' => 'Staff member not found'];
        }
        
        // Department Admin can only assign to their department staff
        if ($this->rbac->isDeptAdmin() && !$this->rbac->isSuperAdmin()) {
            if (!$ticket['department_id']) {
                return [
                    'success' => false,
                    'message' => 'Cannot assign ticket without department'
                ];
            }
            
            if (!$this->rbac->canAccessDepartment($ticket['department_id'])) {
                return [
                    'success' => false,
                    'message' => 'You can only assign tickets within your department'
                ];
            }
            
            // Verify staff member is in the same department
            $staffDepts = $this->userModel->getUserDepartments($staffId);
            $staffDeptIds = array_column($staffDepts, 'id');
            if (!in_array($ticket['department_id'], $staffDeptIds)) {
                return [
                    'success' => false,
                    'message' => 'Staff member is not in this department'
                ];
            }
        }
        
        // Update ticket
        $result = $this->ticketModel->update($ticketId, [
            'assigned_to' => $staffId,
            'status' => $ticket['status'] === 'pending' ? 'open' : $ticket['status']
        ]);
        
        if ($result) {
            // Log assignment
            $this->logAssignment($ticketId, [
                'assigned_to_user_id' => $staffId,
                'assigned_from_user_id' => $ticket['assigned_to'],
                'assigned_by' => $this->currentUserId,
                'assignment_type' => $ticket['assigned_to'] ? 'reassign' : 'initial',
                'notes' => $notes
            ]);
            
            // Notify assigned staff
            $this->notificationModel->create([
                'user_id' => $staffId,
                'type' => 'ticket_assigned',
                'title' => 'Ticket Assigned to You',
                'message' => "Ticket #{$ticket['ticket_number']} has been assigned to you",
                'link' => "admin/view_ticket.php?id=$ticketId"
            ]);
            
            // Log activity
            $this->logTicketActivity($ticketId, 'assigned', 
                $ticket['assigned_name'] ?? null, 
                $staffMember['full_name'],
                "Ticket assigned to {$staffMember['full_name']}");
            
            return [
                'success' => true,
                'message' => "Ticket assigned to {$staffMember['full_name']}"
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to assign ticket'];
    }
    
    /**
     * Reassign ticket (move between departments or staff)
     * Super Admin: Can reassign anything
     * Department Admin: Can only reassign within their department
     * 
     * @param int $ticketId Ticket ID
     * @param array $data ['department_id' => int, 'staff_id' => int, 'notes' => string]
     * @return array Result
     */
    public function reassign($ticketId, $data) {
        if (!$this->rbac->can('tickets.reassign')) {
            return [
                'success' => false,
                'message' => 'You do not have permission to reassign tickets'
            ];
        }
        
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found'];
        }
        
        // If reassigning to different department, must be Super Admin
        if (isset($data['department_id']) && $data['department_id'] != $ticket['department_id']) {
            if (!$this->rbac->isSuperAdmin()) {
                return [
                    'success' => false,
                    'message' => 'Only Super Admin can reassign tickets to different departments'
                ];
            }
            
            return $this->assignToDepartment($ticketId, $data['department_id'], $data['notes'] ?? null);
        }
        
        // Reassigning staff within same department
        if (isset($data['staff_id'])) {
            return $this->assignToStaff($ticketId, $data['staff_id'], $data['notes'] ?? null);
        }
        
        return ['success' => false, 'message' => 'No assignment target specified'];
    }
    
    /**
     * Super Admin override - take control of any ticket
     * 
     * @param int $ticketId Ticket ID
     * @param string|null $reason Override reason
     * @return array Result
     */
    public function override($ticketId, $reason = null) {
        if (!$this->rbac->can('tickets.override')) {
            return [
                'success' => false,
                'message' => 'You do not have permission to override tickets'
            ];
        }
        
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found'];
        }
        
        // Update ticket - assign to current user (Super Admin)
        $result = $this->ticketModel->update($ticketId, [
            'assigned_to' => $this->currentUserId,
            'grabbed_by' => $this->currentUserId,
            'grabbed_at' => date('Y-m-d H:i:s')
        ]);
        
        if ($result) {
            // Log override
            $this->logAssignment($ticketId, [
                'assigned_to_user_id' => $this->currentUserId,
                'assigned_from_user_id' => $ticket['assigned_to'],
                'assigned_by' => $this->currentUserId,
                'assignment_type' => 'override',
                'notes' => $reason
            ]);
            
            // Notify previous assignee if exists
            if ($ticket['assigned_to']) {
                $this->notificationModel->create([
                    'user_id' => $ticket['assigned_to'],
                    'type' => 'ticket_override',
                    'title' => 'Ticket Override',
                    'message' => "Ticket #{$ticket['ticket_number']} has been taken over by Super Admin",
                    'link' => "admin/view_ticket.php?id=$ticketId"
                ]);
            }
            
            $this->logTicketActivity($ticketId, 'override', null, null, 
                "Super Admin override: " . ($reason ?? 'No reason provided'));
            
            return [
                'success' => true,
                'message' => 'Ticket override successful'
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to override ticket'];
    }
    
    /**
     * Update ticket status
     * Respects RBAC permissions
     * 
     * @param int $ticketId Ticket ID
     * @param string $newStatus New status
     * @param string|null $resolution Resolution notes (for resolved/closed)
     * @return array Result
     */
    public function updateStatus($ticketId, $newStatus, $resolution = null) {
        if (!$this->rbac->can('tickets.update_status')) {
            return [
                'success' => false,
                'message' => 'You do not have permission to update ticket status'
            ];
        }
        
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found'];
        }
        
        // Department Admin/Staff can only update tickets in their department
        if (!$this->rbac->isSuperAdmin()) {
            if ($ticket['department_id'] && !$this->rbac->canAccessDepartment($ticket['department_id'])) {
                return [
                    'success' => false,
                    'message' => 'You can only update tickets in your department'
                ];
            }
        }
        
        $validStatuses = ['pending', 'open', 'in_progress', 'resolved', 'closed'];
        if (!in_array($newStatus, $validStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        $updateData = ['status' => $newStatus];
        
        if ($newStatus === 'resolved') {
            $updateData['resolved_at'] = date('Y-m-d H:i:s');
            if ($resolution) {
                $updateData['resolution'] = $resolution;
            }
        } elseif ($newStatus === 'closed') {
            $updateData['closed_at'] = date('Y-m-d H:i:s');
        }
        
        $result = $this->ticketModel->update($ticketId, $updateData);
        
        if ($result) {
            $this->logTicketActivity($ticketId, 'status_change', $ticket['status'], $newStatus, $resolution);
            
            // Notify submitter on resolution
            if (in_array($newStatus, ['resolved', 'closed'])) {
                $this->notifySubmitter($ticket, $newStatus);
            }
            
            return [
                'success' => true,
                'message' => "Ticket status updated to $newStatus"
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to update ticket status'];
    }
    
    /**
     * Update ticket priority
     * 
     * @param int $ticketId Ticket ID
     * @param string $newPriority New priority
     * @return array Result
     */
    public function updatePriority($ticketId, $newPriority) {
        if (!$this->rbac->can('tickets.update_priority')) {
            return [
                'success' => false,
                'message' => 'You do not have permission to update ticket priority'
            ];
        }
        
        $ticket = $this->ticketModel->findById($ticketId);
        if (!$ticket) {
            return ['success' => false, 'message' => 'Ticket not found'];
        }
        
        // Department scope check
        if (!$this->rbac->isSuperAdmin()) {
            if ($ticket['department_id'] && !$this->rbac->canAccessDepartment($ticket['department_id'])) {
                return [
                    'success' => false,
                    'message' => 'You can only update tickets in your department'
                ];
            }
        }
        
        $validPriorities = ['low', 'medium', 'high'];
        if (!in_array($newPriority, $validPriorities)) {
            return ['success' => false, 'message' => 'Invalid priority'];
        }
        
        $result = $this->ticketModel->update($ticketId, ['priority' => $newPriority]);
        
        if ($result) {
            $this->logTicketActivity($ticketId, 'priority_change', $ticket['priority'], $newPriority);
            
            return [
                'success' => true,
                'message' => "Ticket priority updated to $newPriority"
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to update ticket priority'];
    }
    
    /**
     * Get tickets based on user role and permissions
     * 
     * @param array $filters Optional filters
     * @return array Tickets
     */
    public function getAccessibleTickets($filters = []) {
        // Super Admin sees all
        if ($this->rbac->isSuperAdmin()) {
            return $this->ticketModel->getAll($filters);
        }
        
        // Department Admin/Staff sees only their department
        if ($this->rbac->isDeptAdmin() || $this->rbac->isStaff()) {
            $departments = $this->rbac->getAccessibleDepartments();
            if (!empty($departments)) {
                $filters['department_ids'] = $departments;
            }
            return $this->ticketModel->getAll($filters);
        }
        
        // Employee sees only their own tickets
        $filters['submitter_id'] = $this->currentUserId;
        $filters['submitter_type'] = $_SESSION['user_type'];
        return $this->ticketModel->getAll($filters);
    }
    
    /**
     * Get unassigned tickets for Super Admin
     * These are tickets without department assignment
     * 
     * @return array Unassigned tickets
     */
    public function getUnassignedTickets() {
        if (!$this->rbac->isSuperAdmin()) {
            return [];
        }
        
        return $this->ticketModel->getAll([
            'department_id' => null,
            'status' => ['pending', 'open']
        ]);
    }
    
    /**
     * Get tickets pending department assignment (for Super Admin dashboard)
     * 
     * @return array Pending tickets needing routing
     */
    public function getPendingRoutingTickets() {
        if (!$this->rbac->isSuperAdmin()) {
            return [];
        }
        
        $db = Database::getInstance()->getConnection();
        $sql = "SELECT t.*, 
                c.name as category_name,
                CASE 
                    WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                    ELSE u.full_name
                END as submitter_name
                FROM tickets t
                LEFT JOIN categories c ON t.category_id = c.id
                LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
                LEFT JOIN users u ON t.submitter_id = u.id AND t.submitter_type = 'user'
                WHERE t.department_id IS NULL
                AND t.status IN ('pending', 'open')
                ORDER BY 
                    FIELD(t.priority, 'high', 'medium', 'low'),
                    t.created_at ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Log ticket assignment to audit table
     */
    private function logAssignment($ticketId, $data) {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO ticket_assignments 
                (ticket_id, assigned_from_user_id, assigned_to_user_id, 
                 assigned_from_dept_id, assigned_to_dept_id, assigned_by, 
                 assignment_type, notes)
                VALUES 
                (:ticket_id, :from_user, :to_user, :from_dept, :to_dept, 
                 :assigned_by, :type, :notes)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':ticket_id' => $ticketId,
            ':from_user' => $data['assigned_from_user_id'] ?? null,
            ':to_user' => $data['assigned_to_user_id'] ?? null,
            ':from_dept' => $data['assigned_from_dept_id'] ?? null,
            ':to_dept' => $data['assigned_to_dept_id'] ?? null,
            ':assigned_by' => $data['assigned_by'],
            ':type' => $data['assignment_type'],
            ':notes' => $data['notes'] ?? null
        ]);
    }
    
    /**
     * Log ticket activity
     */
    private function logTicketActivity($ticketId, $actionType, $oldValue, $newValue, $comment = null) {
        $db = Database::getInstance()->getConnection();
        $sql = "INSERT INTO ticket_activity 
                (ticket_id, user_id, action_type, old_value, new_value, comment)
                VALUES (:ticket_id, :user_id, :action_type, :old_value, :new_value, :comment)";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':ticket_id' => $ticketId,
            ':user_id' => $this->currentUserId,
            ':action_type' => $actionType,
            ':old_value' => $oldValue,
            ':new_value' => $newValue,
            ':comment' => $comment
        ]);
    }
    
    /**
     * Notify ticket submitter
     */
    private function notifySubmitter($ticket, $status) {
        $message = $status === 'resolved' 
            ? "Your ticket #{$ticket['ticket_number']} has been resolved"
            : "Your ticket #{$ticket['ticket_number']} has been closed";
        
        $notificationData = [
            'type' => 'ticket_' . $status,
            'title' => 'Ticket Update',
            'message' => $message,
            'link' => "customer/view_ticket.php?id={$ticket['id']}"
        ];
        
        if ($ticket['submitter_type'] === 'employee') {
            $notificationData['employee_id'] = $ticket['submitter_id'];
        } else {
            $notificationData['user_id'] = $ticket['submitter_id'];
        }
        
        $this->notificationModel->create($notificationData);
    }
}
