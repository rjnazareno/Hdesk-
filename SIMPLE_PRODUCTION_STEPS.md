# 🎯 PRODUCTION RESET - Simple Steps
## https://hdesk.resourcestaffonline.com/

---

## Before You Start

⚠️ **This will delete ALL tickets and attachments permanently!**

✅ **This will preserve:** Users, Employees, Categories, Settings

---

## Step 1: Database Cleanup

### Access Hostinger phpMyAdmin:
1. Go to: **https://hpanel.hostinger.com/**
2. Login with your Hostinger account
3. Click **Websites** → **hdesk.resourcestaffonline.com**
4. Click **Databases** in left menu
5. Find `u816220874_ticketing` → Click **Manage**
6. Click **Enter phpMyAdmin**

### Run the cleanup SQL:
1. Click the database name: `u816220874_ticketing` (left panel)
2. Click **SQL** tab (top menu)
3. Open file: `database/CLEAN_RESET_TICKETS.sql`
4. Copy ALL the SQL code
5. Paste into the query box
6. Click **Go** button (bottom right)
7. Wait for success message

### ✅ Success looks like:
- Message: "Query executed successfully"
- **Note:** You may see some errors like "Table doesn't exist" - **this is NORMAL!**
- Not all tables exist in every database setup
- **What matters:** The main result shows:
  - tickets: **0 records** ✓
  - notifications: **0 records** ✓
  - users: **> 0** (preserved) ✓

---

## Step 2: Delete Upload Files

### Option A: Web Interface (Easiest) ⭐

1. Upload `cleanup_uploads.php` to your server (if not already there)
2. Visit: **https://hdesk.resourcestaffonline.com/cleanup_uploads.php**
3. Copy the confirmation key shown
4. Paste it in the input box
5. Click "Delete All Upload Files"
6. Wait for completion message
7. **IMPORTANT:** Delete `cleanup_uploads.php` after use!

### Option B: File Manager

1. Login to Hostinger Control Panel
2. **File Manager** → Navigate to your site folder
3. Open `uploads/` directory
4. Select ALL files and folders inside
5. Click **Delete**
6. Confirm deletion

---

## Step 3: Verify & Test

### Verify the reset:

**Option A: Web Tool (Recommended)**
1. Visit: **https://hdesk.resourcestaffonline.com/verify_reset.php**
2. Should show: **"Reset Successful! ✅"**
3. All checks should be green ✓

**Option B: SQL Query**
1. In phpMyAdmin SQL tab
2. Open file: `database/VERIFY_RESET.sql`
3. Copy and paste the query
4. Click **Go**
5. All status should show: "✓ GOOD"

### Clear your browser:
1. Press: `Ctrl + Shift + Delete`
2. Select: "Cached images and files"
3. Click: "Clear data"

### Test the system:
1. **Logout** from the system
2. **Login** as employee
3. **Create** a test ticket
4. Verify ticket number: **TKT-000001** ✓
5. **Login** as IT staff
6. **Assign** the ticket to yourself
7. Verify assignment shows correctly ✓

---

## 🔒 Security Cleanup

**Delete these files after reset:**
- `cleanup_uploads.php` ← Delete for security!
- `verify_reset.php` ← Optional, but recommended

---

## ✅ Done!

Your system is now reset and ready for efficient tracking! 🎉

---

## Need Help?

See detailed guide: `PRODUCTION_RESET_GUIDE.md`
