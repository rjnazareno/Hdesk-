-- ============================================
-- FIX ALL AUTO_INCREMENT ATTRIBUTES
-- ============================================
-- Run this AFTER importing the main SQL dump
-- This ensures all ID columns have AUTO_INCREMENT
-- ============================================

-- IMPORTANT: First drop and recreate the view with correct DEFINER
DROP VIEW IF EXISTS `v_sla_summary`;

CREATE VIEW `v_sla_summary` AS 
SELECT 
  `t`.`id` AS `ticket_id`, 
  `t`.`ticket_number` AS `ticket_number`, 
  `t`.`title` AS `title`, 
  `t`.`priority` AS `priority`, 
  `t`.`status` AS `ticket_status`, 
  `t`.`sla_status` AS `sla_status`, 
  `st`.`response_sla_status` AS `response_sla_status`, 
  `st`.`resolution_sla_status` AS `resolution_sla_status`, 
  `st`.`response_due_at` AS `response_due_at`, 
  `st`.`resolution_due_at` AS `resolution_due_at`, 
  `st`.`first_response_at` AS `first_response_at`, 
  `st`.`resolved_at` AS `resolved_at`, 
  `st`.`response_time_minutes` AS `response_time_minutes`, 
  `st`.`resolution_time_minutes` AS `resolution_time_minutes`, 
  `st`.`is_paused` AS `is_paused`, 
  `sp`.`response_time` AS `target_response_minutes`, 
  `sp`.`resolution_time` AS `target_resolution_minutes`, 
  `sp`.`is_business_hours` AS `is_business_hours`, 
  CASE 
    WHEN `st`.`resolved_at` IS NOT NULL THEN 0 
    WHEN `st`.`is_paused` = 1 THEN TIMESTAMPDIFF(MINUTE, CURRENT_TIMESTAMP(), `st`.`resolution_due_at`) 
    ELSE TIMESTAMPDIFF(MINUTE, CURRENT_TIMESTAMP(), `st`.`resolution_due_at`) 
  END AS `minutes_remaining`, 
  CASE 
    WHEN `st`.`resolved_at` IS NOT NULL THEN 100 
    ELSE ROUND(TIMESTAMPDIFF(MINUTE, `t`.`created_at`, CURRENT_TIMESTAMP()) / `sp`.`resolution_time` * 100, 2) 
  END AS `elapsed_percentage` 
FROM `tickets` `t` 
LEFT JOIN `sla_tracking` `st` ON `t`.`id` = `st`.`ticket_id` 
LEFT JOIN `sla_policies` `sp` ON `st`.`sla_policy_id` = `sp`.`id` 
WHERE `t`.`status` <> 'closed';

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
