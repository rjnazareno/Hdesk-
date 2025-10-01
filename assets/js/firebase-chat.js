/**
 * Firebase Real-Time Chat System
 * Instant messaging with 0ms delay
 */

import { database } from './firebase-config.js';
import { ref, push, onValue, serverTimestamp, off, query, orderByChild, limitToLast } from "https://www.gstatic.com/firebasejs/10.5.0/firebase-database.js";

class FirebaseChat {
    constructor(ticketId, currentUserType, currentUserName) {
        this.ticketId = ticketId;
        this.currentUserType = currentUserType;
        this.currentUserName = currentUserName;
        this.messagesRef = ref(database, `tickets/${ticketId}/messages`);
        this.listener = null;
        this.processedMessages = new Set();
        this.isInitialized = false;
        this.isInitialLoad = true; // Prevent notifications during initial load
        
        console.log('🔥 Firebase Chat initializing...', {
            ticketId,
            currentUserType,
            currentUserName
        });
        
        this.init();
    }
    
    async init() {
        try {
            await this.setupRealTimeListener();
            this.isInitialized = true;
            console.log('✅ Firebase Chat ready for instant messaging!');
            
            // Allow notifications after initial load (5 seconds - enough time for marking messages as seen)
            setTimeout(() => {
                this.isInitialLoad = false;
                console.log('🔔 Firebase notifications now enabled (initial load complete)');
            }, 5000);
            
            // Show connection status
            this.showConnectionStatus(true);
            
        } catch (error) {
            console.error('❌ Firebase Chat initialization failed:', error);
            this.showConnectionStatus(false);
        }
    }
    
    /**
     * Send message instantly via Firebase
     */
    async sendMessage(messageText) {
        if (!this.isInitialized) {
            console.error('❌ Firebase Chat not initialized');
            throw new Error('Firebase Chat not initialized');
        }
        
        try {
            console.log('🔥 Sending message via Firebase...', messageText);
            console.log('🔧 Firebase config:', {
                ticketId: this.ticketId,
                userType: this.currentUserType,
                userName: this.currentUserName,
                messagesRef: this.messagesRef.toString()
            });
            
            const timestamp = new Date();
            const messageData = {
                message: messageText.trim(),
                user_type: this.currentUserType,
                display_name: this.currentUserName,
                ticket_id: this.ticketId,
                timestamp: serverTimestamp(),
                created_at: timestamp.toISOString(),
                formatted_date: timestamp.toLocaleString('en-US', {
                    month: 'short',
                    day: 'numeric',
                    year: 'numeric',
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                }),
                sender_id: `${this.currentUserType}_${Date.now()}`
            };
            
            console.log('📤 Message data prepared:', messageData);
            
            // Send to Firebase Realtime Database (instant sync)
            console.log('🔥 Pushing to Firebase...');
            const firebaseResult = await push(this.messagesRef, messageData);
            console.log('✅ Message sent to Firebase:', firebaseResult.key);
            
            // Show success notification
            this.showSendStatus(true, 'Message sent via Firebase!');
            
            // Also save to MySQL for permanent storage (background)
            this.saveToMySQL(messageText).catch(error => {
                console.warn('⚠️ MySQL backup failed (Firebase message still sent):', error);
            });
            
            return { 
                success: true, 
                id: firebaseResult.key, 
                data: messageData 
            };
            
        } catch (error) {
            console.error('❌ Firebase send error:', error);
            console.error('❌ Error details:', {
                name: error.name,
                message: error.message,
                code: error.code,
                stack: error.stack
            });
            
            this.showSendStatus(false, `Send failed: ${error.message}`);
            throw new Error(`Failed to send message: ${error.message}`);
        }
    }
    
