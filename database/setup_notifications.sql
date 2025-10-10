-- ============================================
-- NOTIFICATIONS SYSTEM - DATABASE SETUP
-- Run this complete SQL script in phpMyAdmin
-- ============================================

-- Step 1: Check if table exists and drop it (optional - only if you want to start fresh)
-- DROP TABLE IF EXISTS `notifications`;

-- Step 2: Create notifications table
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

-- Step 3: Add index for faster queries
CREATE INDEX idx_user_unread ON notifications(user_id, is_read, created_at DESC);

-- Step 4: Verify table was created
SELECT 'Table created successfully!' as status;

-- Step 5: Add test notifications
-- First, let's check what user IDs exist in your system:
SELECT id, username, full_name, role FROM users LIMIT 5;

-- Then add test notifications (replace user_id with actual ID from above query)
-- Example for user_id = 1:
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) VALUES
(1, 'ticket_assigned', 'New Ticket Assigned', 'Ticket #1 has been assigned to you', 1, 0),
(1, 'ticket_updated', 'Ticket Updated', 'Ticket #2 was updated by IT staff', 2, 0),
(1, 'comment_added', 'New Comment', 'A new comment was added to your ticket', 1, 1),
(1, 'status_changed', 'Status Changed', 'Your ticket status was changed to In Progress', 2, 0);

-- Step 6: Verify notifications were inserted
SELECT * FROM notifications;

-- Step 7: Check notification count
SELECT 
    COUNT(*) as total_notifications,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count,
    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count
FROM notifications;

-- ============================================
-- DONE! Notifications table is ready to use
-- ============================================
