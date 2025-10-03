// Firebase Real-time Chat System
import { ref, push, onValue, serverTimestamp } from 'https://www.gstatic.com/firebasejs/10.7.1/firebase-database.js';

class FirebaseChat {
    constructor(ticketId, userId, userType) {
        this.ticketId = ticketId;
        this.userId = userId;
        this.userType = userType;
        this.messagesRef = ref(window.firebaseDb, `tickets/${ticketId}/messages`);
        this.connected = false;
        
        console.log('🔥 FirebaseChat initialized for ticket:', ticketId);
        this.initializeListener();
    }
    
    initializeListener() {
        // Listen for new messages
        onValue(this.messagesRef, (snapshot) => {
            this.connected = true;
            const messages = snapshot.val();
            if (messages) {
                this.handleMessagesUpdate(messages);
            }
        }, (error) => {
            console.error('🔥 Firebase connection error:', error);
            this.connected = false;
        });
    }
    
    handleMessagesUpdate(messages) {
        // Convert Firebase messages to array
        const messageArray = Object.entries(messages).map(([key, value]) => ({
            id: key,
            ...value
        }));
        
        // Update UI with new messages
        if (window.updateChatFromFirebase) {
            window.updateChatFromFirebase(messageArray);
        }
    }
    
    sendMessage(message, isInternal = false) {
        const messageData = {
            message: message,
            user_id: this.userId,
            user_type: this.userType,
            is_internal: isInternal,
            timestamp: serverTimestamp(),
            created_at: new Date().toISOString()
        };
        
        console.log('🔥 Sending message via Firebase:', messageData);
        
        // Add to Firebase for real-time sync
        return push(this.messagesRef, messageData).then(() => {
            console.log('🔥 Message sent to Firebase successfully');
            return { success: true };
        }).catch((error) => {
            console.error('🔥 Firebase send failed:', error);
            throw error;
        });
    }
    
    isConnected() {
        return this.connected;
    }
}

// Export for global access
window.FirebaseChat = FirebaseChat;