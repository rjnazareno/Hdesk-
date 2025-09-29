<?php
/**
 * Upload Attachment API Endpoint
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
if (!checkRateLimit('upload_attachment', 10, 3600)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Too many uploads. Please try again later.']);
    exit;
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

try {
    $ticketId = intval($_POST['ticket_id'] ?? 0);
    
    // Validate input
    if ($ticketId <= 0) {
        throw new Exception('Invalid ticket ID');
    }
    
    // Check if file was uploaded
    if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }
    
    $file = $_FILES['attachment'];
    
    // Validate file
    $fileErrors = validateFileUpload($file);
    if (!empty($fileErrors)) {
        throw new Exception('File validation error: ' . implode(', ', $fileErrors));
    }
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Check if ticket exists and user has permission
    $stmt = $db->prepare("
        SELECT t.*, e.employee_id
        FROM tickets t
        LEFT JOIN employees e ON t.employee_id = e.id
        WHERE t.ticket_id = ?
    ");
    $stmt->execute([$ticketId]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Ticket not found']);
        exit;
    }
    
    // Check permissions - employees can only upload to their own tickets
    if ($auth->isEmployee() && $ticket['employee_id'] != $auth->getUserId()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Check if ticket is closed
    if ($ticket['status'] === 'closed') {
        throw new Exception('Cannot upload attachments to closed tickets');
    }
    
    // Generate secure filename
    $secureFilename = generateSecureFilename($file['name']);
    $uploadPath = UPLOAD_DIR . $secureFilename;
    
    // Create upload directory if it doesn't exist
    if (!is_dir(UPLOAD_DIR)) {
        if (!mkdir(UPLOAD_DIR, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception('Failed to save uploaded file');
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Save attachment info to database
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
    
    $attachmentId = $db->lastInsertId();
    
    // Update ticket modified time
    $stmt = $db->prepare("UPDATE tickets SET updated_at = CURRENT_TIMESTAMP WHERE ticket_id = ?");
    $stmt->execute([$ticketId]);
    
    // Add a response indicating file upload
    $uploadMessage = "File uploaded: " . $file['name'];
    $stmt = $db->prepare("
        INSERT INTO ticket_responses (ticket_id, responder_id, responder_type, message)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([$ticketId, $auth->getUserId(), $auth->getUserType(), $uploadMessage]);
    
    // Commit transaction
    $db->commit();
    
    // Log file upload
    error_log("File uploaded: {$file['name']} to ticket {$ticketId} by {$auth->getUserType()} " . $auth->getUserId());
    
    // Get uploader name
    if ($auth->getUserType() === 'employee') {
        $stmt = $db->prepare("SELECT COALESCE(fname, username) as name FROM employees WHERE id = ?");
    } else {
        $stmt = $db->prepare("SELECT name FROM it_staff WHERE staff_id = ?");
    }
    $stmt->execute([$auth->getUserId()]);
    $uploader = $stmt->fetch();
    $uploaderName = $uploader['name'] ?? 'Unknown';
    
    $response = [
        'success' => true,
        'message' => 'File uploaded successfully',
        'attachment' => [
            'attachment_id' => $attachmentId,
            'original_filename' => $file['name'],
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'created_at' => date('Y-m-d H:i:s'),
            'uploader' => [
                'id' => $auth->getUserId(),
                'type' => $auth->getUserType(),
                'name' => $uploaderName
            ],
            'download_url' => 'api/download_attachment.php?id=' . $attachmentId
        ]
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    
    // Clean up uploaded file if database insert failed
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    
    error_log("Upload attachment error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>