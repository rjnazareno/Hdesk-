<?php
/**
 * Export Tickets to Excel
 */

require_once __DIR__ . '/config/config.php';

$auth = new Auth();
$auth->requireLogin();
$auth->requireITStaff();

$reportGenerator = new ReportGenerator();

// Check report type
$type = $_GET['type'] ?? 'tickets';

if ($type === 'summary') {
    $reportGenerator->generateSummaryReport();
} else {
    // Get filters from session or URL
    $filters = [
        'status' => $_GET['status'] ?? '',
        'priority' => $_GET['priority'] ?? '',
        'category_id' => $_GET['category_id'] ?? '',
    ];
    
    $reportGenerator->generateTicketsReport($filters);
}
