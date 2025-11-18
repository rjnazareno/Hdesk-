<?php
/**
 * FCM Notification Helper
 * Sends push notifications via Firebase Cloud Messaging using Firebase Admin SDK
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FCMNotification {
    
    private $messaging;
    
    public function __construct() {
        try {
            // Path to Firebase service account JSON
            $serviceAccountPath = __DIR__ . '/../config/firebase-service-account.json';
            
            if (!file_exists($serviceAccountPath)) {
                error_log('Firebase service account JSON not found: ' . $serviceAccountPath);
                $this->messaging = null;
                return;
            }
            
            // Initialize Firebase with service account
            $factory = (new Factory)->withServiceAccount($serviceAccountPath);
            $this->messaging = $factory->createMessaging();
            
        } catch (Exception $e) {
            error_log('Firebase initialization error: ' . $e->getMessage());
            $this->messaging = null;
        }
    }
    
    /**
     * Send notification to a single device
     * 
     * @param string $fcmToken Device FCM token
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @return array Response from FCM
     */
    public function sendToDevice($fcmToken, $title, $body, $data = []) {
        if (!$this->messaging) {
            return [
                'success' => false,
                'error' => 'Firebase messaging not initialized'
            ];
        }
        
        if (empty($fcmToken)) {
            return [
                'success' => false,
                'error' => 'FCM token is empty'
            ];
        }
        
        try {
            // Create notification
            $notification = Notification::create($title, $body)
                ->withImageUrl(BASE_URL . 'img/ResolveIT Logo Only without Background.png');
            
            // Add data payload
            $dataPayload = array_merge($data, [
                'timestamp' => time(),
                'app' => 'ResolveIT',
                'click_action' => $data['url'] ?? BASE_URL
            ]);
            
            // Create message
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification($notification)
                ->withData($dataPayload);
            
            // Send message
            $result = $this->messaging->send($message);
            
            error_log("FCM sent successfully to token: " . substr($fcmToken, 0, 20) . "... | Message ID: " . $result);
            
            return [
                'success' => true,
                'message_id' => $result
            ];
            
        } catch (Exception $e) {
            error_log('FCM send error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send notification to multiple devices
     * 
     * @param array $fcmTokens Array of FCM tokens
     * @param string $title Notification title
     * @param string $body Notification body
     * @param array $data Additional data payload
     * @return array Response from FCM
     */
    public function sendToMultipleDevices($fcmTokens, $title, $body, $data = []) {
        if (!$this->messaging) {
            return [
                'success' => false,
                'error' => 'Firebase messaging not initialized'
            ];
        }
        
        if (empty($fcmTokens) || !is_array($fcmTokens)) {
            return [
                'success' => false,
                'error' => 'FCM tokens array is empty'
            ];
        }
        
        // Remove empty tokens
        $fcmTokens = array_filter($fcmTokens);
        
        if (empty($fcmTokens)) {
            return [
                'success' => false,
                'error' => 'No valid FCM tokens'
            ];
        }
        
        try {
            // Create notification
            $notification = Notification::create($title, $body)
                ->withImageUrl(BASE_URL . 'img/ResolveIT Logo Only without Background.png');
            
            // Add data payload
            $dataPayload = array_merge($data, [
                'timestamp' => time(),
                'app' => 'ResolveIT',
                'click_action' => $data['url'] ?? BASE_URL
            ]);
            
            // Create multicast message
            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withData($dataPayload);
            
            // Send to multiple devices
            $result = $this->messaging->sendMulticast($message, $fcmTokens);
            
            $successCount = $result->successes()->count();
            $failureCount = $result->failures()->count();
            
            return [
                'success' => $successCount > 0,
                'success_count' => $successCount,
                'failure_count' => $failureCount,
                'total' => count($fcmTokens)
            ];
            
        } catch (Exception $e) {
            error_log('FCM multicast send error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send notification when ticket is created
     * 
     * @param int $ticketId Ticket ID
     * @param string $ticketNumber Ticket number (e.g., TKT-00123)
     * @param string $submitterName Name of submitter
     * @param string $category Ticket category
     * @return array
     */
    public function notifyTicketCreated($ticketId, $ticketNumber, $submitterName, $category) {
        // Get all IT staff FCM tokens
        $tokens = $this->getITStaffTokens();
        
        if (empty($tokens)) {
            return [
                'success' => false,
                'error' => 'No IT staff with FCM tokens found'
            ];
        }
        
        $title = "🎫 New Ticket Created";
        $body = "{$submitterName} created ticket {$ticketNumber} - {$category}";
        $data = [
            'type' => 'ticket_created',
            'ticket_id' => $ticketId,
            'ticket_number' => $ticketNumber,
            'url' => BASE_URL . "admin/view_ticket.php?id={$ticketId}"
        ];
        
        return $this->sendToMultipleDevices($tokens, $title, $body, $data);
    }
    
    /**
     * Send notification when ticket is assigned
     * 
     * @param int $ticketId Ticket ID
     * @param string $ticketNumber Ticket number
     * @param int $assignedUserId User ID of assigned IT staff
     * @param string $assignedByName Name of person who assigned
     * @return array
     */
    public function notifyTicketAssigned($ticketId, $ticketNumber, $assignedUserId, $assignedByName) {
        // Get assigned user's FCM token
        $token = $this->getUserToken($assignedUserId, 'user');
        
        if (!$token) {
            return [
                'success' => false,
                'error' => 'Assigned user has no FCM token'
            ];
        }
        
        $title = "📌 Ticket Assigned to You";
        $body = "{$assignedByName} assigned ticket {$ticketNumber} to you";
        $data = [
            'type' => 'ticket_assigned',
            'ticket_id' => $ticketId,
            'ticket_number' => $ticketNumber,
            'url' => BASE_URL . "admin/view_ticket.php?id={$ticketId}"
        ];
        
        return $this->sendToDevice($token, $title, $body, $data);
    }
    
    /**
     * Send notification when ticket status is updated
     * 
     * @param int $ticketId Ticket ID
     * @param string $ticketNumber Ticket number
     * @param string $newStatus New status
     * @param int $submitterId Submitter ID
     * @param string $submitterType Submitter type ('employee' or 'user')
     * @return array
     */
    public function notifyTicketStatusChanged($ticketId, $ticketNumber, $newStatus, $submitterId, $submitterType) {
        // Get submitter's FCM token
        $token = $this->getUserToken($submitterId, $submitterType);
        
        error_log("Status change notification: Submitter ID={$submitterId}, Type={$submitterType}, Token=" . ($token ? 'EXISTS' : 'NULL'));
        
        if (!$token) {
            error_log("No FCM token for submitter ID {$submitterId} (type: {$submitterType})");
            return [
                'success' => false,
                'error' => 'Submitter has no FCM token'
            ];
        }
        
        $statusEmoji = [
            'open' => '🟢',
            'in_progress' => '🔵',
            'resolved' => '✅',
            'closed' => '⚫',
            'pending' => '🟡'
        ];
        
        $emoji = $statusEmoji[$newStatus] ?? '📝';
        
        $title = "{$emoji} Ticket Status Updated";
        $body = "Ticket {$ticketNumber} is now: " . ucfirst(str_replace('_', ' ', $newStatus));
        $data = [
            'type' => 'ticket_status_changed',
            'ticket_id' => $ticketId,
            'ticket_number' => $ticketNumber,
            'status' => $newStatus,
            'url' => BASE_URL . "customer/view_ticket.php?id={$ticketId}"
        ];
        
        return $this->sendToDevice($token, $title, $body, $data);
    }
    
    /**
     * Get FCM token for a specific user
     * 
     * @param int $userId User ID
     * @param string $userType 'employee' or 'user'
     * @return string|null
     */
    private function getUserToken($userId, $userType) {
        try {
            $db = Database::getInstance()->getConnection();
            $table = ($userType === 'employee') ? 'employees' : 'users';
            
            $sql = "SELECT fcm_token FROM `{$table}` WHERE id = :id AND fcm_token IS NOT NULL";
            $stmt = $db->prepare($sql);
            $stmt->execute([':id' => $userId]);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['fcm_token'] ?? null;
            
        } catch (PDOException $e) {
            error_log("Error getting user FCM token: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get all IT staff FCM tokens
     * 
     * @return array
     */
    private function getITStaffTokens() {
        try {
            $db = Database::getInstance()->getConnection();
            
            $sql = "SELECT fcm_token 
                    FROM users 
                    WHERE (role = 'it_staff' OR role = 'admin') 
                    AND fcm_token IS NOT NULL 
                    AND fcm_token != ''";
            
            $stmt = $db->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return array_filter($results);
            
        } catch (PDOException $e) {
            error_log("Error getting IT staff FCM tokens: " . $e->getMessage());
            return [];
        }
    }
}
