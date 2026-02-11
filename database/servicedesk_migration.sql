-- =====================================================
-- ServiceDesk Migration Script
-- Date: January 2026
-- Version: 2.0.0
-- 
-- This migration transforms "Resolve IT" into a
-- multi-department "ServiceDesk" platform
-- =====================================================

-- =====================================================
-- 1. DEPARTMENTS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS `departments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `code` VARCHAR(10) NOT NULL UNIQUE COMMENT 'Short code like IT, HR',
    `description` TEXT DEFAULT NULL,
    `icon` VARCHAR(50) DEFAULT 'building',
    `color` VARCHAR(20) DEFAULT '#3B82F6',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default departments
INSERT INTO `departments` (`name`, `code`, `description`, `icon`, `color`) VALUES
('Information Technology', 'IT', 'IT support including hardware, software, network, and system access issues', 'laptop-code', '#3B82F6'),
('Human Resources', 'HR', 'HR services including certificates, payroll inquiries, leave management, and employee relations', 'users', '#10B981')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- =====================================================
-- 2. UPDATE CATEGORIES TABLE - Add department_id
-- =====================================================
ALTER TABLE `categories` 
ADD COLUMN IF NOT EXISTS `department_id` INT(11) DEFAULT NULL AFTER `id`,
ADD COLUMN IF NOT EXISTS `parent_id` INT(11) DEFAULT NULL COMMENT 'For sub-categories',
ADD COLUMN IF NOT EXISTS `sort_order` INT(11) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `requires_fields` JSON DEFAULT NULL COMMENT 'Required custom fields for this category';

-- Add foreign key for department
ALTER TABLE `categories`
ADD CONSTRAINT `fk_category_department` FOREIGN KEY (`department_id`) 
REFERENCES `departments`(`id`) ON DELETE SET NULL;

-- Add self-referential foreign key for parent category
ALTER TABLE `categories`
ADD CONSTRAINT `fk_category_parent` FOREIGN KEY (`parent_id`) 
REFERENCES `categories`(`id`) ON DELETE SET NULL;

-- =====================================================
-- 3. UPDATE TICKETS TABLE - Add department routing
-- =====================================================
ALTER TABLE `tickets`
ADD COLUMN IF NOT EXISTS `department_id` INT(11) DEFAULT NULL AFTER `category_id`,
ADD COLUMN IF NOT EXISTS `grabbed_by` INT(11) DEFAULT NULL COMMENT 'IT staff who grabbed the ticket',
ADD COLUMN IF NOT EXISTS `grabbed_at` TIMESTAMP NULL DEFAULT NULL COMMENT 'When ticket was grabbed';

-- Add foreign key for department
ALTER TABLE `tickets`
ADD CONSTRAINT `fk_ticket_department` FOREIGN KEY (`department_id`) 
REFERENCES `departments`(`id`) ON DELETE SET NULL;

-- Add foreign key for grabbed_by
ALTER TABLE `tickets`
ADD CONSTRAINT `fk_ticket_grabbed_by` FOREIGN KEY (`grabbed_by`) 
REFERENCES `users`(`id`) ON DELETE SET NULL;

-- Add index for department filtering
ALTER TABLE `tickets` ADD INDEX `idx_department` (`department_id`);
ALTER TABLE `tickets` ADD INDEX `idx_grabbed` (`grabbed_by`);

-- =====================================================
-- 4. UPDATE USERS TABLE - Add department assignment
-- =====================================================
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `department_id` INT(11) DEFAULT NULL AFTER `department`,
ADD COLUMN IF NOT EXISTS `can_grab_tickets` TINYINT(1) DEFAULT 0 COMMENT 'Can this user grab tickets from queue';

-- Update IT users to IT department
UPDATE `users` u
SET `department_id` = (SELECT id FROM `departments` WHERE code = 'IT')
WHERE u.`department` LIKE '%IT%' OR u.`role` IN ('it_staff', 'admin');

-- =====================================================
-- 5. REMOVE SLA PAUSE FEATURE
-- =====================================================
-- Update sla_tracking table - deprecate pause columns
ALTER TABLE `sla_tracking`
MODIFY COLUMN `is_paused` TINYINT(1) DEFAULT 0 COMMENT 'DEPRECATED - SLA pause feature removed',
MODIFY COLUMN `paused_at` DATETIME NULL COMMENT 'DEPRECATED - SLA pause feature removed',
MODIFY COLUMN `pause_reason` VARCHAR(255) NULL COMMENT 'DEPRECATED - SLA pause feature removed',
MODIFY COLUMN `total_pause_minutes` INT(11) DEFAULT 0 COMMENT 'DEPRECATED - SLA pause feature removed';

-- Reset any paused SLAs
UPDATE `sla_tracking` SET `is_paused` = 0, `paused_at` = NULL, `pause_reason` = NULL WHERE `is_paused` = 1;

-- =====================================================
-- 6. HR CATEGORIES
-- =====================================================

-- Deactivate old categories that need replacement
UPDATE `categories` SET `is_active` = 0 WHERE `name` IN ('Harley', 'Other');

-- Insert HR Categories
INSERT INTO `categories` (`department_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
((SELECT id FROM departments WHERE code = 'HR'), 'Certificate of Employment (COE)', 'Request for employment certificates and related documents', 'file-certificate', '#10B981', 1),
((SELECT id FROM departments WHERE code = 'HR'), 'Salary Dispute', 'Inquiries and disputes regarding salary, deductions, or compensation', 'money-bill-wave', '#EF4444', 2),
((SELECT id FROM departments WHERE code = 'HR'), 'Timekeeping concerns', 'Past-dated leaves, time adjustments, attendance corrections', 'clock', '#F59E0B', 3),
((SELECT id FROM departments WHERE code = 'HR'), 'Leave concerns', 'Non-standard leaves: bereavement, solo parent, emergency, etc.', 'calendar-alt', '#8B5CF6', 4),
((SELECT id FROM departments WHERE code = 'HR'), 'HR General Inquiry', 'General HR questions and requests', 'question-circle', '#6B7280', 5)
ON DUPLICATE KEY UPDATE `department_id` = VALUES(`department_id`);

