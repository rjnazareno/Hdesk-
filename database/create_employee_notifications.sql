-- Create employee notifications for john.doe (employees.id = 1)
-- These are employee-appropriate notifications (their ticket updates)

-- Employee notifications (personal ticket notifications)
INSERT INTO notifications (employee_id, user_id, type, title, message, ticket_id, related_user_id, is_read, created_at)
VALUES 
-- Ticket submitted confirmation
(1, NULL, 'ticket_created', 'Ticket Submitted Successfully', 'Your support ticket has been received and will be reviewed shortly', 1, NULL, 1, DATE_SUB(NOW(), INTERVAL 2 DAY)),

-- Ticket assigned to IT staff
(1, NULL, 'ticket_assigned', 'Ticket Assigned to Support Team', 'Your ticket has been assigned to our IT support team', 1, 2, 0, DATE_SUB(NOW(), INTERVAL 1 DAY)),

-- Status changed to In Progress
(1, NULL, 'status_changed', 'Ticket Status Updated', 'Your ticket status changed to: In Progress', 1, 2, 0, DATE_SUB(NOW(), INTERVAL 8 HOUR)),

-- IT Staff commented
(1, NULL, 'comment_added', 'New Comment from IT Support', 'IT Staff replied to your ticket with more information', 1, 2, 0, DATE_SUB(NOW(), INTERVAL 4 HOUR)),

-- Ticket resolved
(1, NULL, 'ticket_resolved', 'Ticket Resolved', 'Your support ticket has been marked as resolved', 1, 2, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR));

-- Summary query
SELECT 
    CONCAT(e.fname, ' ', e.lname) as employee_name,
    e.role as employee_role,
    COUNT(n.id) as total_notifications,
    SUM(CASE WHEN n.is_read = 0 THEN 1 ELSE 0 END) as unread_notifications
FROM employees e
LEFT JOIN notifications n ON e.id = n.employee_id
WHERE e.id = 1
GROUP BY e.id;

SELECT 'Employee notifications created successfully' as status;
