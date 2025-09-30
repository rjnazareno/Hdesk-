<?php
/**
 * Test Individual FCM Token Validity
 */
require_once __DIR__ . '/includes/firebase_notifications.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$tokenId = $input['tokenId'] ?? 0;

if (!$token) {
    echo json_encode(['success' => false, 'error' => 'No token provided']);
    exit;
}

$notificationSender = new FirebaseNotificationSender();

// Send a simple test notification
$testNotification = [
    'title' => '🧪 Token Test',
    'body' => "Testing token validity for ID {$tokenId}",
    'icon' => '/favicon.ico',
    'data' => [
        'type' => 'token_test',
        'token_id' => (string)$tokenId,
        'test_time' => (string)time()
    ]
];

$result = $notificationSender->sendNotification($token, $testNotification);

echo json_encode($result);
?>