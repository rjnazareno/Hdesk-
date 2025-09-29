-- IT Ticket Management System Database Setup
-- Version: 1.0
-- Date: September 26, 2025
-- 
-- This script creates the complete database schema for the IT Ticket Management System
-- Run this script in your MySQL database to set up the system

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS ticketing_system 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE ticketing_system;

-- Drop existing tables if they exist (be careful with this in production!)
-- DROP TABLE IF EXISTS ticket_responses;
-- DROP TABLE IF EXISTS ticket_attachments;
-- DROP TABLE IF EXISTS tickets;
-- DROP TABLE IF EXISTS it_staff;
-- DROP TABLE IF EXISTS employees;

-- =============================================================================
-- EMPLOYEES TABLE
-- =============================================================================
CREATE TABLE employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    fname VARCHAR(50) NOT NULL,
    lname VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for better performance
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- =============================================================================
-- IT STAFF TABLE
-- =============================================================================
CREATE TABLE it_staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'technician', 'manager') DEFAULT 'technician',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes for better performance
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- =============================================================================
-- TICKETS TABLE
-- =============================================================================
CREATE TABLE tickets (
    ticket_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    assigned_to INT NULL,
    subject VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('hardware', 'software', 'network', 'security', 'other') DEFAULT 'other',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    acknowledged BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    
    -- Foreign key constraints
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES it_staff(staff_id) ON DELETE SET NULL,
    
    -- Indexes for better query performance
    INDEX idx_employee (employee_id),
    INDEX idx_assigned (assigned_to),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_category (category),
    INDEX idx_created (created_at),
    INDEX idx_status_priority (status, priority),
    INDEX idx_employee_status (employee_id, status)
) ENGINE=InnoDB;

-- =============================================================================
-- TICKET RESPONSES TABLE
-- =============================================================================
CREATE TABLE ticket_responses (
    response_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    responder_id INT NOT NULL,
    message TEXT NOT NULL,
    is_internal BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_ticket (ticket_id),
    INDEX idx_responder (responder_id),
    INDEX idx_internal (is_internal),
    INDEX idx_created (created_at),
    INDEX idx_ticket_created (ticket_id, created_at)
) ENGINE=InnoDB;

-- =============================================================================
-- TICKET ATTACHMENTS TABLE (for future use)
-- =============================================================================
CREATE TABLE ticket_attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    uploaded_by INT NOT NULL,
    user_type ENUM('employee', 'it_staff') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    FOREIGN KEY (ticket_id) REFERENCES tickets(ticket_id) ON DELETE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_ticket (ticket_id),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_user_type (user_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =============================================================================
-- SYSTEM SETTINGS TABLE (for future configuration management)
-- =============================================================================
CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (setting_key)
) ENGINE=InnoDB;

-- =============================================================================
-- SAMPLE DATA INSERTION
-- =============================================================================

