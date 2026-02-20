<?php
/**
 * New Admin Dashboard Entry Point
 * Uses MVC architecture with DashboardController
 */

// Prevent browser from caching this page
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/admin/DashboardController.php';

// Initialize and run the controller
$controller = new DashboardController();
$controller->index();
