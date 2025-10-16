<?php
/**
 * Admin Add Employee Page
 * Entry point for adding new employees
 */

require_once __DIR__ . '/../config/config.php';

$controller = new AddEmployeeController();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->create();
} else {
    $controller->index();
}
