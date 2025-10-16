<?php
/**
 * Admin Create Ticket Page
 * Allows admins to create tickets on behalf of employees
 */

require_once __DIR__ . '/../config/config.php';

$controller = new CreateTicketController();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller->create();
} else {
    $controller->index();
}
