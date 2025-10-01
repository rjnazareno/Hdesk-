<?php
/**
 * Test Seen Messages System
 * Quick test to verify the seen message functionality
 */

require_once 'config/database.php';
require_once 'includes/auth.php';

// Check if we're logged in
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$userId = $auth->getUserId();
$userType = $auth->getUserType();

echo "<h1>Seen Messages Test</h1>";
echo "<p>Current User: {$userId} ({$userType})</p>";

// Test 1: Check recent tickets and their messages
echo "<h2>Recent Tickets and Message Status:</h2>";
$stmt = $db->prepare("
    SELECT t.ticket_id, t.subject, tr.response_id, tr.message, tr.created_at,
           ms.seen_at, ms.seen_by_user_type
    FROM tickets t
    JOIN ticket_responses tr ON t.ticket_id = tr.ticket_id
    LEFT JOIN message_seen ms ON tr.response_id = ms.response_id AND ms.seen_by_user_id = ? AND ms.seen_by_user_type = ?
    ORDER BY tr.created_at DESC
    LIMIT 10
");
$stmt->execute([$userId, $userType]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='width:100%; border-collapse: collapse;'>";
echo "<tr><th>Ticket ID</th><th>Subject</th><th>Message</th><th>Created</th><th>Seen Status</th></tr>";

foreach ($messages as $msg) {
    $seenStatus = $msg['seen_at'] ? "✅ Seen at " . $msg['seen_at'] : "❌ Not seen";
    echo "<tr>";
    echo "<td>#{$msg['ticket_id']}</td>";
    echo "<td>" . htmlspecialchars($msg['subject']) . "</td>";
    echo "<td>" . htmlspecialchars(substr($msg['message'], 0, 50)) . "...</td>";
    echo "<td>{$msg['created_at']}</td>";
    echo "<td>{$seenStatus}</td>";
    echo "</tr>";
}
echo "</table>";

// Test 2: API Test
echo "<h2>API Test (check_message_seen.php):</h2>";
if (!empty($messages)) {
    $testResponse = $messages[0];
    $testUrl = "/IThelp/api/check_message_seen.php?response_id={$testResponse['response_id']}&ticket_id={$testResponse['ticket_id']}";
    echo "<p>Testing API with: <a href='{$testUrl}' target='_blank'>{$testUrl}</a></p>";
}

// Test 3: Mark All Seen API
echo "<h2>Mark All Seen API Test:</h2>";
if (!empty($messages)) {
    $testTicketId = $messages[0]['ticket_id'];
    echo "<form method='POST' action='/IThelp/api/mark_all_seen.php'>";
    echo "<input type='hidden' name='ticket_id' value='{$testTicketId}'>";
    echo "<button type='submit'>Mark All Messages in Ticket #{$testTicketId} as Seen</button>";
    echo "</form>";
}

echo "<br><br><a href='dashboard.php'>← Back to Dashboard</a>";
?>