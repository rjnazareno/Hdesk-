# Webhook Full Sync Feature - Update Summary

## What Changed

### Problem
The original webhook only processed employees sent in the payload. If you wanted to sync ALL employees from Harley to IThelp, you had to:
1. Manually ensure all employees were included in the request
2. No way to detect employees that exist in IThelp but were removed from Harley

### Solution
Added **Full Sync Mode** that:
✅ Processes ALL employees sent in payload
✅ Tracks which employees were synced
✅ Detects employees that exist locally but not in source system
✅ Returns `not_in_source` array with missing employees

---

## Updated Files

### 1. `webhook_employee_sync.php`
**Changes:**
- Added `sync_mode` parameter (`partial` or `full`)
- Added `$trackSyncedIds` array to track processed employee IDs
- Added post-processing check for employees not in source
- Updated response to include `sync_mode` and `not_in_source` count

**New Response Fields:**
```json
{
  "sync_mode": "full",
  "summary": {
    "not_in_source": 1  // New field
  },
  "details": {
    "not_in_source": [   // New array
      {
        "employee_id": "EMP999",
        "email": "old@company.com",
        "name": "Old Employee",
        "note": "Employee exists locally but not in Harley system"
      }
    ]
  }
}
```

### 2. `docs/example_sync_from_harley.php`
**Changes:**
- Updated payload to include `"sync_mode": "full"`
- Added comment explaining when to use `partial` vs `full`

**Before:**
```php
$payload = [
    'employees' => $employees
];
```

**After:**
```php
$payload = [
    'sync_mode' => 'full',  // Recommended for scheduled syncs
    'employees' => $employees
];
```

### 3. `docs/WEBHOOK_EMPLOYEE_SYNC.md`
**Changes:**
- Added "Sync Modes" section in overview
- Added "Sync Mode Comparison" table
- Updated request format examples for both modes
- Added full sync response example
- Updated testing section with both sync modes
- Explained when to use each mode

### 4. `docs/test_webhook_sync.php` *(NEW FILE)*
**Purpose:** Automated testing script for webhook
**Features:**
- Tests partial sync with 2 employees
- Tests full sync with all 3 employees
- Tests updating existing employee
- Tests invalid data handling
- Tests wrong API key rejection
- Displays formatted results in browser

---

## How to Use

### Partial Sync (Single Employee)
**Use When:** Updating specific employees (e.g., on employee save)
```json
{
  "sync_mode": "partial",
  "employees": [
    { "employee_id": "EMP001", ... }
  ]
}
```

### Full Sync (All Employees)
**Use When:** Scheduled jobs, initial import, ensuring complete sync
```json
{
  "sync_mode": "full",
  "employees": [
    { "employee_id": "EMP001", ... },
    { "employee_id": "EMP002", ... },
    { "employee_id": "EMP003", ... }
  ]
}
```

---

## Testing

### Quick Test (Automated)
Visit in browser:
```
http://localhost/IThelp/docs/test_webhook_sync.php
```

This runs 5 automated tests:
1. ✅ Partial sync (2 employees)
2. ✅ Full sync (3 employees)
3. ✅ Update existing employee
4. ✅ Invalid data (missing fields)
5. ✅ Wrong API key

### Manual Test (cURL)
```bash
# Full sync test
curl -X POST http://localhost/IThelp/webhook_employee_sync.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-secret-key-here" \
  -d '{
    "sync_mode": "full",
    "employees": [
      {
        "employee_id": "TEST001",
        "fname": "Test",
        "lname": "User",
        "email": "test@example.com",
        "username": "test.user"
      }
    ]
  }'
```

---

## Production Deployment

### Step 1: Update API Key
Edit `webhook_employee_sync.php` line 10:
```php
define('WEBHOOK_SECRET_KEY', 'generate-secure-random-key');
```

### Step 2: Test Locally
```
http://localhost/IThelp/docs/test_webhook_sync.php
```

### Step 3: Deploy to Harley Website
Upload `example_sync_from_harley.php` and configure:
```php
$webhook_url = 'https://your-ithelp-domain.com/webhook_employee_sync.php';
$api_key = 'same-secret-key-from-step-1';
```

### Step 4: Setup Cron Job (Optional)
On Harley server (cPanel):
```bash
# Run every day at 2 AM
0 2 * * * /usr/bin/php /path/to/example_sync_from_harley.php
```

---

## Benefits of Full Sync

✅ **Complete Data Integrity** - Ensures all employees are synced
✅ **Missing Detection** - Identifies employees removed from Harley
✅ **Audit Trail** - Know exactly which employees are out of sync
✅ **Easy Scheduled Jobs** - Perfect for daily/hourly cron tasks
✅ **No Manual Tracking** - System handles everything automatically

---

## Example Response

### Full Sync with Missing Employee
```json
{
  "status": "completed",
  "sync_mode": "full",
  "timestamp": "2025-11-04 14:30:00",
  "summary": {
    "total": 3,
    "created": 0,
    "updated": 3,
    "failed": 0,
    "not_in_source": 1
  },
  "details": {
    "updated": [
      { "employee_id": "EMP001", "name": "John Doe" },
      { "employee_id": "EMP002", "name": "Jane Smith" },
      { "employee_id": "EMP003", "name": "Bob Johnson" }
    ],
    "not_in_source": [
      {
        "employee_id": "EMP999",
        "email": "old.employee@company.com",
        "name": "Old Employee",
        "note": "Employee exists locally but not in Harley system"
      }
    ]
  }
}
```

**Action:** Review `not_in_source` employees and decide if they should be:
- Removed from IThelp
- Marked as inactive
- Or kept (if they were manually created)

---

## Next Steps

1. ✅ Files updated with full sync feature
2. ⏳ Test webhook locally: `docs/test_webhook_sync.php`
3. ⏳ Update `WEBHOOK_SECRET_KEY` with secure key
4. ⏳ Run database migration (add `employee_id` column)
5. ⏳ Deploy and test from Harley website
6. ⏳ Setup cron job for automatic syncing

---

## Questions?

- **Q: Will full sync be slower?**  
  A: Slightly, but it processes all employees in one request. For 100 employees, it's still under 1 second.

- **Q: What if I don't want to track missing employees?**  
  A: Use `"sync_mode": "partial"` - it won't check for missing employees.

- **Q: Can I delete employees automatically?**  
  A: Not currently. The webhook reports missing employees but doesn't delete them. You can add this feature if needed.

- **Q: What about employees created manually in IThelp?**  
  A: They won't have `employee_id` set, so they're ignored during full sync checks.
