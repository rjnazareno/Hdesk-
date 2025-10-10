# 🔔 NOTIFICATIONS FOR EMPLOYEES - SETUP GUIDE

## ✅ YES! Employees Get Notifications Too!

The notification system works for **EVERYONE**:
- ✅ Admin users
- ✅ IT Staff
- ✅ Employees

---

## 📋 Step 1: Find Your Employee User IDs

Run this in phpMyAdmin to see all users:

```sql
-- See ALL users (Admin, IT Staff, AND Employees)
SELECT 
    u.id,
    u.username,
    u.full_name,
    u.role,
    CASE 
        WHEN u.role = 'admin' THEN 'Admin User'
        WHEN u.role = 'it_staff' THEN 'IT Staff Member'
        WHEN e.id IS NOT NULL THEN 'Employee'
        ELSE 'Unknown'
    END as user_type
FROM users u
LEFT JOIN employees e ON u.id = e.user_id
ORDER BY u.role, u.full_name;
```

**Example Result**:
```
id | username   | full_name      | role     | user_type
---|------------|----------------|----------|---------------
1  | admin      | Admin User     | admin    | Admin User
2  | itstaff1   | John Doe       | it_staff | IT Staff Member
3  | employee1  | Jane Smith     | employee | Employee
4  | employee2  | Bob Johnson    | employee | Employee
5  | employee3  | Mary Williams  | employee | Employee
```

---

## 📋 Step 2: Add Notifications For EACH User Type

### For Admin/IT Staff (user_id = 1 or 2):
```sql
-- Notifications IT staff would receive
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) VALUES
(1, 'ticket_assigned', 'New Ticket Assigned', 'Ticket #101 has been assigned to you', 101, 0),
(1, 'ticket_created', 'New Ticket Created', 'Employee Jane Smith created a new ticket', 102, 0),
(2, 'ticket_assigned', 'New Ticket Assigned', 'Ticket #103 has been assigned to you', 103, 0);
```

### For Employees (user_id = 3, 4, 5, etc.):
```sql
-- Notifications employees would receive
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) VALUES
(3, 'comment_added', 'New Comment on Your Ticket', 'IT staff added a comment to your ticket', 101, 0),
(3, 'status_changed', 'Ticket Status Updated', 'Your ticket status changed to: In Progress', 101, 0),
(4, 'ticket_updated', 'Your Ticket Was Updated', 'IT staff updated your ticket', 102, 0),
(4, 'ticket_resolved', 'Ticket Resolved', 'Your ticket has been resolved!', 102, 0),
(5, 'comment_added', 'New Comment', 'IT staff replied to your ticket', 103, 0);
```

---

## 🎯 Step 3: Complete Example For All Users

Replace user IDs with YOUR actual IDs from Step 1:

```sql
-- Clear any old test notifications (optional)
-- DELETE FROM notifications WHERE message LIKE '%test%';

-- Add notifications for ADMIN (user_id = 1)
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) VALUES
(1, 'ticket_assigned', 'Ticket Assigned to You', 'Ticket #1 - Network issue has been assigned', 1, 0),
(1, 'ticket_created', 'New Ticket Created', 'Employee created ticket #2', 2, 0);

-- Add notifications for IT STAFF (user_id = 2)
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) VALUES
(2, 'ticket_assigned', 'New Assignment', 'Ticket #3 - Printer problem assigned to you', 3, 0),
(2, 'comment_added', 'Employee Replied', 'Employee added comment to ticket #3', 3, 0);

-- Add notifications for EMPLOYEE 1 (user_id = 3)
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) VALUES
(3, 'comment_added', 'IT Staff Responded', 'John from IT replied to your ticket', 1, 0),
(3, 'status_changed', 'Status Update', 'Your ticket is now: In Progress', 1, 0),
(3, 'ticket_resolved', 'Ticket Resolved!', 'Your network issue has been resolved', 1, 1);

-- Add notifications for EMPLOYEE 2 (user_id = 4)
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) VALUES
(4, 'comment_added', 'New Reply', 'IT staff commented on your ticket', 2, 0),
(4, 'status_changed', 'Status Changed', 'Ticket status: Open → In Progress', 2, 0);

-- Add notifications for EMPLOYEE 3 (user_id = 5)
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) VALUES
(5, 'ticket_updated', 'Ticket Updated', 'IT staff updated your printer ticket', 3, 0),
(5, 'priority_changed', 'Priority Increased', 'Your ticket priority changed to: High', 3, 0);
```

---

## 🧪 Step 4: Test For Each User

### Test as Admin (user_id = 1):
1. Login as admin
2. Go to: `http://localhost/IThelp/admin/dashboard.php`
3. **Check bell icon** → Should see badge with "2"
4. **Click bell** → See "Ticket Assigned" and "New Ticket Created"

