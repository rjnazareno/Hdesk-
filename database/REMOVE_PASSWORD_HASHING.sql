-- ============================================
-- REMOVE PASSWORD HASHING
-- Date: February 12, 2026
-- Purpose: Update passwords to plain text format
-- ============================================
-- ⚠️ WARNING: This removes security - use only for development/testing!
-- ============================================

-- Fix the employee password AND status (status must be 'active' to login!)
UPDATE employees 
SET password = '123456',
    status = 'active'
WHERE username = 'kiras001' OR email = 'rmanago@gmail.com';

-- If you need to reset other employee passwords to plain text:
-- Uncomment and modify as needed:

-- UPDATE employees SET password = 'admin123' WHERE username = 'john.doe';
-- UPDATE employees SET password = 'admin123' WHERE username = 'jane.smith';

-- For users table (IT staff):
-- UPDATE users SET password = 'admin123' WHERE username = 'admin';
-- UPDATE users SET password = 'admin123' WHERE username = 'mahfuzul';

-- ============================================
-- Verify the changes
-- ============================================
SELECT 'Password system updated to plain text' AS status;
SELECT username, email, password FROM employees WHERE username = 'kiras001';

-- ============================================
-- SECURITY NOTE
-- ============================================
-- Plain text passwords are NOT secure!
-- Only use this for:
--   • Development environments
--   • Testing purposes
--   • Internal systems with network security
--
-- For production systems, always use password hashing!
-- ============================================
