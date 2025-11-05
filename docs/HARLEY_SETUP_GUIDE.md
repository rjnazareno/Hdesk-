# Quick Setup Guide - Harley to IThelp Sync

## 🚀 Step-by-Step Instructions

### Step 1: Update Webhook Secret Key

1. Open `webhook_employee_sync.php` (line 10)
2. Change this:
   ```php
   define('WEBHOOK_SECRET_KEY', 'your-secret-key-here-change-this');
   ```
   To a secure random key (example):
   ```php
   define('WEBHOOK_SECRET_KEY', 'abc123xyz789SecureKey2025');
   ```

### Step 2: Prepare Harley Sync Script

1. Open `docs/harley_sync_script.php`
2. Update these lines (9-16):

```php
// IThelp webhook URL (change localhost to your domain)
$WEBHOOK_URL = 'https://your-ithelp-domain.com/webhook_employee_sync.php';

// API Key (MUST match webhook_employee_sync.php)
$API_KEY = 'abc123xyz789SecureKey2025'; // Same as Step 1!

// Harley database credentials
$DB_HOST = 'localhost';
$DB_NAME = 'your_harley_database_name';
$DB_USER = 'your_database_username';
$DB_PASS = 'your_database_password';
```

### Step 3: Upload to Harley Website

1. Copy `docs/harley_sync_script.php`
2. Upload to your Harley website at:
   ```
   https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php
   ```

### Step 4: Run the Sync

Open in your browser:
```
https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php
```

You'll see a beautiful dashboard showing:
- ✅ Connection status
- 📊 Sync statistics
- 📋 Created/Updated/Failed employees
- ⚠️ Employees not in source

### Step 5: Test First!

Before running on production:

1. Test locally first:
   ```
   http://localhost/IThelp/docs/test_webhook_sync.php
   ```

2. Check your IThelp database has `employee_id` column:
   ```sql
   ALTER TABLE employees 
   ADD COLUMN employee_id VARCHAR(50) UNIQUE AFTER id,
   ADD INDEX idx_employee_id (employee_id);
   ```

---

## 🔧 Troubleshooting

### Error: "Database connection failed"
**Fix:** Check your Harley database credentials in `harley_sync_script.php` lines 13-16

### Error: "Webhook returned HTTP 401"
**Fix:** API keys don't match
- Check `webhook_employee_sync.php` line 10
- Check `harley_sync_script.php` line 11
- They MUST be identical!

### Error: "Webhook returned HTTP 404"
**Fix:** Webhook URL is wrong
- Check `harley_sync_script.php` line 9
- Make sure it points to your IThelp domain

### Error: "No employees found"
**Fix:** Table name or columns are different
- Edit the SQL query in `harley_sync_script.php` lines 79-88
- Check your actual Harley database table structure

### Error: "cURL Error"
**Fix:** 
- Check if cURL is enabled on Hostinger
- Verify the webhook URL is accessible from Harley server

---

## 🤖 Setup Automatic Sync (Cron Job)

### In cPanel (Hostinger):

1. Go to **cPanel → Cron Jobs**
2. Add new cron job:

**Run daily at 2 AM:**
```
0 2 * * * /usr/bin/php /home/your-username/public_html/Public/module/harley_sync_script.php
```

**Run every hour:**
```
0 * * * * /usr/bin/php /home/your-username/public_html/Public/module/harley_sync_script.php
```

**Run every 30 minutes:**
```
*/30 * * * * /usr/bin/php /home/your-username/public_html/Public/module/harley_sync_script.php
```

---

## 📋 Checklist

Before going live, make sure:

- [ ] Webhook secret key is set in `webhook_employee_sync.php`
- [ ] Same API key is set in `harley_sync_script.php`
- [ ] Database credentials are correct in sync script
- [ ] `employee_id` column added to IThelp database
- [ ] Tested locally with `test_webhook_sync.php`
- [ ] Uploaded `harley_sync_script.php` to Harley server
- [ ] Ran sync script manually in browser (first test)
- [ ] Verified employees synced to IThelp
- [ ] Setup cron job for automatic sync (optional)

---

## 🎯 What You Get

After successful sync:
- ✅ All Harley employees automatically in IThelp
- ✅ New employees auto-created with default password "Welcome123!"
- ✅ Existing employees updated with latest data
- ✅ Detect employees removed from Harley
- ✅ Full audit trail of all changes
- ✅ Beautiful sync dashboard

---

## 📞 Need Help?

Common issues:
1. **API Key Mismatch** - Most common! Check both files.
2. **Database Credentials** - Verify in Hostinger cPanel
3. **Table Structure** - Adjust SQL query for your column names
4. **URL Wrong** - Use HTTPS and correct domain

Test URL:
```
https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php
```
