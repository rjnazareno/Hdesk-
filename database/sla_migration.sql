-- ============================================
-- SLA (Service Level Agreement) Implementation
-- Database Migration Script
-- Date: October 16, 2025
-- ============================================

-- Step 1: Create SLA Policies Table
-- ============================================
CREATE TABLE IF NOT EXISTS sla_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL,
    response_time INT NOT NULL COMMENT 'Minutes until first response required',
    resolution_time INT NOT NULL COMMENT 'Minutes until resolution required',
    is_business_hours BOOLEAN DEFAULT 1 COMMENT '1=business hours only, 0=24/7 calculation',
    is_active BOOLEAN DEFAULT 1 COMMENT 'Enable/disable this policy',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_priority (priority),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 2: Create SLA Tracking Table
-- ============================================
CREATE TABLE IF NOT EXISTS sla_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    sla_policy_id INT NOT NULL,
    
    -- Response SLA Fields
    response_due_at DATETIME NOT NULL COMMENT 'When first response is due',
    first_response_at DATETIME NULL COMMENT 'When IT staff first responded',
    response_sla_status ENUM('met', 'at_risk', 'breached', 'pending') DEFAULT 'pending',
    response_time_minutes INT NULL COMMENT 'Actual response time in minutes',
    
    -- Resolution SLA Fields
    resolution_due_at DATETIME NOT NULL COMMENT 'When resolution is due',
    resolved_at DATETIME NULL COMMENT 'When ticket was resolved',
    resolution_sla_status ENUM('met', 'at_risk', 'breached', 'pending') DEFAULT 'pending',
    resolution_time_minutes INT NULL COMMENT 'Actual resolution time in minutes',
    
    -- Pause Tracking (for waiting on customer)
    is_paused BOOLEAN DEFAULT 0 COMMENT 'Is SLA currently paused',
    paused_at DATETIME NULL COMMENT 'When SLA was paused',
    pause_reason VARCHAR(255) NULL COMMENT 'Why SLA was paused',
    total_pause_minutes INT DEFAULT 0 COMMENT 'Total time SLA has been paused',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign Keys
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (sla_policy_id) REFERENCES sla_policies(id),
    
    -- Indexes for performance
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_response_status (response_sla_status),
    INDEX idx_resolution_status (resolution_sla_status),
    INDEX idx_response_due (response_due_at),
    INDEX idx_resolution_due (resolution_due_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 3: Create SLA Breaches Audit Log
-- ============================================
CREATE TABLE IF NOT EXISTS sla_breaches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    sla_tracking_id INT NOT NULL,
    breach_type ENUM('response', 'resolution') NOT NULL,
    target_time DATETIME NOT NULL COMMENT 'When it should have been completed',
    actual_time DATETIME NOT NULL COMMENT 'When it was actually completed',
    delay_minutes INT NOT NULL COMMENT 'How many minutes late',
    notified BOOLEAN DEFAULT 0 COMMENT 'Has notification been sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (sla_tracking_id) REFERENCES sla_tracking(id) ON DELETE CASCADE,
    
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_breach_type (breach_type),
    INDEX idx_notified (notified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Step 4: Alter Tickets Table
-- ============================================
ALTER TABLE tickets 
ADD COLUMN sla_status ENUM('met', 'at_risk', 'breached', 'pending', 'none') DEFAULT 'pending' 
COMMENT 'Overall SLA status for quick filtering'
AFTER priority;

ALTER TABLE tickets 
ADD INDEX idx_sla_status (sla_status);

-- Step 5: Insert Default SLA Policies
-- ============================================
INSERT INTO sla_policies (priority, response_time, resolution_time, is_business_hours, is_active) VALUES
('urgent',  15,   240,  0, 1),  -- 15 min response, 4 hours resolution (24/7)
('high',    30,   480,  1, 1),  -- 30 min response, 8 hours resolution (business hours)
('medium',  120,  1440, 1, 1),  -- 2 hours response, 24 hours resolution (business hours)
('low',     480,  2880, 1, 1);  -- 8 hours response, 48 hours resolution (business hours)

-- Step 6: Create SLA calculation view (optional, for reporting)
-- ============================================
CREATE OR REPLACE VIEW v_sla_summary AS
SELECT 
    t.id as ticket_id,
    t.ticket_number,
    t.title,
    t.priority,
    t.status as ticket_status,
    t.sla_status,
    st.response_sla_status,
    st.resolution_sla_status,
    st.response_due_at,
    st.resolution_due_at,
    st.first_response_at,
    st.resolved_at,
    st.response_time_minutes,
    st.resolution_time_minutes,
    st.is_paused,
    sp.response_time as target_response_minutes,
    sp.resolution_time as target_resolution_minutes,
    sp.is_business_hours,
    -- Calculate remaining time
    CASE 
        WHEN st.resolved_at IS NOT NULL THEN 0
        WHEN st.is_paused = 1 THEN TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at)
        ELSE TIMESTAMPDIFF(MINUTE, NOW(), st.resolution_due_at)
    END as minutes_remaining,
    -- Calculate elapsed percentage
    CASE 
        WHEN st.resolved_at IS NOT NULL THEN 100
        ELSE ROUND((TIMESTAMPDIFF(MINUTE, t.created_at, NOW()) / sp.resolution_time) * 100, 2)
    END as elapsed_percentage
FROM tickets t
LEFT JOIN sla_tracking st ON t.id = st.ticket_id
LEFT JOIN sla_policies sp ON st.sla_policy_id = sp.id
WHERE t.status NOT IN ('closed');

-- ============================================
-- Verification Queries
-- ============================================

-- Check if tables were created successfully
SELECT 'Tables created successfully' as status;
SELECT TABLE_NAME, TABLE_ROWS 
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
  AND TABLE_NAME IN ('sla_policies', 'sla_tracking', 'sla_breaches');

-- Check default SLA policies
SELECT 'Default SLA Policies:' as info;
SELECT 
    id,
    priority,
    CONCAT(response_time, ' minutes') as response_time,
    CONCAT(resolution_time, ' minutes') as resolution_time,
    CASE WHEN is_business_hours = 1 THEN 'Business Hours' ELSE '24/7' END as calculation_mode,
    CASE WHEN is_active = 1 THEN 'Active' ELSE 'Inactive' END as status
FROM sla_policies
ORDER BY 
    FIELD(priority, 'urgent', 'high', 'medium', 'low');

-- Check tickets table alteration
SELECT 'Tickets table updated:' as info;
SHOW COLUMNS FROM tickets LIKE 'sla_status';

-- ============================================
-- Rollback Script (if needed)
-- ============================================
/*
-- Uncomment to rollback changes:

DROP VIEW IF EXISTS v_sla_summary;
DROP TABLE IF EXISTS sla_breaches;
DROP TABLE IF EXISTS sla_tracking;
DROP TABLE IF EXISTS sla_policies;
ALTER TABLE tickets DROP COLUMN IF EXISTS sla_status;
*/

-- ============================================
-- End of Migration
-- ============================================
