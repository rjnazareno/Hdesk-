<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/admin/EmployeesController.php';

$controller = new EmployeesController();

// Handle different actions
$action = $_GET['action'] ?? 'index';

switch($action) {
    case 'delete':
        $controller->delete();
        break;
    default:
        $controller->index();
        break;
}
