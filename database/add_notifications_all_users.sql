-- ============================================
-- Create Test Notifications for ALL User Types
-- Run this in phpMyAdmin SQL tab
-- ============================================

-- First, let's see what users exist in your system
SELECT 'ADMIN/IT STAFF USERS (users table):' as info;
SELECT id, username, full_name, role FROM users ORDER BY id LIMIT 10;

SELECT '' as spacer;
SELECT 'EMPLOYEES (employees table):' as info;
SELECT id, CONCAT(fname, ' ', lname) as full_name, email FROM employees ORDER BY id LIMIT 10;

-- ============================================
-- Now add notifications for EACH user type
-- ============================================

-- FOR ADMIN/IT STAFF (user_id = 4, which is Cedrick.Arnigo)
INSERT INTO notifications (user_id, employee_id, type, title, message, ticket_id, is_read, created_at) VALUES
(4, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'Ticket #1001 has been assigned to you for review', 1, 0, NOW()),
(4, NULL, 'comment_added', 'New Comment Added', 'A customer added a new comment to ticket #1002', 2, 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(4, NULL, 'status_changed', 'Ticket Status Changed', 'Ticket #1003 status changed to In Progress', 3, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(4, NULL, 'ticket_created', 'New Support Ticket', 'New ticket #1004 needs attention', 4, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(4, NULL, 'priority_changed', 'High Priority Alert', 'Ticket #1005 priority changed to High', 5, 1, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(4, NULL, 'ticket_updated', 'Ticket Updated', 'Ticket #1006 details were modified', 6, 1, DATE_SUB(NOW(), INTERVAL 5 HOUR));

-- FOR OTHER ADMIN/IT USERS (if you have user_id 1, 2, etc.)
-- Uncomment and adjust IDs as needed:
-- INSERT INTO notifications (user_id, employee_id, type, title, message, is_read, created_at) VALUES
-- (1, NULL, 'ticket_assigned', 'Ticket Assigned', 'A ticket was assigned to you', 0, NOW()),
-- (2, NULL, 'comment_added', 'New Comment', 'Someone commented on your ticket', 0, NOW());

-- FOR EMPLOYEES (employee_id - adjust the ID based on your employees table)
-- Replace '1' with actual employee IDs from the query above
INSERT INTO notifications (user_id, employee_id, type, title, message, ticket_id, is_read, created_at) VALUES
(NULL, 1, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #2001 has been created', 1, 0, NOW()),
(NULL, 1, 'status_changed', 'Ticket Status Update', 'Your ticket status changed to In Progress', 1, 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(NULL, 1, 'comment_added', 'IT Staff Replied', 'An IT staff member responded to your ticket', 1, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(NULL, 1, 'ticket_resolved', 'Ticket Resolved', 'Your ticket #2002 has been resolved', 2, 1, DATE_SUB(NOW(), INTERVAL 3 HOUR));

-- If you have more employees (employee_id 2, 3, etc.), add for them too:
-- INSERT INTO notifications (user_id, employee_id, type, title, message, is_read, created_at) VALUES
-- (NULL, 2, 'ticket_created', 'Ticket Created', 'Your ticket was created', 0, NOW()),
-- (NULL, 3, 'status_changed', 'Status Updated', 'Your ticket status changed', 0, NOW());

-- ============================================
-- Verify all notifications were created
-- ============================================

SELECT 'VERIFICATION - All notifications:' as info;
SELECT 
    id,
    CASE 
        WHEN user_id IS NOT NULL THEN CONCAT('Admin/IT (user_id=', user_id, ')')
        WHEN employee_id IS NOT NULL THEN CONCAT('Employee (employee_id=', employee_id, ')')
        ELSE 'Unknown'
    END as recipient,
    type,
    title,
    is_read,
    created_at
FROM notifications 
WHERE (user_id = 4 OR employee_id = 1)
ORDER BY created_at DESC;

-- Count by type
SELECT 
    CASE 
        WHEN user_id IS NOT NULL THEN 'Admin/IT Staff'
        WHEN employee_id IS NOT NULL THEN 'Employees'
        ELSE 'Unknown'
    END as user_type,
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
FROM notifications
WHERE (user_id = 4 OR employee_id = 1)
GROUP BY user_type;

-- ============================================
-- DONE! Now test in the browser
-- ============================================
