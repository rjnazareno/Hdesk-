# Configuration Checklist

## 🔐 Step 1: Generate Secret Key

Run this in PowerShell to generate a secure key:
```powershell
-join ((48..57) + (65..90) + (97..122) | Get-Random -Count 32 | ForEach-Object {[char]$_})
```

Or use this example key:
```
HarleyIThelp2025SecureSync789
```

---

## 📝 Step 2: Update webhook_employee_sync.php

**File:** `webhook_employee_sync.php`  
**Line:** 10

```php
define('WEBHOOK_SECRET_KEY', 'HarleyIThelp2025SecureSync789');
```

---

## 📝 Step 3: Update harley_sync_script.php

**File:** `docs/harley_sync_script.php`  
**Lines:** 9-16

```php
// IThelp webhook URL
$WEBHOOK_URL = 'http://YOUR-ITHELP-DOMAIN.com/webhook_employee_sync.php';
// OR if local testing:
// $WEBHOOK_URL = 'http://localhost/IThelp/webhook_employee_sync.php';

// API Key (MUST MATCH webhook_employee_sync.php!)
$API_KEY = 'HarleyIThelp2025SecureSync789';

// Harley database credentials (from cPanel)
$DB_HOST = 'localhost';
$DB_NAME = 'your_harley_db_name';
$DB_USER = 'your_harley_db_user';
$DB_PASS = 'your_harley_db_password';
```

---

## 🗄️ Step 4: Update Database

Run this SQL in IThelp database:

```sql
ALTER TABLE employees 
ADD COLUMN employee_id VARCHAR(50) UNIQUE AFTER id,
ADD INDEX idx_employee_id (employee_id);
```

---

## 📤 Step 5: Upload to Harley

1. Save `docs/harley_sync_script.php` with your configurations
2. Upload to Hostinger via:
   - FTP
   - cPanel File Manager
   - SSH

**Upload location:**
```
/public_html/Public/module/harley_sync_script.php
```

**Or wherever you want, like:**
```
/public_html/sync/employee_sync.php
```

---

## ✅ Step 6: Test

**Test URL:**
```
https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php
```

**What to check:**
- ✅ Green success messages
- ✅ Employee count matches
- ✅ No red error messages
- ✅ Employees appear in IThelp

---

## 🚨 Common Mistakes

1. **API Keys Don't Match** ❌
   - `webhook_employee_sync.php` line 10
   - `harley_sync_script.php` line 11
   - These MUST be identical!

2. **Wrong Webhook URL** ❌
   - Should be: `https://your-domain.com/webhook_employee_sync.php`
   - NOT: `https://harley.resourcestaffonline.com/...`

3. **Database Credentials** ❌
   - Get these from Hostinger cPanel
   - Database → MySQL Databases → Check credentials

4. **Table Names Different** ❌
   - Check your Harley database
   - Is it `employees` or `users` or `staff`?
   - Update SQL query in sync script line 79

---

## 🎯 Quick Test Commands

### Test 1: Check if webhook is accessible
```bash
curl http://localhost/IThelp/webhook_employee_sync.php
```
Should return: "Method not allowed. Use POST."

### Test 2: Test with sample data
```bash
curl -X POST http://localhost/IThelp/webhook_employee_sync.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: HarleyIThelp2025SecureSync789" \
  -d '{"sync_mode":"full","employees":[{"employee_id":"TEST001","fname":"Test","lname":"User","email":"test@test.com","username":"test.user"}]}'
```

---

## 📞 Need Help Finding Harley Database Info?

### In Hostinger cPanel:

1. **Login to cPanel**
2. **Go to "Databases" section**
3. **Click "MySQL Databases"**
4. You'll see:
   - Database name
   - Database user
   - Current databases list

5. **To find password:**
   - Check your existing config files
   - Or create new database user
   - Or reset password for existing user

### Common config file locations:
```
/config/database.php
/includes/config.php
/config.php
/db_config.php
```

---

## ✅ Final Checklist

Before running in production:

- [ ] API keys match in both files
- [ ] Webhook URL points to IThelp domain
- [ ] Harley database credentials are correct
- [ ] `employee_id` column added to IThelp database
- [ ] Tested locally first
- [ ] Uploaded script to Harley server
- [ ] Tested from Harley URL in browser
- [ ] Verified employees synced successfully
- [ ] No error messages in sync result

---

## 🎉 Success!

If you see this in the sync page:
```
✅ Connected to Harley database successfully
✅ Found X employees in Harley database
✅ Sync completed successfully!
```

**You're done!** Employees are now syncing from Harley to IThelp! 🚀
