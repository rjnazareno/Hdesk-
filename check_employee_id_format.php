<?php
/**
 * Check what employee_id values are actually stored
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

$db = Database::getInstance()->getConnection();

echo '<html><head><title>Employee ID Check</title>';
echo '<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4;}';
echo 'table{border-collapse:collapse;margin:20px 0;}th,td{border:1px solid #444;padding:8px;}';
echo 'th{background:#2d2d2d;color:#569cd6;}</style></head><body>';

echo '<h1>Current Employee IDs in IT Help Desk Database</h1>';

$stmt = $db->query("SELECT id, employee_id, CONCAT(fname, ' ', lname) as name, email 
                    FROM employees 
                    WHERE status = 'active' 
                    ORDER BY id 
                    LIMIT 20");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo '<p>Showing first 20 active employees:</p>';
echo '<table>';
echo '<thead><tr><th>ID</th><th>employee_id (Harley ID)</th><th>Name</th><th>Email</th></tr></thead>';
echo '<tbody>';
foreach ($employees as $emp) {
    echo '<tr>';
    echo '<td>' . $emp['id'] . '</td>';
    echo '<td><strong>' . ($emp['employee_id'] ?? 'NULL') . '</strong></td>';
    echo '<td>' . htmlspecialchars($emp['name']) . '</td>';
    echo '<td>' . htmlspecialchars($emp['email']) . '</td>';
    echo '</tr>';
}
echo '</tbody></table>';

// Check format patterns
$stmt = $db->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN employee_id LIKE 'HRLY-%' THEN 1 ELSE 0 END) as with_prefix,
    SUM(CASE WHEN employee_id NOT LIKE 'HRLY-%' AND employee_id IS NOT NULL THEN 1 ELSE 0 END) as without_prefix,
    SUM(CASE WHEN employee_id IS NULL THEN 1 ELSE 0 END) as null_values
    FROM employees 
    WHERE status = 'active'");
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

echo '<h2>Employee ID Format Analysis:</h2>';
echo '<p><strong>Total Active:</strong> ' . $stats['total'] . '</p>';
echo '<p><strong>With HRLY- prefix:</strong> ' . $stats['with_prefix'] . '</p>';
echo '<p><strong>Without prefix (plain numbers):</strong> ' . $stats['without_prefix'] . '</p>';
echo '<p><strong>NULL values:</strong> ' . $stats['null_values'] . '</p>';

echo '</body></html>';
?>
