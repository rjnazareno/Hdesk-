<?php
/**
 * Test Webhook Sync Script
 * Tests the employee sync webhook locally before deploying to Harley
 * 
 * Usage: Run this file in your browser: http://localhost/ResolveIT/test_webhook_sync.php
 */

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Webhook Sync - IThelp</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .success { background: #e8f5e9; border-color: #4CAF50; color: #2e7d32; }
        .error { background: #ffebee; border-color: #f44336; color: #c62828; }
        .info { background: #e3f2fd; border-color: #2196F3; color: #1565c0; }
        .warning { background: #fff3e0; border-color: #ff9800; color: #e65100; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #4CAF50;
            color: white;
        }
        tr:hover {
            background: #f5f5f5;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .summary-card {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            text-align: center;
            border: 2px solid #ddd;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 36px;
        }
        .summary-card p {
            margin: 0;
            color: #666;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            border: 1px solid #ddd;
            max-height: 400px;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🧪 Test Webhook Employee Sync</h1>
        <p><strong>Test Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        // Configuration
        $webhookUrl = 'http://localhost/ResolveIT/webhook_employee_sync.php';
        $apiKey = '333e582f2e6eccbf4d12274296fa6c53779a0d15c135da1863721c1e2509dece';
        
        echo "<div class='status info'><strong>Configuration:</strong><br>";
        echo "Webhook URL: <code>$webhookUrl</code><br>";
        echo "API Key: <code>" . substr($apiKey, 0, 20) . "...</code></div>";
        
        // Test data - sample employees
        $testEmployees = [
            [
                'employee_id' => 'TEST001',
                'fname' => 'Alice',
                'lname' => 'Johnson',
                'email' => 'alice.johnson@company.com',
                'phone' => '555-0101',
                'department' => 'Marketing',
                'position' => 'Marketing Manager',
                'username' => 'alice.johnson'
            ],
            [
                'employee_id' => 'TEST002',
                'fname' => 'Bob',
                'lname' => 'Smith',
                'email' => 'bob.smith@company.com',
                'phone' => '555-0102',
                'department' => 'Sales',
                'position' => 'Sales Representative',
                'username' => 'bob.smith'
            ],
            [
                'employee_id' => 'TEST003',
                'fname' => 'Carol',
                'lname' => 'Williams',
                'email' => 'carol.williams@company.com',
                'phone' => '555-0103',
                'department' => 'IT',
                'position' => 'Software Developer',
                'username' => 'carol.williams'
            ],
        ];
        
        echo "<div class='status info'><strong>Test Data:</strong> Sending " . count($testEmployees) . " test employees</div>";
        
        // Show test data
        echo "<table>";
        echo "<tr><th>Employee ID</th><th>Name</th><th>Email</th><th>Department</th><th>Position</th></tr>";
        foreach ($testEmployees as $emp) {
            echo "<tr>";
            echo "<td>{$emp['employee_id']}</td>";
            echo "<td>{$emp['fname']} {$emp['lname']}</td>";
            echo "<td>{$emp['email']}</td>";
            echo "<td>{$emp['department']}</td>";
            echo "<td>{$emp['position']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Prepare payload
        $payload = [
            'sync_mode' => 'partial', // Use partial for testing
            'employees' => $testEmployees
        ];
        
        echo "<div class='status info'><strong>Step 1:</strong> Sending POST request to webhook...</div>";
        
        // Send request
        $ch = curl_init($webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-API-Key: ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Display results
        if ($curlError) {
            echo "<div class='status error'>❌ cURL Error: " . htmlspecialchars($curlError) . "</div>";
            echo "<p><strong>Fix:</strong> Check that Apache is running and the webhook URL is correct.</p>";
        } elseif ($httpCode !== 200) {
            echo "<div class='status error'>❌ Webhook returned HTTP $httpCode</div>";
            echo "<p><strong>Response:</strong></p><pre>" . htmlspecialchars($response) . "</pre>";
            
            if ($httpCode == 401) {
                echo "<p><strong>Fix:</strong> API key mismatch. Check the WEBHOOK_SECRET_KEY in webhook_employee_sync.php</p>";
            } elseif ($httpCode == 404) {
                echo "<p><strong>Fix:</strong> Webhook file not found. Check the URL: <code>$webhookUrl</code></p>";
            }
        } else {
            $result = json_decode($response, true);
            
            if ($result && $result['status'] === 'completed') {
                echo "<div class='status success'>✅ Webhook sync completed successfully!</div>";
                
                // Display summary
                echo "<div class='summary'>";
                echo "<div class='summary-card'><h3>" . $result['summary']['total'] . "</h3><p>Total Sent</p></div>";
                echo "<div class='summary-card' style='border-color:#4CAF50;'><h3>" . $result['summary']['created'] . "</h3><p>Created</p></div>";
                echo "<div class='summary-card' style='border-color:#2196F3;'><h3>" . $result['summary']['updated'] . "</h3><p>Updated</p></div>";
                echo "<div class='summary-card' style='border-color:#f44336;'><h3>" . $result['summary']['failed'] . "</h3><p>Failed</p></div>";
                echo "</div>";
                
                // Show details
                if (!empty($result['details']['success'])) {
                    echo "<h3>✨ Newly Created Employees</h3>";
                    echo "<table>";
                    echo "<tr><th>ID</th><th>Employee ID</th><th>Name</th><th>Email</th></tr>";
                    foreach ($result['details']['success'] as $emp) {
                        echo "<tr>";
                        echo "<td>{$emp['id']}</td>";
                        echo "<td>{$emp['employee_id']}</td>";
                        echo "<td>{$emp['name']}</td>";
                        echo "<td>{$emp['email']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
                if (!empty($result['details']['updated'])) {
                    echo "<h3>🔄 Updated Employees</h3>";
                    echo "<table>";
                    echo "<tr><th>Employee ID</th><th>Name</th><th>Email</th></tr>";
                    foreach ($result['details']['updated'] as $emp) {
                        echo "<tr>";
                        echo "<td>{$emp['employee_id']}</td>";
                        echo "<td>{$emp['name']}</td>";
                        echo "<td>{$emp['email']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
                if (!empty($result['details']['failed'])) {
                    echo "<h3>❌ Failed Employees</h3>";
                    echo "<table>";
                    echo "<tr><th>Employee ID</th><th>Email</th><th>Error</th></tr>";
                    foreach ($result['details']['failed'] as $emp) {
                        echo "<tr>";
                        echo "<td>{$emp['employee_id']}</td>";
                        echo "<td>{$emp['email']}</td>";
                        echo "<td style='color:#c62828;'>{$emp['error']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
                echo "<details style='margin-top:20px;'>";
                echo "<summary style='cursor:pointer; color:#1565c0; font-weight:bold;'>📋 View Full Response JSON</summary>";
                echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
                echo "</details>";
                
                echo "<div class='status success' style='margin-top:30px;'>";
                echo "<strong>✅ Test Passed!</strong><br>";
                echo "The webhook is working correctly. You can now deploy the harley_sync_script.php to your Harley server.";
                echo "</div>";
                
            } else {
                echo "<div class='status error'>❌ Unexpected response format</div>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
            }
        }
        ?>
        
        <hr style='margin:30px 0;'>
        <h3>📋 Next Steps</h3>
        <ol>
            <li>✅ Test completed - review the results above</li>
            <li>🔄 <a href="<?php echo $_SERVER['PHP_SELF']; ?>" style="color:#2196F3;">Run test again</a></li>
            <li>🗑️ Clean up test data: <a href="http://localhost/ResolveIT/admin/customers.php" target="_blank" style="color:#2196F3;">View employees</a> and delete TEST001, TEST002, TEST003</li>
            <li>📤 Upload <code>harley_sync_script.php</code> to your Harley server (in <code>Public/module/</code> folder)</li>
            <li>⚙️ Configure the script with your Harley database credentials</li>
            <li>🚀 Run the script from your Harley server to start syncing real employees</li>
        </ol>
        
        <h3>🔧 Configuration Checklist</h3>
        <ul>
            <li>✅ Webhook endpoint created: <code>webhook_employee_sync.php</code></li>
            <li>✅ Employee model updated with <code>employee_id</code> support</li>
            <li>✅ API key authentication enabled</li>
            <li>⚠️ Update API key in production for security</li>
            <li>⚠️ Configure Harley database credentials in <code>harley_sync_script.php</code></li>
            <li>⚠️ Update <code>$WEBHOOK_URL</code> in <code>harley_sync_script.php</code> to your production domain</li>
        </ul>
    </div>
</body>
</html>
