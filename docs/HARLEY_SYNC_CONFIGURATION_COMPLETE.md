# Harley Employee Sync - Configuration Complete ✅

## Summary

The Harley Employee Sync system is now **fully configured and ready for deployment**. This system automatically syncs employee data from your Harley website to the IThelp ticketing system.

**Configuration Date**: November 5, 2025  
**Status**: ✅ Ready for Production

---

## What's Been Configured

### ✅ Backend Infrastructure

1. **Webhook Endpoint** (`webhook_employee_sync.php`)
   - Receives employee data via POST requests
   - API key authentication implemented
   - Validates and sanitizes all incoming data
   - Creates/updates employees in database
   - Returns detailed sync results

2. **Employee Model** (`models/Employee.php`)
   - Updated to support `employee_id` field (external ID from Harley)
   - `findByEmployeeId()` method for lookup
   - `findByEmail()` method as fallback
   - Enhanced `create()` method with phone/department mapping
   - Enhanced `update()` method with field mapping

3. **Database Structure**
   - `employees` table has `employee_id` column (VARCHAR(50), UNIQUE)
   - Indexed for fast lookups
   - Supports dual user system (manual + synced employees)

### ✅ Sync Script

4. **Harley Sync Script** (`docs/harley_sync_script.php`)
   - Connects to Harley database
   - Fetches all employee records
   - Sends to IThelp via webhook
   - Beautiful HTML results page
   - Error handling and diagnostics
   - **Ready to upload to Harley server**

### ✅ Testing Tools

5. **Local Test Script** (`test_webhook_sync.php`)
   - Tests webhook locally before deployment
   - Creates 3 sample employees (TEST001-003)
   - Verifies entire sync flow
   - Access: `http://localhost/ResolveIT/test_webhook_sync.php`

### ✅ Documentation

6. **Comprehensive Guides Created**:
   - `HARLEY_SYNC_DEPLOYMENT_GUIDE.md` - Full deployment instructions
   - `HARLEY_SYNC_QUICKSTART.md` - 5-minute quick start
   - `HARLEY_SYNC_VISUAL_GUIDE.md` - Visual diagrams and flows
   - This summary document

---

## Files Ready for Deployment

```
✅ webhook_employee_sync.php         → Already in IThelp root (ready)
✅ models/Employee.php                → Updated with sync support
✅ test_webhook_sync.php              → Available for testing
⚠️ docs/harley_sync_script.php       → UPLOAD TO HARLEY SERVER
```

---

## Pre-Deployment Testing

### Run Local Test

1. Open in browser: `http://localhost/ResolveIT/test_webhook_sync.php`
2. Should see: ✅ Success message with 3 test employees created
3. Verify in admin panel: `http://localhost/ResolveIT/admin/customers.php`
4. Clean up test data: Delete TEST001, TEST002, TEST003

---

## Deployment Steps

### Step 1: Update Configuration

Edit `docs/harley_sync_script.php` (lines 13-16):

```php
// REQUIRED: Update these values
$WEBHOOK_URL = 'https://your-ithelp-domain.com/webhook_employee_sync.php';
$API_KEY = '333e582f2e6eccbf4d12274296fa6c53779a0d15c135da1863721c1e2509dece';

// Harley database credentials
$DB_HOST = 'localhost';
$DB_NAME = 'your_harley_database_name';
$DB_USER = 'your_database_username';
$DB_PASS = 'your_database_password';
```

### Step 2: Verify Harley Database Structure

The script expects an `employees` table with these columns:
- `id` (or similar - will map to `employee_id`)
- `fname` (first name)
- `lname` (last name)
- `email` (required, must be valid)
- `phone` (optional)
- `department` (optional, maps to `company`)
- `position` (optional)
- `username` (optional, auto-generated if missing)

**If your columns are different**, update the SQL query in `harley_sync_script.php` (lines 31-40).

### Step 3: Upload to Harley Server

1. **Connect via FTP/File Manager** to `harley.resourcestaffonline.com`
2. **Navigate to**: `/public_html/Public/module/`
3. **Upload**: `harley_sync_script.php`
4. **Set permissions**: 644 or 755

### Step 4: Test on Production

Access in browser:
```
https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php
```

**Expected Result**:
- ✅ Database connection successful
- ✅ Employees found and listed
- ✅ Webhook sync completed
- ✅ Summary shows created/updated employees

**If errors occur**, see troubleshooting section below.

### Step 5: Setup Automation (Optional)

**cPanel → Cron Jobs**:
```bash
# Run daily at 2:00 AM
0 2 * * * /usr/bin/php /home/YOUR_USERNAME/public_html/Public/module/harley_sync_script.php > /dev/null 2>&1
```

Replace `YOUR_USERNAME` with your actual cPanel username.

---

## How It Works

### Sync Process

1. **Harley script** queries employees from Harley database
2. **Sends via HTTPS** to IThelp webhook (JSON payload)
3. **Webhook validates** API key and data structure
4. **For each employee**:
   - Check if exists (by `employee_id` or `email`)
   - If exists → UPDATE record with new data
   - If new → INSERT new record with default password
5. **Returns results** (created/updated/failed counts)
6. **Script displays** beautiful HTML results page

### Data Mapping

