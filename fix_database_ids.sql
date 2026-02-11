-- Direct SQL Commands to Fix Employee ID Issues
-- Run these commands directly in your database management tool

-- Step 1: Check current state
SELECT 
    COUNT(*) as total_employees,
    SUM(CASE WHEN id = 0 THEN 1 ELSE 0 END) as zero_ids,
    SUM(CASE WHEN id > 0 THEN 1 ELSE 0 END) as proper_ids,
    MIN(id) as min_id,
    MAX(id) as max_id
FROM employees;

-- Step 2: Find duplicates by username
SELECT username, COUNT(*) as count 
FROM employees 
GROUP BY username 
HAVING COUNT(*) > 1 
ORDER BY count DESC;

-- Step 3: Show employees with id = 0 (the problem ones)
SELECT id, username, fname, lname, employee_id 
FROM employees 
WHERE id = 0 
ORDER BY username 
LIMIT 20;

-- Step 4: BACKUP FIRST! (Important)
-- CREATE TABLE employees_backup AS SELECT * FROM employees;

-- Step 5: Fix ID = 0 issue by reassigning proper IDs
-- Method A: Delete duplicates with id=0 if you have proper records
-- (Use this if you see employees exist with both id=0 AND proper IDs)

-- First, identify employees that exist with both id=0 and proper ID:
SELECT e1.username, e1.id as zero_id, e2.id as proper_id
FROM employees e1 
INNER JOIN employees e2 ON e1.username = e2.username 
WHERE e1.id = 0 AND e2.id > 0;

-- If you have duplicates, remove the id=0 ones:
-- DELETE FROM employees WHERE id = 0 AND username IN (
--     SELECT username FROM (
--         SELECT e1.username
--         FROM employees e1 
--         INNER JOIN employees e2 ON e1.username = e2.username 
--         WHERE e1.id = 0 AND e2.id > 0
--     ) as subquery
-- );

-- Method B: Reassign proper IDs to id=0 employees
-- (Use this if employees ONLY exist with id=0)

-- Get the current maximum ID
SELECT @max_id := COALESCE(MAX(id), 0) FROM employees WHERE id > 0;

-- Create a temporary table with new IDs
CREATE TEMPORARY TABLE temp_id_fixes AS
SELECT 
    username,
    fname,
    lname,
    employee_id,
    ROW_NUMBER() OVER (ORDER BY username) + @max_id as new_id
FROM employees 
WHERE id = 0;

-- Show what will be updated
SELECT * FROM temp_id_fixes;

-- Update employees with new IDs (one by one to avoid conflicts)
-- You'll need to run this for each employee or use a procedure
-- UPDATE employees e
-- INNER JOIN temp_id_fixes t ON e.username = t.username
-- SET e.id = t.new_id
-- WHERE e.id = 0;

-- Step 6: Reset AUTO_INCREMENT
SELECT @new_auto_increment := MAX(id) + 1 FROM employees;
SET @sql = CONCAT('ALTER TABLE employees AUTO_INCREMENT = ', @new_auto_increment);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Step 7: Verify the fix
SELECT 
    COUNT(*) as total_employees,
    SUM(CASE WHEN id = 0 THEN 1 ELSE 0 END) as zero_ids,
    SUM(CASE WHEN id > 0 THEN 1 ELSE 0 END) as proper_ids,
    MIN(id) as min_id,
    MAX(id) as max_id
FROM employees;

-- Step 8: Check for remaining duplicates
SELECT username, COUNT(*) as count 
FROM employees 
GROUP BY username 
HAVING COUNT(*) > 1;

-- Step 9: Show sample of fixed employees
SELECT id, username, fname, lname, employee_id 
FROM employees 
ORDER BY id 
LIMIT 20;

-- IMPORTANT NOTES:
-- 1. ALWAYS backup your table first: CREATE TABLE employees_backup AS SELECT * FROM employees;
-- 2. Test on a small subset first
-- 3. The automated PHP script (fix_database_ids.php) handles this more safely
-- 4. After fixing, test login and profile pages to ensure they work correctly

-- Quick fix for immediate testing (reassign first 10 employees with id=0):
/*
SET @counter = (SELECT MAX(id) FROM employees WHERE id > 0) + 1;

UPDATE employees 
SET id = (@counter := @counter + 1) 
WHERE id = 0 
ORDER BY username 
LIMIT 10;
*/