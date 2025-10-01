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
-- HELPER FUNCTIONS (Implemented in PHP MessageTracker class)
-- =============================================================================
-- Note: MariaDB stored procedures/functions are replaced with PHP logic
-- in includes/MessageTracker.php for better compatibility

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