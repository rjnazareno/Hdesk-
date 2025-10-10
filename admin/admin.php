<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/admin/AdminController.php';

$controller = new AdminController();
$controller->index();
