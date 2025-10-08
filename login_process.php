<?php
/**
 * Login Process Handler
 */

require_once __DIR__ . '/controllers/LoginController.php';

$controller = new LoginController();
$controller->login();
