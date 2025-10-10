-- Admin and IT Staff Notifications
-- These are notifications that ADMINS and IT STAFF should see

-- For Admin (user_id = 1)
INSERT INTO notifications (user_id, type, title, message, ticket_id, related_user_id, is_read, created_at)
VALUES 
-- New tickets created by employees
(1, 'ticket_created', 'New Ticket Submitted', 'A new support ticket requires attention', 1, NULL, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'ticket_created', 'New Ticket Submitted', 'Employee submitted a new urgent ticket', 2, NULL, 0, DATE_SUB(NOW(), INTERVAL 4 HOUR)),

-- Ticket updates
(1, 'ticket_updated', 'Ticket Updated by Employee', 'Employee added more information to ticket #1', 1, NULL, 0, DATE_SUB(NOW(), INTERVAL 6 HOUR)),

-- System notifications
(1, 'ticket_assigned', 'Ticket Auto-Assigned', 'Ticket #1 was assigned to IT Staff', 1, 2, 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- For IT Staff (user_id = 2 - Mahfuzul Islam)
INSERT INTO notifications (user_id, type, title, message, ticket_id, related_user_id, is_read, created_at)
VALUES 
-- Tickets assigned to them
(2, 'ticket_assigned', 'New Ticket Assigned', 'Ticket #1 "Laptop WiFi Issue" has been assigned to you', 1, 1, 0, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(2, 'ticket_assigned', 'New Ticket Assigned', 'Urgent ticket #2 assigned to you', 2, 1, 0, DATE_SUB(NOW(), INTERVAL 5 HOUR)),

-- Employee responses
(2, 'comment_added', 'Employee Response', 'Employee responded to your solution on ticket #1', 1, NULL, 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

-- Display summary
SELECT 
    u.full_name,
    u.role,
    COUNT(n.id) as total_notifications,
    SUM(CASE WHEN n.is_read = 0 THEN 1 ELSE 0 END) as unread_notifications
FROM users u
LEFT JOIN notifications n ON u.id = n.user_id
WHERE u.role IN ('admin', 'it_staff')
GROUP BY u.id, u.full_name, u.role;
