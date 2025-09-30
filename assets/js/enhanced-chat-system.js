/**
 * Enhanced Chat System with Firebase Integration
 * Combines existing chat functionality with Firebase real-time messaging
 */

// Import Firebase Chat
import { FirebaseChat } from './firebase-chat.js';

class EnhancedChatSystem {
    constructor(ticketId, initialResponseCount, currentUserType, currentUserName) {
        this.ticketId = ticketId;
        this.lastResponseCount = initialResponseCount;
        this.currentUserType = currentUserType;
        this.currentUserName = currentUserName;
        this.isTyping = false;
        this.typingTimer = null;
        this.firebaseChat = null;
        
        console.log('💬 Enhanced Chat System initializing...', {
            ticketId,
            initialResponseCount,
            currentUserType,
            currentUserName
        });
        
        this.init();
    }

    async init() {
        console.log('💬 Initializing chat system...');
        
        try {
            // Initialize Firebase chat for real-time messaging
            this.firebaseChat = new FirebaseChat(
                this.ticketId,
                this.currentUserType,
                this.currentUserName
            );
            
            // Initialize form elements and event listeners
            this.initializeElements();
            this.attachEventListeners();
            this.initScrollableChat();
            
            console.log('✅ Enhanced Chat System ready!');
            
        } catch (error) {
            console.error('❌ Chat system initialization failed:', error);
            // Fallback to basic functionality
            this.initializeElements();
            this.attachEventListeners();
        }
    }

    initializeElements() {
        this.form = document.getElementById('messengerForm');
        this.textarea = document.getElementById('response_text');
        this.sendBtn = document.getElementById('messengerSendBtn');
        this.chatContainer = document.getElementById('chatContainer');
        this.statusDiv = document.getElementById('responseStatus');

        if (!this.form) {
            console.error('❌ Messenger form not found');
            return false;
        }

        console.log('✅ Chat elements found and initialized');
        return true;
    }

