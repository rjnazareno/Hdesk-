-- ============================================
-- SLA Priority Mapping Migration
-- Date: February 6, 2026
-- Purpose: Implement category-based auto-priority and correct SLA response/resolution times
-- Based on IT Help Desk SLA Guide spreadsheet
-- ============================================

-- =====================================================
-- 1. UPDATE SLA POLICIES - Correct Response/Resolution Times
-- From the guide (using full 24-hour days, NOT business hours):
--   HIGH:   Response → 30 mins,  Resolution → 1 day (1440 min / 24 hours)
--   MEDIUM: Response → 4 hours (240 min), Resolution → 3 days (4320 min)
--   LOW:    Response → 1 day (1440 min), Resolution → 5 days (7200 min)
-- =====================================================

UPDATE sla_policies SET response_time = 30, resolution_time = 1440, is_business_hours = 0 WHERE priority = 'high' AND is_active = 1;
UPDATE sla_policies SET response_time = 240, resolution_time = 4320, is_business_hours = 0 WHERE priority = 'medium' AND is_active = 1;
UPDATE sla_policies SET response_time = 1440, resolution_time = 7200, is_business_hours = 0 WHERE priority = 'low' AND is_active = 1;

-- Deactivate urgent if still active
UPDATE sla_policies SET is_active = 0 WHERE priority = 'urgent';

-- =====================================================
-- 2. CREATE CATEGORY PRIORITY MAP TABLE
-- Maps each subcategory (issue type) to its default priority
-- When a user selects a subcategory, priority is auto-set
-- =====================================================

CREATE TABLE IF NOT EXISTS `category_priority_map` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `category_id` INT(11) NOT NULL COMMENT 'FK to categories table (subcategory)',
    `default_priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_category` (`category_id`),
    CONSTRAINT `fk_cpm_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. ADD HR SUBCATEGORIES (Issue Types)
-- COE, Salary Dispute, Timekeeping, Leave, General Inquiry
-- =====================================================

SET @dept_hr = (SELECT id FROM departments WHERE code = 'HR');

-- COE Sub-categories
SET @cat_coe = (SELECT id FROM categories WHERE name = 'Certificate of Employment (COE)' AND department_id = @dept_hr LIMIT 1);

INSERT IGNORE INTO `categories` (`department_id`, `parent_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_hr, @cat_coe, 'Single Document', 'Request for COE only', 'file-alt', '#10B981', 1),
(@dept_hr, @cat_coe, 'With other documents', 'COE with additional documents attached', 'copy', '#10B981', 2);

-- Salary Dispute Sub-categories
SET @cat_salary = (SELECT id FROM categories WHERE name = 'Salary Dispute' AND department_id = @dept_hr LIMIT 1);

INSERT IGNORE INTO `categories` (`department_id`, `parent_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_hr, @cat_salary, 'Payslip Dispute (a day before cutoff)', 'Urgent payslip dispute before payroll cutoff', 'exclamation-circle', '#EF4444', 1),
(@dept_hr, @cat_salary, 'Payslip Dispute (after cutoff)', 'Payslip dispute after payroll cutoff date', 'clock', '#EF4444', 2);

-- Timekeeping Sub-categories (Harley)
SET @cat_timekeeping = (SELECT id FROM categories WHERE name = 'Timekeeping concerns' AND department_id = @dept_hr LIMIT 1);

INSERT IGNORE INTO `categories` (`department_id`, `parent_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_hr, @cat_timekeeping, 'Log In Error', 'Cannot log in to Harley timekeeping system', 'exclamation-triangle', '#F59E0B', 1),
(@dept_hr, @cat_timekeeping, 'Missing Log In/Log Out', 'Missing or incorrect time entries in Harley', 'user-clock', '#F59E0B', 2);

-- Leave Sub-categories
SET @cat_leave = (SELECT id FROM categories WHERE name = 'Leave concerns' AND department_id = @dept_hr LIMIT 1);

INSERT IGNORE INTO `categories` (`department_id`, `parent_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_hr, @cat_leave, 'Leave Inquiry', 'Questions about leave policies and entitlements', 'question-circle', '#8B5CF6', 1),
(@dept_hr, @cat_leave, 'Leave Credit Balance', 'Check or dispute remaining leave credits', 'calculator', '#8B5CF6', 2);

-- General Inquiry Sub-categories
SET @cat_hr_general = (SELECT id FROM categories WHERE name = 'HR General Inquiry' AND department_id = @dept_hr LIMIT 1);

INSERT IGNORE INTO `categories` (`department_id`, `parent_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_hr, @cat_hr_general, 'Holiday Inquiry', 'Questions about holiday schedules and observances', 'calendar-day', '#6B7280', 1),
(@dept_hr, @cat_hr_general, 'Non-Harley, Payslip Dispute, Leave-Related inquiries', 'General inquiries not covered by other categories', 'info-circle', '#6B7280', 2);

