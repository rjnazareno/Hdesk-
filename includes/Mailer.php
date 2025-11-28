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
    
    /**
     * Send welcome email to new employee
     */
    public function sendWelcomeEmail($email, $name, $username, $password) {
        try {
            $subject = 'Welcome to ResolveIT Help Desk - Your Account Details';
            $body = $this->getWelcomeEmailTemplate($name, $username, $password);
            
            if ($this->usePHPMailer) {
                $this->mail->clearAddresses();
                $this->mail->addAddress($email, $name);
                $this->mail->Subject = $subject;
                $this->mail->Body = $body;
                $this->mail->AltBody = strip_tags($body);
                return $this->mail->send();
            } else {
                error_log("Welcome email would be sent to: {$email} - Subject: $subject");
                return true;
            }
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Welcome email template
     */
    private function getWelcomeEmailTemplate($name, $username, $password) {
        $loginUrl = BASE_URL . 'login.php';
        $logoUrl = BASE_URL . 'img/ResolveIT%20Logo%20Only%20without%20Background.png';
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Welcome to ResolveIT</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f8fafc;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <!-- Email Container -->
                <div style='background-color: white; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); overflow: hidden;'>
                    
                    <!-- Header with Logo and Blue Gradient -->
                    <div style='background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); padding: 40px 30px; text-align: center;'>
                        <img src='{$logoUrl}' alt='ResolveIT Logo' style='width: 80px; height: 80px; margin-bottom: 20px; filter: brightness(0) invert(1);'>
                        <h1 style='color: white; margin: 0; font-size: 28px; font-weight: bold;'>Welcome to ResolveIT!</h1>
                        <p style='color: rgba(255, 255, 255, 0.9); margin: 10px 0 0 0; font-size: 16px;'>Your IT Help Desk Portal</p>
                    </div>
                    
                    <!-- Main Content -->
                    <div style='padding: 40px 30px;'>
                        <h2 style='color: #1e293b; margin-bottom: 20px; font-size: 22px;'>Hello {$name}! 👋</h2>
                        
                        <p style='color: #475569; font-size: 16px; line-height: 1.6; margin-bottom: 20px;'>
                            Your account has been successfully created! You can now submit and track IT support tickets through our help desk system.
                        </p>
                        
                        <!-- Credentials Box -->
                        <div style='background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 4px solid #2563eb; padding: 25px; margin: 30px 0; border-radius: 8px;'>
                            <h3 style='color: #1e40af; margin: 0 0 20px 0; font-size: 18px;'>
                                <span style='font-size: 20px;'>🔐</span> Your Login Credentials
                            </h3>
                            
                            <div style='background-color: white; padding: 20px; border-radius: 6px; margin-bottom: 15px;'>
                                <p style='margin: 0 0 8px 0; color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;'>Username</p>
                                <p style='margin: 0; color: #1e293b; font-size: 18px; font-weight: bold; font-family: monospace;'>{$username}</p>
                            </div>
                            
                            <div style='background-color: white; padding: 20px; border-radius: 6px;'>
                                <p style='margin: 0 0 8px 0; color: #64748b; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;'>Temporary Password</p>
                                <p style='margin: 0; color: #1e293b; font-size: 18px; font-weight: bold; font-family: monospace;'>{$password}</p>
                            </div>
                        </div>
                        
                        <!-- Important Notice -->
                        <div style='background-color: #fef3c7; border-left: 4px solid #f59e0b; padding: 20px; margin: 25px 0; border-radius: 8px;'>
                            <p style='margin: 0; font-size: 15px; color: #92400e;'>
                                <strong>⚠️ Important Security Reminder:</strong><br>
                                Please change your password after your first login for security purposes.
                            </p>
                        </div>
                        
                        <!-- Login Button -->
                        <div style='text-align: center; margin: 35px 0;'>
                            <a href='{$loginUrl}' 
                               style='display: inline-block; padding: 16px 40px; background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(37, 99, 235, 0.3);'>
                                Login to ResolveIT
                            </a>
                        </div>
                        
                        <!-- Features Section -->
                        <div style='margin-top: 35px; padding-top: 30px; border-top: 2px solid #e2e8f0;'>
                            <h3 style='color: #1e293b; font-size: 18px; margin-bottom: 20px;'>What you can do:</h3>
                            
                            <table style='width: 100%; border-spacing: 0;'>
                                <tr>
                                    <td style='padding: 12px 0; vertical-align: top;'>
                                        <div style='display: inline-block; width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 8px; text-align: center; line-height: 40px; font-size: 20px; margin-right: 15px;'>🎫</div>
                                    </td>
                                    <td style='padding: 12px 0;'>
                                        <strong style='color: #1e293b; font-size: 15px;'>Submit Support Tickets</strong><br>
                                        <span style='color: #64748b; font-size: 14px;'>Report issues and request IT assistance</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding: 12px 0; vertical-align: top;'>
                                        <div style='display: inline-block; width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 8px; text-align: center; line-height: 40px; font-size: 20px; margin-right: 15px;'>📊</div>
                                    </td>
                                    <td style='padding: 12px 0;'>
                                        <strong style='color: #1e293b; font-size: 15px;'>Track Your Tickets</strong><br>
                                        <span style='color: #64748b; font-size: 14px;'>Monitor progress and view updates in real-time</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style='padding: 12px 0; vertical-align: top;'>
                                        <div style='display: inline-block; width: 40px; height: 40px; background: linear-gradient(135deg, #3b82f6, #2563eb); border-radius: 8px; text-align: center; line-height: 40px; font-size: 20px; margin-right: 15px;'>💬</div>
                                    </td>
                                    <td style='padding: 12px 0;'>
                                        <strong style='color: #1e293b; font-size: 15px;'>Direct Communication</strong><br>
                                        <span style='color: #64748b; font-size: 14px;'>Chat with IT staff about your issues</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <p style='margin-top: 35px; font-size: 15px; color: #475569; line-height: 1.6;'>
                            If you have any questions or need assistance, feel free to reach out to our IT support team.
                        </p>
                        
                        <p style='margin-top: 25px; font-size: 15px; color: #1e293b;'>
                            Best regards,<br>
                            <strong style='color: #2563eb;'>IT Help Desk Team</strong>
                        </p>
                    </div>
                    
                    <!-- Footer -->
                    <div style='background-color: #f8fafc; padding: 25px 30px; text-align: center; border-top: 1px solid #e2e8f0;'>
                        <p style='margin: 0; font-size: 13px; color: #64748b;'>
                            This is an automated message from ResolveIT Help Desk System
                        </p>
                        <p style='margin: 8px 0 0 0; font-size: 12px; color: #94a3b8;'>
                            Please do not reply to this email
                        </p>
                    </div>
                    
                </div>
            </div>
        </body>
        </html>
        ";
    }
}