    attachEventListeners() {
        if (!this.initializeElements()) return;

        // Form submission for sending messages
        this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));

        // Textarea events
        if (this.textarea) {
            this.textarea.addEventListener('input', () => this.handleTyping());
            this.textarea.addEventListener('blur', () => this.clearTypingStatus());
            this.textarea.addEventListener('keydown', (e) => this.handleEnterKey(e));
        }

        // Clear button
        const clearBtn = document.getElementById('clearBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                if (this.textarea) this.textarea.value = '';
                this.clearTypingStatus();
            });
        }

        console.log('✅ Event listeners attached');
    }

    async handleFormSubmit(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('📤 Form submit triggered - sending via Firebase');
        
        if (!this.textarea) {
            console.error('❌ Textarea not found');
            return;
        }

        const messageText = this.textarea.value.trim();
        if (!messageText) {
            console.log('⚠️ Empty message, not sending');
            return;
        }

        await this.sendMessage(messageText);
    }

    async sendMessage(messageText) {
        console.log('📤 Enhanced Chat: Sending message:', messageText);
        
        // Disable send button
        this.setSendButtonState(true, 'Sending...');
        
        try {
            if (this.firebaseChat && this.firebaseChat.isInitialized) {
                // Send via Firebase for instant messaging
                console.log('🔥 Using Firebase for instant send...');
                
                // Show own message immediately (optimistic UI)
                this.firebaseChat.displayOwnMessage(messageText);
                
                // Send to Firebase (will sync to other users instantly)
                const result = await this.firebaseChat.sendMessage(messageText);
                
                if (result.success) {
                    console.log('✅ Message sent successfully via Firebase');
                    
                    // Clear textarea
                    this.textarea.value = '';
                    this.textarea.focus();
                    
                    // Update counter
                    this.lastResponseCount++;
                    this.updateResponseCounter();
                    
                    // Clear typing status
                    this.clearTypingStatus();
                    
                } else {
                    throw new Error('Firebase send failed - no success flag');
                }
                
            } else {
                // Fallback to AJAX if Firebase not available
                console.log('⚠️ Firebase not available, using AJAX fallback');
                console.log('🔧 Firebase state:', {
                    firebaseChat: !!this.firebaseChat,
                    isInitialized: this.firebaseChat ? this.firebaseChat.isInitialized : 'N/A'
                });
                await this.sendViaAJAX(messageText);
            }
            
        } catch (error) {
            console.error('❌ Enhanced Chat send error:', error);
            
            // Remove optimistic message on error
            const ownMessages = document.querySelectorAll('[data-own-message="true"]');
            const lastOwnMessage = ownMessages[ownMessages.length - 1];
            if (lastOwnMessage) {
                lastOwnMessage.remove();
            }
            
            // Try fallback to AJAX
            if (this.firebaseChat) {
                console.log('🔄 Trying AJAX fallback after Firebase failure...');
                try {
                    await this.sendViaAJAX(messageText);
                    console.log('✅ AJAX fallback successful');
                } catch (ajaxError) {
                    console.error('❌ AJAX fallback also failed:', ajaxError);
                    this.showErrorMessage(`Failed to send message: ${error.message}`);
                }
            } else {
                this.showErrorMessage(`Failed to send message: ${error.message}`);
            }
            
        } finally {
            // Re-enable send button
            this.setSendButtonState(false, 'Send');
        }
    }

    async sendViaAJAX(messageText) {
        const formData = new FormData();
        formData.append('ticket_id', this.ticketId);
        formData.append('response_text', messageText);

        // Add internal checkbox if it exists (IT staff only)
        const internalCheckbox = document.querySelector('input[name="is_internal"]');
        if (internalCheckbox && internalCheckbox.checked) {
            formData.append('is_internal', '1');
        }

        const response = await fetch('api/add_response_ajax.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }

        const data = await response.json();
        
        if (data.success) {
            console.log('✅ AJAX send successful');
            
            // Add message to display
            if (data.response) {
                this.addResponseToDisplay(data.response);
            }
            
            this.textarea.value = '';
            this.lastResponseCount++;
            this.updateResponseCounter();
            this.clearTypingStatus();
            
        } else {
            throw new Error(data.error || 'AJAX send failed');
        }
    }

    setSendButtonState(disabled, text) {
        if (this.sendBtn) {
            this.sendBtn.disabled = disabled;
            const icon = disabled ? 
                '<i class="fas fa-spinner fa-spin mr-2"></i>' : 
                '<i class="fas fa-paper-plane mr-2"></i>';
            this.sendBtn.innerHTML = `${icon}${text}`;
        }
    }

    handleEnterKey(event) {
        if (event.ctrlKey && event.key === 'Enter') {
            event.preventDefault();
            this.form.dispatchEvent(new Event('submit'));
        }
    }

    handleTyping() {
        if (!this.isTyping) {
            this.isTyping = true;
            this.sendTypingStatus(true);
        }
        
        clearTimeout(this.typingTimer);
        this.typingTimer = setTimeout(() => {
            this.isTyping = false;
            this.sendTypingStatus(false);
        }, 2000);
    }

    sendTypingStatus(isTyping) {
        fetch('api/typing_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                ticket_id: this.ticketId,
                is_typing: isTyping
            })
        }).catch(error => {
            console.error('Typing status error:', error);
        });
    }

    clearTypingStatus() {
        this.sendTypingStatus(false);
    }

    updateResponseCounter() {
        // Update response counter in UI
        const counters = document.querySelectorAll('.bg-blue-100.text-blue-800.px-3.py-1.rounded-full');
        counters.forEach(counter => {
            if (counter.textContent.match(/^\d+$/)) {
                counter.textContent = this.lastResponseCount;
            }
        });
        
        const activityCounters = document.querySelectorAll('.font-bold.text-gray-900');
        activityCounters.forEach(counter => {
            if (counter.textContent.match(/^\d+$/)) {
                counter.textContent = this.lastResponseCount;
            }
        });
    }

    addResponseToDisplay(response) {
        // Fallback method for AJAX responses
        const emptyState = document.querySelector('.text-center.py-16');
        if (emptyState) {
            emptyState.remove();
        }

        const isStaff = response.user_type === 'it_staff';
        const currentUserIsStaff = this.currentUserType === 'it_staff';
        
        const isMyMessage = (currentUserIsStaff && isStaff) || (!currentUserIsStaff && !isStaff);
        const alignRight = isMyMessage;

        let timeDisplay = new Date().toLocaleTimeString('en-US', {
            hour: 'numeric', 
            minute: '2-digit', 
            hour12: true
        });

        if (response.formatted_date) {
            const timeMatch = response.formatted_date.match(/at\s(\d{1,2}:\d{2}\s[AP]M)/);
            if (timeMatch) timeDisplay = timeMatch[1];
        }

        const bubbleClass = alignRight 
            ? 'bg-blue-500 text-white rounded-l-2xl rounded-tr-2xl bubble-sent' 
            : 'bg-green-100 border border-green-200 rounded-r-2xl rounded-tl-2xl text-gray-800 bubble-staff';

        const responseHtml = `
            <div class="flex ${alignRight ? 'justify-end' : 'justify-start'} mb-4">
                <div class="max-w-xs">
                    <div class="chat-bubble relative ${bubbleClass} px-4 py-3 shadow-sm">
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">${this.escapeHtml(response.message)}</p>
                        <div class="flex justify-start mt-2">
                            <span class="text-xs opacity-75">${timeDisplay}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (this.chatContainer) {
            this.chatContainer.insertAdjacentHTML('beforeend', responseHtml);
            setTimeout(() => {
                this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
            }, 100);
        }
    }

    initScrollableChat() {
        if (this.chatContainer) {
            setTimeout(() => {
                this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
            }, 100);
            
            const observer = new MutationObserver(() => {
                this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
            });
            
            observer.observe(this.chatContainer, { 
                childList: true, 
                subtree: true 
            });
        }
    }

    showErrorMessage(message) {
        // Create error notification
        const errorDiv = document.createElement('div');
        errorDiv.className = 'fixed top-4 right-4 bg-red-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
        errorDiv.innerHTML = `
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-3 text-red-200 hover:text-white">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(errorDiv);
        
        setTimeout(() => {
            if (errorDiv.parentNode) {
                errorDiv.remove();
            }
        }, 5000);
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Clean up resources
    destroy() {
        if (this.firebaseChat) {
            this.firebaseChat.destroy();
        }
        
        clearTimeout(this.typingTimer);
        console.log('💬 Chat system cleaned up');
    }
}

