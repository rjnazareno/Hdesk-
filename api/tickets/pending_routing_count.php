<?php
/**
 * Get pending routing count for Super Admin dashboard
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Auth.php';
require_once __DIR__ . '/../../includes/RBAC.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    echo json_encode(['count' => 0]);
    exit;
}

$rbac = RBAC::getInstance();
if (!$rbac->isSuperAdmin()) {
    echo json_encode(['count' => 0]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $sql = "SELECT COUNT(*) FROM tickets 
            WHERE department_id IS NULL 
            AND status IN ('pending', 'open')";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    echo json_encode(['count' => intval($count)]);
} catch (Exception $e) {
    echo json_encode(['count' => 0]);
}
