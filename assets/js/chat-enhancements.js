/**
 * Chat Enhancements
 * Typing indicators and seen indicators for the ticket chat system
 */

class ChatEnhancements {
    constructor(ticketId, userId, userType) {
        this.ticketId = ticketId;
        this.userId = userId;
        this.userType = userType;
        this.isTyping = false;
        this.typingTimeout = null;
        this.lastSeenTime = new Date();
        
        this.init();
    }
    
    init() {
        this.setupTypingIndicator();
        this.setupSeenIndicators();
        this.startHeartbeat();
    }
    
    setupTypingIndicator() {
        const messageInput = document.getElementById('response_text');
        const typingIndicator = document.getElementById('typingIndicator');
        
        if (!messageInput) return;
        
        let typingTimer;
        
        messageInput.addEventListener('input', () => {
            if (!this.isTyping) {
                this.isTyping = true;
                this.sendTypingStatus(true);
            }
            
            // Clear previous timer
            clearTimeout(typingTimer);
            
            // Stop typing after 3 seconds of inactivity
            typingTimer = setTimeout(() => {
                this.isTyping = false;
                this.sendTypingStatus(false);
            }, 3000);
        });
        
        messageInput.addEventListener('blur', () => {
            // Stop typing when input loses focus
            if (this.isTyping) {
                this.isTyping = false;
                this.sendTypingStatus(false);
            }
        });
        
        // Check for typing status from other users every 2 seconds
        setInterval(() => {
            this.checkTypingStatus();
        }, 2000);
    }
    
    async sendTypingStatus(isTyping) {
        try {
            await fetch('api/typing_status.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `ticket_id=${this.ticketId}&is_typing=${isTyping ? 1 : 0}&user_type=${this.userType}`
            });
        } catch (error) {
            console.error('Error sending typing status:', error);
        }
    }
    
    async checkTypingStatus() {
        try {
            const response = await fetch(`api/typing_status.php?ticket_id=${this.ticketId}&user_type=${this.userType}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateTypingIndicator(data.is_typing, data.typing_user);
            }
        } catch (error) {
            console.error('Error checking typing status:', error);
        }
    }
    
    updateTypingIndicator(isTyping, typingUser) {
        const indicator = document.getElementById('typingIndicator');
        const message = indicator.querySelector('span');
        
        if (isTyping && typingUser) {
            indicator.classList.remove('hidden');
            message.textContent = `${typingUser} is typing...`;
            
            // Auto-scroll to show typing indicator
            const chatContainer = document.getElementById('chatContainer');
            if (chatContainer) {
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
        } else {
            indicator.classList.add('hidden');
        }
    }
    
    setupSeenIndicators() {
        // Mark messages as seen when they come into view
        this.observeMessageVisibility();
        
        // Update seen status for all visible messages every 10 seconds
        setInterval(() => {
            this.updateSeenStatus();
        }, 10000);
    }
    
    observeMessageVisibility() {
        const chatContainer = document.getElementById('chatContainer');
        if (!chatContainer) return;
        
        // Use Intersection Observer to detect when messages are visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const messageElement = entry.target;
                    const responseId = messageElement.dataset.responseId;
                    if (responseId) {
                        this.markMessageAsSeen(responseId);
                    }
                }
            });
        }, {
            root: chatContainer,
            threshold: 0.5
        });
        
        // Observe all message elements
        const messages = chatContainer.querySelectorAll('.chat-bubble');
        messages.forEach(message => {
            // Add response ID if available
            const responseId = message.closest('[data-response-id]')?.dataset.responseId;
            if (responseId) {
                message.dataset.responseId = responseId;
                observer.observe(message);
            }
        });
    }
    
    async markMessageAsSeen(responseId) {
        try {
            await fetch('api/mark_seen.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `response_id=${responseId}&ticket_id=${this.ticketId}`
            });
        } catch (error) {
            console.error('Error marking message as seen:', error);
        }
    }
    
    async updateSeenStatus() {
        try {
            const response = await fetch(`api/get_seen_status.php?ticket_id=${this.ticketId}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateSeenIndicators(data.seen_messages);
            }
        } catch (error) {
            console.error('Error updating seen status:', error);
        }
    }
    
    updateSeenIndicators(seenMessages) {
        seenMessages.forEach(seenMessage => {
            const messageElement = document.querySelector(`[data-response-id="${seenMessage.response_id}"]`);
            if (messageElement) {
                const seenIndicator = messageElement.querySelector('.seen-indicator');
                if (seenIndicator) {
                    seenIndicator.innerHTML = '<i class="fas fa-check-double text-xs text-blue-400" title="Seen"></i>';
                }
            }
        });
    }
    
    startHeartbeat() {
        // Send heartbeat every 30 seconds to maintain connection
        setInterval(() => {
            this.sendHeartbeat();
        }, 30000);
    }
    
    async sendHeartbeat() {
        try {
            await fetch('api/heartbeat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `ticket_id=${this.ticketId}&user_type=${this.userType}`
            });
        } catch (error) {
            // Heartbeat errors are non-critical
        }
    }
    
    // Add CSS for typing dots animation if not already present
    addTypingAnimation() {
        if (document.querySelector('.typing-dots-style')) return;
        
        const style = document.createElement('style');
        style.className = 'typing-dots-style';
        style.textContent = `
            .typing-dots {
                display: inline-flex;
                align-items: center;
                gap: 2px;
            }
            
            .typing-dots span {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background-color: #3b82f6;
                animation: typing-dot 1.4s infinite ease-in-out;
            }
            
            .typing-dots span:nth-child(1) { animation-delay: 0s; }
            .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
            .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
            
            @keyframes typing-dot {
                0%, 80%, 100% { 
                    transform: scale(0.8);
                    opacity: 0.5;
                }
                40% { 
                    transform: scale(1);
                    opacity: 1;
                }
            }
            
            .message-seen {
                transition: all 0.3s ease;
            }
            
            .pulse-notification {
                animation: pulse 2s infinite;
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        
        document.head.appendChild(style);
    }
}

// Initialize chat enhancements
let chatEnhancements;
document.addEventListener('DOMContentLoaded', function() {
    // Get ticket info from global variables if available
    if (typeof ticketId !== 'undefined' && typeof isITStaff !== 'undefined') {
        const userType = isITStaff ? 'it_staff' : 'employee';
        chatEnhancements = new ChatEnhancements(ticketId, 1, userType); // User ID will need to be passed from PHP
        chatEnhancements.addTypingAnimation();
    }
});

// Export for global access
window.ChatEnhancements = ChatEnhancements;