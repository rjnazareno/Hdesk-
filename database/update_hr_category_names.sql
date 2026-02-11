-- =====================================================
-- Rename HR Categories
-- Date: January 28, 2026
-- Description: Rename "Timekeeping" to "Timekeeping concerns" 
--              and "Leave Request" to "Leave concerns"
-- =====================================================

-- Update Timekeeping category
UPDATE `categories` 
SET `name` = 'Timekeeping concerns'
WHERE `name` = 'Timekeeping';

-- Update Leave Request category
UPDATE `categories` 
SET `name` = 'Leave concerns'
WHERE `name` = 'Leave Request';

-- Verify changes
SELECT id, name, description 
FROM categories 
WHERE name IN ('Timekeeping concerns', 'Leave concerns');
