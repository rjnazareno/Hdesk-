/**
 * IT Help Desk - Real-time Chat System
 * Handles AJAX messaging, real-time updates, and typing indicators
 */

class ChatSystem {
    constructor(ticketId, initialResponseCount) {
        this.ticketId = ticketId;
        this.lastResponseCount = initialResponseCount;
        this.isTyping = false;
        this.typingTimer = null;
        this.chatIntervals = {};
        this.init();
    }

    init() {
        console.log('Chat system initializing...');
        this.initializeElements();
        this.attachEventListeners();
        this.startRealtimeUpdates();
        this.initScrollableChat();
    }

    initializeElements() {
        this.form = document.getElementById('messengerForm');
        this.textarea = document.getElementById('response_text');
        this.sendBtn = document.getElementById('messengerSendBtn');
        this.chatContainer = document.getElementById('chatContainer');
        this.statusDiv = document.getElementById('responseStatus');

        if (!this.form) {
            console.error('Messenger form not found');
            return false;
        }

        console.log('Form found:', this.form);
        return true;
    }

    attachEventListeners() {
        if (!this.initializeElements()) return;

        // Form submission via AJAX
        this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));

        // Textarea events
        this.textarea?.addEventListener('input', () => this.handleTyping());
        this.textarea?.addEventListener('blur', () => this.clearTypingStatus());
        this.textarea?.addEventListener('keydown', (e) => this.handleEnterKey(e));

        // Clear button
        document.getElementById('clearBtn')?.addEventListener('click', () => {
            this.textarea.value = '';
            this.clearTypingStatus();
        });
    }

    handleFormSubmit(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('AJAX form submit handler triggered');
        
        const messageText = this.textarea.value.trim();
        if (!messageText) {
            console.log('No message to send');
            return;
        }

        this.sendMessage(messageText);
    }

    sendMessage(messageText) {
        // Show optimistic UI
        const tempId = this.showOptimisticMessage(messageText);
        
        // Disable send button
        this.setSendButtonState(true, 'Sending...');

        const formData = new FormData();
        formData.append('ticket_id', this.ticketId);
        formData.append('response_text', messageText);

        // Add internal checkbox if it exists (IT staff only)
        const internalCheckbox = document.querySelector('input[name="is_internal"]');
        if (internalCheckbox && internalCheckbox.checked) {
            formData.append('is_internal', '1');
        }

        console.log('Sending AJAX request...');
        
        fetch('api/add_response_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.text().then(text => {
                console.log('Raw response:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON response:', text);
                    throw new Error('Invalid JSON response from server');
                }
            });
        })
        .then(data => {
            console.log('AJAX success:', data);
            if (data.success) {
                this.updateOptimisticMessage(tempId, data.response);
                this.textarea.value = '';
                this.lastResponseCount++;
                this.updateResponseCounter();
                this.clearTypingStatus();
                
                if (window.triggerFastPolling) {
                    window.triggerFastPolling();
                }
                
                console.log('Message sent successfully');
            } else {
                this.removeOptimisticMessage(tempId);
                console.error('Server error:', data.error);
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('AJAX error:', error);
            this.removeOptimisticMessage(tempId);
            alert('Error sending message: ' + error.message);
        })
        .finally(() => {
            this.setSendButtonState(false, 'Send');
        });
    }

    showOptimisticMessage(messageText) {
        const tempId = 'temp_' + Date.now();
        
        if (this.chatContainer) {
            const responseHtml = `
                <div class="flex justify-end mb-2" data-temp-id="${tempId}">
                    <div class="max-w-xs lg:max-w-md">
                        <div class="chat-bubble relative bg-blue-500 text-white rounded-l-2xl rounded-tr-2xl bubble-sent px-4 py-3 shadow-sm opacity-75">
                            <p class="text-sm leading-relaxed whitespace-pre-wrap">${messageText}</p>
                            <div class="flex items-center justify-between mt-2 text-xs opacity-75">
                                <div class="flex items-center space-x-2">
                                    <span class="font-medium">Employee</span>
                                </div>
                                <span class="italic">Sending...</span>
                            </div>
                        </div>
                    </div>
                </div>`;
            
            this.chatContainer.insertAdjacentHTML('beforeend', responseHtml);
            this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
        }
        
        return tempId;
    }

    updateOptimisticMessage(tempId, response) {
        const tempElement = document.querySelector(`[data-temp-id="${tempId}"]`);
        if (tempElement) {
            const bubbleElement = tempElement.querySelector('.chat-bubble');
            if (bubbleElement) {
                bubbleElement.classList.remove('opacity-75');
                const sendingSpan = tempElement.querySelector('span.italic');
                if (sendingSpan && response.formatted_date) {
                    // Extract time from formatted date like "Dec 29, 2024 at 4:40 PM"
                    const timeMatch = response.formatted_date.match(/at\s(\d{1,2}:\d{2}\s[AP]M)/);
                    const timeDisplay = timeMatch ? timeMatch[1] : new Date().toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
                    sendingSpan.textContent = timeDisplay;
                    sendingSpan.classList.remove('italic');
                }
            }
            tempElement.removeAttribute('data-temp-id');
        }
    }

    removeOptimisticMessage(tempId) {
        const tempElement = document.querySelector(`[data-temp-id="${tempId}"]`);
        if (tempElement) {
            tempElement.remove();
        }
    }

    setSendButtonState(disabled, text) {
        if (this.sendBtn) {
            this.sendBtn.disabled = disabled;
            const icon = disabled ? '<i class="fas fa-spinner fa-spin mr-2"></i>' : '<i class="fas fa-paper-plane mr-2"></i>';
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

    checkForTypingIndicators() {
        fetch(`api/get_typing_status.php?ticket_id=${this.ticketId}`)
            .then(response => response.json())
            .then(data => {
                const typingIndicator = document.getElementById('typingIndicator');
                if (data.someone_typing) {
                    typingIndicator?.classList.remove('hidden');
                } else {
                    typingIndicator?.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Typing check error:', error);
            });
    }

    updateResponseCounter() {
        const counter = document.querySelector('.bg-blue-100.text-blue-800.px-3.py-1.rounded-full');
        if (counter) {
            counter.textContent = this.lastResponseCount;
        }
        
        const activityCounter = document.querySelector('.font-bold.text-gray-900');
        if (activityCounter && activityCounter.textContent.match(/^\d+$/)) {
            activityCounter.textContent = this.lastResponseCount;
        }
    }

    addResponseToDisplay(response) {
        const emptyState = document.querySelector('.text-center.py-16');
        if (emptyState) {
            emptyState.remove();
        }

        const isTemp = response.id && response.id.toString().startsWith('temp_');
        const isStaff = response.user_type === 'it_staff';
        const alignRight = !isStaff;

        // Format timestamp
        let timeDisplay;
        if (response.formatted_date) {
            // Extract time from formatted date like "Dec 29, 2024 at 4:40 PM"
            const timeMatch = response.formatted_date.match(/at\s(\d{1,2}:\d{2}\s[AP]M)/);
            timeDisplay = timeMatch ? timeMatch[1] : new Date().toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
        } else {
            timeDisplay = new Date().toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true});
        }

        const responseHtml = `
            <div class="flex ${alignRight ? 'justify-end' : 'justify-start'} mb-2" ${isTemp ? 'data-temp-message="true"' : ''}>
                <div class="max-w-xs lg:max-w-md">
                    <div class="chat-bubble relative ${alignRight ? 'bg-blue-500 text-white rounded-l-2xl rounded-tr-2xl bubble-sent' : (isStaff ? 'bg-green-100 border border-green-200 rounded-r-2xl rounded-tl-2xl text-gray-800 bubble-staff' : 'bg-white border border-gray-200 rounded-r-2xl rounded-tl-2xl text-gray-800 bubble-received')} px-4 py-3 shadow-sm ${isTemp ? 'opacity-75 border-dashed' : ''}">
                        <p class="text-sm leading-relaxed whitespace-pre-wrap">${response.message}</p>
                        <div class="flex items-center justify-between mt-2 text-xs opacity-75">
                            <div class="flex items-center space-x-2">
                                ${response.is_internal ? '<span class="bg-yellow-200 text-yellow-800 px-2 py-0.5 rounded-full text-xs"><i class="fas fa-lock mr-1"></i>Internal</span>' : ''}
                                <span class="font-medium">${isStaff ? 'IT Support' : 'Employee'}</span>
                                ${isTemp ? '<span class="italic">(sending...)</span>' : ''}
                            </div>
                            <span>${timeDisplay}</span>
                        </div>
                    </div>
                </div>
            </div>`;

        if (this.chatContainer) {
            this.chatContainer.insertAdjacentHTML('beforeend', responseHtml);
            setTimeout(() => {
                this.chatContainer.scrollTop = this.chatContainer.scrollHeight;
            }, 100);
        }
    }

    startRealtimeUpdates() {
        console.log('Starting real-time updates...');
        
        const typingInterval = setInterval(() => this.checkForTypingIndicators(), 2000);
        
        let normalInterval = 2000;
        let fastInterval = 500;
        let currentInterval = normalInterval;
        let lastActivityTime = Date.now();

        const checkForNewResponses = () => {
            console.log('Checking for new responses... Current count:', this.lastResponseCount);
            
            fetch(`api/get_latest_responses.php?ticket_id=${this.ticketId}&after_count=${this.lastResponseCount}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    
                    if (data.success && data.new_responses && data.new_responses.length > 0) {
                        console.log(`Found ${data.new_responses.length} new responses`);
                        
                        document.querySelectorAll('[data-temp-message="true"]').forEach(temp => temp.remove());
                        
                        data.new_responses.forEach(response => {
                            this.addResponseToDisplay(response);
                        });
                        
                        this.lastResponseCount += data.new_responses.length;
                        this.updateResponseCounter();
                        
                        const notification = document.createElement('div');
                        notification.className = 'fixed top-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-50';
                        notification.innerHTML = '<i class="fas fa-comment mr-2"></i>New message received!';
                        document.body.appendChild(notification);
                        
                        setTimeout(() => {
                            if (notification.parentNode) {
                                notification.remove();
                            }
                        }, 3000);
                        
                        lastActivityTime = Date.now();
                        if (currentInterval !== fastInterval) {
                            switchToFastPolling();
                        }
                    } else {
                        console.log('No new responses found');
                        
                        if (Date.now() - lastActivityTime > 30000 && currentInterval !== normalInterval) {
                            switchToNormalPolling();
                        }
                    }
                })
                .catch(error => {
                    console.error('Response check error:', error);
                });
        };

        const switchToFastPolling = () => {
            console.log('Switching to fast polling (500ms)');
            clearInterval(this.chatIntervals.responses);
            currentInterval = fastInterval;
            this.chatIntervals.responses = setInterval(checkForNewResponses, fastInterval);
        };

        const switchToNormalPolling = () => {
            console.log('Switching to normal polling (2s)');
            clearInterval(this.chatIntervals.responses);
            currentInterval = normalInterval;
            this.chatIntervals.responses = setInterval(checkForNewResponses, normalInterval);
        };

        const responseInterval = setInterval(checkForNewResponses, currentInterval);
        
        this.chatIntervals = {
            typing: typingInterval,
            responses: responseInterval
        };

        // Expose functions globally
        window.triggerFastPolling = switchToFastPolling;
        window.addResponseToDisplay = (response) => this.addResponseToDisplay(response);
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
}

// Global function for backwards compatibility
function submitMessage(event) {
    event.preventDefault();
    console.log('submitMessage called - delegating to chat system [v3.0]');
    
    if (window.chatSystem && window.chatSystem.form) {
        window.chatSystem.form.dispatchEvent(new Event('submit'));
    } else {
        console.error('Chat system not initialized');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Get ticket data from global variables that will be set in the main page
    if (typeof window.TICKET_ID !== 'undefined' && typeof window.INITIAL_RESPONSE_COUNT !== 'undefined') {
        window.chatSystem = new ChatSystem(window.TICKET_ID, window.INITIAL_RESPONSE_COUNT);
    }
});