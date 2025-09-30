<?php
/**
 * FCM Token Cleanup and Regeneration Tool
 */
require_once __DIR__ . '/includes/firebase_notifications.php';
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>FCM Token Cleanup</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;background:#f5f5f5;} .container{background:white;padding:20px;margin:15px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);} .success{color:#28a745;background:#d4edda;padding:10px;border-radius:5px;border-left:4px solid #28a745;} .error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:5px;border-left:4px solid #dc3545;} .warning{color:#856404;background:#fff3cd;padding:10px;border-radius:5px;border-left:4px solid #ffc107;} .info{color:#17a2b8;background:#d1ecf1;padding:10px;border-radius:5px;border-left:4px solid #17a2b8;} button{background:#0D8ABC;color:white;padding:10px 15px;border:none;border-radius:5px;cursor:pointer;margin:5px;} button:hover{background:#0a6d94;} button.danger{background:#dc3545;} button.success{background:#28a745;} pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow-x:auto;font-size:12px;}</style></head><body>";

echo "<h1>🧹 FCM Token Cleanup & Regeneration</h1>";

$db = Database::getInstance()->getConnection();
$notificationSender = new FirebaseNotificationSender();

// Check current tokens
echo "<div class='container'>";
echo "<h2>🔍 Current FCM Token Status</h2>";

$stmt = $db->prepare("SELECT id, token, user_id, user_type, created_at, is_active FROM fcm_tokens ORDER BY created_at DESC");
$stmt->execute();
$allTokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($allTokens) {
    echo "<div class='info'><h4>📋 Found " . count($allTokens) . " FCM Token(s)</h4></div>";
    
    echo "<table style='width:100%;border-collapse:collapse;margin:10px 0;'>";
    echo "<tr style='background:#f8f9fa;'>";
    echo "<th style='padding:8px;border:1px solid #ddd;'>ID</th>";
    echo "<th style='padding:8px;border:1px solid #ddd;'>User</th>";
    echo "<th style='padding:8px;border:1px solid #ddd;'>Type</th>";
    echo "<th style='padding:8px;border:1px solid #ddd;'>Token (Last 20)</th>";
    echo "<th style='padding:8px;border:1px solid #ddd;'>Status</th>";
    echo "<th style='padding:8px;border:1px solid #ddd;'>Created</th>";
    echo "<th style='padding:8px;border:1px solid #ddd;'>Action</th>";
    echo "</tr>";
    
    foreach ($allTokens as $token) {
        echo "<tr>";
        echo "<td style='padding:8px;border:1px solid #ddd;'>{$token['id']}</td>";
        echo "<td style='padding:8px;border:1px solid #ddd;'>{$token['user_id']}</td>";
        echo "<td style='padding:8px;border:1px solid #ddd;'>{$token['user_type']}</td>";
        echo "<td style='padding:8px;border:1px solid #ddd;font-family:monospace;'>..." . substr($token['token'], -20) . "</td>";
        
        $status = $token['is_active'] ? 'Active' : 'Inactive';
        $statusColor = $token['is_active'] ? '#28a745' : '#dc3545';
        echo "<td style='padding:8px;border:1px solid #ddd;color:{$statusColor};'>{$status}</td>";
        
        echo "<td style='padding:8px;border:1px solid #ddd;'>{$token['created_at']}</td>";
        echo "<td style='padding:8px;border:1px solid #ddd;'>";
        echo "<button onclick=\"testToken('{$token['token']}', {$token['id']})\" style='padding:5px 8px;font-size:12px;'>Test</button>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'><h4>⚠️ No FCM Tokens Found</h4><p>Need to enable notifications first</p></div>";
}

echo "</div>";

// Cleanup actions
echo "<div class='container'>";
echo "<h2>🧹 Cleanup Actions</h2>";

echo "<div style='display:flex;gap:15px;flex-wrap:wrap;margin:15px 0;'>";
echo "<button onclick='cleanupInvalidTokens()' class='danger'>❌ Remove Invalid Tokens</button>";
echo "<button onclick='deactivateAllTokens()' class='warning'>⏸️ Deactivate All Tokens</button>";
echo "<button onclick='clearAllTokens()' class='danger'>🗑️ Clear All Tokens</button>";
echo "<button onclick='regenerateTokens()' class='success'>🔄 Force Regenerate</button>";
echo "</div>";

echo "<div id='cleanup-results'></div>";
echo "</div>";

// Token regeneration guide
echo "<div class='container'>";
echo "<h2>🎯 How to Fix UNREGISTERED Error</h2>";

echo "<div class='error'>";
echo "<h4>❌ Error Meaning:</h4>";
echo "<p><strong>UNREGISTERED:</strong> FCM token is invalid, expired, or no longer exists</p>";
echo "</div>";

echo "<h3>🔧 Solution Steps:</h3>";
echo "<ol>";
echo "<li><strong>Clean invalid tokens:</strong> Click \"Remove Invalid Tokens\" above</li>";
echo "<li><strong>Clear browser data:</strong> Ctrl+Shift+Del → Clear all</li>";
echo "<li><strong>Re-enable notifications:</strong> <a href='dashboard.php'>Go to Dashboard</a> → Enable Notifications</li>";
echo "<li><strong>Test new token:</strong> Return here and test notifications</li>";
echo "</ol>";

echo "<div class='info'>";
echo "<h4>💡 Why This Happens:</h4>";
echo "<ul>";
echo "<li>Service worker registration changed (moving to domain root)</li>";
echo "<li>Browser cache cleared or app reinstalled</li>";
echo "<li>FCM tokens naturally expire over time</li>";
echo "<li>User denied then re-allowed notification permissions</li>";
echo "</ul>";
echo "</div>";

echo "</div>";

echo "<script>
async function testToken(token, tokenId) {
    const shortToken = '...' + token.slice(-20);
    const results = document.getElementById('cleanup-results');
    results.innerHTML = `<div class='info'><h4>🧪 Testing Token ${tokenId}: ${shortToken}</h4><p>Sending test notification...</p></div>`;
    
    try {
        const response = await fetch('test-token-validity.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({token: token, tokenId: tokenId})
        });
        
        const result = await response.json();
        
        if (result.success) {
            results.innerHTML = `<div class='success'><h4>✅ Token ${tokenId} is Valid</h4><p>Test notification sent successfully</p></div>`;
        } else {
            let errorMsg = result.error || 'Unknown error';
            if (typeof errorMsg === 'object') errorMsg = JSON.stringify(errorMsg);
            results.innerHTML = `<div class='error'><h4>❌ Token ${tokenId} is Invalid</h4><p>Error: ${errorMsg}</p><p>This token should be removed</p></div>`;
        }
    } catch (error) {
        results.innerHTML = `<div class='error'><h4>❌ Test Failed</h4><p>Error: ${error.message}</p></div>`;
    }
}

