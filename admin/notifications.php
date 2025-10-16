<?php
/**
 * Admin/IT Notifications Entry Point
 * Routes to NotificationsController
 */

require_once __DIR__ . '/../config/config.php';

$controller = new NotificationsController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'mark_read':
            $controller->markAsRead();
            break;
        case 'mark_all_read':
            $controller->markAllAsRead();
            break;
        case 'delete':
            $controller->delete();
            break;
        default:
            $controller->index();
    }
} else {
    $controller->index();
}