-- =====================================================
-- 4. ADD IT GENERAL INQUIRY SUB-CATEGORY
-- =====================================================
SET @dept_it = (SELECT id FROM departments WHERE code = 'IT');
SET @cat_it_general = (SELECT id FROM categories WHERE name = 'IT General Inquiry' AND department_id = @dept_it LIMIT 1);

INSERT IGNORE INTO `categories` (`department_id`, `parent_id`, `name`, `description`, `icon`, `color`, `sort_order`) VALUES
(@dept_it, @cat_it_general, 'General IT', 'General IT support requests', 'question-circle', '#6B7280', 1);

-- =====================================================
-- 5. POPULATE PRIORITY MAP - IT Categories
-- =====================================================

-- === ACCESS Sub-categories ===
SET @cat_access = (SELECT id FROM categories WHERE name = 'Access' AND parent_id IS NULL AND department_id = @dept_it LIMIT 1);

-- Account Deactivation → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Account Deactivation' AND parent_id = @cat_access LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- Password Reset → HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'Password Reset' AND parent_id = @cat_access LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- Account Locked → HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'Account Locked' AND parent_id = @cat_access LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- Permission Request → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Permission Request' AND parent_id = @cat_access LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- New Account Request → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'New Account Request' AND parent_id = @cat_access LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- System Access Issue → HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'System Access Issue' AND parent_id = @cat_access LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- === HARDWARE Sub-categories ===
SET @cat_hardware = (SELECT id FROM categories WHERE name = 'Hardware' AND parent_id IS NULL AND department_id = @dept_it LIMIT 1);

-- Desktop/Laptop Issue → HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'Desktop/Laptop Issue' AND parent_id = @cat_hardware LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- Keyboard/Mouse → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Keyboard/Mouse' AND parent_id = @cat_hardware LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Phone/Headset → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Phone/Headset' AND parent_id = @cat_hardware LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- UPS/Power → HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'UPS/Power' AND parent_id = @cat_hardware LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- Monitor Problem → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Monitor Problem' AND parent_id = @cat_hardware LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Printer Issue → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Printer Issue' AND parent_id = @cat_hardware LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- New Hardware Request → LOW (not in spreadsheet, default to low)
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'New Hardware Request' AND parent_id = @cat_hardware LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- === EMAIL Sub-categories ===
SET @cat_email = (SELECT id FROM categories WHERE name = 'Email' AND parent_id IS NULL AND department_id = @dept_it LIMIT 1);

-- Cannot Send/Receive Email → HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'Cannot Send/Receive Email' AND parent_id = @cat_email LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- Email Recovery → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Email Recovery' AND parent_id = @cat_email LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Distribution List Request → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Distribution List Request' AND parent_id = @cat_email LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- Mobile Email Setup → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Mobile Email Setup' AND parent_id = @cat_email LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- Email Quota/Storage → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Email Quota/Storage' AND parent_id = @cat_email LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Outlook Configuration → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Outlook Configuration' AND parent_id = @cat_email LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- === SOFTWARE Sub-categories ===
SET @cat_software = (SELECT id FROM categories WHERE name = 'Software' AND parent_id IS NULL AND department_id = @dept_it LIMIT 1);

-- Antivirus/Security → HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'Antivirus/Security' AND parent_id = @cat_software LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- Application Error → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Application Error' AND parent_id = @cat_software LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Browser Issues → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Browser Issues' AND parent_id = @cat_software LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- License Request → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'License Request' AND parent_id = @cat_software LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- MS Office Issues → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'MS Office Issues' AND parent_id = @cat_software LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Software Installation → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Software Installation' AND parent_id = @cat_software LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- Software Update/Upgrade → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Software Update/Upgrade' AND parent_id = @cat_software LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- === NETWORK Sub-categories ===
SET @cat_network = (SELECT id FROM categories WHERE name = 'Network' AND parent_id IS NULL AND department_id = @dept_it LIMIT 1);

