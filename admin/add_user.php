<?php
/**
 * Admin Add User Page
 * Entry point for adding IT Staff and Admin users
 */

require_once __DIR__ . '/../config/config.php';

$controller = new AddUserController();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->create();
} else {
    $controller->index();
}
