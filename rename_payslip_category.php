<?php
/**
 * Rename "Payslip Dispute (after cutoff)" → "Draft Payslip"
 * Run this on the production server, then delete it.
 */
require_once __DIR__ . '/config/config.php';

$db = Database::getInstance()->getConnection();

// Check current state
$stmt = $db->query("SELECT id, name, parent_id FROM categories WHERE name LIKE '%Payslip%' OR name LIKE '%Draft%'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== BEFORE ===\n";
foreach ($rows as $r) {
    echo "ID: {$r['id']}  |  Name: {$r['name']}  |  Parent: {$r['parent_id']}\n";
}

// Rename "Payslip Dispute (after cutoff)" → "Draft Payslip"
$update = $db->prepare(
    "UPDATE categories 
     SET name = 'Draft Payslip',
         description = 'Draft payslip review and disputes'
     WHERE name = 'Payslip Dispute (after cutoff)'"
);
$update->execute();
echo "\nUpdated {$update->rowCount()} row(s): 'Payslip Dispute (after cutoff)' → 'Draft Payslip'\n";

// Verify
$stmt2 = $db->query("SELECT id, name, parent_id FROM categories WHERE name LIKE '%Payslip%' OR name LIKE '%Draft%'");
$rows2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

echo "\n=== AFTER ===\n";
foreach ($rows2 as $r) {
    echo "ID: {$r['id']}  |  Name: {$r['name']}  |  Parent: {$r['parent_id']}\n";
}

echo "\nDone! You can delete this file now.\n";
