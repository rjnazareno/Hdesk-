<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/admin/TicketsController.php';

$controller = new TicketsController();
$controller->index();