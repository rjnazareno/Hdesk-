<?php
/**
 * Fix Categories — March 2026 (Run on Production)
 * ================================================
 * Combined: Cleanup orphans + Sync categories in one step.
 * 
 * This script:
 *   1. Purges ALL '*HR to file Ticket' orphan rows
 *   2. Normalizes parent_id = 0 → NULL
 *   3. Deduplicates parent and child categories
 *   4. Then runs full category sync to match the SLA spreadsheet
 *
 * URL: https://hdesk.resourcestaffonline.com/fix_categories_production.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px;max-height:90vh;overflow:auto'>\n";
echo "=============================================\n";
echo " Fix Categories — Production (March 2026)\n";
echo "=============================================\n\n";

// ─── PHASE 1: CLEANUP ───
echo "═══ PHASE 1: CLEANUP ═══\n\n";

$deptHR = $db->query("SELECT id FROM departments WHERE code = 'HR' LIMIT 1")->fetchColumn();
$deptIT = $db->query("SELECT id FROM departments WHERE code = 'IT' OR name LIKE '%Information Tech%' LIMIT 1")->fetchColumn();
echo "Departments: HR=id$deptHR, IT=id$deptIT\n\n";

// Show current state
$hrFileCount = $db->query("SELECT COUNT(*) FROM categories WHERE name = '*HR to file Ticket'")->fetchColumn();
echo "Before cleanup: $hrFileCount '*HR to file Ticket' rows\n";
$totalCats = $db->query("SELECT COUNT(*) FROM categories WHERE is_active = 1")->fetchColumn();
echo "Before cleanup: $totalCats total active categories\n\n";

try {
    $db->beginTransaction();

    // STEP 0: Purge ALL '*HR to file Ticket' rows (will recreate correctly via sync)
    echo "--- STEP 0: Purge ALL '*HR to file Ticket' rows ---\n";
    $hrFileRows = $db->query("SELECT id, parent_id FROM categories WHERE name = '*HR to file Ticket'")->fetchAll(PDO::FETCH_ASSOC);
    $purged = 0;
    foreach ($hrFileRows as $row) {
        // Check if any tickets reference this category
        $ticketCount = $db->prepare("SELECT COUNT(*) FROM tickets WHERE category_id = ?");
        $ticketCount->execute([$row['id']]);
        $tix = $ticketCount->fetchColumn();
        if ($tix > 0) {
            // Find the proper OnBoarding/OffBoarding parent to reassign to
            $parentName = null;
            if ($row['parent_id']) {
                $pn = $db->prepare("SELECT name FROM categories WHERE id = ?");
                $pn->execute([$row['parent_id']]);
                $parentName = $pn->fetchColumn();
            }
            // Just deactivate if tickets exist — sync will recreate correctly
            $db->prepare("UPDATE categories SET is_active = 0 WHERE id = ?")->execute([$row['id']]);
            echo "  DEACTIVATED '*HR to file Ticket' id={$row['id']} ($tix tickets attached, parent=$parentName)\n";
        } else {
            $db->prepare("DELETE FROM category_priority_map WHERE category_id = ?")->execute([$row['id']]);
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$row['id']]);
            $purged++;
        }
    }
    echo "  Purged $purged rows, " . (count($hrFileRows) - $purged) . " deactivated (had tickets)\n";

    // STEP 1: Fix parent_id = 0 → NULL
    echo "\n--- STEP 1: Normalize parent_id ---\n";
    $fixed = $db->exec("UPDATE categories SET parent_id = NULL WHERE parent_id = 0");
    echo "  Fixed $fixed rows\n";

    // STEP 2: Dedup parents
    echo "\n--- STEP 2: Dedup parents ---\n";
    $allParents = $db->query("
        SELECT id, name, IFNULL(department_id, 0) AS department_id
        FROM categories WHERE parent_id IS NULL ORDER BY name, department_id, id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $seenParents = [];
    $moveChildren = $db->prepare("UPDATE categories SET parent_id = :keep WHERE parent_id = :dupe");
    $moveTickets = $db->prepare("UPDATE tickets SET category_id = :keep WHERE category_id = :dupe");
    $parentsDeleted = 0;

    foreach ($allParents as $row) {
        $key = $row['name'] . '|' . $row['department_id'];
        if (!isset($seenParents[$key])) {
            $seenParents[$key] = (int)$row['id'];
        } else {
            $keepId = $seenParents[$key];
            $moveChildren->execute([':keep' => $keepId, ':dupe' => (int)$row['id']]);
            $moveTickets->execute([':keep' => $keepId, ':dupe' => (int)$row['id']]);
            $db->prepare("DELETE FROM category_priority_map WHERE category_id = ?")->execute([$row['id']]);
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$row['id']]);
            $parentsDeleted++;
            echo "  DELETED parent '{$row['name']}' id={$row['id']} (keep={$keepId})\n";
        }
    }
    echo "  $parentsDeleted parent duplicates removed\n";

    // STEP 3: Dedup children
    echo "\n--- STEP 3: Dedup children ---\n";
    $allChildren = $db->query("
        SELECT id, name, parent_id FROM categories
        WHERE parent_id IS NOT NULL ORDER BY parent_id, name, id
    ")->fetchAll(PDO::FETCH_ASSOC);

    $seenChildren = [];
    $childrenDeleted = 0;

    foreach ($allChildren as $row) {
        $key = $row['name'] . '|' . $row['parent_id'];
        if (!isset($seenChildren[$key])) {
            $seenChildren[$key] = (int)$row['id'];
        } else {
            $keepId = $seenChildren[$key];
            $moveTickets->execute([':keep' => $keepId, ':dupe' => (int)$row['id']]);
            $db->prepare("DELETE FROM category_priority_map WHERE category_id = ?")->execute([$row['id']]);
            $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$row['id']]);
            $childrenDeleted++;
            echo "  DELETED child '{$row['name']}' id={$row['id']} (keep={$keepId})\n";
        }
    }
    echo "  $childrenDeleted child duplicates removed\n";

    // STEP 4: Remove orphan categories (no department)
    echo "\n--- STEP 4: Remove orphans (no dept) ---\n";
    $orphans = $db->query("
        SELECT c.id, c.name FROM categories c
        LEFT JOIN departments d ON c.department_id = d.id
        WHERE c.parent_id IS NULL AND d.id IS NULL
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($orphans as $o) {
        // Find a proper parent with same name that HAS a department
        $proper = $db->prepare("SELECT id FROM categories WHERE name = ? AND parent_id IS NULL AND department_id IS NOT NULL LIMIT 1");
        $proper->execute([$o['name']]);
        $properId = $proper->fetchColumn();
        if ($properId) {
            $db->prepare("UPDATE categories SET parent_id = ? WHERE parent_id = ?")->execute([$properId, $o['id']]);
            $db->prepare("UPDATE tickets SET category_id = ? WHERE category_id = ?")->execute([$properId, $o['id']]);
        } else {
            // No proper target — just deactivate instead of deleting (FK safe)
            $db->prepare("UPDATE categories SET is_active = 0 WHERE id = ?")->execute([$o['id']]);
            echo "  DEACTIVATED orphan '{$o['name']}' id={$o['id']} (has ticket refs)\n";
            continue;
        }
        $db->prepare("DELETE FROM category_priority_map WHERE category_id = ?")->execute([$o['id']]);
        $db->prepare("DELETE FROM categories WHERE id = ?")->execute([$o['id']]);
        echo "  DELETED orphan '{$o['name']}' id={$o['id']}" . ($properId ? " (moved to id=$properId)" : "") . "\n";
    }
    echo "  " . count($orphans) . " orphans handled\n";

    // STEP 5: Clean priority map orphans
    echo "\n--- STEP 5: Clean priority map ---\n";
    $orphPri = $db->exec("
        DELETE cpm FROM category_priority_map cpm
        LEFT JOIN categories c ON cpm.category_id = c.id
        WHERE c.id IS NULL
    ");
    echo "  Removed $orphPri orphaned priority rows\n";

    $db->commit();
    echo "\n✓ PHASE 1 COMPLETE\n\n";
} catch (Exception $e) {
    $db->rollBack();
    echo "ERROR in cleanup: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "</pre>";
    exit;
}

// ─── PHASE 2: SYNC CATEGORIES TO MATCH SPREADSHEET ───
echo "═══ PHASE 2: SYNC CATEGORIES ═══\n\n";

// Force emulated prepares + buffered queries for Hostinger compatibility
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
$db->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);

$results = [];

// Clean priority map duplicates before adding unique index
$db->exec("
    DELETE cpm1 FROM category_priority_map cpm1
    INNER JOIN category_priority_map cpm2
    ON cpm1.category_id = cpm2.category_id AND cpm1.id > cpm2.id
");
// Also remove any rows for category_id = 0 (from previous buggy runs)
$db->exec("DELETE FROM category_priority_map WHERE category_id = 0");

try {
    $db->exec("ALTER TABLE category_priority_map ADD UNIQUE INDEX uk_category (category_id)");
} catch (PDOException $e) {
    // Already exists — OK
}

/**
 * Get new category ID after INSERT using MAX(id) comparison.
 * This works on ALL hosting including Hostinger where LAST_INSERT_ID() returns 0.
 */
