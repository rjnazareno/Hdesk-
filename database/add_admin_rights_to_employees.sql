-- Add admin_rights_hdesk column to employees table
-- Allows assigning IT, HR, or Super Admin roles to employees

ALTER TABLE employees 
ADD COLUMN admin_rights_hdesk ENUM('it', 'hr', 'superadmin') NULL DEFAULT NULL 
COMMENT 'HelpDesk admin rights: it, hr, superadmin, or null for regular employee'
AFTER role;

-- Add index for faster queries
CREATE INDEX idx_admin_rights ON employees(admin_rights_hdesk);

-- Examples of how to assign roles:
-- UPDATE employees SET admin_rights_hdesk = 'superadmin' WHERE id = 1;
-- UPDATE employees SET admin_rights_hdesk = 'it' WHERE company = 'IT';
-- UPDATE employees SET admin_rights_hdesk = 'hr' WHERE company = 'Marketing';
-- UPDATE employees SET admin_rights_hdesk = NULL WHERE id = 5; -- Remove admin rights