-- Network Drive Access → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Network Drive Access' AND parent_id = @cat_network LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Network Printer → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Network Printer' AND parent_id = @cat_network LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- No Internet Connection → HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'No Internet Connection' AND parent_id = @cat_network LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- Slow Connection → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Slow Connection' AND parent_id = @cat_network LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- VPN Issues → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'VPN Issues' AND parent_id = @cat_network LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- WiFi Problems → HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'WiFi Problems' AND parent_id = @cat_network LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- === IT GENERAL INQUIRY ===
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'General IT' AND parent_id = @cat_it_general LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- Also map the parent category itself
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'IT General Inquiry' AND department_id = @dept_it AND parent_id IS NULL LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- =====================================================
-- 6. POPULATE PRIORITY MAP - HR Categories
-- =====================================================

-- === COE Sub-categories ===
-- Single Document → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Single Document' AND parent_id = @cat_coe LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- With other documents → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'With other documents' AND parent_id = @cat_coe LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- === Salary Dispute Sub-categories ===
-- Payslip Dispute (a day before cutoff) → HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'Payslip Dispute (a day before cutoff)' AND parent_id = @cat_salary LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- Payslip Dispute (after cutoff) → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Payslip Dispute (after cutoff)' AND parent_id = @cat_salary LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- === Timekeeping Sub-categories ===
-- Log In Error → HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'Log In Error' AND parent_id = @cat_timekeeping LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- Missing Log In/Log Out → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Missing Log In/Log Out' AND parent_id = @cat_timekeeping LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- === Leave Sub-categories ===
-- Leave Inquiry → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Leave Inquiry' AND parent_id = @cat_leave LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- Leave Credit Balance → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Leave Credit Balance' AND parent_id = @cat_leave LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- === General Inquiry Sub-categories ===
-- Holiday Inquiry → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Holiday Inquiry' AND parent_id = @cat_hr_general LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- Non-Harley inquiries → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Non-Harley, Payslip Dispute, Leave-Related inquiries' AND parent_id = @cat_hr_general LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- =====================================================
-- 7. MAP PARENT CATEGORIES (fallback when no subcategory selected)
-- =====================================================

-- Access → MEDIUM (middle ground)
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Access' AND parent_id IS NULL AND department_id = @dept_it LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Hardware → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Hardware' AND parent_id IS NULL AND department_id = @dept_it LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Email → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Email' AND parent_id IS NULL AND department_id = @dept_it LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Software → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Software' AND parent_id IS NULL AND department_id = @dept_it LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Network → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Network' AND parent_id IS NULL AND department_id = @dept_it LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- COE → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Certificate of Employment (COE)' AND parent_id IS NULL AND department_id = @dept_hr LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- Salary Dispute → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Salary Dispute' AND parent_id IS NULL AND department_id = @dept_hr LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Timekeeping concerns → MEDIUM
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'medium' FROM categories WHERE name = 'Timekeeping concerns' AND parent_id IS NULL AND department_id = @dept_hr LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'medium';

-- Leave concerns → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Leave concerns' AND parent_id IS NULL AND department_id = @dept_hr LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- HR General Inquiry → LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'HR General Inquiry' AND parent_id IS NULL AND department_id = @dept_hr LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- =====================================================
-- 8. INDEXES
-- =====================================================
CREATE INDEX IF NOT EXISTS idx_cpm_priority ON category_priority_map (default_priority);

-- =====================================================
-- 9. VERIFY DATA
-- =====================================================
SELECT c.name as category_name, 
       pc.name as parent_category,
       cpm.default_priority,
       CASE cpm.default_priority
           WHEN 'high' THEN 'Response: 30min, Resolution: 1 day'
           WHEN 'medium' THEN 'Response: 4hrs, Resolution: 3 days'
           WHEN 'low' THEN 'Response: 1 day, Resolution: 5 days'
       END as sla_target
FROM category_priority_map cpm
JOIN categories c ON cpm.category_id = c.id
LEFT JOIN categories pc ON c.parent_id = pc.id
ORDER BY pc.name, c.name;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
-- Summary:
-- 1. Updated SLA policies: HIGH=30min/8h, MEDIUM=4h/2-3d, LOW=1d/3-5d
-- 2. Created category_priority_map table
-- 3. Added HR subcategories (COE, Salary, Timekeeping, Leave, General)
-- 4. Added IT General Inquiry subcategory
-- 5. Populated priority map for ALL subcategories per spreadsheet guide
-- 6. Added parent category default priorities as fallback
