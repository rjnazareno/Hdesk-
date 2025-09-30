<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['token'])) {
        echo json_encode(['error' => 'Token required']);
        exit;
    }
    
    $token = $input['token'];
    
    $stmt = $pdo->prepare("SELECT id, user_id, created_at, is_active FROM fcm_tokens WHERE token = ? LIMIT 1");
    $stmt->execute([$token]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'exists' => true,
            'token_info' => [
                'id' => $result['id'],
                'user_id' => $result['user_id'],
                'created_at' => $result['created_at'],
                'is_active' => (bool)$result['is_active']
            ]
        ]);
    } else {
        echo json_encode(['exists' => false]);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>