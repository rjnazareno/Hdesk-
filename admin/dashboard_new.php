<?php
/**
 * New Admin Dashboard Entry Point
 * Uses MVC architecture with DashboardController
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/admin/DashboardController.php';

// Initialize and run the controller
$controller = new DashboardController();
$controller->index();