function getMaxCatId($db) {
    $s = $db->query("SELECT MAX(id) FROM categories");
    $v = (int) $s->fetchColumn();
    $s->closeCursor();
    $s = null;
    return $v;
}

/**
 * Find or create a parent category. Uses MAX(id) to detect new ID.
 */
function findOrCreateParent($db, $deptId, $name, $oldNames, $desc, $icon, $color, $sort, &$results) {
    // Check current name
    $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND department_id = ? AND parent_id IS NULL LIMIT 1");
    $stmt->execute([$name, $deptId]);
    $id = $stmt->fetchColumn();
    $stmt->closeCursor();
    $stmt = null;
    if ($id) {
        $u = $db->prepare("UPDATE categories SET is_active = 1 WHERE id = ?");
        $u->execute([$id]);
        $u->closeCursor();
        $u = null;
        return (int)$id;
    }
    // Check old names and rename
    foreach ($oldNames as $old) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND department_id = ? AND parent_id IS NULL LIMIT 1");
        $stmt->execute([$old, $deptId]);
        $id = $stmt->fetchColumn();
        $stmt->closeCursor();
        $stmt = null;
        if ($id) {
            $u = $db->prepare("UPDATE categories SET name = ?, is_active = 1 WHERE id = ?");
            $u->execute([$name, $id]);
            $u->closeCursor();
            $u = null;
            $results[] = "  ✓ Renamed '$old' → '$name' (id=$id)";
            return (int)$id;
        }
    }
    // Capture MAX(id) before insert
    $maxBefore = getMaxCatId($db);
    // INSERT
    $ins = $db->prepare("INSERT INTO categories (department_id, parent_id, name, description, icon, color, sort_order, is_active) VALUES (?, NULL, ?, ?, ?, ?, ?, 1)");
    $ins->execute([$deptId, $name, $desc, $icon, $color, $sort]);
    $ins->closeCursor();
    $ins = null;
    // Get ID: MAX(id) must be higher now
    $id = getMaxCatId($db);
    if ($id <= $maxBefore) {
        // Fallback: query by name
        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND department_id = ? AND parent_id IS NULL ORDER BY id DESC LIMIT 1");
        $stmt->execute([$name, $deptId]);
        $id = (int) $stmt->fetchColumn();
        $stmt->closeCursor();
        $stmt = null;
    }
    $results[] = "  ✓ Created parent '$name' (id=$id)";
    return (int)$id;
}

