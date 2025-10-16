<?php
/**
 * Customer View Ticket Entry Point
 * Routes to CustomerViewTicketController
 */

require_once __DIR__ . '/../config/config.php';

$controller = new CustomerViewTicketController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->update();
} else {
    $controller->index();
}