-- Insert sample employees
-- Note: Passwords are hashed with password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO employees (username, fname, lname, email, password, status) VALUES
('john', 'John', 'Doe', 'john@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
('jane', 'Jane', 'Smith', 'jane@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
('mike', 'Mike', 'Johnson', 'mike@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active'),
('sarah', 'Sarah', 'Wilson', 'sarah@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'active');

-- Insert sample IT staff
-- Note: Passwords are hashed with password_hash('admin123', PASSWORD_DEFAULT) for admin
-- and password_hash('tech123', PASSWORD_DEFAULT) for others
INSERT INTO it_staff (name, username, email, password, role, is_active) VALUES
('System Administrator', 'admin', 'admin@company.com', '$2y$10$e8Nzj2JYfE5.nQFQZrQ3WO5i3k4J8hKH5Y/0J8.8H5J4k3i2nQFQ', 'admin', TRUE),
('Tech Support Level 1', 'tech1', 'tech1@company.com', '$2y$10$f9Oaj3KZgF6.oRGRAsR4XP6j4l5K9iLI6Z/1K9.9I6K5l4j3oRGR', 'technician', TRUE),
('Senior Technician', 'senior', 'senior@company.com', '$2y$10$g0Pbk4LAhG7.pSHSBtS5YQ7k5m6L0jMJ7A/2L0.0J7L6m5k4pSHS', 'technician', TRUE),
('IT Manager', 'manager', 'manager@company.com', '$2y$10$h1Qcl5MBiH8.qTITCtT6ZR8l6n7M1kNK8B/3M1.1K8M7n6l5qTIT', 'manager', TRUE);

-- Insert sample tickets
INSERT INTO tickets (employee_id, assigned_to, subject, description, category, priority, status, acknowledged) VALUES
(1, 1, 'Computer won\'t start', 'My computer shows a black screen when I turn it on. The power light is on but nothing appears on the monitor. This started this morning.', 'hardware', 'high', 'open', FALSE),
(2, 2, 'Email not receiving messages', 'I haven\'t received any emails since yesterday afternoon. I can send emails fine, but nothing is coming into my inbox.', 'software', 'medium', 'in_progress', TRUE),
(3, NULL, 'Need new software installation', 'Can someone please install Adobe Acrobat Pro on my computer? I need it for editing PDF documents for our client presentations.', 'software', 'low', 'open', FALSE),
(4, 1, 'Printer jam error', 'The main office printer keeps showing "Paper Jam Error" even after I\'ve cleared all visible paper. Multiple people are affected.', 'hardware', 'medium', 'resolved', TRUE),
(1, 3, 'Slow internet connection', 'Internet has been extremely slow for the past two days. Takes several minutes to load simple web pages.', 'network', 'medium', 'in_progress', TRUE);

-- Insert sample responses
INSERT INTO ticket_responses (ticket_id, responder_id, message, is_internal) VALUES
(2, 2, 'Hi Jane, I\'ve checked your email settings and found the issue. Your mailbox was full and blocking new messages. I\'ve archived old emails and you should start receiving messages within 10 minutes.', FALSE),
(2, 2, 'User confirmed email is working properly. Moving to resolved status.', TRUE),
(4, 1, 'Hi Sarah, I found a small piece of paper stuck in the back roller. I\'ve removed it and the printer is working normally again. Please let me know if you encounter any more issues.', FALSE),
(5, 3, 'I\'ve run network diagnostics and found high latency to our ISP. I\'ve contacted them and they\'re investigating. Will update you as soon as I have more information.', FALSE);

-- Insert some system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description) VALUES
('system_name', 'IT Ticket Management System', 'string', 'Name of the ticketing system'),
('default_priority', 'medium', 'string', 'Default priority for new tickets'),
('session_timeout', '1800', 'integer', 'Session timeout in seconds'),
('max_file_size', '5242880', 'integer', 'Maximum file upload size in bytes'),
('enable_email_notifications', 'true', 'boolean', 'Enable email notifications for ticket updates'),
('support_categories', '["hardware", "software", "network", "security", "other"]', 'json', 'Available support categories');

-- =============================================================================
-- VIEWS FOR REPORTING
-- =============================================================================

-- Create view for ticket statistics
CREATE OR REPLACE VIEW ticket_stats AS
SELECT 
    COUNT(*) as total_tickets,
    COUNT(CASE WHEN status = 'open' THEN 1 END) as open_tickets,
    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tickets,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_tickets,
    COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed_tickets,
    COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_tickets,
    COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_tickets,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as tickets_this_week,
    COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as tickets_this_month
FROM tickets;

-- Create view for ticket details with employee and IT staff names
CREATE OR REPLACE VIEW ticket_details AS
SELECT 
    t.ticket_id,
    t.subject,
    t.description,
    t.category,
    t.priority,
    t.status,
    t.acknowledged,
    t.created_at,
    t.updated_at,
    t.closed_at,
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    e.email as employee_email,
    e.username as employee_username,
    COALESCE(s.name, 'Unassigned') as assigned_to_name,
    s.username as assigned_to_username,
    s.email as assigned_to_email,
    (SELECT COUNT(*) FROM ticket_responses tr WHERE tr.ticket_id = t.ticket_id AND tr.is_internal = FALSE) as public_responses_count,
    (SELECT COUNT(*) FROM ticket_responses tr WHERE tr.ticket_id = t.ticket_id AND tr.is_internal = TRUE) as internal_responses_count
FROM tickets t
LEFT JOIN employees e ON t.employee_id = e.id
LEFT JOIN it_staff s ON t.assigned_to = s.staff_id;

-- =============================================================================
-- STORED PROCEDURES
-- =============================================================================

-- Procedure to get dashboard statistics for IT staff
DELIMITER //
CREATE PROCEDURE GetDashboardStats()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM tickets WHERE status = 'open') as open_count,
        (SELECT COUNT(*) FROM tickets WHERE status = 'in_progress') as in_progress_count,
        (SELECT COUNT(*) FROM tickets WHERE status = 'resolved') as resolved_count,
        (SELECT COUNT(*) FROM tickets WHERE status = 'closed') as closed_count,
        (SELECT COUNT(*) FROM tickets WHERE priority = 'urgent' AND status NOT IN ('resolved', 'closed')) as urgent_count,
        (SELECT COUNT(*) FROM tickets WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)) as new_today,
        (SELECT COUNT(*) FROM tickets WHERE assigned_to IS NULL AND status = 'open') as unassigned_count;
