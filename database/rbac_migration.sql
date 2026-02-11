-- =====================================================
-- RBAC (Role-Based Access Control) Migration Script
-- Date: February 2026
-- Version: 3.0.0
-- 
-- Implements hierarchical role system:
-- - Super Admin (system-wide access)
-- - Department Admin (department-scoped access)  
-- - Employee/User (submit & view own tickets only)
-- =====================================================

-- =====================================================
-- 1. ROLES TABLE - Define all system roles
-- =====================================================
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `slug` VARCHAR(50) NOT NULL UNIQUE COMMENT 'URL-friendly identifier',
    `description` TEXT DEFAULT NULL,
    `hierarchy_level` INT(11) NOT NULL DEFAULT 0 COMMENT 'Higher = more permissions (100=super_admin, 50=dept_admin, 10=employee)',
    `is_system_role` TINYINT(1) DEFAULT 0 COMMENT 'Cannot be deleted if system role',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_hierarchy` (`hierarchy_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO `roles` (`name`, `slug`, `description`, `hierarchy_level`, `is_system_role`) VALUES
('Super Admin', 'super_admin', 'Full system access. Can manage all users, departments, tickets, and system settings.', 100, 1),
('Department Admin', 'dept_admin', 'Department-scoped access. Can manage tickets and staff within assigned department only.', 50, 1),
('IT Staff', 'it_staff', 'Can process tickets within assigned department. Limited management capabilities.', 30, 1),
('Employee', 'employee', 'Can submit tickets and view their own tickets only.', 10, 1)
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- =====================================================
-- 2. PERMISSIONS TABLE - Granular permission definitions
-- =====================================================
CREATE TABLE IF NOT EXISTS `permissions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `category` VARCHAR(50) DEFAULT 'general' COMMENT 'Group permissions: tickets, users, departments, reports, settings',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_slug` (`slug`),
    INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert all system permissions
INSERT INTO `permissions` (`name`, `slug`, `description`, `category`) VALUES
-- Ticket Permissions
('View All Tickets', 'tickets.view_all', 'View all tickets across all departments', 'tickets'),
('View Department Tickets', 'tickets.view_department', 'View tickets within assigned department only', 'tickets'),
('View Own Tickets', 'tickets.view_own', 'View only self-submitted tickets', 'tickets'),
('Create Tickets', 'tickets.create', 'Create new support tickets', 'tickets'),
('Assign Tickets', 'tickets.assign', 'Assign tickets to staff members', 'tickets'),
('Reassign Tickets', 'tickets.reassign', 'Reassign tickets to different staff or department', 'tickets'),
('Update Ticket Status', 'tickets.update_status', 'Change ticket status (open, in_progress, resolved, closed)', 'tickets'),
('Update Ticket Priority', 'tickets.update_priority', 'Change ticket priority', 'tickets'),
('Delete Tickets', 'tickets.delete', 'Permanently delete tickets', 'tickets'),
('Override Tickets', 'tickets.override', 'Override any ticket regardless of assignment', 'tickets'),
('Grab Tickets', 'tickets.grab', 'Grab unassigned tickets from queue', 'tickets'),
('Release Tickets', 'tickets.release', 'Release grabbed tickets back to queue', 'tickets'),
('Add Comments', 'tickets.comment', 'Add comments to tickets', 'tickets'),
('View Ticket History', 'tickets.view_history', 'View ticket activity history', 'tickets'),

-- User Management Permissions
('View All Users', 'users.view_all', 'View all users across system', 'users'),
('View Department Users', 'users.view_department', 'View users within assigned department', 'users'),
('Create Users', 'users.create', 'Create new user accounts', 'users'),
('Create Admins', 'users.create_admin', 'Create admin accounts (Super Admin only)', 'users'),
('Edit Users', 'users.edit', 'Edit user information', 'users'),
('Deactivate Users', 'users.deactivate', 'Deactivate/activate user accounts', 'users'),
('Delete Users', 'users.delete', 'Permanently delete user accounts', 'users'),
('Assign User Roles', 'users.assign_role', 'Assign roles to users', 'users'),
('Assign User Department', 'users.assign_department', 'Assign users to departments', 'users'),

