<?php
/**
 * Ticket Status Notification API
 * Handles notifications for ticket status changes
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once '../config/database.php';
// Firebase notifications (optional)
if (file_exists('../includes/firebase_notifications.php')) {
    require_once '../includes/firebase_notifications.php';
}
session_start();

// Verify user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get POST data
    $ticketId = filter_input(INPUT_POST, 'ticket_id', FILTER_VALIDATE_INT);
    $newStatus = trim($_POST['status'] ?? '');
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];
    
    // Validate inputs
    if (!$ticketId || empty($newStatus)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Verify ticket exists and get current status
    $stmt = $db->prepare("SELECT status, employee_id, assigned_to FROM tickets WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        exit;
    }
    
    // Check permissions (IT staff only for most status changes, employee can reopen)
    if ($userType === 'employee' && !in_array($newStatus, ['Open'])) {
        echo json_encode(['success' => false, 'error' => 'Permission denied']);
        exit;
    }
    
    if ($userType === 'employee' && $ticket['employee_id'] != $userId) {
        echo json_encode(['success' => false, 'error' => 'You can only modify your own tickets']);
        exit;
    }
    
    // Don't update if status is the same
    if ($ticket['status'] === $newStatus) {
        echo json_encode(['success' => true, 'message' => 'Status unchanged']);
        exit;
    }
    
    // Update ticket status
    $updateSql = "UPDATE tickets SET status = ?, updated_at = NOW() WHERE ticket_id = ?";
    $stmt = $db->prepare($updateSql);
    $result = $stmt->execute([$newStatus, $ticketId]);
    
    if ($result) {
        // Log the status change
        $logSql = "INSERT INTO ticket_logs (ticket_id, user_id, user_type, action, details, created_at) 
                   VALUES (?, ?, ?, 'status_change', ?, NOW())";
        $logStmt = $db->prepare($logSql);
        $logStmt->execute([
            $ticketId, 
            $userId, 
            $userType, 
            "Status changed from '{$ticket['status']}' to '{$newStatus}'"
        ]);
        
        // Send Firebase notification
        try {
            $notificationSender = new FirebaseNotificationSender();
            $notificationResult = $notificationSender->sendStatusChangeNotification(
                $ticketId, 
                $newStatus, 
                $userId
            );
            
            $notificationSent = $notificationResult['success'] ?? false;
            if ($notificationSent) {
                error_log("Status change notification sent for ticket {$ticketId}");
            } else {
                error_log("Status change notification failed for ticket {$ticketId}: " . ($notificationResult['error'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            error_log("Status change notification error: " . $e->getMessage());
            $notificationSent = false;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'old_status' => $ticket['status'],
            'new_status' => $newStatus,
            'notification_sent' => $notificationSent
        ]);
        
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update status']);
    }
    
} catch (Exception $e) {
    error_log("Status update error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Server error occurred',

    ]);
}
?>