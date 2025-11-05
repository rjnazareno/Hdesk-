# Employee Login Troubleshooting Guide

## Problem: Employees Can't Login - "Invalid username or password"

### Root Cause Analysis

The most common reason employees can't login is that their passwords are stored in a **non-bcrypt format** (e.g., MD5, plain text, or other hash formats). 

**Why this happens:**
- PHP's `password_verify()` function **only works with bcrypt hashes**
- When password_verify() receives a non-bcrypt hash, it always returns `false`
- This causes the login to fail even if the employee enters the correct password

### Quick Fix (For Admins)

1. **Access Production Admin Panel**
   - Go to: `https://resolveit.resourcestaffonline.com/admin/`
   - Login as admin

2. **Click "Reset Passwords"** in the sidebar
   - This shows which employees have broken passwords
   - Shows a summary: "X Employee(s) Cannot Login"

3. **Click "Reset All Broken Passwords"**
   - This regenerates all non-bcrypt passwords to bcrypt
   - All affected employees get temporary password: `Welcome123!`
   - Employees should be instructed to reset on first login

4. **Notify Employees**
   - Inform them: "Your password has been reset to: `Welcome123!`"
   - They can now login and should change password immediately

---

## Diagnostic Tools

### 1. Check Employee Password Status

**File:** `https://resolveit.resourcestaffonline.com/admin/check_employee_passwords.php`

This page shows:
- Total employees with bcrypt hashes ✅
- Total employees with invalid hashes ❌
- List of affected employees

### 2. Reset Single Employee Password

**File:** `https://resolveit.resourcestaffonline.com/admin/reset_employee_passwords.php`

Actions:
- Reset individual employee passwords (one at a time)
- Reset all broken passwords at once
- View list of all employees and password status

---

## Technical Details

### Password Hash Formats

**✅ Working (Bcrypt)**
```
$2y$10$abcdefghijklmnopqrstuvwxyz1234567890...
```
- Starts with `$2y$` or `$2a$`
- 60+ characters long
- `password_verify()` works ✓

**❌ Not Working (MD5)**
```
5d41402abc4b2a76b9719d911017c592
```
- Exactly 32 hexadecimal characters
- `password_verify()` returns false ✗

**❌ Not Working (Plain Text)**
```
Welcome123!
```
- Less than 20 characters
- `password_verify()` returns false ✗

### How Employee Login Works

```
1. Employee enters: username="john.doe" password="Welcome123!"
2. App finds employee record in database
3. App calls: password_verify("Welcome123!", $hashFromDB)
4. password_verify() checks if hash matches password
5. If hash is bcrypt: ✅ Returns true/false correctly
6. If hash is MD5/plain: ❌ Always returns false (even correct password!)
7. Login fails with "Invalid username or password"
```

---

## Prevention: Why New Employees Have Correct Passwords

**New employees synced via webhook:**
- Webhook (`webhook_employee_sync.php`) hashes passwords as bcrypt automatically
- Default password: `Welcome123!` → hashed as bcrypt ✓

**Existing employees from old systems:**
- May have MD5 or plain text passwords from migration
- Need to be regenerated

---

## Step-by-Step Admin Instructions

### Step 1: Identify Problem Employees
1. Go to: **Admin → Reset Passwords**
2. Look at the summary box:
   - If you see "X Employee(s) Cannot Login" in red ⚠️
   - Then you have an issue

### Step 2: Review Affected Employees
1. Look at the "Employees with Broken Passwords" table
2. This lists all employees who cannot login
3. Shows their name, email, username

### Step 3: Fix All Employees at Once
1. Click button: **"Reset All Broken Passwords"**
2. Wait for success message ✅
3. Temporary password: `Welcome123!`

### Step 4: Fix Individual Employee (Optional)
1. In the table, click **"Reset"** button next to employee name
2. Success message confirms password was reset
3. Inform employee of temporary password

### Step 5: Notify Employees
Send message to affected employees:
```
Your account password has been reset to: Welcome123!

Please login and change your password immediately to something secure.

If you continue to have issues, contact IT support.
```

---

## Verification: Test Employee Login

1. **Tell employee:** "Your new password is: Welcome123!"
2. **Have employee go to:** `https://resolveit.resourcestaffonline.com/login.php`
3. **Employee enters:**
   - Username: (their username)
   - Password: `Welcome123!`
4. **Result:**
   - ✅ SUCCESS: Redirects to employee dashboard
   - ❌ FAILURE: "Invalid username or password" error

---

## Advanced: Manual SQL Check

If you need to verify password formats in the database:

```sql
-- Check all employee passwords
SELECT id, username, email, password 
FROM employees 
WHERE status = 'active'
LIMIT 20;

-- Check if password is bcrypt (starts with $2)
SELECT username, 
       CASE 
           WHEN password LIKE '$2y$%' THEN 'BCRYPT ✓'
           WHEN password LIKE '$2a$%' THEN 'BCRYPT ✓'
           WHEN LENGTH(password) = 32 AND password REGEXP '^[0-9a-f]{32}$' THEN 'MD5 ✗'
           ELSE 'OTHER'
       END as hash_type
FROM employees
WHERE status = 'active';
```

---

## Automated Fix: Webhook Ensures New Employees Are Correct

When new employees are synced from Harley database:

```php
// webhook_employee_sync.php (lines ~165)
$employeeData['password'] = password_hash('Welcome123!', PASSWORD_DEFAULT);
```

- ✅ Always hashes new passwords with bcrypt
- ✅ New employees can login immediately
- ✅ No manual intervention needed

---

## Troubleshooting Flow

```
❌ Employee can't login
   ↓
📋 Check: Admin → Reset Passwords
   ↓
❓ Are there "Broken Passwords"?
   ├─ YES: → Click "Reset All Broken Passwords" ✓
   ├─ NO:  → Check other issues (below)
   ↓
✅ Inform employee of new password
✅ Employee should now be able to login
✅ Employee should change password on first login
```

### If Still Not Working After Password Reset

1. **Check employee status:** Is employee marked as "active"?
   - Query: `SELECT status FROM employees WHERE username = 'xxx'`
   - Should be: `active`
   - If: `inactive` or `terminated` → Re-activate first

2. **Check username:** Is username unique?
   - Query: `SELECT COUNT(*) FROM employees WHERE username = 'xxx'`
   - Should return: 1 (one match)
   - If: >1 → Duplicate usernames, need to fix

3. **Check email:** Is email unique?
   - Multiple employees with same email can cause issues

4. **Check caps/spaces:** 
   - Usernames are case-insensitive
   - Passwords are case-sensitive
   - Spaces in passwords matter

---

## FAQ

**Q: How long are bcrypt hashes?**
A: Typically 60 characters (sometimes 61 with special chars)

**Q: Can I manually change a password in the database?**
A: NO! Never store plain text. Must use: `password_hash($password, PASSWORD_DEFAULT)`

**Q: What if an employee forgets their password?**
A: Use this tool to reset it to `Welcome123!`, then they can login and change it

**Q: Do new employees automatically get the correct password format?**
A: YES! The webhook handles this automatically for synced employees

**Q: Why didn't we catch this earlier?**
A: Old migration data from the previous system may have had non-bcrypt passwords

---

## Support

- **Diagnostic Tool:** `admin/check_employee_passwords.php`
- **Password Reset Tool:** `admin/reset_employee_passwords.php`
- **Contact:** IT Support

---

**Last Updated:** November 2025
**Status:** Production Fix Available ✅