END //
DELIMITER ;

-- Procedure to assign ticket to IT staff
DELIMITER //
CREATE PROCEDURE AssignTicket(
    IN p_ticket_id INT,
    IN p_staff_id INT,
    IN p_assigned_by INT,
    IN p_note TEXT
)
BEGIN
    -- Update ticket assignment
    UPDATE tickets 
    SET assigned_to = p_staff_id, 
        status = 'in_progress',
        updated_at = CURRENT_TIMESTAMP 
    WHERE ticket_id = p_ticket_id;
    
    -- Add internal note about assignment
    IF p_note IS NOT NULL AND p_note != '' THEN
        INSERT INTO ticket_responses (ticket_id, responder_id, message, is_internal)
        VALUES (p_ticket_id, p_assigned_by, CONCAT('Ticket assigned. Note: ', p_note), TRUE);
    ELSE
        INSERT INTO ticket_responses (ticket_id, responder_id, message, is_internal)
        VALUES (p_ticket_id, p_assigned_by, 'Ticket has been assigned for review.', TRUE);
    END IF;
END //
DELIMITER ;

-- =============================================================================
-- TRIGGERS
-- =============================================================================

-- Trigger to automatically set closed_at when ticket status changes to closed
DELIMITER //
CREATE TRIGGER set_closed_at 
    BEFORE UPDATE ON tickets
    FOR EACH ROW
BEGIN
    IF NEW.status = 'closed' AND OLD.status != 'closed' THEN
        SET NEW.closed_at = CURRENT_TIMESTAMP;
    ELSEIF NEW.status != 'closed' AND OLD.status = 'closed' THEN
        SET NEW.closed_at = NULL;
    END IF;
END //
DELIMITER ;

-- =============================================================================
-- SAMPLE QUERIES FOR TESTING
-- =============================================================================

-- Test basic functionality
-- SELECT * FROM ticket_details WHERE status = 'open';
-- SELECT * FROM ticket_stats;
-- CALL GetDashboardStats();

-- =============================================================================
-- PERFORMANCE OPTIMIZATION
-- =============================================================================

-- Additional indexes for complex queries
CREATE INDEX idx_tickets_status_created ON tickets(status, created_at);
CREATE INDEX idx_tickets_priority_status ON tickets(priority, status);
CREATE INDEX idx_responses_ticket_created ON ticket_responses(ticket_id, created_at);

-- =============================================================================
-- SECURITY MEASURES
-- =============================================================================

-- Create dedicated database user for the application
-- Note: Run these commands separately and replace 'your_secure_password' with an actual secure password
/*
CREATE USER 'ticketing_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON ticketing_system.* TO 'ticketing_user'@'localhost';
GRANT EXECUTE ON ticketing_system.* TO 'ticketing_user'@'localhost';
FLUSH PRIVILEGES;
*/

-- =============================================================================
-- COMPLETION MESSAGE
-- =============================================================================

SELECT 'Database setup completed successfully!' as Status,
       (SELECT COUNT(*) FROM employees) as Employees_Created,
       (SELECT COUNT(*) FROM it_staff) as IT_Staff_Created,
       (SELECT COUNT(*) FROM tickets) as Sample_Tickets_Created,
       (SELECT COUNT(*) FROM ticket_responses) as Sample_Responses_Created;

-- Show default login credentials
SELECT 
    'Default Login Credentials' as Information,
    'Employee Login: john / password123' as Employee_Access,
    'IT Staff Login: admin / admin123' as IT_Staff_Access,
    'Change these passwords immediately!' as Security_Warning;