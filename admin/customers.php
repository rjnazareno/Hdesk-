<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/admin/EmployeesController.php';

$controller = new EmployeesController();
$controller->index();
