<?php
require_once __DIR__ . '/../config/config.php';

echo "<h2>Notifications Table Schema</h2>";
echo "<pre>";

$db = Database::getInstance()->getConnection();

// Get table structure
$stmt = $db->query("DESCRIBE notifications");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Column Name         | Type              | Null | Key | Default | Extra\n";
echo "--------------------------------------------------------------------------------\n";

foreach ($columns as $col) {
    printf("%-19s | %-17s | %-4s | %-3s | %-7s | %s\n",
        $col['Field'],
        $col['Type'],
        $col['Null'],
        $col['Key'],
        $col['Default'] ?? 'NULL',
        $col['Extra']
    );
}

echo "\n\n";
echo "<strong>🔍 DIAGNOSIS:</strong>\n";

// Check if user_id allows NULL
$userIdCol = array_filter($columns, fn($col) => $col['Field'] === 'user_id');
$userIdCol = reset($userIdCol);

if ($userIdCol && $userIdCol['Null'] === 'NO') {
    echo "❌ <strong style='color: red;'>PROBLEM FOUND!</strong>\n";
    echo "   Column 'user_id' is set to NOT NULL\n";
    echo "   This prevents creating notifications with employee_id only\n\n";
    echo "   <strong>FIX REQUIRED:</strong> Run this SQL in phpMyAdmin:\n\n";
    echo "   <code style='background: #f0f0f0; padding: 10px; display: block;'>";
    echo "   ALTER TABLE notifications \n";
    echo "   MODIFY COLUMN user_id INT(11) NULL;\n";
    echo "   </code>\n";
} else {
    echo "✅ user_id column allows NULL values\n";
}

$employeeIdCol = array_filter($columns, fn($col) => $col['Field'] === 'employee_id');
$employeeIdCol = reset($employeeIdCol);

if ($employeeIdCol && $employeeIdCol['Null'] === 'NO') {
    echo "❌ <strong style='color: red;'>PROBLEM FOUND!</strong>\n";
    echo "   Column 'employee_id' is set to NOT NULL\n";
    echo "   This prevents creating notifications with user_id only\n\n";
    echo "   <strong>FIX REQUIRED:</strong> Run this SQL in phpMyAdmin:\n\n";
    echo "   <code style='background: #f0f0f0; padding: 10px; display: block;'>";
    echo "   ALTER TABLE notifications \n";
    echo "   MODIFY COLUMN employee_id INT(11) NULL;\n";
    echo "   </code>\n";
} elseif ($employeeIdCol) {
    echo "✅ employee_id column allows NULL values\n";
} else {
    echo "⚠️ employee_id column does not exist (this is OK if using older schema)\n";
}

echo "</pre>";
