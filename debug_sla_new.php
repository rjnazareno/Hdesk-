<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance()->getConnection();

// Check latest ticket SLA tracking
echo "=== Latest ticket SLA tracking ===" . PHP_EOL;
$rows = $db->query("
    SELECT t.id, t.ticket_number, t.priority, t.department_id, t.category_id,
           t.created_at, st.response_due_at, st.resolution_due_at,
           TIMESTAMPDIFF(MINUTE, t.created_at, st.response_due_at) as response_min,
           TIMESTAMPDIFF(MINUTE, t.created_at, st.resolution_due_at) as resolution_min,
           st.sla_policy_id
    FROM tickets t
    JOIN sla_tracking st ON t.id = st.ticket_id
    ORDER BY t.id DESC LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "ticket={$r['ticket_number']} priority={$r['priority']} dept_id={$r['department_id']}"
       . " response=" . round($r['response_min']/60,1) . "h"
       . " resolution=" . round($r['resolution_min']/60,1) . "h"
       . " policy_id={$r['sla_policy_id']}" . PHP_EOL;
}

// Check sla_policies
echo PHP_EOL . "=== sla_policies ===" . PHP_EOL;
$rows = $db->query("SELECT id, priority, response_time, resolution_time, is_business_hours, is_active FROM sla_policies")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "id={$r['id']} priority={$r['priority']} response={$r['response_time']} resolution={$r['resolution_time']} biz_hours={$r['is_business_hours']} active={$r['is_active']}" . PHP_EOL;
}

// Check sla_department_policies columns
echo PHP_EOL . "=== sla_department_policies ===" . PHP_EOL;
try {
    $rows = $db->query("SELECT * FROM sla_department_policies")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        echo print_r($r, true);
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}
