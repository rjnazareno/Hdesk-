<?php
/**
 * Example: How to send employee data from Harley website to IThelp webhook
 * Place this file on your Harley website (Hostinger)
 */

// Configuration
$webhook_url = 'http://localhost/IThelp/webhook_employee_sync.php'; // Change to your IThelp URL
$api_key = 'your-secret-key-here-change-this'; // Must match WEBHOOK_SECRET_KEY in webhook

// Connect to your Harley database
$harley_db_host = 'localhost';
$harley_db_name = 'your_harley_database';
$harley_db_user = 'your_username';
$harley_db_pass = 'your_password';

try {
    // Connect to Harley database
    $pdo = new PDO(
        "mysql:host=$harley_db_host;dbname=$harley_db_name;charset=utf8mb4",
        $harley_db_user,
        $harley_db_pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch employees from Harley database
    // Adjust column names to match your Harley employees table
    $stmt = $pdo->query("
        SELECT 
            employee_id,
            first_name as fname,
            last_name as lname,
            email,
            phone,
            department,
            position,
            username
        FROM employees 
        WHERE status = 'active'
        ORDER BY employee_id
    ");
    
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
// Prepare payload
// Use 'full' sync mode to sync ALL employees and detect missing ones
$payload = [
    'sync_mode' => 'full',  // Set to 'partial' if you only want to sync specific employees
    'employees' => $employees
];    // Send to IThelp webhook
    $ch = curl_init($webhook_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: ' . $api_key
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Display results
    echo "<h2>Employee Sync Results</h2>";
    echo "<p>HTTP Status: $http_code</p>";
    echo "<pre>" . print_r(json_decode($response, true), true) . "</pre>";
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        echo "<p style='color: green;'>✅ Sync completed successfully!</p>";
        echo "<p>Created: {$result['summary']['created']}</p>";
        echo "<p>Updated: {$result['summary']['updated']}</p>";
        echo "<p>Failed: {$result['summary']['failed']}</p>";
    } else {
        echo "<p style='color: red;'>❌ Sync failed!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
