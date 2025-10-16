# Notifications Dropdown - "No notifications" Issue - Fix Guide

## Problem
The notifications dropdown shows "No notifications" even when there should be notifications.

## Root Causes
1. **Missing `employee_id` column** - The notifications table may only have `user_id` but needs `employee_id` for employees
2. **No test data** - There are no notifications in the database yet
3. **Session mismatch** - The API might be looking for the wrong ID type

## Solution

### Step 1: Run Diagnostic Page
First, identify the exact problem:

**For Admin/IT Staff:**
- Visit: `http://localhost/IThelp/admin/notifications_diagnostic.php`

**For Employees:**
- Visit: `http://localhost/IThelp/customer/notifications_diagnostic.php`

This will show you:
- Session information
- Database table structure
- Notification count
- Any SQL errors

### Step 2: Fix Database Schema (if needed)

If the diagnostic shows **"employee_id column is missing"**, run this SQL in phpMyAdmin:

```sql
-- Add employee_id column
ALTER TABLE notifications 
ADD COLUMN employee_id INT(11) NULL AFTER user_id;

-- Add index for faster queries
CREATE INDEX idx_notifications_employee_id ON notifications(employee_id);
```

Or run the complete fix file: `database/fix_notifications_schema.sql`

### Step 3: Create Test Notifications

Run this in phpMyAdmin to create test notifications:

**Option A: Use the automated script**
```sql
-- Open and run: database/create_test_notifications.sql
```

**Option B: Manual INSERT (faster)**

1. First, find your user ID:
```sql
-- For Admin/IT Staff
SELECT id, username, full_name FROM users WHERE username = 'your_username';

-- For Employees
SELECT id, CONCAT(fname, ' ', lname) as name FROM employees WHERE email = 'your_email';
```

2. Then insert test notifications (replace the IDs with your actual IDs):

**For Admin/IT Staff (user_id):**
```sql
INSERT INTO notifications (user_id, employee_id, type, title, message, is_read, created_at) VALUES
(1, NULL, 'ticket_assigned', 'New Ticket Assigned', 'Ticket has been assigned to you', 0, NOW()),
(1, NULL, 'comment_added', 'New Comment', 'A customer added a comment', 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, NULL, 'status_changed', 'Status Updated', 'Ticket status changed', 1, DATE_SUB(NOW(), INTERVAL 2 HOUR));
```

**For Employees (employee_id):**
```sql
INSERT INTO notifications (user_id, employee_id, type, title, message, is_read, created_at) VALUES
(NULL, 1, 'ticket_created', 'Ticket Created', 'Your ticket was created successfully', 0, NOW()),
(NULL, 1, 'status_changed', 'Status Updated', 'Your ticket status changed', 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
(NULL, 1, 'comment_added', 'IT Replied', 'IT staff added a reply', 1, DATE_SUB(NOW(), INTERVAL 1 HOUR));
```

### Step 4: Verify API is Working

1. Open browser console (F12)
2. Click the bell icon
3. Check the console for API logs:
   - Should see: `📡 Loading notifications from API...`
   - Should see: `✅ Loaded X notifications, Y unread`

If you see errors, check:
- Network tab for failed requests
- Response from API (should be JSON with `success: true`)

### Step 5: Test the Fix

1. **Refresh the page** (Ctrl+R or F5)
2. **Click the bell icon** in the navigation
3. **You should now see** the notifications dropdown with your test data

## Files Updated

### API Improvements
- `api/notifications.php` - Added debug logging and better session handling

### Diagnostic Tools
- `admin/notifications_diagnostic.php` - Check admin/IT notifications
- `customer/notifications_diagnostic.php` - Check employee notifications

### Database Scripts
- `database/create_test_notifications.sql` - Create sample notifications
- `database/fix_notifications_schema.sql` - Fix table structure

## Common Issues

### Issue: "employee_id column doesn't exist"
**Fix:** Run Step 2 above to add the column

### Issue: Still shows "No notifications"
**Causes:**
1. Wrong user_id/employee_id in test data - Check your actual IDs
2. API is using wrong session variable - Check diagnostic page
3. JavaScript not loading - Check browser console for errors

**Debug:**
- Visit `/api/notifications.php?action=get_recent` directly
- Should return JSON with notifications array
- Check the `debug` object in the response

### Issue: Bell icon doesn't open dropdown
**Causes:**
1. JavaScript file not loaded - Check Network tab
2. Button selector not found - Check browser console

**Fix:**
- Ensure `assets/js/notifications.js` is loaded
- Check that the bell icon button has the correct structure

## Expected Results

After following these steps, you should see:

1. ✅ **Bell icon with badge** showing unread count (e.g., "3")
2. ✅ **Dropdown opens** when clicking bell
3. ✅ **Notifications list** with titles, messages, timestamps
4. ✅ **Unread notifications** highlighted in blue
5. ✅ **"Mark as read"** button works
6. ✅ **"View All Notifications"** link works

## Need More Help?

Run the diagnostic pages and check:
- Session variables are set correctly
- Notifications table exists and has data
- API returns `success: true`
- Browser console shows no JavaScript errors

If still having issues, check the error logs:
- PHP errors: Check `C:\xampp\php\logs\php_error_log`
- Apache errors: Check `C:\xampp\apache\logs\error.log`