/**
 * Ensure a child category exists under given parent. Uses MAX(id) for new inserts.
 */
function ensureChild($db, $deptId, $parentId, $name, $desc, $icon, $color, $sort, &$results) {
    $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ? LIMIT 1");
    $stmt->execute([$name, $parentId]);
    $id = $stmt->fetchColumn();
    $stmt->closeCursor();
    $stmt = null;
    if ($id) {
        $u = $db->prepare("UPDATE categories SET is_active = 1 WHERE id = ?");
        $u->execute([$id]);
        $u->closeCursor();
        $u = null;
        return (int)$id;
    }
    $maxBefore = getMaxCatId($db);
    $ins = $db->prepare("INSERT INTO categories (department_id, parent_id, name, description, icon, color, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
    $ins->execute([$deptId, $parentId, $name, $desc, $icon, $color, $sort]);
    $ins->closeCursor();
    $ins = null;
    $id = getMaxCatId($db);
    if ($id <= $maxBefore) {
        $stmt = $db->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$name, $parentId]);
        $id = (int) $stmt->fetchColumn();
        $stmt->closeCursor();
        $stmt = null;
    }
    $results[] = "    + Added '$name' (id=$id)";
    return (int)$id;
}

/**
 * Deactivate children under $parentId that are NOT in $keepNames list.
 */
