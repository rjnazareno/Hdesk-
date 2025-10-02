<?php
session_start();
require_once 'config/database.php';

// Set test session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'it_staff';
}

$ticketId = 7; // From your screenshot

$database = Database::getInstance();
$db = $database->getConnection();

echo "<h2>🐛 Timestamp Debug for Ticket #7</h2>";

// Test 1: Raw database timestamps
echo "<h3>1. Raw Database Data</h3>";
$stmt = $db->prepare("SELECT response_id, message, user_type, created_at FROM ticket_responses WHERE ticket_id = ? ORDER BY created_at ASC");
$stmt->execute([$ticketId]);
$rawData = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>ID</th><th>Message</th><th>User Type</th><th>Raw created_at</th></tr>";
foreach ($rawData as $row) {
    echo "<tr>";
    echo "<td>{$row['response_id']}</td>";
    echo "<td>" . substr($row['message'], 0, 20) . "...</td>";
    echo "<td>{$row['user_type']}</td>";
    echo "<td><strong>{$row['created_at']}</strong></td>";
    echo "</tr>";
}
echo "</table>";

// Test 2: SQL formatted timestamps (like main page)
echo "<h3>2. Main Page Query (SQL Formatting)</h3>";
$stmt = $db->prepare("
    SELECT response_id, message, user_type, created_at,
           DATE_FORMAT(created_at, '%h:%i:%s %p') as formatted_time
    FROM ticket_responses 
    WHERE ticket_id = ? 
    ORDER BY created_at ASC
");
$stmt->execute([$ticketId]);
$mainData = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>ID</th><th>Message</th><th>User Type</th><th>Raw Time</th><th>SQL Formatted</th></tr>";
foreach ($mainData as $row) {
    echo "<tr>";
    echo "<td>{$row['response_id']}</td>";
    echo "<td>" . substr($row['message'], 0, 20) . "...</td>";
    echo "<td>{$row['user_type']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "<td><strong style='color: blue;'>{$row['formatted_time']}</strong></td>";
    echo "</tr>";
}
echo "</table>";

// Test 3: Current database time
echo "<h3>3. Database Current Time</h3>";
$stmt = $db->prepare("SELECT NOW() as db_now, DATE_FORMAT(NOW(), '%h:%i:%s %p') as db_formatted");
$stmt->execute();
$timeData = $stmt->fetch();
echo "<p><strong>Database NOW():</strong> {$timeData['db_now']}</p>";
echo "<p><strong>Database Formatted:</strong> {$timeData['db_formatted']}</p>";
echo "<p><strong>PHP Time:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><strong>PHP Formatted:</strong> " . date('h:i:s A') . "</p>";

// Test 4: Simulate API call
echo "<h3>4. API Response (get_chat_messages.php)</h3>";
$apiResponse = file_get_contents("http://localhost/IThelp/api/get_chat_messages.php?ticket_id={$ticketId}");
$apiData = json_decode($apiResponse, true);

if ($apiData['success']) {
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Message</th><th>User Type</th><th>API Formatted Time</th><th>Raw created_at</th></tr>";
    foreach ($apiData['messages'] as $msg) {
        echo "<tr>";
        echo "<td>{$msg['response_id']}</td>";
        echo "<td>" . substr($msg['message'], 0, 20) . "...</td>";
        echo "<td>{$msg['user_type']}</td>";
        echo "<td><strong style='color: red;'>{$msg['formatted_time']}</strong></td>";
        echo "<td>{$msg['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>API Error: " . ($apiData['message'] ?? 'Unknown error') . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; padding: 20px; }
table { width: 100%; }
th, td { padding: 8px; text-align: left; border: 1px solid #ccc; }
th { background: #f5f5f5; }
h3 { color: #333; margin-top: 30px; }
</style>