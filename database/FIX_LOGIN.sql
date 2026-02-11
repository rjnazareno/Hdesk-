-- ============================================
-- FIX LOGIN ISSUES
-- Date: February 12, 2026
-- ============================================

-- Step 1: Check current status
SELECT id, username, email, password, status, role 
FROM employees 
WHERE username = 'kiras001' OR email = 'rmanago@gmail.com';

-- Step 2: Fix status if needed (must be 'active')
UPDATE employees 
SET status = 'active' 
WHERE username = 'kiras001' OR email = 'rmanago@gmail.com';

-- Step 3: Ensure password is set correctly (no extra spaces)
UPDATE employees 
SET password = TRIM('123456')
WHERE username = 'kiras001' OR email = 'rmanago@gmail.com';

-- Step 4: Verify the fix
SELECT 
    id,
    username,
    email,
    password,
    LENGTH(password) as password_length,
    status,
    role,
    admin_rights_hdesk
FROM employees 
WHERE username = 'kiras001' OR email = 'rmanago@gmail.com';

-- ============================================
-- Expected result:
-- username: kiras001
-- password: 123456 (length: 6)
-- status: active
-- ============================================
