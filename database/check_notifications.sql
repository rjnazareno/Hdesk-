-- Quick check: See all notifications in the database
SELECT 
    id,
    user_id,
    employee_id,
    type,
    title,
    message,
    is_read,
    created_at
FROM notifications 
ORDER BY created_at DESC 
LIMIT 20;

-- Check specifically for user_id = 4
SELECT COUNT(*) as total_for_user_4 FROM notifications WHERE user_id = 4;

-- Check all user_ids that exist
SELECT DISTINCT user_id FROM notifications WHERE user_id IS NOT NULL;

-- Check all employee_ids that exist
SELECT DISTINCT employee_id FROM notifications WHERE employee_id IS NOT NULL;
