-- Simple sample notifications for testing employee notification dropdown
-- Replace user_id values with actual employee IDs from your database

-- For Employee ID 1 (adjust based on your actual employee user_id)
INSERT INTO notifications (user_id, type, title, message, ticket_id, related_user_id, is_read, created_at)
VALUES 
(1, 'status_changed', 'Ticket Status Updated', 'Your ticket #1 status changed to: In Progress', 1, 2, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 'comment_added', 'New Comment on Your Ticket', 'IT Staff replied to your ticket "Laptop WiFi Issue"', 1, 2, 0, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(1, 'ticket_resolved', 'Ticket Resolved', 'Your ticket "MS Office Installation" has been resolved', 2, 2, 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),
(1, 'priority_changed', 'Priority Changed', 'Your ticket priority was elevated to: High', 1, 2, 1, DATE_SUB(NOW(), INTERVAL 2 DAY));

-- Check results
SELECT 
    id,
    user_id,
    type,
    title,
    LEFT(message, 50) as message_preview,
    is_read,
    created_at
FROM notifications
WHERE user_id = 1
ORDER BY created_at DESC;
