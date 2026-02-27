<?php
require_once __DIR__ . '/config/database.php';
$db = Database::getInstance()->getConnection();

// Check how many tickets are missing department_id
$missing = $db->query("
    SELECT COUNT(*) as cnt FROM tickets WHERE department_id IS NULL
")->fetch();
echo "Tickets with NULL department_id: {$missing['cnt']}" . PHP_EOL;

// Check department_id via category
$fixable = $db->query("
    SELECT t.id, t.ticket_number, t.category_id, c.department_id, d.name as dept_name
    FROM tickets t
    JOIN categories c ON t.category_id = c.id
    JOIN departments d ON c.department_id = d.id
    WHERE t.department_id IS NULL
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
echo "Fixable sample:" . PHP_EOL;
foreach ($fixable as $r) {
    echo "  #{$r['ticket_number']} category_id={$r['category_id']} → dept={$r['dept_name']} (id={$r['department_id']})" . PHP_EOL;
}

// Backfill: set department_id from category
$result = $db->exec("
    UPDATE tickets t
    JOIN categories c ON t.category_id = c.id
    SET t.department_id = c.department_id
    WHERE t.department_id IS NULL AND c.department_id IS NOT NULL
");
echo PHP_EOL . "Backfilled department_id on {$result} tickets." . PHP_EOL;

// Also check remaining NULL (tickets with no category or category has no dept)
$remaining = $db->query("SELECT COUNT(*) as cnt FROM tickets WHERE department_id IS NULL")->fetch();
echo "Remaining NULL department_id: {$remaining['cnt']}" . PHP_EOL;
