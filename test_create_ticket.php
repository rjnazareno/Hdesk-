<?php
/**
 * Real-time Ticket Creation Test
 * This simulates the exact ticket creation process
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ticket Creation Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .section { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #333; }
        pre { background: #f9f9f9; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🎫 Real-time Ticket Creation Test</h1>
    
    <?php
    try {
        echo '<div class="section">';
        echo '<h2>Step 1: Initialize Models</h2>';
        
        $db = Database::getInstance()->getConnection();
        $ticketModel = new Ticket();
        
        echo '<p class="pass">✓ Models initialized</p>';
        echo '</div>';
        
        echo '<div class="section">';
        echo '<h2>Step 2: Prepare Test Data</h2>';
        
        $ticketNumber = 'TEST-' . time();
        $testData = [
            'ticket_number' => $ticketNumber,
            'title' => 'Real Test Ticket - ' . date('Y-m-d H:i:s'),
            'description' => 'This is a real test to debug ticket creation',
            'category_id' => 1,
            'department_id' => null,
            'priority' => 'medium',
            'status' => 'pending',
            'submitter_id' => 1,
            'submitter_type' => 'employee',
            'assigned_to' => null,
            'attachments' => null
        ];
        
        echo '<p>Ticket Number: ' . $ticketNumber . '</p>';
        echo '<pre>' . print_r($testData, true) . '</pre>';
        echo '</div>';
        
        echo '<div class="section">';
        echo '<h2>Step 3: Execute Create Method</h2>';
        
        echo '<p>Calling $ticketModel->create()...</p>';
        
        $ticketId = $ticketModel->create($testData);
        
        echo '<p>Returned Ticket ID: <strong>' . var_export($ticketId, true) . '</strong></p>';
        echo '<p>Type: ' . gettype($ticketId) . '</p>';
        
        if ($ticketId) {
            echo '<p class="pass">✓ Ticket created successfully!</p>';
            echo '<p>Ticket ID: ' . $ticketId . '</p>';
            
            // Verify ticket exists
            echo '<h3>Verification:</h3>';
            $stmt = $db->prepare("SELECT * FROM tickets WHERE id = :id");
            $stmt->execute([':id' => $ticketId]);
            $ticket = $stmt->fetch();
            
            if ($ticket) {
                echo '<p class="pass">✓ Ticket found in database</p>';
                echo '<pre>' . print_r($ticket, true) . '</pre>';
                
                // Clean up
                echo '<p>Cleaning up test ticket...</p>';
                $db->prepare("DELETE FROM tickets WHERE id = :id")->execute([':id' => $ticketId]);
                echo '<p class="pass">✓ Test ticket removed</p>';
            } else {
                echo '<p class="fail">✗ Ticket not found in database!</p>';
            }
        } else {
            echo '<p class="fail">✗ Ticket creation returned FALSE</p>';
            
            // Check if ticket was actually created
            echo '<h3>Checking if ticket exists anyway...</h3>';
            $stmt = $db->prepare("SELECT * FROM tickets WHERE ticket_number = :ticket_number");
            $stmt->execute([':ticket_number' => $ticketNumber]);
            $ticket = $stmt->fetch();
            
            if ($ticket) {
                echo '<p class="fail">✗ CRITICAL: Ticket EXISTS in database but create() returned false!</p>';
                echo '<p>This means the fallback query is failing.</p>';
                echo '<pre>' . print_r($ticket, true) . '</pre>';
                
                // Clean up
                $db->prepare("DELETE FROM tickets WHERE id = :id")->execute([':id' => $ticket['id']]);
                echo '<p>Test ticket cleaned up (ID: ' . $ticket['id'] . ')</p>';
            } else {
                echo '<p class="fail">✗ Ticket does NOT exist in database</p>';
                echo '<p>The INSERT statement itself is failing.</p>';
            }
        }
        
        echo '</div>';
        
        // Check error log
        echo '<div class="section">';
        echo '<h2>Step 4: Check Recent Error Logs</h2>';
        
        $logFile = __DIR__ . '/logs/app.log';
        if (file_exists($logFile)) {
            $logs = file($logFile);
            $recentLogs = array_slice($logs, -20);
            echo '<p>Last 20 log entries:</p>';
            echo '<pre>' . implode('', $recentLogs) . '</pre>';
        } else {
            echo '<p>No log file found at: ' . $logFile . '</p>';
            echo '<p>Check PHP error_log location</p>';
        }
        
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="section">';
        echo '<p class="fail">✗ Exception: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    }
    ?>
    
    <div class="section">
        <h2>Next Steps</h2>
        <p>If this test shows:</p>
        <ul>
            <li><strong>Ticket created successfully:</strong> The issue is in the customer controller or session data</li>
            <li><strong>Ticket exists but returned false:</strong> The fallback query has a bug</li>
            <li><strong>No ticket created:</strong> There's a database constraint or trigger issue</li>
        </ul>
    </div>
    
</body>
</html>