function deactivateUnlisted($db, $parentId, $keepNames, &$results) {
    $placeholders = implode(',', array_fill(0, count($keepNames), '?'));
    $stmt = $db->prepare("SELECT id, name FROM categories WHERE parent_id = ? AND is_active = 1 AND name NOT IN ($placeholders)");
    $stmt->execute(array_merge([$parentId], $keepNames));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
    $stmt = null;
    foreach ($rows as $row) {
        $u = $db->prepare("UPDATE categories SET is_active = 0 WHERE id = ?");
        $u->execute([$row['id']]);
        $u->closeCursor();
        $u = null;
        $results[] = "    - Deactivated '{$row['name']}' (id={$row['id']})";
    }
}

/**
 * Upsert priority mapping for a category (skips id=0).
 */
function setPriority($db, $categoryId, $priority) {
    if ($categoryId <= 0) return; // Skip invalid IDs
    $s = $db->prepare("INSERT INTO category_priority_map (category_id, default_priority) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE default_priority = VALUES(default_priority)");
    $s->execute([$categoryId, $priority]);
    $s->closeCursor();
    $s = null;
}

// ═══ HR CATEGORIES ═══
echo "--- HR CATEGORIES ---\n";

// 1. Request a Document
$parentId = findOrCreateParent($db, $deptHR, 'Request a Document',
    ['Certificate of Employment (COE)', 'Request a Document'],
    'Document request services', 'file-alt', '#3B82F6', 1, $results);
