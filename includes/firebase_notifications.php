<?php
/**
 * Firebase Cloud Messaging Notification Sender
 * Sends push notifications via Firebase FCM
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/MessageTracker.php';

class FirebaseNotificationSender {
    private $serverKey;
    private $projectId;
    
    public function __construct() {
        // Firebase Server Key (Legacy) - Try to get from environment or config
        $this->serverKey = $this->getServerKey();
        $this->projectId = 'rssticket-a8d0a';
    }
    
    private function getServerKey() {
        // Try multiple methods to get server key
        
        // Method 1: Service Account JSON (preferred)
        if (file_exists(__DIR__ . '/../config/firebase_service_account.php')) {
            $serviceAccount = include __DIR__ . '/../config/firebase_service_account.php';
            if ($serviceAccount && isset($serviceAccount['private_key'])) {
                return $this->generateAccessTokenFromServiceAccount($serviceAccount);
            }
        }
        
        // Method 2: Environment variable  
        if ($envKey = getenv('FIREBASE_SERVER_KEY')) {
            return $envKey;
        }
        
        // Method 3: Config file
        if (file_exists(__DIR__ . '/../config/firebase_server_key.txt')) {
            return trim(file_get_contents(__DIR__ . '/../config/firebase_server_key.txt'));
        }
        
        // Method 4: Firebase Browser Key (from your Firebase config)
        // This is the same key you're using in firebase-config.js
        if ($firebaseKey = 'AIzaSyC7NBIsU2F8vve9eKPTz6d2i7ns0Cwen90') {
            return $firebaseKey;
        }
        
        // Method 5: Try the transport key from Firebase Performance
        if ($transportKey = 'AIzaSyCx80ru6-RXeTi3GvqkFsMVyMf-vpgIoVw') {
            return $transportKey;
        }
        
        // Method 6: Fallback - paste server key here if found
        return 'TEMP_PASTE_YOUR_SERVER_KEY_HERE'; // TODO: Replace with actual server key
    }
    
    private function generateAccessTokenFromServiceAccount($serviceAccount) {
        try {
            // Create JWT for service account authentication
            $now = time();
            $header = json_encode(['typ' => 'JWT', 'alg' => 'RS256']);
            $payload = json_encode([
                'iss' => $serviceAccount['client_email'],
                'sub' => $serviceAccount['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging'
            ]);
            
            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
            
            $signature = '';
            $privateKey = openssl_pkey_get_private($serviceAccount['private_key']);
            openssl_sign($base64Header . '.' . $base64Payload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            
            $jwt = $base64Header . '.' . $base64Payload . '.' . $base64Signature;
            
            // Exchange JWT for access token
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $tokenData = json_decode($response, true);
                return $tokenData['access_token'] ?? null;
            }
            
            error_log("OAuth2 token error: " . $response);
            return null;
            
        } catch (Exception $e) {
            error_log("Service account token generation error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Send notification to specific user
     */
    public function sendToUser($userId, $userType, $notification) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get active FCM tokens for user
            $stmt = $db->prepare("
                SELECT token FROM fcm_tokens 
                WHERE user_id = ? AND user_type = ? AND is_active = 1 
                ORDER BY updated_at DESC
            ");
            $stmt->execute([$userId, $userType]);
            $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tokens)) {
                return ['success' => false, 'error' => 'No active FCM tokens found'];
            }
            
            $results = [];
            foreach ($tokens as $token) {
                $result = $this->sendNotification($token, $notification);
                $results[] = $result;
            }
            
            return ['success' => true, 'results' => $results, 'tokens_sent' => count($tokens)];
            
        } catch (Exception $e) {
            error_log("Firebase notification error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send notification to all users of a specific type
     */
    public function sendToUserType($userType, $notification, $excludeUserId = null) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $sql = "SELECT DISTINCT token FROM fcm_tokens 
                   WHERE user_type = ? AND is_active = 1";
            $params = [$userType];
            
            if ($excludeUserId) {
                $sql .= " AND user_id != ?";
                $params[] = $excludeUserId;
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $tokens = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($tokens)) {
                return ['success' => false, 'error' => 'No active FCM tokens found'];
            }
            
            $results = [];
            foreach ($tokens as $token) {
                $result = $this->sendNotification($token, $notification);
                $results[] = $result;
            }
            
            return ['success' => true, 'results' => $results, 'tokens_sent' => count($tokens)];
            
        } catch (Exception $e) {
            error_log("Firebase notification error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Send notification via Firebase FCM API
     */
    public function sendNotification($token, $notification) {
        try {
            // Determine which API to use based on key type
            $isOAuth2 = strpos($this->serverKey, 'ya29.') === 0; // OAuth2 access tokens start with ya29.
            
            if ($isOAuth2) {
                // Use v1 API with OAuth2 token
                $fcmUrl = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";
                $payload = [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $notification['title'] ?? 'IT Help Desk',
                            'body' => $notification['body'] ?? 'New notification',
                            'image' => $notification['image'] ?? null // Large photo
                        ],
                        'data' => $notification['data'] ?? [],
                        'webpush' => [
                            'notification' => [
                                'icon' => $notification['icon'] ?? '/favicon.ico',
                                'image' => $notification['image'] ?? null, // Large photo
                                'badge' => '/favicon.ico',
                                'click_action' => $notification['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK',
                                'requireInteraction' => $notification['requireInteraction'] ?? false
                            ]
                        ]
                    ]
                ];
                $headers = [
                    'Authorization: Bearer ' . $this->serverKey,
                    'Content-Type: application/json'
                ];
            } else {
                // Use legacy API with server key
                $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
                $payload = [
                    'to' => $token,
                    'notification' => [
                        'title' => $notification['title'] ?? 'IT Help Desk',
                        'body' => $notification['body'] ?? 'New notification', 
                        'icon' => $notification['icon'] ?? '/favicon.ico',
                        'image' => $notification['image'] ?? null, // Large photo
                        'badge' => '/favicon.ico',
                        'click_action' => $notification['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK'
                    ],
                    'data' => $notification['data'] ?? []
                ];
                $headers = [
                    'Authorization: key=' . $this->serverKey,
                    'Content-Type: application/json'
                ];
            }
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $fcmUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200) {
                if ($isOAuth2) {
                    // v1 API success
                    return ['success' => true, 'response' => $result];
                } else {
                    // Legacy API success
                    if (isset($result['success']) && $result['success'] > 0) {
                        return ['success' => true, 'response' => $result];
                    }
                }
            }
            
            // Handle errors and invalid tokens
            if (isset($result['results'][0]['error']) && 
                in_array($result['results'][0]['error'], ['InvalidRegistration', 'NotRegistered'])) {
                $this->deactivateToken($token);
            }
            
            return ['success' => false, 'error' => $result, 'http_code' => $httpCode];
            
        } catch (Exception $e) {
            error_log("FCM send error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Generate user avatar/photo URL
     */
    private function getUserPhoto($userId, $userName) {
        // Option 1: Check if user has uploaded profile photo
        $uploadsPath = __DIR__ . '/../uploads/profiles/' . $userId . '.jpg';
        if (file_exists($uploadsPath)) {
            $baseUrl = $this->getBaseUrl();
            return $baseUrl . '/uploads/profiles/' . $userId . '.jpg';
        }
        
        // Option 2: Generate avatar from name using external service
        $initials = $this->getInitials($userName);
        return "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&size=200&background=0D8ABC&color=fff&bold=true";
        
        // Option 3: Use Gravatar (if email available)
        // $email = $this->getUserEmail($userId); 
        // return "https://www.gravatar.com/avatar/" . md5(strtolower(trim($email))) . "?s=200&d=identicon";
    }
    
    /**
     * Get user initials from name
     */
    private function getInitials($name) {
        $words = explode(' ', trim($name));
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
                if (strlen($initials) >= 2) break;
            }
        }
        return $initials ?: 'U';
    }
    
    /**
     * Get base URL for images
     */
    private function getBaseUrl() {
        // Auto-detect environment
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            if (strpos($host, 'ithelp.resourcestaffonline.com') !== false) {
                return 'https://ithelp.resourcestaffonline.com/IThelp';
            }
            return $protocol . '://' . $host . '/IThelp';
        }
        
        // Fallback for CLI/cron jobs
        return 'https://ithelp.resourcestaffonline.com/IThelp';
    }
    
    /**
     * Public method to test photo generation
     */
    public function testPhotoGeneration($userId, $userName) {
        return $this->getUserPhoto($userId, $userName);
    }
    
    /**
     * Deactivate invalid FCM token
     */
    private function deactivateToken($token) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("UPDATE fcm_tokens SET is_active = 0 WHERE token = ?");
            $stmt->execute([$token]);
        } catch (Exception $e) {
            error_log("Error deactivating FCM token: " . $e->getMessage());
        }
    }
    
    /**
     * Helper methods for common notification types
     */
    
    public function sendNewReplyNotification($ticketId, $fromUserId, $fromUserType, $message) {
        try {
            $db = Database::getInstance()->getConnection();
            $messageTracker = new MessageTracker();
            
            // Get the response ID of the message we're notifying about
            $stmt = $db->prepare("SELECT response_id FROM ticket_responses WHERE ticket_id = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->execute([$ticketId]);
            $latestResponse = $stmt->fetch(PDO::FETCH_ASSOC);
            $responseId = $latestResponse['response_id'] ?? null;
            
            if (!$responseId) {
                return ['success' => false, 'error' => 'No response found to notify about'];
            }
            
            // Get ticket and recipient info
            $stmt = $db->prepare("
                SELECT t.*, e.fname, e.lname, e.id as employee_user_id,
                       its.name, its.staff_id as staff_user_id
                FROM tickets t
                LEFT JOIN employees e ON t.employee_id = e.id
                LEFT JOIN it_staff its ON t.assigned_to = its.staff_id
                WHERE t.ticket_id = ?
            ");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) return ['success' => false, 'error' => 'Ticket not found'];
            
            // Determine recipient
            if ($fromUserType === 'employee') {
                // Employee replied, notify IT staff
                $recipientId = $ticket['staff_user_id'] ?? null;
                $recipientType = 'it_staff';
                $fromName = trim($ticket['fname'] . ' ' . $ticket['lname']);
                
                // ✅ PREVENT SELF-NOTIFICATION: Don't notify if employee is replying to their own ticket
                error_log("DEBUG: Checking self-notification - fromUserId: {$fromUserId}, recipientId: {$recipientId}, fromUserType: {$fromUserType}");
                if ($recipientId === $fromUserId) {
                    error_log("SKIPPED: Self-notification prevented for user {$fromUserId}");
                    return ['success' => true, 'skipped' => true, 'reason' => 'Self-notification prevented'];
                }
                
                // Also notify all IT staff if not assigned
                if (!$recipientId) {
                    return $this->sendToUserType('it_staff', [
                        'title' => "New Reply - Ticket #{$ticketId}",
                        'body' => "{$fromName} replied: " . substr($message, 0, 100),
                        'icon' => '/favicon.ico',
                        'image' => $this->getUserPhoto($fromUserId, $fromName),
                        'click_action' => "view_ticket.php?id={$ticketId}",
                        'data' => [
                            'type' => 'new_reply',
                            'action' => 'new_reply',
                            'ticket_id' => (string)$ticketId,
                            'from_user_type' => (string)$fromUserType,
                            'action_url' => "view_ticket.php?id={$ticketId}"
                        ]
                    ], $fromUserId);
                }
            } else {
                // IT staff replied, notify employee
                $recipientId = $ticket['employee_user_id'];
                $recipientType = 'employee';
                $fromName = $ticket['name'] ?? 'IT Support';
                
                // ✅ PREVENT SELF-NOTIFICATION: Don't notify if IT staff is replying as the same user
                error_log("DEBUG: IT staff notification - fromUserId: {$fromUserId}, recipientId: {$recipientId}, fromUserType: {$fromUserType}");
                if ($recipientId === $fromUserId) {
                    error_log("SKIPPED: Self-notification prevented for IT staff user {$fromUserId}");
                    return ['success' => true, 'skipped' => true, 'reason' => 'Self-notification prevented'];
                }
            }
            
            if (!$recipientId) {
                return ['success' => false, 'error' => 'No recipient found'];
            }
            
            // ✅ CHECK FOR DUPLICATE NOTIFICATIONS
            if ($messageTracker->wasNotificationSent($ticketId, $responseId, $recipientId, $recipientType)) {
                error_log("Skipping duplicate notification: Ticket {$ticketId}, Response {$responseId}, User {$recipientId} ({$recipientType})");
                return ['success' => true, 'skipped' => true, 'reason' => 'Already notified for this message'];
            }
            
            // ✅ CHECK IF MESSAGE WAS ALREADY SEEN  
            $stmt = $db->prepare("SELECT seen_at FROM message_seen WHERE ticket_id = ? AND seen_by_user_id = ? AND seen_by_user_type = ? AND response_id = ?");
            $stmt->execute([$ticketId, $recipientId, $recipientType, $responseId]);
            $seenRecord = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($seenRecord && $seenRecord['seen_at']) {
                error_log("Skipping FCM notification for already seen message: Ticket {$ticketId}, Response {$responseId}, User {$recipientId} ({$recipientType})");
                return ['success' => true, 'skipped' => true, 'reason' => 'Message already seen by recipient'];
            }
            
            // ✅ CREATE NOTIFICATION WITH PHOTO
            $notification = [
                'title' => "💬 New Reply - Ticket #{$ticketId}",
                'body' => "{$fromName} replied: " . substr($message, 0, 100) . (strlen($message) > 100 ? '...' : ''),
                'icon' => '/IThelp/favicon.ico',
                'image' => $this->getUserPhoto($fromUserId, $fromName), // 📸 USER PHOTO HERE
                'click_action' => "/IThelp/view_ticket.php?id={$ticketId}",
                'requireInteraction' => true,
                'data' => [
                    'type' => 'new_reply',
                    'action' => 'new_reply',
                    'ticket_id' => (string)$ticketId,
                    'response_id' => (string)$responseId,
                    'from_user_id' => (string)$fromUserId,
                    'from_user_type' => (string)$fromUserType,
                    'action_url' => "/IThelp/view_ticket.php?id={$ticketId}"
                ]
            ];
            
            // ✅ SEND NOTIFICATION
            $result = $this->sendToUser($recipientId, $recipientType, $notification);
            
            // ✅ LOG NOTIFICATION SENT (prevent duplicates)
            if ($result['success']) {
                $messageTracker->logNotificationSent($ticketId, $responseId, $recipientId, $recipientType, 'new_reply');
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("New reply notification error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function sendStatusChangeNotification($ticketId, $newStatus, $changedBy) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get ticket info
            $stmt = $db->prepare("
                SELECT t.*, e.fname, e.lname, e.id as employee_user_id
                FROM tickets t
                LEFT JOIN employees e ON t.employee_id = e.id
                WHERE t.ticket_id = ?
            ");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) return ['success' => false, 'error' => 'Ticket not found'];
            
            $statusMessages = [
                'open' => 'has been reopened 🔄',
                'in_progress' => 'is now being worked on 🔧', 
                'resolved' => 'has been resolved ✅',
                'closed' => 'has been closed and completed ✅🔒'
            ];
            
            $statusTitles = [
                'open' => '🔄 Ticket Reopened',
                'in_progress' => '🔧 Work Started',
                'resolved' => '✅ Ticket Resolved', 
                'closed' => '🎯 Ticket Closed'
            ];
            
            $statusMessage = $statusMessages[$newStatus] ?? "status changed to {$newStatus}";
            $statusTitle = $statusTitles[$newStatus] ?? "Status Update";
            
            $notification = [
                'title' => "{$statusTitle} - Ticket #{$ticketId}",
                'body' => "Your ticket {$statusMessage}",
                'icon' => '/IThelp/favicon.ico',
                'image' => $this->getUserPhoto($changedBy, 'IT Support'),
                'click_action' => "/IThelp/view_ticket.php?id={$ticketId}",
                'requireInteraction' => $newStatus === 'closed', // Require interaction for closed tickets
                'data' => [
                    'type' => 'status_change',
                    'action' => 'status_change',
                    'ticket_id' => (string)$ticketId,
                    'new_status' => (string)$newStatus,
                    'action_url' => "view_ticket.php?id={$ticketId}"
                ]
            ];
            
            return $this->sendToUser($ticket['employee_user_id'], 'employee', $notification);
            
        } catch (Exception $e) {
            error_log("Status change notification error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public function sendTicketClosedNotification($ticketId, $closedBy, $resolutionNote = '') {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get ticket info
            $stmt = $db->prepare("
                SELECT t.*, e.fname, e.lname, e.id as employee_user_id,
                       its.name as staff_name
                FROM tickets t
                LEFT JOIN employees e ON t.employee_id = e.id  
                LEFT JOIN it_staff its ON its.staff_id = ?
                WHERE t.ticket_id = ?
            ");
            $stmt->execute([$closedBy, $ticketId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) return ['success' => false, 'error' => 'Ticket not found'];
            
            $staffName = $ticket['staff_name'] ?? 'IT Support';
            $bodyMessage = "Your ticket has been resolved and closed by {$staffName}";
            
            if ($resolutionNote) {
                $bodyMessage .= ". Resolution: " . substr($resolutionNote, 0, 100);
                if (strlen($resolutionNote) > 100) $bodyMessage .= '...';
            }
            
            $notification = [
                'title' => "🎯 Ticket Closed - #{$ticketId}",
                'body' => $bodyMessage,
                'icon' => '/IThelp/favicon.ico',
                'image' => $this->getUserPhoto($closedBy, $staffName),
                'click_action' => "/IThelp/view_ticket.php?id={$ticketId}",
                'requireInteraction' => true, // Important notification for closure
                'data' => [
                    'type' => 'ticket_closed',
                    'action' => 'ticket_closed', 
                    'ticket_id' => (string)$ticketId,
                    'closed_by' => (string)$closedBy,
                    'staff_name' => (string)$staffName,
                    'action_url' => "/IThelp/view_ticket.php?id={$ticketId}"
                ]
            ];
            
            return $this->sendToUser($ticket['employee_user_id'], 'employee', $notification);
            
        } catch (Exception $e) {
            error_log("Ticket closed notification error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function sendNewTicketNotification($ticketId) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Get ticket info
            $stmt = $db->prepare("
                SELECT t.*, e.fname, e.lname
                FROM tickets t
                LEFT JOIN employees e ON t.employee_id = e.id
                WHERE t.ticket_id = ?
            ");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$ticket) return ['success' => false, 'error' => 'Ticket not found'];
            
            $fromName = trim($ticket['fname'] . ' ' . $ticket['lname']);
            
            $notification = [
                'title' => "New Ticket Created - #{$ticketId}",
                'body' => "{$fromName} created: " . substr($ticket['subject'], 0, 100),
                'icon' => '/favicon.ico',
                'click_action' => "view_ticket.php?id={$ticketId}",
                'data' => [
                    'type' => 'new_ticket',
                    'action' => 'new_ticket',
                    'ticket_id' => (string)$ticketId,
                    'priority' => (string)($ticket['priority'] ?? ''),
                    'subject' => (string)($ticket['subject'] ?? ''),
                    'action_url' => "view_ticket.php?id={$ticketId}"
                ]
            ];
            
            return $this->sendToUserType('it_staff', $notification);
            
        } catch (Exception $e) {
            error_log("New ticket notification error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>