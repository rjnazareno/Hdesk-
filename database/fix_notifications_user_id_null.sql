-- Fix Notifications Table: Allow user_id to be NULL
-- This is required for the dual-user system to work properly
--
-- Problem: user_id was set to NOT NULL, preventing notifications 
--          from being created for employees (employee_id only)
--
-- Solution: Change user_id to allow NULL values

-- Step 1: Check current structure (optional - for verification)
DESCRIBE notifications;

-- Step 2: Modify user_id column to allow NULL
ALTER TABLE notifications 
MODIFY COLUMN user_id INT(11) NULL;

-- Step 3: Verify the change
DESCRIBE notifications;

-- Step 4: Test by inserting a notification for an employee
-- (This should now work without errors)
INSERT INTO notifications (user_id, employee_id, type, title, message, ticket_id, is_read, created_at) 
VALUES (NULL, 1, 'ticket_created', 'TEST: Column Fix Successful', 'If you see this, user_id can now be NULL!', 1, 0, NOW());

-- Step 5: View the test notification
SELECT * FROM notifications WHERE title LIKE '%TEST: Column Fix%';

-- Step 6: Clean up test notification (optional)
-- DELETE FROM notifications WHERE title LIKE '%TEST: Column Fix%';

-- ✅ Done! Now the notification system should work properly.
-- 
-- Next steps:
-- 1. Have an employee submit a NEW ticket
-- 2. Admins should receive notifications automatically
-- 3. Check the bell icon - should show new notification badge
