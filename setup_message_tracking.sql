-- =============================================================================
-- MESSAGE READ TRACKING SYSTEM
-- =============================================================================

CREATE TABLE IF NOT EXISTS message_read_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    user_type ENUM('employee', 'it_staff') NOT NULL,
    last_read_response_id INT DEFAULT NULL,
    last_read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Unique constraint: one read status per user per ticket
    UNIQUE KEY unique_user_ticket (ticket_id, user_id, user_type),
    
    -- Foreign keys
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (last_read_response_id) REFERENCES ticket_responses(response_id) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_ticket (ticket_id),
    INDEX idx_user (user_id, user_type),
    INDEX idx_last_read (last_read_response_id)
) ENGINE=InnoDB;

-- =============================================================================
-- NOTIFICATION TRACKING SYSTEM  
-- =============================================================================

CREATE TABLE IF NOT EXISTS notification_sent_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    response_id INT NOT NULL,
    recipient_user_id INT NOT NULL,
    recipient_user_type ENUM('employee', 'it_staff') NOT NULL,
    notification_type VARCHAR(50) DEFAULT 'new_reply',
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Prevent duplicate notifications for same response to same user
    UNIQUE KEY unique_notification (ticket_id, response_id, recipient_user_id, recipient_user_type),
    
    -- Foreign keys
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    FOREIGN KEY (response_id) REFERENCES ticket_responses(response_id) ON DELETE CASCADE,
    
    -- Indexes
    INDEX idx_ticket (ticket_id),
    INDEX idx_response (response_id),
    INDEX idx_recipient (recipient_user_id, recipient_user_type),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB;

-- =============================================================================
-- HELPER FUNCTIONS
-- =============================================================================

DELIMITER ;;

-- Function to mark messages as read for a user
CREATE PROCEDURE MarkMessagesRead(
    IN p_ticket_id INT,
    IN p_user_id INT,
    IN p_user_type ENUM('employee', 'it_staff')
)
BEGIN
    DECLARE v_latest_response_id INT DEFAULT NULL;
    
    -- Get the latest response ID for this ticket
    SELECT response_id INTO v_latest_response_id
    FROM ticket_responses 
    WHERE ticket_id = p_ticket_id 
    ORDER BY created_at DESC 
    LIMIT 1;
    
    -- Update or insert read status
    INSERT INTO message_read_status (ticket_id, user_id, user_type, last_read_response_id, last_read_at)
    VALUES (p_ticket_id, p_user_id, p_user_type, v_latest_response_id, NOW())
    ON DUPLICATE KEY UPDATE
        last_read_response_id = v_latest_response_id,
        last_read_at = NOW();
END;;

-- Function to check if user has unread messages
CREATE FUNCTION HasUnreadMessages(
    p_ticket_id INT,
    p_user_id INT,
    p_user_type ENUM('employee', 'it_staff')
) RETURNS BOOLEAN READS SQL DATA DETERMINISTIC
BEGIN
    DECLARE v_last_read_response_id INT DEFAULT NULL;
    DECLARE v_latest_response_id INT DEFAULT NULL;
    
    -- Get user's last read response
    SELECT last_read_response_id INTO v_last_read_response_id
    FROM message_read_status
    WHERE ticket_id = p_ticket_id 
      AND user_id = p_user_id 
      AND user_type = p_user_type;
    
    -- Get the latest response in the ticket (exclude internal for employees)
    IF p_user_type = 'employee' THEN
        SELECT response_id INTO v_latest_response_id
        FROM ticket_responses 
        WHERE ticket_id = p_ticket_id 
          AND is_internal = FALSE
        ORDER BY created_at DESC 
        LIMIT 1;
    ELSE
        SELECT response_id INTO v_latest_response_id
        FROM ticket_responses 
        WHERE ticket_id = p_ticket_id 
        ORDER BY created_at DESC 
        LIMIT 1;
    END IF;
    
    -- If no latest response, no unread messages
    IF v_latest_response_id IS NULL THEN
        RETURN FALSE;
    END IF;
    
    -- If never read anything, has unread messages
    IF v_last_read_response_id IS NULL THEN
        RETURN TRUE;
    END IF;
    
    -- Compare response IDs
    RETURN v_latest_response_id > v_last_read_response_id;
END;;

DELIMITER ;

-- =============================================================================
-- INITIAL DATA SETUP
-- =============================================================================

-- Mark all existing messages as "read" to avoid spam notifications
INSERT IGNORE INTO message_read_status (ticket_id, user_id, user_type, last_read_response_id)
SELECT DISTINCT 
    t.ticket_id,
    CASE 
        WHEN e.id IS NOT NULL THEN e.id
        WHEN its.staff_id IS NOT NULL THEN its.staff_id
    END as user_id,
    CASE 
        WHEN e.id IS NOT NULL THEN 'employee'
        WHEN its.staff_id IS NOT NULL THEN 'it_staff'
    END as user_type,
    (SELECT MAX(response_id) FROM ticket_responses tr WHERE tr.ticket_id = t.ticket_id) as last_read_response_id
FROM tickets t
LEFT JOIN employees e ON t.employee_id = e.id
LEFT JOIN it_staff its ON t.assigned_to = its.staff_id
WHERE (e.id IS NOT NULL OR its.staff_id IS NOT NULL);

SELECT 'Message read tracking system installed successfully!' as Status;