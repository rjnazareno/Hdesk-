-- Sample Employee Notifications
-- Run this to add test notifications for employees

-- First, get employee user IDs (adjust based on your actual data)
-- Assuming employee user IDs start from 1-5 and IT staff are 6+

-- Notification 1: Ticket status changed
INSERT INTO notifications (user_id, type, title, message, ticket_id, related_user_id, is_read, created_at)
SELECT 
    t.submitter_id as user_id,
    'status_changed' as type,
    'Ticket Status Updated' as title,
    CONCAT('Your ticket status changed to: ', UPPER(t.status)) as message,
    t.id as ticket_id,
    t.assigned_to as related_user_id,
    0 as is_read,
    NOW() as created_at
FROM tickets t
WHERE t.submitter_id IN (SELECT id FROM users WHERE role = 'employee')
LIMIT 3;

-- Notification 2: Comment added to employee's ticket
INSERT INTO notifications (user_id, type, title, message, ticket_id, related_user_id, is_read, created_at)
SELECT 
    t.submitter_id as user_id,
    'comment_added' as type,
    'New Comment on Your Ticket' as title,
    'IT staff has added a comment to your ticket' as message,
    t.id as ticket_id,
    t.assigned_to as related_user_id,
    0 as is_read,
    DATE_SUB(NOW(), INTERVAL 2 HOUR) as created_at
FROM tickets t
WHERE t.submitter_id IN (SELECT id FROM users WHERE role = 'employee')
LIMIT 2;

-- Notification 3: Ticket resolved
INSERT INTO notifications (user_id, type, title, message, ticket_id, related_user_id, is_read, created_at)
SELECT 
    t.submitter_id as user_id,
    'ticket_resolved' as type,
    'Ticket Resolved' as title,
    'Your ticket has been marked as resolved' as message,
    t.id as ticket_id,
    t.assigned_to as related_user_id,
    0 as is_read,
    DATE_SUB(NOW(), INTERVAL 1 DAY) as created_at
FROM tickets t
WHERE t.submitter_id IN (SELECT id FROM users WHERE role = 'employee')
AND t.status = 'closed'
LIMIT 2;

-- Notification 4: Priority changed
INSERT INTO notifications (user_id, type, title, message, ticket_id, related_user_id, is_read, created_at)
SELECT 
    t.submitter_id as user_id,
    'priority_changed' as type,
    'Ticket Priority Changed' as title,
    CONCAT('Your ticket priority was changed to: ', UPPER(t.priority)) as message,
    t.id as ticket_id,
    t.assigned_to as related_user_id,
    0 as is_read,
    DATE_SUB(NOW(), INTERVAL 4 HOUR) as created_at
FROM tickets t
WHERE t.submitter_id IN (SELECT id FROM users WHERE role = 'employee')
LIMIT 2;

-- Display summary
SELECT 
    COUNT(*) as total_notifications,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_notifications
FROM notifications
WHERE user_id IN (SELECT id FROM users WHERE role = 'employee');
