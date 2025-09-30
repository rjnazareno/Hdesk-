<?php
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/activity_logger.php';
// Firebase notifications (optional)
if (file_exists('includes/firebase_notifications.php')) {
    require_once 'includes/firebase_notifications.php';
}

// Start session and require login
session_start();
requireLogin();

// Only employees can create tickets
if (isITStaff()) {
    header('Location: dashboard.php');
    exit;
}

// Helper functions
function getUserId() {
    return $_SESSION['user_id'] ?? 0;
}

function getUserName() {
    if (isset($_SESSION['user_data']['name'])) {
        return $_SESSION['user_data']['name'];
    }
    return $_SESSION['username'] ?? 'User';
}

// Initialize user data for template
$userData = [
    'username' => getUserName(),
    'id' => getUserId()
];

$csrfToken = generateCSRFToken();

$success = false;
$error = '';
$ticketId = null;

// Process form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    try {
        // Validate CSRF token
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token');
        }
        
        // Validate and sanitize input
        $subject = trim($_POST['subject'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $priority = trim($_POST['priority'] ?? 'medium');
        
        // Basic validation
        if (empty($subject)) {
            throw new Exception('Subject is required');
        }
        if (empty($description)) {
            throw new Exception('Description is required');
        }
        if (empty($category)) {
            throw new Exception('Category is required');
        }
        
        if (strlen($subject) > 200) {
            throw new Exception('Subject too long (max 200 characters)');
        }
        if (strlen($description) > 5000) {
            throw new Exception('Description too long (max 5000 characters)');
        }
        
        // Insert ticket into database
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO tickets (employee_id, subject, description, category, priority, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'open', NOW())
        ");
        
        if ($stmt->execute([getUserId(), $subject, $description, $category, $priority])) {
            $ticketId = $db->lastInsertId();
            
            // Log the ticket creation activity
            $logger = new ActivityLogger($db);
            $logger->logTicketCreated(getUserId(), 'employee', $ticketId, $subject, $priority, $category);
            
            // Send Firebase notification to IT staff (if available)
            try {
                if (class_exists('FirebaseNotificationSender')) {
                    $notificationSender = new FirebaseNotificationSender();
                    $notificationResult = $notificationSender->sendNewTicketNotification($ticketId);
                    
                    if ($notificationResult['success']) {
                        error_log("New ticket notification sent successfully for ticket {$ticketId}");
                    } else {
                        error_log("New ticket notification failed for ticket {$ticketId}: " . $notificationResult['error']);
                    }
                }
            } catch (Exception $e) {
                error_log("New ticket notification error: " . $e->getMessage());
            }
            
            // Handle file uploads if any
            if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
                $uploadDir = dirname(__DIR__) . '/uploads/tickets/';
                
                // Create upload directory if it doesn't exist
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
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
                        
                        // Basic file validation
                        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
                        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        
                        if (!in_array($fileExt, $allowedTypes)) {
                            throw new Exception('Invalid file type: ' . $file['name']);
                        }
                        
                        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
                            throw new Exception('File too large: ' . $file['name']);
                        }
                        
                        // Generate secure filename
                        $secureFilename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $file['name']);
                        $uploadPath = $uploadDir . $secureFilename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                            // Save attachment record to database
                            $attachStmt = $db->prepare("
                                INSERT INTO ticket_attachments 
                                (ticket_id, original_filename, stored_filename, file_size, mime_type, uploaded_by, user_type)
                                VALUES (?, ?, ?, ?, ?, ?, ?)
                            ");
                            $attachStmt->execute([
                                $ticketId,
                                $file['name'],
                                $secureFilename,
                                $file['size'],
                                $file['type'],
                                getUserId(),
                                'employee'
                            ]);
                        } else {
                            throw new Exception('Failed to upload file: ' . $file['name']);
                        }
                    }
                }
            }
            
            $success = true;
        } else {
            throw new Exception('Failed to create ticket');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Ticket creation error: " . $e->getMessage());
    }
}

