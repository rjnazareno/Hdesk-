# 🔧 NOTIFICATIONS DATABASE - QUICK SETUP

## ⚠️ Error Fix: "Unknown column 'user_id'"

**This error means**: The `notifications` table doesn't exist yet.

---

## ✅ SOLUTION - Follow These Steps:

### Step 1: Open phpMyAdmin

1. Go to: `http://localhost/phpmyadmin`
2. Click on database: `ithelp` (left sidebar)
3. Click on **SQL** tab (top menu)

---

### Step 2: Run This Complete SQL (Copy All)

```sql
-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('ticket_assigned','ticket_updated','ticket_resolved','ticket_created','comment_added','status_changed','priority_changed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `related_user_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Click "Go"** button to execute.

**Expected result**: ✅ "Query OK, 0 rows affected"

---

### Step 3: Check Your User ID

Run this to see your user ID:

```sql
SELECT id, username, full_name, role FROM users LIMIT 5;
```

**Example result**:
```
id | username  | full_name    | role
---|-----------|--------------|----------
1  | admin     | Admin User   | admin
2  | itstaff1  | John Doe     | it_staff
3  | employee1 | Jane Smith   | employee
```

**Remember your `id` number!** (Example: `1`)

---

### Step 4: Add Test Notifications

**Replace `1` with YOUR user ID from Step 3:**

```sql
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) VALUES
(1, 'ticket_assigned', 'New Ticket Assigned', 'Ticket #1 has been assigned to you', 1, 0),
(1, 'ticket_updated', 'Ticket Updated', 'Ticket #2 was updated', 2, 0),
(1, 'comment_added', 'New Comment', 'New comment on your ticket', 1, 1),
(1, 'status_changed', 'Status Changed', 'Ticket status changed to In Progress', 2, 0);
```

**Click "Go"**

**Expected result**: ✅ "4 rows inserted"

---

### Step 5: Verify It Worked

```sql
SELECT * FROM notifications;
```

**Expected result**: You should see 4 notifications in the table.

---

### Step 6: Check Unread Count

```sql
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread
FROM notifications;
```

**Expected result**:
```
total | unread
------|-------
  4   |   3
```

---

## 🎉 Done! Now Test It:

1. Open: `http://localhost/IThelp/admin/dashboard.php`
2. Look at **bell icon** in header
3. **Expected**: Red badge showing "3" (unread count)
4. **Click bell icon** → See notifications dropdown
5. **Click a notification** → Opens related ticket

---

## 🐛 Still Getting Errors?

### Error: "Table 'ithelp.notifications' doesn't exist"

**Solution**: Run Step 2 again (CREATE TABLE statement)

---

### Error: "Cannot add foreign key constraint"

**Solution**: Remove the foreign keys, run this instead:

```sql
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('ticket_assigned','ticket_updated','ticket_resolved','ticket_created','comment_added','status_changed','priority_changed') NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `related_user_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### No Notifications Showing in Dropdown?

**Check**:
1. User ID in notifications matches your logged-in user ID
2. JavaScript console for errors (F12 → Console tab)
3. API endpoint works: Visit `http://localhost/IThelp/api/notifications.php?action=get_count`

**Expected API response**:
```json
{"success":true,"unread_count":3}
```

---

## 📋 Quick Checklist:

- [ ] Opened phpMyAdmin
- [ ] Selected `ithelp` database
- [ ] Ran CREATE TABLE statement
- [ ] Found my user ID
- [ ] Inserted test notifications with MY user ID
- [ ] Verified notifications exist
- [ ] Tested on admin dashboard
- [ ] See red badge on bell icon
- [ ] Notifications dropdown works

---

## 💬 Still Stuck?

Tell me:
1. What step are you on?
2. What error message do you see?
3. Screenshot of the error (optional)

I'll help you fix it! 🚀
