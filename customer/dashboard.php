<?php
/**
 * Customer/Employee Dashboard Entry Point
 * Routes to CustomerDashboardController
 */

require_once __DIR__ . '/../config/config.php';

$controller = new CustomerDashboardController();
$controller->index();
