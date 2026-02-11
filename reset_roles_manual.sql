-- Manual SQL Commands to Reset All Employee Roles

-- Option 1: Reset ALL employees to regular employee (including superadmin)
UPDATE employees 
SET admin_rights_hdesk = NULL, 
    role = 'employee' 
WHERE 1=1;

-- Option 2: Reset all EXCEPT superadmin (recommended)
UPDATE employees 
SET admin_rights_hdesk = NULL, 
    role = 'employee' 
WHERE admin_rights_hdesk != 'superadmin' 
   OR admin_rights_hdesk IS NULL;

-- Option 3: Reset only IT and HR admins, keep superadmin
UPDATE employees 
SET admin_rights_hdesk = NULL, 
    role = 'employee' 
WHERE admin_rights_hdesk IN ('it', 'hr');

-- Check results after running:
SELECT 
    admin_rights_hdesk, 
    role, 
    COUNT(*) as count 
FROM employees 
GROUP BY admin_rights_hdesk, role 
ORDER BY count DESC;

-- Show first 10 employees after reset:
SELECT id, username, fname, lname, admin_rights_hdesk, role 
FROM employees 
ORDER BY id 
LIMIT 10;

-- Quick verification - should show mostly NULL admin rights:
SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN admin_rights_hdesk IS NULL THEN 1 ELSE 0 END) as regular_employees,
    SUM(CASE WHEN admin_rights_hdesk = 'superadmin' THEN 1 ELSE 0 END) as superadmins,
    SUM(CASE WHEN admin_rights_hdesk = 'hr' THEN 1 ELSE 0 END) as hr_admins,
    SUM(CASE WHEN admin_rights_hdesk = 'it' THEN 1 ELSE 0 END) as it_admins
FROM employees;