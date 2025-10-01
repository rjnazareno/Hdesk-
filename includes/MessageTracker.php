<?php
/**
 * Message Read Tracking System
 * Prevents duplicate notifications for already seen messages
 */

class MessageTracker {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Mark messages as read when user views a ticket
     */
    public function markTicketAsRead($ticketId, $userId, $userType) {
        try {
            // Get the latest response ID for this ticket
            $stmt = $this->db->prepare("
                SELECT response_id 
                FROM ticket_responses 
                WHERE ticket_id = ? 
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$ticketId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $latestResponseId = $result['response_id'] ?? null;
            
            // Update or insert read status
            $stmt = $this->db->prepare("
                INSERT INTO message_read_status (ticket_id, user_id, user_type, last_read_response_id, last_read_at)
                VALUES (?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    last_read_response_id = VALUES(last_read_response_id),
                    last_read_at = NOW()
            ");
            $stmt->execute([$ticketId, $userId, $userType, $latestResponseId]);
            
            error_log("Messages marked as read: Ticket {$ticketId}, User {$userId} ({$userType})");
            return true;
            
        } catch (Exception $e) {
            error_log("Error marking messages as read: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has unread messages in a ticket
     */
    public function hasUnreadMessages($ticketId, $userId, $userType) {
        try {
            // Get user's last read response ID
            $stmt = $this->db->prepare("
                SELECT last_read_response_id 
                FROM message_read_status
                WHERE ticket_id = ? AND user_id = ? AND user_type = ?
            ");
            $stmt->execute([$ticketId, $userId, $userType]);
            $readStatus = $stmt->fetch(PDO::FETCH_ASSOC);
            $lastReadResponseId = $readStatus['last_read_response_id'] ?? null;
            
            // Get the latest response in the ticket
            if ($userType === 'employee') {
                // Exclude internal messages for employees
                $stmt = $this->db->prepare("
                    SELECT response_id 
                    FROM ticket_responses 
                    WHERE ticket_id = ? AND is_internal = FALSE
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
            } else {
                // IT staff can see all messages
                $stmt = $this->db->prepare("
                    SELECT response_id 
                    FROM ticket_responses 
                    WHERE ticket_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
            }
            $stmt->execute([$ticketId]);
            $latestResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $latestResponseId = $latestResult['response_id'] ?? null;
            
            // If no latest response, no unread messages
            if (!$latestResponseId) {
                return false;
            }
            
            // If never read anything, has unread messages
            if (!$lastReadResponseId) {
                return true;
            }
            
            // Compare response IDs
            return $latestResponseId > $lastReadResponseId;
            
        } catch (Exception $e) {
            error_log("Error checking unread messages: " . $e->getMessage());
            return true; // Default to true to ensure notifications aren't missed
        }
    }
    
    /**
     * Get unread response count for a ticket
     */
    public function getUnreadCount($ticketId, $userId, $userType) {
        try {
            // Get last read response ID
            $stmt = $this->db->prepare("
                SELECT last_read_response_id 
                FROM message_read_status 
                WHERE ticket_id = ? AND user_id = ? AND user_type = ?
            ");
            $stmt->execute([$ticketId, $userId, $userType]);
            $readStatus = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $lastReadId = $readStatus['last_read_response_id'] ?? 0;
            
            // Count newer responses
            $countQuery = "
                SELECT COUNT(*) as unread_count
                FROM ticket_responses 
                WHERE ticket_id = ? 
                  AND response_id > ?
            ";
            
            // Exclude internal messages for employees
            if ($userType === 'employee') {
                $countQuery .= " AND is_internal = FALSE";
            }
            
            $stmt = $this->db->prepare($countQuery);
            $stmt->execute([$ticketId, $lastReadId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return (int)$result['unread_count'];
            
        } catch (Exception $e) {
            error_log("Error getting unread count: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Check if notification was already sent for this response
     */
    public function wasNotificationSent($ticketId, $responseId, $recipientUserId, $recipientUserType) {
        try {
            $stmt = $this->db->prepare("
                SELECT id FROM notification_sent_log 
                WHERE ticket_id = ? 
                  AND response_id = ? 
                  AND recipient_user_id = ? 
                  AND recipient_user_type = ?
            ");
            $stmt->execute([$ticketId, $responseId, $recipientUserId, $recipientUserType]);
            
            return $stmt->fetch() !== false;
            
        } catch (Exception $e) {
            error_log("Error checking notification sent status: " . $e->getMessage());
            return false; // Default to false to ensure notifications are sent
        }
    }
    
    /**
     * Log that a notification was sent
     */
    public function logNotificationSent($ticketId, $responseId, $recipientUserId, $recipientUserType, $notificationType = 'new_reply') {
        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO notification_sent_log 
                (ticket_id, response_id, recipient_user_id, recipient_user_type, notification_type)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$ticketId, $responseId, $recipientUserId, $recipientUserType, $notificationType]);
            
            error_log("Notification logged: Ticket {$ticketId}, Response {$responseId}, User {$recipientUserId} ({$recipientUserType})");
            return true;
            
        } catch (Exception $e) {
            error_log("Error logging notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread messages for a user (for dashboard display)
     */
    public function getUnreadTicketsForUser($userId, $userType) {
        try {
            $query = "
                SELECT DISTINCT t.ticket_id, t.title, t.priority, t.status,
                       COUNT(tr.response_id) as total_unread
                FROM tickets t
                JOIN ticket_responses tr ON t.ticket_id = tr.ticket_id
                LEFT JOIN message_read_status mrs ON (
                    t.ticket_id = mrs.ticket_id 
                    AND mrs.user_id = ? 
                    AND mrs.user_type = ?
                )
                WHERE tr.response_id > COALESCE(mrs.last_read_response_id, 0)
            ";
            
            // Add user-specific conditions
            if ($userType === 'employee') {
                $query .= " AND t.employee_id = ? AND tr.is_internal = FALSE";
            } else {
                $query .= " AND (t.assigned_to = ? OR t.assigned_to IS NULL)";
            }
            
            $query .= " GROUP BY t.ticket_id ORDER BY t.updated_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$userId, $userType, $userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting unread tickets: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Clean up old notification logs (optional maintenance)
     */
    public function cleanupOldLogs($daysOld = 30) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM notification_sent_log 
                WHERE sent_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$daysOld]);
            
            $deleted = $stmt->rowCount();
            error_log("Cleaned up {$deleted} old notification logs");
            return $deleted;
            
        } catch (Exception $e) {
            error_log("Error cleaning up notification logs: " . $e->getMessage());
            return 0;
        }
    }
}
?>