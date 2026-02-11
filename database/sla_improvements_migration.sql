-- ============================================
-- SLA Improvements Migration Script
-- Date: February 5, 2026
-- Features: Dual timers, pool rename, 3-tier priority, admin control
-- ============================================

-- Step 1: Update SLA Policies for 3-tier priority (remove urgent, keep low/medium/high)
-- First, update existing tickets with 'urgent' priority to 'high'
UPDATE tickets SET priority = 'high' WHERE priority = 'urgent';

-- Update SLA policies: Merge urgent into high
-- Keep the fastest SLA times for high priority
UPDATE sla_policies 
SET response_time = 30, resolution_time = 480, is_business_hours = 0
WHERE priority = 'high';

-- Deactivate urgent policy
UPDATE sla_policies SET is_active = 0 WHERE priority = 'urgent';

-- Step 2: Add admin_priority column to track if priority was set by admin
ALTER TABLE tickets 
ADD COLUMN IF NOT EXISTS admin_priority TINYINT(1) DEFAULT 0 COMMENT '1 if priority was set/locked by admin'
AFTER priority;

-- Step 3: Add response_target_hours for 24-hour first response (configurable)
-- This is already handled by sla_policies.response_time, but let's add default display
-- Ensure sla_tracking has all necessary fields for dual timer display

-- Step 4: Add indexes for pool filtering performance
CREATE INDEX IF NOT EXISTS idx_tickets_pool ON tickets (grabbed_by, status, created_at);
CREATE INDEX IF NOT EXISTS idx_tickets_category_status ON tickets (category_id, status);

-- Step 5: Update SLA policies for new 24-hour first response target  
-- Update response times to 24 hours (1440 minutes) for standard first response
UPDATE sla_policies SET response_time = 1440 WHERE priority = 'low' AND is_active = 1;
UPDATE sla_policies SET response_time = 1440 WHERE priority = 'medium' AND is_active = 1;
UPDATE sla_policies SET response_time = 240 WHERE priority = 'high' AND is_active = 1;

-- Priority-based resolution times remain:
-- Low: 48 hours (2880 min)
-- Medium: 24 hours (1440 min)
-- High: 8 hours (480 min)

-- Step 6: Add date filters support (no schema change needed, just optimized indexes)
CREATE INDEX IF NOT EXISTS idx_tickets_created_range ON tickets (created_at, status);

-- Step 7: Verify SLA tracking table has all needed columns for dual timer
-- (They should already exist from previous migration)

-- Display updated policies
SELECT priority, response_time as response_min, resolution_time as resolution_min, 
       is_business_hours, is_active 
FROM sla_policies 
ORDER BY FIELD(priority, 'high', 'medium', 'low');