async function cleanupInvalidTokens() {
    const results = document.getElementById('cleanup-results');
    results.innerHTML = `<div class='info'><h4>🧹 Cleaning Invalid Tokens</h4><p>Testing all tokens and removing invalid ones...</p></div>`;
    
    try {
        const response = await fetch('cleanup-invalid-tokens.php', {method: 'POST'});
        const result = await response.json();
        
        if (result.success) {
            results.innerHTML = `<div class='success'><h4>✅ Cleanup Complete</h4><p>Removed ${result.removed} invalid token(s)</p><p>Kept ${result.kept} valid token(s)</p></div>`;
            setTimeout(() => location.reload(), 2000);
        } else {
            results.innerHTML = `<div class='error'><h4>❌ Cleanup Failed</h4><p>${result.error}</p></div>`;
        }
    } catch (error) {
        results.innerHTML = `<div class='error'><h4>❌ Cleanup Error</h4><p>${error.message}</p></div>`;
    }
}

async function deactivateAllTokens() {
    if (!confirm('Deactivate all FCM tokens? Users will need to re-enable notifications.')) return;
    
    const results = document.getElementById('cleanup-results');
    results.innerHTML = `<div class='info'><h4>⏸️ Deactivating All Tokens</h4></div>`;
    
    try {
        const response = await fetch('deactivate-all-tokens.php', {method: 'POST'});
        const result = await response.json();
        
        if (result.success) {
            results.innerHTML = `<div class='success'><h4>✅ All Tokens Deactivated</h4><p>Deactivated ${result.count} token(s)</p></div>`;
            setTimeout(() => location.reload(), 2000);
        } else {
            results.innerHTML = `<div class='error'><h4>❌ Deactivation Failed</h4><p>${result.error}</p></div>`;
        }
    } catch (error) {
        results.innerHTML = `<div class='error'><h4>❌ Error</h4><p>${error.message}</p></div>`;
    }
}

async function clearAllTokens() {
    if (!confirm('DELETE all FCM tokens? This cannot be undone!')) return;
    
    const results = document.getElementById('cleanup-results');
    results.innerHTML = `<div class='warning'><h4>🗑️ Clearing All Tokens</h4></div>`;
    
    try {
        const response = await fetch('clear-all-tokens.php', {method: 'POST'});
        const result = await response.json();
        
        if (result.success) {
            results.innerHTML = `<div class='success'><h4>✅ All Tokens Cleared</h4><p>Deleted ${result.count} token(s)</p></div>`;
            setTimeout(() => location.reload(), 2000);
        } else {
            results.innerHTML = `<div class='error'><h4>❌ Clear Failed</h4><p>${result.error}</p></div>`;
        }
    } catch (error) {
        results.innerHTML = `<div class='error'><h4>❌ Error</h4><p>${error.message}</p></div>`;
    }
}

function regenerateTokens() {
    const results = document.getElementById('cleanup-results');
    results.innerHTML = `<div class='info'><h4>🔄 Force Regenerate</h4><p>Go to dashboard and enable notifications to generate fresh tokens</p></div>`;
    setTimeout(() => window.open('dashboard.php', '_blank'), 1000);
}
</script>";

echo "<div class='container' style='text-align:center;'>";
echo "<h3>🎯 Next Steps</h3>";
echo "<p><a href='dashboard.php' style='background:#28a745;color:white;padding:12px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🔔 Enable Notifications</a> ";
echo "<a href='fix-service-worker.html' style='background:#0D8ABC;color:white;padding:12px 20px;text-decoration:none;border-radius:5px;margin:5px;'>🔧 Fix Service Worker</a></p>";
echo "</div>";

echo "</body></html>";
?>