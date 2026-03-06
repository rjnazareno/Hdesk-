<?php
/**
 * Category & Priority Sync — March 2026 (Final)
 * ================================================
 * Synchronizes categories & subcategories to match the official
 * IT Help Desk SLA Guide spreadsheets (IT sheet + HR sheet).
 *
 * This script is IDEMPOTENT — safe to run multiple times.
 * It will create, rename, deactivate, and update priorities as needed.
 *
 * HR Categories (4 parents):
 *   1. Request a Document → COE (COC) [LOW], Certification of Leave [LOW], Others [LOW]
 *   2. Payroll            → Draft Payslip Discrepancy [HIGH], Post-Payroll Payslip Concerns [MEDIUM]
 *   3. Harley             → Log In Error [HIGH], Missing Log In/Log Out [LOW], Leave Inquiry [LOW]
 *   4. General Inquiry    → HMO Inquiry [MEDIUM], Others [LOW]
 *
 * IT Categories (8 parents):
 *   1. Access           → Account Deactivation [HIGH], Password Reset [HIGH], Account Locked [HIGH],
 *                          Permission Request [LOW], New Account Request [LOW], System Access Issue [HIGH]
 *   2. Email            → Cannot Send/Receive Email [HIGH], Email Recovery [HIGH],
 *                          Mobile Email Setup [LOW], Email Quota/Storage [MEDIUM]
 *   3. Hardware         → Desktop/Laptop Issue [HIGH], Keyboard/Mouse [MEDIUM], Phone/Headset [MEDIUM],
 *                          UPS/Power [HIGH], Monitor Problem [MEDIUM], Printer Issue [MEDIUM]
 *   4. Software         → Antivirus/Security [HIGH], Application Error [MEDIUM], Browser Issues [MEDIUM],
 *                          License Request [LOW], MS Office Issues [MEDIUM], Software Installation [LOW],
 *                          Software Update/Upgrade [LOW]
 *   5. Network          → Network Drive Access [MEDIUM], Network Printer [MEDIUM],
 *                          No Internet Connection [HIGH], Slow Connection [MEDIUM],
 *                          VPN Issues [MEDIUM], WiFi Problems [HIGH]
 *   6. IT General Inquiry → General IT Questions/How-To/Advice [LOW]
 *   7. OnBoarding       → *HR to file Ticket [LOW]
 *   8. OffBoarding      → *HR to file Ticket [LOW]
 *
 * Usage: http://localhost/IThelp/apply_category_sync_march2026.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$results = [];
$errors  = [];

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px'>\n";
echo "==============================================\n";
echo " Category & Priority Sync — March 2026 Final\n";
echo "==============================================\n\n";

try {
    $db->beginTransaction();

    // --------------------------------------------------
    // Helper statements (reused throughout)
    // --------------------------------------------------
    $findParent = $db->prepare(
        "SELECT id FROM categories WHERE name = :name AND department_id = :dept AND (parent_id IS NULL) LIMIT 1"
    );
    $findChild = $db->prepare(
        "SELECT id FROM categories WHERE name = :name AND parent_id = :parent LIMIT 1"
    );
    $insertParent = $db->prepare(
        "INSERT INTO categories (department_id, parent_id, name, description, icon, color, sort_order, is_active)
         VALUES (:dept, NULL, :name, :desc, :icon, :color, :sort, 1)"
    );
    $insertChild = $db->prepare(
        "INSERT INTO categories (department_id, parent_id, name, description, icon, color, sort_order, is_active)
         VALUES (:dept, :parent, :name, :desc, :icon, :color, :sort, 1)"
    );
    $renameCat = $db->prepare("UPDATE categories SET name = :name, description = :desc WHERE id = :id");
    $deactivateCat = $db->prepare("UPDATE categories SET is_active = 0 WHERE id = :id");
    $activateCat = $db->prepare("UPDATE categories SET is_active = 1 WHERE id = :id");
    $upsertPriority = $db->prepare(
        "INSERT INTO category_priority_map (category_id, default_priority)
         VALUES (:cid, :pri) ON DUPLICATE KEY UPDATE default_priority = VALUES(default_priority)"
    );

    // --------------------------------------------------
    // Fetch department IDs
    // --------------------------------------------------
    $deptHR = $db->query("SELECT id FROM departments WHERE code = 'HR' LIMIT 1")->fetchColumn();
    $deptIT = $db->query("SELECT id FROM departments WHERE code = 'IT' OR name LIKE '%Information Tech%' LIMIT 1")->fetchColumn();

    if (!$deptHR) throw new Exception("HR department not found in departments table.");
    if (!$deptIT) throw new Exception("IT department not found in departments table.");

    echo "Departments: HR=id$deptHR, IT=id$deptIT\n\n";

    // Helper: find-or-create a parent category (checks multiple possible old names)
    function findOrCreateParent($db, $findParent, $insertParent, $deptId, $targetName, $oldNames, $desc, $icon, $color, $sort, &$results) {
        // Try current target name first
        $findParent->execute([':name' => $targetName, ':dept' => $deptId]);
        $id = $findParent->fetchColumn();
        if ($id) return $id;

        // Try old names and rename if found
        foreach ($oldNames as $old) {
            $findParent->execute([':name' => $old, ':dept' => $deptId]);
            $id = $findParent->fetchColumn();
            if ($id) {
                $db->prepare("UPDATE categories SET name = :n, description = :d WHERE id = :id")
                   ->execute([':n' => $targetName, ':d' => $desc, ':id' => $id]);
                $results[] = "Renamed '$old' → '$targetName' (id=$id)";
                return $id;
            }
        }

        // Create fresh
        $insertParent->execute([':dept' => $deptId, ':name' => $targetName, ':desc' => $desc, ':icon' => $icon, ':color' => $color, ':sort' => $sort]);
        $id = $db->lastInsertId();
        $results[] = "Created parent '$targetName' (id=$id)";
        return $id;
    }

    // Helper: ensure a subcategory exists under a parent, return its ID
    function ensureChild($db, $findChild, $insertChild, $deptId, $parentId, $name, $desc, $icon, $color, $sort, &$results) {
        $findChild->execute([':name' => $name, ':parent' => $parentId]);
        $id = $findChild->fetchColumn();
        if ($id) {
            // Make sure it's active
            $db->prepare("UPDATE categories SET is_active = 1 WHERE id = :id")->execute([':id' => $id]);
            return $id;
        }
        $insertChild->execute([':dept' => $deptId, ':parent' => $parentId, ':name' => $name, ':desc' => $desc, ':icon' => $icon, ':color' => $color, ':sort' => $sort]);
        $id = $db->lastInsertId();
        $results[] = "  + Added '$name' (id=$id)";
        return $id;
    }

    // Helper: deactivate any children of $parentId whose names are NOT in $keepNames
    function deactivateUnlisted($db, $parentId, $keepNames, &$results) {
        $placeholders = implode(',', array_fill(0, count($keepNames), '?'));
        $stmt = $db->prepare(
            "SELECT id, name FROM categories WHERE parent_id = ? AND is_active = 1 AND name NOT IN ($placeholders)"
        );
        $params = array_merge([$parentId], $keepNames);
        $stmt->execute($params);
        $old = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($old as $row) {
            $db->prepare("UPDATE categories SET is_active = 0 WHERE id = ?")->execute([$row['id']]);
            $results[] = "  - Deactivated '{$row['name']}' (id={$row['id']})";
        }
    }


    // =====================================================
    // SECTION 1: HR CATEGORIES
    // =====================================================
    echo "--- HR CATEGORIES ---\n";

    // ---- 1a. Request a Document ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptHR,
        'Request a Document',
        ['Certificate of Employment (COE)', 'Request a Document'],
        'Request documents from HR',
        'file-certificate', '#10B981', 1, $results
    );

    $hrReqDocSubs = [
        ['Certificate of Employment (COC)', 'Request for COE/COC document', 'file-alt', '#10B981', 1, 'low'],
        ['Certification of Leave', 'Request for certification of leave records', 'file-signature', '#10B981', 2, 'low'],
        ['Others', 'Other document requests', 'file', '#6B7280', 3, 'low'],
    ];
    $keepNames = [];
    foreach ($hrReqDocSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        $subId = ensureChild($db, $findChild, $insertChild, $deptHR, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $upsertPriority->execute([':cid' => $parentId, ':pri' => 'low']);
    $results[] = "HR: 'Request a Document' synced (3 subs)";

    // ---- 1b. Payroll ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptHR,
        'Payroll',
        ['Salary Dispute', 'Payroll Inquiry', 'Payroll'],
        'Payroll and payslip concerns',
        'money-bill-wave', '#EF4444', 2, $results
    );

    $hrPayrollSubs = [
        ['Draft Payslip Discrepancy', 'Discrepancy found in draft payslip', 'exclamation-circle', '#EF4444', 1, 'high'],
        ['Post-Payroll Payslip Concerns', 'Payslip concerns after payroll has been processed', 'file-invoice-dollar', '#F59E0B', 2, 'medium'],
    ];
    $keepNames = [];
    foreach ($hrPayrollSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        // Check for old names that should map to new ones
        if ($name === 'Draft Payslip Discrepancy') {
            // Could be 'Draft Payslip', 'Payslip Disputes', etc.
            foreach (['Draft Payslip', 'Payslip Disputes', 'Payslip Dispute (a day before cutoff)', 'Payslip Dispute (after cutoff)'] as $old) {
                $findChild->execute([':name' => $old, ':parent' => $parentId]);
                $oldId = $findChild->fetchColumn();
                if ($oldId) {
                    $renameCat->execute([':name' => $name, ':desc' => $desc, ':id' => $oldId]);
                    $results[] = "  Renamed '$old' → '$name' (id=$oldId)";
                    break;
                }
            }
        }
        $subId = ensureChild($db, $findChild, $insertChild, $deptHR, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $upsertPriority->execute([':cid' => $parentId, ':pri' => 'medium']);
    $results[] = "HR: 'Payroll' synced (2 subs)";

    // ---- 1c. Harley ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptHR,
        'Harley',
        ['Timekeeping concerns', 'Harley (Timekeeping)', 'Timekeeping Concerns', 'Harley'],
        'Harley timekeeping system concerns',
        'clock', '#F59E0B', 3, $results
    );

    $hrTimekeepingSubs = [
        ['Log In Error', 'Cannot log in to Harley timekeeping system', 'exclamation-triangle', '#EF4444', 1, 'high'],
        ['Missing Log In/Log Out', 'Missing or incorrect time entries in Harley', 'user-clock', '#F59E0B', 2, 'low'],
        ['Leave Inquiry', 'Questions about leave balances, leave policies', 'calendar-check', '#8B5CF6', 3, 'low'],
    ];
    $keepNames = [];
    foreach ($hrTimekeepingSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        $subId = ensureChild($db, $findChild, $insertChild, $deptHR, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $upsertPriority->execute([':cid' => $parentId, ':pri' => 'medium']);
    $results[] = "HR: 'Harley' synced (3 subs)";

    // ---- 1d. General Inquiry ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptHR,
        'General Inquiry',
        ['HR General Inquiry', 'General Inquiry'],
        'General HR inquiries',
        'question-circle', '#6B7280', 4, $results
    );

    $hrGeneralSubs = [
        ['HMO Inquiry', 'Health maintenance organization inquiries', 'heartbeat', '#EF4444', 1, 'medium'],
        ['Others', 'Other general HR inquiries', 'info-circle', '#6B7280', 2, 'low'],
    ];
    $keepNames = [];
    foreach ($hrGeneralSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        $subId = ensureChild($db, $findChild, $insertChild, $deptHR, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $upsertPriority->execute([':cid' => $parentId, ':pri' => 'low']);
    $results[] = "HR: 'General Inquiry' synced (2 subs)";

    // ---- 1e. Deactivate "Leave" parent if it exists (moved under Timekeeping) ----
    $findParent->execute([':name' => 'Leave', ':dept' => $deptHR]);
    $oldLeaveId = $findParent->fetchColumn();
    if ($oldLeaveId) {
        $deactivateCat->execute([':id' => $oldLeaveId]);
        $results[] = "HR: Deactivated standalone 'Leave' parent (id=$oldLeaveId) — Leave Inquiry now under Timekeeping Concerns";
        // Also deactivate its children
        $childStmt = $db->prepare("SELECT id, name FROM categories WHERE parent_id = ? AND is_active = 1");
        $childStmt->execute([$oldLeaveId]);
        foreach ($childStmt->fetchAll(PDO::FETCH_ASSOC) as $child) {
            $deactivateCat->execute([':id' => $child['id']]);
            $results[] = "  - Deactivated old Leave sub: '{$child['name']}' (id={$child['id']})";
        }
    }
    // Also try "Leave concerns"
    $findParent->execute([':name' => 'Leave concerns', ':dept' => $deptHR]);
    $oldLeaveId2 = $findParent->fetchColumn();
    if ($oldLeaveId2 && $oldLeaveId2 != ($oldLeaveId ?? 0)) {
        $deactivateCat->execute([':id' => $oldLeaveId2]);
        $results[] = "HR: Deactivated 'Leave concerns' parent (id=$oldLeaveId2)";
        $childStmt = $db->prepare("SELECT id, name FROM categories WHERE parent_id = ? AND is_active = 1");
        $childStmt->execute([$oldLeaveId2]);
        foreach ($childStmt->fetchAll(PDO::FETCH_ASSOC) as $child) {
            $deactivateCat->execute([':id' => $child['id']]);
            $results[] = "  - Deactivated sub: '{$child['name']}' (id={$child['id']})";
        }
    }

    // Deactivate any other HR parents not in the final 4
    $validHrParents = ['Request a Document', 'Payroll', 'Harley', 'General Inquiry'];
    $hrParentsStmt = $db->prepare(
        "SELECT id, name FROM categories WHERE department_id = ? AND parent_id IS NULL AND is_active = 1"
    );
    $hrParentsStmt->execute([$deptHR]);
    foreach ($hrParentsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!in_array($row['name'], $validHrParents)) {
            $deactivateCat->execute([':id' => $row['id']]);
            $results[] = "HR: Deactivated unlisted parent '{$row['name']}' (id={$row['id']})";
            // Deactivate children too
            $childStmt2 = $db->prepare("SELECT id, name FROM categories WHERE parent_id = ? AND is_active = 1");
            $childStmt2->execute([$row['id']]);
            foreach ($childStmt2->fetchAll(PDO::FETCH_ASSOC) as $child) {
                $deactivateCat->execute([':id' => $child['id']]);
                $results[] = "  - Deactivated sub: '{$child['name']}' (id={$child['id']})";
            }
        }
    }


    // =====================================================
    // SECTION 2: IT CATEGORIES
    // =====================================================
    echo "\n--- IT CATEGORIES ---\n";

    // ---- 2a. Access ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptIT,
        'Access', [], 'Account and access management',
        'user-shield', '#8B5CF6', 1, $results
    );
    $itAccessSubs = [
        ['Account Deactivation', 'Request to disable/remove account', 'user-minus', '#8B5CF6', 1, 'high'],
        ['Password Reset', 'Cannot login, forgot password', 'unlock', '#8B5CF6', 2, 'high'],
        ['Account Locked', 'User account is locked out', 'lock', '#8B5CF6', 3, 'high'],
        ['Permission Request', 'Request access to systems/folders', 'user-shield', '#8B5CF6', 4, 'low'],
        ['New Account Request', 'Create new user account', 'user-plus', '#8B5CF6', 5, 'low'],
        ['System Access Issue', 'Cannot access specific system/app', 'exclamation-triangle', '#8B5CF6', 6, 'high'],
    ];
    $keepNames = [];
    foreach ($itAccessSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        $subId = ensureChild($db, $findChild, $insertChild, $deptIT, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $results[] = "IT: 'Access' synced (6 subs)";

    // ---- 2b. Email ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptIT,
        'Email', [], 'Email and messaging issues',
        'envelope', '#EF4444', 2, $results
    );
    $itEmailSubs = [
        ['Cannot Send/Receive Email', 'Email delivery issues', 'envelope', '#EF4444', 1, 'high'],
        ['Email Recovery', 'Recover deleted emails', 'undo', '#EF4444', 2, 'high'],
        ['Mobile Email Setup', 'Email on phone/tablet', 'mobile-alt', '#EF4444', 3, 'low'],
        ['Email Quota/Storage', 'Mailbox full or storage issues', 'database', '#EF4444', 4, 'medium'],
    ];
    $keepNames = [];
    foreach ($itEmailSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        $subId = ensureChild($db, $findChild, $insertChild, $deptIT, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $results[] = "IT: 'Email' synced (4 subs — removed Distribution List Request, Outlook Configuration)";

    // ---- 2c. Hardware ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptIT,
        'Hardware', [], 'Hardware and equipment issues',
        'desktop', '#3B82F6', 3, $results
    );
    $itHardwareSubs = [
        ['Desktop/Laptop Issue', 'Computer not starting, slow performance, crashes', 'desktop', '#3B82F6', 1, 'high'],
        ['Keyboard/Mouse', 'Input device issues, not responding', 'keyboard', '#3B82F6', 2, 'medium'],
        ['Phone/Headset', 'Desk phone, IP phone, headset issues', 'phone-alt', '#3B82F6', 3, 'medium'],
        ['UPS/Power', 'Power supply issues, battery backup', 'plug', '#3B82F6', 4, 'high'],
        ['Monitor Problem', 'Display issues, no signal, flickering', 'tv', '#3B82F6', 5, 'medium'],
        ['Printer Issue', 'Printing problems, paper jam, connectivity', 'print', '#3B82F6', 6, 'medium'],
    ];
    $keepNames = [];
    foreach ($itHardwareSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        $subId = ensureChild($db, $findChild, $insertChild, $deptIT, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $results[] = "IT: 'Hardware' synced (6 subs — removed New Hardware Request)";

    // ---- 2d. Software ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptIT,
        'Software', [], 'Software and application issues',
        'code', '#10B981', 4, $results
    );
    $itSoftwareSubs = [
        ['Antivirus/Security', 'Virus alerts, security software issues', 'shield-alt', '#10B981', 1, 'high'],
        ['Application Error', 'Software crashes, errors, bugs', 'bug', '#10B981', 2, 'medium'],
        ['Browser Issues', 'Chrome, Edge, Firefox problems', 'globe', '#10B981', 3, 'medium'],
        ['License Request', 'Software license activation or renewal', 'key', '#10B981', 4, 'low'],
        ['MS Office Issues', 'Word, Excel, PowerPoint, Outlook problems', 'file-word', '#10B981', 5, 'medium'],
        ['Software Installation', 'New software installation request', 'download', '#10B981', 6, 'low'],
        ['Software Update/Upgrade', 'Update or upgrade existing software', 'sync', '#10B981', 7, 'low'],
    ];
    $keepNames = [];
    foreach ($itSoftwareSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        $subId = ensureChild($db, $findChild, $insertChild, $deptIT, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $results[] = "IT: 'Software' synced (7 subs)";

    // ---- 2e. Network ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptIT,
        'Network', [], 'Network and connectivity issues',
        'network-wired', '#F59E0B', 5, $results
    );
    $itNetworkSubs = [
        ['Network Drive Access', 'Shared folder/drive access issues', 'folder-open', '#F59E0B', 1, 'medium'],
        ['Network Printer', 'Cannot connect to network printer', 'print', '#F59E0B', 2, 'medium'],
        ['No Internet Connection', 'Cannot connect to internet', 'wifi-slash', '#F59E0B', 3, 'high'],
        ['Slow Connection', 'Internet/network speed issues', 'tachometer-alt', '#F59E0B', 4, 'medium'],
        ['VPN Issues', 'Cannot connect or use VPN', 'network-wired', '#F59E0B', 5, 'medium'],
        ['WiFi Problems', 'Wireless connectivity issues', 'wifi', '#F59E0B', 6, 'high'],
    ];
    $keepNames = [];
    foreach ($itNetworkSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        $subId = ensureChild($db, $findChild, $insertChild, $deptIT, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $results[] = "IT: 'Network' synced (6 subs)";

    // ---- 2f. IT General Inquiry ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptIT,
        'IT General Inquiry', [], 'General IT questions and requests',
        'question-circle', '#6B7280', 6, $results
    );
    $itGenSubs = [
        ['General IT Questions/How-To/Advice', 'General IT questions, how-to guides, and advice', 'question-circle', '#6B7280', 1, 'low'],
    ];
    $keepNames = [];
    foreach ($itGenSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        // Check for old name "General IT"
        $findChild->execute([':name' => 'General IT', ':parent' => $parentId]);
        $oldGenId = $findChild->fetchColumn();
        if ($oldGenId) {
            $renameCat->execute([':name' => $name, ':desc' => $desc, ':id' => $oldGenId]);
            $results[] = "  Renamed 'General IT' → '$name' (id=$oldGenId)";
        }
        $subId = ensureChild($db, $findChild, $insertChild, $deptIT, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $upsertPriority->execute([':cid' => $parentId, ':pri' => 'low']);
    $results[] = "IT: 'IT General Inquiry' synced (1 sub)";

    // ---- 2g. OnBoarding ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptIT,
        'OnBoarding', [], 'New employee onboarding IT setup',
        'user-plus', '#10B981', 7, $results
    );
    $onbSubs = [
        ['*HR to file Ticket', 'Onboarding request — HR files on behalf of new employee', 'user-plus', '#10B981', 1, 'low'],
    ];
    $keepNames = [];
    foreach ($onbSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        $subId = ensureChild($db, $findChild, $insertChild, $deptIT, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $upsertPriority->execute([':cid' => $parentId, ':pri' => 'low']);
    $results[] = "IT: 'OnBoarding' synced (1 sub)";

    // ---- 2h. OffBoarding ----
    $parentId = findOrCreateParent($db, $findParent, $insertParent, $deptIT,
        'OffBoarding', [], 'Employee offboarding IT deactivation',
        'user-minus', '#EF4444', 8, $results
    );
    $offbSubs = [
        ['*HR to file Ticket', 'Offboarding request — HR files for departing employee', 'user-minus', '#EF4444', 1, 'low'],
    ];
    $keepNames = [];
    foreach ($offbSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
        $subId = ensureChild($db, $findChild, $insertChild, $deptIT, $parentId, $name, $desc, $icon, $color, $sort, $results);
        $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        $keepNames[] = $name;
    }
    deactivateUnlisted($db, $parentId, $keepNames, $results);
    $upsertPriority->execute([':cid' => $parentId, ':pri' => 'low']);
    $results[] = "IT: 'OffBoarding' synced (1 sub)";

    // Deactivate any IT parent categories not in the final 8
    $validItParents = ['Access', 'Email', 'Hardware', 'Software', 'Network', 'IT General Inquiry', 'OnBoarding', 'OffBoarding'];
    $itParentsStmt = $db->prepare(
        "SELECT id, name FROM categories WHERE department_id = ? AND parent_id IS NULL AND is_active = 1"
    );
    $itParentsStmt->execute([$deptIT]);
    foreach ($itParentsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if (!in_array($row['name'], $validItParents)) {
            $deactivateCat->execute([':id' => $row['id']]);
            $results[] = "IT: Deactivated unlisted parent '{$row['name']}' (id={$row['id']})";
            $childStmt3 = $db->prepare("SELECT id, name FROM categories WHERE parent_id = ? AND is_active = 1");
            $childStmt3->execute([$row['id']]);
            foreach ($childStmt3->fetchAll(PDO::FETCH_ASSOC) as $child) {
                $deactivateCat->execute([':id' => $child['id']]);
                $results[] = "  - Deactivated sub: '{$child['name']}' (id={$child['id']})";
            }
        }
    }


    // =====================================================
    // SECTION 3: SLA POLICIES (ensure correct)
    // =====================================================
    echo "\n--- SLA POLICIES ---\n";

    // Global SLA
    $db->exec("UPDATE sla_policies SET response_time = 1440 WHERE is_active = 1");
    $db->exec("UPDATE sla_policies SET resolution_time = 1440 WHERE priority = 'high' AND is_active = 1");
    $db->exec("UPDATE sla_policies SET resolution_time = 4320 WHERE priority = 'medium' AND is_active = 1");
    $db->exec("UPDATE sla_policies SET resolution_time = 7200 WHERE priority = 'low' AND is_active = 1");
    $results[] = "Global SLA: all 24h response; High=24h, Med=72h, Low=120h resolution";

    // Department SLA (create table if needed)
    $db->exec("CREATE TABLE IF NOT EXISTS sla_department_policies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        department_id INT NOT NULL,
        priority ENUM('low','medium','high') NOT NULL,
        response_time INT NOT NULL,
        resolution_time INT NOT NULL,
        is_business_hours TINYINT(1) DEFAULT 0,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_dept_priority (department_id, priority),
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $upsertDeptSla = $db->prepare(
        "INSERT INTO sla_department_policies (department_id, priority, response_time, resolution_time)
         VALUES (:dept, :p, :rt, :res)
         ON DUPLICATE KEY UPDATE response_time=VALUES(response_time), resolution_time=VALUES(resolution_time), updated_at=NOW()"
    );

    // IT: High=24h/48h, Medium=24h/96h, Low=24h/120h
    foreach ([['high',1440,2880],['medium',1440,5760],['low',1440,7200]] as [$p,$rt,$res]) {
        $upsertDeptSla->execute([':dept'=>$deptIT,':p'=>$p,':rt'=>$rt,':res'=>$res]);
    }
    $results[] = "IT SLA: High=24h/48h, Medium=24h/96h, Low=24h/120h";

    // HR: High=24h/24h, Medium=24h/72h, Low=24h/120h
    foreach ([['high',1440,1440],['medium',1440,4320],['low',1440,7200]] as [$p,$rt,$res]) {
        $upsertDeptSla->execute([':dept'=>$deptHR,':p'=>$p,':rt'=>$rt,':res'=>$res]);
    }
    $results[] = "HR SLA: High=24h/24h, Medium=24h/72h, Low=24h/120h";


    // =====================================================
    // COMMIT
    // =====================================================
    $db->commit();
    echo "\n✅ Migration committed successfully!\n\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "\n❌ Migration ROLLED BACK: " . htmlspecialchars($e->getMessage()) . "\n\n";
    $errors[] = "ROLLBACK: " . $e->getMessage();
}


// =====================================================
// VERIFICATION
// =====================================================
echo "=== Results ===\n";
foreach ($results as $r) echo "  ✓ $r\n";
if ($errors) {
    echo "\n=== Errors ===\n";
    foreach ($errors as $e) echo "  ✗ $e\n";
}

echo "\n=== Active Categories (Final State) ===\n";
$verify = $db->query("
    SELECT d.code AS dept,
           c.name AS parent_category,
           sub.name AS issue_type,
           sub.is_active,
           COALESCE(cpm.default_priority, '—') AS priority
    FROM categories c
    LEFT JOIN categories sub ON sub.parent_id = c.id AND sub.is_active = 1
    LEFT JOIN departments d ON c.department_id = d.id
    LEFT JOIN category_priority_map cpm ON cpm.category_id = sub.id
    WHERE c.parent_id IS NULL AND c.is_active = 1
    ORDER BY d.code, c.sort_order, sub.sort_order
")->fetchAll(PDO::FETCH_ASSOC);

echo str_pad('Dept', 6) . str_pad('Category', 25) . str_pad('Issue Type', 40) . "Priority\n";
echo str_repeat('-', 80) . "\n";
foreach ($verify as $row) {
    echo str_pad($row['dept'] ?? '?', 6)
       . str_pad($row['parent_category'], 25)
       . str_pad($row['issue_type'] ?? '(no subs)', 40)
       . strtoupper($row['priority']) . "\n";
}

echo "\n=== Department SLA ===\n";
try {
    $sla = $db->query("
        SELECT d.code, sdp.priority, sdp.response_time, sdp.resolution_time
        FROM sla_department_policies sdp
        JOIN departments d ON sdp.department_id = d.id
        WHERE sdp.is_active = 1
        ORDER BY d.code, FIELD(sdp.priority, 'high', 'medium', 'low')
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($sla as $row) {
        $respH = $row['response_time'] / 60;
        $resH  = $row['resolution_time'] / 60;
        echo "  {$row['code']} {$row['priority']}: Response={$respH}h, Resolution={$resH}h\n";
    }
} catch (Exception $e) {
    echo "  (sla_department_policies table not found)\n";
}

echo "\n=== Deactivated Categories ===\n";
$deactivated = $db->query("
    SELECT d.code AS dept, COALESCE(pc.name, '—') AS parent, c.name
    FROM categories c
    LEFT JOIN categories pc ON c.parent_id = pc.id
    LEFT JOIN departments d ON c.department_id = d.id
    WHERE c.is_active = 0
    ORDER BY d.code, c.name
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($deactivated as $row) {
    echo "  [{$row['dept']}] {$row['parent']} → {$row['name']}\n";
}

echo "\n</pre>";
