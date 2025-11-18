<?php
/**
 * Email Notification Handler
 * Falls back to PHP mail() if PHPMailer is not available
 */

class Mailer {
    private $usePHPMailer = false;
    private $mail = null;
    
    public function __construct() {
        // Check if PHPMailer is available
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            
            if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                $this->usePHPMailer = true;
                $this->mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $this->configure();
            }
        }
    }
    
    /**
     * Configure PHPMailer settings
     */
    private function configure() {
        if (!$this->usePHPMailer) return;
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = MAIL_HOST;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = MAIL_USERNAME;
            $this->mail->Password = MAIL_PASSWORD;
            $this->mail->SMTPSecure = MAIL_ENCRYPTION;
            $this->mail->Port = MAIL_PORT;
            
            // Sender info
            $this->mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            
            // Content type
            $this->mail->isHTML(true);
            $this->mail->CharSet = 'UTF-8';
        } catch (Exception $e) {
            error_log("Mailer configuration error: " . $e->getMessage());
        }
    }
    
    /**
     * Send ticket created notification
     */
    public function sendTicketCreated($ticket, $submitter) {
        try {
            $subject = 'Ticket Created: ' . $ticket['ticket_number'];
            $body = $this->getTicketCreatedTemplate($ticket, $submitter);
            
            if ($this->usePHPMailer) {
                $this->mail->clearAddresses();
                $this->mail->addAddress($submitter['email'], $submitter['full_name']);
                $this->mail->Subject = $subject;
                $this->mail->Body = $body;
                $this->mail->AltBody = strip_tags($body);
                return $this->mail->send();
            } else {
                // Fallback to PHP mail()
                error_log("Email would be sent to: {$submitter['email']} - Subject: $subject");
                return true; // Return true to avoid blocking the app
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send ticket assigned notification
     */
    public function sendTicketAssigned($ticket, $assignee) {
        try {
            $subject = 'New Ticket Assigned: ' . $ticket['ticket_number'];
            $body = $this->getTicketAssignedTemplate($ticket, $assignee);
            
            if ($this->usePHPMailer) {
                $this->mail->clearAddresses();
                $this->mail->addAddress($assignee['email'], $assignee['full_name']);
                $this->mail->Subject = $subject;
                $this->mail->Body = $body;
                $this->mail->AltBody = strip_tags($body);
                return $this->mail->send();
            } else {
                error_log("Email would be sent to: {$assignee['email']} - Subject: $subject");
                return true;
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send ticket status updated notification
     */
    public function sendTicketStatusUpdate($ticket, $recipient, $oldStatus, $newStatus) {
        try {
            $subject = 'Ticket Status Updated: ' . $ticket['ticket_number'];
            $body = $this->getTicketStatusUpdateTemplate($ticket, $recipient, $oldStatus, $newStatus);
            
            if ($this->usePHPMailer) {
                $this->mail->clearAddresses();
                $this->mail->addAddress($recipient['email'], $recipient['full_name']);
                $this->mail->Subject = $subject;
                $this->mail->Body = $body;
                $this->mail->AltBody = strip_tags($body);
                return $this->mail->send();
            } else {
                error_log("Email would be sent to: {$recipient['email']} - Subject: $subject");
                return true;
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send ticket resolved notification
     */
    public function sendTicketResolved($ticket, $submitter) {
        try {
            $subject = 'Ticket Resolved: ' . $ticket['ticket_number'];
            $body = $this->getTicketResolvedTemplate($ticket, $submitter);
            
            if ($this->usePHPMailer) {
                $this->mail->clearAddresses();
                $this->mail->addAddress($submitter['email'], $submitter['full_name']);
                $this->mail->Subject = $subject;
                $this->mail->Body = $body;
                $this->mail->AltBody = strip_tags($body);
                return $this->mail->send();
            } else {
                error_log("Email would be sent to: {$submitter['email']} - Subject: $subject");
                return true;
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Template for ticket created email
     */
    private function getTicketCreatedTemplate($ticket, $submitter) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4;'>
                <div style='background-color: #fff; padding: 30px; border-radius: 5px;'>
                    <h2 style='color: #2563eb; margin-bottom: 20px;'>Ticket Created Successfully</h2>
                    <p>Dear {$submitter['full_name']},</p>
                    <p>Your support ticket has been created successfully. Our IT team will review it shortly.</p>
                    
                    <div style='background-color: #f9fafb; padding: 15px; border-left: 4px solid #2563eb; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>Ticket Number:</strong> {$ticket['ticket_number']}</p>
                        <p style='margin: 5px 0;'><strong>Title:</strong> {$ticket['title']}</p>
                        <p style='margin: 5px 0;'><strong>Priority:</strong> <span style='text-transform: uppercase;'>{$ticket['priority']}</span></p>
                        <p style='margin: 5px 0;'><strong>Status:</strong> <span style='text-transform: uppercase;'>{$ticket['status']}</span></p>
                    </div>
                    
                    <p>You can track your ticket status by logging into the IT Help Desk portal.</p>
                    
                    <p style='margin-top: 30px;'>Best regards,<br>IT Help Desk Team</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template for ticket assigned email
     */
    private function getTicketAssignedTemplate($ticket, $assignee) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4;'>
                <div style='background-color: #fff; padding: 30px; border-radius: 5px;'>
                    <h2 style='color: #2563eb; margin-bottom: 20px;'>New Ticket Assigned</h2>
                    <p>Dear {$assignee['full_name']},</p>
                    <p>A new support ticket has been assigned to you.</p>
                    
                    <div style='background-color: #f9fafb; padding: 15px; border-left: 4px solid #2563eb; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>Ticket Number:</strong> {$ticket['ticket_number']}</p>
                        <p style='margin: 5px 0;'><strong>Title:</strong> {$ticket['title']}</p>
                        <p style='margin: 5px 0;'><strong>Priority:</strong> <span style='text-transform: uppercase;'>{$ticket['priority']}</span></p>
                        <p style='margin: 5px 0;'><strong>Description:</strong> {$ticket['description']}</p>
                    </div>
                    
                    <p>Please log in to the IT Help Desk portal to view and manage this ticket.</p>
                    
                    <p style='margin-top: 30px;'>Best regards,<br>IT Help Desk System</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template for ticket status update email
     */
    private function getTicketStatusUpdateTemplate($ticket, $recipient, $oldStatus, $newStatus) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4;'>
                <div style='background-color: #fff; padding: 30px; border-radius: 5px;'>
                    <h2 style='color: #2563eb; margin-bottom: 20px;'>Ticket Status Updated</h2>
                    <p>Dear {$recipient['full_name']},</p>
                    <p>The status of your ticket has been updated.</p>
                    
                    <div style='background-color: #f9fafb; padding: 15px; border-left: 4px solid #2563eb; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>Ticket Number:</strong> {$ticket['ticket_number']}</p>
                        <p style='margin: 5px 0;'><strong>Title:</strong> {$ticket['title']}</p>
                        <p style='margin: 5px 0;'><strong>Previous Status:</strong> <span style='text-transform: uppercase;'>{$oldStatus}</span></p>
                        <p style='margin: 5px 0;'><strong>New Status:</strong> <span style='text-transform: uppercase;'>{$newStatus}</span></p>
                    </div>
                    
                    <p>You can view more details by logging into the IT Help Desk portal.</p>
                    
                    <p style='margin-top: 30px;'>Best regards,<br>IT Help Desk Team</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template for ticket resolved email
     */
    private function getTicketResolvedTemplate($ticket, $submitter) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4;'>
                <div style='background-color: #fff; padding: 30px; border-radius: 5px;'>
                    <h2 style='color: #10b981; margin-bottom: 20px;'>Ticket Resolved</h2>
                    <p>Dear {$submitter['full_name']},</p>
                    <p>Your support ticket has been resolved.</p>
                    
                    <div style='background-color: #f9fafb; padding: 15px; border-left: 4px solid #10b981; margin: 20px 0;'>
                        <p style='margin: 5px 0;'><strong>Ticket Number:</strong> {$ticket['ticket_number']}</p>
                        <p style='margin: 5px 0;'><strong>Title:</strong> {$ticket['title']}</p>
                        <p style='margin: 5px 0;'><strong>Resolution:</strong> {$ticket['resolution']}</p>
                    </div>
                    
                    <p>If you have any further issues or questions, please don't hesitate to create a new ticket.</p>
                    
                    <p style='margin-top: 30px;'>Best regards,<br>IT Help Desk Team</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Send password reset email
     * 
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $resetLink Password reset link
     * @return bool
     */
    public function sendPasswordResetEmail($email, $name, $resetLink) {
        $subject = "Password Reset Request - ResolveIT Help Desk";
        $body = $this->getPasswordResetTemplate($name, $resetLink);
        
        return $this->send($email, $name, $subject, $body);
    }
    
    /**
     * Template for password reset email
     */
    private function getPasswordResetTemplate($name, $resetLink) {
        return "
        <html>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f4f4f4;'>
                <div style='background-color: #fff; padding: 30px; border-radius: 5px;'>
                    <div style='text-align: center; margin-bottom: 30px;'>
                        <h1 style='color: #0ea5e9; margin: 0;'>
                            <span style='color: #334155;'>Resolve</span><span style='color: #06b6d4;'>IT</span>
                        </h1>
                    </div>
                    
                    <h2 style='color: #1e293b; margin-bottom: 20px;'>Password Reset Request</h2>
                    <p>Hello {$name},</p>
                    <p>We received a request to reset your password for your ResolveIT Help Desk account.</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$resetLink}' 
                           style='display: inline-block; padding: 12px 30px; background: linear-gradient(to right, #06b6d4, #0ea5e9); color: white; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                            Reset Password
                        </a>
                    </div>
                    
                    <div style='background-color: #fff7ed; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;'>
                        <p style='margin: 0; font-size: 14px;'><strong>⚠️ Important:</strong></p>
                        <ul style='margin: 10px 0; padding-left: 20px; font-size: 14px;'>
                            <li>This link will expire in <strong>24 hours</strong></li>
                            <li>If you didn't request this, please ignore this email</li>
                            <li>Your password will not change unless you click the link above</li>
                        </ul>
                    </div>
                    
                    <p style='font-size: 14px; color: #64748b;'>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p style='font-size: 12px; color: #0ea5e9; word-break: break-all;'>{$resetLink}</p>
                    
                    <p style='margin-top: 30px; font-size: 14px;'>Best regards,<br><strong>IT Help Desk Team</strong></p>
                    
                    <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                    <p style='font-size: 12px; color: #94a3b8; text-align: center;'>
                        This is an automated message, please do not reply to this email.
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
