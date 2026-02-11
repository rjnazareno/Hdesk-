# Admin Rights Structure - Updated February 12, 2026

## 🎯 New Structure: Role-Based Admin Rights

### **Key Changes:**

The admin rights system now **only shows employees with `role='internal'`** in the admin rights management page. Regular employees (`role='employee'`) are **excluded** and **cannot have admin rights**.

---

## 📋 Employee Role System

### Two Employee Types:

1. **`role='employee'`** - Regular employees
   - ❌ Cannot access admin panel
   - ❌ Cannot have admin rights
   - ✅ Can submit tickets
   - ✅ Can view their own tickets

2. **`role='internal'`** - Internal staff (eligible for admin rights)
   - ✅ Can be assigned admin rights
   - ✅ Shows in admin rights management page
   - ✅ Can be promoted to IT/HR/Super Admin

---

## 🔐 Admin Rights Levels

For employees with **`role='internal'`** only:

### 1. **No Admin Rights** (NULL)
- Default internal employee
- Can see only customer dashboard
- Standard ticket submission

### 2. **IT Admin** (`admin_rights_hdesk='it'`)
- Full ticket management
- Can assign tickets
- View all tickets and reports
- Manage categories and SLA

### 3. **HR Admin** (`admin_rights_hdesk='hr'`)
- View employee data
- Manage employee records
- HR-specific reports
- Limited ticket access

### 4. **Super Admin** (`admin_rights_hdesk='superadmin'`)
- Everything IT and HR can do
- Manage user accounts
- Assign admin rights to others
- System-wide configuration
- **Protected** - Cannot be retracted

---

## 📊 Database Structure

```sql
-- employees table
id | username | role      | admin_rights_hdesk | status
---|----------|-----------|--------------------|---------
1  | john.doe | employee  | NULL               | active   ❌ Not shown in admin rights page
2  | kiras001 | internal  | superadmin         | active   ✅ Super Admin (protected)
3  | jane.smith | internal | it                | active   ✅ IT Admin
4  | bob.jones | internal  | NULL               | active   ✅ Can assign rights
5  | alice.wu | employee  | NULL               | active   ❌ Not shown in admin rights page
```

---

## 🔄 How It Works Now

### **Admin Rights Management Page** (`admin/manage_employee_rights.php`):

#### **Current Admins Tab:**
- Shows: All `role='internal'` employees who have `admin_rights_hdesk` set
- Can: Edit IT/HR rights or retract them (except superadmin)
- Protected: Super Admin rights cannot be removed

#### **Assign Rights Tab:**
- Shows: Only `role='internal'` employees WITHOUT admin rights
- Can: Assign IT/HR/Super Admin rights
- Hidden: All `role='employee'` accounts (never shown)

#### **Empty States:**
- If no internal employees exist → shows empty message
- If all internal employees have rights → shows "All done!" message
- If no admins → shows "No employees with admin rights yet"

---

## 🆕 Code Changes Made

### 1. **New Model Method** - `models/Employee.php`
```php
public function getByRole($role, $status = 'active') {
    // Fetches employees filtered by role (internal/employee)
}
```

### 2. **Updated Admin Rights Page** - `admin/manage_employee_rights.php`
```php
// OLD: Fetched ALL employees
$employees = $employeeModel->getAll('active');

// NEW: Only fetch internal employees
$internalEmployees = $employeeModel->getByRole('internal', 'active');
```

### 3. **UI Updates:**
- Stats card: "Total Employees" → "Internal Employees"
- Headers clearly state "role='internal' only"
- Table headers: "Employee" → "Internal Employee"
- Empty state messages explain role requirements

---

## ✅ Benefits of This Structure

1. **Clearer Separation:**
   - Regular employees (`employee`) vs Internal staff (`internal`)
   - Admin rights only for internal staff

2. **Less Confusion:**
   - Admin rights page doesn't show hundreds of regular employees
   - Only shows staff eligible for admin access

3. **Better Security:**
   - Regular employees cannot accidentally get admin rights
   - Clear distinction between customer-facing and internal staff

4. **Easier Management:**
   - Smaller list to manage
   - Focus on internal team only

---

## 🎯 Best Practices

### **For New Employees:**

1. **Regular Employee (Customer Service, External Staff):**
   ```sql
   INSERT INTO employees (username, fname, lname, role, ...) 
   VALUES ('john.doe', 'John', 'Doe', 'employee', ...);
   ```
   - Will NOT appear in admin rights page
   - Customer dashboard access only

2. **Internal Employee (IT Staff, HR, Management):**
   ```sql
   INSERT INTO employees (username, fname, lname, role, ...) 
   VALUES ('jane.it', 'Jane', 'Smith', 'internal', ...);
   ```
   - Will appear in admin rights page
   - Can be assigned IT/HR/Super Admin rights

### **Promoting Employees:**

To make an employee eligible for admin rights:
```sql
UPDATE employees 
SET role = 'internal' 
WHERE username = 'employee_username';
```

---

## 🔍 Troubleshooting

### **Q: Employee not showing in admin rights page?**
**A:** Check their `role` field:
```sql
SELECT username, role, admin_rights_hdesk 
FROM employees 
WHERE username = 'username_here';
```
- If `role='employee'` → Change to `'internal'` to make them eligible

### **Q: How to see all internal employees?**
**A:** Query:
```sql
SELECT id, username, CONCAT(fname, ' ', lname) as name, role, admin_rights_hdesk 
FROM employees 
WHERE role = 'internal' AND status = 'active'
ORDER BY fname, lname;
```

### **Q: Can I assign admin rights to regular employees?**
**A:** No. The system filters them out. You must:
1. Change their `role` to `'internal'` first
2. Then assign admin rights

---

## 📈 Statistics

The stats cards now reflect **internal employees only**:

- **Internal Employees**: Count of `role='internal'` employees
- **Super Admins**: Count with `admin_rights_hdesk='superadmin'`
- **IT Admins**: Count with `admin_rights_hdesk='it'`
- **HR Admins**: Count with `admin_rights_hdesk='hr'`

Regular employees (`role='employee'`) are excluded from all counts.

---

## 🚀 Next Steps

1. **Upload files** to production:
   - `models/Employee.php`
   - `admin/manage_employee_rights.php`

2. **Test the page**:
   - Visit: `admin/manage_employee_rights.php`
   - Verify only internal employees show
   - Try assigning/retracting rights

3. **Audit your database**:
   ```sql
   -- See which employees should be internal
   SELECT username, CONCAT(fname, ' ', lname) as name, role, position, company
   FROM employees 
   WHERE status = 'active'
   ORDER BY role, fname;
   ```

4. **Update roles if needed**:
   ```sql
   -- Change specific employees to internal
   UPDATE employees SET role = 'internal' 
   WHERE username IN ('it_staff1', 'hr_manager', 'supervisor');
   ```

---

## 📝 Summary

✅ Admin rights now filtered by `role='internal'`  
✅ Regular employees (`role='employee'`) excluded  
✅ Cleaner, focused admin rights management  
✅ Better security and separation of concerns  
✅ Empty states for better UX  

**All changes committed and ready to deploy!**