-- =====================================================
-- 7. IT CATEGORIES (Granular Sub-categories)
-- =====================================================

-- Update existing IT categories with department
UPDATE `categories` c
SET c.`department_id` = (SELECT id FROM departments WHERE code = 'IT')
WHERE c.`name` IN ('Hardware', 'Software', 'Network', 'Email', 'Access');

-- Create temporary variables for parent category IDs
SET @dept_it = (SELECT id FROM departments WHERE code = 'IT');
SET @cat_hardware = (SELECT id FROM categories WHERE name = 'Hardware' LIMIT 1);
SET @cat_software = (SELECT id FROM categories WHERE name = 'Software' LIMIT 1);
SET @cat_network = (SELECT id FROM categories WHERE name = 'Network' LIMIT 1);
SET @cat_email = (SELECT id FROM categories WHERE name = 'Email' LIMIT 1);
SET @cat_access = (SELECT id FROM categories WHERE name = 'Access' LIMIT 1);

-- Hardware Sub-categories
INSERT INTO `categories` (`department_id`, `parent_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_it, @cat_hardware, 'Desktop/Laptop Issue', 'Computer not starting, slow performance, crashes', 'desktop', '#3B82F6', 1),
(@dept_it, @cat_hardware, 'Monitor Problem', 'Display issues, no signal, flickering', 'tv', '#3B82F6', 2),
(@dept_it, @cat_hardware, 'Keyboard/Mouse', 'Input device issues, not responding', 'keyboard', '#3B82F6', 3),
(@dept_it, @cat_hardware, 'Printer Issue', 'Printing problems, paper jam, connectivity', 'print', '#3B82F6', 4),
(@dept_it, @cat_hardware, 'UPS/Power', 'Power supply issues, battery backup', 'plug', '#3B82F6', 5),
(@dept_it, @cat_hardware, 'Phone/Headset', 'Desk phone, IP phone, headset issues', 'phone-alt', '#3B82F6', 6),
(@dept_it, @cat_hardware, 'New Hardware Request', 'Request for new equipment or replacement', 'plus-square', '#3B82F6', 7)
ON DUPLICATE KEY UPDATE `department_id` = VALUES(`department_id`);

-- Software Sub-categories
INSERT INTO `categories` (`department_id`, `parent_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_it, @cat_software, 'MS Office Issues', 'Word, Excel, PowerPoint, Outlook problems', 'file-word', '#10B981', 1),
(@dept_it, @cat_software, 'Software Installation', 'New software installation request', 'download', '#10B981', 2),
(@dept_it, @cat_software, 'Software Update/Upgrade', 'Update or upgrade existing software', 'sync', '#10B981', 3),
(@dept_it, @cat_software, 'Application Error', 'Software crashes, errors, bugs', 'bug', '#10B981', 4),
(@dept_it, @cat_software, 'Browser Issues', 'Chrome, Edge, Firefox problems', 'globe', '#10B981', 5),
(@dept_it, @cat_software, 'Antivirus/Security', 'Virus alerts, security software issues', 'shield-alt', '#10B981', 6),
(@dept_it, @cat_software, 'License Request', 'Software license activation or renewal', 'key', '#10B981', 7)
ON DUPLICATE KEY UPDATE `department_id` = VALUES(`department_id`);

