-- ============================================
-- CLEAN ORPHANED DATA BEFORE ADDING CONSTRAINTS
-- ============================================
-- Run this if you get foreign key constraint errors
-- This removes data referencing non-existent tickets
-- ============================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Clean up orphaned sla_breaches (references non-existent tickets)
DELETE FROM `sla_breaches` 
WHERE `ticket_id` NOT IN (SELECT `id` FROM `tickets`);

-- Clean up orphaned sla_tracking (references non-existent tickets)
DELETE FROM `sla_tracking` 
WHERE `ticket_id` NOT IN (SELECT `id` FROM `tickets`);

-- Clean up orphaned ticket_activity (references non-existent tickets)
DELETE FROM `ticket_activity` 
WHERE `ticket_id` NOT IN (SELECT `id` FROM `tickets`);

-- Clean up orphaned notifications (references non-existent tickets)
DELETE FROM `notifications` 
WHERE `ticket_id` IS NOT NULL 
  AND `ticket_id` NOT IN (SELECT `id` FROM `tickets`);

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- VERIFICATION
-- ============================================
-- Check for any remaining orphaned records:
-- 
-- SELECT COUNT(*) as orphaned_sla_breaches 
-- FROM sla_breaches 
-- WHERE ticket_id NOT IN (SELECT id FROM tickets);
--
-- SELECT COUNT(*) as orphaned_sla_tracking 
-- FROM sla_tracking 
-- WHERE ticket_id NOT IN (SELECT id FROM tickets);
--
-- SELECT COUNT(*) as orphaned_ticket_activity 
-- FROM ticket_activity 
-- WHERE ticket_id NOT IN (SELECT id FROM tickets);
--
-- SELECT COUNT(*) as orphaned_notifications 
-- FROM notifications 
-- WHERE ticket_id IS NOT NULL AND ticket_id NOT IN (SELECT id FROM tickets);
--
-- All should return 0 if cleaned successfully
