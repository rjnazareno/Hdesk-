<?php
require_once __DIR__ . '/../config/config.php';

echo "<h2>Recent Tickets (Last 5)</h2>";
echo "<pre>";

$db = Database::getInstance()->getConnection();
$stmt = $db->query("
    SELECT t.*, 
           CASE 
               WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
               ELSE u.full_name
           END as submitter_name,
           t.created_at
    FROM tickets t
    LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
    LEFT JOIN users u ON t.submitter_id = u.id AND t.submitter_type = 'user'
    ORDER BY t.created_at DESC
    LIMIT 5
");

$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tickets as $ticket) {
    echo "\n=== Ticket #{$ticket['ticket_number']} ===\n";
    echo "ID: {$ticket['id']}\n";
    echo "Title: {$ticket['title']}\n";
    echo "Status: {$ticket['status']}\n";
    echo "Submitted by: {$ticket['submitter_name']} ({$ticket['submitter_type']}, ID: {$ticket['submitter_id']})\n";
    echo "Created: {$ticket['created_at']}\n";
}

echo "\n\n<h2>Recent Notifications (Last 10)</h2>";

$stmt = $db->query("
    SELECT n.*,
           u.username as user_username,
           CONCAT(e.fname, ' ', e.lname) as employee_name
    FROM notifications n
    LEFT JOIN users u ON n.user_id = u.id
    LEFT JOIN employees e ON n.employee_id = e.id
    ORDER BY n.created_at DESC
    LIMIT 10
");

$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($notifications as $notif) {
    echo "\n=== Notification ID {$notif['id']} ===\n";
    echo "Type: {$notif['type']}\n";
    echo "Title: {$notif['title']}\n";
    echo "Message: {$notif['message']}\n";
    echo "Ticket ID: {$notif['ticket_id']}\n";
    echo "User ID: {$notif['user_id']} (Username: {$notif['user_username']})\n";
    echo "Employee ID: {$notif['employee_id']} (Name: {$notif['employee_name']})\n";
    echo "Read: " . ($notif['is_read'] ? 'YES' : 'NO') . "\n";
    echo "Created: {$notif['created_at']}\n";
}

echo "\n\n<h2>Admin Users</h2>";

$stmt = $db->query("
    SELECT id, username, full_name, role 
    FROM users 
    WHERE role IN ('admin', 'it_staff') AND is_active = 1
");

$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($admins as $admin) {
    echo "User ID {$admin['id']}: {$admin['username']} ({$admin['full_name']}) - Role: {$admin['role']}\n";
}

echo "</pre>";
