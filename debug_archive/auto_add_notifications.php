<?php
/**
 * Auto-Insert Notifications for Current User
 * Run this once to populate test notifications
 */

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['user_id'])) {
    die("ERROR: Not logged in. Please login first.");
}

$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? 'user';

echo "<h1>Auto-Adding Notifications</h1>";
echo "<style>body{font-family:Arial;padding:20px;}.success{color:green;}.error{color:red;}</style>";

echo "<p>Current user_id: <strong>$userId</strong></p>";
echo "<p>User type: <strong>$userType</strong></p>";

try {
    // Delete old test notifications for this user to avoid duplicates
    if ($userType === 'employee') {
        $stmt = $db->prepare("DELETE FROM notifications WHERE employee_id = ?");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->execute([$userId]);
    }
    
    echo "<p class='success'>✓ Cleared old test notifications</p>";
    
    // Insert new notifications based on user type
    if ($userType === 'employee') {
        // For employees - use employee_id
        $sql = "INSERT INTO notifications (user_id, employee_id, type, title, message, ticket_id, is_read, created_at) VALUES
        (NULL, ?, 'ticket_created', 'Ticket Created Successfully', 'Your ticket #2001 has been created and assigned to IT', 1, 0, NOW()),
        (NULL, ?, 'status_changed', 'Ticket Status Update', 'Your ticket status changed to In Progress', 1, 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
        (NULL, ?, 'comment_added', 'IT Staff Replied to Your Ticket', 'An IT staff member responded to your ticket', 1, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
        (NULL, ?, 'ticket_resolved', 'Ticket Resolved', 'Your ticket #2002 has been marked as resolved', 2, 1, DATE_SUB(NOW(), INTERVAL 3 HOUR))";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId, $userId]);
        
        echo "<p class='success'>✓ Added 4 employee notifications</p>";
    } else {
        // For admin/IT staff - use user_id
        $sql = "INSERT INTO notifications (user_id, employee_id, type, title, message, ticket_id, is_read, created_at) VALUES
        (?, NULL, 'ticket_assigned', 'New Ticket Assigned to You', 'Ticket #1001 has been assigned to you for review', 1, 0, NOW()),
        (?, NULL, 'comment_added', 'New Comment Added', 'A customer added a new comment to ticket #1002', 2, 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE)),
        (?, NULL, 'status_changed', 'Ticket Status Changed', 'Ticket #1003 status changed to In Progress', 3, 0, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
        (?, NULL, 'ticket_created', 'New Support Ticket', 'New ticket #1004 needs your attention', 4, 0, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
        (?, NULL, 'priority_changed', 'High Priority Alert', 'Ticket #1005 priority changed to High', 5, 1, DATE_SUB(NOW(), INTERVAL 3 HOUR)),
        (?, NULL, 'ticket_updated', 'Ticket Information Updated', 'Ticket #1006 details were modified', 6, 1, DATE_SUB(NOW(), INTERVAL 5 HOUR))";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
        
        echo "<p class='success'>✓ Added 6 admin/IT notifications</p>";
    }
    
    // Verify
    if ($userType === 'employee') {
        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread FROM notifications WHERE employee_id = ?");
    } else {
        $stmt = $db->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread FROM notifications WHERE user_id = ?");
    }
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2 class='success'>✓ SUCCESS!</h2>";
    echo "<p>Total notifications: <strong>{$result['total']}</strong></p>";
    echo "<p>Unread: <strong>{$result['unread']}</strong></p>";
    echo "<p>Read: <strong>" . ($result['total'] - $result['unread']) . "</strong></p>";
    
    echo "<hr>";
    echo "<h2>Next Steps:</h2>";
    echo "<ol>";
    echo "<li><strong>Go back to your dashboard</strong></li>";
    echo "<li><strong>Hard refresh</strong> the page (Ctrl + Shift + R or Ctrl + F5)</li>";
    echo "<li><strong>Click the bell icon</strong> 🔔</li>";
    echo "<li>You should see <strong>{$result['unread']} unread notifications</strong>!</li>";
    echo "</ol>";
    
    if ($userType === 'employee') {
        echo "<p><a href='../customer/dashboard.php' style='background:#000;color:#fff;padding:10px 20px;text-decoration:none;display:inline-block;margin-top:20px;'>← Go to Dashboard</a></p>";
    } else {
        echo "<p><a href='dashboard.php' style='background:#000;color:#fff;padding:10px 20px;text-decoration:none;display:inline-block;margin-top:20px;'>← Go to Dashboard</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>✗ ERROR: " . $e->getMessage() . "</p>";
}
?>
