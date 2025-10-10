<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/admin/CategoriesController.php';

$controller = new CategoriesController();
$controller->index();