// Define basic categories
$categories = [
    ['category_name' => 'Hardware', 'description' => 'Hardware related issues'],
    ['category_name' => 'Software', 'description' => 'Software related issues'],  
    ['category_name' => 'Network', 'description' => 'Network connectivity issues'],
    ['category_name' => 'Account', 'description' => 'Account access issues'],
    ['category_name' => 'Other', 'description' => 'Other technical issues']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape(APP_NAME); ?> - Create Ticket</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="flex items-center">
                        <i class="fas fa-ticket-alt text-blue-600 text-2xl mr-3"></i>
                        <h1 class="text-xl font-semibold text-gray-900"><?php echo escape(APP_NAME); ?></h1>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-gray-700 text-sm font-medium">
                        <i class="fas fa-arrow-left mr-1"></i>Back to Dashboard
                    </a>
                    <span class="text-sm text-gray-500">
                        Employee: <span class="font-medium text-gray-900"><?php echo escape($userData['username']); ?></span>
                    </span>
                    <a href="logout.php" class="text-red-600 hover:text-red-700 text-sm font-medium">
                        <i class="fas fa-sign-out-alt mr-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <?php if ($success): ?>
            <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800">
                            Ticket Created Successfully!
                        </h3>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Your support ticket (#<?php echo escape($ticketId); ?>) has been submitted and our IT team has been notified.</p>
                        </div>
                        <div class="mt-4">
                            <div class="-mx-2 -my-1.5 flex">
                                <a href="view_ticket.php?id=<?php echo escape($ticketId); ?>" 
                                   class="bg-green-50 px-2 py-1.5 rounded-md text-sm font-medium text-green-800 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600">
                                    View Ticket
                                </a>
                                <a href="dashboard.php" 
                                   class="ml-3 bg-green-50 px-2 py-1.5 rounded-md text-sm font-medium text-green-800 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600">
                                    Go to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            Error Creating Ticket
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                            <p><?php echo escape($error); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <div class="bg-white shadow-lg rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-gray-900">
                    <i class="fas fa-plus-circle text-blue-600 mr-2"></i>
                    Create New Support Ticket
                </h2>
                <p class="text-gray-600 mt-1">
                    Please provide detailed information about your IT issue.
                </p>
            </div>

            <form id="createTicketForm" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo escape($csrfToken); ?>">
                <input type="hidden" name="action" value="create_ticket">

                <!-- Subject -->
                <div>
                    <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">
                        Subject <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           id="subject" 
                           name="subject" 
                           required 
                           maxlength="255"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="Brief description of your issue">
                    <p class="text-xs text-gray-500 mt-1">Maximum 255 characters</p>
                </div>

                <!-- Category and Priority Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select id="category" 
                                name="category" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select a category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo escape($category['category_name']); ?>" 
                                        title="<?php echo escape($category['description']); ?>">
                                    <?php echo escape($category['category_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 mb-2">
                            Priority <span class="text-red-500">*</span>
                        </label>
                        <select id="priority" 
                                name="priority" 
                                required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select priority</option>
                            <option value="low">Low - General questions, minor issues</option>
                            <option value="medium" selected>Medium - Standard issues affecting productivity</option>
                            <option value="high">High - Critical issues blocking work</option>
                        </select>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <textarea id="description" 
                              name="description" 
                              required 
                              rows="6"
                              maxlength="5000"
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Please provide detailed information about your issue:&#10;- What happened?&#10;- When did it occur?&#10;- What steps have you tried?&#10;- Any error messages?"></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        <span id="descriptionCount">0</span>/5000 characters
                    </p>
                </div>

                <!-- File Attachments -->
                <div>
                    <label for="attachments" class="block text-sm font-medium text-gray-700 mb-2">
                        Attachments (Optional)
                    </label>
                    <div class="border-2 border-gray-300 rounded-lg p-4">
                        <input type="file" 
                               id="attachments" 
                               name="attachments[]" 
                               multiple 
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt,.zip"
                               class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        <p class="text-xs text-gray-500 mt-2">
                            Select multiple files: PNG, JPG, GIF, PDF, DOC, TXT, ZIP up to 10MB each
                        </p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex flex-col sm:flex-row gap-4 pt-6 border-t border-gray-200">
                    <button type="submit" 
                            id="submitBtn"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-150 ease-in-out flex items-center justify-center">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Submit Ticket
                    </button>
                    
                    <button type="button" 
                            onclick="resetForm()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 font-semibold py-3 px-6 rounded-lg transition duration-150 ease-in-out">
                        <i class="fas fa-undo mr-2"></i>
                        Reset Form
                    </button>
                    
                    <a href="dashboard.php" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-150 ease-in-out text-center">
                        <i class="fas fa-times mr-2"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Simple character counter for description
        document.addEventListener('DOMContentLoaded', function() {
            const descriptionField = document.getElementById('description');
            const countDisplay = document.getElementById('descriptionCount');
            
            if (descriptionField && countDisplay) {
                descriptionField.addEventListener('input', function() {
                    const count = this.value.length;
                    countDisplay.textContent = count;
                    
                    if (count > 4500) {
                        countDisplay.classList.add('text-red-600');
                    } else {
                        countDisplay.classList.remove('text-red-600');
                    }
                });
            }
        });
        
        // Simple form reset function
        function resetForm() {
            document.getElementById('createTicketForm').reset();
            const countDisplay = document.getElementById('descriptionCount');
            if (countDisplay) {
                countDisplay.textContent = '0';
                countDisplay.classList.remove('text-red-600');
            }
        }
    </script>
</body>
</html>