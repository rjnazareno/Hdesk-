-- Add FCM Token Column for Push Notifications
-- Firebase Cloud Messaging Integration
-- Date: November 17, 2025

-- Add fcm_token to employees table
ALTER TABLE `employees` 
ADD COLUMN `fcm_token` VARCHAR(255) NULL AFTER `profile_picture`,
ADD INDEX `idx_fcm_token_employees` (`fcm_token`);

-- Add fcm_token to users table (IT staff/admins)
ALTER TABLE `users` 
ADD COLUMN `fcm_token` VARCHAR(255) NULL,
ADD INDEX `idx_fcm_token_users` (`fcm_token`);

-- Verify columns were added
SELECT 'fcm_token column added to employees table' AS status;
SELECT 'fcm_token column added to users table' AS status;
