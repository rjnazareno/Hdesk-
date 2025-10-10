-- Fix notifications table to support both users and employees
-- Add employee_id column and modify structure

-- Add employee_id column (nullable, FK to employees table)
ALTER TABLE notifications 
ADD COLUMN employee_id INT(11) NULL AFTER user_id,
ADD CONSTRAINT fk_notification_employee 
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE;

-- Add index for faster employee queries
CREATE INDEX idx_notifications_employee_id ON notifications(employee_id);

-- Add check constraint to ensure either user_id or employee_id is set (not both)
-- Note: MySQL 8.0.16+ supports CHECK constraints
ALTER TABLE notifications 
ADD CONSTRAINT chk_notification_recipient 
    CHECK (
        (user_id IS NOT NULL AND employee_id IS NULL) OR 
        (user_id IS NULL AND employee_id IS NOT NULL)
    );

-- Show updated structure
DESCRIBE notifications;

SELECT 'Notifications table updated successfully' as status;
