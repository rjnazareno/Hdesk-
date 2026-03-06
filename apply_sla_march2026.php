<?php
/**
 * SLA & Category Migration - March 2026
 * ========================================
 * Applies the new SLA guide changes for both HR and IT departments.
 *
 * HR Changes:
 *   - Rename parent categories to match new SLA guide
 *   - Replace subcategories with new issue types
 *   - Update priority mappings
 *
 * IT Changes:
 *   - Update priority mappings (Account Deactivation → HIGH, Email Recovery → HIGH)
 *   - Add OnBoarding / OffBoarding categories
 *   - Remove obsolete subcategories (Distribution List Request, Outlook Configuration, New Hardware Request)
 *   - Rename "General IT" → "General IT Questions/How-To/Advice"
 *
 * SLA:
 *   - Global SLA: all 24h response
 *   - Department SLA already correct (IT: 48h/96h/120h, HR: 24h/72h/120h)
 *
 * Usage: php apply_sla_march2026.php
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$results = [];
$errors  = [];

echo "============================================\n";
echo " SLA & Category Migration — March 2026\n";
echo "============================================\n\n";

try {
    $db->beginTransaction();

    // =====================================================
    // 1. GLOBAL SLA POLICIES — All 24h response
    // =====================================================
    echo "1. Updating global SLA policies ...\n";

    $db->exec("UPDATE sla_policies SET response_time = 1440, resolution_time = 1440, is_business_hours = 0 WHERE priority = 'high' AND is_active = 1");
    $db->exec("UPDATE sla_policies SET response_time = 1440, resolution_time = 4320, is_business_hours = 0 WHERE priority = 'medium' AND is_active = 1");
    $db->exec("UPDATE sla_policies SET response_time = 1440, resolution_time = 7200, is_business_hours = 0 WHERE priority = 'low' AND is_active = 1");
    $db->exec("UPDATE sla_policies SET is_active = 0 WHERE priority = 'urgent'");
    $results[] = "Global SLA: HIGH=24h/24h, MEDIUM=24h/72h, LOW=24h/120h (response all 24h)";

    // =====================================================
    // 2. DEPARTMENT-SPECIFIC SLA POLICIES (upsert)
    // =====================================================
    echo "2. Upserting department SLA policies ...\n";

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

    $deptIT = $db->query("SELECT id FROM departments WHERE code='IT' OR name LIKE '%IT%' OR name LIKE '%Information Tech%' LIMIT 1")->fetchColumn();
    $deptHR = $db->query("SELECT id FROM departments WHERE code='HR' LIMIT 1")->fetchColumn();

    $upsertDeptSla = $db->prepare(
        "INSERT INTO sla_department_policies (department_id, priority, response_time, resolution_time)
         VALUES (:dept, :p, :rt, :res)
         ON DUPLICATE KEY UPDATE response_time=VALUES(response_time), resolution_time=VALUES(resolution_time), updated_at=NOW()"
    );

    if ($deptIT) {
        // IT SLA: High=24h/48h, Medium=24h/96h, Low=24h/120h
        foreach ([['high',1440,2880],['medium',1440,5760],['low',1440,7200]] as [$p,$rt,$res]) {
            $upsertDeptSla->execute([':dept'=>$deptIT,':p'=>$p,':rt'=>$rt,':res'=>$res]);
        }
        $results[] = "IT dept SLA: High=24h/48h, Medium=24h/96h, Low=24h/120h";
    } else {
        $errors[] = "IT department not found in departments table.";
    }

    if ($deptHR) {
        // HR SLA: High=24h/24h, Medium=24h/72h, Low=24h/120h
        foreach ([['high',1440,1440],['medium',1440,4320],['low',1440,7200]] as [$p,$rt,$res]) {
            $upsertDeptSla->execute([':dept'=>$deptHR,':p'=>$p,':rt'=>$rt,':res'=>$res]);
        }
        $results[] = "HR dept SLA: High=24h/24h, Medium=24h/72h, Low=24h/120h";
    } else {
        $errors[] = "HR department not found in departments table.";
    }

    // =====================================================
    // 3. HR CATEGORY RESTRUCTURE
    // =====================================================
    echo "3. Restructuring HR categories ...\n";

    if (!$deptHR) {
        throw new Exception("Cannot proceed — HR department not found.");
    }

    // Helper: upsert a category (find by name+parent or insert)
    $findCat = $db->prepare("SELECT id FROM categories WHERE name = :name AND department_id = :dept AND parent_id IS NULL LIMIT 1");
    $findChild = $db->prepare("SELECT id FROM categories WHERE name = :name AND parent_id = :parent LIMIT 1");
    $insertCat = $db->prepare("INSERT INTO categories (department_id, parent_id, name, description, icon, color, sort_order, is_active) 
                                VALUES (:dept, :parent, :name, :desc, :icon, :color, :sort, 1)");
    $renameCat = $db->prepare("UPDATE categories SET name = :name, description = :desc WHERE id = :id");
    $deactivateCat = $db->prepare("UPDATE categories SET is_active = 0 WHERE id = :id");
    $upsertPriority = $db->prepare("INSERT INTO category_priority_map (category_id, default_priority)
                                     VALUES (:cid, :pri) ON DUPLICATE KEY UPDATE default_priority = VALUES(default_priority)");

    // --- 3a. "Certificate of Employment (COE)" → "Request a Document" ---
    $findCat->execute([':name' => 'Certificate of Employment (COE)', ':dept' => $deptHR]);
    $coeId = $findCat->fetchColumn();
    if (!$coeId) {
        // Try alternate names
        $findCat->execute([':name' => 'Request a Document', ':dept' => $deptHR]);
        $coeId = $findCat->fetchColumn();
    }
    if ($coeId) {
        $renameCat->execute([':name' => 'Request a Document', ':desc' => 'Request documents from HR', ':id' => $coeId]);
        $results[] = "Renamed COE → 'Request a Document' (id=$coeId)";

        // Deactivate old sub-categories
        foreach (['Single Document', 'With other documents'] as $oldSub) {
            $findChild->execute([':name' => $oldSub, ':parent' => $coeId]);
            $oldId = $findChild->fetchColumn();
            if ($oldId) {
                $deactivateCat->execute([':id' => $oldId]);
                $results[] = "  Deactivated old sub: '$oldSub' (id=$oldId)";
            }
        }

        // Add new subcategories
        $newSubs = [
            ['Certificate of Employment (COC)', 'Request for COE/COC document', 'file-alt', '#10B981', 1, 'low'],
            ['Certification of Leave', 'Request for certification of leave records', 'file-signature', '#10B981', 2, 'low'],
            ['Others', 'Other document requests', 'file', '#6B7280', 3, 'low'],
        ];
        foreach ($newSubs as [$name, $desc, $icon, $color, $sort, $pri]) {
            $findChild->execute([':name' => $name, ':parent' => $coeId]);
            $existId = $findChild->fetchColumn();
            if (!$existId) {
                $insertCat->execute([':dept' => $deptHR, ':parent' => $coeId, ':name' => $name, ':desc' => $desc, ':icon' => $icon, ':color' => $color, ':sort' => $sort]);
                $existId = $db->lastInsertId();
                $results[] = "  Added sub: '$name' (id=$existId)";
            }
            $upsertPriority->execute([':cid' => $existId, ':pri' => $pri]);
        }
    } else {
        $errors[] = "HR parent 'Certificate of Employment (COE)' not found — creating fresh.";
        $insertCat->execute([':dept' => $deptHR, ':parent' => null, ':name' => 'Request a Document', ':desc' => 'Request documents from HR', ':icon' => 'file-alt', ':color' => '#10B981', ':sort' => 1]);
        $coeId = $db->lastInsertId();
        foreach ([
            ['Certificate of Employment (COC)', 'Request for COE/COC document', 'file-alt', '#10B981', 1, 'low'],
            ['Certification of Leave', 'Request for certification of leave records', 'file-signature', '#10B981', 2, 'low'],
            ['Others', 'Other document requests', 'file', '#6B7280', 3, 'low'],
        ] as [$name, $desc, $icon, $color, $sort, $pri]) {
            $insertCat->execute([':dept' => $deptHR, ':parent' => $coeId, ':name' => $name, ':desc' => $desc, ':icon' => $icon, ':color' => $color, ':sort' => $sort]);
            $upsertPriority->execute([':cid' => $db->lastInsertId(), ':pri' => $pri]);
        }
        $results[] = "Created 'Request a Document' with 3 subcategories (fresh)";
    }
    // Map parent fallback
    $upsertPriority->execute([':cid' => $coeId, ':pri' => 'low']);

    // --- 3b. "Salary Dispute" → "Payroll" ---
    $findCat->execute([':name' => 'Salary Dispute', ':dept' => $deptHR]);
    $salaryId = $findCat->fetchColumn();
    if (!$salaryId) {
        $findCat->execute([':name' => 'Payroll', ':dept' => $deptHR]);
        $salaryId = $findCat->fetchColumn();
    }
    if ($salaryId) {
        $renameCat->execute([':name' => 'Payroll', ':desc' => 'Payroll and payslip concerns', ':id' => $salaryId]);
        $results[] = "Renamed 'Salary Dispute' → 'Payroll' (id=$salaryId)";

        // Deactivate old subs
        foreach (['Payslip Disputes', 'Payslip Dispute (a day before cutoff)', 'Payslip Dispute (after cutoff)'] as $old) {
            $findChild->execute([':name' => $old, ':parent' => $salaryId]);
            $oldId = $findChild->fetchColumn();
            if ($oldId) {
                $deactivateCat->execute([':id' => $oldId]);
                $results[] = "  Deactivated old sub: '$old' (id=$oldId)";
            }
        }

        // Rename Draft Payslip → Draft Payslip Discrepancy (if exists)
        $findChild->execute([':name' => 'Draft Payslip', ':parent' => $salaryId]);
        $draftId = $findChild->fetchColumn();
        if ($draftId) {
            $renameCat->execute([':name' => 'Draft Payslip Discrepancy', ':desc' => 'Discrepancy found in draft payslip', ':id' => $draftId]);
            $upsertPriority->execute([':cid' => $draftId, ':pri' => 'high']);
            $results[] = "  Renamed 'Draft Payslip' → 'Draft Payslip Discrepancy' [HIGH] (id=$draftId)";
        } else {
            // Check if already renamed
            $findChild->execute([':name' => 'Draft Payslip Discrepancy', ':parent' => $salaryId]);
            $draftId = $findChild->fetchColumn();
            if (!$draftId) {
                $insertCat->execute([':dept' => $deptHR, ':parent' => $salaryId, ':name' => 'Draft Payslip Discrepancy', ':desc' => 'Discrepancy found in draft payslip', ':icon' => 'exclamation-circle', ':color' => '#EF4444', ':sort' => 1]);
                $draftId = $db->lastInsertId();
                $results[] = "  Added sub: 'Draft Payslip Discrepancy' (id=$draftId)";
            }
            $upsertPriority->execute([':cid' => $draftId, ':pri' => 'high']);
        }

        // Add Post-Payroll Payslip Concerns
        $findChild->execute([':name' => 'Post-Payroll Payslip Concerns', ':parent' => $salaryId]);
        $postId = $findChild->fetchColumn();
        if (!$postId) {
            $insertCat->execute([':dept' => $deptHR, ':parent' => $salaryId, ':name' => 'Post-Payroll Payslip Concerns', ':desc' => 'Payslip concerns after payroll has been processed', ':icon' => 'file-invoice-dollar', ':color' => '#F59E0B', ':sort' => 2]);
            $postId = $db->lastInsertId();
            $results[] = "  Added sub: 'Post-Payroll Payslip Concerns' (id=$postId)";
        }
        $upsertPriority->execute([':cid' => $postId, ':pri' => 'medium']);
    } else {
        $insertCat->execute([':dept' => $deptHR, ':parent' => null, ':name' => 'Payroll', ':desc' => 'Payroll and payslip concerns', ':icon' => 'money-bill-wave', ':color' => '#EF4444', ':sort' => 2]);
        $salaryId = $db->lastInsertId();
        $insertCat->execute([':dept' => $deptHR, ':parent' => $salaryId, ':name' => 'Draft Payslip Discrepancy', ':desc' => 'Discrepancy found in draft payslip', ':icon' => 'exclamation-circle', ':color' => '#EF4444', ':sort' => 1]);
        $upsertPriority->execute([':cid' => $db->lastInsertId(), ':pri' => 'high']);
        $insertCat->execute([':dept' => $deptHR, ':parent' => $salaryId, ':name' => 'Post-Payroll Payslip Concerns', ':desc' => 'Payslip concerns after payroll', ':icon' => 'file-invoice-dollar', ':color' => '#F59E0B', ':sort' => 2]);
        $upsertPriority->execute([':cid' => $db->lastInsertId(), ':pri' => 'medium']);
        $results[] = "Created 'Payroll' with 2 subcategories (fresh)";
    }
    $upsertPriority->execute([':cid' => $salaryId, ':pri' => 'medium']);

    // --- 3c. "Timekeeping concerns" → "Harley (Timekeeping)" ---
    $findCat->execute([':name' => 'Timekeeping concerns', ':dept' => $deptHR]);
    $tkId = $findCat->fetchColumn();
    if (!$tkId) {
        $findCat->execute([':name' => 'Harley (Timekeeping)', ':dept' => $deptHR]);
        $tkId = $findCat->fetchColumn();
    }
    if ($tkId) {
        $renameCat->execute([':name' => 'Harley (Timekeeping)', ':desc' => 'Harley timekeeping system concerns', ':id' => $tkId]);
        $results[] = "Renamed 'Timekeeping concerns' → 'Harley (Timekeeping)' (id=$tkId)";

        // Ensure subcategories exist with correct priority
        foreach ([
            ['Log In Error', 'Cannot log in to Harley timekeeping system', 'exclamation-triangle', '#F59E0B', 1, 'high'],
            ['Missing Log In/Log Out', 'Missing or incorrect time entries in Harley', 'user-clock', '#F59E0B', 2, 'low'],
        ] as [$name, $desc, $icon, $color, $sort, $pri]) {
            $findChild->execute([':name' => $name, ':parent' => $tkId]);
            $subId = $findChild->fetchColumn();
            if (!$subId) {
                $insertCat->execute([':dept' => $deptHR, ':parent' => $tkId, ':name' => $name, ':desc' => $desc, ':icon' => $icon, ':color' => $color, ':sort' => $sort]);
                $subId = $db->lastInsertId();
            }
            $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        }
    } else {
        $insertCat->execute([':dept' => $deptHR, ':parent' => null, ':name' => 'Harley (Timekeeping)', ':desc' => 'Harley timekeeping system concerns', ':icon' => 'clock', ':color' => '#F59E0B', ':sort' => 3]);
        $tkId = $db->lastInsertId();
        $insertCat->execute([':dept' => $deptHR, ':parent' => $tkId, ':name' => 'Log In Error', ':desc' => 'Cannot log in to Harley', ':icon' => 'exclamation-triangle', ':color' => '#F59E0B', ':sort' => 1]);
        $upsertPriority->execute([':cid' => $db->lastInsertId(), ':pri' => 'high']);
        $insertCat->execute([':dept' => $deptHR, ':parent' => $tkId, ':name' => 'Missing Log In/Log Out', ':desc' => 'Missing time entries', ':icon' => 'user-clock', ':color' => '#F59E0B', ':sort' => 2]);
        $upsertPriority->execute([':cid' => $db->lastInsertId(), ':pri' => 'low']);
        $results[] = "Created 'Harley (Timekeeping)' with 2 subcategories (fresh)";
    }
    $upsertPriority->execute([':cid' => $tkId, ':pri' => 'medium']);

    // --- 3d. "Leave concerns" → "Leave" ---
    $findCat->execute([':name' => 'Leave concerns', ':dept' => $deptHR]);
    $leaveId = $findCat->fetchColumn();
    if (!$leaveId) {
        $findCat->execute([':name' => 'Leave', ':dept' => $deptHR]);
        $leaveId = $findCat->fetchColumn();
    }
    if ($leaveId) {
        $renameCat->execute([':name' => 'Leave', ':desc' => 'Leave inquiries and concerns', ':id' => $leaveId]);
        $results[] = "Renamed 'Leave concerns' → 'Leave' (id=$leaveId)";

        // Deactivate old subs not in new guide
        foreach (['Leave Credit Balance', 'Leave Assistance'] as $old) {
            $findChild->execute([':name' => $old, ':parent' => $leaveId]);
            $oldId = $findChild->fetchColumn();
            if ($oldId) {
                $deactivateCat->execute([':id' => $oldId]);
                $results[] = "  Deactivated old sub: '$old' (id=$oldId)";
            }
        }

        // Ensure Leave Inquiry exists
        $findChild->execute([':name' => 'Leave Inquiry', ':parent' => $leaveId]);
        $liId = $findChild->fetchColumn();
        if (!$liId) {
            $insertCat->execute([':dept' => $deptHR, ':parent' => $leaveId, ':name' => 'Leave Inquiry', ':desc' => 'Questions about leave policies', ':icon' => 'question-circle', ':color' => '#8B5CF6', ':sort' => 1]);
            $liId = $db->lastInsertId();
        }
        $upsertPriority->execute([':cid' => $liId, ':pri' => 'low']);
    } else {
        $insertCat->execute([':dept' => $deptHR, ':parent' => null, ':name' => 'Leave', ':desc' => 'Leave inquiries', ':icon' => 'calendar-alt', ':color' => '#8B5CF6', ':sort' => 4]);
        $leaveId = $db->lastInsertId();
        $insertCat->execute([':dept' => $deptHR, ':parent' => $leaveId, ':name' => 'Leave Inquiry', ':desc' => 'Questions about leave', ':icon' => 'question-circle', ':color' => '#8B5CF6', ':sort' => 1]);
        $upsertPriority->execute([':cid' => $db->lastInsertId(), ':pri' => 'low']);
        $results[] = "Created 'Leave' with 1 subcategory (fresh)";
    }
    $upsertPriority->execute([':cid' => $leaveId, ':pri' => 'low']);

    // --- 3e. "HR General Inquiry" → "General Inquiry" ---
    $findCat->execute([':name' => 'HR General Inquiry', ':dept' => $deptHR]);
    $giId = $findCat->fetchColumn();
    if (!$giId) {
        $findCat->execute([':name' => 'General Inquiry', ':dept' => $deptHR]);
        $giId = $findCat->fetchColumn();
    }
    if ($giId) {
        $renameCat->execute([':name' => 'General Inquiry', ':desc' => 'General HR inquiries', ':id' => $giId]);
        $results[] = "Renamed 'HR General Inquiry' → 'General Inquiry' (id=$giId)";

        // Deactivate old subs
        foreach (['Holiday Inquiry', 'Non-Harley, Payslip Dispute, Leave-Related inquiries'] as $old) {
            $findChild->execute([':name' => $old, ':parent' => $giId]);
            $oldId = $findChild->fetchColumn();
            if ($oldId) {
                $deactivateCat->execute([':id' => $oldId]);
                $results[] = "  Deactivated old sub: '$old' (id=$oldId)";
            }
        }

        // Add new subs
        foreach ([
            ['HMO Inquiry', 'Health maintenance organization inquiries', 'heartbeat', '#EF4444', 1, 'medium'],
            ['Others', 'Other general HR inquiries', 'info-circle', '#6B7280', 2, 'low'],
        ] as [$name, $desc, $icon, $color, $sort, $pri]) {
            $findChild->execute([':name' => $name, ':parent' => $giId]);
            $subId = $findChild->fetchColumn();
            if (!$subId) {
                $insertCat->execute([':dept' => $deptHR, ':parent' => $giId, ':name' => $name, ':desc' => $desc, ':icon' => $icon, ':color' => $color, ':sort' => $sort]);
                $subId = $db->lastInsertId();
                $results[] = "  Added sub: '$name' (id=$subId)";
            }
            $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        }
    } else {
        $insertCat->execute([':dept' => $deptHR, ':parent' => null, ':name' => 'General Inquiry', ':desc' => 'General HR inquiries', ':icon' => 'info-circle', ':color' => '#6B7280', ':sort' => 5]);
        $giId = $db->lastInsertId();
        $insertCat->execute([':dept' => $deptHR, ':parent' => $giId, ':name' => 'HMO Inquiry', ':desc' => 'HMO inquiries', ':icon' => 'heartbeat', ':color' => '#EF4444', ':sort' => 1]);
        $upsertPriority->execute([':cid' => $db->lastInsertId(), ':pri' => 'medium']);
        $insertCat->execute([':dept' => $deptHR, ':parent' => $giId, ':name' => 'Others', ':desc' => 'Other HR inquiries', ':icon' => 'info-circle', ':color' => '#6B7280', ':sort' => 2]);
        $upsertPriority->execute([':cid' => $db->lastInsertId(), ':pri' => 'low']);
        $results[] = "Created 'General Inquiry' with 2 subcategories (fresh)";
    }
    $upsertPriority->execute([':cid' => $giId, ':pri' => 'low']);

    // =====================================================
    // 4. IT CATEGORY UPDATES
    // =====================================================
    echo "4. Updating IT categories ...\n";

    if (!$deptIT) {
        throw new Exception("Cannot proceed — IT department not found.");
    }

    // --- 4a. ACCESS — Update Account Deactivation priority LOW → HIGH ---
    $findCat->execute([':name' => 'Access', ':dept' => $deptIT]);
    $accessId = $findCat->fetchColumn();
    if ($accessId) {
        $findChild->execute([':name' => 'Account Deactivation', ':parent' => $accessId]);
        $adId = $findChild->fetchColumn();
        if ($adId) {
            $upsertPriority->execute([':cid' => $adId, ':pri' => 'high']);
            $results[] = "IT: Account Deactivation → HIGH (id=$adId)";
        }
        // Ensure all other Access subcategories have correct priorities
        foreach ([
            ['Password Reset', 'high'],
            ['Account Locked', 'high'],
            ['Permission Request', 'low'],
            ['New Account Request', 'low'],
            ['System Access Issue', 'high'],
        ] as [$name, $pri]) {
            $findChild->execute([':name' => $name, ':parent' => $accessId]);
            $subId = $findChild->fetchColumn();
            if ($subId) $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        }
    }

    // --- 4b. EMAIL — Update Email Recovery priority MEDIUM → HIGH, remove obsolete ---
    $findCat->execute([':name' => 'Email', ':dept' => $deptIT]);
    $emailId = $findCat->fetchColumn();
    if ($emailId) {
        $findChild->execute([':name' => 'Email Recovery', ':parent' => $emailId]);
        $erId = $findChild->fetchColumn();
        if ($erId) {
            $upsertPriority->execute([':cid' => $erId, ':pri' => 'high']);
            $results[] = "IT: Email Recovery → HIGH (id=$erId)";
        }

        // Deactivate obsolete email subcategories
        foreach (['Distribution List Request', 'Outlook Configuration'] as $old) {
            $findChild->execute([':name' => $old, ':parent' => $emailId]);
            $oldId = $findChild->fetchColumn();
            if ($oldId) {
                $deactivateCat->execute([':id' => $oldId]);
                $results[] = "  Deactivated IT email sub: '$old' (id=$oldId)";
            }
        }

        // Ensure remaining have correct priorities
        foreach ([
            ['Cannot Send/Receive Email', 'high'],
            ['Mobile Email Setup', 'low'],
            ['Email Quota/Storage', 'medium'],
        ] as [$name, $pri]) {
            $findChild->execute([':name' => $name, ':parent' => $emailId]);
            $subId = $findChild->fetchColumn();
            if ($subId) $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        }
    }

    // --- 4c. HARDWARE — Remove "New Hardware Request" ---
    $findCat->execute([':name' => 'Hardware', ':dept' => $deptIT]);
    $hwId = $findCat->fetchColumn();
    if ($hwId) {
        $findChild->execute([':name' => 'New Hardware Request', ':parent' => $hwId]);
        $nhrId = $findChild->fetchColumn();
        if ($nhrId) {
            $deactivateCat->execute([':id' => $nhrId]);
            $results[] = "Deactivated IT hardware sub: 'New Hardware Request' (id=$nhrId)";
        }

        // Ensure correct priorities
        foreach ([
            ['Desktop/Laptop Issue', 'high'],
            ['Keyboard/Mouse', 'medium'],
            ['Phone/Headset', 'medium'],
            ['UPS/Power', 'high'],
            ['Monitor Problem', 'medium'],
            ['Printer Issue', 'medium'],
        ] as [$name, $pri]) {
            $findChild->execute([':name' => $name, ':parent' => $hwId]);
            $subId = $findChild->fetchColumn();
            if ($subId) $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        }
    }

    // --- 4d. SOFTWARE — Ensure correct priorities ---
    $findCat->execute([':name' => 'Software', ':dept' => $deptIT]);
    $swId = $findCat->fetchColumn();
    if ($swId) {
        foreach ([
            ['Antivirus/Security', 'high'],
            ['Application Error', 'medium'],
            ['Browser Issues', 'medium'],
            ['License Request', 'low'],
            ['MS Office Issues', 'medium'],
            ['Software Installation', 'low'],
            ['Software Update/Upgrade', 'low'],
        ] as [$name, $pri]) {
            $findChild->execute([':name' => $name, ':parent' => $swId]);
            $subId = $findChild->fetchColumn();
            if ($subId) $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        }
    }

    // --- 4e. NETWORK — Ensure correct priorities ---
    $findCat->execute([':name' => 'Network', ':dept' => $deptIT]);
    $netId = $findCat->fetchColumn();
    if ($netId) {
        foreach ([
            ['Network Drive Access', 'medium'],
            ['Network Printer', 'medium'],
            ['No Internet Connection', 'high'],
            ['Slow Connection', 'medium'],
            ['VPN Issues', 'medium'],
            ['WiFi Problems', 'high'],
        ] as [$name, $pri]) {
            $findChild->execute([':name' => $name, ':parent' => $netId]);
            $subId = $findChild->fetchColumn();
            if ($subId) $upsertPriority->execute([':cid' => $subId, ':pri' => $pri]);
        }
    }

    // --- 4f. IT GENERAL INQUIRY — Rename subcategory ---
    $findCat->execute([':name' => 'IT General Inquiry', ':dept' => $deptIT]);
    $itGenId = $findCat->fetchColumn();
    if ($itGenId) {
        // Rename "General IT" → "General IT Questions/How-To/Advice"
        $findChild->execute([':name' => 'General IT', ':parent' => $itGenId]);
        $genItId = $findChild->fetchColumn();
        if ($genItId) {
            $renameCat->execute([':name' => 'General IT Questions/How-To/Advice', ':desc' => 'General IT questions, how-to guides, and advice', ':id' => $genItId]);
            $upsertPriority->execute([':cid' => $genItId, ':pri' => 'low']);
            $results[] = "IT: Renamed 'General IT' → 'General IT Questions/How-To/Advice' (id=$genItId)";
        } else {
            // Check if already renamed
            $findChild->execute([':name' => 'General IT Questions/How-To/Advice', ':parent' => $itGenId]);
            $genItId = $findChild->fetchColumn();
            if (!$genItId) {
                $insertCat->execute([':dept' => $deptIT, ':parent' => $itGenId, ':name' => 'General IT Questions/How-To/Advice', ':desc' => 'General IT questions', ':icon' => 'question-circle', ':color' => '#6B7280', ':sort' => 1]);
                $genItId = $db->lastInsertId();
            }
            $upsertPriority->execute([':cid' => $genItId, ':pri' => 'low']);
        }
        $upsertPriority->execute([':cid' => $itGenId, ':pri' => 'low']);
    }

    // --- 4g. OnBoarding (new parent) ---
    $findCat->execute([':name' => 'OnBoarding', ':dept' => $deptIT]);
    $onbId = $findCat->fetchColumn();
    if (!$onbId) {
        $insertCat->execute([':dept' => $deptIT, ':parent' => null, ':name' => 'OnBoarding', ':desc' => 'New employee onboarding IT setup', ':icon' => 'user-plus', ':color' => '#10B981', ':sort' => 7]);
        $onbId = $db->lastInsertId();
        $results[] = "IT: Created parent 'OnBoarding' (id=$onbId)";
    }
    $upsertPriority->execute([':cid' => $onbId, ':pri' => 'low']);

    // Sub: *HR to file Ticket
    $findChild->execute([':name' => '*HR to file Ticket', ':parent' => $onbId]);
    $onbSubId = $findChild->fetchColumn();
    if (!$onbSubId) {
        $insertCat->execute([':dept' => $deptIT, ':parent' => $onbId, ':name' => '*HR to file Ticket', ':desc' => 'Onboarding request — HR files on behalf of new employee', ':icon' => 'user-plus', ':color' => '#10B981', ':sort' => 1]);
        $onbSubId = $db->lastInsertId();
        $results[] = "  Added sub: '*HR to file Ticket' (id=$onbSubId)";
    }
    $upsertPriority->execute([':cid' => $onbSubId, ':pri' => 'low']);

    // --- 4h. OffBoarding (new parent) ---
    $findCat->execute([':name' => 'OffBoarding', ':dept' => $deptIT]);
    $offbId = $findCat->fetchColumn();
    if (!$offbId) {
        $insertCat->execute([':dept' => $deptIT, ':parent' => null, ':name' => 'OffBoarding', ':desc' => 'Employee offboarding IT deactivation', ':icon' => 'user-minus', ':color' => '#EF4444', ':sort' => 8]);
        $offbId = $db->lastInsertId();
        $results[] = "IT: Created parent 'OffBoarding' (id=$offbId)";
    }
    $upsertPriority->execute([':cid' => $offbId, ':pri' => 'low']);

    // Sub: *HR to file Ticket
    $findChild->execute([':name' => '*HR to file Ticket', ':parent' => $offbId]);
    $offbSubId = $findChild->fetchColumn();
    if (!$offbSubId) {
        $insertCat->execute([':dept' => $deptIT, ':parent' => $offbId, ':name' => '*HR to file Ticket', ':desc' => 'Offboarding request — HR files for departing employee', ':icon' => 'user-minus', ':color' => '#EF4444', ':sort' => 1]);
        $offbSubId = $db->lastInsertId();
        $results[] = "  Added sub: '*HR to file Ticket' (id=$offbSubId)";
    }
    $upsertPriority->execute([':cid' => $offbSubId, ':pri' => 'low']);

    // =====================================================
    // 5. COMMIT
    // =====================================================
    $db->commit();
    echo "\n✅ Migration committed successfully!\n\n";

} catch (Exception $e) {
    $db->rollBack();
    echo "\n❌ Migration ROLLED BACK: " . $e->getMessage() . "\n\n";
    $errors[] = "ROLLBACK: " . $e->getMessage();
}

// =====================================================
// 6. VERIFICATION
// =====================================================
echo "=== Results ===\n";
foreach ($results as $r) echo "  ✓ $r\n";
if ($errors) {
    echo "\n=== Errors ===\n";
    foreach ($errors as $e) echo "  ✗ $e\n";
}

echo "\n=== Verification: Full Category-Priority Map ===\n";
$verify = $db->query("
    SELECT d.name AS department,
           COALESCE(pc.name, '—') AS parent_category,
           c.name AS issue_type,
           c.is_active,
           cpm.default_priority
    FROM category_priority_map cpm
    JOIN categories c ON cpm.category_id = c.id
    LEFT JOIN categories pc ON c.parent_id = pc.id
    LEFT JOIN departments d ON c.department_id = d.id
    ORDER BY d.name, pc.name, c.name
")->fetchAll(PDO::FETCH_ASSOC);

echo str_pad('Department', 15) . str_pad('Parent', 28) . str_pad('Issue Type', 40) . str_pad('Priority', 10) . "Active\n";
echo str_repeat('-', 100) . "\n";
foreach ($verify as $row) {
    $active = $row['is_active'] ? 'Yes' : 'NO';
    echo str_pad($row['department'] ?? '?', 15)
       . str_pad($row['parent_category'], 28)
       . str_pad($row['issue_type'], 40)
       . str_pad(strtoupper($row['default_priority']), 10)
       . "$active\n";
}

echo "\n=== Department SLA Policies ===\n";
try {
    $slaVerify = $db->query("
        SELECT d.name AS department, sdp.priority, sdp.response_time, sdp.resolution_time
        FROM sla_department_policies sdp
        JOIN departments d ON sdp.department_id = d.id
        WHERE sdp.is_active = 1
        ORDER BY d.name, FIELD(sdp.priority, 'high', 'medium', 'low')
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo str_pad('Department', 15) . str_pad('Priority', 10) . str_pad('Response', 15) . "Resolution\n";
    echo str_repeat('-', 55) . "\n";
    foreach ($slaVerify as $row) {
        echo str_pad($row['department'], 15)
           . str_pad(strtoupper($row['priority']), 10)
           . str_pad($row['response_time'] . ' min (' . ($row['response_time']/60) . 'h)', 15)
           . $row['resolution_time'] . ' min (' . ($row['resolution_time']/60) . "h)\n";
    }
} catch (PDOException $e) {
    echo "  (sla_department_policies table not available)\n";
}

echo "\nDone.\n";
