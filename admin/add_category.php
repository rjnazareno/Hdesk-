<?php
/**
 * Admin Add Category Page
 * Entry point for adding new categories
 */

require_once __DIR__ . '/../config/config.php';

$controller = new AddCategoryController();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->create();
} else {
    $controller->index();
}