-- Network Sub-categories
INSERT INTO `categories` (`department_id`, `parent_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_it, @cat_network, 'No Internet Connection', 'Cannot connect to internet', 'wifi-slash', '#F59E0B', 1),
(@dept_it, @cat_network, 'Slow Connection', 'Internet/network speed issues', 'tachometer-alt', '#F59E0B', 2),
(@dept_it, @cat_network, 'VPN Issues', 'Cannot connect or use VPN', 'network-wired', '#F59E0B', 3),
(@dept_it, @cat_network, 'WiFi Problems', 'Wireless connectivity issues', 'wifi', '#F59E0B', 4),
(@dept_it, @cat_network, 'Network Drive Access', 'Shared folder/drive access issues', 'folder-open', '#F59E0B', 5),
(@dept_it, @cat_network, 'Network Printer', 'Cannot connect to network printer', 'print', '#F59E0B', 6)
ON DUPLICATE KEY UPDATE `department_id` = VALUES(`department_id`);

-- Email Sub-categories
INSERT INTO `categories` (`department_id`, `parent_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_it, @cat_email, 'Cannot Send/Receive Email', 'Email delivery issues', 'envelope', '#EF4444', 1),
(@dept_it, @cat_email, 'Outlook Configuration', 'Email client setup and settings', 'cog', '#EF4444', 2),
(@dept_it, @cat_email, 'Mobile Email Setup', 'Email on phone/tablet', 'mobile-alt', '#EF4444', 3),
(@dept_it, @cat_email, 'Email Quota/Storage', 'Mailbox full or storage issues', 'database', '#EF4444', 4),
(@dept_it, @cat_email, 'Distribution List Request', 'Create or modify email groups', 'users', '#EF4444', 5),
(@dept_it, @cat_email, 'Email Recovery', 'Recover deleted emails', 'undo', '#EF4444', 6)
ON DUPLICATE KEY UPDATE `department_id` = VALUES(`department_id`);

-- Access Sub-categories
INSERT INTO `categories` (`department_id`, `parent_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_it, @cat_access, 'Password Reset', 'Cannot login, forgot password', 'unlock', '#8B5CF6', 1),
(@dept_it, @cat_access, 'Account Locked', 'User account is locked out', 'lock', '#8B5CF6', 2),
(@dept_it, @cat_access, 'New Account Request', 'Create new user account', 'user-plus', '#8B5CF6', 3),
(@dept_it, @cat_access, 'Permission Request', 'Request access to systems/folders', 'user-shield', '#8B5CF6', 4),
(@dept_it, @cat_access, 'System Access Issue', 'Cannot access specific system/app', 'exclamation-triangle', '#8B5CF6', 5),
(@dept_it, @cat_access, 'Account Deactivation', 'Request to disable/remove account', 'user-minus', '#8B5CF6', 6)
ON DUPLICATE KEY UPDATE `department_id` = VALUES(`department_id`);

-- IT General Category
INSERT INTO `categories` (`department_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_it, 'IT General Inquiry', 'General IT questions and requests not covered elsewhere', 'question-circle', '#6B7280', 99)
ON DUPLICATE KEY UPDATE `department_id` = VALUES(`department_id`);

