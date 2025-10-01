<?php
/**
 * Safe version of simple_check_updates with extensive error handling
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display on production, but log them
ini_set('log_errors', 1);

// Set JSON header early
header('Content-Type: application/json');

try {
    // Step 1: Start session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Step 2: Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Authentication required', 
            'redirect' => 'login.php',

        ]);
        exit;
    }
    
    // Step 3: Validate input
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Ticket ID required']);
        exit;
    }
    
    $ticketId = intval($_GET['id']);
    $userId = $_SESSION['user_id'];
    
    if ($ticketId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid ticket ID']);
        exit;
    }
    
    // Step 4: Include database config
    $dbConfigPath = __DIR__ . '/../config/database.php';
    if (!file_exists($dbConfigPath)) {
        throw new Exception("Database config file not found at: " . $dbConfigPath);
    }
    
    require_once $dbConfigPath;
    
    // Step 5: Check if getDB function exists
    if (!function_exists('getDB')) {
        throw new Exception("getDB function not available after including database config");
    }
    
    // Step 6: Get database connection
    $pdo = getDB();
    if (!$pdo) {
        throw new Exception("Failed to get database connection");
    }
    
    // Step 7: Prepare safe query with minimal complexity
    $sessionKey = "last_check_ticket_{$ticketId}";
    $lastCheck = $_SESSION[$sessionKey] ?? date('Y-m-d H:i:s', time() - 300); // 5 minutes
    
    // Simple count query first
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as new_count
        FROM ticket_responses 
        WHERE ticket_id = ? AND created_at > ? AND user_id != ?
    ");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare SQL statement");
    }
    
    $executed = $stmt->execute([$ticketId, $lastCheck, $userId]);
    if (!$executed) {
        throw new Exception("Failed to execute SQL query: " . implode(', ', $stmt->errorInfo()));
    }
    
    $result = $stmt->fetch();
    if ($result === false) {
        throw new Exception("Failed to fetch query results");
    }
    
    $hasUpdates = $result['new_count'] > 0;
    $message = $hasUpdates ? 
               $result['new_count'] . ' new response' . ($result['new_count'] > 1 ? 's' : '') :
               'No updates';
    
    // Update session only if no updates
    if (!$hasUpdates) {
        $_SESSION[$sessionKey] = date('Y-m-d H:i:s');
    }
    
    // Step 8: Return successful response
    echo json_encode([
        'hasUpdates' => $hasUpdates,
        'message' => $message,
        'timestamp' => time()
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in safe_check_updates: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'type' => 'PDOException'
    ]);
    
} catch (Exception $e) {
    error_log("General error in safe_check_updates: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile()),
        'type' => 'Exception'
    ]);
    
} catch (Error $e) {
    error_log("Fatal error in safe_check_updates: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'Fatal error',
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile()),
        'type' => 'Error'
    ]);
}
?>