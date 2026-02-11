-- ============================================
-- SLA Update Migration - February 10, 2026
-- Purpose: Align categories & SLA display with latest SLA guide
-- 
-- Changes:
-- 1. Rename HR Salary Dispute subcategories
-- 2. Add "Leave Assistance" subcategory under Leave concerns
-- 3. SLA display text updated to show ranges (actual targets unchanged)
--    - MEDIUM resolution: "2-3 days" (target remains 3 days / 4320 min)
--    - LOW resolution: "3-5 days" (target remains 5 days / 7200 min)
-- ============================================

-- =====================================================
-- 1. RENAME SALARY DISPUTE SUBCATEGORIES
-- Old: "Payslip Dispute (a day before cutoff)" → New: "Draft Payslip"
-- Old: "Payslip Dispute (after cutoff)" → New: "Payslip Disputes"
-- =====================================================

SET @dept_hr = (SELECT id FROM departments WHERE code = 'HR');
SET @cat_salary = (SELECT id FROM categories WHERE name = 'Salary Dispute' AND department_id = @dept_hr LIMIT 1);

-- Rename "Payslip Dispute (a day before cutoff)" → "Draft Payslip"
UPDATE categories 
SET name = 'Draft Payslip',
    description = 'Urgent draft payslip request'
WHERE name = 'Payslip Dispute (a day before cutoff)' 
  AND parent_id = @cat_salary;

-- Rename "Payslip Dispute (after cutoff)" → "Payslip Disputes"
UPDATE categories 
SET name = 'Payslip Disputes',
    description = 'General payslip dispute inquiries'
WHERE name = 'Payslip Dispute (after cutoff)' 
  AND parent_id = @cat_salary;

-- =====================================================
-- 2. ADD "Leave Assistance" SUBCATEGORY
-- Under Leave concerns → Priority: LOW
-- =====================================================

SET @cat_leave = (SELECT id FROM categories WHERE name = 'Leave concerns' AND department_id = @dept_hr LIMIT 1);

-- Add Leave Assistance (only if it doesn't already exist)
INSERT INTO categories (department_id, parent_id, name, description, icon, color, sort_order)
SELECT @dept_hr, @cat_leave, 'Leave Assistance', 'Assistance with leave applications and processes', 'hands-helping', '#8B5CF6', 3
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM categories WHERE name = 'Leave Assistance' AND parent_id = @cat_leave
);

-- Map Leave Assistance → LOW priority
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Leave Assistance' AND parent_id = @cat_leave LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- =====================================================
-- 3. VERIFY EXISTING PRIORITY MAPPINGS MATCH LATEST GUIDE
-- (No priority changes needed - all match the spreadsheet)
-- =====================================================

-- Verify Draft Payslip still mapped to HIGH
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'high' FROM categories WHERE name = 'Draft Payslip' AND parent_id = @cat_salary LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'high';

-- Verify Payslip Disputes still mapped to LOW
INSERT INTO category_priority_map (category_id, default_priority)
SELECT id, 'low' FROM categories WHERE name = 'Payslip Disputes' AND parent_id = @cat_salary LIMIT 1
ON DUPLICATE KEY UPDATE default_priority = 'low';

-- =====================================================
-- 4. VERIFICATION QUERIES
-- =====================================================

-- Check renamed Salary subcategories
SELECT 'Salary Dispute subcategories:' AS info;
SELECT c.id, c.name, cpm.default_priority 
FROM categories c 
LEFT JOIN category_priority_map cpm ON c.id = cpm.category_id
WHERE c.parent_id = @cat_salary;

-- Check Leave subcategories (should now include Leave Assistance)
SELECT 'Leave concerns subcategories:' AS info;
SELECT c.id, c.name, cpm.default_priority 
FROM categories c 
LEFT JOIN category_priority_map cpm ON c.id = cpm.category_id
WHERE c.parent_id = @cat_leave;

-- Full category-priority map verification
SELECT 'Full HR+IT Category Priority Map:' AS info;
SELECT d.name AS department,
       COALESCE(pc.name, '-') AS parent_category, 
       c.name AS issue_type, 
       cpm.default_priority,
       CASE cpm.default_priority
           WHEN 'high'   THEN 'Response: 30 min  | Resolution: 1 business day'
           WHEN 'medium' THEN 'Response: 4 hrs   | Resolution: 2-3 days'
           WHEN 'low'    THEN 'Response: 1 day   | Resolution: 3-5 days'
       END AS sla_target
FROM category_priority_map cpm
JOIN categories c ON cpm.category_id = c.id
LEFT JOIN categories pc ON c.parent_id = pc.id
LEFT JOIN departments d ON c.department_id = d.id
ORDER BY d.name, pc.name, c.name;

-- =====================================================
-- MIGRATION COMPLETE
-- =====================================================
