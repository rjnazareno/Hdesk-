# Harley Employee Sync - Deployment Guide

## Overview

This system automatically syncs employees from your Harley website to the IThelp ticketing system. Employees synced from Harley can immediately submit tickets without manual account creation.

---

## Architecture

```
┌─────────────────────────┐
│  Harley Website         │
│  (Hostinger Server)     │
│                         │
│  harley_sync_script.php │──┐
│  - Fetches employees    │  │
│  - Sends via webhook    │  │
└─────────────────────────┘  │
                             │ HTTPS POST
                             │ (JSON payload)
                             ▼
┌─────────────────────────┐
│  IThelp System          │
│  (Your Server)          │
│                         │
│  webhook_employee_sync  │
│  - Receives data        │
│  - Creates/updates DB   │
└─────────────────────────┘
```

---

## Files Involved

### 1. **webhook_employee_sync.php** (IThelp side)
- **Location**: `/webhook_employee_sync.php` (root of IThelp)
- **Purpose**: Receives employee data and syncs to database
- **Security**: API key authentication required
- **Status**: ✅ Already configured

### 2. **harley_sync_script.php** (Harley side)
- **Location**: Upload to `https://harley.resourcestaffonline.com/Public/module/`
- **Purpose**: Fetches employees from Harley DB and sends to IThelp
- **Configuration Required**: ✅ (see below)

### 3. **test_webhook_sync.php** (Testing only)
- **Location**: `/test_webhook_sync.php` (local testing)
- **Purpose**: Test webhook locally before deploying
- **Access**: `http://localhost/ResolveIT/test_webhook_sync.php`

---

## Pre-Deployment Checklist

### Local Testing (Do First!)

1. **Test the webhook locally**:
   ```
   http://localhost/ResolveIT/test_webhook_sync.php
   ```
   - Should create 3 test employees (TEST001, TEST002, TEST003)
   - Verify success message
   - Check employees appear in: `http://localhost/ResolveIT/admin/customers.php`

2. **Clean up test data**:
   - Go to Employees page
   - Delete the 3 test employees (TEST001-003)
   - Or run SQL: `DELETE FROM employees WHERE employee_id LIKE 'TEST%'`

### Production Preparation

3. **Generate new API key** (recommended for production):
   ```php
   // Run in PHP:
   echo bin2hex(random_bytes(32));
   // Example output: 7f8e9a2b3c4d5e6f7a8b9c0d1e2f3a4b...
   ```

4. **Update webhook_employee_sync.php**:
   ```php
   define('WEBHOOK_SECRET_KEY', 'YOUR_NEW_API_KEY_HERE');
   ```

5. **Update harley_sync_script.php** (lines 13-16):
   ```php
   $WEBHOOK_URL = 'https://your-ithelp-domain.com/webhook_employee_sync.php';
   $API_KEY = 'YOUR_NEW_API_KEY_HERE'; // Must match webhook file
   
   $DB_HOST = 'localhost';
   $DB_NAME = 'your_harley_database_name';
   $DB_USER = 'your_database_username';
   $DB_PASS = 'your_database_password';
   ```

---

## Deployment Steps

### Step 1: Upload to Harley Server

1. **Connect to Hostinger via FTP/File Manager**
2. **Navigate to**: `/public_html/Public/module/`
3. **Upload**: `harley_sync_script.php`
4. **Set permissions**: 644 (read/write for owner, read for others)

### Step 2: Configure Database Connection

The script needs to connect to your Harley database. You have two options:

#### Option A: Use existing Harley config (recommended)

If Harley already has a database config file, modify `harley_sync_script.php`:

```php
// Line 17-20, replace with:
require_once '../config/database.php'; // Adjust path to your config
```

#### Option B: Add credentials directly

```php
$DB_HOST = 'localhost';
$DB_NAME = 'harley_db_name';        // Check in cPanel > MySQL Databases
$DB_USER = 'harley_db_user';        // Your MySQL username
$DB_PASS = 'your_secure_password';  // Your MySQL password
```

### Step 3: Verify Harley Database Structure

The script expects this table structure:

```sql
-- Your Harley employees table should have these columns:
CREATE TABLE employees (
    id INT PRIMARY KEY,
    fname VARCHAR(100),
    lname VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),       -- Optional
    department VARCHAR(100), -- Optional
    position VARCHAR(100),   -- Optional
    username VARCHAR(50)     -- Optional
);
```

