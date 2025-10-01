-- In-app notifications system
-- This table stores notifications that appear in the notification bell dropdown

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `user_type` ENUM('employee', 'it_staff') NOT NULL,
    `type` VARCHAR(50) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `message` TEXT NOT NULL,
    `action_url` VARCHAR(255) DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    INDEX `user_notifications` (`user_id`, `user_type`),
    INDEX `unread_notifications` (`user_id`, `user_type`, `is_read`),
    INDEX `notification_type` (`type`),
    INDEX `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample notification data for testing
INSERT INTO `notifications` (`user_id`, `user_type`, `type`, `title`, `message`, `action_url`, `is_read`) VALUES
(1, 'employee', 'ticket_status', 'Ticket Closed', 'Your ticket #1 has been resolved and closed', '/IThelp/view_ticket.php?id=1', 0),
(1, 'employee', 'ticket_reply', 'New Reply', 'IT staff replied to your ticket #2', '/IThelp/view_ticket.php?id=2', 0),
(1, 'it_staff', 'new_ticket', 'New Ticket Created', 'Employee John Doe created a new ticket #3', '/IThelp/view_ticket.php?id=3', 1);