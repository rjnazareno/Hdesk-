# Migration Guide - Restructuring Complete

## Overview
The IT Help Desk system has been restructured to separate admin/IT staff functionality from employee (customer) functionality with proper MVC architecture.

## What Changed

### 1. **Database Structure**
The database schema has been updated to separate user types:

- **`users` table**: IT staff and admins only
  - Columns: id, username, email, password, full_name, role (it_staff/admin), department, phone, is_active, created_at, updated_at
  
- **`employees` table**: Regular employees (customers)
  - Columns: id, username, email, personal_email, password, fname, lname, company, position, contact, official_sched, role (employee/manager/supervisor), status (active/inactive/terminated), profile_picture, profile_image, created_at

- **`tickets` table**: Added `submitter_type` field
  - Values: 'employee' or 'user' to indicate who submitted the ticket

- **`ticket_activity` table**: Added `user_type` field
  - Values: 'employee' or 'user' to indicate who performed the action

### 2. **Folder Structure**
```
IThelp/
├── admin/                  # IT Staff/Admin Pages (NEW)
│   ├── dashboard.php
│   ├── tickets.php
│   ├── view_ticket.php
│   ├── customers.php
│   ├── categories.php
│   └── export_tickets.php
│
├── customer/              # Employee Pages (NEW)
│   ├── dashboard.php
│   ├── tickets.php
│   ├── create_ticket.php
│   └── view_ticket.php
│
├── controllers/           # MVC Controllers (NEW)
│   └── LoginController.php
│
├── models/
│   ├── User.php          # IT Staff/Admin operations
│   ├── Employee.php      # Employee operations (NEW)
│   ├── Ticket.php        # Updated with submitter_type
│   ├── TicketActivity.php # Updated with user_type
│   └── Category.php
│
├── includes/
│   ├── Auth.php          # Updated for dual authentication
│   ├── Mailer.php
│   └── ReportGenerator.php
│
└── config/
    ├── config.php
    └── database.php
```

### 3. **Authentication Changes**

#### Session Variables
The system now tracks user type with `$_SESSION['user_type']`:
- `'user'` - IT staff or admin
- `'employee'` - Regular employee

#### Auth Class Updates
- `login()` - Now checks both users and employees tables
- `verifyLogin()` - Returns user data with type
- `requireITStaff()` - New method to restrict IT staff-only pages

#### LoginController
- Automatically redirects users based on type:
  - IT Staff/Admin → `admin/dashboard.php`
  - Employee → `customer/dashboard.php`

### 4. **Model Updates**

#### User Model (`models/User.php`)
- Now handles only IT staff and admins
- `getStats()` updated to exclude employees

#### Employee Model (`models/Employee.php`) - NEW
- Complete CRUD operations for employees
- `verifyLogin()` - Authenticate employee
- `getFullName()` - Concatenate fname + lname
- `getStats()` - Employee-specific statistics

#### Ticket Model (`models/Ticket.php`)
- `create()` method now includes `submitter_type`
- Queries updated to handle both user types

#### TicketActivity Model (`models/TicketActivity.php`)
- `log()` method now includes `user_type`

### 5. **File Path Updates**
All admin and customer pages now use relative paths:
- Config: `require_once __DIR__ . '/../config/config.php';`
- Logout: `href="../logout.php"`
- Assets: `../uploads/`, `../article.php`, etc.

## Migration Steps

### Step 1: Backup Current Database
```sql
mysqldump -u root ithelp > backup_before_migration.sql
```

### Step 2: Run Database Migration
Import the updated schema:
```sql
mysql -u root ithelp < database/schema_updated.sql
```

This will:
- Create the new `employees` table
- Update `tickets` table with `submitter_type` column
- Update `ticket_activity` table with `user_type` column
- Keep existing users table for IT staff/admins

### Step 3: Migrate Existing Data (If Needed)

