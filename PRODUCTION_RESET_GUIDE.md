# 🚀 PRODUCTION RESET GUIDE
## Hostinger Server: hdesk.resourcestaffonline.com

**Date:** February 11, 2026  
**Environment:** Production (Hostinger)  
**Database:** u816220874_ticketing

---

## ⚠️ IMPORTANT: Pre-Reset Checklist

- [ ] **Backup recommended** (optional): Export current tickets via phpMyAdmin
- [ ] **Notify users**: System will show 0 tickets after reset
- [ ] **Verify database access**: Can you login to Hostinger phpMyAdmin?
- [ ] **Test account ready**: Have an employee/IT staff account to test with

---

## 🎯 Production Reset (3 Steps)

### Step 1️⃣: Clean Database via Hostinger phpMyAdmin

1. **Login to Hostinger Control Panel**
   - Go to: https://hpanel.hostinger.com/
   - Navigate to: **Websites** → **hdesk.resourcestaffonline.com**

2. **Access phpMyAdmin**
   - In left sidebar, click **Databases**
   - Click **Manage** next to `u816220874_ticketing`
   - Click **Enter phpMyAdmin** button

3. **Select Database**
   - In left panel, click database: `u816220874_ticketing`

4. **Run Cleanup SQL**
   - Click **SQL** tab at top
   - Copy entire contents of: `database/CLEAN_RESET_TICKETS.sql`
   - Paste into SQL query box
   - Click **Go** button
   - Wait for "Query executed successfully" message

5. **Verify Results**
   - You should see message: "Query executed successfully"
   - **Important:** You may see some errors like:
     - `Table 'u816220874_ticketing.ticket_comments' doesn't exist`
     - `Table 'u816220874_ticketing.sla_tracking' doesn't exist`
   - **This is NORMAL!** Not all tables exist in every setup
   - **What matters:** Check these core tables show 0 records:
     - tickets: 0 records ✓
     - ticket_activity: 0 records ✓
     - notifications: 0 records ✓
   - And these are preserved:
     - users: > 0 records (preserved) ✓
     - categories: > 0 records (preserved) ✓

---

### Step 2️⃣: Clean Upload Files via Web Interface

1. **Access cleanup script**
   - Visit: https://hdesk.resourcestaffonline.com/cleanup_uploads.php

2. **Confirm deletion**
   - Copy the confirmation key shown on screen
   - Paste into the input field
   - Click "Delete All Upload Files"

3. **Wait for completion**
   - Script will show each file being deleted
   - Should see: "Cleanup completed successfully!"

4. **Verify uploads directory is empty**
   - You can check via Hostinger File Manager if needed

---

### Step 3️⃣: Verify Reset

1. **Run verification script**
   - Visit: https://hdesk.resourcestaffonline.com/verify_reset.php
   - Should show: "Reset Successful! ✅"
   - All ticket tables should show 0 records

2. **Clear browser cache**
   - Press: `Ctrl + Shift + Delete`
   - Clear cached images and files
   - Close all browser tabs

3. **Test the system**
   - Logout if currently logged in
   - Login as an employee
   - Create a test ticket
   - Verify ticket number is: **TKT-000001**
   - Login as IT staff
   - Verify you can see the test ticket
   - Assign it to yourself
   - Verify assignment persists correctly

---

## 🔒 Security: Post-Reset Cleanup

**IMPORTANT:** Delete these files after reset for security:

```
cleanup_uploads.php  ← Delete this!
verify_reset.php     ← Optional, but recommended to delete
```

You can delete via:
- **Hostinger File Manager**: Websites → File Manager → Select file → Delete
- **FTP Client**: FileZilla, etc.

---

## 📊 What Was Deleted

| Data Type | Status |
|-----------|--------|
| All tickets | ❌ Deleted |
| All ticket activity logs | ❌ Deleted |
| All notifications | ❌ Deleted |
| All SLA tracking records | ❌ Deleted |
| All attachment files | ❌ Deleted |
| **Users** | ✅ **Preserved** |
| **Employees** | ✅ **Preserved** |
| **Categories** | ✅ **Preserved** |
| **SLA Policies** | ✅ **Preserved** |
| **System Settings** | ✅ **Preserved** |

---

## 🔧 Alternative: Hostinger File Manager Method

If you prefer to delete uploads manually:

