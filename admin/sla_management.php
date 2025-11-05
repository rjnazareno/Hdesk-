<?php
/**
 * SLA Management Page (Admin Only)
 * Configure SLA policies and monitor compliance
 */

require_once __DIR__ . '/../config/config.php';

$controller = new SLAManagementController();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_policy') {
        $controller->updatePolicy();
    }
}

// Display page
$controller->index();
