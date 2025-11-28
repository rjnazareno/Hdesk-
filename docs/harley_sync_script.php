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
$WEBHOOK_URL = 'https://resolveit.resourcestaffonline.com/webhook_employee_sync.php'; // Production IThelp domain
$API_KEY = '333e582f2e6eccbf4d12274296fa6c53779a0d15c135da1863721c1e2509dece'; // Must match webhook_employee_sync.php

// Harley database configuration (already on Hostinger)
// You may already have these in your config file
// If so, include that file instead: require_once '../config/database.php';

$DB_HOST = 'localhost'; // Usually localhost on Hostinger
$DB_NAME = 'u816220874_harleyrss';
$DB_USER = 'u816220874_harley';
$DB_PASS = 'Z&e#mtcW3';

// ============================================
// SCRIPT START - NO NEED TO EDIT BELOW
// ============================================

header('Content-Type: text/html; charset=utf-8');
ob_start(); // Start output buffering for loading effect

echo "<!DOCTYPE html>
<html>
<head>
    <title>Harley → IThelp Employee Sync</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            padding: 20px;
            color: #e2e8f0;
        }
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .loading-content {
            text-align: center;
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(16px);
            padding: 40px;
            border-radius: 16px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(6, 182, 212, 0.2);
            border-top-color: #06b6d4;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .loading-text {
            font-size: 24px;
            font-weight: 600;
            background: linear-gradient(to right, #06b6d4, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        .loading-subtext {
            color: #94a3b8;
            font-size: 14px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(148, 163, 184, 0.2);
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        h1 {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(to right, #06b6d4, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        .sync-time {
            color: #94a3b8;
            font-size: 14px;
            margin-bottom: 30px;
        }
        .status {
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 12px;
            border: 1px solid;
            background: rgba(30, 41, 59, 0.3);
            backdrop-filter: blur(8px);
        }
        .success { 
            border-color: rgba(34, 197, 94, 0.5);
            background: rgba(34, 197, 94, 0.1);
            color: #86efac;
        }
        .error { 
            border-color: rgba(239, 68, 68, 0.5);
            background: rgba(239, 68, 68, 0.1);
            color: #fca5a5;
        }
        .info { 
            border-color: rgba(59, 130, 246, 0.5);
            background: rgba(59, 130, 246, 0.1);
            color: #93c5fd;
        }
        .warning { 
            border-color: rgba(251, 146, 60, 0.5);
            background: rgba(251, 146, 60, 0.1);
            color: #fdba74;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: rgba(30, 41, 59, 0.3);
            backdrop-filter: blur(8px);
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }
        th, td {
            padding: 16px;
            text-align: left;
        }
        th {
            background: rgba(6, 182, 212, 0.2);
            color: #06b6d4;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        td {
            color: #cbd5e1;
            border-top: 1px solid rgba(148, 163, 184, 0.1);
        }
        tr:hover td {
            background: rgba(148, 163, 184, 0.05);
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .summary-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(16px);
            padding: 24px;
            border-radius: 12px;
            text-align: center;
            border: 1px solid rgba(148, 163, 184, 0.2);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.4);
        }
        .summary-card h3 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 8px;
            background: linear-gradient(to right, #06b6d4, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .summary-card p {
            color: #94a3b8;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-card.created h3 { 
            background: linear-gradient(to right, #22c55e, #10b981);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .summary-card.updated h3 { 
            background: linear-gradient(to right, #3b82f6, #2563eb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .summary-card.failed h3 { 
            background: linear-gradient(to right, #ef4444, #dc2626);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .summary-card.warning h3 { 
            background: linear-gradient(to right, #fb923c, #f97316);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        pre {
            background: rgba(15, 23, 42, 0.5);
            padding: 20px;
            border-radius: 12px;
            overflow-x: auto;
            border: 1px solid rgba(148, 163, 184, 0.2);
            color: #cbd5e1;
            font-size: 13px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 14px 28px;
            background: linear-gradient(to right, #06b6d4, #3b82f6);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin: 10px 5px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(6, 182, 212, 0.4);
        }
        details {
            margin: 20px 0;
            background: rgba(30, 41, 59, 0.3);
            backdrop-filter: blur(8px);
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, 0.2);
            padding: 16px;
        }
        summary {
            cursor: pointer;
            font-weight: 600;
            color: #06b6d4;
            padding: 8px;
            user-select: none;
        }
        summary:hover {
            color: #3b82f6;
        }
        h3 {
            color: #e2e8f0;
            font-size: 20px;
            margin: 30px 0 15px 0;
            font-weight: 600;
        }
        hr {
            border: none;
            height: 1px;
            background: linear-gradient(to right, transparent, rgba(148, 163, 184, 0.3), transparent);
            margin: 40px 0;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        ul li {
            padding: 10px 0;
            color: #cbd5e1;
        }
        ul li:before {
            content: '▹ ';
            color: #06b6d4;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class='loading-overlay' id='loadingOverlay'>
        <div class='loading-content'>
            <div class='spinner'></div>
            <div class='loading-text'>Importing Employees</div>
            <div class='loading-subtext'>Syncing data from Harley to ResolveIT...</div>
        </div>
    </div>
    <div class='container'>
        <h1>🔄 Harley → ResolveIT Employee Sync</h1>
        <p class='sync-time'><strong>Sync Time:</strong> " . date('F d, Y - H:i:s') . "</p>
";

// Hide loading overlay after content loads
echo "<script>
    window.addEventListener('load', function() {
        setTimeout(function() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }, 500);
    });
</script>";

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
    // First, check what columns exist in the employees table
    $columnCheck = $pdo->query("DESCRIBE employees");
    $availableColumns = array_column($columnCheck->fetchAll(PDO::FETCH_ASSOC), 'Field');
    
    // Build query with only available columns
    $selectFields = ['id as employee_id', 'fname', 'lname', 'email'];
    $optionalFields = [
        'phone' => 'phone',
        'mobile' => 'phone',
        'cell' => 'phone',
        'department' => 'department',
        'dept' => 'department',
        'position' => 'position',
        'title' => 'position',
        'username' => 'username',
        'login' => 'username'
    ];
    
    // Add optional fields if they exist
    foreach ($optionalFields as $dbColumn => $alias) {
        if (in_array($dbColumn, $availableColumns)) {
            // Avoid duplicates (e.g., if both 'mobile' and 'phone' exist, use first found)
            if (!in_array($alias, array_values($selectFields))) {
                $selectFields[] = ($dbColumn !== $alias) ? "$dbColumn as $alias" : $dbColumn;
            }
        }
    }
    
    $sql = "SELECT 
                " . implode(",\n                ", $selectFields) . "
            FROM employees 
            WHERE 1=1
            ORDER BY id";
    
    echo "<details style='margin:10px 0;'>";
    echo "<summary style='cursor:pointer; color:#1565c0;'>📋 View SQL Query</summary>";
    echo "<pre>" . htmlspecialchars($sql) . "</pre>";
    echo "</details>";
    
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

// Debug: Show API key being used
echo "<details style='margin:10px 0;'>";
echo "<summary style='cursor:pointer; color:#666;'>🔍 Debug: API Key Info</summary>";
echo "<pre>";
echo "Sync Script API Key: " . $API_KEY . "\n";
echo "API Key Length: " . strlen($API_KEY) . " characters\n";
echo "Webhook URL: " . $WEBHOOK_URL . "\n";
echo "</pre>";
echo "</details>";

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
    CURLOPT_VERBOSE => false,
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
        echo "<div class='summary-card created'><h3>" . $result['summary']['created'] . "</h3><p>Created</p></div>";
        echo "<div class='summary-card updated'><h3>" . $result['summary']['updated'] . "</h3><p>Updated</p></div>";
        echo "<div class='summary-card failed'><h3>" . $result['summary']['failed'] . "</h3><p>Failed</p></div>";
        if ($result['summary']['not_in_source'] > 0) {
            echo "<div class='summary-card warning'><h3>" . $result['summary']['not_in_source'] . "</h3><p>Not in Harley</p></div>";
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
        <hr>
        <h3>Next Steps</h3>
        <ul>
            <li>Review the sync results above</li>
            <li><a href='" . $_SERVER['PHP_SELF'] . "' class='btn'>Run Sync Again</a></li>
            <li>Setup cron job in cPanel to run this automatically (daily/hourly)</li>
            <li><a href='https://resolveit.resourcestaffonline.com/admin/customers.php' class='btn'>View Employees in ResolveIT</a></li>
        </ul>
        
        <h3>🤖 Setup Automatic Sync (Optional)</h3>
        <p style='color:#94a3b8; line-height:1.8;'>To automatically sync employees every day, add this cron job in cPanel:</p>
        <pre>0 2 * * * /usr/bin/php /home/u816220874/public_html/harley.resourcestaffonline.com/Public/module/harley_sync_script.php > /dev/null 2>&1</pre>
        <p style='color:#94a3b8; line-height:1.8;'>This will run the sync every day at 2:00 AM.</p>
    </div>
</body>
</html>
";
?>