| Harley Field | IThelp Field | Notes |
|--------------|--------------|-------|
| `id` | `employee_id` | Unique identifier |
| `fname` | `fname` | First name |
| `lname` | `lname` | Last name |
| `email` | `email` | Must be unique |
| `phone` | `contact` | Phone number |
| `department` | `company` | Department name |
| `position` | `position` | Job title |
| `username` | `username` | Auto-generated if missing |
| (generated) | `password` | Default: `Welcome123!` |

### New Employee Login

After sync, employees can login to IThelp:
- **URL**: `http://your-ithelp-domain.com/login.php`
- **Username**: Their username (e.g., `john.doe`)
- **Password**: `Welcome123!` (they should change this)

---

## Security Features

✅ **API Key Authentication** - Prevents unauthorized access  
✅ **HTTPS Encryption** - Data transmitted securely  
✅ **Request Method Validation** - Only POST allowed  
✅ **Input Sanitization** - Prevents XSS attacks  
✅ **Prepared Statements** - SQL injection prevention  
✅ **Password Hashing** - BCrypt for new passwords  

**Recommendation**: Change the default API key before production deployment.

---

## Monitoring

### Check Sync Status

**View synced employees**:
```sql
SELECT employee_id, fname, lname, email, created_at 
FROM employees 
WHERE employee_id IS NOT NULL 
ORDER BY created_at DESC;
```

**Count synced vs manual**:
```sql
SELECT 
  SUM(CASE WHEN employee_id IS NOT NULL THEN 1 ELSE 0 END) as synced,
  SUM(CASE WHEN employee_id IS NULL THEN 1 ELSE 0 END) as manual
FROM employees;
```

### Manual Sync

Run anytime by accessing:
```
https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php
```

---

## Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| **Database connection failed** | Check credentials in harley_sync_script.php |
| **No employees found** | Check table/column names in SQL query |
| **401 Unauthorized** | API keys don't match in both files |
| **404 Not Found** | Check WEBHOOK_URL is correct |
| **Duplicate email error** | Email already exists, will update existing record |
| **Invalid email format** | Employee email is malformed, check source data |

### Enable Detailed Logging

Add to `webhook_employee_sync.php` (after line 8):
```php
// Log all requests
file_put_contents(
    __DIR__ . '/webhook_sync_log.txt', 
    date('Y-m-d H:i:s') . ' - ' . json_encode($data) . "\n", 
    FILE_APPEND
);
```

---

## Success Criteria

✅ **Before considering deployment complete**, verify:

- [ ] Local test successful (`test_webhook_sync.php`)
- [ ] Configuration updated with production values
- [ ] Script uploaded to Harley server
- [ ] Manual run successful on production
- [ ] At least one employee synced successfully
- [ ] Employee can login to IThelp
- [ ] Test data cleaned up
- [ ] Cron job setup (if automated)
- [ ] Documentation reviewed

---

## Rollback Plan

If issues occur:

1. **Disable cron job** (if automated)
2. **Backup employees table**:
   ```sql
   CREATE TABLE employees_backup AS SELECT * FROM employees;
   ```
3. **Remove synced employees**:
   ```sql
   DELETE FROM employees WHERE employee_id IS NOT NULL;
   ```
4. **Fix issues and re-sync**

---

## Support & Maintenance

### Documentation Locations

- **Full Guide**: `docs/HARLEY_SYNC_DEPLOYMENT_GUIDE.md`
- **Quick Start**: `docs/HARLEY_SYNC_QUICKSTART.md`
- **Visual Guide**: `docs/HARLEY_SYNC_VISUAL_GUIDE.md`
- **This Summary**: `docs/HARLEY_SYNC_CONFIGURATION_COMPLETE.md`

### Key Files

- **Webhook**: `webhook_employee_sync.php`
- **Model**: `models/Employee.php`
- **Sync Script**: `docs/harley_sync_script.php` (upload to Harley)
- **Test**: `test_webhook_sync.php`

### Future Enhancements

Potential additions:
- Email notification for new accounts
- Profile picture sync
- Two-way sync (IThelp → Harley)
- Deactivate employees not in Harley
- Sync activity dashboard
- Slack/email notifications on sync errors

---

## Configuration Team

**Configured by**: GitHub Copilot AI Assistant  
**Date**: November 5, 2025  
**System**: IThelp Ticketing System  
**Repository**: AYRGO/IThelp  
**Branch**: local  

---

## Next Actions

### Immediate (Today)

1. ✅ Run local test: `http://localhost/ResolveIT/test_webhook_sync.php`
2. ⚠️ Update configuration in `harley_sync_script.php`
3. ⚠️ Upload to Harley server
4. ⚠️ Test on production

### Short Term (This Week)

5. ⚠️ Setup cron job for daily sync
6. ⚠️ Monitor first few syncs
7. ⚠️ Notify employees about IThelp access

### Long Term (Optional)

8. ⚪ Add email notifications for new accounts
9. ⚪ Implement two-way sync
10. ⚪ Create sync monitoring dashboard

---

## Questions?

Refer to the comprehensive guides in the `docs/` folder:
- Deployment issues → `HARLEY_SYNC_DEPLOYMENT_GUIDE.md`
- Quick reference → `HARLEY_SYNC_QUICKSTART.md`
- Visual diagrams → `HARLEY_SYNC_VISUAL_GUIDE.md`

---

**🎉 Configuration Complete! Ready for Deployment.**

Last updated: November 5, 2025
