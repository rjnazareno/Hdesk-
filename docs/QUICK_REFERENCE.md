# Quick Reference - New Structure

## User Types

| Type | Table | Login Redirect | Access |
|------|-------|----------------|--------|
| IT Staff | `users` | `admin/dashboard.php` | All admin functions |
| Admin | `users` | `admin/dashboard.php` | All admin + settings |
| Employee | `employees` | `customer/dashboard.php` | Own tickets only |

## Session Variables

```php
$_SESSION['user_id']        // ID from users or employees table
$_SESSION['user_type']      // 'user' or 'employee'
$_SESSION['username']       // Username
$_SESSION['full_name']      // Display name
$_SESSION['email']          // Email address
$_SESSION['role']           // it_staff, admin, or employee
$_SESSION['department']     // Department or Company
$_SESSION['logged_in']      // true/false
$_SESSION['last_activity']  // Timestamp for session timeout
```

## URL Structure

### Admin Pages (IT Staff/Admin only)
- `/admin/dashboard.php` - Admin dashboard
- `/admin/tickets.php` - All tickets
- `/admin/view_ticket.php?id=X` - View/manage ticket
- `/admin/customers.php` - Manage employees
- `/admin/categories.php` - Manage categories
- `/admin/export_tickets.php` - Export to Excel

### Customer Pages (Employees only)
- `/customer/dashboard.php` - Employee dashboard
- `/customer/tickets.php` - Employee's tickets
- `/customer/create_ticket.php` - Create new ticket
- `/customer/view_ticket.php?id=X` - View own ticket

### Shared Pages
- `/login.php` - Login page
- `/logout.php` - Logout handler
- `/article.php` - Knowledge base

## Database Tables

### users (IT Staff/Admin)
```sql
id, username, email, password, full_name, 
role, department, phone, is_active, 
created_at, updated_at
```

### employees (Regular Employees)
```sql
id, username, email, personal_email, password, 
fname, lname, company, position, contact, 
official_sched, role, status, profile_picture, 
profile_image, created_at
```

### tickets
```sql
id, ticket_number, title, description, 
category_id, priority, status, 
submitter_id, submitter_type,  # NEW FIELD
assigned_to, attachments, 
created_at, updated_at
```

### ticket_activity
```sql
id, ticket_id, user_id, user_type,  # NEW FIELD
action_type, old_value, new_value, 
comment, created_at
```

## Authentication Flow

```
1. User enters credentials
   ↓
2. login_process.php called
   ↓
3. LoginController->login()
   ↓
4. Auth->login() checks both tables
   ↓
5. Session variables set with user_type
   ↓
6. Redirect based on user_type:
   - 'user' → admin/dashboard.php
   - 'employee' → customer/dashboard.php
```

## Common Code Patterns

### Check if user is IT Staff
```php
$isITStaff = $_SESSION['user_type'] === 'user' && 
             ($_SESSION['role'] === 'it_staff' || $_SESSION['role'] === 'admin');
```

### Restrict page to IT Staff
```php
$auth = new Auth();
$auth->requireLogin();
$auth->requireITStaff();
```

### Restrict page to Employees
```php
$auth = new Auth();
$auth->requireLogin();

if ($_SESSION['user_type'] !== 'employee') {
    redirect('admin/dashboard.php');
}
```

### Create ticket with submitter type
```php
$ticketData = [
    'ticket_number' => $ticketNumber,
    'title' => $title,
    'description' => $description,
    'category_id' => $categoryId,
    'priority' => $priority,
    'status' => 'pending',
    'submitter_id' => $_SESSION['user_id'],
    'submitter_type' => $_SESSION['user_type'] ?? 'employee'
];
```

### Log activity with user type
```php
$activityModel->log([
    'ticket_id' => $ticketId,
    'user_id' => $_SESSION['user_id'],
    'user_type' => $_SESSION['user_type'],
    'action_type' => 'created',
    'new_value' => 'pending',
    'comment' => 'Ticket created'
]);
```

## File Path Patterns

### In admin/ folder
```php
require_once __DIR__ . '/../config/config.php';  // Config
href="../logout.php"                             // Logout
href="../uploads/<?php echo $file; ?>"           // Uploads
```

### In customer/ folder
```php
require_once __DIR__ . '/../config/config.php';  // Config
href="../logout.php"                             // Logout
href="../article.php"                            // Articles
```

## Test Credentials

### Admin Account
- **Username**: admin
- **Password**: password
- **Access**: All admin functions
- **Table**: users

### Employee Account
- **Username**: employee
- **Password**: password
- **Access**: Create/view own tickets
- **Table**: employees

## Quick Commands

### Import Schema
```bash
cd c:\xampp\htdocs\IThelp
mysql -u root ithelp < database\schema_updated.sql
```

### Create Admin User
```sql
INSERT INTO users (username, email, password, full_name, role, is_active) 
VALUES ('admin', 'admin@company.com', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'Admin User', 'admin', 1);
```

### Create Employee User
```sql
INSERT INTO employees (username, email, password, fname, lname, company, status) 
VALUES ('employee', 'employee@company.com', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'John', 'Doe', 'ABC Company', 'active');
```

## Debugging Tips

### Check session variables
```php
echo '<pre>';
print_r($_SESSION);
echo '</pre>';
die();
```

### Check user type after login
```php
error_log("User Type: " . ($_SESSION['user_type'] ?? 'not set'));
error_log("Role: " . ($_SESSION['role'] ?? 'not set'));
```

### Verify table data
```sql
-- Check if user exists in users table
SELECT * FROM users WHERE username = 'admin';

-- Check if employee exists
SELECT * FROM employees WHERE username = 'employee';

-- Check tickets with submitter_type
SELECT id, ticket_number, submitter_id, submitter_type FROM tickets;
```
