-- Quick test notifications for user_id = 4 (Cedrick.Arnigo - Admin)
-- Run this in phpMyAdmin SQL tab

INSERT INTO notifications (user_id, employee_id, type, title, message, ticket_id, is_read, created_at) VALUES
(4, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'Ticket #1001 has been assigned to you for review', 1, 0, NOW()),
(4, NULL, 'comment_added', 'New Comment on Your Ticket', 'A customer added a new comment to ticket #1002', 2, 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(4, NULL, 'status_changed', 'Ticket Status Changed', 'Ticket #1003 status was changed to In Progress', 3, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(4, NULL, 'ticket_created', 'New Ticket Created', 'A new support ticket #1004 needs your attention', 4, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(4, NULL, 'priority_changed', 'Priority Updated', 'Ticket #1005 priority was changed to High', 5, 1, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(4, NULL, 'ticket_updated', 'Ticket Information Updated', 'Ticket #1006 details were modified', 6, 1, DATE_SUB(NOW(), INTERVAL 5 HOUR));

-- Verify the notifications were created
SELECT * FROM notifications WHERE user_id = 4 ORDER BY created_at DESC;

-- Check the count
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread,
    SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read
FROM notifications 
WHERE user_id = 4;
