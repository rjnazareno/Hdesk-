# Harley → ResolveIT Employee Sync Deployment Guide

## Overview
Automate employee synchronization from Harley website to ResolveIT ticketing system.

**Flow**: Harley Database → Sync Script → Webhook → ResolveIT Database

---

## Prerequisites

✅ **Completed**:
- Harley database on Hostinger: `u816220874_harleyrss`
- ResolveIT database on Hostinger: `u816220874_resolveIT`
- Both systems on same hosting account
- API keys match (already configured)

---

## Deployment Steps

### Step 1: Upload Webhook to ResolveIT Production

The webhook receiver is already in your codebase. Deploy it:

```bash
# Connect to production server and pull latest code
cd /home/u816220874/public_html/resolveit.resourcestaffonline.com
git pull origin main
```

**Webhook URL**: `https://resolveit.resourcestaffonline.com/webhook_employee_sync.php`

**Verify deployment**:
```bash
ls -la webhook_employee_sync.php
```

### Step 2: Upload Sync Script to Harley Server

Upload `docs/harley_sync_script.php` to Harley server:

**Target Location**: `https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php`

**Upload Methods**:

**Option A - FTP/File Manager** (Recommended):
1. Download `docs/harley_sync_script.php` from ResolveIT
2. Open cPanel File Manager
3. Navigate to: `/home/u816220874/public_html/harley.resourcestaffonline.com/Public/module/`
4. Upload `harley_sync_script.php`
5. Set permissions to 644

**Option B - Command Line** (if you have SSH access):
```bash
cd /home/u816220874/public_html/harley.resourcestaffonline.com/Public/module/
# Upload file via SFTP or copy from ResolveIT repo
wget https://raw.githubusercontent.com/AYRGO/IThelp/main/docs/harley_sync_script.php
chmod 644 harley_sync_script.php
```

### Step 3: Test Manual Sync

Visit the sync script in your browser:
```
https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php
```

**Expected Output**:
- ✅ Connected to Harley database
- ✅ Found X employees
- ✅ Sent to ResolveIT webhook
- ✅ Summary: Created/Updated/Failed counts
- ✅ Detailed employee list

**If You See Errors**:

| Error | Fix |
|-------|-----|
| Database connection failed | Verify Harley DB credentials in script lines 21-24 |
| Table not found | Check if Harley uses different table name (adjust line 73) |
| cURL error | Check webhook URL is accessible |
| HTTP 401 Unauthorized | API keys don't match between scripts |
| HTTP 404 Not Found | Webhook file not deployed to ResolveIT |

### Step 4: Verify in ResolveIT

1. Login to ResolveIT admin: `https://resolveit.resourcestaffonline.com/admin/`
2. Navigate to: **Customers** (Employee Management)
3. Verify employees from Harley are listed
4. Check employee details match Harley data

---

## Automation Setup (Optional but Recommended)

### Setup Daily Auto-Sync via Cron Job

**cPanel Instructions**:

1. Login to cPanel: `https://resourcestaffonline.com/cpanel`
2. Search for "Cron Jobs"
3. Click "Cron Jobs" under Advanced
4. Add new cron job:

**Settings**:
```
Common Settings: Once Per Day (1:00 AM)
Minute: 0
Hour: 1
Day: *
Month: *
Weekday: *
Command: /usr/bin/php /home/u816220874/public_html/harley.resourcestaffonline.com/Public/module/harley_sync_script.php > /dev/null 2>&1
```

**Alternative Times**:
- Every hour: `0 * * * *`
- Every 6 hours: `0 */6 * * *`
- Twice daily (6am & 6pm): `0 6,18 * * *`

**Email Notifications**:
To receive sync reports via email instead of silent execution:
```bash
/usr/bin/php /home/u816220874/public_html/harley.resourcestaffonline.com/Public/module/harley_sync_script.php
```
(Remove the `> /dev/null 2>&1` part)

---

## Configuration Reference

### Harley Sync Script Configuration
**File**: `harley_sync_script.php` (lines 10-24)

```php
// IThelp webhook
$WEBHOOK_URL = 'https://resolveit.resourcestaffonline.com/webhook_employee_sync.php';
$API_KEY = '333e582f2e6eccbf4d12274296fa6c53779a0d15c135da1863721c1e2509dece';

// Harley database
$DB_HOST = 'localhost';
$DB_NAME = 'u816220874_harleyrss';
$DB_USER = 'u816220874_harley';
$DB_PASS = 'Z&e#mtcW3';
```

### ResolveIT Webhook Configuration
**File**: `webhook_employee_sync.php` (line 10)

```php
define('WEBHOOK_SECRET_KEY', '333e582f2e6eccbf4d12274296fa6c53779a0d15c135da1863721c1e2509dece');
```

**Security Note**: Both API keys must match exactly!

---

## How It Works

### Sync Logic

