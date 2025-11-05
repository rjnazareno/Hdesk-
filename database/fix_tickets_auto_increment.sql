-- Fix Tickets Table - Add AUTO_INCREMENT and PRIMARY KEY
-- Run this query in your production database

-- Step 1: Make sure id column is PRIMARY KEY with AUTO_INCREMENT
ALTER TABLE `tickets` 
  MODIFY COLUMN `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY;

-- Verify the fix
DESCRIBE tickets;

-- This will show the table structure. 
-- Look for the 'id' row - the 'Extra' column should show 'auto_increment'
-- and the 'Key' column should show 'PRI'
