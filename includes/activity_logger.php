<?php
/**
 * Activity Logger - Tracks ticket and system activities
 */

class ActivityLogger {
    private $pdo;
    
    public function __construct($pdo = null) {
        $this->pdo = $pdo ?: getDB();
    }
    
    /**
     * Log an activity
     */
    public function log($userId, $userType, $action, $entityType, $entityId, $details = null, $ipAddress = null, $userAgent = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO activity_log (user_id, user_type, action, entity_type, entity_id, details, ip_address, user_agent, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $detailsJson = $details ? json_encode($details) : null;
            $ipAddress = $ipAddress ?: $_SERVER['REMOTE_ADDR'] ?? null;
            $userAgent = $userAgent ?: $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            $stmt->execute([
                $userId,
                $userType,
                $action,
                $entityType,
                $entityId,
                $detailsJson,
                $ipAddress,
                $userAgent
            ]);
            
            return true;
        } catch (Exception $e) {
            error_log("Activity logging error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log ticket creation
     */
    public function logTicketCreated($userId, $userType, $ticketId, $subject, $priority, $category) {
        $this->log($userId, $userType, 'ticket_created', 'ticket', $ticketId, [
            'subject' => $subject,
            'priority' => $priority,
            'category' => $category
        ]);
    }
    
    /**
     * Log ticket status change
     */
    public function logStatusChange($userId, $userType, $ticketId, $oldStatus, $newStatus) {
        $this->log($userId, $userType, 'status_changed', 'ticket', $ticketId, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ]);
    }
    
    /**
     * Log ticket assignment
     */
    public function logAssignment($userId, $userType, $ticketId, $assignedToId, $assignedToName) {
        $this->log($userId, $userType, 'ticket_assigned', 'ticket', $ticketId, [
            'assigned_to_id' => $assignedToId,
            'assigned_to_name' => $assignedToName
        ]);
    }
    
    /**
     * Log priority change
     */
    public function logPriorityChange($userId, $userType, $ticketId, $oldPriority, $newPriority) {
        $this->log($userId, $userType, 'priority_changed', 'ticket', $ticketId, [
            'old_priority' => $oldPriority,
            'new_priority' => $newPriority
        ]);
    }
    
    /**
     * Log response added
     */
    public function logResponseAdded($userId, $userType, $ticketId, $responseId, $isInternal = false) {
        $this->log($userId, $userType, 'response_added', 'ticket', $ticketId, [
            'response_id' => $responseId,
            'is_internal' => $isInternal
        ]);
    }
    
    /**
     * Get activities for a specific ticket
     */
    public function getTicketActivities($ticketId, $limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    al.*,
                    CASE 
                        WHEN al.user_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                        WHEN al.user_type = 'it_staff' THEN its.name
                        ELSE 'Unknown User'
                    END as user_name,
                    CASE 
                        WHEN al.user_type = 'employee' THEN e.username
                        WHEN al.user_type = 'it_staff' THEN its.username
                        ELSE NULL
                    END as username
                FROM activity_log al
                LEFT JOIN employees e ON al.user_type = 'employee' AND al.user_id = e.id
                LEFT JOIN it_staff its ON al.user_type = 'it_staff' AND al.user_id = its.staff_id
                WHERE al.entity_type = 'ticket' AND al.entity_id = ?
                ORDER BY al.created_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$ticketId, $limit]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get ticket activities error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get formatted activity description
     */
    public function getActivityDescription($activity) {
        $details = $activity['details'] ? json_decode($activity['details'], true) : [];
        
        switch ($activity['action']) {
            case 'ticket_created':
                return "created this ticket";
                
            case 'status_changed':
                $oldStatus = $details['old_status'] ?? 'unknown';
                $newStatus = $details['new_status'] ?? 'unknown';
                return "changed status from <strong>" . ucfirst(str_replace('_', ' ', $oldStatus)) . "</strong> to <strong>" . ucfirst(str_replace('_', ' ', $newStatus)) . "</strong>";
                
            case 'priority_changed':
                $oldPriority = $details['old_priority'] ?? 'unknown';
                $newPriority = $details['new_priority'] ?? 'unknown';
                return "changed priority from <strong>" . ucfirst($oldPriority) . "</strong> to <strong>" . ucfirst($newPriority) . "</strong>";
                
            case 'ticket_assigned':
                $assignedTo = $details['assigned_to_name'] ?? 'Unknown';
                return "assigned this ticket to <strong>" . htmlspecialchars($assignedTo) . "</strong>";
                
            case 'response_added':
                $isInternal = $details['is_internal'] ?? false;
                return "added a " . ($isInternal ? "internal note" : "response");
                
            default:
                return $activity['action'];
        }
    }
    
    /**
     * Get activity icon
     */
    public function getActivityIcon($action) {
        switch ($action) {
            case 'ticket_created': return 'fas fa-plus-circle text-green-500';
            case 'status_changed': return 'fas fa-exchange-alt text-blue-500';
            case 'priority_changed': return 'fas fa-exclamation-triangle text-orange-500';
            case 'ticket_assigned': return 'fas fa-user-tag text-purple-500';
            case 'response_added': return 'fas fa-comment text-gray-500';
            default: return 'fas fa-circle text-gray-400';
        }
    }
}
?>