    /**
     * Set up real-time listener for incoming messages
     */
    async setupRealTimeListener() {
        console.log('🔥 Setting up Firebase real-time listener...');
        
        // Query for recent messages (last 50)
        const messagesQuery = query(
            this.messagesRef,
            orderByChild('timestamp'),
            limitToLast(50)
        );
        
        this.listener = onValue(messagesQuery, (snapshot) => {
            const messages = snapshot.val();
            
            if (messages) {
                console.log('🔥 Firebase messages received:', Object.keys(messages).length);
                
                // Process new messages
                Object.entries(messages).forEach(([key, message]) => {
                    if (!this.processedMessages.has(key)) {
                        this.processedMessages.add(key);
                        
                        // Only display messages from other users to avoid duplicates
                        // ✅ PREVENT SELF-NOTIFICATIONS: Check user ID first, then fallback to name/type
                        const isSameUser = (
                            (message.user_id && this.userId && message.user_id == this.userId) ||
                            (message.user_type === this.currentUserType && message.display_name === this.currentUserName)
                        );
                        
                        if (!isSameUser) {
                            console.log('📨 New message from other user:', message.display_name, 'UserID:', message.user_id);
                            this.displayIncomingMessage(message);
                            
                            // Only show notification for truly new messages (not during initial load or already seen)
                            if (!this.isInitialLoad) {
                                this.checkAndShowNotification(message);
                            } else {
                                console.log('🔕 Skipping notification during initial load');
                            }
                        } else {
                            console.log('🚫 Skipping self-notification for user:', message.display_name, 'UserID:', message.user_id);
                        }
                    }
                });
            }
        }, (error) => {
            console.error('❌ Firebase listener error:', error);
            this.showConnectionStatus(false);
        });
        
        console.log('✅ Real-time listener active');
    }
    
    /**
     * Display incoming message in chat
     */
    displayIncomingMessage(messageData) {
        const chatContainer = document.getElementById('chatContainer');
        if (!chatContainer) {
            console.warn('⚠️ Chat container not found');
            return;
        }
        
        // Remove empty state if present
        const emptyState = chatContainer.querySelector('.text-center.py-16');
        if (emptyState) {
            emptyState.remove();
        }
        
        // Determine message alignment and colors
        const isStaff = messageData.user_type === 'it_staff';
        const currentUserIsStaff = this.currentUserType === 'it_staff';
        
        // Message from same user type goes right (blue), different type goes left (green)
        const isMyMessage = (currentUserIsStaff && isStaff) || (!currentUserIsStaff && !isStaff);
        const alignClass = isMyMessage ? 'justify-end' : 'justify-start';
        const bubbleClass = isMyMessage 
            ? 'bg-blue-500 text-white rounded-l-2xl rounded-tr-2xl bubble-sent' 
            : 'bg-green-100 border border-green-200 rounded-r-2xl rounded-tl-2xl text-gray-800 bubble-staff';
        
        const timeDisplay = this.extractTimeFromDate(messageData.formatted_date) || 
                           new Date().toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
        
        const messageHTML = `
            <div class="flex ${alignClass} mb-4" data-firebase-message="${messageData.sender_id}">
                <div class="max-w-xs">
                    <div class="chat-bubble relative ${bubbleClass} px-4 py-3 shadow-sm">
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">${this.escapeHtml(messageData.message)}</p>
                        <div class="flex justify-start mt-2">
                            <span class="text-xs opacity-75">${timeDisplay}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add message with animation
        chatContainer.insertAdjacentHTML('beforeend', messageHTML);
        
        // Smooth scroll to bottom
        setTimeout(() => {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }, 50);
        
        // Add entrance animation
        const newMessage = chatContainer.lastElementChild;
        if (newMessage) {
            newMessage.style.opacity = '0';
            newMessage.style.transform = 'translateY(10px)';
            setTimeout(() => {
                newMessage.style.transition = 'all 0.3s ease-out';
                newMessage.style.opacity = '1';
                newMessage.style.transform = 'translateY(0)';
            }, 10);
        }
        
        console.log('✅ Message displayed:', messageData.message.substring(0, 30) + '...');
    }
    
    /**
     * Display your own message immediately (optimistic UI)
     */
    displayOwnMessage(messageText) {
        const chatContainer = document.getElementById('chatContainer');
        if (!chatContainer) return;
        
        const now = new Date();
        const timeString = now.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
        
        const messageHTML = `
            <div class="flex justify-end mb-4" data-own-message="true">
                <div class="max-w-xs">
                    <div class="chat-bubble relative bg-blue-500 text-white rounded-l-2xl rounded-tr-2xl bubble-sent px-4 py-3 shadow-sm">
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">${this.escapeHtml(messageText)}</p>
                        <div class="flex justify-start mt-2">
                            <span class="text-xs opacity-75">${timeString}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        chatContainer.insertAdjacentHTML('beforeend', messageHTML);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    
    /**
     * Check if message was seen and show notification only for new messages
     */
    async checkAndShowNotification(message) {
        try {
            // Check if message was already seen
            const response = await fetch('/IThelp/api/check_message_seen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    ticket_id: this.ticketId,
                    message_id: message.id || Date.now() // Use message ID or timestamp
                })
            });
            
