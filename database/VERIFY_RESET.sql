-- ============================================
-- QUICK VERIFICATION QUERY
-- Run this AFTER CLEAN_RESET_TICKETS.sql
-- ============================================
-- Copy and paste this in phpMyAdmin SQL tab
-- to verify the reset was successful
-- ============================================

-- Check main tables
SELECT 
    'tickets' as table_name, 
    COUNT(*) as record_count,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ GOOD - Deleted'
        ELSE '✗ ERROR - Still has records!'
    END as status
FROM tickets

UNION ALL

SELECT 
    'ticket_activity' as table_name, 
    COUNT(*) as record_count,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ GOOD - Deleted'
        ELSE '✗ ERROR - Still has records!'
    END as status
FROM ticket_activity

UNION ALL

SELECT 
    'notifications' as table_name, 
    COUNT(*) as record_count,
    CASE 
        WHEN COUNT(*) = 0 THEN '✓ GOOD - Deleted'
        ELSE '✗ ERROR - Still has records!'
    END as status
FROM notifications

UNION ALL

SELECT 
    'users' as table_name, 
    COUNT(*) as record_count,
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ GOOD - Preserved'
        ELSE '✗ ERROR - Users deleted!'
    END as status
FROM users

UNION ALL

SELECT 
    'categories' as table_name, 
    COUNT(*) as record_count,
    CASE 
        WHEN COUNT(*) > 0 THEN '✓ GOOD - Preserved'
        ELSE '✗ ERROR - Categories deleted!'
    END as status
FROM categories;

-- ============================================
-- Expected Results:
-- ============================================
-- tickets: 0 records - ✓ GOOD - Deleted
-- ticket_activity: 0 records - ✓ GOOD - Deleted
-- notifications: 0 records - ✓ GOOD - Deleted
-- users: >0 records - ✓ GOOD - Preserved
-- categories: >0 records - ✓ GOOD - Preserved
-- ============================================