**If your table has different column names**, edit line 31-40 in `harley_sync_script.php`:

```php
$sql = "SELECT 
            id as employee_id,
            first_name as fname,    -- Change column names here
            last_name as lname,
            email_address as email,
            -- ... adjust as needed
        FROM your_table_name        -- Change table name if needed
        WHERE status = 'active'";   -- Add your filters
```

### Step 4: Test from Harley Server

1. **Access the script** in your browser:
   ```
   https://harley.resourcestaffonline.com/Public/module/harley_sync_script.php
   ```

2. **Expected result**:
   - Shows connection status
   - Displays number of employees found
   - Shows sync results (created/updated/failed)
   - Lists any errors

3. **Common issues**:
   - ❌ **Database connection failed**: Check credentials (Step 2)
   - ❌ **No employees found**: Check table/column names (Step 3)
   - ❌ **Webhook failed**: Check WEBHOOK_URL and API_KEY match
   - ❌ **401 Unauthorized**: API keys don't match

---

## Sync Modes

### Partial Sync (Default)
```php
'sync_mode' => 'partial'
```
- Only creates/updates employees sent in payload
- Does NOT check for missing employees
- Faster, recommended for scheduled syncs

### Full Sync
```php
'sync_mode' => 'full'
```
- Creates/updates employees
- **Also reports** employees in IThelp that aren't in Harley
- Useful for audit/cleanup
- Slightly slower

To use full sync, change line 11 in `harley_sync_script.php`:
```php
$payload = [
    'sync_mode' => 'full',  // Changed from 'partial'
    'employees' => $employees
];
```

---

## Automated Sync (Optional)

### Setup Cron Job in cPanel

1. **Login to cPanel** (Hostinger)
2. **Go to**: Advanced → Cron Jobs
3. **Add new cron job**:

   ```bash
   # Run daily at 2:00 AM
   0 2 * * * /usr/bin/php /home/your-username/public_html/Public/module/harley_sync_script.php > /dev/null 2>&1
   ```

   Or hourly:
   ```bash
   0 * * * * /usr/bin/php /home/your-username/public_html/Public/module/harley_sync_script.php > /dev/null 2>&1
   ```

4. **Email notifications**: 
   - cPanel will email you if the script fails
   - Or redirect to log file:
     ```bash
     0 2 * * * /usr/bin/php /path/to/harley_sync_script.php >> /path/to/logs/sync.log 2>&1
     ```

---

## Monitoring & Troubleshooting

### Check Sync Status

**View in IThelp**:
- Go to: `http://your-ithelp-domain.com/admin/customers.php`
- Look for employees with `employee_id` set
- These are synced from Harley

**SQL Query**:
```sql
-- Count synced employees
SELECT COUNT(*) FROM employees WHERE employee_id IS NOT NULL;

-- View recent syncs
SELECT employee_id, fname, lname, email, created_at 
FROM employees 
WHERE employee_id IS NOT NULL 
ORDER BY created_at DESC 
LIMIT 10;
```

### Common Issues

#### 1. Duplicate Emails
**Error**: "Email already exists"

**Fix**: The webhook checks both `employee_id` and `email`. If an employee exists with the same email but different `employee_id`, it will update the existing record.

#### 2. Missing Employees
**Issue**: Employees aren't appearing in IThelp

**Debug**:
1. Run script manually: `https://harley.../harley_sync_script.php`
2. Check "Failed Employees" section
3. Common causes:
   - Missing required field (fname, lname, email)
   - Invalid email format
   - Database constraint violation

#### 3. Password for New Employees
**Default**: `Welcome123!`

**Change** in `webhook_employee_sync.php` line 116:
```php
$employeeData['password'] = password_hash('YourDefaultPassword', PASSWORD_DEFAULT);
```

**Best practice**: Send password reset email (TODO: add email notification)

---

## Security Considerations

### 1. API Key Protection
- **Change default key** before production
- Store in environment variables if possible
- Never commit to public repositories

### 2. HTTPS Required
- Ensure webhook URL uses HTTPS in production
- Prevents man-in-the-middle attacks
- Most hosting providers (Hostinger) support free SSL

