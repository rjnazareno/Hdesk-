<?php
/**
 * Cleanup Duplicate Categories — PRODUCTION v3 (Hostinger)
 * =========================================================
 * Row-by-row deduplication. No GROUP_CONCAT tricks.
 * For each unique (name, department_id) parent combo:
 *   → keep the FIRST row (lowest id), DELETE every other one.
 * For each unique (name, parent_id) child combo:
 *   → keep the FIRST row, DELETE every other one.
 * Tickets and priority maps are safely reassigned before deletion.
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
echo " Category Duplicate Cleanup v3 (Production)\n";
echo "============================================\n\n";

$totalParentsDeleted = 0;
$totalChildrenDeleted = 0;
$ticketsReassigned = 0;

// Department IDs
$deptHR = $db->query("SELECT id FROM departments WHERE code = 'HR' LIMIT 1")->fetchColumn();
$deptIT = $db->query("SELECT id FROM departments WHERE code = 'IT' OR name LIKE '%Information Tech%' LIMIT 1")->fetchColumn();
echo "Departments: HR=id$deptHR, IT=id$deptIT\n\n";

// =========================================================
// DIAGNOSTIC: Show what's actually in the DB before cleanup
// =========================================================
echo "--- DIAGNOSTIC: Raw parent counts ---\n";
$rawParents = $db->query("
    SELECT name, department_id, 
           IFNULL(parent_id, 'NULL') AS pid,
           COUNT(*) AS cnt,
           GROUP_CONCAT(id ORDER BY id SEPARATOR ', ') AS ids
    FROM categories
    WHERE parent_id IS NULL OR parent_id = 0
    GROUP BY name, department_id
    ORDER BY cnt DESC, name
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rawParents as $r) {
    $marker = ($r['cnt'] > 1) ? '⚠' : '✓';
    echo "  $marker '{$r['name']}' dept={$r['department_id']} pid={$r['pid']} × {$r['cnt']}  ids=[{$r['ids']}]\n";
}

echo "\n--- DIAGNOSTIC: Raw child duplicates ---\n";
$rawChildren = $db->query("
    SELECT c.name, c.parent_id, pc.name AS parent_name,
           COUNT(*) AS cnt,
           GROUP_CONCAT(c.id ORDER BY c.id SEPARATOR ', ') AS ids
    FROM categories c
    LEFT JOIN categories pc ON c.parent_id = pc.id
    WHERE c.parent_id IS NOT NULL AND c.parent_id != 0
    GROUP BY c.name, c.parent_id
    HAVING COUNT(*) > 1
    ORDER BY cnt DESC
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rawChildren as $r) {
    echo "  ⚠ '{$r['parent_name']}' → '{$r['name']}' × {$r['cnt']}  ids=[{$r['ids']}]\n";
}
if (empty($rawChildren)) echo "  (none)\n";

echo "\n";

try {
    $db->beginTransaction();

    // =========================================================
    // STEP 0: Purge ALL '*HR to file Ticket' rows
    //   These are OnBoarding/OffBoarding subs created by broken
    //   migration (lastInsertId=0 bug). They got parent_id=0
    //   which makes them float as pseudo-parents in dropdowns.
    //   Migration will recreate them correctly after cleanup.
    // =========================================================
    echo "--- STEP 0: Purge orphaned '*HR to file Ticket' ---\n";
    $hrFileRows = $db->query("SELECT id, parent_id FROM categories WHERE name = '*HR to file Ticket'")->fetchAll(PDO::FETCH_ASSOC);
    $purged = 0;
    $deleteStmt0 = $db->prepare("DELETE FROM categories WHERE id = :id");
    $deletePri0  = $db->prepare("DELETE FROM category_priority_map WHERE category_id = :id");
    foreach ($hrFileRows as $row) {
        $deletePri0->execute([':id' => $row['id']]);
        $deleteStmt0->execute([':id' => $row['id']]);
        $purged++;
        echo "  PURGED '*HR to file Ticket' id={$row['id']} (parent_id=" . ($row['parent_id'] ?? 'NULL') . ")\n";
    }
    if ($purged === 0) echo "  None found.\n";
    else echo "  Total purged: $purged (migration will recreate correctly)\n";

    // =========================================================
    // STEP 1: Fix parent_id = 0 → NULL
    // =========================================================
    echo "\n--- STEP 1: Normalize parent_id ---\n";
    $fixed = $db->exec("UPDATE categories SET parent_id = NULL WHERE parent_id = 0");
    echo "  Fixed $fixed rows (parent_id 0 → NULL)\n";

    // =========================================================
    // STEP 2: Row-by-row PARENT deduplication
    //   Iterate ALL parents sorted by name + dept + id.
    //   First occurrence of each (name, dept) is kept.
    //   All subsequent are deleted after reassigning children/tickets.
    // =========================================================
    echo "\n--- STEP 2: Dedup parents (row-by-row) ---\n";

    $allParents = $db->query("
        SELECT id, name, IFNULL(department_id, 0) AS department_id
        FROM categories
        WHERE parent_id IS NULL
        ORDER BY name, department_id, id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $seenParents = []; // "name|dept" => keepId
    $deleteStmt = $db->prepare("DELETE FROM categories WHERE id = :id");
    $moveChildren = $db->prepare("UPDATE categories SET parent_id = :keep WHERE parent_id = :dupe");
    $moveTickets = $db->prepare("UPDATE tickets SET category_id = :keep WHERE category_id = :dupe");
    $deletePriority = $db->prepare("DELETE FROM category_priority_map WHERE category_id = :id");

    foreach ($allParents as $row) {
        $id = (int)$row['id'];
        $key = $row['name'] . '|' . $row['department_id'];

        if (!isset($seenParents[$key])) {
            // First occurrence — keep this one
            $seenParents[$key] = $id;
        } else {
            // Duplicate — reassign children + tickets, then delete
            $keepId = $seenParents[$key];

            $moveChildren->execute([':keep' => $keepId, ':dupe' => $id]);
            $childrenMoved = $moveChildren->rowCount();

            $moveTickets->execute([':keep' => $keepId, ':dupe' => $id]);
            $tix = $moveTickets->rowCount();
            $ticketsReassigned += $tix;

            $deletePriority->execute([':id' => $id]);
            $deleteStmt->execute([':id' => $id]);
            $totalParentsDeleted++;

            $detail = [];
            if ($childrenMoved) $detail[] = "$childrenMoved children moved";
            if ($tix) $detail[] = "$tix tickets";
            $extra = $detail ? ' (' . implode(', ', $detail) . ')' : '';
            echo "  DELETED parent '{$row['name']}' id=$id (keep=$keepId)$extra\n";
        }
    }
    if ($totalParentsDeleted === 0) echo "  No duplicate parents.\n";

    // =========================================================
    // STEP 3: Row-by-row CHILD deduplication
    //   After parents are deduped and children reassigned,
    //   find duplicate children under the same parent.
    // =========================================================
    echo "\n--- STEP 3: Dedup children (row-by-row) ---\n";

    $allChildren = $db->query("
        SELECT id, name, parent_id
        FROM categories
        WHERE parent_id IS NOT NULL
        ORDER BY parent_id, name, id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $seenChildren = []; // "name|parent_id" => keepId

    foreach ($allChildren as $row) {
        $id = (int)$row['id'];
        $key = $row['name'] . '|' . $row['parent_id'];

        if (!isset($seenChildren[$key])) {
            $seenChildren[$key] = $id;
        } else {
            $keepId = $seenChildren[$key];

            $moveTickets->execute([':keep' => $keepId, ':dupe' => $id]);
            $tix = $moveTickets->rowCount();
            $ticketsReassigned += $tix;

            $deletePriority->execute([':id' => $id]);
            $deleteStmt->execute([':id' => $id]);
            $totalChildrenDeleted++;

            $extra = $tix ? " ($tix tickets reassigned)" : '';
            echo "  DELETED child '{$row['name']}' id=$id under parent={$row['parent_id']} (keep=$keepId)$extra\n";
        }
    }
    if ($totalChildrenDeleted === 0) echo "  No duplicate children.\n";

    // =========================================================
    // STEP 4: Clean orphan categories (no department) 
    // =========================================================
    echo "\n--- STEP 4: Remove orphan categories ---\n";

    $orphans = $db->query("
        SELECT c.id, c.name, c.department_id
        FROM categories c
        LEFT JOIN departments d ON c.department_id = d.id
        WHERE c.parent_id IS NULL AND d.id IS NULL
    ")->fetchAll(PDO::FETCH_ASSOC);

    $orphanCount = 0;
    foreach ($orphans as $orph) {
        $id = (int)$orph['id'];
        // Move children to correct parent if possible
        // Find the proper parent with the same name that HAS a department
        $proper = $db->prepare("
            SELECT id FROM categories 
            WHERE name = :name AND parent_id IS NULL AND department_id IS NOT NULL 
            LIMIT 1
        ");
        $proper->execute([':name' => $orph['name']]);
        $properId = $proper->fetchColumn();

        if ($properId) {
            $moveChildren->execute([':keep' => (int)$properId, ':dupe' => $id]);
            $moveTickets->execute([':keep' => (int)$properId, ':dupe' => $id]);
        }
        $deletePriority->execute([':id' => $id]);
        $deleteStmt->execute([':id' => $id]);
        $orphanCount++;
        echo "  DELETED orphan '{$orph['name']}' id=$id (dept_id={$orph['department_id']}" 
           . ($properId ? ", moved to id=$properId" : "") . ")\n";
    }
    if ($orphanCount === 0) echo "  No orphans.\n";

    // =========================================================
    // STEP 5: Clean priority map
    // =========================================================
    echo "\n--- STEP 5: Clean priority_map ---\n";

    // Dedup priority rows
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

    // Remove orphaned mappings
    $orphPri = $db->exec("
        DELETE cpm FROM category_priority_map cpm
        LEFT JOIN categories c ON cpm.category_id = c.id
        WHERE c.id IS NULL
    ");
    if ($orphPri) echo "  Removed $orphPri orphaned priority rows\n";

    // =========================================================
    // STEP 6: Activate correct categories
    // =========================================================
    echo "\n--- STEP 6: Activate correct categories ---\n";

    $expectedParents = [
        'Request a Document', 'Payroll', 'Harley', 'General Inquiry',
        'Access', 'Email', 'Hardware', 'Software', 'Network',
        'IT General Inquiry', 'OnBoarding', 'OffBoarding'
    ];
    $parentList = implode(',', array_map(function($n) use ($db) { return $db->quote($n); }, $expectedParents));

    $activated = $db->exec("UPDATE categories SET is_active = 1 WHERE name IN ($parentList) AND parent_id IS NULL");
    echo "  Activated $activated parent categories\n";

    // Deactivate unexpected parents
    $deactivated = $db->exec("UPDATE categories SET is_active = 0 WHERE parent_id IS NULL AND name NOT IN ($parentList)");
    if ($deactivated) echo "  Deactivated $deactivated unexpected parents\n";

    // Activate children of active parents (except known deactivated ones)
    $deactivatedSubs = [
        'Distribution List Request', 'Outlook Configuration', 'New Hardware Request',
        'Single Document', 'With other documents', 'Holiday Inquiry',
        'Leave Credit Balance', 'Non-Harley, Payslip Dispute, Leave-Related inquiries'
    ];
    $deactList = implode(',', array_map(function($n) use ($db) { return $db->quote($n); }, $deactivatedSubs));

    $subAct = $db->exec("
        UPDATE categories c
        INNER JOIN categories p ON c.parent_id = p.id AND p.is_active = 1
        SET c.is_active = 1
        WHERE c.is_active = 0 AND c.name NOT IN ($deactList)
    ");
    if ($subAct) echo "  Activated $subAct sub-categories\n";

    // =========================================================
    // COMMIT
    // =========================================================
    $db->commit();
    echo "\n✅ Cleanup committed!\n";
    echo "   Parents deleted: $totalParentsDeleted\n";
    echo "   Children deleted: $totalChildrenDeleted\n";
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

// Duplicate check
echo "--- Duplicate Check ---\n";
$remaining = $db->query("
    SELECT 
        CASE WHEN c.parent_id IS NULL THEN 'PARENT' ELSE 'CHILD' END AS type,
        c.name,
        IFNULL(c.parent_id, 'NULL') AS parent_id,
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
    SELECT c.id, d.code AS dept, c.name,
           (SELECT COUNT(*) FROM categories sub WHERE sub.parent_id = c.id AND sub.is_active = 1) AS subs
    FROM categories c
    LEFT JOIN departments d ON c.department_id = d.id
    WHERE c.parent_id IS NULL AND c.is_active = 1
    ORDER BY d.code, c.sort_order
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($parents as $p) {
    echo "  [{$p['dept']}] {$p['name']} (id={$p['id']}, {$p['subs']} subs)\n";
}
echo "  Total: " . count($parents) . " parents (expected: 12)\n";

// Full listing
echo "\n--- Category Listing ---\n";
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

echo "\nTotal: " . count($verify) . " rows (expected: 42)\n";
echo "\n</pre>";
