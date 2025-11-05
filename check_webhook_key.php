<?php
/**
 * Webhook API Key Checker
 * Upload to: https://resolveit.resourcestaffonline.com/check_webhook_key.php
 * This will show what API key the webhook expects
 */

// Load the webhook file and extract the API key
$webhookFile = __DIR__ . '/webhook_employee_sync.php';

if (!file_exists($webhookFile)) {
    die("ERROR: webhook_employee_sync.php not found at: $webhookFile");
}

$content = file_get_contents($webhookFile);

// Extract the WEBHOOK_SECRET_KEY value
preg_match("/define\('WEBHOOK_SECRET_KEY',\s*'([^']+)'/", $content, $matches);

header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Webhook API Key Checker</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        .key { background: #e3f2fd; padding: 15px; border-radius: 5px; font-family: monospace; word-break: break-all; border: 2px solid #2196F3; }
        .info { background: #fff3e0; padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid #ff9800; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <div class="box">
        <h1>🔐 Webhook API Key Checker</h1>
        <p><strong>Date:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <p><strong>Server:</strong> <?php echo $_SERVER['HTTP_HOST']; ?></p>
        
        <h2>Webhook Configuration</h2>
        
        <?php if (isset($matches[1])): ?>
            <table>
                <tr>
                    <th>Property</th>
                    <th>Value</th>
                </tr>
                <tr>
                    <td><strong>Webhook Secret Key</strong></td>
                    <td class="key"><?php echo htmlspecialchars($matches[1]); ?></td>
                </tr>
                <tr>
                    <td><strong>Key Length</strong></td>
                    <td><?php echo strlen($matches[1]); ?> characters</td>
                </tr>
                <tr>
                    <td><strong>First 10 chars</strong></td>
                    <td><?php echo htmlspecialchars(substr($matches[1], 0, 10)); ?>...</td>
                </tr>
                <tr>
                    <td><strong>Last 10 chars</strong></td>
                    <td>...<?php echo htmlspecialchars(substr($matches[1], -10)); ?></td>
                </tr>
            </table>
            
            <div class="info">
                <strong>✅ API Key Found!</strong><br>
                Copy this exact key to your <code>harley_sync_script.php</code> file (line 16):<br><br>
                <code>$API_KEY = '<?php echo htmlspecialchars($matches[1]); ?>';</code>
            </div>
            
        <?php else: ?>
            <div class="info" style="border-color: #f44336; background: #ffebee;">
                <strong>❌ Could not extract API key from webhook file</strong><br>
                The webhook file might have a different format.
            </div>
        <?php endif; ?>
        
        <h2>Expected Sync Script Configuration</h2>
        <p>Your <code>harley_sync_script.php</code> should have this exact line:</p>
        <pre style="background: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto;">$API_KEY = '<?php echo isset($matches[1]) ? htmlspecialchars($matches[1]) : 'KEY_NOT_FOUND'; ?>';</pre>
        
        <h2>Test Webhook Access</h2>
        <p>Test if webhook is accessible: <a href="webhook_employee_sync.php" target="_blank">webhook_employee_sync.php</a></p>
        <p><em>(Should show: "Method not allowed. Use POST.")</em></p>
        
        <hr style="margin: 30px 0;">
        <p style="color: #666; font-size: 12px;">
            <strong>Security Note:</strong> Delete this file after checking! It exposes your API key.<br>
            File location: <code><?php echo __FILE__; ?></code>
        </p>
    </div>
</body>
</html>
