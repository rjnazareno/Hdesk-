<?php
/**
 * Customer Create Ticket Entry Point
 * Routes to CustomerCreateTicketController
 */

require_once __DIR__ . '/../config/config.php';

$controller = new CustomerCreateTicketController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->create();
} else {
    $controller->index();
}
