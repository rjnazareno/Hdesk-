# TICKET SYSTEM RESET GUIDE
## Fresh Start for Efficient Tracking

**Date:** February 11, 2026  
**Issue:** Inconsistent ticket tracking due to Harley HRIS database structure integration

---

## Problem Summary

The current system has inconsistencies in ticket tracking, with assignments appearing to transfer incorrectly between users. This is likely due to:

1. **Dual User System**: Mixing `users` table (IT staff) and `employees` table (Harley HRIS sync)
2. **Complex assignment tracking**: Multiple fields (`assigned_to`, `grabbed_by`, `assignee_type`, `submitter_type`)
3. **Data migration conflicts**: Historic data from Harley sync causing ID mismatches

---

## Solution: Complete Ticket Reset

This reset will:
- ✅ DELETE all tickets and ticket history
- ✅ DELETE all notifications
- ✅ DELETE all SLA tracking records
- ✅ CLEAR all attachment files
- ✅ PRESERVE user accounts, employees, and categories
- ✅ Reset auto-increment counters to start fresh

---

## Step-by-Step Reset Process

### ⚠️ BACKUP FIRST (Optional but Recommended)

If you want to keep a backup before deletion:

```powershell
# Export current tickets to CSV (optional)
# Run this in phpMyAdmin or MySQL client:
SELECT * FROM tickets INTO OUTFILE 'C:/backup_tickets.csv' 
FIELDS TERMINATED BY ',' 
ENCLOSED BY '"' 
LINES TERMINATED BY '\n';
```

---

### Step 1: Run Database Cleanup SQL

1. Open **phpMyAdmin** or your MySQL client
2. Select database: `u816220874_ticketing`
3. Go to **SQL** tab
4. Open file: `database/CLEAN_RESET_TICKETS.sql`
5. Click **Execute**

**What this does:**
- Deletes all records from `tickets` table
- Deletes all records from `ticket_activity` table
- Deletes all records from `notifications` table
- Deletes all records from `sla_tracking` table
- Resets auto-increment counters to 1

---

### Step 2: Clean Physical Upload Files

Run the PowerShell cleanup script:

```powershell
cd c:\Users\resty\Hdesk
.\clean-uploads.ps1
```

**What this does:**
- Deletes all files in `/uploads/` directory
- Removes ticket attachment files
- Asks for confirmation before deletion

---

### Step 3: Clear Browser Cache & Sessions

Clear sessions to prevent stale data:

```powershell
# Delete PHP session files (if using file-based sessions)
Remove-Item "C:\xampp\tmp\sess_*" -Force

# Or logout and login again in the browser
```

---

### Step 4: Verify Clean State

After running both cleanup scripts, verify:

1. **Database Check:**
   ```sql
   SELECT COUNT(*) as ticket_count FROM tickets;
   -- Should return 0
   
   SELECT COUNT(*) as user_count FROM users;
   -- Should return > 0 (users preserved)
   
   SELECT COUNT(*) as category_count FROM categories;
   -- Should return > 0 (categories preserved)
   ```

2. **Uploads Directory:**
   - Check `/uploads/` folder - should be empty

3. **Web Interface:**
   - Login as IT staff or admin
   - Check dashboard - should show 0 tickets
   - Try creating a new test ticket
   - Verify ticket number starts at **TKT-000001**

---

## Recommendations for Going Forward

### 1. **Simplify User Assignment System**

Consider unifying the dual-user system to reduce complexity:

**Current (Complex):**
- `submitter_type`: 'employee' or 'user'
- `submitter_id`: ID from either `employees` or `users` table
- `assignee_type`: 'employee' or 'user'
- `grabbed_by`: ID with assignee_type

**Recommended (Simple):**
- Only use `users` table for authentication
- Sync Harley employees INTO `users` table (not separate)
- Remove `submitter_type` and `assignee_type` fields
- Single foreign key relationships

### 2. **Fix Harley Sync Strategy**

Instead of maintaining separate `employees` table:

```sql
-- Sync Harley employees directly into users table
INSERT INTO users (username, email, full_name, role, department)
SELECT employee_id, email, CONCAT(fname, ' ', lname), 'employee', department
FROM harley_employees
ON DUPLICATE KEY UPDATE 
    full_name = VALUES(full_name),
    department = VALUES(department);
```

### 3. **Enforce Single Assignment Model**

Simplify ticket assignment:
- Remove "grab" functionality if causing confusion
- Direct assignment only: admin assigns ticket to specific IT staff
- Clear ownership: one ticket, one assigned person

---

## Database Schema Recommendations

### Current Problems:

```sql
-- TOO COMPLEX: Multiple assignment fields
assigned_to INT
grabbed_by INT
assignee_type ENUM('user', 'employee')
submitter_type ENUM('user', 'employee')
```

### Proposed Fix:

```sql
-- SIMPLIFIED: Single assignment field
assigned_to INT FOREIGN KEY REFERENCES users(id)
submitter_id INT FOREIGN KEY REFERENCES users(id)
-- Remove assignee_type and submitter_type entirely
```

---

## After Reset: Create First Test Ticket

1. Login as employee (submitter)
2. Create ticket: "Test Ticket - Fresh Start"
3. Login as IT staff
4. Verify ticket shows correctly in queue
5. Assign to yourself
6. Verify assignment persists correctly
7. Update status to "In Progress"
8. Verify no assignment changes or tracking errors

---

## Need Help?

If issues persist after reset:

1. **Check error logs:**
   - `logs/error.log`
   - PHP error log: `C:\xampp\php\logs\php_error_log`

2. **Enable debug mode:**
   - Edit `config/config.php`
   - Set `define('DEBUG_MODE', true);`

3. **Review ticket creation flow:**
   - File: `models/Ticket.php` (line 19-79)
   - Check for `submitter_type` logic

4. **Verify ticket queries:**
   - File: `models/Ticket.php` (findById method)
   - Look for CASE statements joining users/employees

---

## Files Created

1. `database/CLEAN_RESET_TICKETS.sql` - Database cleanup script
2. `clean-uploads.ps1` - File cleanup script
3. `RESET_GUIDE.md` - This guide

---

## Next Steps After Reset

- [ ] Run database cleanup SQL
- [ ] Run file cleanup PowerShell script
- [ ] Clear browser cache/logout
- [ ] Verify clean state (0 tickets)
- [ ] Create test ticket
- [ ] Verify assignment works correctly
- [ ] Consider implementing schema simplification
- [ ] Update Harley sync to merge into users table

---

**Remember:** This is a fresh start. Going forward, maintain simple, clear assignment logic to avoid tracking confusion.