If you have existing users that should be employees:
```sql
-- Copy regular users to employees table
INSERT INTO employees (username, email, password, fname, lname, company, position, status, created_at)
SELECT username, email, password, 
       SUBSTRING_INDEX(full_name, ' ', 1) as fname,
       SUBSTRING_INDEX(full_name, ' ', -1) as lname,
       department as company,
       'Employee' as position,
       IF(is_active = 1, 'active', 'inactive') as status,
       created_at
FROM users 
WHERE role NOT IN ('it_staff', 'admin');

-- Remove non-IT staff from users table
DELETE FROM users WHERE role NOT IN ('it_staff', 'admin');

-- Update existing tickets to set submitter_type
UPDATE tickets SET submitter_type = 'employee' WHERE submitter_type IS NULL;

-- Update existing ticket_activity to set user_type
UPDATE ticket_activity SET user_type = 'employee' WHERE user_type IS NULL;
```

### Step 4: Create Test Accounts

Create test accounts for both user types:

```sql
-- IT Staff Account
INSERT INTO users (username, email, password, full_name, role, department, is_active) 
VALUES ('admin', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin', 'IT Department', 1);
-- Password: password

-- Employee Account  
INSERT INTO employees (username, email, password, fname, lname, company, position, status) 
VALUES ('employee', 'employee@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', 'ABC Company', 'Sales Representative', 'active');
-- Password: password
```

### Step 5: Test the System

1. **Test IT Staff Login**
   - URL: `http://localhost/IThelp/login.php`
   - Username: `admin`
   - Password: `password`
   - Should redirect to: `admin/dashboard.php`
   - Should see: Admin menu with Customers, Categories

2. **Test Employee Login**
   - URL: `http://localhost/IThelp/login.php`
   - Username: `employee`
   - Password: `password`
   - Should redirect to: `customer/dashboard.php`
   - Should see: Customer menu with My Tickets, Create Ticket

3. **Test Ticket Creation**
   - Login as employee
   - Create a new ticket
   - Verify `submitter_type` is set to 'employee'
   - Verify ticket appears in employee dashboard
   - Login as admin
   - Verify ticket appears in admin dashboard

## Important Notes

### Database Name
- The database name is **`ithelp`** (not `ithelp_db`)
- Verify in `config/database.php`: `DB_NAME = 'ithelp'`

### File Permissions
Ensure upload directory has write permissions:
```bash
chmod 777 uploads/
```

### Old Files
The old root-level files (dashboard.php, tickets.php, etc.) are still present but no longer used. You can:
- Keep them as backup
- Delete them after confirming the new structure works
- Move them to an `_old/` folder

### Security
- All admin pages now require IT staff role: `$auth->requireITStaff();`
- All customer pages check for employee type
- Unauthorized users are automatically redirected

## Troubleshooting

### Login Issues
1. Check `$_SESSION['user_type']` is being set correctly
2. Verify `includes/Auth.php` has the `verifyLogin()` method
3. Check browser console for JavaScript errors

### Path Issues
1. If CSS/JS not loading, check relative paths (../)
2. Verify `.htaccess` rewrite rules are working
3. Check Apache mod_rewrite is enabled

### Database Issues
1. Verify schema_updated.sql was imported successfully
2. Check table structures match the documentation
3. Ensure foreign keys are properly set

### Redirect Issues
1. Clear browser cache and cookies
2. Check `controllers/LoginController.php` redirects
3. Verify both admin/ and customer/ folders exist

## Next Steps

After successful migration:

1. **Test thoroughly**
   - Test all functionality as both user types
   - Verify tickets are created with correct submitter_type
   - Check permissions and access control

2. **Update documentation**
   - Update README.md with new structure
   - Document new employee table fields
   - Update API documentation if applicable

3. **Clean up**
   - Remove old root-level view files
   - Delete unused debug files
   - Organize uploads directory

4. **Production deployment**
   - Run migration on production database
   - Update production config files
   - Test production environment thoroughly

## Support

For issues or questions:
1. Check error logs: `error_log` in Apache logs
2. Enable PHP error reporting during development
3. Review session variables with var_dump($_SESSION)
