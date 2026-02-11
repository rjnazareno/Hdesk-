-- ============================================
-- OPTIONAL: Simplify Ticket Assignment System
-- Date: February 11, 2026
-- ============================================
-- This script removes the complexity of dual-user system
-- Run this AFTER cleaning tickets if you want to simplify
-- ============================================

-- WARNING: Run this on a TEST environment first!
-- This makes structural changes to your database

-- ============================================
-- STEP 1: Check current structure
-- ============================================

SELECT 
    'Before Migration' as status,
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'tickets'
AND COLUMN_NAME IN ('submitter_type', 'assignee_type', 'grabbed_by', 'grabbed_by_type')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- ============================================
-- STEP 2: Backup current ticket structure
-- ============================================

CREATE TABLE IF NOT EXISTS tickets_structure_backup LIKE tickets;

-- ============================================
-- STEP 3: Remove complex dual-user fields
-- ============================================

-- Remove submitter_type (if exists)
SET @sql1 = (SELECT IF(
    EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'tickets' 
        AND COLUMN_NAME = 'submitter_type'),
    'ALTER TABLE tickets DROP COLUMN submitter_type',
    'SELECT "submitter_type column does not exist" AS info'
));
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

-- Remove assignee_type (if exists)
SET @sql2 = (SELECT IF(
    EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'tickets' 
        AND COLUMN_NAME = 'assignee_type'),
    'ALTER TABLE tickets DROP COLUMN assignee_type',
    'SELECT "assignee_type column does not exist" AS info'
));
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Remove grabbed_by_type (if exists)
SET @sql3 = (SELECT IF(
    EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'tickets' 
        AND COLUMN_NAME = 'grabbed_by_type'),
    'ALTER TABLE tickets DROP COLUMN grabbed_by_type',
    'SELECT "grabbed_by_type column does not exist" AS info'
));
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- ============================================
-- STEP 4: Simplify foreign key relationships
-- ============================================

-- Drop existing foreign keys if they exist
SET FOREIGN_KEY_CHECKS = 0;

-- Drop old foreign key on submitter_id (if exists)
SET @sql4 = (SELECT CONCAT('ALTER TABLE tickets DROP FOREIGN KEY ', CONSTRAINT_NAME)
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tickets'
    AND COLUMN_NAME = 'submitter_id'
    AND CONSTRAINT_NAME LIKE 'fk_%' OR CONSTRAINT_NAME LIKE 'tickets_ibfk_%'
    LIMIT 1
);

-- Execute only if foreign key exists
SET @sql4 = IFNULL(@sql4, 'SELECT "No FK on submitter_id" AS info');
PREPARE stmt4 FROM @sql4;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

-- Add new foreign key to users table only
ALTER TABLE tickets 
    ADD CONSTRAINT fk_tickets_submitter 
    FOREIGN KEY (submitter_id) REFERENCES users(id) ON DELETE CASCADE;

-- Ensure assigned_to references users table
ALTER TABLE tickets 
    DROP FOREIGN KEY IF EXISTS fk_tickets_assigned_to;
    
ALTER TABLE tickets 
    ADD CONSTRAINT fk_tickets_assigned_to 
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL;

-- Ensure grabbed_by references users table (if column exists)
SET @sql5 = (SELECT IF(
    EXISTS(SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'tickets' 
        AND COLUMN_NAME = 'grabbed_by'),
    'ALTER TABLE tickets ADD CONSTRAINT fk_tickets_grabbed_by FOREIGN KEY (grabbed_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "grabbed_by column does not exist" AS info'
));
PREPARE stmt5 FROM @sql5;
EXECUTE stmt5;
DEALLOCATE PREPARE stmt5;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- STEP 5: Add helpful comments
-- ============================================

ALTER TABLE tickets 
    MODIFY COLUMN submitter_id INT(11) NOT NULL COMMENT 'User ID from users table';

ALTER TABLE tickets 
    MODIFY COLUMN assigned_to INT(11) DEFAULT NULL COMMENT 'User ID from users table';

-- ============================================
-- STEP 6: Verify simplified structure
-- ============================================

SELECT 
    'After Migration' as status,
    TABLE_NAME,
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'tickets'
AND COLUMN_NAME IN ('submitter_id', 'assigned_to', 'grabbed_by')
ORDER BY TABLE_NAME, ORDINAL_POSITION;

-- ============================================
-- STEP 7: Show foreign key relationships
-- ============================================

SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'tickets'
AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY CONSTRAINT_NAME;

-- ============================================
-- COMPLETED: Simplified Structure
-- ============================================
-- The tickets table now uses ONLY the users table
-- No more submitter_type or assignee_type confusion
-- All assignments reference users.id directly
-- ============================================

SELECT 'Migration completed successfully!' AS status;
SELECT 'Next steps:' AS info;
SELECT '1. Update Ticket.php model to remove submitter_type logic' AS step;
SELECT '2. Sync Harley employees INTO users table instead of separate table' AS step;
SELECT '3. Update ticket creation forms to use current user ID only' AS step;
SELECT '4. Test ticket creation and assignment' AS step;
