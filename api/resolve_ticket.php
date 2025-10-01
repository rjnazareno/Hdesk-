<?php
/**
 * Resolve Ticket API Endpoint (IT Staff Only)
 */
require_once '../includes/auth.php';
require_once '../includes/security.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is IT staff
if (!$auth->isITStaff()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

try {
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    $action = sanitizeInput($_POST['action'] ?? '');
    $assignTo = intval($_POST['assign_to'] ?? 0);
    $resolution = sanitizeInput($_POST['resolution'] ?? '');
    
    // Validate input
    if ($ticketId <= 0) {
        throw new Exception('Invalid ticket ID');
    }
    
    if (!in_array($action, ['assign', 'resolve', 'close', 'reopen', 'update_status'])) {
        throw new Exception('Invalid action');
    }
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Check if ticket exists
    $stmt = $db->prepare("
        SELECT t.*, e.username as employee_username, e.email as employee_email
        FROM tickets t
        LEFT JOIN employees e ON t.employee_id = e.id
        WHERE t.ticket_id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    $updatedFields = [];
    $responseMessage = '';
    
    switch ($action) {
        case 'assign':
            if ($assignTo > 0) {
                // Verify IT staff member exists
                $stmt = $db->prepare("SELECT staff_id, name FROM it_staff WHERE staff_id = ? AND is_active = 1");
                $stmt->execute([$assignTo]);
                $staff = $stmt->fetch();
                
                if (!$staff) {
                    throw new Exception('Invalid IT staff member');
                }
                
                $stmt = $db->prepare("UPDATE tickets SET assigned_to = ?, status = 'in_progress' WHERE ticket_id = ?");
                $stmt->execute([$assignTo, $ticketId]);
                
                $updatedFields[] = 'assigned to ' . $staff['name'];
                $responseMessage = "Ticket assigned to {$staff['name']}";
            } else {
                // Unassign ticket
                $stmt = $db->prepare("UPDATE tickets SET assigned_to = NULL WHERE ticket_id = ?");
                $stmt->execute([$ticketId]);
                
                $updatedFields[] = 'unassigned';
                $responseMessage = "Ticket unassigned";
            }
            break;
            
        case 'resolve':
            if (empty($resolution)) {
                throw new Exception('Resolution message is required');
            }
            
            $stmt = $db->prepare("
                UPDATE tickets 
                SET status = 'resolved', acknowledged = 0, closed_by = ? 
                WHERE ticket_id = ?
            ");
            $stmt->execute([$auth->getUserId(), $ticketId]);
            
            // Add resolution response
            $stmt = $db->prepare("
                INSERT INTO ticket_responses (ticket_id, responder_id, responder_type, message)
                VALUES (?, ?, 'it', ?)
            ");
            $stmt->execute([$ticketId, $auth->getUserId(), "RESOLVED: " . $resolution]);
            
            $updatedFields[] = 'marked as resolved';
            $responseMessage = "Ticket marked as resolved";
            break;
            
        case 'close':
            $stmt = $db->prepare("
                UPDATE tickets 
                SET status = 'closed', closed_at = CURRENT_TIMESTAMP, closed_by = ? 
                WHERE ticket_id = ?
            ");
            $stmt->execute([$auth->getUserId(), $ticketId]);
            
            if (!empty($resolution)) {
                // Add closing response
                $stmt = $db->prepare("
                    INSERT INTO ticket_responses (ticket_id, responder_id, responder_type, message)
                    VALUES (?, ?, 'it', ?)
                ");
                $stmt->execute([$ticketId, $auth->getUserId(), "CLOSED: " . $resolution]);
            }
            
            $updatedFields[] = 'closed';
            $responseMessage = "Ticket closed";
            break;
            
        case 'reopen':
            $stmt = $db->prepare("
                UPDATE tickets 
                SET status = 'open', acknowledged = 0, closed_at = NULL, closed_by = NULL 
                WHERE ticket_id = ?
            ");
            $stmt->execute([$ticketId]);
            
            // Add reopen response
            $stmt = $db->prepare("
                INSERT INTO ticket_responses (ticket_id, responder_id, responder_type, message)
                VALUES (?, ?, 'it', 'Ticket reopened for further investigation')
            ");
            $stmt->execute([$ticketId, $auth->getUserId()]);
            
            $updatedFields[] = 'reopened';
            $responseMessage = "Ticket reopened";
            break;
            
        case 'update_status':
            $newStatus = sanitizeInput($_POST['status'] ?? '');
            if (!in_array($newStatus, ['open', 'in_progress', 'resolved'])) {
                throw new Exception('Invalid status');
            }
            
            $stmt = $db->prepare("UPDATE tickets SET status = ? WHERE ticket_id = ?");
            $stmt->execute([$newStatus, $ticketId]);
            
            $updatedFields[] = "status changed to {$newStatus}";
            $responseMessage = "Ticket status updated to {$newStatus}";
            break;
    }
    
    // Update ticket modified time
    $stmt = $db->prepare("UPDATE tickets SET updated_at = CURRENT_TIMESTAMP WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    
    // Commit transaction
    $db->commit();
    
    // Log the action
    error_log("Ticket {$ticketId} {$responseMessage} by IT staff " . $auth->getUserId());
    
    // Get updated ticket info
    $stmt = $db->prepare("
        SELECT status, assigned_to, acknowledged, closed_at, closed_by
        FROM tickets 
        WHERE ticket_id = ?
    ");
    $stmt->execute([$ticketId]);
    $updatedTicket = $stmt->fetch();
    
    $response = [
        'success' => true,
        'message' => $responseMessage,
        'ticket' => [
            'ticket_id' => $ticketId,
            'status' => $updatedTicket['status'],
            'assigned_to' => $updatedTicket['assigned_to'],
            'acknowledged' => (bool)$updatedTicket['acknowledged'],
            'closed_at' => $updatedTicket['closed_at'],
            'closed_by' => $updatedTicket['closed_by']
        ]
    ];
    
    // Send email notifications for certain actions
    if (in_array($action, ['resolve', 'close', 'assign'])) {
        try {
            require_once '../includes/email.php';
            $emailService = new EmailService();
            
            if ($action === 'resolve') {
                $emailService->sendTicketResolvedNotification($ticketId);
            } elseif ($action === 'assign') {
                $emailService->sendTicketAssignedNotification($ticketId);
            }
        } catch (Exception $e) {
            error_log("Email notification error: " . $e->getMessage());
            // Don't fail the action if email fails
        }
    }
    
    // 📱 Send Firebase notifications for status changes
    if (in_array($action, ['close', 'resolve', 'update_status'])) {
        try {
            require_once '../includes/firebase_notifications.php';
            $notificationSender = new FirebaseNotificationSender();
            
            if ($action === 'close') {
                // Use specialized closure notification
                $result = $notificationSender->sendTicketClosedNotification($ticketId, $auth->getUserId(), $resolution ?? '');
            } else {
                // Use general status change notification
                $notificationStatus = $action;
                if ($action === 'update_status') {
                    $notificationStatus = $newStatus;
                } elseif ($action === 'resolve') {
                    $notificationStatus = 'resolved';
                }
                
                $result = $notificationSender->sendStatusChangeNotification($ticketId, $notificationStatus, $auth->getUserId());
            }
            
            if ($result['success']) {
                error_log("🔥 Firebase notification sent for ticket #{$ticketId} - Action: {$action}");
            } else {
                error_log("❌ Firebase notification failed for ticket #{$ticketId}: " . json_encode($result));
            }
        } catch (Exception $e) {
            error_log("Firebase notification error: " . $e->getMessage());
            // Don't fail the action if notification fails
        }
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction if active
    if ($db && $db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("Resolve ticket error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>