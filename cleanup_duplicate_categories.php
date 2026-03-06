<?php
/**
 * Cleanup Duplicate Categories — PRODUCTION (Hostinger)
 * =====================================================
 * Aggressive cleanup that HARD DELETES all duplicate categories
 * created by repeated migration script runs.
 *
 * Strategy:
 *   1. Fix parent_id = 0 → NULL (root cause of dedup misses)
 *   2. For each parent name+dept, keep lowest ID, delete rest
 *   3. For each child name+parent, keep lowest ID, delete rest
 *   4. Clean up priority_map orphans/dupes
 *   5. Ensure correct categories are active
 *
 * Safe to run multiple times — idempotent.
 *
 * URL: https://hdesk.resourcestaffonline.com/cleanup_duplicate_categories.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px'>\n";
echo "============================================\n";
echo " Category Duplicate Cleanup (Production)\n";
echo "============================================\n\n";

$totalDeleted = 0;
$ticketsReassigned = 0;

// Get department IDs
$deptHR = $db->query("SELECT id FROM departments WHERE code = 'HR' LIMIT 1")->fetchColumn();
$deptIT = $db->query("SELECT id FROM departments WHERE code = 'IT' OR name LIKE '%Information Tech%' LIMIT 1")->fetchColumn();
echo "Departments: HR=id$deptHR, IT=id$deptIT\n\n";

try {
    $db->beginTransaction();

    // =========================================================
    // STEP 1: Normalize parent_id — convert 0 to NULL
    //   This is the ROOT CAUSE: some parents had parent_id=0
    //   instead of NULL, making GROUP BY miss them as dupes
    // =========================================================
    echo "--- STEP 1: Normalize parent_id (0 → NULL) ---\n";
    $fixed = $db->exec("UPDATE categories SET parent_id = NULL WHERE parent_id = 0");
    echo "  Fixed $fixed rows (parent_id 0 → NULL)\n";

    // =========================================================
    // STEP 2: Deduplicate PARENT categories by name + dept
    //   Now that parent_id is normalized, GROUP BY will work
    // =========================================================
    echo "\n--- STEP 2: Dedup parent categories ---\n";

    $dupParents = $db->query("
        SELECT name, department_id,
               MIN(id) AS keep_id,
               GROUP_CONCAT(id ORDER BY id) AS all_ids,
               COUNT(*) AS cnt
        FROM categories
        WHERE parent_id IS NULL
        GROUP BY name, department_id
        HAVING COUNT(*) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dupParents as $dup) {
        $keepId = (int)$dup['keep_id'];
        $allIds = array_map('intval', explode(',', $dup['all_ids']));
        $extraIds = array_values(array_filter($allIds, function($id) use ($keepId) { return $id !== $keepId; }));
        if (empty($extraIds)) continue;

        $extraList = implode(',', $extraIds);
        $cnt = count($extraIds);

        // Move all children from duplicate parents to the kept parent
        $moved = $db->exec("UPDATE categories SET parent_id = $keepId WHERE parent_id IN ($extraList)");

        // Reassign tickets pointing to duplicate parent IDs
        $tix = $db->exec("UPDATE tickets SET category_id = $keepId WHERE category_id IN ($extraList)");
        $ticketsReassigned += $tix;

        // Delete priority mappings for the duplicates
        $db->exec("DELETE FROM category_priority_map WHERE category_id IN ($extraList)");

        // HARD DELETE duplicate parents
        $db->exec("DELETE FROM categories WHERE id IN ($extraList)");
        $totalDeleted += $cnt;

        echo "  '{$dup['name']}' (dept={$dup['department_id']}): kept id=$keepId, DELETED $cnt dupes\n";
        if ($moved) echo "    → Moved $moved children → parent $keepId\n";
        if ($tix)   echo "    → Reassigned $tix tickets\n";
    }
    if (empty($dupParents)) echo "  No duplicate parents found.\n";

    // =========================================================
    // STEP 3: Deduplicate CHILD categories by name + parent_id
    //   After step 2, children are consolidated under parents.
    //   Now dedup children with same name under same parent.
    // =========================================================
    echo "\n--- STEP 3: Dedup child categories ---\n";

    $dupChildren = $db->query("
        SELECT c.name, c.parent_id,
               COALESCE(pc.name, '?') AS parent_name,
               MIN(c.id) AS keep_id,
               GROUP_CONCAT(c.id ORDER BY c.id) AS all_ids,
               COUNT(*) AS cnt
        FROM categories c
        LEFT JOIN categories pc ON c.parent_id = pc.id
        WHERE c.parent_id IS NOT NULL
        GROUP BY c.name, c.parent_id
        HAVING COUNT(*) > 1
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dupChildren as $dup) {
        $keepId = (int)$dup['keep_id'];
        $allIds = array_map('intval', explode(',', $dup['all_ids']));
        $extraIds = array_values(array_filter($allIds, function($id) use ($keepId) { return $id !== $keepId; }));
        if (empty($extraIds)) continue;

        $extraList = implode(',', $extraIds);
        $cnt = count($extraIds);

        // Reassign tickets
        $tix = $db->exec("UPDATE tickets SET category_id = $keepId WHERE category_id IN ($extraList)");
        $ticketsReassigned += $tix;

        // Delete priority mappings
        $db->exec("DELETE FROM category_priority_map WHERE category_id IN ($extraList)");

        // HARD DELETE duplicate children
        $db->exec("DELETE FROM categories WHERE id IN ($extraList)");
        $totalDeleted += $cnt;

        echo "  '{$dup['parent_name']}' → '{$dup['name']}': kept id=$keepId, DELETED $cnt dupes\n";
        if ($tix) echo "    → Reassigned $tix tickets\n";
    }
    if (empty($dupChildren)) echo "  No duplicate children found.\n";

    // =========================================================
    // STEP 4: Clean up category_priority_map
    // =========================================================
    echo "\n--- STEP 4: Clean priority_map ---\n";

    // Remove duplicate priority entries
    $priDups = $db->exec("
        DELETE cpm FROM category_priority_map cpm
        INNER JOIN (
            SELECT category_id, MAX(id) AS keep_id
            FROM category_priority_map
            GROUP BY category_id
            HAVING COUNT(*) > 1
        ) dupes ON cpm.category_id = dupes.category_id AND cpm.id < dupes.keep_id
    ");
    echo "  Removed $priDups duplicate priority rows\n";

    // Remove orphaned mappings (category deleted)
    $orphaned = $db->exec("
        DELETE cpm FROM category_priority_map cpm
        LEFT JOIN categories c ON cpm.category_id = c.id
        WHERE c.id IS NULL
    ");
    if ($orphaned > 0) echo "  Removed $orphaned orphaned priority rows\n";

    // =========================================================
    // STEP 5: Ensure correct parent categories are ACTIVE
    // =========================================================
    echo "\n--- STEP 5: Activate correct categories ---\n";

    // The 12 expected parent names
    $expectedParents = [
        'Request a Document', 'Payroll', 'Harley', 'General Inquiry',
        'Access', 'Email', 'Hardware', 'Software', 'Network',
        'IT General Inquiry', 'OnBoarding', 'OffBoarding'
    ];
    $parentList = implode(',', array_map(function($n) use ($db) { return $db->quote($n); }, $expectedParents));

    $activated = $db->exec("
        UPDATE categories SET is_active = 1 
        WHERE name IN ($parentList) AND parent_id IS NULL
    ");
    echo "  Activated $activated parent categories\n";

    // Deactivate HR/IT parents NOT in expected list
    $deactivated = $db->exec("
        UPDATE categories SET is_active = 0
        WHERE parent_id IS NULL 
          AND name NOT IN ($parentList)
    ");
    if ($deactivated) echo "  Deactivated $deactivated unexpected parents\n";

    // Activate children of active parents (except known deactivated subs)
    $deactivatedSubs = [
        'Distribution List Request', 'Outlook Configuration', 'New Hardware Request',
        'Single Document', 'With other documents', 'Holiday Inquiry',
        'Leave Credit Balance', 'Non-Harley, Payslip Dispute, Leave-Related inquiries'
    ];
    $deactList = implode(',', array_map(function($n) use ($db) { return $db->quote($n); }, $deactivatedSubs));

    $subActivated = $db->exec("
        UPDATE categories c
        INNER JOIN categories p ON c.parent_id = p.id AND p.is_active = 1
        SET c.is_active = 1
        WHERE c.is_active = 0
          AND c.name NOT IN ($deactList)
    ");
    if ($subActivated) echo "  Activated $subActivated sub-categories\n";

    // =========================================================
    // COMMIT
    // =========================================================
    $db->commit();
    echo "\n✅ Cleanup committed!\n";
    echo "   Total deleted: $totalDeleted categories\n";
    echo "   Tickets reassigned: $ticketsReassigned\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "\n❌ ROLLED BACK: " . htmlspecialchars($e->getMessage()) . "\n";
}


// =========================================================
// VERIFICATION
// =========================================================
echo "\n\n============================================\n";
echo " VERIFICATION\n";
echo "============================================\n\n";

// Check for remaining duplicates
echo "--- Duplicate Check ---\n";
$remaining = $db->query("
    SELECT 
        CASE WHEN c.parent_id IS NULL THEN 'PARENT' ELSE 'CHILD' END AS type,
        c.name,
        c.parent_id,
        COUNT(*) AS cnt
    FROM categories c
    WHERE c.is_active = 1
    GROUP BY c.name, c.parent_id
    HAVING COUNT(*) > 1
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($remaining)) {
    echo "✅ No duplicates remain!\n\n";
} else {
    echo "⚠ Still has duplicates:\n";
    foreach ($remaining as $r) {
        echo "  {$r['type']}: '{$r['name']}' (parent_id={$r['parent_id']}) × {$r['cnt']}\n";
    }
    echo "\n";
}

// Active parents
echo "--- Active Parents ---\n";
$parents = $db->query("
    SELECT c.id, d.code AS dept, c.name, c.is_active,
           (SELECT COUNT(*) FROM categories sub WHERE sub.parent_id = c.id AND sub.is_active = 1) AS active_subs
    FROM categories c
    LEFT JOIN departments d ON c.department_id = d.id
    WHERE c.parent_id IS NULL AND c.is_active = 1
    ORDER BY d.code, c.sort_order
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($parents as $p) {
    echo "  [{$p['dept']}] {$p['name']} (id={$p['id']}, {$p['active_subs']} subs)\n";
}
echo "  Total: " . count($parents) . " parents (expected: 12)\n";

// Full category listing
echo "\n--- Full Category Listing ---\n";
echo str_pad('Dept', 6) . str_pad('Category', 25) . str_pad('Issue Type', 42) . "Priority\n";
echo str_repeat('-', 90) . "\n";

$verify = $db->query("
    SELECT d.code AS dept,
           c.name AS parent_category,
           sub.name AS issue_type,
           COALESCE((
               SELECT cpm.default_priority
               FROM category_priority_map cpm
               WHERE cpm.category_id = sub.id
               LIMIT 1
           ), '—') AS priority
    FROM categories c
    INNER JOIN categories sub ON sub.parent_id = c.id AND sub.is_active = 1
    LEFT JOIN departments d ON c.department_id = d.id
    WHERE c.parent_id IS NULL AND c.is_active = 1
    ORDER BY d.code, c.sort_order, sub.sort_order
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($verify as $row) {
    echo str_pad($row['dept'] ?? '?', 6)
       . str_pad($row['parent_category'], 25)
       . str_pad($row['issue_type'] ?? '(none)', 42)
       . strtoupper($row['priority']) . "\n";
}

echo "\nTotal rows: " . count($verify) . " (expected: 42)\n";

echo "\n</pre>";
