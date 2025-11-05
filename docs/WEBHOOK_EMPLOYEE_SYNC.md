# Employee Webhook Sync - Setup Guide

## Overview
This webhook allows your Harley website (Hostinger) to automatically sync employee data to the IThelp system.

### Sync Modes
- **Partial Sync** (default): Only processes employees in the payload
- **Full Sync**: Syncs ALL employees and detects any missing from source system

---

## Setup Steps

### 1. Configure Webhook Secret Key

Edit `webhook_employee_sync.php` line 10:
```php
define('WEBHOOK_SECRET_KEY', 'your-secret-key-here-change-this'); // Change this!
```

**Generate a secure key:**
```bash
# Use this or generate your own random string
php -r "echo bin2hex(random_bytes(32));"
```

### 2. Add employee_id Column to Database

Run this SQL on your IThelp database:
```sql
ALTER TABLE employees 
ADD COLUMN employee_id VARCHAR(50) UNIQUE AFTER id,
ADD INDEX idx_employee_id (employee_id);
```

### 3. Deploy Webhook on IThelp Server

The webhook is located at:
```
http://your-ithelp-domain.com/webhook_employee_sync.php
```

### 4. Create Sync Script on Harley Website

Upload `example_sync_from_harley.php` to your Harley website and configure:

```php
$webhook_url = 'https://your-ithelp-domain.com/webhook_employee_sync.php';
$api_key = 'same-secret-key-from-step-1';

// Your Harley database credentials
$harley_db_host = 'localhost';
$harley_db_name = 'harley_database';
$harley_db_user = 'db_username';
$harley_db_pass = 'db_password';
```

---

## How It Works

### Step 1: Harley Website Sends Employee Data
```
Harley Website (Hostinger)
    ↓
    Fetch employees from database
    ↓
    Send HTTP POST to webhook
    ↓
IThelp Webhook receives data
```

### Step 2: Webhook Processes Data
```
1. Validates API key
2. Checks if employee exists (by employee_id or email)
3. If exists → Update employee data
4. If new → Create new employee with default password
5. If full sync → Check for employees not in source
6. Returns sync results
```

### Sync Mode Comparison

| Feature | Partial Sync | Full Sync |
|---------|-------------|-----------|
| **Use Case** | Sync specific employees | Sync ALL employees |
| **Missing Detection** | ❌ No | ✅ Yes |
| **Payload Size** | Smaller | Larger |
| **Best For** | Single employee updates | Daily/hourly full syncs |
| **API Call** | `"sync_mode": "partial"` | `"sync_mode": "full"` |

**Recommendation:** Use **full sync** for scheduled jobs (cron) to ensure all employees stay synchronized and detect any that may have been removed from Harley system.

---

## API Endpoint

**URL:** `http://your-domain.com/webhook_employee_sync.php`  
**Method:** `POST`  
**Content-Type:** `application/json`  
**Authentication:** `X-API-Key` header

### Request Format

**Partial Sync (default):**
```json
{
  "sync_mode": "partial",
  "employees": [
    {
      "employee_id": "EMP001",
      "fname": "John",
      "lname": "Doe",
      "email": "john.doe@company.com",
      "phone": "1234567890",
      "department": "IT",
      "position": "Developer",
      "username": "john.doe"
    }
  ]
}
```

**Full Sync (recommended):**
```json
{
  "sync_mode": "full",
  "employees": [
    {
      "employee_id": "EMP001",
      "fname": "John",
      "lname": "Doe",
      "email": "john.doe@company.com",
      "phone": "1234567890",
      "department": "IT",
      "position": "Developer",
      "username": "john.doe"
    },
    {
      "employee_id": "EMP002",
      "fname": "Jane",
      "lname": "Smith",
      "email": "jane.smith@company.com",
      "phone": "0987654321",
      "department": "HR",
      "position": "Manager",
      "username": "jane.smith"
    }
  ]
}
```

### Response Format

**Partial Sync Response:**
```json
{
  "status": "completed",
  "sync_mode": "partial",
  "timestamp": "2025-11-04 10:30:00",
  "summary": {
    "total": 2,
    "created": 1,
    "updated": 1,
    "failed": 0,
    "not_in_source": 0
  },
  "details": {
    "success": [
      {
        "employee_id": "EMP001",
        "email": "john.doe@company.com",
        "name": "John Doe",
        "id": 123
      }
    ],
    "updated": [
      {
        "employee_id": "EMP002",
        "email": "jane.smith@company.com",
        "name": "Jane Smith"
      }
    ],
    "failed": [],
    "not_in_source": []
  }
}
```

