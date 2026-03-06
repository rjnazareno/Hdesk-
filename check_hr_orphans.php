<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

// Check *HR to file Ticket rows
$stmt = $db->query("SELECT id, name, parent_id, department_id, is_active FROM categories WHERE name LIKE '%HR to file%' ORDER BY id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo count($rows) . " rows with '*HR to file Ticket':\n";
foreach ($rows as $r) {
    echo "  id={$r['id']} parent={$r['parent_id']} dept={$r['department_id']} active={$r['is_active']}\n";
}

// Check total active categories
$stmt2 = $db->query("SELECT COUNT(*) as c FROM categories WHERE is_active = 1");
echo "\nTotal active categories: " . $stmt2->fetch()['c'] . "\n";

// Check active parents (no parent_id)
$stmt3 = $db->query("SELECT id, name, department_id FROM categories WHERE is_active = 1 AND parent_id IS NULL ORDER BY department_id, name");
$parents = $stmt3->fetchAll(PDO::FETCH_ASSOC);
echo "\nActive parent categories (" . count($parents) . "):\n";
foreach ($parents as $p) {
    echo "  [{$p['department_id']}] {$p['name']} (id={$p['id']})\n";
}

// Check OnBoarding/OffBoarding subcategories specifically
$stmt4 = $db->query("SELECT c.id, c.name, c.parent_id, p.name as parent_name, c.is_active 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    WHERE c.name LIKE '%HR to file%' OR c.name LIKE '%OnBoarding%' OR c.name LIKE '%OffBoarding%'
    ORDER BY c.id");
$boarding = $stmt4->fetchAll(PDO::FETCH_ASSOC);
echo "\nOnBoarding/OffBoarding related rows (" . count($boarding) . "):\n";
foreach ($boarding as $b) {
    echo "  id={$b['id']} name='{$b['name']}' parent={$b['parent_id']}({$b['parent_name']}) active={$b['is_active']}\n";
}
