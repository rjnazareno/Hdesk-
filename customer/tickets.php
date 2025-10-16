<?php
/**
 * Customer Tickets Entry Point
 * Routes to CustomerTicketsController
 */

require_once __DIR__ . '/../config/config.php';

$controller = new CustomerTicketsController();
$controller->index();
