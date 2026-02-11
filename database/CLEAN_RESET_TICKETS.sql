-- ============================================
-- CLEAN RESET: Remove All Tickets & Related Data
-- Date: February 11, 2026
-- Environment: PRODUCTION (Hostinger)
-- Database: u816220874_ticketing
-- ============================================
-- This script will DELETE all tickets and related data
-- while preserving users, employees, and categories.
--
-- ⚠️ WARNING: THIS CANNOT BE UNDONE!
-- 
-- How to use:
-- 1. Login to Hostinger phpMyAdmin
-- 2. Select database: u816220874_ticketing
-- 3. Copy and paste this ENTIRE script
-- 4. Click "Go" button
-- 5. Wait for success message
-- ============================================

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- STEP 1: Delete all ticket-related data
-- ============================================
-- Only deleting from CORE tables that exist in your database
-- Optional tables are commented out

-- CORE TABLES - These should exist:

-- Delete all notifications
DELETE FROM notifications WHERE 1=1;

-- Delete ticket activity/logs
DELETE FROM ticket_activity WHERE 1=1;

-- FINALLY: Delete all tickets
DELETE FROM tickets WHERE 1=1;

-- OPTIONAL TABLES - Uncomment only if these exist in your database:
-- DELETE FROM sla_tracking WHERE 1=1;
-- DELETE FROM ticket_comments WHERE 1=1;
-- DELETE FROM employee_notifications WHERE 1=1;
-- DELETE FROM ticket_attachments WHERE 1=1;
-- DELETE FROM ticket_assignments WHERE 1=1;

-- ============================================
-- STEP 2: Reset Auto-Increment Counters
-- ============================================
-- Only resetting counters for CORE tables

-- Reset tickets ID counter
ALTER TABLE tickets AUTO_INCREMENT = 1;

-- Reset ticket_activity ID counter
ALTER TABLE ticket_activity AUTO_INCREMENT = 1;

-- Reset notifications counter
ALTER TABLE notifications AUTO_INCREMENT = 1;

-- OPTIONAL - Uncomment if these tables exist:
-- ALTER TABLE sla_tracking AUTO_INCREMENT = 1;
-- ALTER TABLE ticket_comments AUTO_INCREMENT = 1;
-- ALTER TABLE employee_notifications AUTO_INCREMENT = 1;

-- ============================================
-- STEP 3: Clean up physical attachment files
-- ============================================
-- NOTE: This SQL cannot delete physical files.
-- Use cleanup_uploads.php to delete files in: /uploads/
-- ============================================

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- SUCCESS MESSAGE
-- ============================================
SELECT 'Ticket system reset completed successfully!' AS status;
SELECT 'All tickets, notifications, and activity logs deleted' AS message;
SELECT 'Users, employees, and categories preserved' AS preserved;
SELECT 'Next ticket will be: TKT-000001' AS next_ticket;

-- ============================================
-- COMPLETED
-- ============================================
-- Core tables cleaned:
--   ✓ tickets
--   ✓ ticket_activity
--   ✓ notifications
--
-- Preserved:
--   ✓ users
--   ✓ employees
--   ✓ categories
--
-- Next steps:
-- 1. Run VERIFY_RESET.sql to confirm (optional)
-- 2. Visit verify_reset.php in browser
-- 3. Use cleanup_uploads.php to delete attachment files
-- ============================================
