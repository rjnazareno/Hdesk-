<?php
/**
 * SLA Performance Entry Point
 * Shows SLA scores and performance metrics for IT staff
 */

require_once __DIR__ . '/../config/config.php';

$controller = new SLAPerformanceController();
$controller->index();