            const result = await response.json();
            
            // Only show notification if message was NOT already seen
            if (result.success && !result.seen) {
                console.log('📢 Showing notification for new unseen message');
                this.showNewMessageNotification(message);
            } else {
                console.log('🔕 Message already seen, skipping notification');
            }
        } catch (error) {
            console.error('Error checking message seen status:', error);
            // Fallback: show notification if can't check status
            this.showNewMessageNotification(message);
        }
    }

    /**
     * Show notification for new messages
     */
    showNewMessageNotification(messageData) {
        // Create notification banner
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50 transform transition-all duration-300 ease-in-out';
        notification.style.transform = 'translateX(100%)';
        
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-comment mr-2"></i>
                <span>New message from ${messageData.display_name}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-green-200 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Slide in animation
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 4000);
        
        // Browser notification (if permission granted)
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(`New message from ${messageData.display_name}`, {
                body: messageData.message.substring(0, 100),
                icon: '/favicon.ico',
                tag: `ticket-${this.ticketId}-message`
            });
        }
    }
    
    /**
     * Show connection status
     */
    showConnectionStatus(connected) {
        const statusDiv = document.getElementById('firebaseStatus') || this.createStatusDiv();
        
        if (connected) {
            statusDiv.innerHTML = '<i class="fas fa-wifi text-green-500 mr-1"></i><span class="text-green-600 text-xs">Real-time connected</span>';
            statusDiv.className = 'fixed bottom-4 right-4 bg-white border border-green-200 px-2 py-1 rounded shadow-sm z-40';
        } else {
            statusDiv.innerHTML = '<i class="fas fa-wifi text-red-500 mr-1"></i><span class="text-red-600 text-xs">Connection lost</span>';
            statusDiv.className = 'fixed bottom-4 right-4 bg-white border border-red-200 px-2 py-1 rounded shadow-sm z-40';
        }
    }
    
    /**
     * Show send status feedback
     */
    showSendStatus(success, message) {
        const statusDiv = document.createElement('div');
        statusDiv.className = success 
            ? 'fixed top-4 left-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50'
            : 'fixed top-4 left-4 bg-red-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
        
        statusDiv.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${success ? 'fa-check' : 'fa-exclamation-triangle'} mr-2"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-3 opacity-75 hover:opacity-100">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(statusDiv);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            if (statusDiv.parentNode) {
                statusDiv.remove();
            }
        }, 3000);
        
        console.log(success ? '✅ Send status: SUCCESS' : '❌ Send status: FAILED', message);
    }
    
    createStatusDiv() {
        const statusDiv = document.createElement('div');
        statusDiv.id = 'firebaseStatus';
        document.body.appendChild(statusDiv);
        return statusDiv;
    }
    
    /**
     * Save message to MySQL database for permanent storage
     */
    async saveToMySQL(messageText) {
        try {
            const formData = new FormData();
            formData.append('ticket_id', this.ticketId);
            formData.append('response_text', messageText);
            
            const response = await fetch('api/add_response_ajax.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                console.log('💾 Message backed up to MySQL:', result.response?.id);
            } else {
                throw new Error(result.error || 'MySQL save failed');
            }
        } catch (error) {
            console.error('💾 MySQL backup error:', error);
            throw error;
        }
    }
    
    /**
     * Utility functions
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    extractTimeFromDate(dateString) {
        if (!dateString) return null;
        const timeMatch = dateString.match(/at\s(\d{1,2}:\d{2}\s[AP]M)/);
        return timeMatch ? timeMatch[1] : null;
    }
    
    /**
     * Clean up Firebase listener
     */
    destroy() {
        if (this.listener) {
            off(this.messagesRef);
            this.listener = null;
            console.log('🔥 Firebase listener cleaned up');
        }
        
        // Remove status indicator
        const statusDiv = document.getElementById('firebaseStatus');
        if (statusDiv) {
            statusDiv.remove();
        }
    }
}

// Export for global use
export { FirebaseChat };