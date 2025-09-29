<?php
/**
 * Production Server Diagnostic Script
 * This will help identify the cause of the 500 error
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start output buffering to capture any errors
ob_start();

echo "=== IT Help Desk - Production Diagnostic ===\n\n";

try {
    // 1. Check PHP version
    echo "1. PHP Version: " . PHP_VERSION . "\n";
    
    // 2. Check if session can start
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        echo "2. Session: Started successfully\n";
    } else {
        echo "2. Session: Already active\n";
    }
    
    // 3. Check file paths
    $dbConfigPath = __DIR__ . '/../config/database.php';
    echo "3. Database config path: " . $dbConfigPath . "\n";
    echo "   File exists: " . (file_exists($dbConfigPath) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($dbConfigPath)) {
        // 4. Try to include database config
        echo "4. Including database config...\n";
        require_once $dbConfigPath;
        echo "   Database config loaded successfully\n";
        
        // 5. Check if getDB function exists
        if (function_exists('getDB')) {
            echo "5. getDB function: Available\n";
            
            // 6. Try database connection
            echo "6. Testing database connection...\n";
            $pdo = getDB();
            echo "   Database connection: SUCCESS\n";
            
            // 7. Check if ticket_responses table exists
            echo "7. Checking ticket_responses table...\n";
            $stmt = $pdo->query("DESCRIBE ticket_responses");
            $columns = $stmt->fetchAll();
            echo "   Table exists with " . count($columns) . " columns:\n";
            foreach ($columns as $col) {
                echo "     - " . $col['Field'] . " (" . $col['Type'] . ")\n";
            }
            
            // 8. Check if there are any responses
            echo "8. Checking for existing responses...\n";
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM ticket_responses");
            $result = $stmt->fetch();
            echo "   Total responses in database: " . $result['count'] . "\n";
            
            // 9. Test the actual query from the API
            echo "9. Testing API query...\n";
            $ticketId = 1;
            $userId = $_SESSION['user_id'] ?? 1;
            $lastCheck = date('Y-m-d H:i:s', time() - 180);
            
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as new_count,
                       MAX(created_at) as latest_time
                FROM ticket_responses 
                WHERE ticket_id = ? AND created_at > ? AND user_id != ?
            ");
            $stmt->execute([$ticketId, $lastCheck, $userId]);
            $result = $stmt->fetch();
            echo "   Query executed successfully\n";
            echo "   Results: " . json_encode($result) . "\n";
            
        } else {
            echo "5. getDB function: NOT AVAILABLE\n";
        }
    } else {
        echo "4. Database config file not found!\n";
    }
    
    echo "\n=== Diagnostic Complete - No Fatal Errors ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR FOUND:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "\n❌ FATAL ERROR FOUND:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

// Get the output
$output = ob_get_clean();

// Return as both text and JSON for flexibility
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode(['diagnostic_output' => $output]);
} else {
    header('Content-Type: text/plain');
    echo $output;
}
?>