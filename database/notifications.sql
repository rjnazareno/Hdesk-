-- Notifications System for IThelp
-- Run this SQL to create the notifications table

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('ticket_assigned','ticket_updated','ticket_resolved','ticket_created','comment_added','status_changed','priority_changed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `related_user_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add index for faster queries
CREATE INDEX idx_user_unread ON notifications(user_id, is_read, created_at DESC);

-- Sample notification data (optional - for testing)
-- INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) VALUES
-- (1, 'ticket_assigned', 'New Ticket Assigned', 'Ticket #123 has been assigned to you', 123, 0),
-- (1, 'ticket_updated', 'Ticket Updated', 'Ticket #124 was updated by John Doe', 124, 0),
-- (1, 'comment_added', 'New Comment', 'New comment added to ticket #125', 125, 0);
