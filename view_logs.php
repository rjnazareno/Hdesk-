<?php
/**
 * View PHP Error Logs
 * Shows recent error_log entries
 */

// Security: Only allow in development or with password
$password = 'debug123'; // Change this!

if (!isset($_GET['key']) || $_GET['key'] !== $password) {
    die('Access denied. Add ?key=debug123 to URL');
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Error Logs</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        pre { background: #2d2d2d; padding: 15px; overflow-x: auto; border-left: 3px solid #007acc; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        .info { color: #4ec9b0; }
        h1 { color: #4ec9b0; }
    </style>
</head>
<body>
    <h1>📋 PHP Error Logs</h1>
    
    <?php
    // Try different log locations
    $logLocations = [
        __DIR__ . '/logs/app.log',
        __DIR__ . '/error_log',
        ini_get('error_log'),
        '/home/u816220874/logs/error_log',
        '/home/u816220874/public_html/error_log'
    ];
    
    $foundLogs = false;
    
    foreach ($logLocations as $logPath) {
        if (!$logPath || !file_exists($logPath)) continue;
        
        $foundLogs = true;
        echo '<h2>Log: ' . htmlspecialchars($logPath) . '</h2>';
        
        $logs = file($logPath);
        $recentLogs = array_slice($logs, -100); // Last 100 lines
        
        // Filter for ticket-related entries
        $ticketLogs = array_filter($recentLogs, function($line) {
            return stripos($line, 'ticket') !== false || 
                   stripos($line, 'TICKET') !== false ||
                   stripos($line, 'create') !== false;
        });
        
        if (!empty($ticketLogs)) {
            echo '<h3>Ticket-Related Entries (Last 100 lines):</h3>';
            echo '<pre>';
            foreach ($ticketLogs as $line) {
                if (stripos($line, 'error') !== false) {
                    echo '<span class="error">' . htmlspecialchars($line) . '</span>';
                } elseif (stripos($line, 'warning') !== false) {
                    echo '<span class="warning">' . htmlspecialchars($line) . '</span>';
                } else {
                    echo '<span class="info">' . htmlspecialchars($line) . '</span>';
                }
            }
            echo '</pre>';
        } else {
            echo '<p>No ticket-related entries found in last 100 lines</p>';
        }
        
        echo '<hr>';
    }
    
    if (!$foundLogs) {
        echo '<p>No error logs found. Checked locations:</p>';
        echo '<ul>';
        foreach ($logLocations as $loc) {
            echo '<li>' . htmlspecialchars($loc) . '</li>';
        }
        echo '</ul>';
        
        echo '<p>PHP error_log setting: ' . ini_get('error_log') . '</p>';
        echo '<p>Check your hosting control panel for error log location</p>';
    }
    
    // Show recent PHP errors from error_get_last()
    $lastError = error_get_last();
    if ($lastError) {
        echo '<h2>Last PHP Error:</h2>';
        echo '<pre>' . print_r($lastError, true) . '</pre>';
    }
    ?>
    
</body>
</html>
