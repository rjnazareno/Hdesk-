<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

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
    $responseText = trim($_POST['response_text'] ?? '');
    $isInternal = isset($_POST['is_internal']) && $_POST['is_internal'] ? 1 : 0;
    $userId = $_SESSION['user_id'];
    $userType = $_SESSION['user_type'];
    
    // Validate inputs
    if (!$ticketId || empty($responseText)) {
        echo json_encode(['success' => false, 'error' => 'Missing required fields']);
        exit;
    }
    
    // Verify ticket exists
    $ticketQuery = "SELECT employee_id FROM tickets WHERE id = ?";
    $stmt = $db->prepare($ticketQuery);
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        echo json_encode(['success' => false, 'error' => 'Ticket not found']);
        exit;
    }
    
    // Check permissions
    if ($userType === 'employee' && $ticket['employee_id'] != $userId) {
        echo json_encode(['success' => false, 'error' => 'You can only add responses to your own tickets']);
        exit;
    }
    
    // Employees cannot add internal responses
    if ($userType === 'employee' && $isInternal) {
        $isInternal = 0;
    }
    
    // Insert the response
    $insertQuery = "INSERT INTO ticket_responses (ticket_id, user_id, user_type, message, is_internal, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($insertQuery);
    $result = $stmt->execute([$ticketId, $userId, $userType, $responseText, $isInternal]);
    
    if ($result) {
        // Update ticket's updated_at timestamp
        $updateTicket = "UPDATE tickets SET updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($updateTicket);
        $stmt->execute([$ticketId]);
        
        // Get the newly created response with formatting
        $getResponseQuery = "
            SELECT tr.*, tr.created_at,
                   CASE 
                       WHEN tr.user_type = 'it_staff' THEN 'IT Support'
                       ELSE 'Employee'
                   END as display_name
            FROM ticket_responses tr 
            WHERE tr.ticket_id = ? 
            ORDER BY tr.created_at DESC 
            LIMIT 1";
        
        $stmt = $db->prepare($getResponseQuery);
        $stmt->execute([$ticketId]);
        $newResponse = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($newResponse) {
            // Format the response for display
            $formattedResponse = [
                'id' => $newResponse['id'],
                'user_type' => $newResponse['user_type'],
                'display_name' => $newResponse['display_name'],
                'message' => htmlspecialchars($newResponse['message']),
                'is_internal' => (bool)$newResponse['is_internal'],
                'created_at' => $newResponse['created_at'],
                'formatted_date' => date('M j, Y \a\t g:i A', strtotime($newResponse['created_at']))
            ];
            
            echo json_encode([
                'success' => true, 
                'message' => 'Response added successfully',
                'response' => $formattedResponse
            ]);
        } else {
            echo json_encode(['success' => true, 'message' => 'Response added successfully']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add response']);
    }
    
} catch (Exception $e) {
    error_log("Add response AJAX error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error occurred']);
}
?>