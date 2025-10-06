-- Add seen functionality to ticket_responses table
-- Run this to add the seen tracking feature

USE ticketing_system;

-- Add columns for tracking message seen status
ALTER TABLE ticket_responses 
ADD COLUMN is_seen BOOLEAN DEFAULT FALSE AFTER is_internal,
ADD COLUMN seen_at TIMESTAMP NULL AFTER is_seen,
ADD COLUMN seen_by INT NULL AFTER seen_at,
ADD COLUMN user_type ENUM('employee', 'it_staff') NOT NULL DEFAULT 'it_staff' AFTER responder_id;

-- Add index for better query performance
CREATE INDEX idx_seen_status ON ticket_responses(ticket_id, is_seen);
CREATE INDEX idx_user_type ON ticket_responses(user_type);

-- Update existing responses to set user_type based on responder_id
-- This is a one-time migration for existing data
UPDATE ticket_responses tr
SET user_type = 'it_staff'
WHERE EXISTS (SELECT 1 FROM it_staff WHERE staff_id = tr.responder_id);

UPDATE ticket_responses tr
SET user_type = 'employee'
WHERE EXISTS (SELECT 1 FROM employees WHERE id = tr.responder_id);

SELECT 'Seen functionality added successfully!' as Status;