$subs = [
    ['Certificate of Employment (COC)', 'Request for Certificate of Employment', 'file-contract', '#3B82F6', 1, 'low'],
    ['Certification of Leave', 'Request for leave certification', 'calendar-check', '#10B981', 2, 'low'],
    ['Others', 'Other document requests', 'file', '#6B7280', 3, 'low'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptHR, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'low');
$results[] = "HR: 'Request a Document' synced (3 subs)";

// 2. Payroll
$parentId = findOrCreateParent($db, $deptHR, 'Payroll',
    ['Salary Dispute', 'Payroll'],
    'Payroll and salary concerns', 'money-bill-wave', '#F59E0B', 2, $results);
$subs = [
    ['Draft Payslip Discrepancy', 'Issues with draft payslip before cutoff', 'exclamation-circle', '#EF4444', 1, 'high'],
    ['Post-Payroll Payslip Concerns', 'Payslip concerns after payroll processing', 'receipt', '#F59E0B', 2, 'medium'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptHR, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'medium');
$results[] = "HR: 'Payroll' synced (2 subs)";

// 3. Harley (Timekeeping)
$parentId = findOrCreateParent($db, $deptHR, 'Harley',
    ['Timekeeping concerns', 'Harley (Timekeeping)', 'Timekeeping Concerns', 'Harley'],
    'Harley timekeeping system concerns', 'clock', '#F59E0B', 3, $results);
$subs = [
    ['Log In Error', 'Cannot log in to Harley timekeeping system', 'exclamation-triangle', '#EF4444', 1, 'high'],
    ['Missing Log In/Log Out', 'Missing or incorrect time entries in Harley', 'user-clock', '#F59E0B', 2, 'low'],
    ['Leave Inquiry', 'Questions about leave balances, leave policies', 'calendar-check', '#8B5CF6', 3, 'low'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptHR, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'medium');
$results[] = "HR: 'Harley' synced (3 subs)";

// Deactivate old standalone Leave parents
$oldLeave = $db->query("SELECT id FROM categories WHERE name IN ('Leave', 'Leave concerns') AND department_id = $deptHR AND parent_id IS NULL")->fetchAll(PDO::FETCH_COLUMN);
foreach ($oldLeave as $oldId) {
    $db->prepare("UPDATE categories SET is_active = 0 WHERE id = ?")->execute([$oldId]);
    $results[] = "HR: Deactivated old Leave parent (id=$oldId)";
}

// 4. General Inquiry
$parentId = findOrCreateParent($db, $deptHR, 'General Inquiry',
    ['HR General Inquiry', 'General Inquiry'],
    'General HR inquiries', 'info-circle', '#6B7280', 4, $results);
$subs = [
    ['HMO Inquiry', 'Health insurance and HMO questions', 'heartbeat', '#EF4444', 1, 'medium'],
    ['Others', 'Other general HR inquiries', 'question-circle', '#6B7280', 2, 'low'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptHR, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'low');
$results[] = "HR: 'General Inquiry' synced (2 subs)";

// ═══ IT CATEGORIES ═══
echo "--- IT CATEGORIES ---\n";

// 1. Access
$parentId = findOrCreateParent($db, $deptIT, 'Access',
    ['Access'], 'System access and permissions', 'key', '#8B5CF6', 1, $results);
$subs = [
    ['Account Deactivation', 'Deactivate user account', 'user-slash', '#EF4444', 1, 'high'],
    ['Password Reset', 'Reset user password', 'lock', '#F59E0B', 2, 'high'],
    ['Account Locked', 'Unlock user account', 'lock', '#EF4444', 3, 'high'],
    ['Permission Request', 'Request new permissions', 'user-shield', '#3B82F6', 4, 'low'],
    ['New Account Request', 'Create new user account', 'user-plus', '#10B981', 5, 'low'],
    ['System Access Issue', 'Cannot access system', 'exclamation-triangle', '#EF4444', 6, 'high'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptIT, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'high');

// 2. Email
$parentId = findOrCreateParent($db, $deptIT, 'Email',
    ['Email'], 'Email services', 'envelope', '#EF4444', 2, $results);
$subs = [
    ['Cannot Send/Receive Email', 'Email sending or receiving issues', 'exclamation-triangle', '#EF4444', 1, 'high'],
    ['Email Recovery', 'Recover deleted or lost emails', 'undo', '#F59E0B', 2, 'high'],
    ['Mobile Email Setup', 'Setup email on mobile device', 'mobile-alt', '#3B82F6', 3, 'low'],
    ['Email Quota/Storage', 'Email storage full or quota issues', 'database', '#F59E0B', 4, 'medium'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptIT, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'medium');

// 3. Hardware
$parentId = findOrCreateParent($db, $deptIT, 'Hardware',
    ['Hardware'], 'Hardware issues', 'desktop', '#3B82F6', 3, $results);
$subs = [
    ['Desktop/Laptop Issue', 'Computer hardware problems', 'laptop', '#EF4444', 1, 'high'],
    ['Keyboard/Mouse', 'Input device issues', 'keyboard', '#F59E0B', 2, 'medium'],
    ['Phone/Headset', 'Phone or headset issues', 'headset', '#F59E0B', 3, 'medium'],
    ['UPS/Power', 'Power supply or UPS issues', 'bolt', '#EF4444', 4, 'high'],
    ['Monitor Problem', 'Display or monitor issues', 'tv', '#F59E0B', 5, 'medium'],
    ['Printer Issue', 'Printer hardware problems', 'print', '#F59E0B', 6, 'medium'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptIT, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'medium');

// 4. Software
$parentId = findOrCreateParent($db, $deptIT, 'Software',
    ['Software'], 'Software issues', 'code', '#10B981', 4, $results);
$subs = [
    ['Antivirus/Security', 'Security software issues', 'shield-alt', '#EF4444', 1, 'high'],
    ['Application Error', 'Application crashes or errors', 'exclamation-triangle', '#F59E0B', 2, 'medium'],
    ['Browser Issues', 'Web browser problems', 'globe', '#3B82F6', 3, 'medium'],
    ['License Request', 'Software license requests', 'file-contract', '#8B5CF6', 4, 'low'],
    ['MS Office Issues', 'Microsoft Office problems', 'file-word', '#3B82F6', 5, 'medium'],
    ['Software Installation', 'Install new software', 'download', '#10B981', 6, 'low'],
    ['Software Update/Upgrade', 'Update or upgrade software', 'sync', '#F59E0B', 7, 'low'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptIT, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'medium');

// 5. Network
$parentId = findOrCreateParent($db, $deptIT, 'Network',
    ['Network'], 'Network issues', 'wifi', '#F59E0B', 5, $results);
$subs = [
    ['Network Drive Access', 'Cannot access shared drives', 'hdd', '#F59E0B', 1, 'medium'],
    ['Network Printer', 'Network printer issues', 'print', '#F59E0B', 2, 'medium'],
    ['No Internet Connection', 'No internet access', 'exclamation-circle', '#EF4444', 3, 'high'],
    ['Slow Connection', 'Internet or network is slow', 'tachometer-alt', '#F59E0B', 4, 'medium'],
    ['VPN Issues', 'VPN connectivity problems', 'shield-alt', '#8B5CF6', 5, 'medium'],
    ['WiFi Problems', 'WiFi connectivity issues', 'wifi', '#EF4444', 6, 'high'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptIT, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'medium');

// 6. IT General Inquiry
$parentId = findOrCreateParent($db, $deptIT, 'IT General Inquiry',
    ['IT General Inquiry'], 'General IT questions', 'question-circle', '#6B7280', 6, $results);
$subs = [
    ['General IT Questions/How-To/Advice', 'General IT help', 'info-circle', '#6B7280', 1, 'low'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptIT, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'low');

// 7. OnBoarding
$parentId = findOrCreateParent($db, $deptIT, 'OnBoarding',
    ['OnBoarding'], 'New employee onboarding', 'user-plus', '#10B981', 7, $results);
$subs = [
    ['*HR to file Ticket', 'HR files onboarding ticket for new employee', 'user-tie', '#10B981', 1, 'low'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptIT, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'low');

// 8. OffBoarding
$parentId = findOrCreateParent($db, $deptIT, 'OffBoarding',
    ['OffBoarding'], 'Employee offboarding', 'user-minus', '#EF4444', 8, $results);
$subs = [
    ['*HR to file Ticket', 'HR files offboarding ticket for departing employee', 'user-times', '#EF4444', 1, 'low'],
];
$keepNames = [];
foreach ($subs as [$n, $d, $i, $c, $s, $p]) {
    $subId = ensureChild($db, $deptIT, $parentId, $n, $d, $i, $c, $s, $results);
    setPriority($db, $subId, $p);
    $keepNames[] = $n;
}
deactivateUnlisted($db, $parentId, $keepNames, $results);
setPriority($db, $parentId, 'low');

// Print sync results
echo "\n=== Sync Results ===\n";
foreach ($results as $line) echo "  $line\n";

// ═══ PHASE 2.5: POST-SYNC *HR TO FILE TICKET FIXUP ═══
echo "\n═══ POST-SYNC: *HR to file Ticket FIXUP ═══\n";

// Get real parent IDs by name
$stmt = $db->prepare("SELECT id FROM categories WHERE name = 'OnBoarding' AND department_id = ? AND parent_id IS NULL AND is_active = 1 LIMIT 1");
$stmt->execute([$deptIT]);
$onboardId = (int) $stmt->fetchColumn();
$stmt->closeCursor();
$stmt = null;

$stmt = $db->prepare("SELECT id FROM categories WHERE name = 'OffBoarding' AND department_id = ? AND parent_id IS NULL AND is_active = 1 LIMIT 1");
$stmt->execute([$deptIT]);
$offboardId = (int) $stmt->fetchColumn();
$stmt->closeCursor();
$stmt = null;

echo "  OnBoarding parent id=$onboardId, OffBoarding parent id=$offboardId\n";

// Delete any *HR rows with wrong parent_id (orphans from id=0 bug)
$stmt = $db->query("SELECT id, parent_id FROM categories WHERE name = '*HR to file Ticket'");
$allHR = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();
$stmt = null;

foreach ($allHR as $hr) {
    $pid = (int)$hr['parent_id'];
    if ($pid !== $onboardId && $pid !== $offboardId) {
        $db->exec("DELETE FROM category_priority_map WHERE category_id = " . (int)$hr['id']);
        $db->exec("DELETE FROM categories WHERE id = " . (int)$hr['id']);
        echo "  DELETED orphan *HR id={$hr['id']} (parent_id=$pid)\n";
    }
}

// Ensure exactly 1 active *HR under OnBoarding
$stmt = $db->prepare("SELECT id FROM categories WHERE name = '*HR to file Ticket' AND parent_id = ? AND is_active = 1 ORDER BY id");
$stmt->execute([$onboardId]);
$onHRs = $stmt->fetchAll(PDO::FETCH_COLUMN);
$stmt->closeCursor();
$stmt = null;

if (count($onHRs) == 0) {
    $maxB = getMaxCatId($db);
    $ins = $db->prepare("INSERT INTO categories (department_id, parent_id, name, description, icon, color, sort_order, is_active) VALUES (?, ?, '*HR to file Ticket', 'HR files onboarding ticket', 'user-tie', '#10B981', 1, 1)");
    $ins->execute([$deptIT, $onboardId]);
    $ins->closeCursor();
    $ins = null;
    $newId = getMaxCatId($db);
    setPriority($db, $newId, 'low');
    echo "  Created *HR under OnBoarding (id=$newId)\n";
} elseif (count($onHRs) > 1) {
    for ($i = 1; $i < count($onHRs); $i++) {
        $db->exec("DELETE FROM category_priority_map WHERE category_id = " . (int)$onHRs[$i]);
        $db->exec("DELETE FROM categories WHERE id = " . (int)$onHRs[$i]);
        echo "  DELETED extra *HR under OnBoarding (id={$onHRs[$i]})\n";
    }
} else {
    setPriority($db, (int)$onHRs[0], 'low');
    echo "  ✓ OnBoarding has 1 *HR (id={$onHRs[0]})\n";
}

// Ensure exactly 1 active *HR under OffBoarding
$stmt = $db->prepare("SELECT id FROM categories WHERE name = '*HR to file Ticket' AND parent_id = ? AND is_active = 1 ORDER BY id");
$stmt->execute([$offboardId]);
$offHRs = $stmt->fetchAll(PDO::FETCH_COLUMN);
$stmt->closeCursor();
$stmt = null;

if (count($offHRs) == 0) {
    $maxB = getMaxCatId($db);
    $ins = $db->prepare("INSERT INTO categories (department_id, parent_id, name, description, icon, color, sort_order, is_active) VALUES (?, ?, '*HR to file Ticket', 'HR files offboarding ticket', 'user-times', '#EF4444', 1, 1)");
    $ins->execute([$deptIT, $offboardId]);
    $ins->closeCursor();
    $ins = null;
    $newId = getMaxCatId($db);
    setPriority($db, $newId, 'low');
    echo "  Created *HR under OffBoarding (id=$newId)\n";
} elseif (count($offHRs) > 1) {
    for ($i = 1; $i < count($offHRs); $i++) {
        $db->exec("DELETE FROM category_priority_map WHERE category_id = " . (int)$offHRs[$i]);
        $db->exec("DELETE FROM categories WHERE id = " . (int)$offHRs[$i]);
        echo "  DELETED extra *HR under OffBoarding (id={$offHRs[$i]})\n";
    }
} else {
    setPriority($db, (int)$offHRs[0], 'low');
    echo "  ✓ OffBoarding has 1 *HR (id={$offHRs[0]})\n";
}

echo "\n";

// ═══ PHASE 3: FINAL VERIFICATION ═══
echo "═══ PHASE 3: VERIFICATION ═══\n\n";

// Use scalar subquery for priority to avoid JOIN duplicates
$stmt = $db->query("
    SELECT c.name AS parent, sc.name AS sub, sc.id AS sub_id, sc.parent_id,
           CASE WHEN c.department_id = $deptHR THEN 'HR' ELSE 'IT' END AS dept,
           (SELECT cpm.default_priority FROM category_priority_map cpm WHERE cpm.category_id = sc.id LIMIT 1) AS priority
    FROM categories c
    JOIN categories sc ON sc.parent_id = c.id AND sc.is_active = 1
    WHERE c.parent_id IS NULL AND c.is_active = 1
    ORDER BY dept, c.sort_order, sc.sort_order
");

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();
$stmt = null;
$currentDept = '';
printf("%-5s %-24s %-40s %-6s %-8s\n", "DEPT", "PARENT", "SUBCATEGORY", "ID", "PRIORITY");
echo str_repeat('─', 90) . "\n";
foreach ($rows as $r) {
    if ($r['dept'] !== $currentDept) {
        $currentDept = $r['dept'];
        echo "\n";
    }
    printf("%-5s %-24s %-40s %-6s %-8s\n", $r['dept'], $r['parent'], $r['sub'] ?? '(no subs)', $r['sub_id'], strtoupper($r['priority'] ?? '-'));
}

// Count check
$activeParents = $db->query("SELECT COUNT(*) FROM categories WHERE parent_id IS NULL AND is_active = 1")->fetchColumn();
$activeChildren = $db->query("SELECT COUNT(*) FROM categories WHERE parent_id IS NOT NULL AND is_active = 1")->fetchColumn();
$hrFile = $db->query("SELECT COUNT(*) FROM categories WHERE name = '*HR to file Ticket' AND is_active = 1")->fetchColumn();
$hrFileTotal = $db->query("SELECT COUNT(*) FROM categories WHERE name = '*HR to file Ticket'")->fetchColumn();

echo "\n═══ SUMMARY ═══\n";
echo "Active parents: $activeParents (expected: 12)\n";
echo "Active children: $activeChildren (expected: 42)\n";
echo "Active '*HR to file Ticket' rows: $hrFile (expected: 2)\n";
echo "Total '*HR to file Ticket' rows (incl inactive): $hrFileTotal\n";
echo ($activeParents == 12 && $activeChildren == 42 && $hrFile == 2) ? "\n✅ ALL GOOD!\n" : "\n⚠ Check counts above\n";
echo "</pre>";
