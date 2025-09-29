-- =====================================================
-- IT Ticketing System - Live Deployment Database Schema
-- Version: 1.0 - Production Ready
-- Date: September 29, 2025
-- =====================================================

-- Create database (modify name as needed)
-- CREATE DATABASE it_ticketing_live CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE it_ticketing_live;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Employees Table (existing structure preserved)
-- Note: This assumes you already have an employees table
-- If not, uncomment and modify the structure below:

/*
CREATE TABLE IF NOT EXISTS `employees` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `fname` varchar(255) DEFAULT NULL,
    `lname` varchar(255) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `personal_email` varchar(255) DEFAULT NULL,
    `contact` varchar(255) DEFAULT NULL,
    `position` varchar(255) DEFAULT NULL,
    `status` enum('active','inactive') DEFAULT 'active',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `username` varchar(100) DEFAULT NULL,
    `password` varchar(255) DEFAULT NULL,
    `company` varchar(255) DEFAULT NULL,
    `profile_image` varchar(255) DEFAULT NULL,
    `profile_picture` varchar(255) DEFAULT NULL,
    `official_sched` int(11) DEFAULT NULL,
    `role` enum('employee','internal') NOT NULL DEFAULT 'employee',
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    KEY `idx_status` (`status`),
    KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/

-- IT Staff Table
CREATE TABLE IF NOT EXISTS `it_staff` (
    `staff_id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(150) NOT NULL,
    `email` varchar(150) NOT NULL,
    `username` varchar(50) NOT NULL,
    `password` varchar(255) NOT NULL,
    `role` enum('admin','technician','support') DEFAULT 'support',
    `department` varchar(100) DEFAULT 'IT',
    `phone` varchar(20) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`staff_id`),
    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `username` (`username`),
    KEY `idx_active` (`is_active`),
    KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tickets Table
CREATE TABLE IF NOT EXISTS `tickets` (
    `ticket_id` int(11) NOT NULL AUTO_INCREMENT,
    `employee_id` int(11) NOT NULL,
    `subject` varchar(255) NOT NULL,
    `description` text NOT NULL,
    `category` varchar(100) DEFAULT 'General',
    `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
    `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
    `assigned_to` int(11) DEFAULT NULL COMMENT 'IT Staff ID',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    `resolved_at` timestamp NULL DEFAULT NULL,
    `closed_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`ticket_id`),
    KEY `idx_employee_id` (`employee_id`),
    KEY `idx_assigned_to` (`assigned_to`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    KEY `idx_category` (`category`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_updated_at` (`updated_at`),
    CONSTRAINT `fk_tickets_employee` FOREIGN KEY (`employee_id`) REFERENCES `employees` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_tickets_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `it_staff` (`staff_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket Responses Table
CREATE TABLE IF NOT EXISTS `ticket_responses` (
    `response_id` int(11) NOT NULL AUTO_INCREMENT,
    `ticket_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `user_type` enum('employee','it_staff') NOT NULL,
    `message` text NOT NULL,
    `is_internal` tinyint(1) DEFAULT 0 COMMENT '1 for internal IT notes, 0 for public responses',
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`response_id`),
    KEY `idx_ticket_id` (`ticket_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_user_type` (`user_type`),
    KEY `idx_created_at` (`created_at`),
    KEY `idx_internal` (`is_internal`),
    CONSTRAINT `fk_responses_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket Attachments Table
CREATE TABLE IF NOT EXISTS `ticket_attachments` (
    `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
    `ticket_id` int(11) NOT NULL,
    `response_id` int(11) DEFAULT NULL,
    `original_filename` varchar(255) NOT NULL,
    `stored_filename` varchar(255) NOT NULL,
    `file_path` varchar(500) NOT NULL,
    `file_size` int(11) NOT NULL,
    `mime_type` varchar(100) NOT NULL,
    `uploaded_by` int(11) NOT NULL,
    `user_type` enum('employee','it_staff') NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`attachment_id`),
    KEY `idx_ticket_id` (`ticket_id`),
    KEY `idx_response_id` (`response_id`),
    KEY `idx_uploaded_by` (`uploaded_by`),
    KEY `idx_user_type` (`user_type`),
    CONSTRAINT `fk_attachments_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_attachments_response` FOREIGN KEY (`response_id`) REFERENCES `ticket_responses` (`response_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- OPTIONAL ENHANCEMENT TABLES
-- =====================================================

-- Ticket Categories Table (Optional - for better organization)
CREATE TABLE IF NOT EXISTS `ticket_categories` (
    `category_id` int(11) NOT NULL AUTO_INCREMENT,
    `category_name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `color` varchar(7) DEFAULT '#007bff' COMMENT 'Hex color code',
    `is_active` tinyint(1) DEFAULT 1,
    `sort_order` int(11) DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`category_id`),
    UNIQUE KEY `category_name` (`category_name`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings Table (Optional - for configuration)
CREATE TABLE IF NOT EXISTS `system_settings` (
    `setting_id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text DEFAULT NULL,
    `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
    `description` varchar(255) DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`setting_id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Log Table (Optional - for auditing)
CREATE TABLE IF NOT EXISTS `activity_log` (
    `log_id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `user_type` enum('employee','it_staff') NOT NULL,
    `action` varchar(100) NOT NULL,
    `entity_type` varchar(50) NOT NULL COMMENT 'ticket, response, etc.',
    `entity_id` int(11) NOT NULL,
    `details` json DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`log_id`),
    KEY `idx_user` (`user_id`, `user_type`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_action` (`action`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INITIAL DATA SETUP
-- =====================================================

-- Insert default IT Staff account
INSERT INTO `it_staff` (`name`, `email`, `username`, `password`, `role`, `is_active`) 
VALUES 
('System Administrator', 'admin@company.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1)
ON DUPLICATE KEY UPDATE `updated_at` = current_timestamp();

-- Insert default ticket categories
INSERT INTO `ticket_categories` (`category_name`, `description`, `color`, `sort_order`) 
VALUES 
('Hardware', 'Hardware related issues and requests', '#dc3545', 1),
('Software', 'Software installation, updates, and issues', '#28a745', 2),
('Network', 'Network connectivity and access issues', '#17a2b8', 3),
('Account', 'User account and access management', '#ffc107', 4),
('Email', 'Email setup and troubleshooting', '#6f42c1', 5),
('Security', 'Security concerns and incidents', '#fd7e14', 6),
('Other', 'General IT support requests', '#6c757d', 7)
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) 
VALUES 
('app_name', 'IT Ticketing System', 'string', 'Application name displayed in interface'),
('tickets_per_page', '10', 'number', 'Number of tickets to display per page'),
('max_file_size', '10485760', 'number', 'Maximum file upload size in bytes (10MB)'),
('allowed_file_types', 'jpg,jpeg,png,gif,pdf,doc,docx,txt,zip', 'string', 'Comma-separated list of allowed file extensions'),
('auto_assign_tickets', 'false', 'boolean', 'Automatically assign tickets to available IT staff'),
('email_notifications', 'true', 'boolean', 'Send email notifications for ticket updates'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'),
('session_timeout', '28800', 'number', 'Session timeout in seconds (8 hours)')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Additional performance indexes
ALTER TABLE `tickets` 
ADD INDEX `idx_status_priority` (`status`, `priority`),
ADD INDEX `idx_employee_status` (`employee_id`, `status`),
ADD INDEX `idx_assigned_status` (`assigned_to`, `status`);

ALTER TABLE `ticket_responses` 
ADD INDEX `idx_ticket_internal` (`ticket_id`, `is_internal`),
ADD INDEX `idx_user_responses` (`user_id`, `user_type`);

-- =====================================================
-- VIEWS FOR REPORTING (Optional)
-- =====================================================

-- Ticket Summary View
CREATE OR REPLACE VIEW `v_ticket_summary` AS
SELECT 
    t.ticket_id,
    t.subject,
    t.category,
    t.priority,
    t.status,
    t.created_at,
    t.updated_at,
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    e.email as employee_email,
    its.name as assigned_staff_name,
    (SELECT COUNT(*) FROM ticket_responses tr WHERE tr.ticket_id = t.ticket_id) as response_count,
    (SELECT COUNT(*) FROM ticket_responses tr WHERE tr.ticket_id = t.ticket_id AND tr.is_internal = 0) as public_response_count
FROM tickets t
LEFT JOIN employees e ON t.employee_id = e.id
LEFT JOIN it_staff its ON t.assigned_to = its.staff_id;

-- Staff Workload View
CREATE OR REPLACE VIEW `v_staff_workload` AS
SELECT 
    its.staff_id,
    its.name as staff_name,
    its.email,
    its.role,
    COUNT(CASE WHEN t.status = 'open' THEN 1 END) as open_tickets,
    COUNT(CASE WHEN t.status = 'in_progress' THEN 1 END) as in_progress_tickets,
    COUNT(CASE WHEN t.status IN ('resolved', 'closed') THEN 1 END) as completed_tickets,
    COUNT(t.ticket_id) as total_assigned
FROM it_staff its
LEFT JOIN tickets t ON its.staff_id = t.assigned_to
WHERE its.is_active = 1
GROUP BY its.staff_id, its.name, its.email, its.role;

-- =====================================================
-- COMPLETION MESSAGE
-- =====================================================

SELECT 'IT Ticketing System database schema created successfully!' as Status,
       'Remember to update your config/config.php file with the correct database credentials' as Note;