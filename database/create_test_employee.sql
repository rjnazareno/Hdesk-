-- Create test employee account
-- Password will be 'password123' (you should hash it properly in production)

INSERT INTO users (username, password, full_name, email, role, created_at) 
VALUES (
    'employee1', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- bcrypt hash of 'password'
    'John Doe', 
    'john.doe@company.com', 
    'employee', 
    NOW()
);

-- Get the new employee ID
SELECT id, username, full_name, email, role FROM users WHERE username = 'employee1';

-- Now add employee-appropriate notifications for this new employee
SET @employee_id = (SELECT id FROM users WHERE username = 'employee1' LIMIT 1);

INSERT INTO notifications (user_id, type, title, message, ticket_id, related_user_id, is_read, created_at)
VALUES 
(@employee_id, 'ticket_created', 'Ticket Submitted Successfully', 'Your support ticket has been received and is being reviewed', 1, NULL, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(@employee_id, 'ticket_assigned', 'Ticket Assigned to Support', 'Your ticket has been assigned to an IT staff member', 1, 2, 0, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
(@employee_id, 'status_changed', 'Ticket Status Updated', 'Your ticket status changed to: In Progress', 1, 2, 0, DATE_SUB(NOW(), INTERVAL 4 HOUR)),
(@employee_id, 'comment_added', 'New Comment on Your Ticket', 'IT Staff replied to your ticket', 1, 2, 0, DATE_SUB(NOW(), INTERVAL 5 HOUR)),
(@employee_id, 'ticket_resolved', 'Ticket Resolved', 'Your ticket has been marked as resolved', 1, 2, 1, DATE_SUB(NOW(), INTERVAL 1 DAY));

SELECT 'Employee account created with notifications' as status;
