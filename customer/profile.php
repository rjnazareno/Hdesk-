<?php
/**
 * Customer/Employee Profile Page
 * Allows employees to view and update their profile, change password, and upload profile picture
 */

require_once __DIR__ . '/../config/config.php';

$controller = new ProfileController();
$controller->index();