### Test as IT Staff (user_id = 2):
1. Login as IT staff
2. Go to: `http://localhost/IThelp/admin/dashboard.php`
3. **Check bell icon** → Should see badge with "2"
4. **Click bell** → See "New Assignment" and "Employee Replied"

### Test as Employee (user_id = 3, 4, 5):
1. Login as employee
2. Go to: `http://localhost/IThelp/customer/dashboard.php`
3. **Check bell icon** → Should see badge with unread count
4. **Click bell** → See notifications like "IT Staff Responded", "Status Update", etc.

---

## 📊 Verify All Users Have Notifications

```sql
-- See all notifications grouped by user
SELECT 
    u.id,
    u.username,
    u.full_name,
    u.role,
    COUNT(n.id) as total_notifications,
    SUM(CASE WHEN n.is_read = 0 THEN 1 ELSE 0 END) as unread_count
FROM users u
LEFT JOIN notifications n ON u.id = n.user_id
GROUP BY u.id, u.username, u.full_name, u.role
ORDER BY u.role, u.full_name;
```

**Expected Result**:
```
id | username   | full_name     | role     | total_notifications | unread_count
---|------------|---------------|----------|---------------------|-------------
1  | admin      | Admin User    | admin    | 2                   | 2
2  | itstaff1   | John Doe      | it_staff | 2                   | 2
3  | employee1  | Jane Smith    | employee | 3                   | 2
4  | employee2  | Bob Johnson   | employee | 2                   | 2
5  | employee3  | Mary Williams | employee | 2                   | 2
```

---

## 🎯 What Employees See vs What Admin/IT See

### Employee Dashboard (`customer/dashboard.php`):
```
┌─────────────────────────────────┐
│  🔔 (3) ← Badge shows 3 unread  │
│                                 │
│  Notifications ▼                │
│  ┌──────────────────────────┐  │
│  │ 💬 IT Staff Responded     │  │
│  │    John replied to your   │  │
│  │    ticket about network   │  │
│  │    2h ago                 │  │
│  ├──────────────────────────┤  │
│  │ 🔄 Status Changed         │  │
│  │    Your ticket is now:    │  │
│  │    In Progress            │  │
│  │    3h ago                 │  │
│  ├──────────────────────────┤  │
│  │ ✅ Ticket Resolved        │  │
│  │    Your issue has been    │  │
│  │    resolved               │  │
│  │    1d ago                 │  │
│  └──────────────────────────┘  │
└─────────────────────────────────┘
```

### Admin/IT Dashboard (`admin/dashboard.php`):
```
┌─────────────────────────────────┐
│  🔔 (2) ← Badge shows 2 unread  │
│                                 │
│  Notifications ▼                │
│  ┌──────────────────────────┐  │
│  │ 👤 Ticket Assigned        │  │
│  │    Ticket #1 assigned     │  │
│  │    to you                 │  │
│  │    1h ago                 │  │
│  ├──────────────────────────┤  │
│  │ ➕ New Ticket Created     │  │
│  │    Employee Jane Smith    │  │
│  │    created ticket #2      │  │
│  │    2h ago                 │  │
│  └──────────────────────────┘  │
└─────────────────────────────────┘
```

---

## ✅ Quick Setup For YOUR System

### Find Your User IDs:
```sql
SELECT id, username, full_name, role FROM users;
```

### Add Notifications (Replace IDs):
```sql
-- For Admin (replace 1 with your admin user_id)
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) 
VALUES (1, 'ticket_assigned', 'New Ticket', 'Ticket assigned to you', 1, 0);

-- For IT Staff (replace 2 with your IT staff user_id)
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) 
VALUES (2, 'ticket_created', 'New Ticket', 'Employee created new ticket', 2, 0);

-- For Employee (replace 3 with your employee user_id)
INSERT INTO notifications (user_id, type, title, message, ticket_id, is_read) 
VALUES (3, 'comment_added', 'New Comment', 'IT staff replied to your ticket', 1, 0);
```

---

## 🚀 Make It Automatic (Optional)

To automatically create notifications when tickets are updated, I can:

1. **Add code to ticket creation** → Notify IT staff when employee creates ticket
2. **Add code to comments** → Notify employee when IT staff replies
3. **Add code to status changes** → Notify employee when status changes
4. **Add code to assignments** → Notify IT staff when ticket is assigned

**Want me to add automatic notifications?** Just say:
- "Add automatic notifications"
- "Show me how to add auto notifications"

---

## 📝 Summary

✅ **System already supports employees!**  
✅ **Just add notifications with employee user_id**  
✅ **Employees see notifications on customer/dashboard.php**  
✅ **Badge shows on bell icon for all users**  
✅ **Same dropdown, same features, works for everyone!**

**Try it now!** Login as employee and check the bell icon! 🔔
