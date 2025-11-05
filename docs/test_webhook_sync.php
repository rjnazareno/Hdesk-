<?php
/**
 * Test Script for Webhook Employee Sync
 * This demonstrates both partial and full sync modes
 */

// Configuration
$webhookUrl = 'http://localhost/IThelp/webhook_employee_sync.php';
$apiKey = 'your-secret-key-here-change-this'; // Must match webhook

// Test Data: Sample employees
$testEmployees = [
    [
        'employee_id' => 'EMP001',
        'fname' => 'John',
        'lname' => 'Doe',
        'email' => 'john.doe@harley.com',
        'phone' => '1234567890',
        'department' => 'IT',
        'position' => 'Developer',
        'username' => 'john.doe'
    ],
    [
        'employee_id' => 'EMP002',
        'fname' => 'Jane',
        'lname' => 'Smith',
        'email' => 'jane.smith@harley.com',
        'phone' => '0987654321',
        'department' => 'HR',
        'position' => 'Manager',
        'username' => 'jane.smith'
    ],
    [
        'employee_id' => 'EMP003',
        'fname' => 'Bob',
        'lname' => 'Johnson',
        'email' => 'bob.johnson@harley.com',
        'phone' => '5551234567',
        'department' => 'Sales',
        'position' => 'Sales Rep',
        'username' => 'bob.johnson'
    ]
];

echo "<h1>Webhook Employee Sync Test</h1>";
echo "<hr>";

// TEST 1: Partial Sync (default behavior)
echo "<h2>Test 1: Partial Sync (2 employees)</h2>";
$payload1 = [
    'sync_mode' => 'partial',
    'employees' => array_slice($testEmployees, 0, 2) // Only send first 2
];

$result1 = sendWebhookRequest($webhookUrl, $apiKey, $payload1);
echo "<pre>" . json_encode($result1, JSON_PRETTY_PRINT) . "</pre>";
echo "<hr>";

// TEST 2: Full Sync (all employees)
echo "<h2>Test 2: Full Sync (all 3 employees)</h2>";
$payload2 = [
    'sync_mode' => 'full',
    'employees' => $testEmployees // Send all employees
];

$result2 = sendWebhookRequest($webhookUrl, $apiKey, $payload2);
echo "<pre>" . json_encode($result2, JSON_PRETTY_PRINT) . "</pre>";
echo "<hr>";

// TEST 3: Update Existing Employee
echo "<h2>Test 3: Update Existing Employee</h2>";
$updatedEmployee = $testEmployees[0];
$updatedEmployee['department'] = 'Engineering'; // Changed department
$updatedEmployee['position'] = 'Senior Developer'; // Changed position

$payload3 = [
    'sync_mode' => 'partial',
    'employees' => [$updatedEmployee]
];

$result3 = sendWebhookRequest($webhookUrl, $apiKey, $payload3);
echo "<pre>" . json_encode($result3, JSON_PRETTY_PRINT) . "</pre>";
echo "<hr>";

// TEST 4: Invalid Data (missing required field)
echo "<h2>Test 4: Invalid Data Test</h2>";
$invalidEmployee = [
    'employee_id' => 'EMP999',
    'fname' => 'Invalid',
    // Missing 'lname' and 'email'
];

$payload4 = [
    'employees' => [$invalidEmployee]
];

$result4 = sendWebhookRequest($webhookUrl, $apiKey, $payload4);
echo "<pre>" . json_encode($result4, JSON_PRETTY_PRINT) . "</pre>";
echo "<hr>";

// TEST 5: Wrong API Key
echo "<h2>Test 5: Wrong API Key Test</h2>";
$result5 = sendWebhookRequest($webhookUrl, 'wrong-api-key', $payload1);
echo "<pre>" . json_encode($result5, JSON_PRETTY_PRINT) . "</pre>";

/**
 * Helper function to send webhook request
 */
function sendWebhookRequest($url, $apiKey, $payload) {
    $ch = curl_init($url);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-Key: ' . $apiKey
        ],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: #f5f5f5;
}
h1, h2 {
    color: #333;
}
pre {
    background: #fff;
    border: 1px solid #ddd;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
}
hr {
    margin: 30px 0;
    border: none;
    border-top: 2px solid #ccc;
}
</style>
