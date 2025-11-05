-- ============================================
-- FIX ALL AUTO_INCREMENT ATTRIBUTES
-- ============================================
-- Run this AFTER importing the main SQL dump
-- This ensures all ID columns have AUTO_INCREMENT
-- ============================================

-- Fix categories table
ALTER TABLE `categories` 
  MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Fix employees table  
ALTER TABLE `employees` 
  MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Fix notifications table
ALTER TABLE `notifications` 
  MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Fix sla_breaches table
ALTER TABLE `sla_breaches` 
  MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Fix sla_policies table
ALTER TABLE `sla_policies` 
  MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Fix sla_tracking table
ALTER TABLE `sla_tracking` 
  MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Fix tickets table (CRITICAL - blocks ticket creation)
ALTER TABLE `tickets` 
  MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Fix ticket_activity table
ALTER TABLE `ticket_activity` 
  MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Fix users table
ALTER TABLE `users` 
  MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- ============================================
-- VERIFICATION QUERY
-- ============================================
-- Run this to confirm all tables have AUTO_INCREMENT:
-- 
-- SELECT 
--   TABLE_NAME, 
--   COLUMN_NAME, 
--   EXTRA 
-- FROM information_schema.COLUMNS 
-- WHERE TABLE_SCHEMA = 'u816220874_resolveIT' 
--   AND COLUMN_NAME = 'id' 
--   AND TABLE_NAME IN ('categories','employees','notifications','sla_breaches','sla_policies','sla_tracking','tickets','ticket_activity','users')
-- ORDER BY TABLE_NAME;
--
-- Expected: All rows should show 'auto_increment' in EXTRA column
