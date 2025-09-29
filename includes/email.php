<?php
/**
 * Email Service using PHPMailer
 * Handles all email notifications for the ticketing system
 */

// Include PHPMailer (you'll need to install it via Composer)
// composer require phpmailer/phpmailer

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Uncomment these lines after installing PHPMailer via Composer:
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\SMTP;
// use PHPMailer\PHPMailer\Exception;
// 
// require_once __DIR__ . '/../vendor/autoload.php';

class EmailService {
    private $db;
    private $mailer;
    
    public function __construct() {
        $this->db = getDB();
        $this->setupMailer();
    }
    
    private function setupMailer() {
        /* 
        // Uncomment after installing PHPMailer:
        $this->mailer = new PHPMailer(true);
        
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = SMTP_PORT;
            
            // Default from address
            $this->mailer->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            
        } catch (Exception $e) {
            error_log("Mailer setup error: " . $e->getMessage());
        }
        */
        
        // For now, we'll simulate email sending
        $this->mailer = null;
    }
    
    /**
     * Send notification when a new ticket is created
     */
    public function sendTicketCreatedNotification($ticketId) {
        try {
            // Get ticket details
            $stmt = $this->db->prepare("
                SELECT t.*, e.username as employee_username, e.email as employee_email,
                       COALESCE(e.fname, e.username) as employee_name
                FROM tickets t
                JOIN employees e ON t.employee_id = e.id
                WHERE t.ticket_id = ?
            ");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                throw new Exception("Ticket not found: $ticketId");
            }
            
            // Send email to employee (confirmation)
            $this->sendEmail(
                $ticket['employee_email'],
                $ticket['employee_name'],
                "Ticket Created - #{$ticketId}: {$ticket['subject']}",
                $this->getTicketCreatedEmployeeTemplate($ticket),
                'ticket_created'
            );
            
            // Send email to IT staff (notification)
            $itEmails = explode(',', IT_NOTIFICATION_EMAILS);
            foreach ($itEmails as $email) {
                $email = trim($email);
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->sendEmail(
                        $email,
                        'IT Support Team',
                        "New Ticket - #{$ticketId}: {$ticket['subject']}",
                        $this->getTicketCreatedITTemplate($ticket),
                        'ticket_created'
                    );
                }
            }
            
            // Log notification
            $this->logNotification($ticketId, 'ticket_created', true);
            
        } catch (Exception $e) {
            error_log("Send ticket created notification error: " . $e->getMessage());
            $this->logNotification($ticketId, 'ticket_created', false, $e->getMessage());
        }
    }
    
    /**
     * Send notification when a response is added
     */
    public function sendResponseNotification($ticketId, $responseId) {
        try {
            // Get response details
            $stmt = $this->db->prepare("
                SELECT tr.*, t.subject, t.employee_id,
                       e.email as employee_email, e.username as employee_username,
                       COALESCE(e.first_name, e.username) as employee_name,
                       CASE 
                           WHEN tr.user_type = 'employee' THEN COALESCE(emp.fname, emp.username)
                           WHEN tr.user_type = 'it_staff' THEN its.name
                       END as responder_name
                FROM ticket_responses tr
                JOIN tickets t ON tr.ticket_id = t.ticket_id
                JOIN employees e ON t.employee_id = e.id
                LEFT JOIN employees emp ON tr.user_type = 'employee' AND tr.user_id = emp.id
                LEFT JOIN it_staff its ON tr.user_type = 'it_staff' AND tr.user_id = its.staff_id
                WHERE tr.response_id = ?
            ");
            $stmt->execute([$responseId]);
            $response = $stmt->fetch();
            
            if (!$response) {
                throw new Exception("Response not found: $responseId");
            }
            
            // Don't send emails for internal responses
            if ($response['is_internal']) {
                return;
            }
            
            if ($response['responder_type'] === 'it') {
                // IT staff responded - notify employee
                $this->sendEmail(
                    $response['employee_email'],
                    $response['employee_name'],
                    "Response Added - Ticket #{$ticketId}: {$response['subject']}",
                    $this->getResponseNotificationTemplate($response, 'employee'),
                    'response_added'
                );
            } else {
                // Employee responded - notify IT staff
                $itEmails = explode(',', IT_NOTIFICATION_EMAILS);
                foreach ($itEmails as $email) {
                    $email = trim($email);
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $this->sendEmail(
                            $email,
                            'IT Support Team',
                            "Employee Response - Ticket #{$ticketId}: {$response['subject']}",
                            $this->getResponseNotificationTemplate($response, 'it'),
                            'response_added'
                        );
                    }
                }
            }
            
            $this->logNotification($ticketId, 'response_added', true);
            
        } catch (Exception $e) {
            error_log("Send response notification error: " . $e->getMessage());
            $this->logNotification($ticketId, 'response_added', false, $e->getMessage());
        }
    }
    
    /**
     * Send notification when ticket is resolved
     */
    public function sendTicketResolvedNotification($ticketId) {
        try {
            // Get ticket details
            $stmt = $this->db->prepare("
                SELECT t.*, e.username as employee_username, e.email as employee_email,
                       COALESCE(e.fname, e.username) as employee_name,
                       its.name as resolved_by_name
                FROM tickets t
                JOIN employees e ON t.employee_id = e.id
                LEFT JOIN it_staff its ON t.closed_by = its.staff_id
                WHERE t.ticket_id = ?
            ");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            
            if (!$ticket) {
                throw new Exception("Ticket not found: $ticketId");
            }
            
            // Generate acknowledgment token
            $acknowledgeToken = md5($ticketId . 'acknowledge_token_salt');
            $acknowledgeUrl = APP_URL . "api/acknowledge_ticket.php?id={$ticketId}&token={$acknowledgeToken}";
            
            // Send email to employee
            $this->sendEmail(
                $ticket['employee_email'],
                $ticket['employee_name'],
                "Ticket Resolved - #{$ticketId}: {$ticket['subject']}",
                $this->getTicketResolvedTemplate($ticket, $acknowledgeUrl),
                'ticket_resolved'
            );
            
            $this->logNotification($ticketId, 'ticket_resolved', true);
            
        } catch (Exception $e) {
            error_log("Send ticket resolved notification error: " . $e->getMessage());
            $this->logNotification($ticketId, 'ticket_resolved', false, $e->getMessage());
        }
    }
    
    /**
     * Send notification when ticket is assigned
     */
    public function sendTicketAssignedNotification($ticketId) {
        try {
            // Get ticket details
            $stmt = $this->db->prepare("
                SELECT t.*, e.username as employee_username,
                       COALESCE(e.fname, e.username) as employee_name,
                       its.name as assigned_staff_name, its.email as assigned_staff_email
                FROM tickets t
                JOIN employees e ON t.employee_id = e.id
                LEFT JOIN it_staff its ON t.assigned_to = its.staff_id
                WHERE t.ticket_id = ?
            ");
            $stmt->execute([$ticketId]);
            $ticket = $stmt->fetch();
            
            if (!$ticket || !$ticket['assigned_staff_email']) {
                return; // No assigned staff or email
            }
            
            // Send email to assigned IT staff
            $this->sendEmail(
                $ticket['assigned_staff_email'],
                $ticket['assigned_staff_name'],
                "Ticket Assigned - #{$ticketId}: {$ticket['subject']}",
                $this->getTicketAssignedTemplate($ticket),
                'status_changed'
            );
            
            $this->logNotification($ticketId, 'status_changed', true);
            
        } catch (Exception $e) {
            error_log("Send ticket assigned notification error: " . $e->getMessage());
            $this->logNotification($ticketId, 'status_changed', false, $e->getMessage());
        }
    }
    
    /**
     * Send actual email using PHPMailer
     */
    private function sendEmail($to, $toName, $subject, $body, $type) {
        try {
            // For now, just log the email (since PHPMailer is not installed)
            error_log("EMAIL SIMULATION - To: $to, Subject: $subject");
            error_log("EMAIL BODY: " . $body);
            
            /* 
            // Uncomment after installing PHPMailer:
            
            if (!$this->mailer) {
                throw new Exception("Mailer not initialized");
            }
            
            // Clear previous recipients
            $this->mailer->clearAllRecipients();
            $this->mailer->clearAttachments();
            
            // Set recipient
            $this->mailer->addAddress($to, $toName);
            
            // Set content
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags(str_replace('<br>', "\n", $body));
            
            $this->mailer->send();
            */
            
            return true;
            
        } catch (Exception $e) {
            error_log("Send email error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log email notifications
     */
    private function logNotification($ticketId, $type, $success, $errorMessage = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO email_notifications (ticket_id, recipient_email, notification_type, success, error_message)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$ticketId, 'system@local', $type, $success ? 1 : 0, $errorMessage]);
        } catch (Exception $e) {
            error_log("Log notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Email Templates
     */
    
    private function getTicketCreatedEmployeeTemplate($ticket) {
        $ticketUrl = APP_URL . "ticket_view.php?id={$ticket['ticket_id']}";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Ticket Created</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>Your Support Ticket Has Been Created</h2>
                
                <p>Hello {$ticket['employee_name']},</p>
                
                <p>Your IT support ticket has been successfully submitted and our team has been notified.</p>
                
                <div style='background: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <strong>Ticket Details:</strong><br>
                    <strong>Ticket ID:</strong> #{$ticket['ticket_id']}<br>
                    <strong>Subject:</strong> {$ticket['subject']}<br>
                    <strong>Priority:</strong> " . ucfirst($ticket['priority']) . "<br>
                    <strong>Category:</strong> {$ticket['category']}<br>
                    <strong>Created:</strong> {$ticket['created_at']}
                </div>
                
                <p><strong>Description:</strong></p>
                <p style='background: #f9f9f9; padding: 10px; border-left: 4px solid #2563eb;'>" . nl2br(htmlspecialchars($ticket['description'])) . "</p>
                
                <p><a href='{$ticketUrl}' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Ticket</a></p>
                
                <p>Our IT team will respond to your request as soon as possible. You will receive email notifications when there are updates to your ticket.</p>
                
                <p>Thank you,<br>" . SMTP_FROM_NAME . "</p>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getTicketCreatedITTemplate($ticket) {
        $ticketUrl = APP_URL . "ticket_view.php?id={$ticket['ticket_id']}";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>New Support Ticket</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #dc2626;'>New Support Ticket Submitted</h2>
                
                <p>A new support ticket has been submitted and requires attention.</p>
                
                <div style='background: #fef2f2; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #dc2626;'>
                    <strong>Ticket Details:</strong><br>
                    <strong>Ticket ID:</strong> #{$ticket['ticket_id']}<br>
                    <strong>Employee:</strong> {$ticket['employee_name']} ({$ticket['employee_username']})<br>
                    <strong>Subject:</strong> {$ticket['subject']}<br>
                    <strong>Priority:</strong> " . ucfirst($ticket['priority']) . "<br>
                    <strong>Category:</strong> {$ticket['category']}<br>
                    <strong>Created:</strong> {$ticket['created_at']}
                </div>
                
                <p><strong>Description:</strong></p>
                <p style='background: #f9f9f9; padding: 10px; border-left: 4px solid #dc2626;'>" . nl2br(htmlspecialchars($ticket['description'])) . "</p>
                
                <p><a href='{$ticketUrl}' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View & Assign Ticket</a></p>
                
                <p>Please review and assign this ticket to the appropriate team member.</p>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getResponseNotificationTemplate($response, $recipient) {
        $ticketUrl = APP_URL . "ticket_view.php?id={$response['ticket_id']}";
        
        if ($recipient === 'employee') {
            $title = "IT Response Added to Your Ticket";
            $message = "Our IT team has responded to your support ticket.";
        } else {
            $title = "Employee Response Added to Ticket";
            $message = "The employee has responded to the support ticket.";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Ticket Response</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #2563eb;'>{$title}</h2>
                
                <p>{$message}</p>
                
                <div style='background: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <strong>Ticket #{$response['ticket_id']}:</strong> {$response['subject']}<br>
                    <strong>Response by:</strong> {$response['responder_name']}<br>
                    <strong>Response time:</strong> {$response['created_at']}
                </div>
                
                <p><strong>Response:</strong></p>
                <p style='background: #f9f9f9; padding: 10px; border-left: 4px solid #2563eb;'>" . nl2br(htmlspecialchars($response['message'])) . "</p>
                
                <p><a href='{$ticketUrl}' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Full Conversation</a></p>
                
                <p>Thank you,<br>" . SMTP_FROM_NAME . "</p>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getTicketResolvedTemplate($ticket, $acknowledgeUrl) {
        $ticketUrl = APP_URL . "ticket_view.php?id={$ticket['ticket_id']}";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Ticket Resolved</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #059669;'>Your Support Ticket Has Been Resolved</h2>
                
                <p>Hello {$ticket['employee_name']},</p>
                
                <p>Good news! Your IT support ticket has been resolved by our team.</p>
                
                <div style='background: #f0fdf4; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #059669;'>
                    <strong>Ticket Details:</strong><br>
                    <strong>Ticket ID:</strong> #{$ticket['ticket_id']}<br>
                    <strong>Subject:</strong> {$ticket['subject']}<br>
                    <strong>Resolved by:</strong> {$ticket['resolved_by_name']}<br>
                    <strong>Resolution Date:</strong> {$ticket['updated_at']}
                </div>
                
                <p>Please review the resolution and let us know if the issue has been fully resolved.</p>
                
                <div style='margin: 30px 0; text-align: center;'>
                    <a href='{$acknowledgeUrl}' style='background: #059669; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>✓ Acknowledge Resolution</a>
                    <a href='{$ticketUrl}' style='background: #6b7280; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;'>View Ticket Details</a>
                </div>
                
                <p><strong>Important:</strong> If the issue is not fully resolved, please respond to the ticket and we will reopen it for further investigation.</p>
                
                <p>Thank you for using our IT support system!</p>
                
                <p>Best regards,<br>" . SMTP_FROM_NAME . "</p>
            </div>
        </body>
        </html>
        ";
    }
    
    private function getTicketAssignedTemplate($ticket) {
        $ticketUrl = APP_URL . "ticket_view.php?id={$ticket['ticket_id']}";
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Ticket Assigned</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #7c3aed;'>Support Ticket Assigned to You</h2>
                
                <p>Hello {$ticket['assigned_staff_name']},</p>
                
                <p>A support ticket has been assigned to you for resolution.</p>
                
                <div style='background: #faf5ff; padding: 15px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #7c3aed;'>
                    <strong>Ticket Details:</strong><br>
                    <strong>Ticket ID:</strong> #{$ticket['ticket_id']}<br>
                    <strong>Employee:</strong> {$ticket['employee_name']}<br>
                    <strong>Subject:</strong> {$ticket['subject']}<br>
                    <strong>Priority:</strong> " . ucfirst($ticket['priority']) . "<br>
                    <strong>Category:</strong> {$ticket['category']}<br>
                    <strong>Created:</strong> {$ticket['created_at']}
                </div>
                
                <p><strong>Description:</strong></p>
                <p style='background: #f9f9f9; padding: 10px; border-left: 4px solid #7c3aed;'>" . nl2br(htmlspecialchars($ticket['description'])) . "</p>
                
                <p><a href='{$ticketUrl}' style='background: #7c3aed; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View & Respond to Ticket</a></p>
                
                <p>Please review this ticket and respond as soon as possible.</p>
                
                <p>Thank you,<br>" . SMTP_FROM_NAME . "</p>
            </div>
        </body>
        </html>
        ";
    }
}
?>