-- Department Management Permissions
('View All Departments', 'departments.view_all', 'View all departments', 'departments'),
('Create Departments', 'departments.create', 'Create new departments', 'departments'),
('Edit Departments', 'departments.edit', 'Edit department information', 'departments'),
('Delete Departments', 'departments.delete', 'Delete departments', 'departments'),
('Manage Department Categories', 'departments.manage_categories', 'Manage categories within department', 'departments'),

-- Report Permissions
('View All Reports', 'reports.view_all', 'View reports for all departments', 'reports'),
('View Department Reports', 'reports.view_department', 'View reports for assigned department only', 'reports'),
('Export Reports', 'reports.export', 'Export reports to Excel/PDF', 'reports'),
('View Analytics Dashboard', 'reports.analytics', 'Access analytics and performance metrics', 'reports'),

-- Settings Permissions
('Manage System Settings', 'settings.system', 'Modify system-wide configuration', 'settings'),
('Manage SLA Policies', 'settings.sla', 'Configure SLA policies', 'settings'),
('Manage Email Templates', 'settings.email_templates', 'Edit notification email templates', 'settings'),
('Manage Roles & Permissions', 'settings.roles', 'Create and modify roles/permissions', 'settings'),
('View Audit Log', 'settings.audit_log', 'View system audit trail', 'settings')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- =====================================================
-- 3. ROLE_PERMISSIONS TABLE - Map permissions to roles
-- =====================================================
CREATE TABLE IF NOT EXISTS `role_permissions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `role_id` INT(11) NOT NULL,
    `permission_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_role_permission` (`role_id`, `permission_id`),
    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assign permissions to Super Admin (ALL permissions)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 
    (SELECT id FROM roles WHERE slug = 'super_admin'),
    p.id
FROM permissions p
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- Assign permissions to Department Admin
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 
    (SELECT id FROM roles WHERE slug = 'dept_admin'),
    p.id
FROM permissions p
WHERE p.slug IN (
    'tickets.view_department',
    'tickets.view_own',
    'tickets.create',
    'tickets.assign',
    'tickets.reassign',
    'tickets.update_status',
    'tickets.update_priority',
    'tickets.grab',
    'tickets.release',
    'tickets.comment',
    'tickets.view_history',
    'users.view_department',
    'reports.view_department',
    'reports.export',
    'reports.analytics',
    'departments.manage_categories'
)
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- Assign permissions to IT Staff
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 
    (SELECT id FROM roles WHERE slug = 'it_staff'),
    p.id
FROM permissions p
WHERE p.slug IN (
    'tickets.view_department',
    'tickets.view_own',
    'tickets.create',
    'tickets.update_status',
    'tickets.grab',
    'tickets.release',
    'tickets.comment',
    'tickets.view_history'
)
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- Assign permissions to Employee
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 
    (SELECT id FROM roles WHERE slug = 'employee'),
    p.id
FROM permissions p
WHERE p.slug IN (
    'tickets.view_own',
    'tickets.create',
    'tickets.comment'
)
ON DUPLICATE KEY UPDATE `role_id` = `role_id`;

-- =====================================================
-- 4. USER_DEPARTMENTS TABLE - Assign users to departments
-- =====================================================
CREATE TABLE IF NOT EXISTS `user_departments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `department_id` INT(11) NOT NULL,
    `is_primary` TINYINT(1) DEFAULT 1 COMMENT 'Primary department for the user',
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `assigned_by` INT(11) DEFAULT NULL COMMENT 'Super Admin who assigned this',
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_dept` (`user_id`, `department_id`),
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`department_id`) REFERENCES `departments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user` (`user_id`),
    INDEX `idx_department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. UPDATE USERS TABLE - Add role_id column
-- =====================================================
-- First, add the role_id column
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `role_id` INT(11) DEFAULT NULL AFTER `role`,
ADD COLUMN IF NOT EXISTS `created_by` INT(11) DEFAULT NULL COMMENT 'Super Admin who created this user',
ADD COLUMN IF NOT EXISTS `last_login_at` TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `deactivated_at` TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `deactivated_by` INT(11) DEFAULT NULL;

-- Add foreign key for role
ALTER TABLE `users`
ADD CONSTRAINT `fk_user_role` FOREIGN KEY (`role_id`) 
REFERENCES `roles`(`id`) ON DELETE SET NULL;

