<?php
/**
 * Deactivate All FCM Tokens
 */
require_once __DIR__ . '/config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Deactivate all tokens
    $stmt = $db->prepare("UPDATE fcm_tokens SET is_active = 0 WHERE is_active = 1");
    $stmt->execute();
    $count = $stmt->rowCount();
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>