-- =====================================================
-- 8. TICKET ACTIVITY - Add grab action type
-- =====================================================
-- No schema change needed, just document new action types:
-- 'grabbed' - Admin grabbed ticket from queue
-- 'released' - Admin released ticket back to queue

-- =====================================================
-- 9. CREATE VIEW FOR TICKET QUEUE (Department Buckets)
-- =====================================================
DROP VIEW IF EXISTS `v_ticket_queue`;
CREATE VIEW `v_ticket_queue` AS
SELECT 
    t.id,
    t.ticket_number,
    t.title,
    t.description,
    t.priority,
    t.status,
    t.created_at,
    t.updated_at,
    d.id as department_id,
    d.name as department_name,
    d.code as department_code,
    c.id as category_id,
    c.name as category_name,
    c.color as category_color,
    CASE 
        WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
        ELSE u1.full_name
    END as submitter_name,
    CASE 
        WHEN t.submitter_type = 'employee' THEN e.email
        ELSE u1.email
    END as submitter_email,
    t.grabbed_by,
    t.grabbed_at,
    u2.full_name as grabbed_by_name,
    t.assigned_to,
    u3.full_name as assigned_name,
    st.resolution_due_at,
    st.resolution_sla_status,
    CASE 
        WHEN t.status IN ('resolved', 'closed') THEN 'completed'
        WHEN NOW() > st.resolution_due_at THEN 'breached'
        WHEN TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) <= 60 THEN 'at_risk'
        ELSE 'safe'
    END as sla_status,
    TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at) as minutes_remaining
FROM tickets t
LEFT JOIN departments d ON t.department_id = d.id
LEFT JOIN categories c ON t.category_id = c.id
LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
LEFT JOIN users u2 ON t.grabbed_by = u2.id
LEFT JOIN users u3 ON t.assigned_to = u3.id
LEFT JOIN sla_tracking st ON t.id = st.ticket_id
WHERE t.status NOT IN ('closed');

-- =====================================================
-- 10. UPDATE EXISTING TICKETS - Set default department
-- =====================================================
-- Set all existing tickets to IT department
UPDATE `tickets` t
SET t.`department_id` = (SELECT id FROM `departments` WHERE code = 'IT')
WHERE t.`department_id` IS NULL;

-- Also set grabbed_by = assigned_to for existing assigned tickets
UPDATE `tickets` t
SET t.`grabbed_by` = t.`assigned_to`, 
    t.`grabbed_at` = t.`updated_at`
WHERE t.`assigned_to` IS NOT NULL AND t.`grabbed_by` IS NULL;

-- =====================================================
-- 11. INDEXES FOR PERFORMANCE
-- =====================================================
ALTER TABLE `categories` ADD INDEX IF NOT EXISTS `idx_department` (`department_id`);
ALTER TABLE `categories` ADD INDEX IF NOT EXISTS `idx_parent` (`parent_id`);
ALTER TABLE `tickets` ADD INDEX IF NOT EXISTS `idx_dept_status` (`department_id`, `status`);
ALTER TABLE `tickets` ADD INDEX IF NOT EXISTS `idx_grabbed_status` (`grabbed_by`, `status`);

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
-- Summary of changes:
-- 1. Created departments table (IT, HR)
-- 2. Added department_id to categories with parent/child support
-- 3. Added department_id, grabbed_by, grabbed_at to tickets
-- 4. Added department_id to users for staff assignment
-- 5. Deprecated SLA pause feature (columns retained for data integrity)
-- 6. Added HR categories
-- 7. Added granular IT sub-categories
-- 8. Created v_ticket_queue view for department bucket routing
-- 9. Migrated existing data to IT department
