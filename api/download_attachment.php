<?php
/**
 * Download Attachment API Endpoint
 */
require_once '../includes/auth.php';
require_once '../includes/security.php';
require_once '../config/database.php';

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    header('Location: login.php');
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

try {
    $attachmentId = intval($_GET['id'] ?? 0);
    
    if ($attachmentId <= 0) {
        throw new Exception('Invalid attachment ID');
    }
    
    $database = Database::getInstance();
    $db = $database->getConnection();
    
    // Get attachment info with ticket details
    $stmt = $db->prepare("
        SELECT 
            ta.*,
            t.employee_id
        FROM ticket_attachments ta
        JOIN tickets t ON ta.ticket_id = t.ticket_id
        WHERE ta.attachment_id = ?
    ");
    $stmt->execute([$attachmentId]);
    $attachment = $stmt->fetch();
    
    if (!$attachment) {
        throw new Exception('Attachment not found');
    }
    
    // Check permissions - employees can only download attachments from their own tickets
    if ($auth->isEmployee() && $attachment['employee_id'] != $auth->getUserId()) {
        throw new Exception('Access denied');
    }
    
    $filePath = UPLOAD_DIR . $attachment['stored_filename'];
    
    // Check if file exists
    if (!file_exists($filePath)) {
        throw new Exception('File not found on server');
    }
    
    // Log download
    error_log("Attachment downloaded: {$attachment['original_filename']} by {$auth->getUserType()} " . $auth->getUserId());
    
    // Set headers for file download
    header('Content-Type: ' . ($attachment['mime_type'] ?: 'application/octet-stream'));
    header('Content-Disposition: attachment; filename="' . addslashes($attachment['original_filename']) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Output file
    readfile($filePath);

} catch (Exception $e) {
    error_log("Download attachment error: " . $e->getMessage());
    http_response_code(404);
    echo 'File not found: ' . $e->getMessage();
}
?>