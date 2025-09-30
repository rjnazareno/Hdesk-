<?php
/**
 * Test Firebase Notification Sending
 * Use this to test server-side notification sending
 */

require_once 'config/database.php';
require_once 'includes/firebase_notifications.php';

// Simple test interface
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
?>
<!DOCTYPE html>
<html>
<head>
    <title>🔥 Firebase Server Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-6">🔥 Firebase Server Notification Test</h1>
        
        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium mb-2">Test Type:</label>
                <select name="test_type" class="w-full border rounded-lg p-3">
                    <option value="new_reply">💬 New Reply Notification</option>
                    <option value="status_change">📋 Status Change Notification</option>
                    <option value="new_ticket">🆕 New Ticket Notification</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Ticket ID:</label>
                <input type="number" name="ticket_id" class="w-full border rounded-lg p-3" value="1" min="1">
            </div>
            
            <div>
                <label class="block text-sm font-medium mb-2">Test Message:</label>
                <textarea name="message" class="w-full border rounded-lg p-3" rows="3">This is a test notification from the server!</textarea>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors">
                🚀 Send Test Notification
            </button>
        </form>
        
        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <h3 class="font-bold text-yellow-800 mb-2">⚠️ Requirements:</h3>
            <ul class="text-yellow-700 text-sm space-y-1">
                <li>• Firebase Server Key must be configured in firebase_notifications.php</li>
                <li>• At least one user must have granted notification permission</li>
                <li>• FCM tokens must be saved in the database</li>
            </ul>
        </div>
    </div>
</body>
</html>
<?php
    exit;
}

// Handle POST request
header('Content-Type: application/json');

try {
    $testType = $_POST['test_type'] ?? 'new_reply';
    $ticketId = intval($_POST['ticket_id'] ?? 1);
    $message = $_POST['message'] ?? 'Test notification';
    
    $notificationSender = new FirebaseNotificationSender();
    $result = null;
    
    switch ($testType) {
        case 'new_reply':
            // Test new reply notification
            $result = $notificationSender->sendNewReplyNotification($ticketId, 1, 'it_staff', $message);
            break;
            
        case 'status_change':
            // Test status change notification
            $result = $notificationSender->sendStatusChangeNotification($ticketId, 'Closed', 1);
            break;
            
        case 'new_ticket':
            // Test new ticket notification
            $result = $notificationSender->sendNewTicketNotification($ticketId);
            break;
            
        default:
            throw new Exception('Invalid test type');
    }
    
    echo json_encode([
        'success' => true,
        'test_type' => $testType,
        'ticket_id' => $ticketId,
        'result' => $result,
        'message' => 'Test notification sent successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>