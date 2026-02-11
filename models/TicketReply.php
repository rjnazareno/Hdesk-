<?php
/**
 * TicketReply Model
 * Handles ticket conversation between admin/IT staff and customers
 */
class TicketReply {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Create a new reply
     */
    public function create($data) {
        $sql = "INSERT INTO ticket_replies (ticket_id, user_id, user_type, message) 
                VALUES (:ticket_id, :user_id, :user_type, :message)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':ticket_id' => $data['ticket_id'],
            ':user_id' => $data['user_id'],
            ':user_type' => $data['user_type'],
            ':message' => $data['message']
        ]);
    }

    /**
     * Get all replies for a ticket with sender names
     */
    public function getByTicketId($ticketId) {
        $sql = "SELECT r.*,
                    CASE 
                        WHEN r.user_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
                        ELSE u.full_name
                    END as sender_name,
                    CASE 
                        WHEN r.user_type = 'employee' THEN e.admin_rights_hdesk
                        ELSE u.role
                    END as sender_role
                FROM ticket_replies r
                LEFT JOIN employees e ON r.user_id = e.id AND r.user_type = 'employee'
                LEFT JOIN users u ON r.user_id = u.id AND r.user_type = 'user'
                WHERE r.ticket_id = :ticket_id
                ORDER BY r.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ticket_id' => $ticketId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get reply count for a ticket
     */
    public function getReplyCount($ticketId) {
        $sql = "SELECT COUNT(*) as count FROM ticket_replies WHERE ticket_id = :ticket_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':ticket_id' => $ticketId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['count'] ?? 0);
    }
}