1. **Fetch**: Harley script queries `employees` table
2. **Send**: POSTs JSON data to ResolveIT webhook with API key
3. **Authenticate**: Webhook verifies API key
4. **Process**: Webhook calls `Employee` model to sync data
5. **Response**: Returns summary of created/updated/failed records

### Data Mapping

| Harley DB Field | ResolveIT Field | Notes |
|----------------|----------------|-------|
| `id` | `employee_id` | Primary identifier |
| `fname` | `fname` | First name |
| `lname` | `lname` | Last name |
| `email` | `email` | Must be unique |
| `phone` | `phone` | Optional |
| `department` | `department` | Optional |
| `position` | `position` | Optional |
| `username` | `username` | Auto-generated if missing |

### Sync Modes

**Full Sync** (default):
- Syncs all employees from Harley
- Detects employees in ResolveIT but not in Harley
- Recommended for daily automation

**Partial Sync**:
- Only syncs provided employees
- Doesn't detect missing employees
- Useful for real-time updates

---

## Troubleshooting

### Common Issues

**1. "No employees found in Harley database"**
- **Cause**: Table name might be different
- **Fix**: Check Harley database, adjust query in script line 73
- **Check**: Run in phpMyAdmin: `SHOW TABLES LIKE '%employee%'`

**2. "cURL error: Could not resolve host"**
- **Cause**: Webhook URL incorrect or DNS issue
- **Fix**: Verify URL in browser first: `https://resolveit.resourcestaffonline.com/webhook_employee_sync.php`
- **Expected**: Should return JSON: `{"error": "Method not allowed. Use POST."}`

**3. "HTTP 401 Unauthorized"**
- **Cause**: API keys don't match
- **Fix**: Verify both files have same `WEBHOOK_SECRET_KEY` / `API_KEY`

**4. "Duplicate entry for key 'email'"**
- **Cause**: Multiple Harley employees share same email
- **Fix**: Clean Harley data or make email nullable in ResolveIT

**5. Sync works but employees can't login**
- **Cause**: Passwords not synced (security feature)
- **Fix**: Employees must use "Forgot Password" or manually set in ResolveIT admin

### Debug Mode

To see detailed webhook response, temporarily add this after curl_exec:
```php
echo "<pre>RAW RESPONSE:\n" . $response . "</pre>";
```

---

## Testing Checklist

Before setting up automation:

- [ ] Webhook accessible: Visit webhook URL (should return Method Not Allowed error)
- [ ] Manual sync works: Run sync script in browser
- [ ] Employees appear in ResolveIT admin panel
- [ ] Employee details match Harley data (name, email, department)
- [ ] No duplicate employees created
- [ ] Sync summary shows correct counts

After automation:

- [ ] Cron job created in cPanel
- [ ] First automated run completes successfully
- [ ] Check cron logs: `/var/log/cron` or email notifications
- [ ] Verify employees stay in sync after Harley updates

---

## Maintenance

### Regular Checks
- **Weekly**: Review sync logs for errors
- **Monthly**: Verify employee count matches between systems
- **Quarterly**: Test manual sync to ensure script still works

### When to Re-Run Manual Sync
- After bulk employee import in Harley
- After database migration
- If automated sync fails
- To verify data integrity

### Updating Sync Logic

**To add/change synced fields**:
1. Edit Harley sync script query (line 73)
2. Edit webhook Employee model mapping
3. Test with manual sync
4. Monitor for errors

---

## Security Notes

🔒 **API Key Security**:
- Never commit real API keys to public repos
- Use environment variables in production (future enhancement)
- Rotate API key if compromised

🔒 **Database Security**:
- Both databases on same hosting account = secure internal network
- Webhook only accepts POST with valid API key
- No public database credentials exposed

🔒 **Access Control**:
- Sync script requires direct URL access (not linked publicly)
- Consider adding IP whitelist if needed
- Webhook validates API key on every request

---

## Success Indicators

✅ **Deployment Successful When**:
1. Manual sync completes without errors
2. All Harley employees appear in ResolveIT
3. Employee details accurate (names, emails, departments)
4. Cron job runs automatically (check tomorrow)
5. No duplicate employees created

✅ **System Working Properly When**:
- New Harley employees auto-appear in ResolveIT
- Updated Harley data syncs to ResolveIT
- Sync logs show 0 failures
- IT staff can assign tickets to all employees

---

## Support

**Logs Location**:
- Cron job logs: cPanel → Cron Jobs → View Logs
- PHP errors: `/home/u816220874/logs/error_log`

**Quick Diagnosis**:
```bash
# Test webhook accessibility
curl -I https://resolveit.resourcestaffonline.com/webhook_employee_sync.php

# Test sync script syntax
php -l /path/to/harley_sync_script.php

# Check file permissions
ls -la harley_sync_script.php  # Should be 644
```

**Need Help?**
- Review error messages in browser output
- Check PHP error logs in cPanel
- Verify database credentials in both scripts
- Test webhook URL separately before full sync
