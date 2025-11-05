<?php
/**
 * Harley Employee Sync Script
 * Upload this file to: https://harley.resourcestaffonline.com/Public/module/
 * 
 * This script fetches all employees from Harley database
 * and syncs them to IThelp system via webhook
 */

// ============================================
// CONFIGURATION - UPDATE THESE VALUES
// ============================================

// IThelp webhook configuration
$WEBHOOK_URL = 'http://localhost/IThelp/webhook_employee_sync.php'; // Change to your IThelp domain
$API_KEY = '333e582f2e6eccbf4d12274296fa6c53779a0d15c135da1863721c1e2509dece'; // Must match webhook_employee_sync.php

// Harley database configuration (already on Hostinger)
// You may already have these in your config file
// If so, include that file instead: require_once '../config/database.php';

$DB_HOST = 'localhost'; // Usually localhost on Hostinger
$DB_NAME = 'your_harley_database_name';
$DB_USER = 'your_database_username';
$DB_PASS = 'your_database_password';

// ============================================
// SCRIPT START - NO NEED TO EDIT BELOW
// ============================================

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Harley → IThelp Employee Sync</title>
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
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔄 Harley → IThelp Employee Sync</h1>
        <p><strong>Sync Time:</strong> " . date('Y-m-d H:i:s') . "</p>
";

// Step 1: Connect to Harley database
echo "<div class='status info'><strong>Step 1:</strong> Connecting to Harley database...</div>";

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<div class='status success'>✅ Connected to Harley database successfully</div>";
} catch (PDOException $e) {
    echo "<div class='status error'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><strong>Fix:</strong> Check your database credentials in this file (lines 13-16)</p>";
    echo "</div></body></html>";
    exit;
}

// Step 2: Fetch all employees from Harley
echo "<div class='status info'><strong>Step 2:</strong> Fetching employees from Harley database...</div>";