### 3. IP Whitelisting (Optional)
Add to `webhook_employee_sync.php` after line 8:

```php
// Only allow requests from Harley server
$allowedIPs = ['123.456.789.0']; // Harley server IP
$clientIP = $_SERVER['REMOTE_ADDR'];

if (!in_array($clientIP, $allowedIPs)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied from IP: ' . $clientIP]);
    exit;
}
```

### 4. Rate Limiting (Optional)
Prevent abuse with rate limiting:

```php
// Allow max 10 requests per hour
$cacheFile = __DIR__ . '/webhook_rate_limit.txt';
$requests = file_exists($cacheFile) ? (int)file_get_contents($cacheFile) : 0;

if ($requests > 10) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit;
}

file_put_contents($cacheFile, $requests + 1);
// Reset hourly via cron: 0 * * * * echo 0 > /path/webhook_rate_limit.txt
```

---

## Field Mapping

| Harley Field | IThelp Field | Required | Notes |
|--------------|--------------|----------|-------|
| `id` | `employee_id` | ✅ Yes | Unique identifier from Harley |
| `fname` | `fname` | ✅ Yes | First name |
| `lname` | `lname` | ✅ Yes | Last name |
| `email` | `email` | ✅ Yes | Must be valid email format |
| `phone` | `contact` | ⚪ Optional | Phone number |
| `department` | `company` | ⚪ Optional | Mapped to company field |
| `position` | `position` | ⚪ Optional | Job title |
| `username` | `username` | ⚪ Optional | Auto-generated if missing |

### Auto-Generated Fields

- **Username**: Generated as `firstname.lastname` if not provided
- **Password**: Set to `Welcome123!` for new employees
- **Role**: Defaults to `employee`
- **Status**: Defaults to `active`

---

## Testing Checklist

Before going live, verify:

- [ ] Local test passed (`test_webhook_sync.php`)
- [ ] API keys match in both files
- [ ] WEBHOOK_URL points to production domain
- [ ] Harley database credentials are correct
- [ ] Script runs without errors on Harley server
- [ ] At least one employee synced successfully
- [ ] Employee appears in IThelp admin panel
- [ ] Employee can login to IThelp
- [ ] Clean up test data before production

---

## Support & Maintenance

### Logs Location
- **IThelp**: Check Apache error log (`/xampp/apache/logs/error.log`)
- **Harley**: Check cPanel error logs or script output

### Manual Sync
Run anytime: `https://harley.../harley_sync_script.php`

### Disable Sync
Comment out cron job or rename script file

### Update Sync Logic
Edit `webhook_employee_sync.php` to customize:
- Default password
- Field validation
- Email notifications
- Duplicate handling

---

## Rollback Plan

If something goes wrong:

1. **Disable cron job** (if automated)
2. **Backup employees table**:
   ```sql
   CREATE TABLE employees_backup AS SELECT * FROM employees;
   ```
3. **Remove synced employees** (if needed):
   ```sql
   DELETE FROM employees WHERE employee_id IS NOT NULL;
   ```
4. **Restore from backup**:
   ```sql
   INSERT INTO employees SELECT * FROM employees_backup;
   ```

---

## Future Enhancements

### Planned Features
- [ ] Email notification for new employee accounts
- [ ] Two-way sync (IThelp → Harley)
- [ ] Deactivate employees not in Harley (full sync mode)
- [ ] Sync profile pictures
- [ ] Sync schedule/shift data
- [ ] Webhook activity log/dashboard
- [ ] Error notification via email/Slack

### Custom Modifications
See `webhook_employee_sync.php` and `models/Employee.php` for extension points.

---

## Questions?

**Issue**: Script not working  
**Solution**: Check this guide's "Troubleshooting" section

**Issue**: Need to sync additional fields  
**Solution**: Edit SQL query in `harley_sync_script.php` (line 31) and field mapping in `webhook_employee_sync.php` (line 79)

**Issue**: Want to customize default password  
**Solution**: Edit `webhook_employee_sync.php` line 116

---

## Deployment Date
- **Configured**: November 5, 2025
- **Deployed**: _Pending_
- **First Sync**: _Pending_

---

## Contact Information
- **System**: IThelp Ticketing System
- **Repository**: AYRGO/IThelp
- **Branch**: local