-- Migrate existing roles to new role_id system
UPDATE `users` u
SET u.role_id = (
    CASE 
        WHEN u.role = 'admin' THEN (SELECT id FROM roles WHERE slug = 'super_admin')
        WHEN u.role = 'it_staff' THEN (SELECT id FROM roles WHERE slug = 'it_staff')
        ELSE (SELECT id FROM roles WHERE slug = 'employee')
    END
)
WHERE u.role_id IS NULL;

-- =====================================================
-- 6. TICKET ASSIGNMENT LOG - Track all assignments
-- =====================================================
CREATE TABLE IF NOT EXISTS `ticket_assignments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `ticket_id` INT(11) NOT NULL,
    `assigned_from_user_id` INT(11) DEFAULT NULL COMMENT 'Previous assignee (NULL if unassigned)',
    `assigned_to_user_id` INT(11) DEFAULT NULL COMMENT 'New assignee',
    `assigned_from_dept_id` INT(11) DEFAULT NULL COMMENT 'Previous department',
    `assigned_to_dept_id` INT(11) DEFAULT NULL COMMENT 'New department',
    `assigned_by` INT(11) NOT NULL COMMENT 'User who made the assignment',
    `assignment_type` ENUM('initial', 'reassign', 'escalate', 'override', 'grab', 'release') NOT NULL DEFAULT 'initial',
    `notes` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`assigned_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_ticket` (`ticket_id`),
    INDEX `idx_assigned_to` (`assigned_to_user_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. AUDIT LOG - Track all administrative actions
-- =====================================================
CREATE TABLE IF NOT EXISTS `audit_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL COMMENT 'User who performed the action',
    `action_type` VARCHAR(50) NOT NULL COMMENT 'create, update, delete, login, logout, etc.',
    `entity_type` VARCHAR(50) NOT NULL COMMENT 'user, ticket, department, role, etc.',
    `entity_id` INT(11) DEFAULT NULL COMMENT 'ID of the affected entity',
    `old_values` JSON DEFAULT NULL COMMENT 'Previous state (for updates)',
    `new_values` JSON DEFAULT NULL COMMENT 'New state (for creates/updates)',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user` (`user_id`),
    INDEX `idx_action` (`action_type`),
    INDEX `idx_entity` (`entity_type`, `entity_id`),
    INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. CREATE SUPER ADMIN ACCOUNT (if not exists)
-- =====================================================
-- Update existing admin to super_admin
UPDATE `users` 
SET `role_id` = (SELECT id FROM roles WHERE slug = 'super_admin')
WHERE `role` = 'admin' AND `username` = 'admin';

-- =====================================================
-- 9. CREATE VIEWS FOR EASY QUERYING
-- =====================================================

-- View: Users with their roles and departments
CREATE OR REPLACE VIEW `v_users_with_roles` AS
SELECT 
    u.id,
    u.username,
    u.email,
    u.full_name,
    u.role AS legacy_role,
    r.name AS role_name,
    r.slug AS role_slug,
    r.hierarchy_level,
    GROUP_CONCAT(DISTINCT d.name ORDER BY ud.is_primary DESC SEPARATOR ', ') AS departments,
    GROUP_CONCAT(DISTINCT d.id ORDER BY ud.is_primary DESC) AS department_ids,
    u.is_active,
    u.created_at,
    u.last_login_at
FROM users u
LEFT JOIN roles r ON u.role_id = r.id
LEFT JOIN user_departments ud ON u.id = ud.user_id
LEFT JOIN departments d ON ud.department_id = d.id
GROUP BY u.id;

-- View: User permissions (flattened)
CREATE OR REPLACE VIEW `v_user_permissions` AS
SELECT 
    u.id AS user_id,
    u.username,
    r.slug AS role_slug,
    p.slug AS permission_slug,
    p.category AS permission_category
FROM users u
JOIN roles r ON u.role_id = r.id
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE u.is_active = 1;

-- =====================================================
-- 10. INDEXES FOR PERFORMANCE
-- =====================================================
-- Additional indexes for common queries
CREATE INDEX IF NOT EXISTS `idx_users_role_id` ON `users` (`role_id`);
CREATE INDEX IF NOT EXISTS `idx_tickets_dept_status` ON `tickets` (`department_id`, `status`);
CREATE INDEX IF NOT EXISTS `idx_tickets_assigned_status` ON `tickets` (`assigned_to`, `status`);