// Global function for backwards compatibility
function submitMessage(event) {
    event.preventDefault();
    console.log('📤 submitMessage called - delegating to enhanced chat system');
    
    if (window.enhancedChatSystem) {
        const textarea = document.getElementById('response_text');
        if (textarea && textarea.value.trim()) {
            window.enhancedChatSystem.sendMessage(textarea.value.trim());
        }
    } else {
        console.error('❌ Enhanced chat system not initialized');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing Enhanced Chat System...');
    
    // Get global variables from PHP
    if (typeof window.TICKET_ID !== 'undefined' && 
        typeof window.INITIAL_RESPONSE_COUNT !== 'undefined' &&
        typeof window.CURRENT_USER_TYPE !== 'undefined' &&
        typeof window.CURRENT_USER_NAME !== 'undefined') {
        
        window.enhancedChatSystem = new EnhancedChatSystem(
            window.TICKET_ID,
            window.INITIAL_RESPONSE_COUNT,
            window.CURRENT_USER_TYPE,
            window.CURRENT_USER_NAME
        );
        
        console.log('✅ Enhanced Chat System initialized successfully');
        
    } else {
        console.error('❌ Required global variables not found:', {
            TICKET_ID: window.TICKET_ID,
            INITIAL_RESPONSE_COUNT: window.INITIAL_RESPONSE_COUNT,
            CURRENT_USER_TYPE: window.CURRENT_USER_TYPE,
            CURRENT_USER_NAME: window.CURRENT_USER_NAME
        });
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    if (window.enhancedChatSystem) {
        window.enhancedChatSystem.destroy();
    }
});

// Export for module use
export { EnhancedChatSystem };