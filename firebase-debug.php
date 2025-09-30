<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firebase Debug Test</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold mb-6">🔥 Firebase Debug Test</h1>
        
        <div id="status" class="mb-6 p-4 bg-gray-50 rounded-lg">
            <div class="text-sm font-mono">
                <div id="firebase-status">🔄 Initializing Firebase...</div>
                <div id="connection-status"></div>
                <div id="test-results"></div>
            </div>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Test Message:</label>
            <input type="text" id="testMessage" class="w-full p-3 border rounded-lg" 
                   placeholder="Enter test message" value="Hello Firebase Test!">
        </div>
        
        <div class="flex gap-4">
            <button onclick="testFirebaseConnection()" 
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                Test Connection
            </button>
            <button onclick="testSendMessage()" 
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                Test Send Message
            </button>
            <button onclick="testReadMessages()" 
                    class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                Test Read Messages
            </button>
            <button onclick="clearMessages()" 
                    class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                Clear Test Data
            </button>
        </div>
        
        <div id="messages" class="mt-6 p-4 bg-gray-50 rounded-lg min-h-32">
            <h3 class="font-bold mb-2">Messages:</h3>
            <div id="messagesList" class="space-y-2 font-mono text-sm"></div>
        </div>
    </div>

    <script type="module">
        import { initializeApp } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-app.js";
        import { getDatabase, ref, push, onValue, serverTimestamp, remove } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-database.js";

        // Firebase config (your actual config)
        const firebaseConfig = {
            apiKey: "AIzaSyC7NBIsU2F8vve9eKPTz6d2i7ns0Cwen90",
            authDomain: "rssticket-a8d0a.firebaseapp.com",
            databaseURL: "https://rssticket-a8d0a-default-rtdb.asia-southeast1.firebasedatabase.app",
            projectId: "rssticket-a8d0a",
            storageBucket: "rssticket-a8d0a.firebasestorage.app",
            messagingSenderId: "410726919561",
            appId: "1:410726919561:web:409adc98f34498ee984558",
            measurementId: "G-2T29LBJG4P"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const database = getDatabase(app);
        const testRef = ref(database, 'debug-test');
        
        let messageListener = null;

        // Update status
        function updateStatus(id, message, type = 'info') {
            const element = document.getElementById(id);
            const colors = {
                info: 'text-blue-600',
                success: 'text-green-600', 
                error: 'text-red-600',
                warning: 'text-yellow-600'
            };
            element.className = colors[type] || colors.info;
            element.textContent = message;
        }

        // Test Firebase connection
        window.testFirebaseConnection = async function() {
            try {
                updateStatus('connection-status', '🔄 Testing connection...', 'info');
                
                // Try to write a simple test value
                const testData = {
                    test: true,
                    timestamp: serverTimestamp(),
                    message: 'Connection test'
                };
                
                const result = await push(testRef, testData);
                updateStatus('connection-status', `✅ Connection successful! Key: ${result.key}`, 'success');
                
                // Clean up test data after 2 seconds
                setTimeout(() => {
                    remove(ref(database, `debug-test/${result.key}`));
                }, 2000);
                
            } catch (error) {
                updateStatus('connection-status', `❌ Connection failed: ${error.message}`, 'error');
                console.error('Firebase connection error:', error);
            }
        };

        // Test send message
        window.testSendMessage = async function() {
            try {
                const messageText = document.getElementById('testMessage').value;
                if (!messageText) {
                    alert('Please enter a test message');
                    return;
                }
                
                updateStatus('test-results', '🔄 Sending message...', 'info');
                
                const messageData = {
                    message: messageText,
                    user_type: 'test_user',
                    display_name: 'Debug Test',
                    timestamp: serverTimestamp(),
                    created_at: new Date().toISOString()
                };
                
                const result = await push(ref(database, 'debug-test/messages'), messageData);
                updateStatus('test-results', `✅ Message sent! Key: ${result.key}`, 'success');
                
            } catch (error) {
                updateStatus('test-results', `❌ Send failed: ${error.message}`, 'error');
                console.error('Send message error:', error);
            }
        };

        // Test read messages
        window.testReadMessages = function() {
            try {
                updateStatus('test-results', '🔄 Setting up message listener...', 'info');
                
                if (messageListener) {
                    messageListener(); // Remove old listener
                }
                
                const messagesRef = ref(database, 'debug-test/messages');
                messageListener = onValue(messagesRef, (snapshot) => {
                    const messages = snapshot.val();
                    const messagesList = document.getElementById('messagesList');
                    
                    if (messages) {
                        const messageItems = Object.entries(messages).map(([key, msg]) => {
                            return `<div class="p-2 bg-white rounded border">
                                <strong>${msg.display_name}:</strong> ${msg.message}
                                <div class="text-xs text-gray-500">Key: ${key}</div>
                            </div>`;
                        }).join('');
                        
                        messagesList.innerHTML = messageItems;
                        updateStatus('test-results', `✅ Found ${Object.keys(messages).length} messages`, 'success');
                    } else {
                        messagesList.innerHTML = '<div class="text-gray-500">No messages found</div>';
                        updateStatus('test-results', '📭 No messages found', 'warning');
                    }
                }, (error) => {
                    updateStatus('test-results', `❌ Read failed: ${error.message}`, 'error');
                    console.error('Read messages error:', error);
                });
                
            } catch (error) {
                updateStatus('test-results', `❌ Listener setup failed: ${error.message}`, 'error');
                console.error('Message listener error:', error);
            }
        };

        // Clear test data
        window.clearMessages = async function() {
            try {
                updateStatus('test-results', '🔄 Clearing test data...', 'info');
                await remove(testRef);
                document.getElementById('messagesList').innerHTML = '<div class="text-gray-500">Test data cleared</div>';
                updateStatus('test-results', '✅ Test data cleared', 'success');
            } catch (error) {
                updateStatus('test-results', `❌ Clear failed: ${error.message}`, 'error');
                console.error('Clear error:', error);
            }
        };

        // Initialize
        updateStatus('firebase-status', '✅ Firebase initialized successfully', 'success');
        
        // Auto-start message listener
        setTimeout(() => {
            testReadMessages();
        }, 1000);

    </script>
</body>
</html>