try {
    // Adjust this query based on your actual Harley database structure
    // Common table names: employees, users, staff, team_members
    $sql = "SELECT 
                id as employee_id,
                fname,
                lname,
                email,
                phone,
                department,
                position,
                username
            FROM employees 
            WHERE 1=1
            ORDER BY id";
    
    $stmt = $pdo->query($sql);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($employees)) {
        echo "<div class='status warning'>⚠️ No employees found in Harley database. Check your table name and query.</div>";
        echo "<p><strong>Current query:</strong></p><pre>" . htmlspecialchars($sql) . "</pre>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<div class='status success'>✅ Found " . count($employees) . " employees in Harley database</div>";
    
    // Show sample of first 3 employees
    echo "<p><strong>Sample employees:</strong></p>";
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Department</th></tr>";
    foreach (array_slice($employees, 0, 3) as $emp) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($emp['employee_id']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['fname'] . ' ' . $emp['lname']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['email']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['department'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    if (count($employees) > 3) {
        echo "<tr><td colspan='4' style='text-align:center; color:#666;'>... and " . (count($employees) - 3) . " more</td></tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "<div class='status error'>❌ Failed to fetch employees: " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<p><strong>Fix:</strong> Your employees table might have different column names. Check your database structure.</p>";
    echo "</div></body></html>";
    exit;
}

// Step 3: Send to IThelp webhook
echo "<div class='status info'><strong>Step 3:</strong> Sending employees to IThelp webhook...</div>";

$payload = [
    'sync_mode' => 'full', // Use full sync to detect missing employees
    'employees' => $employees
];

$ch = curl_init($WEBHOOK_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-API-Key: ' . $API_KEY
    ],
    CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Step 4: Display results
if ($curlError) {
    echo "<div class='status error'>❌ cURL Error: " . htmlspecialchars($curlError) . "</div>";
    echo "<p><strong>Fix:</strong> Check that your webhook URL is correct: <code>$WEBHOOK_URL</code></p>";
} elseif ($httpCode !== 200) {
    echo "<div class='status error'>❌ Webhook returned HTTP $httpCode</div>";
    echo "<p><strong>Response:</strong></p><pre>" . htmlspecialchars($response) . "</pre>";
    
    if ($httpCode == 401) {
        echo "<p><strong>Fix:</strong> API key mismatch. Make sure the API_KEY in this file matches WEBHOOK_SECRET_KEY in webhook_employee_sync.php</p>";
    } elseif ($httpCode == 404) {
        echo "<p><strong>Fix:</strong> Webhook URL not found. Check the URL: <code>$WEBHOOK_URL</code></p>";
    }
} else {
    $result = json_decode($response, true);
    
    if ($result && $result['status'] === 'completed') {
        echo "<div class='status success'>✅ Sync completed successfully!</div>";
        
        // Display summary
        echo "<div class='summary'>";
        echo "<div class='summary-card'><h3>" . $result['summary']['total'] . "</h3><p>Total Sent</p></div>";
        echo "<div class='summary-card' style='border-color:#4CAF50;'><h3>" . $result['summary']['created'] . "</h3><p>Created</p></div>";
        echo "<div class='summary-card' style='border-color:#2196F3;'><h3>" . $result['summary']['updated'] . "</h3><p>Updated</p></div>";
        echo "<div class='summary-card' style='border-color:#f44336;'><h3>" . $result['summary']['failed'] . "</h3><p>Failed</p></div>";
        if ($result['summary']['not_in_source'] > 0) {
            echo "<div class='summary-card' style='border-color:#ff9800;'><h3>" . $result['summary']['not_in_source'] . "</h3><p>Not in Harley</p></div>";
        }
        echo "</div>";
        
        // Show created employees
        if (!empty($result['details']['success'])) {
            echo "<h3>✨ Newly Created Employees</h3>";
            echo "<table>";
            echo "<tr><th>Employee ID</th><th>Name</th><th>Email</th></tr>";
            foreach ($result['details']['success'] as $emp) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($emp['employee_id']) . "</td>";
                echo "<td>" . htmlspecialchars($emp['name']) . "</td>";
                echo "<td>" . htmlspecialchars($emp['email']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Show updated employees
        if (!empty($result['details']['updated'])) {
            echo "<h3>🔄 Updated Employees</h3>";
            echo "<table>";
            echo "<tr><th>Employee ID</th><th>Name</th><th>Email</th></tr>";
            foreach ($result['details']['updated'] as $emp) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($emp['employee_id']) . "</td>";
                echo "<td>" . htmlspecialchars($emp['name']) . "</td>";
                echo "<td>" . htmlspecialchars($emp['email']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Show failed employees
        if (!empty($result['details']['failed'])) {
            echo "<h3>❌ Failed Employees</h3>";
            echo "<table>";
            echo "<tr><th>Employee ID</th><th>Email</th><th>Error</th></tr>";
            foreach ($result['details']['failed'] as $emp) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($emp['employee_id']) . "</td>";
                echo "<td>" . htmlspecialchars($emp['email']) . "</td>";
                echo "<td style='color:#c62828;'>" . htmlspecialchars($emp['error']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Show employees not in source
        if (!empty($result['details']['not_in_source'])) {
            echo "<h3>⚠️ Employees in IThelp but NOT in Harley</h3>";
            echo "<p>These employees exist in IThelp but were not found in Harley database:</p>";
            echo "<table>";
            echo "<tr><th>Employee ID</th><th>Name</th><th>Email</th><th>Note</th></tr>";
            foreach ($result['details']['not_in_source'] as $emp) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($emp['employee_id']) . "</td>";
                echo "<td>" . htmlspecialchars($emp['name']) . "</td>";
                echo "<td>" . htmlspecialchars($emp['email']) . "</td>";
                echo "<td>" . htmlspecialchars($emp['note']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Show full response
        echo "<details style='margin-top:20px;'>";
        echo "<summary style='cursor:pointer; color:#1565c0; font-weight:bold;'>📋 View Full Response JSON</summary>";
        echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
        echo "</details>";
        
    } else {
        echo "<div class='status error'>❌ Unexpected response format</div>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
}

echo "
        <hr style='margin:30px 0;'>
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>✅ Review the sync results above</li>
            <li>🔄 <a href='" . $_SERVER['PHP_SELF'] . "' class='btn'>Run Sync Again</a></li>
            <li>📅 Setup cron job in cPanel to run this automatically (daily/hourly)</li>
            <li>🔗 <a href='https://your-ithelp-domain.com/admin/customers.php' class='btn' style='background:#2196F3;'>View Employees in IThelp</a></li>
        </ul>
        
        <h3>🤖 Setup Automatic Sync (Optional)</h3>
        <p>To automatically sync employees every day, add this cron job in cPanel:</p>
        <pre>0 2 * * * /usr/bin/php /home/your-username/public_html/Public/module/harley_sync_script.php > /dev/null 2>&1</pre>
        <p>This will run the sync every day at 2:00 AM.</p>
    </div>
</body>
</html>
";
?>
