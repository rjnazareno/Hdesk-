<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/admin/ITStaffController.php';

$controller = new ITStaffController();
$controller->index();
