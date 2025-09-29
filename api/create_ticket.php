<?php
/**
 * Create Ticket API Endpoint
 */
require_once '../includes/auth.php';
require_once '../includes/security.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check rate limiting
if (!checkRateLimit('create_ticket', 10, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many requests. Please try again later.']);
    exit;
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

try {
    // Get and sanitize input
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $description = sanitizeInput($_POST['description'] ?? '');
    $category = sanitizeInput($_POST['category'] ?? 'General');
    $priority = sanitizeInput($_POST['priority'] ?? 'low');
    
    // Validate input
    if (empty($subject) || empty($description)) {
        throw new Exception('Subject and description are required');
    }
    
    if (strlen($subject) > 255) {
        throw new Exception('Subject must be 255 characters or less');
    }
    
    if (!in_array($priority, ['low', 'medium', 'high'])) {
        $priority = 'low';
    }
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Validate category exists
    $stmt = $db->prepare("SELECT category_name FROM ticket_categories WHERE category_name = ? AND is_active = 1");
    $stmt->execute([$category]);
    if (!$stmt->fetch()) {
        $category = 'General'; // Default fallback
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Insert ticket
    $stmt = $db->prepare("
        INSERT INTO tickets (employee_id, subject, description, category, priority)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $auth->getUserId(),
        $subject,
        $description,
        $category,
        $priority
    ]);
    
    $ticketId = $db->lastInsertId();
    
    // Handle file attachments if any
    $attachments = [];
    if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
        $fileCount = count($_FILES['attachments']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['attachments']['error'][$i] === UPLOAD_ERR_OK) {
                $file = [
                    'name' => $_FILES['attachments']['name'][$i],
                    'type' => $_FILES['attachments']['type'][$i],
                    'tmp_name' => $_FILES['attachments']['tmp_name'][$i],
                    'error' => $_FILES['attachments']['error'][$i],
                    'size' => $_FILES['attachments']['size'][$i]
                ];
                
                // Validate file
                $fileErrors = validateFileUpload($file);
                if (!empty($fileErrors)) {
                    throw new Exception('File validation error: ' . implode(', ', $fileErrors));
                }
                
                // Generate secure filename
                $secureFilename = generateSecureFilename($file['name']);
                $uploadPath = UPLOAD_DIR . $secureFilename;
                
                // Move file
                if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                    // Save to database
                    $stmt = $db->prepare("
                        INSERT INTO ticket_attachments 
                        (ticket_id, original_filename, stored_filename, file_size, mime_type, uploaded_by, user_type)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $stmt->execute([
                        $ticketId,
                        $file['name'],
                        $secureFilename,
                        $file['size'],
                        $file['type'],
                        $auth->getUserId(),
                        $auth->getUserType()
                    ]);
                    
                    $attachments[] = $file['name'];
                }
            }
        }
    }
    
    // Commit transaction
    $db->commit();
    
    // Log ticket creation
    error_log("Ticket created: ID {$ticketId} by employee " . $auth->getUserId());
    
    // Prepare success response
    $response = [
        'success' => true,
        'message' => 'Ticket created successfully',
        'ticket_id' => $ticketId,
        'attachments' => $attachments
    ];
    
    // Send email notifications (async would be better in production)
    try {
        require_once '../includes/email.php';
        $emailService = new EmailService();
        $emailService->sendTicketCreatedNotification($ticketId);
    } catch (Exception $e) {
        error_log("Email notification error: " . $e->getMessage());
        // Don't fail the ticket creation if email fails
    }
    
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction if active
    if ($db && $db->inTransaction()) {
        $db->rollback();
    }
    
    error_log("Create ticket error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>