1. Login to Hostinger Control Panel
2. Go to: **Websites** → **File Manager**
3. Navigate to: `public_html/` (or your root directory)
4. Find: `uploads/` folder
5. Select all files inside
6. Click **Delete** button
7. Confirm deletion
This is NORMAL and expected! Some tables (like `ticket_comments`, `sla_tracking`, `ticket_attachments`) may not exist in your database. The script tries to clean all possible tables. As long as the main tables (`tickets`, `notifications`) show 0 records, the reset is successful. You can safely ignore these "table doesn't exist" errors
---

## 🐛 Troubleshooting

### Problem: SQL script gives error "Table doesn't exist"
**Solution:** Some tables might not exist in your database. This is OK - the script will skip them. As long as `tickets` table shows 0 records, you're good.

### Problem: phpMyAdmin times out
**Solution:** The script is comprehensive. If it times out:
1. Run individual sections separately
2. Most important: `TRUNCATE TABLE tickets;`
3. Then: `TRUNCATE TABLE ticket_activity;`
4. Then: `TRUNCATE TABLE notifications;`

### Problem: "Permission denied" when deleting files
**Solution:** 
- Check file permissions in File Manager
- Try: Select all files → Right-click → Change Permissions → 644
- Then retry deletion

### Problem: Still see old tickets after reset
**Solution:**
1. Clear browser cache completely
2. Try incognito/private browser window
3. Check browser not using cached data
4. Logout and login again

### Problem: Can't access cleanup_uploads.php
**Solution:**
- Verify file uploaded to server
- Check URL is correct: `https://hdesk.resourcestaffonline.com/cleanup_uploads.php`
- Check file has `.php` extension, not `.php.txt`

---

## ⚡ Quick Command Reference

### SQL Commands (run in phpMyAdmin SQL tab):

```sql
-- Check ticket count (should be 0 after reset)
SELECT COUNT(*) FROM tickets;

-- Check users preserved (should be > 0)
SELECT COUNT(*) FROM users;

-- View all table sizes
SELECT 
    TABLE_NAME, 
    TABLE_ROWS 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = 'u816220874_ticketing'
ORDER BY TABLE_NAME;
```

---

## 📈 Database Details

- **Server:** localhost (Hostinger shared hosting)
- **Database Name:** u816220874_ticketing
- **Database User:** u816220874_ticketing
- **Hostinger Panel:** https://hpanel.hostinger.com/

---

## ✅ Success Criteria

After reset, you should have:

- ✅ 0 tickets in system
- ✅ 0 notifications
- ✅ Empty uploads directory
- ✅ Users/employees still exist
- ✅ Categories still exist
- ✅ Can login normally
- ✅ Can create new ticket starting with TKT-000001
- ✅ Ticket assignment works correctly
- ✅ No tracking errors

---

## 🎉 Post-Reset: Fresh Start Recommendations

### 1. Test Core Functionality
- Create ticket as employee
- Assign ticket as IT staff
- Update ticket status
- Add comments
- Close ticket
- Verify all actions are tracked correctly

### 2. Monitor First Week
- Watch for assignment tracking issues
- Verify submitters display correctly
- Check notifications work
- Ensure SLA tracking is accurate

### 3. Consider Schema Simplification
If tracking issues return, run:
- `database/OPTIONAL_SIMPLIFY_SCHEMA.sql`
- This removes dual-user system complexity
- Simplifies assignment tracking permanently

---

## 📞 Need Help?

### Resources Created:
- `database/CLEAN_RESET_TICKETS.sql` - Database cleanup
- `cleanup_uploads.php` - Web-based file cleanup
- `verify_reset.php` - Verification tool
- `PRODUCTION_RESET_GUIDE.md` - This guide
- `database/OPTIONAL_SIMPLIFY_SCHEMA.sql` - Schema simplification

### Verification Steps:
1. Run `verify_reset.php` - should show all green checkmarks
2. Check dashboard - should show 0 tickets
3. Test ticket creation - should work smoothly

---

## ⏱️ Estimated Time

- Database cleanup: **2-3 minutes**
- File cleanup: **1-2 minutes**
- Verification: **2 minutes**
- Testing: **5 minutes**

**Total:** ~10-15 minutes

---

**Ready to proceed?** Start with Step 1 above! 🚀
