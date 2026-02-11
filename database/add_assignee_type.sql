-- Migration: Add assignee_type column to support both user and employee assignees
-- This allows employees with admin rights to grab/be assigned tickets

-- Add assignee_type column (similar to submitter_type pattern)
ALTER TABLE `tickets` 
ADD COLUMN IF NOT EXISTS `assignee_type` ENUM('user', 'employee') DEFAULT 'user' 
COMMENT 'Type of assignee: user (from users table) or employee (from employees table)';

-- Note: grabbed_by and assigned_to now store IDs that can be from either users or employees table
-- The assignee_type column determines which table to join

-- Create index for better query performance
ALTER TABLE `tickets` ADD INDEX IF NOT EXISTS `idx_assignee_type` (`assignee_type`);

-- For existing tickets, keep assignee_type as 'user' (default)
-- New employee-assigned tickets will have assignee_type = 'employee'
