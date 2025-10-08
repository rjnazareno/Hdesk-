-- IThelp Ticketing System Database Schema
-- Updated: October 8, 2025
-- Database: ithelp

-- Drop existing tables if they exist
DROP TABLE IF EXISTS `ticket_activity`;
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `employees`;
DROP TABLE IF EXISTS `users`;

-- Users table: stores IT staff and admin only
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('it_staff', 'admin') NOT NULL DEFAULT 'it_staff',
  `department` VARCHAR(50) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_role` (`role`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Employees table: stores regular employees who submit tickets
CREATE TABLE `employees` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) UNIQUE,
  `email` VARCHAR(100),
  `personal_email` VARCHAR(100),
  `password` VARCHAR(255),
  `fname` VARCHAR(100),
  `lname` VARCHAR(100),
  `company` VARCHAR(100),
  `position` VARCHAR(100),
  `contact` VARCHAR(20),
  `official_sched` INT(11) DEFAULT NULL,
  `role` ENUM('employee', 'manager', 'supervisor') DEFAULT 'employee',
  `status` ENUM('active', 'inactive', 'terminated') DEFAULT 'active',
  `profile_picture` VARCHAR(255),
  `profile_image` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email` (`email`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories table: ticket categories
CREATE TABLE `categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `color` VARCHAR(20) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tickets table: main ticketing data
CREATE TABLE `tickets` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` VARCHAR(20) NOT NULL UNIQUE,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `category_id` INT(11) NOT NULL,
  `priority` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
  `status` ENUM('pending', 'open', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'pending',
  `submitter_id` INT(11) NOT NULL,
  `submitter_type` ENUM('employee', 'user') NOT NULL DEFAULT 'employee',
  `assigned_to` INT(11) DEFAULT NULL,
  `resolution` TEXT DEFAULT NULL,
  `attachments` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` TIMESTAMP NULL DEFAULT NULL,
  `closed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ticket_number` (`ticket_number`),
  INDEX `idx_status` (`status`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_submitter` (`submitter_id`, `submitter_type`),
  INDEX `idx_assigned` (`assigned_to`),
  INDEX `idx_category` (`category_id`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`assigned_to`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket Activity table: logs all actions on tickets
CREATE TABLE `ticket_activity` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `user_type` ENUM('employee', 'user') NOT NULL DEFAULT 'user',
  `action_type` VARCHAR(50) NOT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `comment` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_ticket` (`ticket_id`),
  INDEX `idx_user` (`user_id`, `user_type`),
  INDEX `idx_created` (`created_at`),
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default IT staff/admin users (password: admin123)
INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`, `department`) VALUES
('admin', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin', 'IT'),
('mahfuzul', 'mahfuzul@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mahfuzul Islam', 'it_staff', 'IT');

-- Insert default employee (password: admin123)
INSERT INTO `employees` (`username`, `email`, `password`, `fname`, `lname`, `company`, `position`, `contact`, `role`, `status`) VALUES
('john.doe', 'john.doe@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'Company Inc', 'Sales Representative', '1234567890', 'employee', 'active');

-- Insert default categories
INSERT INTO `categories` (`name`, `description`, `icon`, `color`) VALUES
('Hardware', 'Hardware related issues (computers, printers, phones)', 'desktop', '#3B82F6'),
('Software', 'Software installation and troubleshooting', 'code', '#10B981'),
('Network', 'Network connectivity and access issues', 'wifi', '#F59E0B'),
('Email', 'Email account and configuration issues', 'mail', '#EF4444'),
('Access', 'System access and permission requests', 'key', '#8B5CF6'),
('Other', 'Other IT support requests', 'help-circle', '#6B7280');

-- Insert sample tickets for demonstration
INSERT INTO `tickets` (`ticket_number`, `title`, `description`, `category_id`, `priority`, `status`, `submitter_id`, `submitter_type`, `assigned_to`) VALUES
('TKT-2025-001', 'Laptop not connecting to WiFi', 'My laptop cannot connect to the office WiFi network. I have tried restarting but the issue persists.', 3, 'high', 'open', 1, 'employee', 2),
('TKT-2025-002', 'Need MS Office installation', 'Please install Microsoft Office on my new workstation.', 2, 'medium', 'pending', 1, 'employee', NULL),
('TKT-2025-003', 'Printer not working', 'The printer in the sales department is not responding to print commands.', 1, 'medium', 'in_progress', 1, 'employee', 2),
('TKT-2025-004', 'Email account setup', 'Need help setting up my email on my mobile phone.', 4, 'low', 'resolved', 1, 'employee', 2);

-- Insert sample activity logs
INSERT INTO `ticket_activity` (`ticket_id`, `user_id`, `user_type`, `action_type`, `new_value`, `comment`) VALUES
(1, 1, 'employee', 'created', 'open', 'Ticket created'),
(1, 2, 'user', 'assigned', 'Mahfuzul Islam', 'Ticket assigned to IT staff'),
(2, 1, 'employee', 'created', 'pending', 'Ticket created'),
(3, 1, 'employee', 'created', 'pending', 'Ticket created'),
(3, 2, 'user', 'status_change', 'in_progress', 'Started working on this issue'),
(4, 1, 'employee', 'created', 'pending', 'Ticket created'),
(4, 2, 'user', 'status_change', 'resolved', 'Email configured successfully');
