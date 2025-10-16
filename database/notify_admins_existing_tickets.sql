-- ============================================
-- Create Notifications for Existing Pending Tickets
-- This will notify admins about existing unassigned tickets
-- ============================================

-- Step 1: See all pending/open tickets that need admin attention
SELECT 
    t.id,
    t.ticket_number,
    t.title,
    t.status,
    t.assigned_to,
    t.created_at,
    CASE 
        WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
        ELSE u.full_name
    END as submitter_name
FROM tickets t
LEFT JOIN employees e ON t.submitter_type = 'employee' AND t.submitter_id = e.id
LEFT JOIN users u ON t.submitter_type = 'user' AND t.submitter_id = u.id
WHERE t.status IN ('pending', 'open') 
  AND (t.assigned_to IS NULL OR t.assigned_to = 0)
ORDER BY t.created_at DESC;

-- Step 2: Get all admin/IT staff users
SELECT id, username, full_name, role 
FROM users 
WHERE role IN ('admin', 'it_staff') 
  AND is_active = 1;

-- Step 3: Create notifications for ALL admins about each pending ticket
-- Replace the user IDs (4, 1, 2, etc.) with your actual admin/IT staff user IDs from Step 2

-- For ticket "test12345" - notify all admins
INSERT INTO notifications (user_id, employee_id, type, title, message, ticket_id, is_read, created_at)
SELECT 
    u.id as user_id,
    NULL as employee_id,
    'ticket_created' as type,
    'New Ticket Submitted' as title,
    CONCAT('New ticket #', t.ticket_number, ': ', t.title, ' (', 
           CASE WHEN t.priority = 'urgent' THEN 'URGENT' 
                WHEN t.priority = 'high' THEN 'HIGH'
                ELSE UPPER(t.priority) END, 
           ')') as message,
    t.id as ticket_id,
    0 as is_read,
    t.created_at as created_at
FROM tickets t
CROSS JOIN users u
WHERE t.status IN ('pending', 'open')
  AND (t.assigned_to IS NULL OR t.assigned_to = 0)
  AND u.role IN ('admin', 'it_staff')
  AND u.is_active = 1
  -- Only create if notification doesn't already exist
  AND NOT EXISTS (
      SELECT 1 FROM notifications n 
      WHERE n.ticket_id = t.id 
        AND n.user_id = u.id 
        AND n.type = 'ticket_created'
  );

-- Step 4: Verify notifications were created
SELECT 
    n.id,
    u.full_name as admin_name,
    n.title,
    n.message,
    n.is_read,
    n.created_at
FROM notifications n
JOIN users u ON n.user_id = u.id
WHERE n.type = 'ticket_created'
ORDER BY n.created_at DESC
LIMIT 20;

-- Step 5: Count notifications per admin
SELECT 
    u.full_name as admin_name,
    COUNT(*) as notification_count,
    SUM(CASE WHEN n.is_read = 0 THEN 1 ELSE 0 END) as unread_count
FROM users u
LEFT JOIN notifications n ON u.id = n.user_id AND n.type = 'ticket_created'
WHERE u.role IN ('admin', 'it_staff')
  AND u.is_active = 1
GROUP BY u.id, u.full_name;

-- ============================================
-- DONE! Now refresh your dashboard and check the bell icon
-- ============================================
