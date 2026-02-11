<?php
/**
 * SLA Report Excel Export
 * Generates an Excel file with SLA performance data
 * Uses temp file approach to prevent output contamination
 */

// Buffer ALL output from includes to prevent contamination
ob_start();

// Suppress any errors/warnings from polluting output
error_reporting(0);
ini_set('display_errors', 0);

// Load config and database
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Auth check - use session directly to avoid any output from Auth class
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    ob_end_clean();
    header('Location: ../login.php');
    exit;
}

// Check IT staff/admin role
$userType = $_SESSION['user_type'] ?? '';
$userRole = '';
if ($userType === 'user') {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT role FROM users WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $userRole = $row['role'] ?? '';
    } catch (Exception $e) {
        // ignore
    }
} elseif ($userType === 'employee') {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT admin_rights_hdesk FROM employees WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $adminRights = $row['admin_rights_hdesk'] ?? '';
        if (in_array($adminRights, ['superadmin', 'it'])) {
            $userRole = 'it_staff';
        }
    } catch (Exception $e) {
        // ignore
    }
}

if (!in_array($userRole, ['admin', 'it_staff'])) {
    ob_end_clean();
    header('Location: ../login.php');
    exit;
}

// PhpSpreadsheet
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

// Get date range parameters
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

// Ensure valid dates
if (!strtotime($dateFrom)) $dateFrom = date('Y-m-01');
if (!strtotime($dateTo)) $dateTo = date('Y-m-d');

