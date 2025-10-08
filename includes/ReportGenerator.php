<?php
/**
 * Report Generator
 * Creates Excel reports using PhpSpreadsheet
 */

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

require_once __DIR__ . '/../vendor/autoload.php';

class ReportGenerator {
    private $spreadsheet;
    private $ticketModel;
    private $userModel;
    private $categoryModel;
    
    public function __construct() {
        $this->spreadsheet = new Spreadsheet();
        $this->ticketModel = new Ticket();
        $this->userModel = new User();
        $this->categoryModel = new Category();
    }
    
    /**
     * Generate tickets report
     */
    public function generateTicketsReport($filters = []) {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Tickets Report');
        
        // Set header
        $headers = ['Ticket #', 'Title', 'Category', 'Priority', 'Status', 'Submitter', 'Assigned To', 'Created Date', 'Updated Date'];
        $sheet->fromArray($headers, null, 'A1');
        
        // Style header
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '2563EB']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);
        
        // Get tickets data
        $tickets = $this->ticketModel->getAll($filters);
        
        // Populate data
        $row = 2;
        foreach ($tickets as $ticket) {
            $sheet->setCellValue('A' . $row, $ticket['ticket_number']);
            $sheet->setCellValue('B' . $row, $ticket['title']);
            $sheet->setCellValue('C' . $row, $ticket['category_name']);
            $sheet->setCellValue('D' . $row, strtoupper($ticket['priority']));
            $sheet->setCellValue('E' . $row, strtoupper($ticket['status']));
            $sheet->setCellValue('F' . $row, $ticket['submitter_name']);
            $sheet->setCellValue('G' . $row, $ticket['assigned_name'] ?? 'Unassigned');
            $sheet->setCellValue('H' . $row, date('Y-m-d H:i', strtotime($ticket['created_at'])));
            $sheet->setCellValue('I' . $row, date('Y-m-d H:i', strtotime($ticket['updated_at'])));
            
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Add borders
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ];
        $sheet->getStyle('A1:I' . ($row - 1))->applyFromArray($styleArray);
        
        return $this->download('Tickets_Report_' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * Generate summary report
     */
    public function generateSummaryReport() {
        $sheet = $this->spreadsheet->getActiveSheet();
        $sheet->setTitle('Summary Report');
        
        // Title
        $sheet->setCellValue('A1', 'IT Help Desk - Summary Report');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setSize(16)->setBold(true);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Report date
        $sheet->setCellValue('A2', 'Generated on: ' . date('F d, Y H:i'));
        $sheet->mergeCells('A2:D2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        $row = 4;
        
        // Ticket Statistics
        $stats = $this->ticketModel->getStats();
        
        $sheet->setCellValue('A' . $row, 'Ticket Statistics');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Total Tickets:');
        $sheet->setCellValue('B' . $row, $stats['total']);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Pending:');
        $sheet->setCellValue('B' . $row, $stats['pending']);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Open:');
        $sheet->setCellValue('B' . $row, $stats['open']);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'In Progress:');
        $sheet->setCellValue('B' . $row, $stats['in_progress']);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Resolved:');
        $sheet->setCellValue('B' . $row, $stats['resolved']);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Closed:');
        $sheet->setCellValue('B' . $row, $stats['closed']);
        $row += 2;
        
        // Category Statistics
        $categoryStats = $this->categoryModel->getStats();
        
        $sheet->setCellValue('A' . $row, 'Tickets by Category');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;
        
        $sheet->setCellValue('A' . $row, 'Category');
        $sheet->setCellValue('B' . $row, 'Total Tickets');
        $sheet->setCellValue('C' . $row, 'Open Tickets');
        $sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
        $row++;
        
        foreach ($categoryStats as $cat) {
            $sheet->setCellValue('A' . $row, $cat['name']);
            $sheet->setCellValue('B' . $row, $cat['ticket_count']);
            $sheet->setCellValue('C' . $row, $cat['open_tickets']);
            $row++;
        }
        
        // Auto-size columns
        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        return $this->download('Summary_Report_' . date('Y-m-d') . '.xlsx');
    }
    
    /**
     * Download the Excel file
     */
    private function download($filename) {
        $writer = new Xlsx($this->spreadsheet);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}