**Full Sync Response (includes not_in_source):**
```json
{
  "status": "completed",
  "sync_mode": "full",
  "timestamp": "2025-11-04 10:30:00",
  "summary": {
    "total": 3,
    "created": 0,
    "updated": 3,
    "failed": 0,
    "not_in_source": 1
  },
  "details": {
    "success": [],
    "updated": [
      {
        "employee_id": "EMP001",
        "email": "john.doe@company.com",
        "name": "John Doe"
      },
      {
        "employee_id": "EMP002",
        "email": "jane.smith@company.com",
        "name": "Jane Smith"
      },
      {
        "employee_id": "EMP003",
        "email": "bob.johnson@company.com",
        "name": "Bob Johnson"
      }
    ],
    "failed": [],
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

---

## Security Features

✅ **API Key Authentication** - Only authorized requests accepted  
✅ **Input Validation** - All data sanitized before database insert  
✅ **Email Validation** - Ensures valid email format  
✅ **SQL Injection Protection** - Uses prepared statements  
✅ **HTTPS Recommended** - Use SSL in production

---

## Testing

### Quick Test with Test Script

1. Open `docs/test_webhook_sync.php` in browser:
   ```
   http://localhost/IThelp/docs/test_webhook_sync.php
   ```
2. This will run 5 automated tests showing both sync modes

### Manual Test with cURL

**Partial Sync:**
```bash
curl -X POST http://localhost/IThelp/webhook_employee_sync.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-secret-key-here" \
  -d '{
    "sync_mode": "partial",
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

**Full Sync:**
```bash
curl -X POST http://localhost/IThelp/webhook_employee_sync.php \
  -H "Content-Type: application/json" \
  -H "X-API-Key: your-secret-key-here" \
  -d '{
    "sync_mode": "full",
    "employees": [
      {
        "employee_id": "EMP001",
        "fname": "John",
        "lname": "Doe",
        "email": "john@company.com",
        "username": "john.doe"
      },
      {
        "employee_id": "EMP002",
        "fname": "Jane",
        "lname": "Smith",
        "email": "jane@company.com",
        "username": "jane.smith"
      }
    ]
  }'
```

### Test from Harley Website

1. Upload `example_sync_from_harley.php` to Harley website
2. Configure database credentials
3. Visit the URL in browser
4. Check sync results

---

## Automation Options

### Option 1: Cron Job (Recommended)
Set up cron on Harley server to run daily:

```bash
# Run every day at 2 AM
0 2 * * * /usr/bin/php /path/to/sync_employees.php >> /path/to/logs/sync.log 2>&1
```

### Option 2: Manual Trigger
Create an admin button on Harley dashboard:
```php
<button onclick="syncEmployees()">Sync Employees to IThelp</button>
```

### Option 3: Real-time Sync
Trigger webhook when employee is created/updated in Harley:
```php
// In Harley's employee create/update function
function afterEmployeeSave($employee) {
    syncToITHelp([$employee]);
}
```

---

## Troubleshooting

### Error: "Unauthorized. Invalid API key"
- Check that `X-API-Key` header matches `WEBHOOK_SECRET_KEY`
- Ensure no extra spaces in the key

### Error: "Missing required field"
- Verify all employees have: `employee_id`, `fname`, `lname`, `email`
- Check JSON structure matches expected format

### Error: "Failed to create employee"
- Check database connection
- Verify `employees` table exists
- Check if `employee_id` column was added

### Employee Not Syncing
- Check if `employee_id` or `email` already exists
- Look at `failed` array in response for details
- Check IThelp error logs

---

## Default Password

New employees get default password: **`Welcome123!`**

They should change this on first login.

---

## Field Mapping

| Harley Field | IThelp Field | Required | Notes |
|--------------|--------------|----------|-------|
| `employee_id` | `employee_id` | ✅ Yes | Unique identifier |
| `first_name` | `fname` | ✅ Yes | First name |
| `last_name` | `lname` | ✅ Yes | Last name |
| `email` | `email` | ✅ Yes | Work email |
| `phone` | `phone` | ❌ No | Contact number |
| `department` | `department` | ❌ No | Department name |
| `position` | `position` | ❌ No | Job title |
| `username` | `username` | ❌ No | Auto-generated if missing |

---

## Support

For issues or questions, check:
1. Webhook response for error details
2. IThelp error logs
3. Network connectivity between servers
4. API key configuration

---

**Created:** November 4, 2025  
**Version:** 1.0
