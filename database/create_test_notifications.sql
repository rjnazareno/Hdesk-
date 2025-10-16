-- ============================================
-- QUICK FIX: Add Test Notifications
-- Run this in phpMyAdmin to create test notifications
-- ============================================

-- First, check if notifications table has employee_id column
SHOW COLUMNS FROM notifications LIKE 'employee_id';

-- If employee_id doesn't exist, run this:
-- ALTER TABLE notifications ADD COLUMN employee_id INT(11) NULL AFTER user_id;

-- Then, let's see what users/employees exist:
SELECT 'USERS TABLE (Admin/IT Staff):' as info;
SELECT id, username, full_name, role FROM users LIMIT 5;

SELECT 'EMPLOYEES TABLE (Regular Employees):' as info;
SELECT id, CONCAT(fname, ' ', lname) as full_name, email FROM employees LIMIT 5;

-- Add test notifications for admin/IT staff (user_id)
-- Replace '1' with your actual user_id from the users table above
INSERT INTO notifications (user_id, employee_id, type, title, message, ticket_id, is_read, created_at) VALUES
(1, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'Ticket #101 has been assigned to you for review', 1, 0, NOW()),
(1, NULL, 'ticket_updated', 'Ticket Updated', 'Ticket #102 was updated with new information', 2, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, NULL, 'comment_added', 'New Comment on Ticket', 'A customer added a new comment to ticket #101', 1, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, NULL, 'status_changed', 'Status Changed to In Progress', 'Ticket #103 status was changed to In Progress', 3, 1, DATE_SUB(NOW(), INTERVAL 5 HOUR));

-- Add test notifications for employees (employee_id)
-- Replace '1' with your actual employee_id from the employees table above
INSERT INTO notifications (user_id, employee_id, type, title, message, ticket_id, is_read, created_at) VALUES
(NULL, 1, 'ticket_created', 'Your Ticket Was Created', 'Your ticket #101 has been created successfully', 1, 0, NOW()),
(NULL, 1, 'status_changed', 'Ticket Status Updated', 'Your ticket status was changed to In Progress', 1, 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(NULL, 1, 'comment_added', 'IT Staff Replied', 'An IT staff member added a reply to your ticket', 1, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(NULL, 1, 'ticket_resolved', 'Ticket Resolved', 'Your ticket #102 has been marked as resolved', 2, 1, DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- Verify notifications were created
SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10;

-- Check counts
SELECT 
    'User Notifications (Admin/IT)' as type,
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
FROM notifications 
WHERE user_id IS NOT NULL

UNION ALL

SELECT 
    'Employee Notifications' as type,
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
FROM notifications 
WHERE employee_id IS NOT NULL;

-- ============================================
-- DONE! Test notifications created
-- Now refresh your page and check the bell icon
-- ============================================
