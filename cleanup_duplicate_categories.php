<?php
/**
 * Cleanup Duplicate Categories
 * =============================
 * Aggressive one-time cleanup script that HARD DELETES duplicate
 * categories created by repeated migration runs.
 *
 * For each set of duplicates (same name + same parent), keeps the
 * one with the LOWEST id, reassigns tickets, and deletes the rest.
 *
 * Safe to run multiple times — idempotent.
 *
 * Usage: http://localhost/IThelp/cleanup_duplicate_categories.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px'>\n";
echo "============================================\n";
echo " Category Duplicate Cleanup\n";
echo "============================================\n\n";

$totalDeleted = 0;
$ticketsReassigned = 0;
$prioritiesDeleted = 0;

try {
    $db->beginTransaction();

    // =========================================================
    // STEP 1: Deduplicate PARENT categories
    //   Group by: (name, department_id) WHERE parent_id IS NULL
    //   Keep: lowest id
    // =========================================================
    echo "--- STEP 1: Dedup parent categories ---\n";

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
        $extraIds = array_filter($allIds, function($id) use ($keepId) { return $id !== $keepId; });
        if (empty($extraIds)) continue;

        $extraList = implode(',', $extraIds);
        $cnt = count($extraIds);

        // Reassign children of duplicate parents to the kept parent
        $moved = $db->exec("UPDATE categories SET parent_id = $keepId WHERE parent_id IN ($extraList)");

        // Reassign any tickets pointing to duplicate parent IDs
        $tix = $db->exec("UPDATE tickets SET category_id = $keepId WHERE category_id IN ($extraList)");
        $ticketsReassigned += $tix;

        // Remove priority mappings for duplicates
        $pri = $db->exec("DELETE FROM category_priority_map WHERE category_id IN ($extraList)");
        $prioritiesDeleted += $pri;

        // HARD DELETE the duplicate parents
        $db->exec("DELETE FROM categories WHERE id IN ($extraList)");
        $totalDeleted += $cnt;

        echo "  '{$dup['name']}' (dept={$dup['department_id']}): kept id=$keepId, deleted $cnt dupes [$extraList]\n";
        if ($moved) echo "    → Moved $moved children to parent $keepId\n";
        if ($tix)   echo "    → Reassigned $tix tickets\n";
    }

    if (empty($dupParents)) echo "  No duplicate parents found.\n";

    // =========================================================
    // STEP 2: Deduplicate CHILD categories
    //   Group by: (name, parent_id)
    //   Keep: lowest id
    // =========================================================
    echo "\n--- STEP 2: Dedup child categories ---\n";

    $dupChildren = $db->query("
        SELECT c.name, c.parent_id, pc.name AS parent_name,
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
        $extraIds = array_filter($allIds, function($id) use ($keepId) { return $id !== $keepId; });
        if (empty($extraIds)) continue;

        $extraList = implode(',', $extraIds);
        $cnt = count($extraIds);

        // Reassign tickets from duplicate children to the kept child
        $tix = $db->exec("UPDATE tickets SET category_id = $keepId WHERE category_id IN ($extraList)");
        $ticketsReassigned += $tix;

        // Remove priority mappings for duplicates
        $pri = $db->exec("DELETE FROM category_priority_map WHERE category_id IN ($extraList)");
        $prioritiesDeleted += $pri;

        // HARD DELETE the duplicate children
        $db->exec("DELETE FROM categories WHERE id IN ($extraList)");
        $totalDeleted += $cnt;

        $parentLabel = $dup['parent_name'] ?? 'id=' . $dup['parent_id'];
        echo "  '$parentLabel' → '{$dup['name']}': kept id=$keepId, deleted $cnt dupes\n";
        if ($tix) echo "    → Reassigned $tix tickets\n";
    }

    if (empty($dupChildren)) echo "  No duplicate children found.\n";

    // =========================================================
    // STEP 3: Clean up category_priority_map duplicates
    // =========================================================
    echo "\n--- STEP 3: Dedup category_priority_map ---\n";

    $priDups = $db->exec("
        DELETE cpm FROM category_priority_map cpm
        INNER JOIN (
            SELECT category_id, MAX(id) AS keep_id
            FROM category_priority_map
            GROUP BY category_id
            HAVING COUNT(*) > 1
        ) dupes ON cpm.category_id = dupes.category_id AND cpm.id < dupes.keep_id
    ");
    echo "  Removed $priDups duplicate priority map rows\n";

    // Remove orphaned priority mappings (category no longer exists)
    $orphaned = $db->exec("
        DELETE cpm FROM category_priority_map cpm
        LEFT JOIN categories c ON cpm.category_id = c.id
        WHERE c.id IS NULL
    ");
    if ($orphaned > 0) echo "  Removed $orphaned orphaned priority map rows\n";

    // =========================================================
    // STEP 4: Ensure kept categories are active
    // =========================================================
    echo "\n--- STEP 4: Reactivate kept categories ---\n";

    // Reactivate all parent categories that are in the expected set
    $reactivated = $db->exec("
        UPDATE categories SET is_active = 1
        WHERE parent_id IS NULL
          AND name IN ('Request a Document','Payroll','Harley','General Inquiry',
                       'Access','Email','Hardware','Software','Network',
                       'IT General Inquiry','OnBoarding','OffBoarding')
    ");
    echo "  Reactivated $reactivated parent categories\n";

    // Reactivate all active children of active parents
    $reactivatedSubs = $db->exec("
        UPDATE categories c
        INNER JOIN categories p ON c.parent_id = p.id AND p.is_active = 1
        SET c.is_active = 1
        WHERE c.parent_id IS NOT NULL
          AND c.is_active = 0
          AND c.name NOT IN (
              'Distribution List Request','Outlook Configuration','New Hardware Request',
              'Single Document','With other documents','Holiday Inquiry',
              'Leave Credit Balance','Non-Harley, Payslip Dispute, Leave-Related inquiries'
          )
    ");
    if ($reactivatedSubs > 0) echo "  Reactivated $reactivatedSubs sub-categories\n";

    // =========================================================
    // COMMIT
    // =========================================================
    $db->commit();
    echo "\n✅ Cleanup committed!\n";
    echo "   Deleted: $totalDeleted duplicate categories\n";
    echo "   Reassigned: $ticketsReassigned tickets\n";
    echo "   Cleaned: $prioritiesDeleted priority map entries\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "\n❌ ROLLED BACK: " . htmlspecialchars($e->getMessage()) . "\n";
}

// =========================================================
// VERIFICATION — show final clean state
// =========================================================
echo "\n\n============================================\n";
echo " VERIFICATION — Final State\n";
echo "============================================\n\n";

// Count check
$counts = $db->query("
    SELECT 
        CASE WHEN c.parent_id IS NULL THEN 'parent' ELSE 'child' END AS type,
        c.name,
        COUNT(*) AS cnt
    FROM categories c
    WHERE c.is_active = 1
    GROUP BY type, c.name, c.parent_id
    HAVING COUNT(*) > 1
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);

if (empty($counts)) {
    echo "✅ No remaining duplicates!\n\n";
} else {
    echo "⚠ Still has duplicates:\n";
    foreach ($counts as $row) {
        echo "  {$row['type']}: '{$row['name']}' × {$row['cnt']}\n";
    }
    echo "\n";
}

// Clean category listing
echo str_pad('Dept', 6) . str_pad('Category', 25) . str_pad('Issue Type', 40) . "Priority\n";
echo str_repeat('-', 85) . "\n";

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
       . str_pad($row['issue_type'] ?? '(none)', 40)
       . strtoupper($row['priority']) . "\n";
}

echo "\nTotal active categories: " . count($verify) . "\n";

// Expected count
echo "\nExpected: HR=10 subs + IT=32 subs = 42 total\n";

echo "\n</pre>";
