<?php
/**
 * Admin Manage Categories Page
 * Entry point for managing categories (edit, delete)
 */

require_once __DIR__ . '/../config/config.php';

$controller = new ManageCategoriesController();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $controller->update();
                break;
            case 'delete':
                $controller->delete();
                break;
            default:
                redirect('admin/manage_categories.php');
        }
    }
} else {
    $controller->index();
}
