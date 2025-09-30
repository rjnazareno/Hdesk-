<?php
/**
 * Check FCM Tokens in Database
 * Debug tool to see if FCM tokens are being saved
 */

require_once 'config/database.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 FCM Token Checker</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h1 class="text-3xl font-bold mb-6 text-center">🔍 FCM Token Database Checker</h1>
            
            <?php
            try {
                $db = Database::getInstance()->getConnection();
                
                // Check if fcm_tokens table exists
                echo "<div class='bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6'>";
                echo "<h3 class='font-bold text-blue-800 mb-2'>📊 Table Status</h3>";
                
                $tableCheck = $db->query("SHOW TABLES LIKE 'fcm_tokens'");
                if ($tableCheck->rowCount() > 0) {
                    echo "<p class='text-green-600'>✅ fcm_tokens table exists</p>";
                    
                    // Get table structure
                    echo "<h4 class='font-bold mt-4 mb-2'>Table Structure:</h4>";
                    $columns = $db->query("DESCRIBE fcm_tokens")->fetchAll(PDO::FETCH_ASSOC);
                    echo "<div class='bg-gray-100 p-2 rounded text-sm'>";
                    foreach ($columns as $column) {
                        echo "<div>{$column['Field']} - {$column['Type']} ({$column['Null']})</div>";
                    }
                    echo "</div>";
                    
                } else {
                    echo "<p class='text-red-600'>❌ fcm_tokens table does not exist</p>";
                    echo "<p class='text-yellow-600'>⚠️ Run create-fcm-tables.php first</p>";
                }
                echo "</div>";
                
                // Show current tokens
                echo "<div class='bg-green-50 border border-green-200 rounded-lg p-4 mb-6'>";
                echo "<h3 class='font-bold text-green-800 mb-2'>🔑 Current FCM Tokens</h3>";
                
                if ($tableCheck->rowCount() > 0) {
                    $stmt = $db->prepare("SELECT * FROM fcm_tokens ORDER BY updated_at DESC");
                    $stmt->execute();
                    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($tokens) > 0) {
                        echo "<p class='mb-4 font-semibold'>Found " . count($tokens) . " FCM tokens:</p>";
                        echo "<div class='overflow-x-auto'>";
                        echo "<table class='min-w-full bg-white border border-gray-300'>";
                        echo "<thead class='bg-gray-50'>";
                        echo "<tr>";
                        echo "<th class='px-4 py-2 border'>ID</th>";
                        echo "<th class='px-4 py-2 border'>User ID</th>";
                        echo "<th class='px-4 py-2 border'>User Type</th>";
                        echo "<th class='px-4 py-2 border'>Token (Preview)</th>";
                        echo "<th class='px-4 py-2 border'>Active</th>";
                        echo "<th class='px-4 py-2 border'>Created</th>";
                        echo "<th class='px-4 py-2 border'>Last Updated</th>";
                        echo "</tr>";
                        echo "</thead>";
                        echo "<tbody>";
                        
                        foreach ($tokens as $token) {
                            echo "<tr>";
                            echo "<td class='px-4 py-2 border'>{$token['id']}</td>";
                            echo "<td class='px-4 py-2 border'>{$token['user_id']}</td>";
                            echo "<td class='px-4 py-2 border'>{$token['user_type']}</td>";
                            echo "<td class='px-4 py-2 border font-mono text-xs'>" . substr($token['token'], 0, 30) . "...</td>";
                            echo "<td class='px-4 py-2 border'>" . ($token['is_active'] ? '✅' : '❌') . "</td>";
                            echo "<td class='px-4 py-2 border text-xs'>{$token['created_at']}</td>";
                            echo "<td class='px-4 py-2 border text-xs'>{$token['updated_at']}</td>";
                            echo "</tr>";
                        }
                        
                        echo "</tbody>";
                        echo "</table>";
                        echo "</div>";
                    } else {
                        echo "<p class='text-yellow-600'>⚠️ No FCM tokens found in database</p>";
                        echo "<p class='text-gray-600 mt-2'>This means:</p>";
                        echo "<ul class='list-disc list-inside text-gray-600 mt-1'>";
                        echo "<li>Users haven't granted notification permission yet</li>";
                        echo "<li>FCM token saving is not working</li>";
                        echo "<li>There's an error in the save process</li>";
                        echo "</ul>";
                    }
                }
                echo "</div>";
                
                // Test save functionality
                echo "<div class='bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6'>";
                echo "<h3 class='font-bold text-yellow-800 mb-2'>🧪 Test Token Save</h3>";
                echo "<button onclick='testTokenSave()' class='bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700'>Test Save FCM Token</button>";
                echo "<div id='testResult' class='mt-4'></div>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div class='bg-red-50 border border-red-200 rounded-lg p-4'>";
                echo "<h3 class='font-bold text-red-800 mb-2'>❌ Database Error</h3>";
                echo "<p class='text-red-600'>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
            ?>
            
            <!-- Manual token save test -->
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <h3 class="font-bold text-purple-800 mb-2">🔧 Manual Debug</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <input type="number" id="userId" placeholder="User ID" class="border rounded px-3 py-2" value="1">
                    <select id="userType" class="border rounded px-3 py-2">
                        <option value="employee">Employee</option>
                        <option value="it_staff">IT Staff</option>
                    </select>
                    <button onclick="manualTokenSave()" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                        Manual Save Test
                    </button>
                </div>
                <div id="manualResult" class="mt-4"></div>
            </div>
        </div>
    </div>

    <script>
        // Test token save with current session
        async function testTokenSave() {
            const resultDiv = document.getElementById('testResult');
            resultDiv.innerHTML = '<p class="text-blue-600">🔄 Testing token save...</p>';
            
            try {
                const testToken = 'test_token_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                
                const response = await fetch('api/save_fcm_token.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        token: testToken
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="bg-green-100 border border-green-400 rounded p-3">
                            <p class="text-green-800">✅ Token save successful!</p>
                            <p class="text-sm">User: ${result.user_id} (${result.user_type})</p>
                            <p class="text-xs font-mono">Token: ${testToken.substr(0, 30)}...</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="bg-red-100 border border-red-400 rounded p-3">
                            <p class="text-red-800">❌ Token save failed!</p>
                            <p class="text-sm">Error: ${result.error}</p>
                            ${result.debug ? '<p class="text-xs">Debug: ' + result.debug + '</p>' : ''}
                        </div>
                    `;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="bg-red-100 border border-red-400 rounded p-3">
                        <p class="text-red-800">❌ Request failed!</p>
                        <p class="text-sm">Error: ${error.message}</p>
                    </div>
                `;
            }
            
            // Refresh page after 2 seconds to show updated data
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
        
        // Manual token save test
        async function manualTokenSave() {
            const userId = document.getElementById('userId').value;
            const userType = document.getElementById('userType').value;
            const resultDiv = document.getElementById('manualResult');
            
            resultDiv.innerHTML = '<p class="text-blue-600">🔄 Testing manual save...</p>';
            
            try {
                const testToken = 'manual_test_token_' + Date.now();
                
                // We'll need to create a manual endpoint or modify the existing one
                const response = await fetch('api/save_fcm_token.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        token: testToken,
                        manual_user_id: userId,
                        manual_user_type: userType
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    resultDiv.innerHTML = `
                        <div class="bg-green-100 border border-green-400 rounded p-3">
                            <p class="text-green-800">✅ Manual save successful!</p>
                            <p class="text-sm">Saved for User ${userId} (${userType})</p>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="bg-red-100 border border-red-400 rounded p-3">
                            <p class="text-red-800">❌ Manual save failed!</p>
                            <p class="text-sm">Error: ${result.error}</p>
                        </div>
                    `;
                }
                
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="bg-red-100 border border-red-400 rounded p-3">
                        <p class="text-red-800">❌ Manual test failed!</p>
                        <p class="text-sm">Error: ${error.message}</p>
                    </div>
                `;
            }
        }
    </script>
</body>
</html>