try {
    $db = Database::getInstance()->getConnection();

    // ── Overall Stats Query ──
    $overallSql = "SELECT 
        COUNT(DISTINCT t.id) as total_tickets,
        SUM(CASE WHEN st.response_sla_status = 'met' THEN 1 ELSE 0 END) as response_met,
        SUM(CASE WHEN st.response_sla_status = 'breached' THEN 1 ELSE 0 END) as response_breached,
        SUM(CASE WHEN st.resolution_sla_status = 'met' THEN 1 ELSE 0 END) as resolution_met,
        SUM(CASE WHEN st.resolution_sla_status = 'breached' THEN 1 ELSE 0 END) as resolution_breached,
        AVG(st.response_time_minutes) as avg_response_time,
        AVG(st.resolution_time_minutes) as avg_resolution_time
    FROM tickets t
    LEFT JOIN sla_tracking st ON t.id = st.ticket_id
    WHERE t.created_at BETWEEN :date_from AND :date_to_end";

    $stmt = $db->prepare($overallSql);
    $stmt->execute([
        ':date_from' => $dateFrom . ' 00:00:00',
        ':date_to_end' => $dateTo . ' 23:59:59'
    ]);
    $overall = $stmt->fetch(PDO::FETCH_ASSOC);

    // ── Staff Performance Query ──
    $staffSql = "SELECT 
        e.id,
        CONCAT(e.fname, ' ', e.lname) as full_name,
        e.email,
        e.admin_rights_hdesk,
        COUNT(DISTINCT t.id) as total_tickets,
        SUM(CASE WHEN t.status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as resolved_tickets,
        SUM(CASE WHEN st.response_sla_status = 'met' THEN 1 ELSE 0 END) as response_met,
        SUM(CASE WHEN st.response_sla_status = 'breached' THEN 1 ELSE 0 END) as response_breached,
        SUM(CASE WHEN st.resolution_sla_status = 'met' THEN 1 ELSE 0 END) as resolution_met,
        SUM(CASE WHEN st.resolution_sla_status = 'breached' THEN 1 ELSE 0 END) as resolution_breached,
        AVG(st.response_time_minutes) as avg_response_time,
        AVG(st.resolution_time_minutes) as avg_resolution_time
    FROM employees e
    INNER JOIN tickets t ON t.assigned_to = e.id
    LEFT JOIN sla_tracking st ON t.id = st.ticket_id
    WHERE e.admin_rights_hdesk IN ('superadmin', 'it', 'hr')
    AND t.created_at BETWEEN :date_from AND :date_to_end
    GROUP BY e.id, e.fname, e.lname, e.email, e.admin_rights_hdesk
    ORDER BY total_tickets DESC";

    $stmt = $db->prepare($staffSql);
    $stmt->execute([
        ':date_from' => $dateFrom . ' 00:00:00',
        ':date_to_end' => $dateTo . ' 23:59:59'
    ]);
    $staffData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate SLA scores and sort
    foreach ($staffData as &$staff) {
        $totalTracked = ($staff['response_met'] + $staff['response_breached'] + $staff['resolution_met'] + $staff['resolution_breached']);
        if ($totalTracked > 0) {
            $metCount = $staff['response_met'] + $staff['resolution_met'];
            $staff['sla_score'] = round(($metCount / $totalTracked) * 100, 1);
        } else {
            $staff['sla_score'] = 0;
        }

        $respTotal = $staff['response_met'] + $staff['response_breached'];
        $staff['response_pct'] = $respTotal > 0 ? round(($staff['response_met'] / $respTotal) * 100, 1) : 0;

        $resTotal = $staff['resolution_met'] + $staff['resolution_breached'];
        $staff['resolution_pct'] = $resTotal > 0 ? round(($staff['resolution_met'] / $resTotal) * 100, 1) : 0;
    }
    unset($staff);

    // Sort by SLA score descending
    usort($staffData, function($a, $b) {
        return $b['sla_score'] <=> $a['sla_score'];
    });

    // ── Build Spreadsheet ──
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('SLA Performance Report');

    // Helper: format minutes to readable string
    $formatMinutes = function($minutes) {
        if ($minutes === null || $minutes === '') return 'N/A';
        $minutes = (float)$minutes;
        if ($minutes < 60) return round($minutes) . ' min';
        if ($minutes < 1440) return round($minutes / 60, 1) . ' hrs';
        return round($minutes / 1440, 1) . ' days';
    };

    // ── Title Section ──
    $sheet->setCellValue('A1', 'SLA Performance Report');
    $sheet->mergeCells('A1:K1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->setCellValue('A2', 'Date Range: ' . date('M d, Y', strtotime($dateFrom)) . ' to ' . date('M d, Y', strtotime($dateTo)));
    $sheet->mergeCells('A2:K2');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A2')->getFont()->setSize(11);

    $sheet->setCellValue('A3', 'Generated: ' . date('M d, Y h:i A'));
    $sheet->mergeCells('A3:K3');
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('A3')->getFont()->setSize(10)->setColor(new Color('666666'));

    // ── Staff Performance Table ──
    $row = 5;
    $sheet->setCellValue('A' . $row, 'Staff SLA Performance Rankings');
    $sheet->mergeCells('A' . $row . ':K' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(13);
    $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1F2937');
    $sheet->getStyle('A' . $row)->getFont()->setColor(new Color('FFFFFF'));

    // Table Headers
    $row++;
    $headers = ['Rank', 'Name', 'Email', 'Admin Rights', 'SLA Score', 'Tickets', 'Resolved', 'Response SLA %', 'Resolution SLA %', 'Avg Response', 'Avg Resolution'];
    $cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K'];

    for ($i = 0; $i < count($headers); $i++) {
        $sheet->setCellValue($cols[$i] . $row, $headers[$i]);
    }

    // Header styling
    $headerStyle = $sheet->getStyle('A' . $row . ':K' . $row);
    $headerStyle->getFont()->setBold(true)->setColor(new Color('FFFFFF'));
    $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('374151');
    $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('6B7280');

    // Data rows
    $rank = 1;
    foreach ($staffData as $staff) {
        $row++;
        $sheet->setCellValue('A' . $row, $rank);
        $sheet->setCellValue('B' . $row, $staff['full_name']);
        $sheet->setCellValue('C' . $row, $staff['email']);
        $sheet->setCellValue('D' . $row, ucfirst($staff['admin_rights_hdesk']));
        $sheet->setCellValue('E' . $row, $staff['sla_score'] . '%');
        $sheet->setCellValue('F' . $row, $staff['total_tickets']);
        $sheet->setCellValue('G' . $row, $staff['resolved_tickets']);
        $sheet->setCellValue('H' . $row, $staff['response_pct'] . '%');
        $sheet->setCellValue('I' . $row, $staff['resolution_pct'] . '%');
        $sheet->setCellValue('J' . $row, $formatMinutes($staff['avg_response_time']));
        $sheet->setCellValue('K' . $row, $formatMinutes($staff['avg_resolution_time']));

        // Score color coding
        $scoreColor = 'DC2626'; // Red
        if ($staff['sla_score'] >= 90) $scoreColor = '16A34A'; // Green
        elseif ($staff['sla_score'] >= 70) $scoreColor = 'CA8A04'; // Yellow
        elseif ($staff['sla_score'] >= 50) $scoreColor = 'EA580C'; // Orange

        $sheet->getStyle('E' . $row)->getFont()->setBold(true)->setColor(new Color($scoreColor));

        // Zebra striping
        if ($rank % 2 === 0) {
            $sheet->getStyle('A' . $row . ':K' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
        }

        // Center align numeric cells
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('E' . $row . ':I' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Borders
        $sheet->getStyle('A' . $row . ':K' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');

        $rank++;
    }

    if (empty($staffData)) {
        $row++;
        $sheet->setCellValue('A' . $row, 'No staff performance data found for this date range.');
        $sheet->mergeCells('A' . $row . ':K' . $row);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A' . $row)->getFont()->setItalic(true)->setColor(new Color('9CA3AF'));
    }

    // ── Legend ──
    $row += 2;
    $sheet->setCellValue('A' . $row, 'Score Legend');
    $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
    $row++;

    $legendItems = [
        ['90-100%', 'Excellent', '16A34A'],
        ['70-89%', 'Good', 'CA8A04'],
        ['50-69%', 'Needs Improvement', 'EA580C'],
        ['Below 50%', 'Critical', 'DC2626']
    ];

    foreach ($legendItems as $item) {
        $sheet->setCellValue('A' . $row, $item[0]);
        $sheet->setCellValue('B' . $row, $item[1]);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setColor(new Color($item[2]));
        $sheet->getStyle('B' . $row)->getFont()->setColor(new Color($item[2]));
        $row++;
    }

    // ── Column Widths ──
    $colWidths = ['A' => 8, 'B' => 22, 'C' => 28, 'D' => 16, 'E' => 12, 'F' => 10, 'G' => 10, 'H' => 16, 'I' => 18, 'J' => 16, 'K' => 16];
    foreach ($colWidths as $col => $width) {
        $sheet->getColumnDimension($col)->setWidth($width);
    }

    // ── Write to temp file (prevents output contamination) ──
    $writer = new Xlsx($spreadsheet);
    $tempFile = tempnam(sys_get_temp_dir(), 'sla_report_');
    $writer->save($tempFile);

    // Discard ALL buffered output from includes/config/etc
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Generate filename
    $filename = 'SLA_Report_' . date('Y-m-d', strtotime($dateFrom)) . '_to_' . date('Y-m-d', strtotime($dateTo)) . '.xlsx';

    // Serve the clean file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tempFile));
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    readfile($tempFile);
    unlink($tempFile);
    exit;

} catch (Exception $e) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/plain');
    echo 'Error generating report: ' . $e->getMessage();
    exit;
}
